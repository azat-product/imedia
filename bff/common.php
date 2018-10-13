<?php

/**
 * Типы данных (для валидации компонентом bff\base\Input)
 */

define('TYPE_NOCLEAN',  0); # без изменений
define('TYPE_BOOL',     1); # boolean
define('TYPE_INT',      2); # integer
define('TYPE_UINT',     3); # unsigned integer
define('TYPE_NUM',      4); # number
define('TYPE_UNUM',     5); # unsigned number
define('TYPE_UNIXTIME', 6); # unix datestamp (unsigned integer)
define('TYPE_STR',      7); # trimmed string
define('TYPE_NOTRIM',   8); # string - no trim
define('TYPE_NOHTML',   9); # trimmed string with HTML made safe
define('TYPE_ARRAY',   10); # array
define('TYPE_BINARY',  12); # binary string
define('TYPE_NOHTMLCOND', 13); # trimmed string with HTML made safe if determined to be unsafe
define('TYPE_NOTAGS',  14); # trimmed string, stripped tags
define('TYPE_DATE',    15); # date
define('TYPE_PRICE',   16); # price
define('TYPE_TEXT',    17); # plain text
define('TYPE_JSON',    18); # json (config only)
define('TYPE_PASS',    19); # password = string no trim

define('TYPE_ARRAY_BOOL',     101);
define('TYPE_ARRAY_INT',      102);
define('TYPE_ARRAY_UINT',     103);
define('TYPE_ARRAY_NUM',      104);
define('TYPE_ARRAY_UNUM',     105);
define('TYPE_ARRAY_UNIXTIME', 106);
define('TYPE_ARRAY_STR',      107);
define('TYPE_ARRAY_NOTRIM',   108);
define('TYPE_ARRAY_NOHTML',   109);
define('TYPE_ARRAY_ARRAY',    110);
define('TYPE_ARRAY_BINARY',   112);
define('TYPE_ARRAY_NOHTMLCOND',113);
define('TYPE_ARRAY_NOTAGS',   114);
define('TYPE_ARRAY_DATE',     115);
define('TYPE_ARRAY_PRICE',    116);
define('TYPE_ARRAY_TEXT',     117);

define('TYPE_CONVERT_SINGLE', 100); # value to subtract from array types to convert to single types
define('TYPE_CONVERT_KEYS',   200); # value to subtract from array => keys types to convert to single types

# ----------------------------------------------------------
# Интерфейсы

/**
 * Интерфейс проверки кеш-зависимостей
 */
interface ICacheDependency
{
    public function evaluateDependency();

    public function getHasChanged();
}

/**
 * Интерфейс IModuleWithSvc
 * Модуль приложения предусматривающий активацию услуг через модуль Svc
 */
interface IModuleWithSvc
{
    /**
     * Активация услуги / пакета услуг
     * @param integer $nItemID ID записи
     * @param integer $nSvcID ID услуги / пакета услуг
     * @param mixed $aSvcData данные об услуге(*) или FALSE
     * @param array $aSvcSettings @ref дополнительные параметры услуги / нескольких услуг / пакета услуг
     * @return boolean|string
     *  true(string) - услуга успешно активирована (детали для описание счета активации услуги),
     *  false - ошибка активации услуги
     */
    public function svcActivate($nItemID, $nSvcID, $aSvcData = false, array &$aSvcSettings = array());

    /**
     * Формируем описание счета активации услуги (пакета услуг)
     * @param integer $nItemID ID записи
     * @param integer $nSvcID ID услуги / пакета услуг
     * @param array|boolean $aData false или array('item'=>array('id',...),'svc'=>array('id','type'))
     * @param array $aSvcSettings @ref дополнительные параметры услуги / нескольких услуг / пакета услуг
     * @return string
     */
    function svcBillDescription($nItemID, $nSvcID, $aData = false, array &$aSvcSettings = array());

    /**
     * Крон-метод, вызываемый из модуля Svc по-крону
     * @return mixed
     */
    function svcCron();
}

/**
 * Gettext обвертка
 * @param string $context контекст фразы
 * @param string $message фраза
 * @param array $params подстановочные данные
 * @param string $language язык локализации, null - текущая
 * @param array $opts дополнительные настройки
 * @example ('context', 'Дата: [date]', array('date'=>date('Y.m.d'))); => Дата 2001.01.01
 */
function _t($context, $message, array $params = array(), $language = null, array $opts = array())
{
    return \bff\DI::get('locale')->translate($context, $message, $params, $language, $opts);
}

function _te($context, $message, array $params = array(), $language = null, array $opts = array())
{
    return htmlspecialchars(_t($context, $message, $params, $language, $opts), ENT_QUOTES, 'UTF-8');
}

function _tejs($context, $message, array $params = array(), $language = null, array $opts = array())
{
    return strtr(_t($context, $message, $params, $language, $opts), array(
            '\\' => '\\\\',
            "'"  => "\\'",
            '"'  => '\\"',
            "\r" => '\\r',
            "\n" => '\\n',
            '</' => '<\/'
        )
    );
}