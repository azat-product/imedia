<?php

abstract class SiteBase_ extends SiteModule
{
    /** @var SiteModel */
    public $model = null;

    /**
     * Размеры логотипа
     */
    const LOGO_SIZE_NORMAL = 'normal';
    const LOGO_SIZE_SMALL  = 'small';

    public function init()
    {
        parent::init();

        bff::autoloadEx(array(
            'SiteCurrencyRate' => array('app', 'modules/site/site.currency.rate.php'),
        ));

        # Проверка переключение языка сайта на фронтенде
        if ( ! bff::adminPanel() && User::id()) {
            $lng = $this->locale->getLanguageCookie();
            if ($lng != User::lang()) {
                Users::i()->localeChange($lng);
            }
        }

        # Таблицы локализации
        if (FORDEV) {
            bff::hookAdd('dev.locale.translate.modules', function($list){
                $list[] = 'bbs';
                $list[] = 'shops';
                $list[] = 'geo';
                return $list;
            });
        }
    }

    /**
     * Использовать капчу из плагина
     * @param string $page page kwyword
     * @return bool
     */
    public static function captchaCustom($page = '')
    {
        if (bff::filter('captcha.custom.active')) {
            return bff::filter('captcha.custom.active', $page);
        }
        return false;
    }

    public static function currencyOptions($nSelectedID, $mEmpty = false)
    {
        $aCurrency = static::model()->currencyData(false);

        return HTML::selectOptions($aCurrency, $nSelectedID, $mEmpty, 'id', 'title_short');
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts доп. параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        $url = $base = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # главная страница
            case 'index':
                $url .= '/';
                break;
            # главная страница
            case 'index-geo':
                $url = Geo::url($opts, $dynamic);
                break;
            # статическая страница
            case 'page':
                $url .= '/' . $opts['filename'] . static::$pagesExtension;
                break;
            # карта сайта
            case 'sitemap':
                $url .= '/sitemap/';
                break;
            # страница "Услуги"
            case 'services':
                $url .= '/services/';
                break;
        }
        return bff::filter('site.url', $url, array('key'=>$key, 'opts'=>$opts, 'dynamic'=>$dynamic, 'base'=>$base));
    }

    /**
     * Дополнительные параметры для редиректов
     * @param array $options доп. параметры
     * @param array $settings настройки: 'type'
     * @return array
     */
    public static function urlExtra(array $options = array(), array $settings = array())
    {
        if (Geo::urlType() == Geo::URL_SUBDIR) {
            $options['region'] = array(
                'value' => function($key, $options) use ($settings) {
                    return Geo::filterUrl('keyword');
                },
                'position' => 2,
            );
        }

        return bff::filter('site.url.extra', parent::urlExtra($options, $settings));
    }

    /**
     * Описание index шаблонов страниц
     * @param string $templateKey ключ шаблона tpl
     * @return array
     */
    public static function indexTemplates($templateKey = '')
    {
        $aTemplates = bff::filter('site.index.templates', array(
            'index.default' => array('title' => _t('site', 'Обычный'), 'map' => false, 'regions' => false),
            'index.regions' => array('title' => _t('site', 'Обычный + регионы'), 'map' => false, 'regions' => true),
            'index.map1'    => array('title' => _t('site', 'Карта №1'), 'map' => true, 'regions' => true),
            'index.map2'    => array('title' => _t('site', 'Карта №2'), 'map' => true, 'regions' => false),
            'index.map3'    => array('title' => _t('site', 'Карта №3'), 'map' => true, 'regions' => true)
        ));
        $i = 0;
        foreach ($aTemplates as $k=>&$v) {
            $v['id'] = $i++;
            $v['tpl'] = $k;
        } unset($v);
        if ($templateKey && isset($aTemplates[$templateKey])) {
            return $aTemplates[$templateKey];
        }

        return $aTemplates;
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        return array(
            'pages' => array(
                'index'         => array(
                    't'      => _t('site','Главная страница'),
                    'i'      => true,
                    'macros' => array(),
                    'fields' => array(
                        'titleh1' => array(
                            't'      => _t('site','Приветствие (заголовок H1)'),
                            'type'   => 'text',
                            'before' => true,
                        ),
                        'seotext' => array(
                            't'    => _t('','SEO текст'),
                            'type' => 'wy',
                        )
                    ),
                ),
                'index-region'  => array(
                    't'      => _t('site','Главная страница (регион)'),
                    'i'      => true,
                    'macros' => array(
                        'region' => array('t' => _t('site','Регион поиска')),
                    ),
                    'fields' => array(
                        'titleh1' => array(
                            't'      => _t('site','Приветствие (заголовок H1)'),
                            'type'   => 'text',
                            'before' => true,
                        ),
                        'seotext' => array(
                            't'    => _t('','SEO текст'),
                            'type' => 'wy',
                        )
                    ),
                    'visible' => !config::sysAdmin('bbs.index.region.search', false, TYPE_BOOL),
                ),
                'page-view'     => array(
                    't'       => _t('site','Статические страницы'),
                    'i'       => true,
                    'inherit' => true,
                    'macros'  => array(
                        'title' => array('t' => _t('site','Заголовок страницы')),
                    ),
                ),
                'sitemap'       => array(
                    't'      => _t('site','Карта сайта'),
                    'i'      => true,
                    'macros' => array(),
                    'fields' => array(
                        'breadcrumb' => array(
                            't'    => _t('','Хлебная крошка'),
                            'type' => 'text',
                        ),
                        'titleh1' => array(
                            't'    => _t('','Заголовок H1'),
                            'type' => 'text',
                        ),
                        'seotext' => array(
                            't'    => _t('','SEO текст'),
                            'type' => 'wy',
                        )
                    ),
                ),
                'services'      => array(
                    't'      => _t('site','Страница "Услуги"'),
                    'i'      => true,
                    'macros' => array(),
                    'fields'  => array(
                        'titleh1' => array(
                            't'    => _t('','Заголовок H1'),
                            'type' => 'text',
                        ),
                        'seotext' => array(
                            't'    => _t('','SEO текст'),
                            'type' => 'wy',
                        ),
                    ),
                ),
                'contacts-form' => array(
                    't'      => _t('site','Форма контактов'),
                    'i'      => true,
                    'macros' => array(),
                    'fields' => array(
                        'breadcrumb' => array(
                            't'    => _t('','Хлебная крошка'),
                            'type' => 'text',
                        ),
                    ),
                ),
                'offline'     => array(
                    't'       => _t('site','Выключение сайта'),
                    'i'       => true,
                    'macros'  => array(),
                ),
            ),
        );
    }

    /**
     * Логотип сайта
     * Фильтры: 'site.logo.url', 'site.logo.url.{size}.{position}'
     * @param string $position информация о позиции отображения логотипа
     * @param string $size размер логотипа: self::LOGO_SIZE_NORMAL, self::LOGO_SIZE_SMALL
     * @return string URL требуемого логотипа
     */
    public static function logoURL($position = '', $size = self::LOGO_SIZE_NORMAL)
    {
        static $list;
        if (!isset($list)) {
            $list = bff::filterSys('site.logo.url.sizes', array(
                self::LOGO_SIZE_NORMAL => '/img/do-logo.png',
                self::LOGO_SIZE_SMALL  => '/img/do-logo-small.png',
            ));
        }
        if (!isset($list[$size])) {
            $size = key($list);
        }
        $url = bff::filterSys('site.logo.url.'.$position, $list[$size], $size);
        return (mb_stripos($url, SITEURL_STATIC) === 0 ? $url : bff::url($url));
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            PATH_PUBLIC.'files' => 'dir-only', # sitemap.xml
            PATH_PUBLIC.'files'.DS.'sitemap.xml' => 'file-e', # файл sitemap.xml
        ));
    }
}