<?php

/**
 * Плагинизация: хуки модуля Sitemap
 * @version 0.1
 * @modified 6.sep.2017
 */

class SitemapHooks
{
    /**
     * Фильтр расширения пунктов меню
     * @param string $position ключ позиции меню:
     *   1) 'main' - главное меню
     *   2) 'footer' - меню в футере
     * @param callable $callback {
     *   @param array $menu пункты меню:
     *   $menu['уникальный ключ'] = [
     *          'link' => 'http://example.com', # ссылка
     *          'title' => 'Название пункта меню',
     *          'target' => '_blank', # открывать ссылку в новой вкладке
     *          'a' => false, # активный ли пункт меню (true / false)
     *          'priority' => 1, # приоритет отображения в списке, или false - в конец списка
     *      ];
     * }
     * @param int|null $priority приоритет вызова
     * @return \Hook
     */
    public static function menu($position, callable $callback, $priority = NULL)
    {
        return bff::hooks()->add('sitemap.menu.'.$position, $callback, $priority);
    }
}