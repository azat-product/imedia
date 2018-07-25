<?php namespace bff\extend;

/**
 * Плагинизация: тема
 * @abstract
 * @version 1.21
 * @modified 23.mar.2018
 */

abstract class Theme extends \Module
{
    use Extension;

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
     * Инициализация
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->extension_type = EXTENSION_TYPE_THEME;
        $this->module_name  = $name = $this->getName();
        $this->module_title = $this->getTitle();
        $this->module_dir   = $this->extension_path;

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
        \bff::hookAdd('themes.start.'.$name, function($obj, $testMode) {
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
     * @param string $name название темы
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
     * @param bool $testMode включена ли тема в режиме тестирования
     * @return bool
     */
    public function isEnabled($testMode = false)
    {
        return $this->isActive($testMode);
    }

    /**
     * Активирована ли тема
     * @param bool $testMode включена ли тема в режиме тестирования
     * @return bool
     */
    public function isActive($testMode = false)
    {
        if ($testMode) {
            return $this->isTestmode();
        }
        $theme = \bff::theme(true, $testMode);
        return (!empty($theme) && $this->getName() === $theme->getName() && $this->isInstalled());
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