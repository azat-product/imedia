<?php namespace bff\extend\theme;

/**
 * Плагинизация: аддон тема
 * @version 0.2
 * @modified 6.aug.2018
 * @copyright Tamaranga
 */

class Addon extends Base
{
    protected $theme_addon_enabled = false;

    /**
     * Инициализация
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->siteFavicon([]);
        $this->siteLogo([]);
    }

    /**
     * Активирован ли аддон
     * @param bool|null $testMode включен ли аддон в режиме тестирования
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
        return $this->theme_addon_enabled;
    }
}