<?php namespace bff\extend;

/**
 * Плагинизация: плагин
 * @abstract
 * @version 1.95
 * @modified 30.jul.2018
 * @copyright Tamaranga
 */

abstract class Plugin extends \Module implements ExtensionInterface
{
    use Extension {
        viewPHP as viewPHPBase;
    }

    /**
     * Внутреннее название плагина
     * @var string
     */
    protected $plugin_name = '';
    /**
     * Алиас внутреннего названия плагина
     * @var string
     */
    protected $plugin_alias = '';
    /**
     * Название плагина отображаемое в админ. панели
     * @var string
     */
    protected $plugin_title = '?';
    /**
     * Версия плагина
     * @var string
     */
    protected $plugin_version = '?';
    /**
     * Выполнялась ли установка плагина
     * @var bool
     */
    protected $plugin_installed = false;
    /**
     * Выполнялось ли включение плагина
     * @var bool
     */
    protected $plugin_enabled = false;
    /**
     * Директория шаблонов плагина
     * @var string
     */
    protected $plugin_templates_dir = 'tpl';

    /**
     * Инициализация
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->extension_type = EXTENSION_TYPE_PLUGIN;
        $this->module_name  = $name = $this->getName();
        $this->module_title = $this->getTitle();
        $this->module_dir   = $this->extension_path;

        if (\bff::adminPanel()) {
            foreach (array('install','uninstall','enable','disable') as $action) {
                \bff::hookAdd('plugins.' . $action . '.' . $name, function () use ($action) {
                    if ($action !== 'disable') {
                        $this->refreshStatic(in_array($action, ['install', 'enable']));
                    }
                    return $this->$action();
                }, 4);
            }
        }
        \bff::hookAdd('plugins.update.'.$name, function ($return, $context) {
            $this->refreshStatic(true);
            return $this->update($context);
        }, 4);
        \bff::hookAdd('plugins.start.'.$name, function($obj, $testMode) {
            static $started = false;
            if (!$started && $this->isActive($testMode)) {
                $started = true;
                if (BFF_DEBUG) {
                    $this->refreshStatic(true);
                }
                $this->extensionStart();
                $this->start();
            }
        }, 4);
    }

    /**
     * Запуск плагина (если был установлен и включен)
     */
    protected function start()
    {
    }

    /**
     * Установка плагина
     * Метод вызываемый при инсталяции плагина администратором
     * @return bool
     */
    protected function install()
    {
        return true;
    }

    /**
     * Удаление плагина
     * Метод вызываемый при удалении плагина администратором
     * @return bool
     */
    protected function uninstall()
    {
        return true;
    }

    /**
     * Установлен ли плагин
     * @return bool
     */
    public function isInstalled()
    {
        return $this->plugin_installed;
    }

    /**
     * Включение плагина
     * Метод вызываемый при включении плагина администратором
     * @return bool
     */
    protected function enable()
    {
        return true;
    }

    /**
     * Выключение плагина
     * Метод вызываемый при выключении плагина администратором
     * @return bool
     */
    protected function disable()
    {
        return true;
    }

    /**
     * Обновление плагина
     * Метод вызываемый при обновлении плагина
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
     * Включен ли плагин
     * @param bool|null $testMode проверяем включен ли плагин в режиме тестирования
     * @return bool
     */
    public function isEnabled($testMode = null)
    {
        if ( ! is_bool($testMode)) {
            $testMode = \bff::dev()->extensionsTestMode();
        }
        return ($testMode ? $this->isTestmode() : $this->plugin_enabled);
    }

    /**
     * Плагин был установлен и включен
     * @param bool $testMode проверяем включен ли плагин в режиме тестирования
     * @return bool
     */
    public function isActive($testMode = false)
    {
        return $this->isInstalled() && $this->isEnabled($testMode);
    }

    /**
     * Внутренее название плагина
     * @return string
     */
    public function getName()
    {
        return $this->plugin_name;
    }

    /**
     * Алиас внутреннего названия плагина
     * @return string
     */
    public function getAlias()
    {
        return $this->plugin_alias;
    }

    /**
     * Видимое название плагина
     * @return string
     */
    public function getTitle()
    {
        if ($this->plugin_title === '?'
         || $this->plugin_title === '{TITLE}') {
            return $this->getName();
        }
        return $this->plugin_title;
    }

    /**
     * Версия плагина
     * @return string
     */
    public function getVersion()
    {
        return $this->plugin_version;
    }

    /**
     * Расписание запуска крон задач плагина:
     * [
     *   'название публичного метода плагина' => ['period'=>'* * * * *'],
     *   ...
     * ]
     * @return array
     */
    public function cronSettings()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function viewPHP(array &$aData, $templateName, $templateDir = false, $display = false)
    {
        # Поиск шаблона для текущей активной темы:
        do {
            if ( ! empty($templateDir)) {
                break;
            }
            if (($theme = \bff::theme()) === false ||
                ($themeId = $theme->getExtensionId(false, true)) === '') {
                break;
            }
            $templateName = ltrim($templateName, DS);
            if (mb_stripos($templateName, $this->plugin_templates_dir . DS) !== 0) {
                break;
            }
            $templateNameThemed = $this->plugin_templates_dir . DS . $themeId . DS .
                mb_substr($templateName, mb_strlen($this->plugin_templates_dir . DS));
            if (is_file($this->extension_path . $templateNameThemed . '.php')) {
                # tpl/file.php => tpl/themeID/file.php
                # tpl/subdir/file.php => tpl/themeID/subdir/file.php
                $templateName = $templateNameThemed;
            }
        } while (false);

        return $this->viewPHPBase($aData, $templateName, $templateDir, $display);
    }
}