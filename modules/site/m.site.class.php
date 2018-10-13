<?php

class M_Site_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $menuTitle = _t('menu','Настройки сайта');
        # страницы
        if ($security->haveAccessToModuleToMethod('site-pages', 'listing')) {
            $module = _t('menu', 'Страницы');
            $menu->assign($module, _t('site', 'Список страниц'), 'site', 'pageslisting', true, 1,
                array('rlink' => array('event' => 'pagesAdd'))
            );
            $menu->assign($module, _t('site', 'Добавить cтраницу'), 'site', 'pagesAdd', false, 2);
            $menu->assign($module, _t('site', 'Редактирование cтраницы'), 'site', 'pagesEdit', false, 3);
        }

        # настройки сайта
        $menu->assign($menuTitle, _t('site', 'Общие настройки'), 'site', 'settings', true, 10, array(
            'access' => 'settings',
        ));

        # системные настройки
        if (config::sysAdminEnabled()) {
            $menu->assign($menuTitle, _t('site', 'Системные настройки'), 'site', 'settingsSystemManager', true, 11, array(
                'access' => 'settings-system',
            ));
        }

        # дополнения (плагины и темы)
        $menu->assign($menuTitle, _t('site','Дополнения'), 'site', 'extensionsManager', true, 12, array(
            'access' => 'extensions',
        ));

        # обновления
        $menu->assign($menuTitle, _t('dev', 'Обновления'), 'dev', 'updatesManager', true, 15, array(
            'access' => array('site','updates'),
        ));

        # seo
        $menu->assign(_t('', 'SEO'), _t('site', 'Настройки сайта'), 'site', 'seo_templates_edit', true, 50, array(
            'access' => 'seo',
        ));

        # инструкции
        //$menu->assign($menuTitle, 'Инструкции', 'site', 'instructions', true, 50);

        # счетчики
        $menu->assign($menuTitle, _t('site','Счетчики и код'), 'site', 'counters', true, 60,
            array('rlink' => array('event' => 'counters&act=add'), 'access' => 'counters')
        );

        # валюты
        $menu->assign($menuTitle, _t('site', 'Валюты'), 'site', 'currencies', true, 70, array(
            'access' => 'currencies',
        ));

        # локализация
        $menu->assign($menuTitle, _t('dev','Локализация'), 'dev', 'locale_data', true, 80, array(
            'access' => array('site','localization'),
        ));

    }
}