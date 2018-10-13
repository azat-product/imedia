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

        # Позиции счетчиков и кода
        if (bff::adminPanel()) {
            bff::hookAdd('site.admin.counters.position.list', function($list){
                if (sizeof($list) === 1) {
                    array_unshift($list,
                        ['id' => static::COUNTERS_POS_HEAD, 'title' => _t('site', 'в блоке head')],
                        ['id' => static::COUNTERS_POS_BODY_START, 'title' => _t('site', 'после открывающего body')],
                        ['id' => static::COUNTERS_POS_BODY_FINISH, 'title' => _t('site', 'перед закрывающим body')]
                    );
                }
                return $list;
            });
        } else {
            if ($this->isGET()) {
                $countersList = $this->model->countersViewByPosition();
                if ( ! empty($countersList[static::COUNTERS_POS_HEAD])) {
                    bff::hooks()->viewBlock('head', function ($content) use (&$countersList) {
                        foreach ($countersList[static::COUNTERS_POS_HEAD] as $data) {
                            $content .= $data['code'];
                        }
                        return $content;
                    });
                }
                bff::hooks()->viewBlock('body', function($content) use (&$countersList) {
                    if ( ! empty($countersList[static::COUNTERS_POS_BODY_START])) {
                        foreach ($countersList[static::COUNTERS_POS_BODY_START] as $data) {
                            $content = $data['code'].$content;
                        }
                    }
                    if ( ! empty($countersList[static::COUNTERS_POS_BODY_FINISH])) {
                        foreach ($countersList[static::COUNTERS_POS_BODY_FINISH] as $data) {
                            $content .= $data['code'];
                        }
                    }
                    return $content;
                });
            }
        }

        # Название сайта
        SiteHooks::title(function($title, $position, $language, $default){
            switch ($position) {
                case 'seo.template.macros': {
                    $title = config::get('title_seo_'.$language, $title);
                } break;
                case 'sendmail.template.macros': {
                    $title = config::get('title_sendmail_'.$language, $title);
                } break;
            }
            return $title;
        }, 4);
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
        switch ($key) {
            # главная страница + geo
            case 'index-geo':
                return Geo::url($opts, $dynamic);
                break;
        }
        return bff::router()->url($key, $opts, ['dynamic'=>$dynamic,'module'=>'site']);
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
                    if ( ! isset($settings[$key])) {
                        $settings[$key] = Geo::filterUrl('keyword');
                    }
                    return $settings[$key];
                },
                'position' => 2,
            );
        }

        return bff::filter('site.url.extra', parent::urlExtra($options, $settings));
    }

    /**
     * Описание index шаблонов страниц
     * @param string|array $templateKey ключ шаблона tpl (string) или список имплементированных шаблонов
     * @return array
     */
    public static function indexTemplates($templateKey = '')
    {
        $templatesList = bff::filter('site.index.templates', array(
            'index.default' => array('title' => _t('site', 'Обычный'), 'map' => false, 'regions' => false),
            'index.regions' => array('title' => _t('site', 'Обычный + регионы'), 'map' => false, 'regions' => true),
            'index.map1'    => array('title' => _t('site', 'Карта №1'), 'map' => true, 'regions' => true),
            'index.map2'    => array('title' => _t('site', 'Карта №2'), 'map' => true, 'regions' => false),
            'index.map3'    => array('title' => _t('site', 'Карта №3'), 'map' => true, 'regions' => true),
        ));
        if (is_array($templateKey) && ! empty($templateKey)) {
            $finalList = array();
            foreach ($templateKey as $key=>$params) {
                if (is_integer($key) && is_string($params)) {
                    $key = $params;
                }
                if (is_array($params)) {
                    $finalList[$key] = $params;
                } else if (array_key_exists($key, $templatesList)) {
                    $finalList[$key] = $templatesList[$key];
                }
            }
            $templatesList = $finalList;
            $templateKey = '';
        }
        foreach ($templatesList as $key=>&$template) {
            $template['key'] = $key;
            if (empty($template['file'])) {
                $template['file'] = $key;
            }
        } unset($template);

        if ( ! empty($templateKey)) {
            if (empty($templatesList[$templateKey])) {
                reset($templatesList);
                $templateKey = key($templatesList);
            }
            return $templatesList[$templateKey];
        }

        return $templatesList;
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
                    'visible' => !config::sysAdmin('bbs.index.region.search', true, TYPE_BOOL),
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