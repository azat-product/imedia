<?php namespace bff\extend;

/**
 * Плагинизация: плагин
 * @abstract
 * @version 1.91
 * @modified 23.mar.2018
 */

abstract class Plugin extends \Module
{
    use Extension;

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
     * @param bool $testMode проверяем включен ли плагин в режиме тестирования
     * @return bool
     */
    public function isEnabled($testMode = false)
    {
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
}