<?php

/**
 * Класс работы с данными авторизованного пользователя
 * @abstract
 */
abstract class User_ extends \bff\base\User
{
    /**
     * Получаем ID магазина
     * @return integer
     */
    public static function shopID()
    {
        return \bff::security()->getShopID();
    }

    /**
     * Подтвержден ли номер телефона пользователя
     * @return bool true - подтвержден
     */
    public static function phoneNumberVerified()
    {
        return (static::id() && static::data('phone_number_verified'));
    }

    /**
     * Локаль пользователя
     */
    public static function lang()
    {
        return \bff::security()->getLang();
    }
}