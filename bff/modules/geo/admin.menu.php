<?php

# регионы
if ($security->haveAccessToModuleToMethod('site', 'regions')) {
    if (Geo::$useRegions !== false) {
        $menu->assign(_t('site','Настройки сайта'), _t('geo','Регионы'), 'geo', 'regions', true, 26);
    }
}