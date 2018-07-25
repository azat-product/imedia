<?php

/**
 * Класс работы с конфигурацией
 * Настройки бывают следующих типов:
 *  1) системные - хранятся отдельно в файле (/config/sys.php), доступны для чтения через config::sys()
 *  2) статические - редактируются в админ-панели, "Настройки сайта/Общие настройки" (/config/site.php)
 *  3) динамические(счетчики) - не сохраняются в cache и загружаются каждый раз из БД (для счетчиков модулей)
 *  4) инструкции - инструкции для сайта, доп. блоки с описанием (/config/intructions.php), доступны через  config::instruction()
 * @version 0.972
 * @modified 23.may.2015
 */

class config
{
    public static $data = array();

    /**
     * Загружаем настройки сайта
     * Загрузка настроек производится в следующем порядке: cache > database > file
     * @return array массив с настроками ключ=>значение
     */
    public static function load()
    {
    }

    /**
     * Сохраняем настройку сайта
     * @param string|array $key ключ настройки
     * @param mixed $value значение
     * @param bool $dynamic динамическая настройка
     * @return void
     */
    public static function save($key, $value = false, $dynamic = false)
    {
    }

    /**
     * Сохранение некольких настроек сайта - (файл + база)
     * @param array $config настройки array(key=>value,...)
     * @param bool $includeDynamic включая динамические настройки
     * @return void
     */
    public static function saveMany($config, $includeDynamic = false)
    {
    }

    /**
     * Обновляем счетчик, в настройках сайта (сохраняем)
     * @param string $key ключ настройки
     * @param int $increment инкремент/декремент значение счетчика
     * @param bool $dynamic динамическая настройка (если создаем счетчик)
     * @return void
     */
    public static function saveCount($key, $increment, $dynamic = false)
    {
    }

    /**
     * Сбрасываем все динамические настройки сайта в 0
     * @return void
     */
    public static function resetCounters()
    {
    }

    /**
     * Сброс кеша всех настроек сайта
     * @return void
     */
    public static function resetCache()
    {
    }

    /**
     * Сохраняем настройку сайта в текущий загруженный конфиг
     * @param string|array $key ключ настройки: array массив ключ=>значение, string - ключ
     * @param mixed $value значение
     * @param string|bool $keyPrefix префикс ключа
     * @return void
     */
    public static function set($key, $value = false, $keyPrefix = false)
    {
    }

    /**
     * Получаем настройку сайта из текущего загруженного конфига
     * @param string|array $key ключ настройки: array массив ключ=>значение, string - ключ
     * @param mixed $defaultValue значение по-умолчанию
     * @param string|bool $keyPrefix префикс ключа
     * @return mixed
     */
    public static function get($key, $defaultValue = false, $keyPrefix = false)
    {
    }

    /**
     * Получаем настройки сайта из текущего загруженного конфига по префиксу ключа
     * @param string $keyPrefix префикс ключа настройки
     * @return array
     */
    public static function getWithPrefix($keyPrefix)
    {
    }

    /**
     * Получаем системные настройки
     * @param string|array $key ключ настройки, ключи строек
     * @param string|mixed $default значение по-умолчанию
     * @param string $keyPrefix префикс ключа настройки
     * @param boolean $keyPrefixCut true - отрезать префикс ключа, false - оставить
     * @examples:
     *   config::sys('db.host') => 'data', - настройка по ключу
     *   config::sys('host', '', 'db') => 'data', - настройка по ключу, с префиксом
     *   config::sys([]) => [...] - все системные настройки
     *   config::sys([], [], 'db') => ['db.host','db.name',...] - все настройки с префиксом 'db'
     *   config::sys(['db.host','db.name']) => ['db.host','db.name'] - перечисленные настройки
     *   config::sys(['host','name'], [], 'db') => ['db.host','db.name'] - перечисленные настройки с префиксом
     * @return mixed
     */
    public static function sys($key, $default = '', $keyPrefix = '', $keyPrefixCut = false)
    {
    }

    /**
     * Получение инструкций по ключу
     * @param mixed $key:
     *  1) string 'key' - ключ требуемой инструкции
     *  2) array array('key1','key2',..) - несколько ключей требуемых инструкций
     *  3) true|false|null|array() - возвращает все инструкции
     * @return string|array
     */
    public static function instruction($key = false)
    {
    }

    /**
     * Сохранение инструкций сайта в файл
     * @param array $data инструкции
     * @param bool $merge объединить с текущими инструкциями
     * @return bool
     */
    public static function instructionSave($data, $merge = true)
    {
    }

    /**
     * Подключаем требуемый config-файл
     * @param string $filename имя config-файла (без расширения): site, sys, ...
     * @param bool $returnPath возвращаем только путь к файлу (без подключения)
     * @return mixed|string
     */
    public static function file($filename, $returnPath = false)
    {
    }
}