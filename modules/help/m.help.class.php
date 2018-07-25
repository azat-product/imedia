<?php

class M_Help_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $menuTitle = _t('menu','Помощь');

        # Вопросы
        $menu->assign($menuTitle, _t('help','Вопросы'), 'help', 'questions', true, 1,
            array('rlink' => array('event' => 'questions&act=add'), 'access' => 'questions')
        );
        # Категории
        $menu->assign($menuTitle, _t('help','Категории'), 'help', 'categories', true, 5,
            array('rlink' => array('event' => 'categories&act=add'), 'access' => 'categories')
        );
        # SEO
        $menu->assign(_t('','SEO'), $menuTitle, 'help', 'seo_templates_edit', true, 40,
            array('access' => 'seo')
        );
    }
}