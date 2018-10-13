<?php

# paths
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('BFF_PRODUCT', 'do');
define('BFF_VERSION', '2.4.2');

require 'paths.php';
require PATH_CORE . 'init.php';


class bff extends \bff\base\app
{
    static public $userSettings = 0;

    /**
     * Инициализация приложения
     * @return void
     */
    public function init()
    {
        # Инициализируем base\app

        static::autoloadEx(array(
            'User'  => array('app', 'app/user.php'),
            'Hooks' => array('app', 'app/hooks.php'),
            'Theme' => array('app', 'app/theme/base.php'),
        ));
        parent::init();
        if (static::cron()) {
            return;
        }

        # Middleware
        $this->_middleware = static::filter('app.middleware', array(
            'Offline'    => ['callback'=>\app\middleware\Offline::class,    'admin'=>false, 'priority'=>10],
            'RememberMe' => ['callback'=>\app\middleware\RememberMe::class, 'admin'=>false, 'priority'=>20],
            'LoginAuto'  => ['callback'=>\app\middleware\LoginAuto::class,  'admin'=>false, 'priority'=>30],
        ));

        # Yandex Карты 2.1
        Geo::$ymapsCoordOrder = 'latlong';
        Geo::$ymapsDefaultCoords = '55.7481,37.6206';
        Geo::$ymapsJS = Request::scheme().'://api-maps.yandex.ru/2.1/?lang=ru_RU';

        # подключаем Javascript + CSS
        tpl::includeJS('jquery', true);
        tpl::includeJS('bff', true, 6);
        tpl::includeJS(array('bootstrap.min'), false);
        if (!static::adminPanel()) {
            # для фронтенда
            js::setDefaultPosition(js::POS_FOOT); # переносим все инициализируемые inline-скрипты в footer
            tpl::includeJS('app', false, 18);
            self::$userSettings = static::input()->cookie(static::cookiePrefix() . 'usett');
        } else {
            # для админки
            tpl::includeJS('admin/bff', true, 2);
            tpl::includeJS('fancybox', true);
        }

        if (($userID = User::id())) {
            # актуализируем "Время последней активности" пользователя
            if ((BFF_NOW - func::SESSION('last_activity', 0)) >= config::sys('users.activity.timeout')) {
                Users::model()->userSave($userID, false, array('last_activity' => static::database()->now()));
                func::setSESSION('last_activity', BFF_NOW);
            }
            # актуализируем счетчики пользователя
            static::security()->userCounter(null);
        }
    }

    public static function isIndex()
    {
        return static::router()->isCurrent('index');
    }



    /**
     * Устанавливаем активный пункт меню
     * @param string $sPath keyword пункта меню (sitemap)
     * @param bool $bUpdateMeta обновить meta-данные
     * @param mixed $mActiveStateData данные для активного пункта меню
     */
    public static function setActiveMenu($sPath, $bUpdateMeta = true, $mActiveStateData = 1)
    {
        if (Request::isAJAX()) {
            return;
        }
        $sPath = str_replace('//', '/main/', $sPath);
        Sitemap::i()->setActiveMenuByPath($sPath, $bUpdateMeta, $mActiveStateData);
    }

    /**
     * Устанавливаем / получаем данные о фильтре
     * @param string $sKey ключ фильтра
     * @param array|NULL $mData данные или NULL (получаем текущие)
     * @return mixed
     */
    public static function filterData($sKey, $mData = null)
    {
        if (is_null($mData)) {
            return config::get('filter-' . $sKey, array());
        } else {
            config::set('filter-' . $sKey, $mData);
        }
    }

    /**
     * Проверка / сохранение типа текущего устройства:
     * > if( bff::device(bff::DEVICE_DESKTOP) ) - проверяем, является ли текущее устройство DESKTOP
     * > if( bff::device(array(bff::DEVICE_DESKTOP,bff::DEVICE_TABLET)) ) - проверяем, является ли текущее устройство DESKTOP или TABLET
     * > $deviceID = bff::device() - получаем текущий тип устройства
     * > bff::device(bff::DEVICE_DESKTOP, true) - сохраняем тип текущего устройства
     * @param string|array|bool $device ID устройства (self::DEVICE_), ID нескольких устройств или FALSE
     * @param bool $set true - сохраняем тип текущего устройства
     * @return bool|int
     */
    public static function device($device = 0, $set = false)
    {
        static $detected;
        $cookieKey = static::cookiePrefix() . 'device';

        # получаем тип устройства
        if (!$set) {
            if (!isset($detected)) {
                $detected = static::input()->cookie($cookieKey, TYPE_STR);
                if (empty($detected) || !in_array($detected, array(
                        self::DEVICE_DESKTOP, self::DEVICE_TABLET, self::DEVICE_PHONE), true)
                   ) {
                    $detected = static::deviceDetector();
                }
            }
            if (!empty($device)) {
                # для desktop загружаем весь контент (эмулируем все устройства)
                if (static::deviceDetector(self::DEVICE_DESKTOP) && static::deviceDesktopResponsive()) {
                    return true;
                }

                return (is_string($device) ? $detected === $device :
                    (is_array($device) ? in_array($detected, $device, true) :
                        false));
            } else {
                return $detected;
            }
        } # устанавливаем тип устройства
        else {
            if (empty($device) || is_array($device) || !in_array($device, array(
                        self::DEVICE_DESKTOP,
                        self::DEVICE_TABLET,
                        self::DEVICE_PHONE
                    ), true
                ) || static::deviceNoResponsive()
            ) {
                $device = static::deviceDetector();
            }
            if ($device !== static::input()->cookie($cookieKey, TYPE_STR)) {
                unset($detected);
                setcookie($cookieKey, $device, time() + 604800, '/', '.' . SITEHOST);
                $_COOKIE[$cookieKey] = $device;
            }
        }
    }

    public static function deviceDesktopResponsive()
    {
        return config::sysTheme('device.desktop.responsive', true, TYPE_BOOL);
    }

    public static function deviceNoResponsive()
    {
        return (static::deviceDetector(self::DEVICE_DESKTOP) && !static::deviceDesktopResponsive());
    }

    public static function shopsEnabled($onlyPublisher = false)
    {
        static $shopsCatalog;
        if (!isset($shopsCatalog)) {
            $shopsCatalog = static::moduleExists('shops') && Shops::categoriesEnabled();
        }
        return !BBS::publisher(BBS::PUBLISHER_USER) || ($shopsCatalog && !$onlyPublisher);
    }

    public static function servicesEnabled($settingOnly = false)
    {
        $setting = config::sysAdmin('services.enabled', true);
        if ($settingOnly) {
            return $setting;
        }
        $module = bff::moduleExists('svc', false);
        if (bff::adminPanel()) {
            return $module;
        } else {
            return $setting && $module;
        }
    }

    /**
     * Подмена общих URL префиксов модулей
     * @param string $module название модуля
     * @param string $section дополнительное название секции
     * @param string $default значение по умолчанию
     * @return mixed
     */
    public static function urlPrefix($module, $section, $default)
    {
        return trim(config::sysAdmin($module.'.url.prefix.'.$section, $default), "/ \t\n\r\0\x0B");
    }

    /**
     * Формирование URL при изменении региона
     * @param string $regionKey ключ региона
     * @param boolean $addQuery добавлять в URL строку запроса
     * @return string
     */
    public static function urlRegionChange($regionKey, $addQuery = true)
    {
        $url = SITEURL; # proto + host
        $extra = \Site::urlExtra(array(), array('region'=>$regionKey)); # extra
        if (!empty($extra)) { $url.= '/'.join('/', $extra).'/'; } else { $url .= '/'; }
        $url.= static::router()->getUri(); # uri
        if ($addQuery) {
            $query = \Request::getSERVER('QUERY_STRING');
            $url .= ( ! empty($query) ? '?' . $query : '');
        }
        return $url;
    }

    public static function urlAway($sURL)
    {
        $sURL = str_replace(array('http://', 'https://', 'ftp://'), '', $sURL);
        if (empty($sURL) || $sURL == '/') {
            return static::urlBase();
        }

        return Site::url('away', array('url'=>$sURL));
    }
}

bff::i()->init();

# объявляем константы типа текущего устройства пользователя
define('DEVICE_DESKTOP', bff::device(bff::DEVICE_DESKTOP));
define('DEVICE_TABLET', bff::device(bff::DEVICE_TABLET));
define('DEVICE_PHONE', bff::device(bff::DEVICE_PHONE));
define('DEVICE_DESKTOP_OR_TABLET', DEVICE_DESKTOP || DEVICE_TABLET);
define('DEVICE_TABLET_OR_PHONE', DEVICE_TABLET || DEVICE_PHONE);

if (bff::adminPanel()) {

    config::set('core.dev.menu.localization.hide', true);
    Site::adminPanel(array(
        'bbs'          => _t('menu', 'Объявления'),
        'shops'        => _t('menu', 'Магазины'),
        'users'        => _t('menu', 'Пользователи'),
        'bills'        => _t('menu', 'Счета'),
        'banners'      => _t('menu', 'Баннеры'),
        'internalmail' => _t('menu', 'Сообщения'),
        'blog'         => _t('menu', 'Блог'),
        'help'         => _t('menu', 'Помощь'),
        'pages'        => _t('menu', 'Страницы'),
        'contacts'     => _t('menu', 'Контакты'),
        'sendmail'     => _t('menu', 'Работа с почтой'),
        'sitemap'      => _t('menu', 'Карта сайта и меню'),
        'seo'          => _t('', 'SEO'),
        'settings'     => _t('menu', 'Настройки сайта'),
    ));
}