<?php

class M_Sitemap_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $menu->assign(_t('menu','Карта сайта и меню'), _t('sitemap','Управление меню'), 'sitemap', 'listing', true, 1,
            array('access'=>'listing')
        );
    }
}