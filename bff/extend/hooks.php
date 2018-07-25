<?php namespace bff\extend;

/**
 * Плагинизация: хуки
 * @version 0.59
 * @modified 9.mar.2018
 */

class Hooks
{
    const PRIORITY_LIMIT = 10;

    protected $data;

    /**
     * Запуск хука
     * @param string $key ключ
     */
    public function run($key)
    {
        $args = func_get_args(); array_shift($args);
        if (\BFF_DEBUG && \config::sys('debug.hooks', false)) {
            \bff::log('hook-run: "'.$key.'"');
        }
        if ( ! empty($this->data[$key])) {
            for ($p = 0; $p <= static::PRIORITY_LIMIT; $p++) {
                if (!empty($this->data[$key][$p])) {
                    foreach ($this->data[$key][$p] as $c) {
                        call_user_func_array($c, $args);
                    }
                }
            }
        }
    }

    /**
     * Применение хука
     * @param string $key ключ
     * @return mixed
     */
    public function apply($key)
    {
        $args = func_get_args(); array_shift($args);
        $content = (isset($args[0]) ? $args[0] : '');
        if (\BFF_DEBUG && \config::sys('debug.hooks', false)) {
            \bff::log('hook-apply: "'.$key.'"');
        }

        if (!empty($this->data[$key])) {
            for ($p = 0; $p <= static::PRIORITY_LIMIT; $p++) {
                if (!empty($this->data[$key][$p])) {
                    foreach ($this->data[$key][$p] as $c) {
                        if (is_callable($c)) {
                            $content = call_user_func_array($c, $args);
                            $args[0] = $content;
                        }
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Добавление хука
     * @param string $key ключ
     * @param callable $callable функция обработчик
     * @param int $priority приоритет вызова
     * @return \Hook|boolean
     */
    public function add($key, callable $callable, $priority = 5)
    {
        $key = trim(strval($key));
        if ($callable instanceof \Closure) {
            $callable = new \Hook($callable);
        }
        if ( ! empty($this->data[$key])) {
            foreach ($this->data[$key] as &$hooks) {
                foreach ($hooks as $c) {
                    if ($c == $callable) {
                        return false; # уже существует
                    }
                }
            } unset($hooks);
        }
        if (is_null($priority) || $priority < 0) $priority = 5;
        $this->data[$key][$priority][] = $callable;
        return $callable;
    }

    /**
     * Удаление хука
     * @param string $key ключ
     * @param callable $callable функция обработчик
     * @return boolean
     */
    public function remove($key, callable $callable)
    {
        if ( ! empty($this->data[$key])) {
            if ($callable instanceof \Closure) {
                $callable = new \Hook($callable);
            }
            foreach ($this->data[$key] as $p=>&$hooks) {
                foreach ($hooks as $k => $c) {
                    if ($c == $callable) {
                        unset($hooks[$k], $hooks);
                        if (empty($this->data[$key][$p])) {
                            unset($this->data[$key][$p]);
                        }
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Проверка наличия хука
     * @param string $key ключ
     * @return boolean
     */
    public function has($key)
    {
        return !empty($this->data[$key]);
    }

    ## Доступные хуки и фильтры:

    /**
     * Хук вызываемый после инициализации приложения
     * @see \bff\base\app::init
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function appInit(callable $callback, $priority = NULL)
    {
        return $this->add('app.init', $callback, $priority);
    }

    /**
     * Расширение списка middleware (посредников) приложения
     * @see \bff::init
     * @param callable|string $callable замыкание или название класса с реализованным методом __invoke
     * @param array $options доп. опции:
     *   int 'priority' приоритет запуска посредника
     *   bool|null 'admin' выполнять запуск посредника в админ. панели
     *   string|array 'route' ключ роута за которым следует закрепить middleware
     * @return \Hook
     */
    public function appMiddleware($callable, array $options = array())
    {
        return $this->add('app.middleware', function($list) use ($callable, $options) {
            $middleware = array_merge(['callback'=>$callable], $options);
            if ( ! empty($options['route'])) {
                $route = $options['route'];
                $this->add('routes', function($list) use ($route, $middleware) {
                    if (is_string($route)) {
                        if (isset($list[$route])) {
                            $list[$route]['middleware'][] = $middleware;
                        }
                    } else if (is_array($route)) {
                        foreach ($route as $v) {
                            if (isset($list[$v])) {
                                $list[$v]['middleware'][] = $middleware;
                            }
                        }
                    }
                    return $list;
                });
            } else {
                $list[] = $middleware;
            }
            return $list;
        });
    }

    /**
     * Хук вызываемый сразу после инициализации модуля
     * @param string $module название модуля
     * @param callable $callback {
     *   @param \Module $object объект модуля
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleInit($module, callable $callback, $priority = NULL)
    {
        return $this->add($module.'.init', $callback, $priority);
    }

    /**
     * Фильтр списка крон задач модуля
     * @param string $module название модуля
     * @param callable $callback {
     *   @param array $tasks список задач модуля
     *   @param \Module $object объект модуля
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleCronSettings($module, callable $callback, $priority = NULL)
    {
        return $this->add('cron.settings.module.'.$module, $callback, $priority);
    }

    /**
     * Фильтр списка шаблонов писем модуля
     * @param string $module название модуля
     * @param callable $callback {
     *   @param array $templates список шаблонов модуля
     *   @param \Module $object объект модуля
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleSendmailTemplates($module, callable $callback, $priority = NULL)
    {
        return $this->add('sendmail.templates.module.'.$module, $callback, $priority);
    }

    /**
     * Фильтр настроек SEO шаблонов модуля
     * @param string $module название модуля
     * @param callable $callback {
     *   @param array $templates список настроек шаблонов
     *   @param \Module $object объект модуля
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleSeoTemplates($module, callable $callback, $priority = NULL)
    {
        return $this->add('seo.templates.'.$module, $callback, $priority);
    }

    /**
     * Фильтр списка системных настроек модуля
     * @param string $module название модуля
     * @param callable $callback {
     *   @param array $settings дополнительные системные настройки
     *   @param \Module $object объект модуля
     *   return: array настройки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleSettingsSystem($module, callable $callback, $priority = NULL)
    {
        return $this->add('site.settings.system.extra.'.$module, $callback, $priority);
    }

    /**
     * Фильтр настроек компонента \bff\db\Publicator инициализируемого в модуле
     * @param string $module название модуля
     * @param callable $callback {
     *   @param array $settings настройки компонента
     *   @param \bff\db\Publicator $object объект компонента
     *   return: array настройки компонента
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleSettingsPublicator($module, callable $callback, $priority = NULL)
    {
        return $this->add('publicator.settings.'.$module, $callback, $priority);
    }

    /**
     * Фильтр списка локализируемых таблиц и полей модуля
     * @param string $module название модуля
     * @param callable $callback {
     *   @param array $list список
     *   @param \Module $object объект модуля
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleLocaleTables($module, callable $callback, $priority = NULL)
    {
        return $this->add('locale.tables.'.$module, $callback, $priority);
    }

    /**
     * Фильтр списка роутов модуля, в случае вызова метода bff::route() в рамках модуля
     * @see \bff\base\app::route
     * @param string $module название модуля
     * @param callable $callback {
     *   @param array $list список роутов
     *   @param array $options дополнительные опции
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleRoutes($module, callable $callback, $priority = NULL)
    {
        if (!empty($module)) {
            return $this->add($module . '.routes', $callback, $priority);
        }
        return $this->routes($callback, $priority);
    }

    /**
     * Фильтр формирования ссылок модуля, реализованный в статическом методе url() модуля
     * @param string $module название модуля
     * @param callable $callback {
     *   @param string $url сформированный URL ссылки
     *   @param array $data - параметры ссылки:
     *      string 'key' - ключ
     *      array 'opts' - доп. параметры
     *      boolean 'dynamic' - динамическая ссылка
     *      string 'base' - базовый URL
     *   return: string URL ссылки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleUrl($module, callable $callback, $priority = NULL)
    {
        return $this->add($module.'.url', $callback, $priority);
    }

    /**
     * Фильтр для пост-обработки шаблонов модуля
     * @see \View::render
     * @param string $module название модуля
     * @param string $template название файла шаблона модуля (без расширения)
     * @param callable $callback {
     *   @param string $content сформированный HTML шаблон
     *   @param array $data данные передаваемые в шаблон
     *   @param array $extra:
     *      string 'filePath' - путь к подключаемому файлу
     *      string 'fileName' - имя файла с расширением
     *      string 'relPath' - относительный путь
     *      boolean 'theme' - подключается ли файл темы
     *   return: string HTML шаблон
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleViewTemplate($module, $template, callable $callback, $priority = NULL)
    {
        return $this->add('view.module.'.$module.'.'.$template, $callback, $priority);
    }

    /**
     * Хук для предварительной обработки данных шаблонов модуля
     * @see \View::render
     * @param string $module название модуля
     * @param string $template название файла шаблона модуля (без расширения)
     * @param callable $callback {
     *   @param array $view:
     *      array 'data' @ref - данные передаваемые по ссылке
     *      string 'filePath' - путь к подключаемому файлу
     *      string 'fileName' - имя файла с расширением
     *      string 'relPath' - относительный путь
     *      boolean 'theme' - подключается ли файл темы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function moduleViewTemplateData($module, $template, callable $callback, $priority = NULL)
    {
        return $this->add('view.module.'.$module.'.'.$template.'.data', $callback, $priority);
    }

    /**
     * Хук вызываемый после инициализации плагина, перед вызовом метода start
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param \Plugin $object объект плагина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginInit($plugin, callable $callback, $priority = NULL)
    {
        return $this->add('plugins.init.'.$plugin, $callback, $priority);
    }

    /**
     * Хук вызываемый после старта плагина
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param \Plugin $object объект плагина
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginStart($plugin, callable $callback, $priority = NULL)
    {
        return $this->add('plugins.start.'.$plugin, $callback, $priority);
    }

    /**
     * Фильтр списка крон задач плагина
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param array $tasks список задач плагина
     *   @param \Plugin $object объект плагина
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginCronSettings($plugin, callable $callback, $priority = NULL)
    {
        return $this->add('cron.settings.plugin.'.$plugin, $callback, $priority);
    }

    /**
     * Фильтр списка шаблонов писем плагина
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param array $templates список шаблонов плагина
     *   @param \Plugin $object объект плагина
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginSendmailTemplates($plugin, callable $callback, $priority = NULL)
    {
        return $this->add('sendmail.templates.plugin.'.$plugin, $callback, $priority);
    }

    /**
     * Фильтр настроек SEO шаблонов плагина
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param array $templates список настроек шаблонов
     *   @param \Plugin $object объект плагина
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginSeoTemplates($plugin, callable $callback, $priority = NULL)
    {
        return $this->add('seo.templates.'.$plugin, $callback, $priority);
    }

    /**
     * Фильтр списка табов формы настроек плагина (админ. панель)
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param array $tabs список названий пунктов меню
     *   @param \Plugin $plugin объект плагина
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginSettingsTabs($plugin, callable $callback, $priority = NULL)
    {
        return $this->add('extensions.plugins.'.$plugin.'.settings.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы настроек плагина (админ. панель)
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param array $data:
     *      array 'settings' @ref настройки плагина
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginSettingsTabsContent($plugin, callable $callback, $priority = NULL)
    {
        return $this->add('extensions.plugins.'.$plugin.'.settings.tabs.content', $callback, $priority);
    }

    /**
     * Фильтр списка роутов плагина, в случае вызова метода bff::route() в рамках плагина
     * @param string $plugin название плагина
     * @param callable $callback {
     *   @param array $list список роутов
     *   @param array $options дополнительные опции
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginRoutes($plugin, callable $callback, $priority = NULL)
    {
        return $this->add($plugin.'.routes', $callback, $priority);
    }

    /**
     * Фильтр для пост-обработки шаблонов плагина
     * @see \View::render
     * @param string $plugin название плагина
     * @param string $template название файла шаблона плагина (без расширения)
     * @param callable $callback {
     *   @param string $content сформированный HTML шаблон
     *   @param array $data данные передаваемые в шаблон
     *   @param array $extra:
     *      string 'filePath' - путь к подключаемому файлу
     *      string 'fileName' - имя файла с расширением
     *      string 'relPath' - относительный путь
     *      boolean 'theme' - подключается ли файл темы
     *   return: string HTML шаблон
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginViewTemplate($plugin, $template, callable $callback, $priority = NULL)
    {
        return $this->add('view.plugin.'.$plugin.'.'.$template, $callback, $priority);
    }

    /**
     * Хук для предварительной обработки данных шаблонов плагина
     * @see \View::render
     * @param string $plugin название плагина
     * @param string $template название файла шаблона плагина (без расширения)
     * @param callable $callback {
     *   @param array $view:
     *      array 'data' @ref - данные передаваемые по ссылке
     *      string 'filePath' - путь к подключаемому файлу
     *      string 'fileName' - имя файла с расширением
     *      string 'relPath' - относительный путь
     *      boolean 'theme' - подключается ли файл темы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function pluginViewTemplateData($plugin, $template, callable $callback, $priority = NULL)
    {
        return $this->add('view.plugin.'.$plugin.'.'.$template.'.data', $callback, $priority);
    }

    /**
     * Фильтр списка табов формы настроек темы (админ. панель)
     * @param string $theme название темы
     * @param callable $callback {
     *   @param array $tabs список названий пунктов меню
     *   @param \Theme $theme объект темы
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function themeSettingsTabs($theme, callable $callback, $priority = NULL)
    {
        return $this->add('extensions.themes.'.$theme.'.settings.tabs', $callback, $priority);
    }

    /**
     * Хук расширения содержимого табов формы настроек темы (админ. панель)
     * @param string $theme название темы
     * @param callable $callback {
     *   @param array $data:
     *      array 'settings' @ref настройки темы
     *      array 'tabs' табы формы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function themeSettingsTabsContent($theme, callable $callback, $priority = NULL)
    {
        return $this->add('extensions.themes.'.$theme.'.settings.tabs.content', $callback, $priority);
    }

    /**
     * Фильтр для пост-обработки общих шаблонов, из директории /tpl/
     * @see \View::render
     * @param string $template название файла общего шаблона (без расширения)
     * @param callable $callback {
     *   @param string $content сформированный HTML шаблон
     *   @param array $data данные передаваемые в шаблон
     *   @param array $extra:
     *      string 'filePath' - путь к подключаемому файлу
     *      string 'fileName' - имя файла с расширением
     *      string 'relPath' - относительный путь
     *      boolean 'theme' - подключается ли файл темы
     *   return: string HTML шаблон
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function viewTemplate($template, callable $callback, $priority = NULL)
    {
        return $this->add('view.tpl.'.$template, $callback, $priority);
    }

    /**
     * Хук для предварительной обработки данных общих шаблонов, из директории /tpl/
     * @see \View::render
     * @param string $template название файла общего шаблона (без расширения)
     * @param callable $callback {
     *   @param array $view:
     *      array 'data' @ref - данные передаваемые по ссылке
     *      string 'filePath' - путь к подключаемому файлу
     *      string 'fileName' - имя файла с расширением
     *      string 'relPath' - относительный путь
     *      boolean 'theme' - подключается ли файл темы
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function viewTemplateData($template, callable $callback, $priority = NULL)
    {
        return $this->add('view.tpl.'.$template.'.data', $callback, $priority);
    }

    /**
     * Фильтр списка роутов проекта
     * @see \bff\base\app::routes
     * @param callable $callback {
     *   @param array $list список роутов
     *   @param array $options дополнительные опции
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function routes(callable $callback, $priority = NULL)
    {
        return $this->add('routes', $callback, $priority);
    }

    /**
     * Фильтр списка определяющего порядок основных разделов меню в панели управления
     * @see \CMenu::buildAdminMenu
     * @param callable $callback {
     *   @param array $tabs список названий пунктов меню
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function adminMenuTabs(callable $callback, $priority = NULL)
    {
        return $this->add('admin.menu.tabs', $callback, $priority);
    }

    /**
     * Хук вызываемый после завершения формирования списка меню в панели управления
     * @see \CMenu::buildAdminMenu
     * @param callable $callback {
     *   @param \bff\Menu $object
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function adminMenuBuild(callable $callback, $priority = NULL)
    {
        return $this->add('admin.menu.build', $callback, $priority);
    }

    /**
     * Фильтр списка пунктов со счетчиками в шапке в панели управления
     * @param callable $callback {
     *   @param array $list список
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function adminMenuHeaderCounters(callable $callback, $priority = NULL)
    {
        return $this->add('admin.menu.header.counters', $callback, $priority);
    }

    /**
     * Фильтр списка пунктов меню в выпадающем списке пользователя в правом верхнем углу в панели управления
     * @param callable $callback {
     *   @param array $list список
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function adminMenuHeaderUser(callable $callback, $priority = NULL)
    {
        return $this->add('admin.menu.header.user', $callback, $priority);
    }

    /**
     * Хук для подключения дополнительных javascript файлов
     * @param boolean $frontend true - фронтенд, false - панель управления
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function javascriptExtra($frontend = true, callable $callback, $priority = NULL)
    {
        return $this->add(($frontend ? '' : 'admin.').'js.extra', $callback, $priority);
    }

    /**
     * Фильтр списка подключенных javascript файлов
     * @param boolean $frontend true - фронтенд, false - панель управления
     * @param callable $callback {
     *   @param array $list список файлов
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function javascriptIncludes($frontend = true, callable $callback, $priority = NULL)
    {
        return $this->add(($frontend ? '' : 'admin.').'js.includes', $callback, $priority);
    }

    /**
     * Фильтр списка javascript библиотек ядра
     * @param callable $callback {
     *   @param array $list список файлов
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function javascriptIncludesCore(callable $callback, $priority = NULL)
    {
        return $this->add('js.includes.core', $callback, $priority);
    }

    /**
     * Фильтр списка фраз локализации передаваемых в javascript объекта app (frontend)
     * @param callable $callback {
     *   @param array $list список фраз
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function javascriptAppLang(callable $callback, $priority = NULL)
    {
        return $this->add('js.app.lang', $callback, $priority);
    }

    /**
     * Хук для подключения дополнительных CSS файлов
     * @param boolean $frontend true - фронтенд, false - панель управления
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function cssExtra($frontend = true, callable $callback, $priority = NULL)
    {
        return $this->add(($frontend ? '' : 'admin.').'css.extra', $callback, $priority);
    }

    /**
     * Фильтр списка подключенных CSS файлов
     * @param boolean $frontend true - фронтенд, false - панель управления
     * @param callable $callback {
     *   @param array $list список файлов
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function cssIncludes($frontend = true, callable $callback, $priority = NULL)
    {
        return $this->add(($frontend ? '' : 'admin.').'css.includes', $callback, $priority);
    }

    /**
     * Фильтр списка данных о системах оплат используемый в ядре
     * @see \Bills::getPaySystemData
     * @param callable $callback {
     *   @param array $list список данных о системах оплаты
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPaySystemsData(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.systems.data', $callback, $priority);
    }

    /**
     * Фильтр списка доступных для выбора способов оплаты отображаемый пользователю
     * @see \Bills::getPaySystems
     * @param callable $callback {
     *   @param array $list список данных о системах оплаты
     *   @param array $extra дополнительные данные
     *      boolean 'balanceUse' - доступен ли выбор оплаты со счета
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPaySystemsUser(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.systems.user', $callback, $priority);
    }

    /**
     * Хук обработки события сабмита формы пополнения счета
     * @see \Bills::my_pay
     * @param callable $callback {
     *   @param float $amount сумма указанная пользователем
     *   @param string $paySystem ключ системы оплаты
     *   @param array $pay сумма к оплате:
     *      float 'amount' - сумма
     *      integer 'currency' - ID валюты
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPaySubmit(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.submit', $callback, $priority);
    }

    /**
     * Фильтр формы оплаты счета отправляемый провайдеру системы оплаты
     * @see \Bills::buildPayRequestForm
     * @param callable $callback {
     *   @param string $form HTML форма
     *   @param int $paySystem ID системы оплаты
     *   @param array $data дополнительные данные о выставляемом счете
     *   return: string HTML форма
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPayForm(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.form', $callback, $priority);
    }

    /**
     * Хук обработки запроса от системы оплаты
     * @see \Bills::processPayRequest
     * @param callable $callback {
     *   @param string $paySystem ключ системы оплаты выполняющей запрос либо название метода
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPayProcess(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.process', $callback, $priority);
    }

    /**
     * Хук обработки страницы успешной оплаты
     * @see \Bills::success
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPaySuccess(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.success', $callback, $priority);
    }

    /**
     * Хук обработки страницы ошибки/отмены оплаты
     * @see \Bills::fail
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPayFail(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.fail', $callback, $priority);
    }

    /**
     * Хук обработки результирующего метода (требуется для некоторых систем оплаты)
     * @see \Bills::result
     * @param callable $callback
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsPayResult(callable $callback, $priority = NULL)
    {
        return $this->add('bills.pay.result', $callback, $priority);
    }

    /**
     * Фильтр списка статусов счета
     * @see \Bills::getStatusData
     * @param callable $callback {
     *   @param array $list список доступных статусов
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function billsStatusList(callable $callback, $priority = NULL)
    {
        return $this->add('bills.status.list', $callback, $priority);
    }

    /**
     * Фильтр настроек отправки почты
     * @param callable $callback {
     *   @param array $settings настройки почты
     *   return: array настройки
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function mailConfig(callable $callback, $priority = NULL)
    {
        return $this->add('mail.config', $callback, $priority);
    }

    /**
     * Фильтр данных письма перед отправкой
     * @param callable $callback {
     *   @param array $data данные о письме:
     *      string 'to' @ref email получателя
     *      string 'from' @ref email отправителя или пустая строка
     *      string 'fromName' @ref имя отправителя или пустая строка
     *      string 'body' @ref текст письма
     *      string 'subject' @ref заголовок письма
     *      array 'customHeaders' @ref доп. заголовки письма
     *   return: array данные, если указан ответ в формате ['sended'=>any value], стандартная отправка письма не выполняется
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function mailSend(callable $callback, $priority = NULL)
    {
        return $this->add('mail.send', $callback, $priority);
    }

    /**
     * Фильтр данных шаблона письма перед отправкой
     * @param callable $callback {
     *   @param array $data данные о шаблоне письма:
     *      string 'to' email получателя
     *      string 'from' email отправителя или пустая строка
     *      string 'fromName' имя отправителя или пустая строка
     *      string 'body' текст письма
     *      string 'subject' заголовок письма
     *      boolean 'is_html' используется HTML шаблон письма
     *      integer 'wrapper_id' ID шаблона письма
     *      array 'vars' @ref данные подставляемые в шаблон '{макрос}' => 'значение'
     *      string 'lang' ключ языка шаблона
     *      array 'customHeaders' @ref доп. заголовки письма
     *   @param string $templateName название шаблона
     *   return: array данные, если указан ответ в формате true/false, стандартная отправка письма не выполняется
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function mailSendTemplate(callable $callback, $priority = NULL)
    {
        return $this->add('mail.send.template', $callback, $priority);
    }

    /**
     * Хук отправки почты, вызываемый сразу после отправки письма
     * @param callable $callback {
     *   @param array $data данные об отправке:
     *      'to', 'subject', 'body', 'from', 'fromName',
     *      'result' - результат отправки, 'time' - время отправки
     *   @param \CMail $object объект компонента отправки почты
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function mailSended(callable $callback, $priority = NULL)
    {
        return $this->add('mail.sended', $callback, $priority);
    }

    /**
     * Фильтр списка meta-данных выводимых в блоке <head>
     * @see \SEO::metaRender
     * @param callable $callback {
     *   @param array $view список meta-данных
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function seoMetaRender(callable $callback, $priority = NULL)
    {
        return $this->add('seo.meta.render', $callback, $priority);
    }

    /**
     * Фильтр подстановки SEO макросов в текст
     * @see \SEO::metaTextPrepare
     * @param callable $callback {
     *   @param string|array $text SEO текст или список текстов
     *   @param array $data доп. параметры:
     *      'replace' - ссылка на список замены с => на,
     *      'macrosData' - ссылка на данные подставляемые вместо макросов
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function seoMetaTextPrepare(callable $callback, $priority = NULL)
    {
        return $this->add('seo.meta.text.prepare', $callback, $priority);
    }

    /**
     * Фильтр данных передаваемых в Sitemap.xml
     * @see \Site::cronSitemapXML
     * @param callable $callback {
     *   @param array $data данные в формате callback-списка
     *   return: array список
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function siteCronSitemapXML(callable $callback, $priority = NULL)
    {
        return $this->add('site.cron.sitemapXML', $callback, $priority);
    }

    /**
     * Фильтр дополнительной проверки наличия email адреса в списке запрещенных (временных адресов) (isEmailTemporary)
     * @param callable $callback {
     *   @param bool $isTemporary был помечен системой как временный (true), не был помечен (false)
     *   @param string $email email адрес
     *   return: bool пометка
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function inputEmailTemporary(callable $callback, $priority = NULL)
    {
        return $this->add('input.email.temporary', $callback, $priority);
    }

    /**
     * Фильтр дополнительной валидации номера телефона (isPhoneNumber)
     * @param callable $callback {
     *   @param string $phoneNumber номер телефона
     *   return: string номер телефона
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function inputPhoneValidate(callable $callback, $priority = NULL)
    {
        return $this->add('input.phone.validate', $callback, $priority);
    }

    /**
     * Фильтр дополнительной валидации текста типа TYPE_TEXT
     * @param callable $callback {
     *   @param string $text текст
     *   return: string текст
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function inputTextValidate(callable $callback, $priority = NULL)
    {
        return $this->add('input.text.validate', $callback, $priority);
    }

    /**
     * Фильтр дополнительной валидации строки для поиска (cleanSearchString)
     * @param callable $callback {
     *   @param string $string строка поиска
     *   return: string
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function inputSearchStringValidate(callable $callback, $priority = NULL)
    {
        return $this->add('input.searchstring.validate', $callback, $priority);
    }

    /**
     * Фильтр дополнительной транслитерации текста
     * @see \func::translit
     * @param callable $callback {
     *   @param string $text текст для которого выполняется транслитерация
     *   return: string
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function utilsFuncTranslit(callable $callback, $priority = NULL)
    {
        return $this->add('utils.func.translit', $callback, $priority);
    }

    /**
     * Фильтр списка транслитерируемых символов применяемых при транслитерации текста
     * @see \func::translit
     * @param callable $callback {
     *   @param array $list список 'символ'=>'транслитерация'
     *   return: array
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function utilsFuncTranslitConvert(callable $callback, $priority = NULL)
    {
        return $this->add('utils.func.translit.convert', $callback, $priority);
    }

    /**
     * Фильтр списка поддерживаемых провайдеров видео ссылок
     * @param callable $callback {
     *   @param array $list список настроек провайдеров
     *   return: array
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function utilsVideoparserProvidersList(callable $callback, $priority = NULL)
    {
        return $this->add('utils.videoparser.providers.list', $callback, $priority);
    }

    /**
     * Фильтр списка директорий и файлов требующих проверки прав записи
     * @see \Dev::writableCheckProcess
     * @param callable $callback {
     *   @param array $list список
     *   return: array
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function devWritableCheckList(callable $callback, $priority = NULL)
    {
        return $this->add('dev.writable.check.list', $callback, $priority);
    }

    /**
     * Фильтр реализующий возможность подключения другого провайдера определения региона по IP
     * @see \GeoModel::regionDataByIp
     * @param callable $callback {
     *   @param bool|array $data данные о регионе или false
     *   @param string $ipAddress IP адрес пользователя
     *   @param string $provider ключ провайдера указанный в системной настройке 'geo.ip.location.provider'
     *   return: array|bool данные о регионе или false
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function geoIpLocationRegion(callable $callback, $priority = NULL)
    {
        return $this->add('geo.ip.location.region', $callback, $priority);
    }

    /**
     * Хук страницы HTTP ошибок (404, ...)
     * @see \Errors::errorHttp
     * @param callable $callback {
     *   @param int $errorCode ключ ошибки: 404, ...
     *   @param array $context контекст: [
     *      array 'data' @ref данные об ошибке: 'title' - заголовок, 'message' - описание
     *      \Psr\Http\Message\ResponseInterface 'response' @ref объект ответа
     *      string 'template' @ref название шаблона по-умолчанию
     *   ]
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function errorsHttpError(callable $callback, $priority = NULL)
    {
        return $this->add('errors.http.error', $callback, $priority);
    }

    /**
     * Фильтр тегированного запроса в базу данных
     * @see \bff\db\Database::tag, \bff\db\Database::exec
     * @param string $key ключ тега
     * @param callable $callback {
     *   @param string $query строка запроса
     *   @param array $data параметры запроса:
     *      array 'bind' @ref агрументы запроса
     *      array 'data' @ref доп. параметры
     *   return: string строка запроса
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function databaseQuery($key, callable $callback, $priority = NULL)
    {
        return $this->add('db.query.'.$key, $callback, $priority);
    }

    /**
     * Фильтр данных тегированного запроса в базу данных
     * @see \bff\db\Database::tag
     * @param string $key ключ тега
     * @param callable $callback {
     *   @param array $data данные запроса
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public function databaseQueryData($key, callable $callback, $priority = NULL)
    {
        return $this->add('db.query.'.$key.'.data', $callback, $priority);
    }
}