<?php namespace bff\base;

/**
 * Класс валидации входящих данных (GET, POST, COOKIE ...)
 * @version 1.64
 * @modified 25.feb.2014
 */

class Input_
{
    /**
     * Конструктор
     */
    public function __construct()
    {
    }
    
    /**
     * Приводим данные в массиве к безопасной форме
     * @core-doc
     * @param array $data @ref массив данных, которые необходимо обработать
     * @param array $variables массив имя переменной и тип, которые необходимо извлечь
     * @param array $return @ref результат обработки
     * @return array
     */
    public function clean_array(&$data, $variables, &$return = array())
    {
    }

    /**
     * Приводим данные GET к безопасной форме
     * @core-doc
     * @param array $variables массив: имя переменной и тип, которые необходимо извлечь
     * @param mixed $return @ref результат обработки
     * @param mixed $prefix префикс (подставляемый к ключу, если необходимо)
     * @return array
     */
    public function getm(array $variables, &$return = array(), $prefix = false)
    {
    }

    /**
     * Приводим данные GET к безопасной форме, основываясь на доступных языках
     * @core-doc
     * @param array $variables массив: имя переменной и тип, которые необходимо извлечь
     * @param mixed $return @ref результат обработки
     * @param mixed $prefix префикс (подставляемый к ключу, если необходимо)
     * @return array
     */
    public function getm_lang(array $variables, &$return = array(), $prefix = false)
    {
    }

    /**
     * Приводим данные POST к безопасной форме
     * @core-doc
     * @param array $variables массив: имя переменной и тип, которые необходимо извлечь
     * @param mixed $return @ref результат обработки
     * @param mixed $prefix префикс (подставляемый к ключу, если необходимо)
     * @return array
     */
    public function postm(array $variables, &$return = array(), $prefix = false)
    {
    }

    /**
     * Приводим данные POST к безопасной форме, основываясь на доступных языках
     * @core-doc
     * @param array $variables массив: имя переменной и тип, которые необходимо извлечь
     * @param mixed $return @ref результат обработки
     * @param mixed $prefix префикс (подставляемый к ключу, если необходимо)
     * @return array
     */
    public function postm_lang(array $variables, &$return = array(), $prefix = false)
    {
    }

    /**
     * Приводим данные POST/GET к безопасной форме
     * @param array $variables массив: имя переменной и тип, которые необходимо извлечь
     * @param mixed $return @ref результат обработки
     * @param mixed $prefix префикс (подставляемый к ключу, если необходимо)
     * @return array
     */
    public function postgetm(array $variables, &$return = array(), $prefix = false)
    {
    }

    /**
     * Приводим данные POST/GET к безопасной форме, основываясь на доступных языках
     * @core-doc
     * @param array $variables массив: имя переменной и тип, которые необходимо извлечь
     * @param mixed $return @ref результат обработки
     * @param mixed $prefix префикс (подставляемый к ключу, если необходимо)
     * @return array
     */
    public function postgetm_lang(array $variables, &$return = array(), $prefix = false)
    {
    }

    /**
     * Приводим одну переменную GET-POST-COOKIE к безопасной форме и возвращаем её
     * @core-doc
     * @param string $source сокращение для суперглобального массива: g, p, c, r или f (get, post, cookie, request или files)
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function clean_gpc($source, $varname, $vartype = TYPE_NOCLEAN, array $extra = array())
    {
    }

    /**
     * Приводим одну переменную GET к безопасной форме и возвращаем её
     * @core-doc
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function get($varname, $vartype = TYPE_NOCLEAN, array $extra = array())
    {
    }

    /**
     * Приводим одну переменную GET-POST к безопасной форме и возвращаем её
     * @core-doc
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function getpost($varname, $vartype = TYPE_NOCLEAN, array $extra = array())
    {
    }

    /**
     * Приводим одну переменную POST к безопасной форме и возвращаем её
     * @core-doc
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function post($varname, $vartype = TYPE_NOCLEAN, array $extra = array())
    {
    }

    /**
     * Приводим одну переменную POST-GET к безопасной форме и возвращаем её
     * @core-doc
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function postget($varname, $vartype = TYPE_NOCLEAN, array $extra = array())
    {
    }

    /**
     * Приводим одну переменную COOKIE к безопасной форме и возвращаем её
     * @core-doc
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function cookie($varname, $vartype = TYPE_STR, array $extra = array())
    {
    }

    /**
     * Приводим одну переменную SERVER к безопасной форме и возвращаем её
     * @core-doc
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function server($varname, $vartype = TYPE_STR, array $extra = array())
    {
    }

    /**
     * Приводим одну переменную ENV к безопасной форме и возвращаем её
     * @core-doc
     * @param string $varname имя переменной
     * @param integer $vartype тип переменной
     * @param array $extra дополнительные параметры валидации
     * @return mixed
     */
    public function env($varname, $vartype = TYPE_STR, array $extra = array())
    {
    }
    
    /**
     * Приводим одну переменную к безопасной форме и возвращаем её
     * @core-doc
     * @param mixed $var @ref значение
     * @param integer|mixed $vartype тип
     * @param boolean $exists требуется ли очистка переменной либо ее инициализация
     * @param array $extra дополнительные параметры валидации
     * @return mixed переменная в безопасной форме
     */
    public function clean(&$var, $vartype = TYPE_NOCLEAN, $exists = true, array $extra = array())
    {
    }
    
    /**
     * Проверяем email адрес на корректность
     * @core-doc
     * @param string $sEmail @ref email адрес
     * @param boolean $bFormat форматируем email
     * @return boolean
     */
    public function isEmail(&$sEmail, $bFormat = false)
    {
    }

    /**
     * Форматирование email адреса
     * @core-doc
     * @param string $sEmail email адрес
     * @return string
     */
    public function formatEmail($sEmail)
    {
    }

    /**
     * Проверка, является ли email адрес временным
     * @param string $email email-адрес
     * @return boolean true - временный, false - нормальный
     */
    public function isEmailTemporary($email)
    {
    }

    /**
     * Очистка текста (без HTML тегов)
     * @core-doc
     * @param string $sText
     * @param boolean $nMaxLength
     * @param boolean $bActivateLinks
     * @return string
     */
    public function cleanTextPlain($sText, $nMaxLength = false, $bActivateLinks = true)
    {
    }

    /**
     * Подготовка строки для поиска (UTF-8, сжимание пробелов)
     * @core-doc
     * @param string $sString строка поиска
     * @param integer $nMaxLength максимально допустимая длина строки
     * @return string
     */
    public function cleanSearchString($sString, $nMaxLength = 64)
    {
    }

}