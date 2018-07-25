<?php

class M_Banners_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if (!$security->haveAccessToModuleToMethod('banners', 'listing')) {
            return;
        }
        
        $module = _t('menu', 'Баннеры');
        # баннеры
        $menu->assign($module, _t('', 'Список'), 'banners', 'listing', true, 1, array(
                'rlink' => array('event' => 'add')
            )
        );
        $menu->assign($module, _t('banners', 'Добавление баннера'), 'banners', 'add', false, 2);
        $menu->assign($module, _t('banners', 'Редактирование баннера'), 'banners', 'edit', false, 3);

        # статистика
        $menu->assign($module, _t('banners', 'Статистика по баннеру'), 'banners', 'statistic', false, 4);

        # позиции
        $menu->assign($module, _t('banners', 'Позиции'), 'banners', 'positions', true, 5, (FORDEV ? array(
                'rlink' => array('event' => 'positions&act=add')
            ) : array())
        );
        $menu->assign($module, _t('banners', 'Удаление позиции'), 'banners', 'position_delete', false, 6);
    }
}