<?php

class M_Sendmail_
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if (!$security->haveAccessToModuleToMethod('sendmail', 'templates-listing')) {
            return;
        }

        $module = _t('menu', 'Работа с почтой');
        $menu->assign($module, _t('sendmail', 'Список рассылок'), 'sendmail', 'massend_listing', true, 1, array(
                'rlink' => array('event' => 'massend_form')
            )
        );
        $menu->assign($module, _t('sendmail', 'Информация о рассылке'), 'sendmail', 'massend_receivers_listing', false, 2);
        $menu->assign($module, _t('sendmail', 'Начать рассылку'), 'sendmail', 'massend_form', false, 3);

        $menu->assign($module, _t('sendmail', 'Уведомления'), 'sendmail', 'template_listing', true, 10);
        $menu->assign($module, _t('sendmail', 'Уведомления / Редактирование'), 'sendmail', 'template_edit', false, 11);

        if ($security->haveAccessToModuleToMethod('sendmail','wrappers')) {
            $menu->assign($module, _t('', 'Шаблоны писем'), 'sendmail', 'wrappers', true, 20);
        }
    }
}