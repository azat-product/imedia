<?php namespace bff\base;

/**
 * Класс работы с локализацией
 * @version 1.16
 * @modified 21.sep.2014
 *
 * Используемые файлы/директории:
 * 1. '/files/locale/(ru_RU)' - директории файлов локализаций
 * 2. '/files/locale/domain.php' - файл счетчика текущего текстового домена (gettext)
 * 3. '/config/languages.php' - настройки доступных языков приложения
 *
 */

class Locale_
{
    const DEF = 'def';
    const LNG_VAR = 'lng';

    /**
     * Инициализация
     * @param array $languages языки приложения, формат: ['ключ языка'=>'название языка', ...]
     * @param string $defaultLanguageKey ключ языка по-умолчанию
     * @return string ключ текущего (определенного) языка
     */
    public function init(array $languages = array(), $defaultLanguageKey = '')
    {
    }

    /**
     * Добавить язык сайта
     * @param string $languageKey ключ языка
     * @param string $title название языка
     * @param string $charset кодировка
     * @return $this
     */
    public function assignLanguage($languageKey, $title = 'Default', $charset = 'UTF-8')
    {
    }

    /**
     * Получаем ключ текущего языка
     * @return string
     */
    public function getCurrentLanguage()
    {
    }

    /**
     * Устанавливаем текущий язык
     * @param string $languageKey ключ языка
     * @return string
     */
    public function setCurrentLanguage($languageKey)
    {
    }

    /**
     * Получаем префикс языка, для подстановки в URL
     * @param string $languageKey ключ языка
     * @param boolean $trailingSlash добавлять завершающий слеш
     * @return string
     */
    public function getLanguageUrlPrefix($languageKey = LNG, $trailingSlash = false)
    {
    }

    /**
     * Устанавливаем необходимость использовать префикс языка по-умолчанию в URL
     * @param boolean $enabled использовать или нет
     * @return boolean
     */
    public function setDefaultLanguageUrlPrefix($enabled)
    {
    }

    /**
     * Получаем настройку о необходимости использовать префикс языка по-умолчанию в URL
     * @return boolean
     */
    public function getDefaultLanguageUrlPrefix()
    {
    }

    /**
     * Получаем ключ языка по-умолчанию
     * @return string
     */
    public function getDefaultLanguage()
    {
    }

    /**
     * Получение списка используемых языков
     * @param boolean $keywordsOnly true - только ключи, false - все доступные данные
     * @return array
     */
    public function getLanguages($keywordsOnly = true)
    {
    }

    /**
     * Получение настройки языка
     * @param string $languageKey ключ языка
     * @param string|mixed $key ключ требуемой настройки
     * @param mixed $default значение по-умолчанию
     * @return mixed
     */
    public function getLanguageSettings($languageKey, $key, $default = '')
    {
    }

    /**
     * Формируем форму редактирования мультиязычных данных
     * @param array $data @ref данные, подставляемые в шаблон формы ($template)
     * @param string $prefix дополнительный префикс формы
     * @param string $template шаблон формы
     * @param array $extra дополнительные параметры:
     *  string {onchange} - имя JavaScript-метода, вызываемое после переключения таба языка
     *  boolean {table} - формировать таблицу
     *  integer {cols} - кол-во столбцов в таблице
     * @return string HTML
     */
    public function buildForm(array &$data, $prefix, $template, array $extra = array())
    {
    }

    /**
     * Формирование HTML кода мультиязычного поля
     * @param string $sName name поля
     * @param array $aData данные
     * @param string $sType тип поля, доступны: 'text', 'textarea'
     * @param array $aAttr доп. атрибуты поля
     * @return string HTML
     */
    public function formField($sName, $aData, $sType, array $aAttr = array())
    {
    }

    /**
     * Формируем название месяца
     * @param integer|boolean $monthIndex порядковый номер месяца, начиная с 0, или false - если необходим полный список названий
     * @param string $languageKey ключ языка
     * @return string|array
     */
    public function getMonthTitle($monthIndex = false, $languageKey = LNG)
    {
    }

    /**
     * Gettext: путь к директории локализации
     * @param string $languageKey ключ языка, '' - возвращаем путь к директории содержащей все локализации
     * @return string путь к директории локализации
     */
    public function gt_Path($languageKey = '')
    {
    }

    /**
     * Gettext: Управление Gettext domain файлом
     * @param string|boolean $returnType :
     *   1) false - возвращаем ключ домена
     *   2) lastmodify - возвращаем дату последней модификации файла domain.php
     *   3) path - возвращаем путь к файлу domain.php
     *   4) next - возвращаем следующий ключ домена (текущий+1)
     * @return mixed
     */
    public function gt_Domain($returnType = false)
    {
    }

    /**
     * Gettext: Формируем название директории содержащей [po,mo] файлы переводов на указанный язык
     * @param string $languageKey ключ языка
     * @param boolean $addCharset добавлять к названию директории кодировку
     * @return string
     */
    public function gt_LocaleMessagesFolder($languageKey = '', $addCharset = false)
    {
    }
}