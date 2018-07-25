<?php

class M_Users_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $module = _t('menu', 'Пользователи');
        if ($security->haveAccessToModuleToMethod('users', 'members-listing')) {
            $menu->assign($module, _t('users', 'Список пользователей'), 'users', 'listing', true, 1,
                array('rlink' => array('event' => 'user_add'))
            );
            $menu->assign($module, _t('users', 'Настройки профиля'), 'users', 'profile', false, 10);
        }
        if ($security->haveAccessToModuleToMethod('users', 'admins-listing')) {
            $menu->assign($module, _t('users', 'Список модераторов'), 'users', 'listing_moderators', true, 2,
                array('rlink' => array('event' => 'user_add'))
            );
        }
        if ($security->haveAccessToModuleToMethod('users', 'users-edit')) {
            $menu->assign($module, _t('users', 'Добавить пользователя'), 'users', 'user_add', false, 3);
            $menu->assign($module, _t('users', 'Редактирование пользователя'), 'users', 'user_edit', false, 4);
            $menu->assign($module, _t('users', 'Удаление пользователя'), 'users', 'user_delete', false, 5);
            $menu->assign($module, _t('users', 'Блокировка пользователей'), 'users', 'ban', true, 6);
        }

        # SEO
        if ($security->haveAccessToModuleToMethod('users', 'seo')) {
            $menu->assign(_t('', 'SEO'), $module, 'users', 'seo_templates_edit', true, 25);
        }
    }
}