<?php

class SEO_ extends SEOBase
{
    /**
     * Контент файла robots.txt
     */
    public function robots_txt()
    {
        $filter = '';
        if (Geo::urlType() == Geo::URL_SUBDOMAIN) {
            $filter = Geo::filterUrl('keyword');
        }

        $fileTemplate = function() use ($filter) {
            $template = config::get('seo_robots_template', '');
            $template = strtr($template, array(
                # подменяем макрос {sitemap}
                '{sitemap}' => static::urlSEO($filter).'sitemap.xml',
            ));
            return $template;
        };

        header('Content-Type: text/plain');
        echo $fileTemplate();
        bff::shutdown();
    }

    /**
     * Контент файла Sitemap.xml
     */
    public function sitemap_xml()
    {
        $path = static::pathSEO(); # путь для хранения файлов

        $geo = array('city' => 0, 'region' => 0, 'country' => 0, 'id' => 0, 'keyword' => '');
        if (Geo::urlType() == Geo::URL_SUBDOMAIN) {
            $geo = Geo::filterUrl();
        }
        $url = static::urlSEO($geo['keyword']);

        $file = 'sitemap';
        if ($geo['keyword']) {
            $file = $geo['keyword'].'_'.$file;
        }
        $ext = '.xml';
        $modified = 0;
        if (file_exists($path.$file.$ext)) {
            $modified = filemtime($path.$file.$ext);
            if ($this->input->get('check', TYPE_UINT)){
                # проверка скачивания, не пересоздавать
                $modified = time();
            }
        }
        if ($modified + static::sitemapGenerateTimeout() < time()) {
            # создадим каталог, если не существует
            if ( ! file_exists($path)) {
                @mkdir($path, 0755, true);
            }
            if ( ! file_exists($path)) {
                $this->errors->error404();
            }

            if (Geo::urlType() == Geo::URL_SUBDOMAIN){
                $this->generateSitemapXMLSubdomains($geo, $file, $path, $url);
            } else {
                $this->generateSitemapXMLSingleDomain($file, $path, $url);
            }
        }

        if ( ! file_exists($path.$file.$ext)) {
            $this->errors->error404();
        }

        # отдадим содержимое файла
        header('Content-type: text/xml');
        readfile($path.$file.$ext);
        bff::shutdown();
    }

    /**
     * Контент файла {region_}sitemap{number}.xml - если Sitemap.xml длинный и разбивается на части
     * @param string $gzip расширение сжатого файла
     */
    public function sitemap_xml_part($gzip = '')
    {
        $data = $this->input->getm(array(
            'region' => TYPE_NOTAGS,
            'number' => TYPE_UINT,
        ));
        $path = static::pathSEO().$data['region'].'sitemap'.$data['number'].'.xml'.$gzip;

        if ( ! file_exists($path)) {
            $this->errors->error404();
        }

        if ($data['region']) {
            $filter = Geo::filterUrl('keyword');
            if ($filter != trim($data['region'], '_')) {
                $this->errors->error404();
            }
        }

        header('Content-type: text/xml');
        if ($gzip) {
            header('Content-Encoding: gzip');
        }
        readfile($path);
        bff::shutdown();
    }

    /**
     * Контент файла {region_}sitemap{number}.xml.gz - если sitemap.xml длинный, разбивается на части и архивируется
     */
    public function sitemap_xml_part_gz()
    {
        $this->sitemap_xml_part('.gz');
    }

    /**
     * Cron: Формирование файла Sitemap.xml
     * Рекомендуемый период: раз в сутки
     */
    public function cronSitemapXML()
    {
        if (!bff::cron()) return;

        $file = 'sitemap';
        if ($this->robotsEnabled(true)) {
            # новая схема
            $path = static::pathSEO(); # путь для хранения файлов
            $url = static::urlSEO();

            if (Geo::urlType() == Geo::URL_SUBDOMAIN) {
                # для основного домена
                $geo = array('city' => 0, 'region' => 0, 'country' => 0, 'id' => 0, 'keyword' => '');
                $this->generateSitemapXMLSubdomains($geo, $file, $path, $url);

                # для поддоменов, в которых есть объявления
                $counters = BBS::model()->itemsCountByFilter(array(
                    'cat_id' => 0,
                    'delivery' => 0,
                    ':reg' => 'region_id > 0'
                ), array('region_id'), false);
                $fields = array(
                    Geo::lvlCountry => 'country',
                    Geo::lvlRegion  => 'region',
                    Geo::lvlCity    => 'city',
                );
                foreach($counters as $v) {
                    $region = Geo::regionData($v['region_id']);
                    if (empty($region)) continue;
                    $geo = array('city' => 0, 'region' => 0, 'country' => 0, 'id' => $v['region_id'], 'keyword' => $region['keyword']);
                    $geo[ $fields[ $region['numlevel'] ] ] = $v;
                    $this->generateSitemapXMLSubdomains($geo, $region['keyword'].'_sitemap', $path, static::urlSEO($region['keyword']));
                }

            } else {
                # для основного домена
                $this->generateSitemapXMLSingleDomain($file, $path, $url);
            }
        } else {
            # старая схема
            $this->generateSitemapXMLSingleDomain($file, bff::path(''), bff::url(''));
        }
    }

    /**
     * Формирование Sitemap.xml для мультидоменной конфигурации сайта
     * @param array $geo фильтр по региону
     * @param string $file название файла
     * @param string $path путь к файлу
     * @param string $url урл к файлу
     */
    protected function generateSitemapXMLSubdomains($geo, $file, $path, $url)
    {
        # строим XML
        $data = array();

        if ( ! $geo['id']) {
            # для основного домена

            # Посадочные страницы
            if (static::landingPagesEnabled()) {
                # непривязанные к модулям
                $data['landingpages_not_joined'] = $this->model->landingpagesSitemapXmlData(true, '', [
                    'filter' => ['notJoined' => 1],
                ]);
                # с не пустыми категориями для всех регионов
                $data['landingpages_bbs_items'] = $this->model->landingpagesSitemapXmlData(true, '', [
                    'filter' => ['withBBSItems' => 0],
                ]);
            }

            # Блог
            if (bff::moduleExists('blog')) {
                $data['blog'] = Blog::model()->postsSitemapXmlData();
            }

        } else {
            # для поддоменов (страна / регион / город)

            # Посадочные страницы
            if (static::landingPagesEnabled()) {
                # только для категорий с объявлениями в указанном регионе
                $data['landingpages_bbs_items'] = $this->model->landingpagesSitemapXmlData(true, '', [
                    'filter' => ['withBBSItems' => $geo['id']],
                    'subdomains' => [$geo['keyword']],
                ]);
                # непривязанные к модулям
                $data['landingpages_not_joined'] = $this->model->landingpagesSitemapXmlData(true, '', [
                    'filter' => ['notJoined' => 1],
                    'subdomains' => [$geo['keyword']],
                ]);
            }

            if ( ! empty($geo['city'])) {
                # для города добавляем объявления в этом городе
                $sql = array();
                $region = Geo::model()->regionParents($geo['id']);
                foreach($region['db'] as $k => $v) {
                    if (empty($v)) {
                        unset($region['db'][$k]);
                    }
                }
                $sql[':reg_path'] = array('reg_path LIKE :regionQuery', ':regionQuery' => '-'.join('-', $region['db']).'-');
                $data['items'] = BBS::model()->itemsSitemapXmlData($sql);
            }
        }

        # Дополнительно
        $data = bff::filter('site.cron.sitemapXML', $data, array('geo' => $geo, 'file' => $file, 'path' => $path, 'url' => $url));

        # Строим XML
        ini_set('memory_limit', '2048M');
        $sitemap = new CSitemapXML();
        $sitemap->buildIterator($data, $file, $path, $url, config::sysAdmin('site.sitemapXML.gzip', true, TYPE_BOOL));
    }

    /**
     * Формирование Sitemap.xml для однодоменной конфигурации сайта
     * @param string $file название файла
     * @param string $path путь к файлу
     * @param string $url урл к файлу
     */
    protected function generateSitemapXMLSingleDomain($file, $path, $url)
    {
        $data = array();

        # Посадочные страницы
        if (static::landingPagesEnabled()) {
            $data['landingpages'] = $this->model->landingpagesSitemapXmlData();
        }

        # Блог
        if (bff::moduleExists('blog')) {
            $data['blog'] = Blog::model()->postsSitemapXmlData();
        }

        # Объявления
        $data['items'] = BBS::model()->itemsSitemapXmlData();

        # Дополнительно
        $data = bff::filter('site.cron.sitemapXML', $data);

        # Строим XML
        ini_set('memory_limit', '2048M');
        $sitemap = new CSitemapXML();
        $sitemap->setPing(config::sysAdmin('site.sitemapXML.ping', true, TYPE_BOOL));
        $sitemap->buildIterator($data, $file, $path, $url, config::sysAdmin('site.sitemapXML.gzip', true, TYPE_BOOL));
    }

    /**
     * Расписание запуска крон задач
     * @return array
     */
    public function cronSettings()
    {

        return array(
            'cronSitemapXML' => array('period' => '0 2 * * *'),
        );
    }

}