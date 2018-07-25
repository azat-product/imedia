<?php

class M_Bills_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $menu->assign(_t('menu','Счета'), _t('menu','Список счетов'), 'bills', 'listing', true, 1, array('access'=>'listing'));
    }
}