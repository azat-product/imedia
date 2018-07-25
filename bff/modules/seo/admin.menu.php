<?php

# Посадочные страницы
if (SEO::landingPagesEnabled() && $security->haveAccessToModuleToMethod('seo','landingpages')) {
    $menu->assign('SEO', _t('seo','Посадочные страницы'), 'seo', 'landingpages', true, 100,
            array('rlink'=>array('event'=>'landingpages&act=add') ));

}

# Редиректы
if (SEO::redirectsEnabled() && $security->haveAccessToModuleToMethod('seo','redirects')) {
    $menu->assign('SEO', _t('seo','Редиректы'), 'seo', 'redirects', true, 105,
            array('rlink'=>array('event'=>'redirects&act=add') ));

}