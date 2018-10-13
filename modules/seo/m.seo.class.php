<?php

class M_SEO_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $menu->assign(_t('', 'SEO'), _t('', 'Настройки'), 'seo', 'settings', true, 150, array(
            'access' => 'seo',
        ));
    }
}