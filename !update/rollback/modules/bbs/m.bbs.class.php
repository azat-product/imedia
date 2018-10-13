<?php

class M_Bbs_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $sClass = 'bbs';

        # Объявления
        $sMenuTitle = _t('menu','Объявления');
        if ($security->haveAccessToModuleToMethod($sClass, 'items-listing')) {
            $menu->assign($sMenuTitle, _t('', 'Список'), $sClass, 'listing', true, 4,
                array('rlink' => array('event' => 'add'), 'counter' => 'bbs_items_moderating')
            );
        }

        # Печать объявлений
        if (bff::servicesEnabled() && BBS::PRESS_ON && $security->haveAccessToModuleToMethod($sClass, 'items-press')) {
            $menu->assign($sMenuTitle, _t('bbs', 'Печать в прессе'), $sClass, 'listing_press', true, 5,
                array('counter' => 'bbs_items_press')
            );
        }

        # Формы добавления / редактирования
        $menu->assign($sMenuTitle, _t('bbs', 'Добавить объявление'), $sClass, 'add', false, 11);
        $menu->assign($sMenuTitle, _t('bbs', 'Редактирование объявления'), $sClass, 'edit', false, 12);

        # Комментарии
        if (BBS::commentsEnabled() && $security->haveAccessToModuleToMethod($sClass, 'items-comments')) {
            $menu->adminHeaderCounter(_t('', 'комментарии'), 'bbs_comments_mod', $sClass, 'comments_mod', 2, 'icon-comment');
            $menu->assign($sMenuTitle, _t('', 'Комментарии'), $sClass, 'comments_mod', true, 14, array('counter' => 'bbs_comments_mod'));
        }
        # Жалобы
        if ($security->haveAccessToModuleToMethod($sClass, 'claims-listing')) {
            $menu->adminHeaderCounter(_t('bbs', 'жалобы'), 'bbs_items_claims', $sClass, 'claims', 1, 'icon-warning-sign', array('danger' => true));
            $menu->assign($sMenuTitle, _t('bbs', 'Жалобы'), $sClass, 'claims', true, 15, array('counter' => 'bbs_items_claims'));
        }

        # Категории
        if ($security->haveAccessToModuleToMethod($sClass, 'categories')) {
            $menu->assign($sMenuTitle, _t('bbs', 'Категории'), $sClass, 'categories_listing', true, 20,
                array('rlink' => array('event' => 'categories_add'))
            );
            $menu->assign($sMenuTitle, _t('bbs', 'Добавить категорию'), $sClass, 'categories_add', false, 21);
            $menu->assign($sMenuTitle, _t('bbs', 'Редактирование категории'), $sClass, 'categories_edit', false, 22);
            $menu->assign($sMenuTitle, _t('bbs', 'Пакетные настройки'), $sClass, 'categories_packetActions', false, 24);
            $menu->assign($sMenuTitle, _t('bbs', 'Типы категорий'), $sClass, 'types', false, 23);
        }

        # Дин. свойства
        $menu->assign($sMenuTitle, _t('bbs', 'Дин. св-ва категории'), $sClass, 'dynprops_listing', false, 30);
        $menu->assign($sMenuTitle, _t('bbs', 'Дин. св-ва категории'), $sClass, 'dynprops_action', false, 31);

        # Услуги / пакеты услуг
        if (bff::servicesEnabled() && $security->haveAccessToModuleToMethod($sClass, 'svc')) {
            $menu->assign($sMenuTitle, _t('bbs', 'Услуги'), $sClass, 'svc_services', true, 40);
            $menu->assign($sMenuTitle, _t('bbs', 'Пакеты услуг'), $sClass, 'svc_packs', true, 41,
                array('rlink' => array('event' => 'svc_packs_create&type=2'))
            );
            $menu->assign($sMenuTitle, _t('bbs', 'Создание пакета услуг'), $sClass, 'svc_packs_create', false, 42);
        }

        # Платное расширение лимитов
        if ($security->haveAccessToModuleToMethod($sClass, 'items-limits-payed')) {
            $menu->assign($sMenuTitle, _t('bbs', 'Лимиты'), $sClass, 'limitsPayed', true, 50);
        }

        # SEO
        if ($security->haveAccessToModuleToMethod($sClass, 'seo')) {
            $menu->assign(_t('', 'SEO'), $sMenuTitle, $sClass, 'seo_templates_edit', true, 10);
        }

        # Импорт / Экспорт
        if ($security->haveAccessToModuleToMethod($sClass, 'items-import') || $security->haveAccessToModuleToMethod($sClass, 'items-export')) {
            $menu->assign($sMenuTitle, _t('bbs', 'Импорт / Экспорт'), $sClass, 'import', true, 55);
        }

        # Настройки
        if ($security->haveAccessToModuleToMethod($sClass, 'settings')) {
            $menu->assign($sMenuTitle, _t('', 'Настройки'), $sClass, 'settings', true, 60);
        }
    }
}