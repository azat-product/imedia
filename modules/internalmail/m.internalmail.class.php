<?php

class M_InternalMail_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $module = _t('menu', 'Сообщения');
        if ($security->haveAccessToModuleToMethod('internalmail', 'read')) {
            $menu->adminHeaderCounter(_t('internalmail', 'сообщения'), 'internalmail_new', 'internalmail', 'listing', 4, 'icon-envelope',
                array('userCounter' => true)
            );

            $menu->assign($module, _t('internalmail', 'Личные сообщения'), 'internalmail', 'listing', true, 1,
                array('counter' => 'internalmail_new', 'userCounter' => true)
            );
            $menu->assign($module, _t('internalmail', 'Личные переписка'), 'internalmail', 'conv', false, 2);
        }

        if ($security->haveAccessToModuleToMethod('internalmail', 'spy')) {
            $menu->assign($module, _t('internalmail', 'Лента сообщений'), 'internalmail', 'spy_lenta', true, 10);

            $menu->assign($module, _t('internalmail', 'Cообщения пользователя'), 'internalmail', 'spy_listing', true, 20);
            $menu->assign($module, _t('internalmail', 'Переписка пользователя'), 'internalmail', 'spy_conv', false, 21);
        }
    }
}