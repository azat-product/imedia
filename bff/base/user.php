<?php namespace bff\base;

/**
 * Класс работы с данными авторизованного пользователя
 * @abstract
 * @version 0.3
 * @modified 3.jun.2018
 * @copyright Tamaranga
 */
abstract class User
{
    /**
     * Получаем ID текущего авторизованного пользователя
     * @return integer
     */
    public static function id()
    {
        return \bff::security()->getUserID();
    }

    /**
     * Получаем текущий баланс на счету пользователя (результат кешируется)
     * @param boolean $resetCache сбросить кеш (получить актуальное значение из БД)
     * @return float
     */
    public static function balance($resetCache = false)
    {
        return \Users::model()->userBalance(false, $resetCache);
    }

    /**
     * Получаем значения счетчика(ов) пользователя (TABLE_USERS_STAT, столбцы с префиксом "cnt_")
     * @param mixed $key :
     *  string  - ключ счетчика, без префикса "cnt_"
     *  NULL    - все значения счетчиков из БД
     *  array() - все имеющиеся значения счетчиков
     * @return mixed
     */
    public static function counter($key = '')
    {
        return \bff::security()->userCounter($key);
    }

    /**
     * Изменяем значение счетчика пользователя (TABLE_USERS_STAT, столбцы с префиксом "cnt_")
     * @param string $key ключ счетчика (без префикса "cnt_")
     * @param integer $value значение
     * @param boolean $incrementDecrement :
     *  false - изменяем на новое значение {$value}
     *  true - +/- от текущего значения счетчика
     * @return mixed
     */
    public static function counterSave($key, $value, $incrementDecrement = false)
    {
        return \bff::security()->userCounter($key, $value, false, $incrementDecrement);
    }

    /**
     * Получаем данные о пользователе
     * @param array|string $keys ключи требуемых данных
     * @param boolean $forceDatabase доставать принудительно из базы
     * @return array|mixed
     */
    public static function data($keys = array(), $forceDatabase = false)
    {
        if ($oneValue = !is_array($keys)) {
            $keys = array($keys);
        }
        # из сессии
        if (!$forceDatabase) {
            $data = \bff::security()->getUserInfo($keys);
        }
        # из базы
        if ($forceDatabase || empty($data) || sizeof($keys) != sizeof($data)) {
            $data = \Users::model()->userData(static::id(), $keys);
            if (empty($data)) {
                $data = array();
            }
        }
        if ($oneValue) {
            if (sizeof($data) == 1) {
                return reset($data);
            } else {
                return '';
            }
        }

        return $data;
    }

    /**
     * Проверка текущего пользователя по ID
     * @param integer $userID ID пользователя
     * @return boolean
     */
    public static function isCurrent($userID)
    {
        return ($userID > 0 && (static::id() == $userID));
    }

    /**
     * Совпадает ли указанный пароль {$password} с текущим
     * @param string $password пароль в открытом виде
     * @return boolean
     */
    public static function isCurrentPassword($password)
    {
        if (!static::id()) {
            return false;
        }
        $data = static::data(array('password', 'password_salt'));
        if (empty($password) || empty($data) ||
            ($data['password'] != \bff::security()->getUserPasswordMD5($password, $data['password_salt']))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Проверка на вхождение в группу
     * @param integer|array $groupID ключ группы/нескольких групп
     * @param array $opts доп. параметры:
     *   boolean 'at-least-one' - состоит как минимум в одной из указанных групп
     * @return bool входит в указанные группы
     */
    public static function isInGroup($groupID, array $opts = array())
    {
        $userGroups = \bff::security()->getUserGroups();
        if (empty($userGroups)) {
            return false;
        }
        if ( ! is_array($groupID)) {
            $groupID = array($groupID);
        }
        $commonGroups = array_intersect($userGroups, $groupID);
        if ( ! empty($opts['at-least-one'])) {
            return sizeof($commonGroups) > 0;
        } else {
            return (sizeof($commonGroups) === sizeof($groupID));
        }
    }

}