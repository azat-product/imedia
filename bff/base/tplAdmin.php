<?php namespace bff\base;

use bff\tpl\admin\BlockList;

/**
 * Вспомогательные методы формирования шаблонов в админ. панели
 * Helper methods for admin templates
 * @abstract
 * @version 0.31
 * @modified 30.mar.2017
 * @copyright Tamaranga
 */

abstract class tplAdmin extends tpl
{
    /**
     * Блок: список
     * @param \Module $module объект контроллера
     * @param string $initTemplate название php шаблона контроллера в котором выполняется инициализация списка
     * @param array $opts доп. параметры: [
     *      string|bool 'action' название метода контроллера или false функция в которой данный метод был вызван
     *      string 'version' версия шаблонов для рендеринга блока, по умолчанию '2'
     * ]
     * @return BlockList
     */
    public static function blockList(\Module $controller, $initTemplate = null, array $opts = array())
    {
        $opts = array_merge(array(
            'action' => false,
            'version' => '2',
        ), $opts);

        if (empty($opts['action'])) {
            $opts['action'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        }

        $list = new BlockList($controller, $opts['action']);
        $list->setTemplateDir(PATH_CORE.'tpl'.DS.'admin'.DS.'v'.strval($opts['version']));
        $list->init();

        if ( ! empty($initTemplate)) {
            $data = ['list'=>$list];
            $controller->viewPHP($data, $initTemplate);
        }

        return $list;
    }

    /**
     * Блок: список
     * @param string $module модуль
     * @param string $method метод
     * @param array $settings настройки
     * @return string HTML
     */
    public static function listing($module, $method, array $settings = array())
    {
        if (empty($module) || empty($method) || !isset($settings['list']['cols'])) {
            return '';
        }
        $settings['module'] = $module;
        $settings['method'] = $method;
        foreach (array('tabs', 'filter', 'list') as $k) {
            if (!isset($settings[$k]) || !is_array($settings[$k])) {
                $settings[$k] = false;
            } else if (empty($settings[$k]['opts'])) {
                $settings[$k]['opts'] = array();
            }
        }
        if ( ! empty($settings['tabs']['tabs'])) {
            foreach ($settings['tabs']['tabs'] as $k => $v) {
                if ( ! empty($v['rotate'])) {
                    $settings['rotate'] = true;
                    if (isset($settings['tabs']['opts']['active']) && $settings['tabs']['opts']['active'] == $k) {
                        $settings['list']['opts']['rotate'] = true;
                    }
                }
            }
        }
        return View::renderTemplate($settings, 'block.listing', PATH_CORE.'tpl'.DIRECTORY_SEPARATOR.'admin');
    }

    /**
     * Блок: список
     * @param array $data @ref информация о столбцах, данные, ...
     * @return string HTML
     */
    public static function listTable(array & $data)
    {
        return View::renderTemplate($data, 'block.list.table', PATH_CORE.'tpl'.DIRECTORY_SEPARATOR.'admin');
    }

    /**
     * Блок: список - кнопка действия
     * @param string $action
     * @param array $v @ref данные о записе
     * @param array $o доп. параметры
     * @return string
     */
    public static function listAction($action, array &$v, array $o = array())
    {
        static $lang;
        if ( ! isset($lang)) {
            $lang = array(
                'edit'    => _te('', 'Edit'),
                'enable'  => _te('', 'Enable'),
                'disable' => _te('', 'Disable'),
                'delete'  => _te('', 'Delete'),
                'info'    => _te('', 'Info'),
                'user'    => _te('', 'User'),
            );
        }
        $key = isset($o['key']) ? $o['key'] : 'id';
        $url = isset($o['url']) ? $o['url'] : tpl::adminLink(\bff::$event.'&act='.$action.'&id='.$v[$key]);
        $class = '';
        $link = '';
        if ( ! empty($o['modal'])) {
            $class = 'j-modal-link';
            $link .= 'data-link="'.(isset($o['url']) ? $o['url'] : $url).'"';
            $url = 'javascript:';
        }
        if ( ! empty($o['class'])) {
            $class = $o['class'];
        }
        if ( ! empty($o['onclick'])) {
            $link .= ' onclick="'.$o['onclick'].'" ';
        }
        $t = array(
            'key' => 'fav',
            'fa'    => array('on' => 'fa-heart',        'off' => 'fa-heart-o') ,
            'lang'  => array('on' => $lang['enable'],   'off' => $lang['disable']),
        );
        if ( ! empty($o['toggle'])) {
            $t = array_merge($t, $o['toggle']);
        }

        if ( ! empty($o['disabled'])) {
            $class = 'disabled';
            $url = 'javascript:';
        }

        switch ($action) {
            case 'info': return '<a href="'.$url.'" class="j-tooltip fa fa-eye '.($class ? $class : 'j-info').'" data-id="'.$v[$key].'" data-original-title="'.$lang['info'].'" title="'.$lang['info'].'" '.$link.'></a>';
            case 'edit': return '<a href="'.$url.'" class="j-tooltip fa fa-edit '.($class ? $class : 'j-form-url').'" data-id="'.$v[$key].'" data-original-title="'.$lang['edit'].'" title="'.$lang['edit'].'" '.$link.'></a>';
            case 'user': return '<a href="'.$url.'" class="j-tooltip fa fa-user j-user" data-id="'.$v[$key].'" data-original-title="'.$lang['user'].'" title="'.$lang['user'].'" '.$link.'></a>';
            case 'toggle-enabled': return '<a href="'.$url.'" class="j-tooltip fa fa-toggle-'.(empty($v['enabled']) ? 'off' : 'on').' j-toggle" data-type="enabled" data-id="'.$v[$key].'" title="'.(empty($v['enabled']) ? $lang['enable'] : $lang['disable']).'" data-title-on="'.$lang['disable'].'" data-title-off="'.$lang['enable'].'"'.$link.'></a>';
            case 'toggle': return '<a href="'.$url.'" class="j-tooltip fa '.(empty($v[ $t['key'] ]) ? $t['fa']['off'] : $t['fa']['on']).' j-toggle" data-type="enabled" data-id="'.$v[$key].'" title="'.(empty($v[ $t['key'] ]) ? $t['lang']['on'] : $t['lang']['off']).'" data-title-on="'.$t['lang']['on'].'" data-title-off="'.$t['lang']['off'].'"'.$link.'></a>';
            case 'delete': return '<a href="'.$url.'" class="j-tooltip tip-red fa fa-trash-o '.($class ? $class : 'j-delete').'" data-id="'.$v[$key].'" data-original-title="'.$lang['delete'].'" title="'.$lang['delete'].'"'.$link.'></a>';
        }
        return '';
    }

    /**
     * Блок: фильтр списка
     * @param string $module модуль
     * @param string $method метод
     * @param array $filter поля фильтра
     * @param array $data @ref данные фильтра
     * @param array $opts доп. параметры
     * @return string HTML
     */
    public static function listFilter($module, $method, array $filter, array &$data, array $opts = array())
    {
        $template = array(
            'module' => $module,
            'method' => $method,
            'filter' => $filter,
            'data'   => &$data,
            'opts'   => $opts,
        );
        return View::renderTemplate($template, 'block.list.filter', PATH_CORE.'tpl'.DIRECTORY_SEPARATOR.'admin');
    }

    /**
     * Блок: табы
     * @param array $tabs табы
     * @param array $opts доп. параметры
     * @return string HTML
     */
    public static function tabs(array $tabs, array $opts = array())
    {
        $data = array(
            'tabs' => $tabs,
            'opts' => $opts,
        );
        return View::renderTemplate($data, 'block.tabs', PATH_CORE.'tpl'.DIRECTORY_SEPARATOR.'admin');
    }

}