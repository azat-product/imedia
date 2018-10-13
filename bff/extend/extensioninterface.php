<?php namespace bff\extend;

/**
 * Плагинизация: интерфейс расширения
 * @version 0.1
 * @modified 2.aug.2018
 * @copyright Tamaranga
 */

interface ExtensionInterface
{
    const THEME_BASE = 'base';

    /**
     * Ключи стандартных файлов стилей
     */
    const CSS_FILE_MAIN   = 'main';
    const CSS_FILE_CUSTOM = 'custom';

    /**
     * Инициализация расширения
     * @return void
     */
    public function init();

    /**
     * Внутреннее название расширения
     * @return string
     */
    public function getName();

    /**
     * Видимое название расширения
     * @return string
     */
    public function getTitle();

    /**
     * Версия темы
     * @return string
     */
    public function getVersion();

    /**
     * Установлено ли расширение
     * @return bool
     */
    public function isInstalled();

    /**
     * Активировано ли расширение
     * Алиас для isActive
     * @param bool|null $testMode включено ли оно в режиме тестирования
     * @return bool
     */
    public function isEnabled($testMode = null);

    /**
     * Активировано ли расширение
     * @param bool $testMode включено ли оно в режиме тестирования
     * @return bool
     */
    public function isActive($testMode = false);
}