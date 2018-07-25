<?php namespace bff\base;

/**
 * Базовый класс приложения
 * @abstract
 * @version 2.77
 * @modified 20.mar.2018
 */

use \config;
use \Logger;
use bff\utils\Files;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class app extends \bff\Singleton
{
    /** @var \Pimple DI-контейнер */
    protected static $di;
    /** @var integer|bool ID текущего авторизованного пользователя или FALSE (0) */
    public static $userID = false;
    /** @var bool является ли текущий пользователь поисковым ботом */
    public static $isBot = false;
    /** @var string название требуемого модуля */
    public static $class = '';
    /** @var string название требуемого метода модуля */
    public static $event = '';

    /** @var array список модулей ядра (базовая функциональность которых реализована в ядре) */
    protected $_m_core = array('bills', 'dev', 'geo', 'sendmail', 'seo', 'site', 'sitemap', 'svc', 'users');
    /** @var array список дополнительных модулей */
    protected $_m_registered = array();
    /** @var array список инициализированных объектов модулей */
    protected $_m = array();
    /** @var array список middleware */
    protected $_middleware = array();

    # тип устройства:
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_PHONE = 'phone';

    /**
     * Singleton
     * @return \bff|mixed
     */
    public static function i()
    {
        return parent::i();
    }

    /**
     * Инициализация приложения
     */
    public function init()
    {
        parent::init();

        # Иницииализируем компонент работы с хуками
        static::hooks();

        # Загружаем настройки сайта
        config::load();

        # Панель администратора
        $adminPanel = static::adminPanel();

        # Параметры запроса
        static::$isBot = (empty($_COOKIE) && preg_match("#(google|googlebot|yandex|rambler|msnbot|bingbot|yahoo! slurp|facebookexternalhit)#si", \Request::userAgent()));
        static::$class = static::DI('input')->getpost('s', TYPE_STR, array('len' => 250));
        static::$event = static::DI('input')->getpost('ev', TYPE_STR, array('len' => 250));

        # Хуки
        # modules hooks:
        foreach ($this->getModulesList() as $module=>$moduleParams) {
            static::$autoloadMap[ucfirst($module).'Hooks'] = array('app', 'modules/'.$module.'/hooks.php');
        }
        # config/sys hooks:
        static::hooksBulk(config::sys('hooks', array()));

        # Локализация
        $localeDefault = config::sys('locale.default');
        if ($adminPanel) {
            $localeDefault = config::sys('locale.default.admin', $localeDefault);
        }
        static::locale()->init(config::sys('locale.available'), $localeDefault, $adminPanel);

        # Строим путь к основным шаблонам
        define('TPL_PATH', PATH_BASE . 'tpl' .
            ($adminPanel ? DIRECTORY_SEPARATOR . 'admin' :
                (static::isMobile() ? DIRECTORY_SEPARATOR . 'mobile' : '')));

        # Инициализируем работу с сессией
        static::DI('security')->init();
        static::DI('security')->checkExpired();

        # Настройки админ. панели
        if ($adminPanel && config::sys('admin.smarty.enabled', false)) {
            config::set('tpl_custom_center_area', false);

            $oSm = \CSmarty::i();
            $oSm->force_compile = FORDEV;
            $oSm->compile_check = true;
            $oSm->debugging = false;
            $oSm->compile_dir = PATH_BASE . 'files/smarty';
            $oSm->config_dir = PATH_BASE . 'config';
            $oSm->plugins_dir = array('plugins', 'plugins/bff');
            $oSm->template_dir = TPL_PATH;
            $oSm->assign('site_url', SITEURL);
            $oSm->assign('fordev', FORDEV);
            $oSm->assign('class', static::$class);
            $oSm->assign('event', static::$event);
            $oSm->assign_by_ref('config', config::$data);
        }

        config::set('bot', static::$isBot);

        # Плагины
        static::dev()->pluginsLoad();

        # Темы
        static::theme();

        # Моды: live debug
        if (BFF_DEBUG) {
            $mods = new \bff\extend\Mods();
            $mods->refresh(true);
        }

        # Событие инициализации
        static::hook('app.init');
    }

    /**
     * Является ли текущий пользователь поисковым ботом
     * @return bool
     */
    public static function isRobot()
    {
        return static::$isBot;
    }

    /**
     * Запуск приложения
     * @param array $routes
     * @param boolean $respond
     */
    public function run(array $routes = array(), $respond = true)
    {
        if ( ! static::$class) {
            # Собираем роуты
            foreach ($this->getModulesList() as $module=>$moduleParams) {
                $file = (!empty($moduleParams['path']) ? $moduleParams['path'] : PATH_MODULES . $module . DIRECTORY_SEPARATOR ) . 'routes.php';
                if (is_file($file)) {
                    $routes = array_merge($routes, include modification($file, false));
                }
            }
            # Выполняем поиск подходящего роута
            $route = static::route($routes, array(
                'landing-pages' => config::sys('seo.landing.pages.enabled', false, TYPE_BOOL),
            ));
        } else {
            $route = array(
                'class' => static::$class,
                'event' => static::$event,
                'params' => array(),
                'route' => false,
            );
        }

        # Middleware роута
        if (!empty($route['route']['middleware'])) {
            foreach ($route['route']['middleware'] as $v) {
                $this->middlewareAdd($v);
            }
        }

        # Выполняем роут
        $this->middlewareAdd(function(ServerRequestInterface $request, $next) use ($route) {
            try {
                if ($route['route']) {
                    if (is_string($route['route']['callback'])) {
                        $newResponse = $this->callModule([$route['class'], $route['event']]);
                    } else {
                        $newResponse = call_user_func($route['route']['callback'], $request);
                    }
                } else {
                    $newResponse = $this->callModule([$route['class'], $route['event']]);
                }
                if ($newResponse instanceof ResponseInterface) {
                    $response = $newResponse;
                } else {
                    if (is_string($newResponse)) {
                        $layout = \View::getLayout();
                        if (!empty($layout)) {
                            $data = array('centerblock' => $newResponse);
                            $newResponse = \View::renderLayout($data, $layout);
                        }
                    } else if (is_array($newResponse)) {
                        $newResponse = json_encode($newResponse, JSON_FORCE_OBJECT);
                    }
                    $response = new \Response();
                    if ($response->getBody()->isWritable()) {
                        $response->getBody()->write($newResponse);
                    }
                }
            } catch (\Throwable $e) {
                if (BFF_DEBUG) {
                    static::errors()->set($e->getMessage() . ', ' . $e->getFile() . ' [' . $e->getCode() . ']', true);
                    if (class_exists('\Whoops\Run')) {
                        \Errors::whoops()->handleException($e);
                    }
                } else {
                    static::errors()->error404();
                }
                throw $e;
            } catch (\Exception $e) {
                if (BFF_DEBUG) {
                    static::errors()->set($e->getMessage() . ', ' . $e->getFile() . ' [' . $e->getCode() . ']', true);
                    if (class_exists('\Whoops\Run')) {
                        \Errors::whoops()->handleException($e);
                    }
                } else {
                    static::errors()->error404();
                }
                throw $e;
            }
            return $response;
        }, 1000);

        # Запускаем middleware
        $request = static::request();
        $response = $this->middlewareCall($this->_middleware, $request);
        if ($response->getProtocolVersion() !== ($requestProtocol = $request->getProtocolVersion())) {
            if ($requestProtocol === '2.0') {
                $requestProtocol = '2';
            }
            $response = $response->withProtocolVersion($requestProtocol);
        }
        if ($respond) {
            return static::respond($response, false);
        }

        return $response;
    }

    /**
     * Отправляем ответ клиенту
     * @param ResponseInterface $response
     * @param boolean $finish
     */
    public static function respond(ResponseInterface $response, $finish = false)
    {
        # Empty
        $empty = in_array($response->getStatusCode(), [204, 205, 304]);
        if ($empty) {
            $response = $response->withoutHeader('Content-Type')->withoutHeader('Content-Length');
        }

        # Headers
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ));
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        # Body
        if ( ! $empty) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $chunkSize = static::filter('app.response.chunkSize', 4096);

            $contentLength = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }

            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;
                    $amountToRead -= strlen($data);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }

        # Finish
        if ($finish) {
            static::shutdown();
        }
    }

    /**
     * Добавляем посредника
     * @param callable|array $callable замыкание принимающее 2 параметра:
     *  - ServerRequestInterface $request
     *  - callable $next
     */
    protected function middlewareAdd($middleware, $priority = false)
    {
        if (is_callable($middleware)) {
            $middleware = ['callback'=>$middleware];
            if (is_int($priority)) {
                $middleware['priority'] = $priority;
            }
            $this->_middleware[] = $middleware;
        } else if (is_array($middleware) && isset($middleware['callback'])) {
            $this->_middleware[] = $middleware;
        }
    }

    /**
     * Запуск стека middleware
     * @param array $list список middleware
     * @param ServerRequestInterface $request объект запроса
     * @return ResponseInterface объект ответа
     */
    protected function middlewareCall(array $list, ServerRequestInterface $request)
    {
        \func::sortByPriority($list); $list = array_reverse($list, true);
        $stack = new \SplStack();
        $stack->setIteratorMode(\SplDoublyLinkedList::IT_MODE_LIFO | \SplDoublyLinkedList::IT_MODE_KEEP);
        $stack[] = function(){};
        $admin = static::adminPanel();
        foreach ($list as $k=>$middleware) {
            if (isset($middleware['admin']) && (
                (empty($middleware['admin']) && $admin) ||
                (!empty($middleware['admin']) && !$admin)
            )) {
                continue;
            }
            $callable = $middleware['callback'];
            if ( ! is_callable($callable)) {
                if (is_string($callable) && class_exists($callable)) {
                    $callable = new $callable();
                }
            }
            $next = $stack->top();
            $stack[] = function (ServerRequestInterface $request) use ($callable, $next) {
                $result = call_user_func($callable, $request, $next);
                if (!($result instanceof ResponseInterface)) {
                    throw new \Exception('Посредник (middleware) должен возвращать объект \Psr\Http\Message\ResponseInterface');
                }
                return $result;
            };
        }

        $start = $stack->top();
        return $start($request);
    }

    /**
     * Dependency Injection Container
     * @param bool $key ключ требуемого "сервиса", false - объект \Pimple
     * @return mixed|\Pimple
     */
    public static function DI($key = false)
    {
        if (!isset(static::$di)) {
            static::$di = new \Pimple(array(
                'errors'         => function ($c) {
                        return $c['errors_class']::i();
                    },
                'errors_class'   => '\Errors',
                'security'       => function ($c) {
                        return new $c['security_class']();
                    },
                'security_class' => '\Security',
                'locale'         => function ($c) {
                        return new $c['locale_class']();
                    },
                'locale_class'   => '\bff\base\Locale',
                'input'          => function ($c) {
                        return new $c['input_class']();
                    },
                'input_class'    => '\bff\base\Input',
                'database'       => function ($c) {
                        $db = new $c['database_class']();
                        $db->connectionConfig('db');
                        $db->connect();

                        return $db;
                    },
                'database_class' => '\bff\db\Database',
                'hooks'          => function ($c) {
                        return new $c['hooks_class']();
                    },
                'hooks_class'    => '\Hooks',
                'request'        => function () {
                        return \Request::fromGlobals();
                    },
            ));
            static::$di['database_factory'] = static::$di->factory(function ($c) {
                    return new $c['database_class']();
                }
            );
        }
        if (!empty($key)) {
            return static::$di[$key];
        }

        return static::$di;
    }

    /**
     * Обработка вызова метода модуля
     * @param string $name имя модуля
     * @param array $aArgs аргументы
     * @return mixed
     */
    public function __call($name, $aArgs)
    {
        return $this->callModule($name, $aArgs);
    }

    /**
     * Вызываем метод требуемого модуля
     * @param string|array $name имя модуля 'module', 'module_method', ['class'=>'','event'=>'']
     * @param array $aArgs аргументы
     * @return mixed
     */
    public function callModule($name, $aArgs = array())
    {
        if (is_string($name)) {
            $name = explode('_', $name, 2);
        }
        if (!empty($name['class'])) {
            $method = $name['event'];
            $name = $name['class'];
        } else {
            $method = (isset($name[1]) ? $name[1] : '');
            $name = (isset($name[0]) ? mb_strtolower($name[0]) : '');
        }

        if (static::moduleExists($name)) {
            $oModule = $this->getModule($name);
        } else if (static::pluginExists($name)) {
            $oModule = $this->getPlugin($name);
        } else if (($theme = static::theme()) !== false && $theme->getName() === $name) {
            $oModule = $theme;
        } else {
            throw new \Exception(_t('system', 'Неудалось найти модуль "[module]"', array('module' => $name)));
        }

        $aArgsRef = array();
        foreach ($aArgs as $key => $v) {
            $aArgsRef[] = & $aArgs[$key];
        }

        if (!method_exists($oModule, $method)) {
            $result = $oModule->$method($aArgsRef); # пытаемся вызвать метод прикрепленного компонента
            if ($result === null) {
                if (!static::adminPanel()) {
                    static::errors()->error404();
                }
                static::errors()->set(_t('system', 'Модуль [module] не имеет метода [method]', array(
                            'module' => $name,
                            'method' => $method
                        )
                    ), true
                );
                static::errors()->autohide(false);
                $result = '';
            }
        } else {
            $result = call_user_func_array(array($oModule, $method), $aArgsRef);
        }

        return $result;
    }

    /**
     * Вызываем требуемый метод во всех модулях приложения (в которых он реализован)
     * @param string $sMethod имя метода
     * @param array $aArgs аргументы
     * @param boolean $bPlugins вызывать методы плагинов
     * @param boolean $bTheme вызывать методы активной темы
     * @return mixed
     */
    public function callModules($sMethod, array $aArgs = array(), $bPlugins = true, $bTheme = true)
    {
        $aArgsRef = array();
        foreach ($aArgs as $key => $v) {
            $aArgsRef[] = & $aArgs[$key];
        }

        $aModules = $this->getModulesList();
        foreach ($aModules as $moduleName=>$moduleParams) {
            $oModule = $this->getModule($moduleName);
            if (method_exists($oModule, $sMethod)) {
                call_user_func_array(array($oModule, $sMethod), $aArgsRef);
            }
        }

        if ($bPlugins) {
            $aPlugins = static::dev()->getPluginsList();
            foreach($aPlugins as $pluginName=>$pluginObject) {
                if (method_exists($pluginObject, $sMethod)) {
                    call_user_func_array(array($pluginObject, $sMethod), $aArgsRef);
                }
            }
        }

        if ($bTheme) {
            $themeObject = \bff::theme();
            if ($themeObject !== false && method_exists($themeObject, $sMethod)) {
                call_user_func_array(array($themeObject, $sMethod), $aArgsRef);
            }
        }
    }

    /**
     * Возвращаем объект модуля, имя модуля и имя вызванного метода
     * @param string $moduleName название модуля
     * @param bool $onlyObject true - возвращать только объект модуля; false - array(объект, название, название метода, bool-первое обращение к модулю)
     * @return \Module|array
     */
    public function getModule($moduleName, $onlyObject = true)
    {
        $moduleName = mb_strtolower(str_replace(array('.',DS), '', $moduleName));

        $firstRun = true;
        if (isset($this->_m[$moduleName])) {
            $moduleObject = $this->_m[$moduleName];
            $firstRun = false;
        } else {
            if (!empty($this->_m_registered[$moduleName]['path'])) {
                $moduleObject = $this->moduleInit($moduleName, $this->_m_registered[$moduleName]['path']);
            } else {
                $moduleObject = $this->moduleInit($moduleName, false);
            }
        }

        return ($onlyObject ? $moduleObject : array($moduleObject, $moduleName, $firstRun));
    }

    /**
     * Регистрируем дополнительный модуль
     * @param string $moduleName название модуля
     * @param string $modulePath путь к директории модуля
     * @return boolean
     */
    public function moduleRegister($moduleName, $modulePath)
    {
        $moduleName = mb_strtolower(str_replace(array('.',DS), '', strval($moduleName)));
        if (empty($moduleName)
            || in_array($moduleName, $this->_m_core)
            || isset($this->_m[$moduleName])
            || isset($this->_m_registered[$moduleName])
            || !is_dir($modulePath)
            || is_dir(PATH_MODULES.$moduleName)) {
            return false;
        }
        $this->_m_registered[$moduleName] = array(
            'name' => $moduleName,
            'path' => rtrim($modulePath, DS.' ').DS,
        );
        return true;
    }

    /**
     * Инициализируем модуль
     * @param string $moduleName название модуля
     * @param string|boolean $modulePath путь к директории модуля или false (/modules/{name}/)
     * @return \Module
     */
    protected function moduleInit($moduleName, $modulePath = false)
    {
        $moduleName = mb_strtolower(str_replace(array('.',DS), '', $moduleName));

        if (isset($this->_m[$moduleName])) {
            return $this->_m[$moduleName];
        }

        $adm = static::adminPanel();
        $core = in_array($moduleName, $this->_m_core);
        if ($core) {
            # подключаем [ModuleName]ModuleBase
            require modification(PATH_CORE . 'modules' . DS . $moduleName . DS . 'base.php', true, false);
            # подключаем [ModuleName]Model
            require modification(PATH_CORE . 'modules' . DS . $moduleName . DS . 'model.php', true, false);
            # подключаем [ModuleName]Module
            require modification(PATH_CORE . 'modules' . DS . $moduleName . DS . ($adm ? 'admin' : 'frontend') . '.php', true, false);
        }

        $path = (!empty($modulePath) ? rtrim($modulePath, DS).DS : PATH_MODULES . $moduleName . DS); # ищем в модулях приложения (/modules)
        $pathConroller = modification($path . $moduleName . ($adm ? '.adm' : '') . '.class.php', true, false);
        if (file_exists($pathConroller)) {
            # подключаем [ModuleName]Base
            require modification($path . $moduleName . '.bl.class.php', true, false);
            # подключаем [ModuleName]Model
            require modification($path . $moduleName . '.model.php', true, false);
            # подключаем [ModuleName][_]
            require $pathConroller;
            # псевдоним класса модуля
            static::classAlias($moduleName);

            # cоздаем объект модуля приложения
            $moduleObject = $this->_m[$moduleName] = new $moduleName();
            $moduleObject->setSettings('module_dir', $path);
            $moduleObject->initModule($moduleName);
            $moduleObject->init();
            static::hook($moduleName.'.init', $moduleObject);
        } else {
            if ($core) {
                # cоздаем объект модуля ядра
                $moduleCore = $moduleName . 'Module';
                $moduleObject = $this->_m[$moduleName] = new $moduleCore();
                $moduleObject->initModule($moduleName);
                $moduleObject->init();
                static::hook($moduleName.'.init', $moduleObject);
            } else {
                throw new \Exception(_t('system', 'Неудалось найти модуль "[module]"', array('module' => $moduleName)));
            }
        }

        return $moduleObject;
    }

    /**
     * Получаем список модулей приложения
     * @param bool|mixed $mCoreModules :
     *  true  - список модулей ядра
     *  false - список модулей приложения
     *  'all' - список всех модулей (ядра + приложения)
     * @return array
     */
    public function getModulesList($mCoreModules = false)
    {
        if ($mCoreModules === true) {
            return array_combine($this->_m_core, $this->_m_core);
        }
        static $cache;
        if (!isset($cache)) {
            $aModules = Files::getDirs(PATH_MODULES);
            foreach ($aModules as $k => $v) {
                if ($v{0} != '.' && $v{0} != '_' && $v != 'test') {
                    $aModules[$v] = array('name'=>$v,'path'=>PATH_MODULES.$v.DS);
                }
                unset($aModules[$k]);
            }
            $cache = $aModules;
        }
        if ($mCoreModules === 'all') {
            foreach ($this->_m_core as $v) {
                if (!isset($cache[$v])) {
                    $cache[$v] = $v;
                }
            }
        }
        foreach ($this->_m_registered as $k=>$v) {
            if (!isset($cache[$k])) {
                $cache[$k] = $v;
            }
        }

        return $cache;
    }

    /**
     * Возвращаем объект модуля, имя модуля и имя вызванного метода
     * Сокращение для static::i()->getModule()
     * @param string $moduleName название модуля
     * @param bool $onlyObject возвращать только объект модуля - true, либо array(объект, название, ...) - false
     * @return @see bff::getModule
     */
    public static function module($moduleName, $onlyObject = true)
    {
        return static::i()->getModule($moduleName, $onlyObject);
    }

    /**
     * Проверяем существование модуля
     * @param string $moduleName название модуля
     * @param bool $bCheckCore выполнять поиск также среди модулей ядра или только среди модулей приложения
     * @return bool
     */
    public static function moduleExists($moduleName, $bCheckCore = true)
    {
        if (empty($moduleName) || !is_string($moduleName)) {
            return false;
        }
        $moduleName = mb_strtolower($moduleName);

        if ($bCheckCore) {
            if (in_array($moduleName, static::i()->getModulesList(true))) {
                return true;
            }
        }

        return array_key_exists($moduleName, static::i()->getModulesList(false));
    }

    /**
     * Возвращаем объект модели указанного модуля
     * @param string $moduleName название модуля
     * @return \Model
     */
    public static function model($moduleName)
    {
        return static::i()->getModule($moduleName, true)->model;
    }

    /**
     * Выполняется ли запрос из admin-панели
     * @param boolean $pathOnly только путь в админ. панель
     * @return bool|string
     */
    public static function adminPanel($pathOnly = false)
    {
        static $path, $is = false;
        if (!isset($path)) {
            $path = trim(config::sys('admin.path', 'admin', TYPE_STR), " \t\n\r\0\x0B/?");
            if (mb_strlen($path) <= 2) { $path = 'admin'; }
            if (defined('BFF_ADMINPANEL')) {
                $is = true;
            } else {
                $uri = trim(preg_replace("/^(.*)\?.*$/U", '$1', \Request::uri()), '/');
                $is = ($uri === $path || $uri === $path . '/index.php');
            }
        }
        if ($pathOnly) {
            return '/'.$path.'/';
        }
        return $is;
    }

    /**
     * Компонент работы с меню админ. панели
     * @return \CMenu
     */
    public static function adminMenu()
    {
        static $i;
        if (!isset($i)) {
            $i = \CMenu::i();
            $i->init();
        }
        return $i;
    }

    /**
     * Корректно ли выполнено обращение к cron-методу модуля
     * @return bool
     */
    public static function cron()
    {
        return defined('BFF_CRON');
    }

    /**
     * Инициализация cron-менеджера
     * @return \CronManager
     */
    public static function cronManager()
    {
        static $i;
        if ( ! isset($i)) {
            $i = new \CronManager();
        }
        return $i;
    }

    /**
     * Cookie префикс
     * @return string
     */
    public static function cookiePrefix()
    {
        return config::sysAdmin('cookie.prefix', 'bff_', TYPE_STR);
    }

    /**
     * Демо версия
     * @return bool
     */
    public static function demo()
    {
        return defined('BFF_DEMO') || config::sys('demo');
    }

    /**
     * @return \Errors
     */
    public static function errors()
    {
        return static::DI('errors');
    }

    /**
     * @return ServerRequestInterface
     */
    public static function request()
    {
        return static::DI('request');
    }

    /**
     * @return \bff\base\Input
     */
    public static function input()
    {
        return static::DI('input');
    }

    /**
     * @return \bff\base\Locale
     */
    public static function locale()
    {
        return static::DI('locale');
    }

    /**
     * @return \Security
     */
    public static function security()
    {
        return static::DI('security');
    }

    /**
     * @return \bff\db\Database
     */
    public static function database()
    {
        return static::DI('database');
    }

    /**
     * @return \Hooks
     */
    public static function hooks()
    {
        return static::DI('hooks');
    }

    /**
     * Запуск хука
     * @param string $key ключ хука
     */
    public static function hook($key)
    {
        $args = func_get_args();
        call_user_func_array(array(static::hooks(), 'run'), $args);
    }

    /**
     * Добавление хука
     * @param string $key ключ хука
     * @param callable $callable функция обработчик
     * @param int|null $priority приоритет вызова
     * @return \Hook|boolean
     */
    public static function hookAdd($key, callable $callable, $priority = NULL)
    {
        return static::hooks()->add($key, $callable, $priority);
    }

    /**
     * Добавление хуков пакетно
     * @param array $hooks хуки:
     * [
     *     'ключ хука' => значение,
     *     'ключ хука' => функция обработчик,
     * ]
     * @return array
     */
    public static function hooksBulk($hooks = array())
    {
        if (empty($hooks) || ! is_array($hooks)) {
            return array();
        }
        $result = array();
        foreach ($hooks as $key=>$value) {
            if (is_string($key)) {
                if ($value instanceof \Closure) {
                    $result[$key] = static::hookAdd($key, $value);
                } else if (is_scalar($value)) {
                    $result[$key] = static::hookAdd($key, function() use ($value) {
                        return $value;
                    });
                } else if (is_array($value)) {
                    $result[$key] = static::hookAdd($key, function($data) use ($value) {
                        return config::merge($data, $value);
                    });
                }
            }
        }
        return $result;
    }

    /**
     * Проверка наличия привязанных хуков по ключу
     * @param string $key ключ хука
     * @return boolean
     */
    public static function hooksAdded($key)
    {
        return static::hooks()->has($key);
    }

    /**
     * Удаление хука
     * @param string $key ключ хука
     * @param callable $callable функция обработчик
     * @return boolean
     */
    public static function hookRemove($key, callable $callable)
    {
        return static::hooks()->remove($key, $callable);
    }

    /**
     * Применение фильтра
     * @param string $key ключ хука
     * @return mixed
     */
    public static function filter($key)
    {
        $args = func_get_args();
        return call_user_func_array(array(static::hooks(), 'apply'), $args);
    }

    /**
     * Применение фильтра c предварительным получением значения из файла системных настроек,
     * если таковое было указано
     * @param string $key ключ хука
     * @return mixed
     */
    public static function filterSys($key)
    {
        $args = func_get_args();
        if (isset($args[1])) {
            $args[1] = config::sys($key, $args[1]);
        }
        return call_user_func_array(array(static::hooks(), 'apply'), $args);
    }

    /**
     * Возвращаем объект плагина
     * @param string $pluginName название плагина
     * @return \Plugin|boolean
     */
    public static function plugin($pluginName)
    {
        return static::i()->getPlugin($pluginName);
    }

    /**
     * Проверяем был ли подключен плагин
     * @param string $pluginName название плагина
     * @return bool
     */
    public static function pluginExists($pluginName)
    {
        return (static::i()->getPlugin($pluginName) !== false);
    }

    /**
     * Возвращаем объект плагина
     * @param string $pluginName название плагина
     * @return \Plugin|boolean
     */
    public function getPlugin($pluginName)
    {
        return static::dev()->getPlugin($pluginName);
    }

    /**
     * Модуль Dev
     * @return \DevModule
     */
    public static function dev()
    {
        return static::module('dev');
    }

    /**
     * Проверка на index-страницу
     * @return bool
     */
    public static function isIndex()
    {
        return empty(static::$class) || (static::$class == 1);
    }

    /**
     * Определение необходимости отображения mobile-версии на отдельном домене
     * @return bool
     */
    public static function isMobile()
    {
        static $isMobile;
        if (isset($isMobile)) return $isMobile;

        $sMobileHost = config::sys('site.mobile.host');
        if (empty($sMobileHost)) {
            # мобильная версия незадействована
            return ($isMobile = false);
        }
        $sHttpHost = \Request::host(SITEHOST);
        $sForceCookieName = static::cookiePrefix() . 'full';

        # находимся на поддомене m. => значит показываем мобильную версию, независимо от типа устройства (и куков)
        if (stripos($sHttpHost, $sMobileHost) === 0 || stripos($sHttpHost, '.'.$sMobileHost) !== false) {
            return ($isMobile = true);
        }

        # переход с мобильной версии на полную, ставим куку
        # m.host.com => host.com?full=1
        if (!empty($_GET['full']) && strpos(\Request::referer(), $sMobileHost) !== false) {
            \Request::setCOOKIE($sForceCookieName, 1);

            return ($isMobile = false);
        }

        # проверяем наличие куки для принудительного отображения полной версии
        $bForceFull = static::DI('input')->cookie($sForceCookieName, TYPE_BOOL);
        if (!empty($bForceFull)) {
            # кука есть => показываем полную версию
            return ($isMobile = false);
        }

        # определяем зашел ли пользователь с мобильного устройства, если да, тогда выполняем редирект на поддомен
        $sMobileRedirect = \Request::scheme().'://' . $sMobileHost;
        if (!empty($_SERVER['REQUEST_URI'])) {
            if (strpos($sHttpHost, '.' . SITEHOST)) {
                # находимся на поддомене, значит нет такой страницы в мобильной версии
                $sMobileRedirect .= '';
            } else {
                $sMobileRedirect .= $_SERVER['REQUEST_URI'];
            }
        }

        $isMobile = static::deviceDetector(self::DEVICE_PHONE);
        if ($isMobile) {
            \Request::redirect($sMobileRedirect);
        }

        return $isMobile;
    }

    /**
     * Определение типа устройства
     * @param null|string|array $check тип определяемого устройства или NULL
     * @return bool
     */
    public static function deviceDetector($check = null)
    {
        static $device;
        if (!isset($device)) {
            $detector = new \Mobile_Detect();
            $device = (!$detector->isMobile() ? self::DEVICE_DESKTOP :
                ($detector->isTablet() ? self::DEVICE_TABLET :
                    self::DEVICE_PHONE));
        }
        if (empty($check)) {
            return $device;
        } else if (is_array($check)) {
            return in_array($device, $check);
        } else {
            return ($device === $check);
        }
    }

    /**
     * Работает ли приложение в https-only режиме
     * @return boolean
     */
    public static function httpsOnly()
    {
        return config::sys('https.only', false, TYPE_BOOL);
    }

    /**
     * Отправка письма на основе шаблона
     * @param array $tplVars данные подставляемые в шаблон
     * @param string $tplName ключ шаблона письма
     * @param string $to email получателя
     * @param string|bool $subject заголовок письма или FALSE (берем из шаблона письма)
     * @param string $from email отправителя
     * @param string $fromName имя отправителя
     * @param string $lng ключ языка шаблона
     * @param array $customHeaders доп. заголовки письма
     * @return bool
     */
    public static function sendMailTemplate($tplVars, $tplName, $to, $subject = false, $from = '', $fromName = '', $lng = LNG, array $customHeaders = array())
    {
        try {
            $data = \Sendmail::i()->getMailTemplate($tplName, $tplVars, $lng);
            if ($subject !== false) { $data['subject'] = $subject; }
            $data['name'] = $tplName;
            $data['to'] = $to;
            $data['from'] = $from;
            $data['fromName'] = $fromName;
            $data['vars'] = &$tplVars;
            $data['lang'] = $lng;
            $data['customHeaders'] = &$customHeaders;
            $data = static::filter('mail.send.template', $data, $tplName);
            if (BFF_LOCALHOST) {
                static::log(array('tpl' => $tplName, 'data' => $data));
                return true;
            }
            if (is_bool($data)) {
                return $data;
            }
            return static::sendMail($data['to'], $data['subject'], $data['body'], $data['from'], $data['fromName'], $customHeaders);
        } catch (\Exception $e) {
            static::errors()->set('sendMailTemplate: '.$tplName, true);
            static::errors()->set($e->getMessage(), true);
        }
        return false;
    }

    /**
     * Отправка письма
     * @param string $to email получателя
     * @param string $subject заголовок письма
     * @param string $from email отправителя
     * @param string $fromName имя отправителя
     * @param array $customHeaders доп. заголовки письма
     * @return bool
     */
    public static function sendMail($to, $subject, $body, $from = '', $fromName = '', array $customHeaders = array())
    {
        return \Sendmail::i()->sendMail($to, $subject, $body, $from, $fromName, $customHeaders);
    }

    /**
     * Формирование директорий миграций
     * @param boolean $includePlugins выполнять подключение миграций плагинов
     * @return array
     */
    public function migrationsPaths($includePlugins = false)
    {
        $paths = [
            'migrations' => [],
            'seeds' => [],
        ];

        $paths['migrations'][] = PATH_BASE.'files'.DS.'migrations';

        foreach ($paths['migrations'] as $path) {
            if (is_dir($path.DS.'seeds')) {
                $paths['seeds'][] = $path.DS.'seeds';
            }
        }

        return static::filter('app.migrations.paths', $paths);
    }

    /**
     * Формирование пути к файлу
     * @param string $part вложенная директория либо относительный путь к файлу
     * @param string|bool $type тип пути(доступные: 'images') или FALSE
     * @return string
     */
    public static function path($part, $type = false)
    {
        $sep = DIRECTORY_SEPARATOR;
        if ($type === 'images') {
            return PATH_PUBLIC . 'files' . $sep . 'images' . $sep . $part . $sep;
        }
        if (!empty($part) && is_string($part)) {
            if ($part[0] === '/') {
                $path = static::themeFile($part, false);
                return modification($path !== false ? $path.$part : PATH_BASE.ltrim($part,'/'));
            }
            return PATH_PUBLIC . 'files' . $sep . $part . $sep;
        } else {
            return PATH_PUBLIC . 'files' . $sep;
        }
    }

    /**
     * Формирование URL для статики
     * @param string $part часть URL
     * @param mixed $version версия или 'images'
     * @return string
     */
    public static function url($part, $version = false)
    {
        $static = SITEURL_STATIC;
        if (is_string($version) && $version === 'images') {
            return $static . '/files/images/' . $part . '/';
        }
        if (!empty($part) && is_string($part)) {
            if ($part[0] === '/') {
                # theme
                if (($url = static::themeFile($part, true)) === false) {
                    $url = $static;
                }
                # /public/custom
                $partLeft = mb_substr($url, mb_strlen($static));
                if (is_file(PATH_PUBLIC.'custom'.$partLeft.$part)) {
                    $url = $static.'/custom'.$partLeft;
                }
                return $url . $part . (!empty($version) ? '?v='.strval($version) : '');
            }
            return $static . '/files/' . $part . '/';
        } else {
            return $static . '/files/';
        }
    }

    /**
     * Формирование полного URL для статики
     * @param string $url относительный URL
     * @param string $version версия
     * @return string
     */
    public static function urlStatic($url, $version = '')
    {
        return static::url($url, $version);
    }

    /**
     * Формирование базового URL
     * @param boolean $trailingSlash
     * @param string $languageKey ключ языка
     * @param array $subdomains поддомены
     * @param string
     */
    public static function urlBase($trailingSlash = true, $languageKey = LNG, array $subdomains = array())
    {
        $subdomains = ( ! empty($subdomains) ? join('.', $subdomains) . '.' : '' );
        return \Request::scheme() . '://' . $subdomains . SITEHOST . static::locale()->getLanguageUrlPrefix($languageKey, $trailingSlash);
    }

    /**
     * Формирование URL для переключения языка
     * @param string $languageKey ключ языка
     * @param boolean $addQuery добавлять в URL строку запроса
     * @return string
     */
    public static function urlLocaleChange($languageKey = LNG, $addQuery = true)
    {
        $url = \Request::scheme() . '://' . \Request::host(); # proto + host
        $extra = \Site::urlExtra(array(), array('locale'=>$languageKey)); # extra
        if (!empty($extra)) { $url.= '/'.join('/', $extra).'/'; } else { $url .= '/'; }
        $url.= static::route(array(), array('return-request-uri'=>true)); # uri
        $query = array();
        if ($addQuery) {
            parse_str(\Request::getSERVER('QUERY_STRING'), $query);
            if (isset($query['lng'])) {
                unset($query['lng']);
            }
        }
        if (empty($query) && mb_stripos($url, '/'.$languageKey.'/') === false) {
            $query['lng'] = $languageKey;
        }
        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        return $url;
    }

    /**
     * Формирование ajax URL
     * @param string $moduleName название модуля
     * @param string $sActionQuery доп. параметры запроса
     * @return string
     */
    public static function ajaxURL($moduleName, $sActionQuery)
    {
        return '/index.php?bff=ajax&s=' . $moduleName . '&act=' . $sActionQuery;
    }

    /**
     * Текущая тема
     * @param string|boolean $active true - текущая тема, 'name' - название темы (для инициализации)
     * @param boolean $testMode приоритет у темы с включенным режимом тестирования
     * @return \Theme|bool объект темы или false (тема по-умолчанию)
     */
    public static function theme($active = true, $testMode = true)
    {
        return static::dev()->themeInit($active, false, $testMode);
    }

    /**
     * Существует ли файл в текущей теме
     * @param string $file относительные путь к файлу, начинается с "/"
     * @param boolean $asUrl вернуть url
     * @return string путь/url к текущей теме (в случае если запрашиваемый файл в ней был найден)
     */
    public static function themeFile($file = '', $asUrl = false)
    {
        static $theme, $path, $pathPublic, $urlPublic;
        if ( ! isset($theme)) {
            $theme = static::theme();
            if ( ! empty($theme)) {
                $path       = PATH_THEMES.$theme->getName();
                $pathPublic = PATH_PUBLIC.'themes'.DIRECTORY_SEPARATOR.$theme->getName();
                $urlPublic  = SITEURL_STATIC.'/themes/'.$theme->getName();
            }
        }
        if (empty($theme)) {
            return false;
        }
        if ($asUrl) {
            if ( ! file_exists($pathPublic.$file)) {
                return false;
            }
            return $urlPublic;
        }
        if ( ! file_exists($path.$file)) {
            return false;
        }
        return $path;
    }

    /**
     * Роутинг
     * @param array $routes массив правил роутинга: array(key=>value, ...)
     *   key: string регулярное выражение, определяющее некоторый URL @example ([\d]+)\.html, /users/shop
     *   value: string строка, определяющая, итоговый модуль-метод-параметры через "/"
     *      [module]/[method]/[param1=\\1&param2=\\2&test=www]
     * @param array|boolean $options настройки:
     *   'wrap' - обворачивать регулярное выражение,
     *   'return-request-uri' - вернуть текущий uri запроса
     *   'landing-pages' - задействовать посадочные страницы (seo)
     *   'redirects' - задействовать редиректы (seo)
     *   'init-class-event' - инициализировать bff::$class, bff::$event
     *   'hook' - ключ хука для дополнительной фильтрации правил роутинга, по-умолчанию: 'routes','{bff::$class}.routes'
     * @return array|mixed: array('class'=>'','event'=>'','params'=>array())
     */
    public static function route(array $routes = array(), $options = false)
    {
        static $req, $reqOriginal;
        # parse request URI
        if (!isset($req)) {
            $req = preg_replace('/\/+/', '/', \Request::uri()); # // => /
            $req = ltrim($req, '/'); # remove first / (left)
            $req = preg_replace("/^(.*)\?.*$/U", '$1', $req); # remove query "?xxx"
            $extra = \Site::urlExtra();
            if (!empty($extra)) {
                $extra = join('/', $extra).'/';
                if (mb_stripos($req, $extra) === 0) {
                    $req = mb_substr($req, mb_strlen($extra));
                }
            }
            if (method_exists('bff', 'routeEx')) {
                $req = static::routeEx($req);
            }
        }

        # options
        $options = array_merge(array('wrap'=>true, 'landing-pages'=>false, 'redirects'=>true, 'init-class-event'=>true, 'return-request-uri'=>false),
            (!is_array($options) ? array('wrap' => !empty($options)) : $options));

        # return request uri
        if ($options['return-request-uri']) {
            return (isset($reqOriginal) ? $reqOriginal : $req);
        }

        # hooks
        $hook = (empty($options['hook']) ? ((empty(static::$class) ? '' : static::$class.'.') . 'routes') : $options['hook']);
        if (!empty($hook)) {
            $routes = static::filter($hook, $routes, $options);
            \func::sortByPriority($routes);
        }

        # landing pages
        if ($options['landing-pages']) {
            $reqNew = \SEO::landingPage($req);
            if ($reqNew !== false && $req !== $reqNew) {
                $reqOriginal = $req; $req = ltrim($reqNew, '/ ');
                $query = mb_stripos($req, '?');
                if ($query !== false) {
                    list($query, $req) = array(mb_substr($req, $query+1), mb_substr($req, 0, $query));
                    if (!empty($query)) {
                        parse_str($query, $query);
                        if (!empty($query)) {
                            foreach ($query as $k=>&$v) {
                                if (!isset($_GET[$k])) $_GET[$k] = $v;
                                if (!isset($_POST[$k])) $_POST[$k] = $v;
                            }
                        } unset($v);
                    }
                }
            }
        }

        # routes + hosts
        $hostsFound = false;
        foreach ($routes as $v) {
            if (is_array($v) && ! empty($v['host'])) {
                $hostsFound = true; break;
            }
        }
        if ($hostsFound) {
            $host = \Request::host();
            if ($host == SITEHOST) {
                foreach ($routes as $k => $v) {
                    if (is_array($v) && ! empty($v['host']) && $v['host'] != $host) {
                        unset($routes[$k]);
                    }
                }
            } else {
                foreach ($routes as $k => $v) {
                    if ( ! is_array($v) || empty($v['host']) || $v['host'] != $host) {
                        unset($routes[$k]);
                    }
                }
            }
        }

        # search route
        $routeMatch = false;
        foreach ($routes as $k => &$v) {
            if (!is_array($v)) {
                $v = array('pattern' => $k, 'callback' => $v);
            } else if (isset($v[0])) {
                $v['pattern']  = $v[0]; unset($v[0]);
                $v['callback'] = $v[1]; unset($v[1]);
            }
            $v['key'] = $k;
            $pattern = $v['pattern'];
            $callback = $v['callback'];
            if ($options['wrap']) { # wrap keys
                $pattern = '#^' . $pattern . '$#i';
            }
            # compare
            if (is_string($callback)) {
                $routeCallback = preg_replace($pattern, $callback, $req, 1, $found);
                if ($found && $routeCallback !== '') {
                    $routeMatch = $k;
                    break;
                }
            } else {
                if (preg_match($pattern, $req, $matches)) {
                    $routeCallback = '';
                    $routeMatch = $k;
                    break;
                }
            }
        } unset($v);

        # init class-event-params
        $result = array('class'=>false, 'event'=>false, 'params'=>[], 'route'=>false);
        if ($routeMatch !== false) {
            $result['route'] = $routes[$routeMatch];
            $routeCallback = explode('/', $routeCallback, 3);
            if (isset($routeCallback[0])) $result['class'] = $routeCallback[0];
            if (isset($routeCallback[1])) $result['event'] = $routeCallback[1];
            if (isset($routeCallback[2])) {
                parse_str($routeCallback[2], $result['params']);
                $_GET = array_merge($_GET, $result['params']);
            }
            if ($options['init-class-event']) {
                if (!empty($result['class'])) static::$class = $result['class'];
                if (!empty($result['event'])) static::$event = $result['event'];
            }
        }

        # redirects
        if ($options['redirects'] && empty($reqNew) && \Request::isGET()) {
            \SEO::redirectsProcess($req);
        }

        return $result;
    }

    /**
     * Логирование сообщение
     * @param string|array $message сообщение
     * @param string|integer|array $level уровень логирования
     * @param string $name название логгера
     * @param array $context данные контекста
     * @return bool
     */
    public static function log($message, $level = Logger::ERROR, $name = Logger::DEFAULT_FILE, array $context = array())
    {
        if (is_array($message)) {
            $message = print_r($message, true);
        }
        if (is_string($level) && mb_stripos($level, '.log') !== false) {
            $name = $level;
            $level = Logger::ERROR;
        }
        if (is_array($level)) {
            foreach ($level as $k => $v) {
                $context[$k] = $v;
            }
            $level = Logger::ERROR;
        }
        return static::logger($name)->log($level, $message, $context);
    }

    /**
     * Инициализируем объект логгера
     * @param string $name имя логгера
     * @param string $fileName путь к файлу или false
     * @param integer|bool $level
     * @param array $handlers
     * @param array $processors
     * @return Logger
     */
    public static function logger($name, $fileName = false, $level = false, array $handlers = array(), array $processors = array())
    {
        static $loggers = [];
        if ( ! isset($loggers[$name])) {
            if (!empty($fileName) || empty($handlers)) {
                if (empty($fileName)) {
                    $fileName = $name;
                    $name = 'core';
                }
                $i = Logger::factoryRotatingFile($name, $fileName, $level, false, $handlers, $processors);
            } else {
                $i = new Logger($name, $handlers, $processors);
            }
            $loggers[$name] = $i;
        }
        return $loggers[$name];
    }

    /**
     * Установка meta тегов
     * @param string|bool $title заголовок страницы
     * @param string|bool $keywords ключевые слова
     * @param string|bool $description описание
     * @param array $macrosData данные для макросов
     * @param bool $last true - окончательный вариант, false - перекрываемый более поздним вызовом setMeta
     */
    public static function setMeta($title = false, $keywords = false, $description = false, array $macrosData = array(), $last = true)
    {
        static $set = false;
        if ($set === true && !$last) return;
        if ($last) $set = true;

        # заменяем макросы
        $data = array();
        if (!empty($title)) $data['mtitle'] = $title;
        if (!empty($keywords)) $data['mkeywords'] = $keywords;
        if (!empty($description)) $data['mdescription'] = $description;
        $data = \SEO::i()->metaTextPrepare($data, $macrosData);

        # устанавливаем meta теги
        foreach ($data as $k => &$v) {
            if (empty($v)) continue;
            \SEO::i()->metaSet($k, $v);
            config::set($k . '_' . LNG, trim($v, ' -|,')); # old version compability
        }
        unset($v);
    }

    /**
     * Метод, вызываемый перед завершением запроса
     */
    public static function shutdown()
    {
        static::hook('app.shutdown');
        exit;
    }

    /**
     * Создаем псевдоним класса class_ => class
     * @param string $className имя класса
     * @param string|boolean $originalName исходное имя класса или false = 'class_'
     */
    public static function classAlias($className, $originalName = false)
    {
        $originalName = mb_strtolower($originalName === false ? $className.'_' : $originalName);
        if (!class_exists($className, false) && class_exists($originalName, false)) {
            if (!empty(static::$classExtensions[$originalName])) {
                foreach (static::$classExtensions[$originalName] as $v) {
                    class_alias($originalName, $v['base']);
                    include_once $v['path'];
                    $originalName = $v['name'];
                }
            }
            class_alias($originalName, $className);
        }
    }

    protected static $classExtensions = array();

    /**
     * Расширение класса
     * @param string $className имя исходного класса
     * @param string $extensionName имя класса расширения
     * @param string $filePath путь к файлу объявляющему класс расширения
     * @param boolean|null $adminPanel admin-контекст (true), frontend-контекст (false), независимо от контекста (null)
     */
    public static function classExtension($className, $extensionName, $filePath, $adminPanel = null)
    {
        if (empty($className) || empty($extensionName) || !file_exists($filePath)) {
            return false;
        }
        if (is_bool($adminPanel) && $adminPanel !== static::adminPanel()) {
            return false;
        }
        static::$classExtensions[mb_strtolower($className)][] = array(
            'base' => $extensionName.'_Base',
            'name' => $extensionName,
            'path' => $filePath,
        );
    }

    /**
     * Autoload
     * @param string $className имя требуемого класса
     */
    public static function autoload($className)
    {
        $className = ltrim($className, '\\');
        if (isset(static::$autoloadMap[$className])) {
            list($group, $path) = static::$autoloadMap[$className];
            $aliasOf = (!empty(static::$autoloadMap[$className]['aliasof']) ? static::$autoloadMap[$className]['aliasof'] : false);

            # ищем среди компонентов
            switch ($group) {
                case 'core': # ядра
                    if (file_exists(PATH_CORE . $path)) {
                        include modification(PATH_CORE . $path, true, false);
                        static::classAlias($className, $aliasOf);

                        return class_exists($className, false) || interface_exists($className, false);
                    }
                    break;
                case 'app': # приложения
                    if (file_exists(PATH_BASE . $path)) {
                        include modification(PATH_BASE . $path, true, false);
                        static::classAlias($className, $aliasOf);

                        return class_exists($className, false) || interface_exists($className, false);
                    }
                    break;
            }
        } else {
            # псевдоним класса: class_ => class
            if (class_exists($className.'_', false)) {
                static::classAlias($className);
                return true;
            }
            # ищем среди компонентов проекта
            $path = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
            if (is_file(PATH_BASE . $path)) {
                include modification(PATH_BASE . $path, true, false);
                static::classAlias($className);
                return class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false);
            }
            $pathLower = mb_strtolower($path);
            if (is_file(PATH_BASE . $pathLower)) {
                include modification(PATH_BASE . $pathLower, true, false);
                static::classAlias($className);

                return class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false);
            }
            # ищем требуемый класс среди модулей (ядра/приложения)
            if (class_exists('bff', false)) {
                try {
                    \bff::i()->getModule($className);
                } catch (\Exception $e) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Расширяем autoload
     * @param array $classes :
     *  array(
     *      'имя класса' => array('ключ группы, варианты: [app, core]', 'путь к файлу, относительно директории группы', ...)
     *  )
     */
    public static function autoloadEx(array $classes = array())
    {
        if (!empty($classes)) {
            foreach ($classes as $k => $v) {
                static::$autoloadMap[$k] = $v;
            }
        }
    }

    /**
     * @var array autoload карта
     *  array(
     *      'имя класса' => array('ключ группы: [app, core]', 'путь к файлу, относительно директории группы')
     *  )
     */
    protected static $autoloadMap = array(
        # app
        'tpl'                   => array('app', 'app/tpl.php'),
        'tplAdmin'              => array('app', 'app/tpl.admin.php'),
        'Module'                => array('app', 'app/module.php'),
        'Security'              => array('app', 'app/security.php'),
        'Logger'                => array('core', 'logs/logger.php', 'aliasof'=>'\bff\logs\Logger'),
        'js'                    => array('core', 'base/js.php', 'aliasof'=>'\bff\base\js'),
        'User'                  => array('core', 'base/user.php', 'aliasof'=>'\bff\base\User'),
        'HTML'                  => array('core', 'base/html.php', 'aliasof'=>'\bff\base\HTML'),
        'View'                  => array('core', 'base/view.php', 'aliasof'=>'\bff\base\View'),
        'CMenu'                 => array('core', 'menu.php', 'aliasof'=>'\bff\Menu'),
        'Request'               => array('core', 'base/request.php', 'aliasof'=>'\bff\base\Request'),
        'Response'              => array('core', 'base/response.php', 'aliasof'=>'\bff\base\Response'),
        'CSitemapXML'           => array('core', 'utils/sitemap.php', 'aliasof'=>'\bff\utils\Sitemap'),
        # files
        'CUploader'             => array('core', 'files/uploader.php', 'aliasof'=>'\bff\files\Uploader'),
        'CImageUploader'        => array('core', 'img/image.uploader.php'),
        'CImagesUploader'       => array('core', 'img/images.uploader.php'),
        'CImagesUploaderField'  => array('core', 'img/images.uploader.field.php'),
        'CImagesUploaderTable'  => array('core', 'img/images.uploader.table.php'),
        'bff\files\AttachmentsTable' => array('core', 'files/attachments.table.php'),
        # captcha
        'CCaptchaProtection'    => array('core', 'captcha/captcha.protection.php'),
        # core modules
        'UsersAvatar'           => array('app', 'modules/users/users.avatar.php'),
        'UsersSocial'           => array('app', 'modules/users/users.social.php'),
        # database
        'bff\db\Categories'     => array('core', 'db/categories/categories.php'),
        'bff\db\Comments'       => array('core', 'db/comments/comments.php'),
        'bff\db\Dynprops'       => array('core', 'db/dynprops/dynprops.php'),
        'bff\db\NestedSetsTree' => array('core', 'db/nestedsets/nestedsets.php'),
        'bff\db\Publicator'     => array('core', 'db/publicator/publicator.php'),
        'bff\db\Tags'           => array('core', 'db/tags/tags.php'),
        # external
        'Pimple'                => array('core', 'external/pimple.php'),
        'Mobile_Detect'         => array('core', 'external/mobile.detect.php'),
        'CMail'                 => array('core', 'external/mail.php', 'aliasof'=>'\bff\external\Mail'),
        'CSmarty'               => array('core', 'external/smarty.php', 'aliasof'=>'\bff\external\Smarty'),
        'CWysiwyg'              => array('core', 'external/wysiwyg.php', 'aliasof'=>'\bff\external\Wysiwyg'),
        'qqFileUploader'        => array('core', 'external/qquploader.php'),
        'Parsedown'             => array('core', 'external/parsedown/parsedown.php'),
        'Minifier'              => array('core', 'external/minifier.php'),
        # core
        'Model'                 => array('core', 'model.php', 'aliasof'=>'\bff\Model'),
        'Errors'                => array('core', 'errors.php', 'aliasof'=>'\bff\Errors'),
        'Hook'                  => array('core', 'extend/hook.php', 'aliasof'=>'\bff\extend\Hook'),
        'Hooks'                 => array('core', 'extend/hooks.php', 'aliasof'=>'\bff\extend\Hooks'),
        'Plugin'                => array('core', 'extend/plugin.php', 'aliasof'=>'\bff\extend\Plugin'),
        'Theme'                 => array('core', 'extend/theme.php', 'aliasof'=>'\bff\extend\Theme'),
        'func'                  => array('core', 'utils/func.php', 'aliasof'=>'\bff\utils\func'),
        'Pagination'            => array('core', 'utils/pagination.php', 'aliasof'=>'\bff\utils\Pagination'),
        'config'                => array('core', 'config.php'),
        'Cache'                 => array('core', 'cache/cache.php', 'aliasof'=>'\bff\cache\Cache'),
        'Component'             => array('core', 'component.php', 'aliasof'=>'\bff\Component'),
        'CronManager'           => array('core', 'cron.php', 'aliasof'=>'\bff\CronManager'),
    );

}