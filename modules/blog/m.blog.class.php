<?php

class M_Blog_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $lang_menu = _t('menu','Блог');
        # Посты
        $menu->assign($lang_menu, _t('blog','Посты'), 'blog', 'posts', true, 1,
            array('rlink' => array('event' => 'posts&act=add'), 'access'=>'posts')
        );

        # Категории
        if (Blog::categoriesEnabled()) {
            $menu->assign($lang_menu, _t('blog','Категории'), 'blog', 'categories', true, 2,
                array('rlink' => array('event' => 'categories&act=add'), 'access'=>'categories')
            );
        }

        # Теги
        if (Blog::tagsEnabled()) {
            $menu->assign($lang_menu, _t('blog','Теги'), 'blog', 'tags', true, 3, array('access'=>'tags'));
        }

        # SEO
        $menu->assign(_t('','SEO'), $lang_menu, 'blog', 'seo_templates_edit', true, 30, array('access'=>'seo'));
    }
}