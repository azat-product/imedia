<?php

class Sitemap_ extends SitemapBase
{
    /**
     * Построение меню
     * @param string $key ключ меню, например: 'main', 'footer'
     */
    public static function view($key)
    {
        static $cache;

        if (!isset($cache)) {
            $self = static::i();
            $self->buildMenu(true, 'none');
            $cache = $self->menu;
            if (!bff::shopsEnabled() && isset($cache['main']['sub']['shops'])) {
                unset($cache['main']['sub']['shops']);
            }
            if (!bff::servicesEnabled() && isset($cache['main']['sub']['services'])) {
                unset($cache['main']['sub']['services']);
            }
        }

        $menu = (!empty($cache[$key]['sub']) ? $cache[$key]['sub'] : array());
        if (bff::hooksAdded('sitemap.menu.'.$key)) {
            $menu = bff::filter('sitemap.menu.'.$key, $menu);
            func::sortByPriority($menu);
        }
        return $menu;
    }

}