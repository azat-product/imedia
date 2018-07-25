<?php namespace bff\base;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Вспомогательные методы работы с параметрами запроса
 * @abstract
 * @version 0.3
 * @modified 31.jan.2014
 */

abstract class Request
{
    /**
     * Построение объекта запроса на основе данных суперглобальных массивов
     * Если данные не указаны, будут использованы данные из соответствующих массивов
     * @param array $server $_SERVER
     * @param array $query $_GET
     * @param array $body $_POST
     * @param array $cookies $_COOKIE
     * @param array $files $_FILES
     * @return ServerRequestInterface
     */
    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) {
    }

    /**
     * Редирект
     * @param string $url URL
     * @param integer|bool $status статус редиректа или FALSE
     * @param bool $useJS выполнить редирект средствами JavaScript
     * @return void
     */
    public static function redirect($url, $status = false, $useJS = false)
    {
    }

    /**
     * Выполняется ли POST запрос
     * @return boolean
     */
    public static function isPOST()
    {
    }

    /**
     * Выполняется ли POST запрос
     * @return boolean
     */
    public static function isGET()
    {
    }

    /**
     * Выполняется ли HTTPS запрос
     * @return boolean
     */
    public static function isHTTPS()
    {
    }

    /**
     * Является ли текущий запрос обновлением страницы F5
     * @return boolean
     */
    public static function isRefresh()
    {
    }

    /**
     * Выполняется ли AJAX запрос
     * @param string|boolean $requestMethod тип запроса: 'GET','POST',NULL(не выполнять проверку типа)
     * @return boolean
     */
    public static function isAJAX($requestMethod = 'POST')
    {
    }

    /**
     * Получение IP адреса клиента
     * @param boolean $convertToInteger конвертировать в число
     * @param boolean $trustForwardedHeader
     * @return string|integer
     */
    public static function remoteAddress($convertToInteger = false, $trustForwardedHeader = false)
    {
    }

    /**
     * Значение HTTP_HOST
     * @param string $defaultValue значение по-умолчанию
     * @return string
     */
    public static function host($defaultValue = '')
    {
    }

    /**
     * Значение REQUEST_URI
     * @param string $defaultValue значение по-умолчанию
     * @return string
     */
    public static function uri($defaultValue = '')
    {
    }

    /**
     * URL Запроса
     * @param boolean $addURI false - добавлять значение REQUEST_URI
     * @return mixed
     */
    public static function url($addURI = false)
    {
    }

    /**
     * Значение HTTP_REFERER
     * @param string $defaultValue значение по-умолчанию
     * @return string
     */
    public static function referer($defaultValue = '')
    {
    }

    /**
     * Значение HTTP_USER_AGENT
     * @param string $defaultValue значение по-умолчанию
     * @return mixed
     */
    public static function userAgent($defaultValue = '')
    {
    }

    /**
     * Получаем HTTP протокол (http/https)
     * @return string
     */
    public static function scheme()
    {
    }

    /**
     * Получаем метод HTTP запроса
     * @return string
     */
    public static function method()
    {
    }

    /**
     * Значение из массива SERVER
     * @param string $key ключ
     * @param string $defaultValue значение по-умолчанию
     * @return string
     */
    public static function getSERVER($key, $defaultValue = '')
    {
    }

    /**
     * Установка COOKIE
     * @param string $key ключ
     * @param mixed $value значение
     * @param integer /null $expireDays кол-во дней жизни куков; NULL - удалить куки
     * @param string $path путь, по-умолчанию '/'
     * @param string|boolean $domain домен, false - .SITEHOST
     * @return boolean
     */
    public static function setCOOKIE($key, $value, $expireDays = 30, $path = '/', $domain = false)
    {
    }

    /**
     * Удаление COOKIE
     * @param string $key ключ
     * @param string $path путь, по-умолчанию '/'
     * @param string|boolean $domain домен, false - .SITEHOST
     * @return boolean
     */
    public static function deleteCOOKIE($key, $path = '/', $domain = false)
    {
    }

    /**
     * Корректировка URL текущего запроса с последующим редиректом
     * @param string $url корректный URL
     * @param array $options параметры
     */
    public static function urlCorrection($url, array $options = array())
    {
    }

	/**
	 * Проверка изменения страницы с ответом Last-Modified / Not Modified
	 * @param mixed $modified дата последнего изменения страницы
	 */
	public static function lastModified($modified)
	{
    }
}