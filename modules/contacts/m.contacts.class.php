<?php

class M_Contacts_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if ($security->haveAccessToModuleToMethod('contacts', 'view')) {
            $menu->adminHeaderCounter(_t('contacts','контакты'), 'contacts_new', 'contacts', 'listing', 5, 'icon-envelope');
            $menu->assign(_t('menu','Контакты'), _t('contacts','Список сообщений'), 'contacts', 'listing', true, 1);
        }
    }
}