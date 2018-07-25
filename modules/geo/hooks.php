<?php

/**
 * Плагинизация: хуки модуля Geo
 * @version 0.1
 * @modified 11.jul.2017
 */

class GeoHooks
{
    /**
     * Фильтр списка доступных провайдеров определения региона по IP
     * Используется в разделе "Настройки сайта / Системные настройки / Гео"
     * @see \Hooks::geoIpLocationRegion
     * @param callable $callback {
     *   @param array $list список доступных провайдеров в формате:
     *      array(
     *          'уникальный ключ латиницей' => array(
     *              'title' => 'Название',
     *              'description' => 'Описание',
     *          ),
     *          ...
     *      )
     *   return: array список доступных провайдеров
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function ipLocationProvidersList(callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('geo.ip.location.providers.list', $callback, $priority);
    }
}