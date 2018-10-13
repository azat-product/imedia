<?php namespace bff\extend\theme;

use bff\extend\Extension;
use bff\extend\ExtensionInterface;
use const bff\extend\EXTENSION_TYPE_THEME;

/**
 * Плагинизация: тема
 * @version 2.1
 * @modified 6.aug.2018
 * @copyright Tamaranga
 */

class Base extends \Module implements ExtensionInterface
{
    use Extension {
        config as configBase;
    }

    /**
     * Внутреннее название темы
     * @var string
     */
    protected $theme_name = '';
    /**
     * Название темы отображаемое в админ. панели
     * @var string
     */
    protected $theme_title = '?';
    /**
     * Версия темы
     * @var string
     */
    protected $theme_version = '?';
    /**
     * Объект исходной темы или её название
     * @var \Theme|string
     */
    protected $theme_parent;
    /**
     * Объекты тем наследников
     * @var \SplObjectStorage
     */
    protected $theme_children;

    /**
     * Инициализация
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->extension_type = EXTENSION_TYPE_THEME;
        $this->module_name    = $name = $this->getName();
        $this->module_title   = $this->getTitle();
        $this->module_dir     = $this->extension_path;
        $this->theme_children = new \SplObjectStorage();
        if ($this->isBaseTheme()) {
            $this->theme_title = _t('ext', 'Стандартная тема');
            $this->theme_version = BFF_VERSION;
        }

        if (\bff::adminPanel()) {
            foreach (array('install','uninstall','activate','deactivate') as $action) {
                \bff::hookAdd('themes.' . $action . '.' . $name, function () use ($action) {
                    if ($action !== 'deactivate') {
                        $this->refreshStatic(in_array($action, ['install', 'activate']));
                    }
                    return $this->$action();
                }, 4);
            }
        }
        \bff::hookAdd('themes.update.'.$name, function ($return, $context) {
            $this->refreshStatic(true);
            return $this->update($context);
        }, 4);
        $started = false;
        \bff::hookAdd('themes.start.'.$name, function($obj, $testMode) use (&$started) {
            if ( ! $started && ( $this->isActive($testMode) || $obj->isParentTheme() ) ) {
                $started = true;
                if (BFF_DEBUG) {
                    $this->refreshStatic(true);
                }
                # Start:
                $this->extensionStart();
                $this->start();
            }
        }, 4);

        # Logo:
        $this->configSettingsTemplateRegister('logo', [
            'template' => [
                'input' => 'image',
                'image' => array(
                    'sizes' => array(
                        'view',
                    ),
                ),
                'tab' => '_logo',
            ],
            'keys.overrideBy' => ['logo'],
            'tab' => [
                'key'   => '_logo',
                'title' => _t('ext', 'Логотип'),
            ],
        ], function($settings){
            foreach ($settings as $posKey=>$posSettings) {
                \bff::hookAdd('site.logo.url.' . $posKey, function ($url) use ($posKey, $posSettings) {
                    $logo = $this->configImages($posSettings['config.key'], 'view');
                    return (!empty($logo) ? $logo : (!empty($posSettings['default']) ? $posSettings['default'] : $url));
                });
            }
        });

        # Favicon:
        $this->configSettingsTemplateRegister('favicon', [
            'template' => [
                'input' => 'file',
                'file'  => [
                    'extensionsAllowed' => 'png,svg,ico',
                    'maxSize' => 2097152, # 2Mb
                    'publicStore' => true,
                ],
                'tab'   => '_favicon',
            ],
            'keys.overrideBy' => ['favicon'],
            'tab' => [
                'key'   => '_favicon',
                'title' => _t('ext', 'Favicon'),
            ],
        ], function($settings){
            $iconsList = array();
            foreach ($settings as $iconKey=>$iconSettings) {
                $icon = $this->configFiles($iconSettings['config.key'], true);
                if ( ! empty($icon[0]['url'])) {
                    $icon = current($icon);
                    if ($icon['url'][0] === '/') {
                        $icon['url'] = \Request::scheme().':'.$icon['url'];
                    }
                    $iconSettings['attr']['href'] = $icon['url'];
                    $iconsList[$iconKey] = $iconSettings['attr'];
                } else if ( ! empty($iconSettings['default'])) {
                    if (is_string($iconSettings['default'])) {
                        $iconSettings['attr']['href'] = $iconSettings['default'];
                        $iconsList[$iconKey] = $iconSettings['attr'];
                    } else if (is_array($iconSettings['default'])) {
                        $iconsList[$iconKey] = $iconSettings['default'];
                    }
                }
            }
            if (sizeof($iconsList) > 0) {
                \bff::hookAdd('site.favicon.list', function($list) use ($iconsList) {
                    return $iconsList;
                });
            }
        });
    }

    /**
     * Параметры загружаемых логотипов сайта
     * @param array|null $settings настройки
     * @param bool $extend расширить базовые настройки
     * @return array [
     *      'уникальный ключ позиции' => [
     *          'title' => 'название позиции',
     *          'tip' => 'описание позиции',
     *          'default' => 'URL по умолчанию', # например /img/logo.png
     *      ],
     *      ...
     * ]
     */
    protected function siteLogo(array $settings, $extend = false)
    {
        $this->configSettingsTemplate('logo', $settings, $extend);
    }

    /**
     * Параметры загружаемых favicon изображений
     * @param array $settings настройки
     * @param bool $extend расширить базовые настройки
     * @return array [
     *      'уникальный ключ' => [
     *          'title' => 'название поля загрузки файла',
     *          'description' => 'примечание',
     *          'attr' => [
     *              # атрибуты мета-тега
     *              'rel' => 'icon',
     *          ],
     *          'default' => 'URL по умолчанию', # например /favicon.ico
     *          'file' => [
     *              # допустимые параметры загружаемых файлов
     *              'extensionsAllowed' => 'png,svg,ico', # разрешенные расширения
     *              'maxSize' => '2097152', # максимально допустимый размер файла в байтах
     *          ],
     *      ],
     *      ...
     */
    protected function siteFavicon(array $settings, $extend = false)
    {
        $this->configSettingsTemplate('favicon', $settings, $extend);
    }

    /**
     * Устанавливаем исходную тему
     * @param Theme $parent
     * @return boolean
     * @throws \Exception
     */
    public function setParentTheme(self $parent)
    {
        $this->theme_parent = $parent;
        $parent->addChildTheme($this);
        return true;
    }

    /**
     * Возвращаем исходную тему
     * @return self|string
     */
    public function getParentTheme()
    {
        return $this->theme_parent;
    }

    /**
     * Указана ли исходная тема
     * @return boolean
     */
    public function hasParentTheme()
    {
        return ! empty($this->theme_parent) && is_object($this->theme_parent);
    }

    /**
     * Тема является наследником
     * @return boolean
     */
    public function isChildTheme()
    {
        return $this->hasParentTheme();
    }

    /**
     * Закрепляем связь с наследниками
     * @param Theme $child
     * @return boolean
     */
    public function addChildTheme(self $child)
    {
        if ( ! $this->theme_children->contains($child)) {
            $this->theme_children->attach($child, $child->getName());
            return true;
        }
        return false;
    }

    /**
     * Является ли данная тема исходной
     * @return boolean
     */
    public function isParentTheme()
    {
        return ($this->theme_children->count() > 0);
    }

    /**
     * Является ли данная тема стандартной
     * @return boolean
     */
    public function isBaseTheme()
    {
        return ($this->getName() === static::THEME_BASE);
    }

    /**
     * Получение настройки темы по ключу
     * Если настройка с таким ключем отсутствует обращаемся к методу исходной темы
     * {@inheritdoc}
     */
    public function config($key, $default = '', $language = null)
    {
        if ( ! $this->hasParentTheme()) {
            return $this->configBase($key, $default, $language);
        }
        $parent = $this->getParentTheme();
        if (is_string($key)) {
            if ($this->configExists($key)) {
                return $this->configBase($key, $default, $language);
            } else {
                return $parent->config($key, $default, $language);
            }
        } else if (is_array($key)) {
            $data = array();
            foreach ($key as $k=>$v) {
                if (is_string($k)) {
                    if ($this->configExists($k)) {
                        $data[$k] = $this->configBase($k, $v, $language);
                    } else {
                        $data[$k] = $parent->config($k, $v, $language);
                    }
                } else if (is_string($v)) {
                    if ($this->configExists($v)) {
                        $data[$v] = $this->configBase($v, $default, $language);
                    } else {
                        $data[$v] = $parent->config($v, $default, $language);
                    }
                }
            }
            return $data;
        }
        return $default;
    }

    /**
     * Запуск темы (если была включена)
     */
    protected function start()
    {
    }

    /**
     * Установка темы
     * Метод вызываемый при инсталяции темы администратором
     * @return bool
     */
    protected function install()
    {
        return true;
    }

    /**
     * Удаление темы
     * Метод вызываемый при удалении темы администратором
     * @return bool
     */
    protected function uninstall()
    {
        return true;
    }

    /**
     * Установлена ли тема
     * @return bool
     */
    public function isInstalled()
    {
        $list = \config::get('themes.installed.list', array(), TYPE_ARRAY);
        return (is_array($list) && isset($list[$this->getName()]));
    }

    /**
     * Активация темы
     * Метод вызываемый при активации темы администратором
     * @return bool
     */
    protected function activate()
    {
        return true;
    }

    /**
     * Деактивация темы
     * Метод вызываемый при дактивации темы администратором
     * @return bool
     */
    protected function deactivate()
    {
        return true;
    }

    /**
     * Обновление темы
     * Метод вызываемый при обновлении темы
     * @param array $context [
     *    'version_from' => 'версия до обновления (X.X.X)',
     *    'version_to' => 'версия обновления (X.X.X)',
     *    'date' => 'дата обновления (d.m.Y)'
     * ]
     * @return bool
     */
    protected function update($context)
    {
        return true;
    }

    /**
     * Активирована ли тема
     * Алиас для isActive
     * @param bool|null $testMode включена ли тема в режиме тестирования
     * @return bool
     */
    public function isEnabled($testMode = null)
    {
        if ( ! is_bool($testMode)) {
            $testMode = \bff::dev()->extensionsTestMode();
        }
        return $this->isActive($testMode);
    }

    /**
     * Активирована ли тема
     * @param bool|null $testMode включена ли тема в режиме тестирования
     * @return bool
     */
    public function isActive($testMode = false)
    {
        if ( ! is_bool($testMode)) {
            $testMode = \bff::dev()->extensionsTestMode();
        }
        if ($testMode) {
            return $this->isTestmode();
        }
        $theme = \bff::theme(true, $testMode);
        return (
            !empty($theme) && $this->getName() === $theme->getName()
            &&
            ($this->isInstalled() || $this->isBaseTheme())
        );
    }

    /**
     * Внутреннее название темы
     * @return string
     */
    public function getName()
    {
        return $this->theme_name;
    }

    /**
     * Видимое название темы
     * @return string
     */
    public function getTitle()
    {
        if ($this->theme_title === '?'
         || $this->theme_title === '{TITLE}') {
            return $this->getName();
        }
        return $this->theme_title;
    }

    /**
     * Версия темы
     * @return string
     */
    public function getVersion()
    {
        return $this->theme_version;
    }

    /**
     * Путь к файлу в теме
     * @param string $file относительный путь к файлу, начинается с "/"
     * @param boolean $asUrl вернуть url
     * @return string путь/url к теме (в случае если запрашиваемый файл в ней был найден)
     */
    public function fileThemed($file, $asUrl)
    {
        if ($asUrl) {
            # public path
            $path = $this->pathPublic($file, false);
            if ( ! file_exists($path)) {
                if ($this->isChildTheme()) {
                    return $this->getParentTheme()->fileThemed($file, $asUrl);
                } else {
                    foreach ($this->extension_addons as $addon) {
                        if ($addon->isTheme()) {
                            $addonUrl = $addon->fileThemed($file, $asUrl);
                            if ($addonUrl !== false) {
                                return $addonUrl;
                            }
                        }
                    }
                    return false;
                }
            }
            return SITEURL_STATIC.'/themes/'.$this->getName();
        } else {
            # path
            $path = $this->path($file, false);
            if ( ! file_exists($path)) {
                if ($this->isChildTheme()) {
                    return $this->getParentTheme()->fileThemed($file, $asUrl);
                } else {
                    foreach ($this->extension_addons as $addon) {
                        if ($addon->isTheme()) {
                            $addonPath = $addon->fileThemed($file, $asUrl);
                            if ($addonPath !== false) {
                                return $addonPath;
                            }
                        }
                    }
                    return false;
                }
            }
            return rtrim($this->extension_path, DS);
        }
    }

    /**
     * Расписание запуска крон задач темы:
     * [
     *   'название публичного метода темы' => ['period'=>'* * * * *'],
     *   ...
     * ]
     * @return array
     */
    public function cronSettings()
    {
        return array();
    }
}