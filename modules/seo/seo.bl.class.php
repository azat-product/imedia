<?php

abstract class SEOBase_ extends SEOModule
{
    /** @var SEOModel */
    public $model = null;

    const ROBOTS_FILE  = 'robots.txt';
    const SITEMAP_FILE = 'sitemap.xml';

    /**
     * Рекомендуемое содержимое файла robots.txt
     * @return string
     */
    public static function robotsTemplate()
    {
        return "User-agent: *\nDisallow: /admin\nDisallow: /cabinet/\nDisallow: */?c\nDisallow: /*.php\nDisallow: *?print\nDisallow: /away/\nDisallow: /shop/*/contact/\nDisallow: /shop/promote\nDisallow: /item/promote\nDisallow: /rss/\nSitemap: {sitemap}";
    }

    /**
     * Путь для хранения файлов SEO
     * @return string
     */
    public static function pathSEO()
    {
        return bff::path('seo');
    }

    /**
     * URL к файлам SEO
     * @param string $subDomain поддомен (опционально)
     * @return string
     */
    public static function urlSEO($subDomain = '')
    {
        return \Request::scheme().'://'.($subDomain ? $subDomain.'.' : '').SITEHOST.'/';
    }

    /**
     * Время перегенерации файла sitemap.xml
     * @return mixed
     */
    public static function sitemapGenerateTimeout()
    {
        return config::sysAdmin('seo.sitemap.generate.timeout', 86400, TYPE_UINT);
    }

    /**
     * Обновить файл Sitemap.xml принудительно
     * @return bool
     */
    public static function sitemapRefresh()
    {
        $cronManager = bff::cronManager();
        if ( ! $cronManager->isEnabled()) {
            return false;
        }
        return $cronManager->executeOnce('seo', 'cronSitemapXML');
    }

    /**
     * Проверка работает или нет механизм изменения файла robots.txt
     * @param bool $resetCache сбросить кеш
     * @param array $msg @ref сообщения об ошибках и рекомендации
     * @return bool
     */
    public function robotsEnabled($resetCache = false, & $msg = array())
    {
        $publicDir = str_replace(PATH_BASE, DS, PATH_PUBLIC);

        $robots = PATH_PUBLIC.static::ROBOTS_FILE;
        if (file_exists($robots)) {
            $msg[] = _t('seo', 'Для возможности управления данными настройками вам необходимо удалить исходный файл [file] [delete_link]', array(
                'file' => '<code class="j-tooltip" data-placement="top" data-toggle="tooltip" title="'.$robots.'">'.$publicDir.static::ROBOTS_FILE.'</code>',
                'delete_link' => '<a href="javascript:" class="btn btn-mini btn-danger pull-right j-delete-robots">'._te('', 'Delete').'</a>',
            ));
            config::save('seo_robots_template', file_get_contents($robots));
            return false;
        }

        $sitemap = PATH_PUBLIC.static::SITEMAP_FILE;
        if (file_exists($sitemap)) {
            $msg[] = _t('seo', 'Для возможности управления данными настройками вам необходимо удалить исходный файл [file] [delete_link]', array(
                'file' => '<code class="j-tooltip" data-placement="top" data-toggle="tooltip" title="'.$sitemap.'">'.$publicDir.static::SITEMAP_FILE.'</code>',
                'delete_link' => '<a href="javascript:" class="btn btn-mini btn-danger pull-right j-delete-sitemap">'._te('', 'Delete').'</a>',
            ));
            return false;
        }

        $allow = config::get('seo_robots_rewrite', false);

        if ($resetCache) {
            $url = $this->router->url('seo-robots-txt');
            $returnCode = 1;
            \bff\utils\Files::downloadFile($url, false, ['setErrors' => false, 'returnCode' => & $returnCode]);
            $result = $returnCode >= 200 && $returnCode <= 300;

            if ( ! $result) {
                $msg[] = _t('seo', 'Проверьте настройки сервера, при текущих настройках php не обрабатывает обращения к файлу <b>Robots.txt</b> поисковыми роботами.  [a]Подробнее</a>
                                <div class="displaynone j-more-bl" style="margin-top: 5px;">Если в настройках Nginx есть следующая строка:
                                <br/><code> location = /robots.txt  { access_log off; log_not_found off;} </code><br/>
                                Заметите её на следующую:
                                <br/><code> location = /robots.txt  { access_log off; log_not_found off; try_files $uri @rewrites;} </code></div>', array(
                    'a' => '<a href="javascript:" class="desc ajax j-show-more">',
                ));
            } else {
                $url = $this->router->url('seo-sitemap-xml', ['check'=>1]);
                $returnCode = 1;
                \bff\utils\Files::downloadFile($url, false, ['setErrors' => false, 'returnCode' => & $returnCode]);
                $result = $returnCode >= 200 && $returnCode <= 300;

                if ( ! $result) {
                    $msg[] = _t('seo', 'Проверьте настройки кешируемых web-сервером файлов, при текущих настройках php не обрабатывает обращения к файлу <b>Sitemap.xml</b> поисковыми роботами.');
                }
            }

            $allow = $result;
            config::save('seo_robots_rewrite', $allow);
        }

        return $allow;
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        $dirs[static::pathSEO()] = 'dir-only';
        return array_merge(parent::writableCheck(), $dirs);
    }

}