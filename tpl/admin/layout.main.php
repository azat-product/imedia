<?php
/**
 * Layout: основной каркас панели управления
 * @var $err_errors array список уведомлений
 * @var $err_success boolean success-only уведомления
 * @var $err_autohide boolean скрыть уведомления по таймеру
 * @var $menu array меню
 * @var $menu_header_counters array меню счетчиков в шапке
 * @var $menu_header_user array меню пользователя
 * @var $page array данные о странице
 * @var $centerblock string содержимое (HTML)
 */
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?= Site::titleHeader('admin.title') ?> | <?= _t('', 'Панель управления') ?></title>
<?= View::template('css'); ?>
<?= View::template('js'); ?>
<? if($err_errors && $err_autohide) { ?>
<script type="text/javascript">
$(function(){ bff.error(false, {init: true}); /* init err block */ });
</script>
<? } ?>
<?php bff::hook('admin.head'); ?>
</head>
<body lang="<?= LNG ?>" data-db-queries="<?= $db_querycnt; ?>">
    <div class="warnblock warnblock-fixed" id="warning" style="<? if(empty($err_errors)) { ?>display:none;<? } ?>">
        <div class="warnblock-content alert alert-<?= ($err_success ? 'success' : 'danger') ?>">
            <a class="close j-close" href="#">&times;</a>
            <ul class="warns unstyled">
                <? foreach($err_errors as $v) { ?>
                <li><?= $v['msg'] ?><? if($v['errno'] == Errors::ACCESSDENIED) { ?> (<a href="#" onclick="history.back();"><?= _t('', 'назад') ?></a>)<? } ?></li>
                <? } ?>
            </ul>
        </div>
    </div>
    <div id="popupMsg" class="ipopup" style="display:none;">
        <div class="ipopup-wrapper">
            <div class="ipopup-title"></div>
            <div class="ipopup-content"></div>
            <div class="ipopup-footer-wrapper">
                <a href="javascript:void(null);" rel="close" class="ajax right"><?= _t('', 'Закрыть') ?></a>
                <div class="ipopup-footer"></div>
            </div>
        </div>
    </div>
    <div id="wrapper">
        <? if(bff::dev()->maintenanceEnabled()) { ?>
            <div class="well well-small well-warning" style="margin-top: 5px;">
                <?= _t('dev', 'Активирован <b>режим обслуживания</b>, на время действия данного режима сайт будет оставаться выключен для пользователей.<br />Изменить причину отключения сайта вы можете в разделе "[settings-section]", поле "[offline-reason-field]".', array(
                    'settings-section' => _t('menu','Настройки сайта').' / '._t('site', 'Общие настройки'),
                    'offline-reason-field' => _t('site','Причина выключения'),
                )); ?>
            </div>
        <? } ?>
        <div id="main-side">
            <div class="navbar admintopmenu">
                <div class="navbar-inner">
                    <div class="container-fluid">
                        <a href="<?= Site::urlBase() ?>" class="brand"><img src="<?= Site::logoURL('admin.header', Site::LOGO_SIZE_SMALL) ?>" alt="" /> <span class="hidden-phone"><?= Site::titleHeader('admin.header', true, '', _t('', 'Панель администратора')); ?></span></a>
                        <!-- start: Header Menu -->
                        <div class="btn-group pull-right">
                            <?php foreach($menu_header_counters as $v): ?>
                                <a href="<?= $v['url'] ?>" class="btn">
                                    <? if( ! empty($v['i']) ) { ?><i class="<?= $v['i'] ?>"></i><? } ?>
                                    <span class="hidden-phone hidden-tablet"> <?= $v['t'] ?></span><? if($v['cnt'] > 0) { ?> <span class="label <?= ($v['danger'] ? 'label-important' : 'label-success') ?>"><?= $v['cnt'] ?></span><? } ?>
                                </a>
                            <? endforeach; ?>
                            <!-- start: User Dropdown -->
                            <a href="javascript:void(0);" data-toggle="dropdown" class="btn<?= (FORDEV ? ' btn-info' : '') ?> dropdown-toggle">
                                <i class="<?= (!FORDEV ? 'icon-user' : 'icon-wrench') ?>"></i><span class="hidden-phone hidden-tablet"> <?= $user_login ?></span>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($menu_header_user as $v): ?>
                                    <li><a href="<?= $v['url'] ?>"><i class="<?= $v['icon'] ?>"></i> <?= $v['title'] ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                            <!-- end: User Dropdown -->
                        </div>
                        <!-- end: Header Menu -->
                    </div>
                </div>
            </div>
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="span3">
                        <div id="adminmenu" class="adminmenu">
                        <ul class="nav nav-list">
                            <? foreach($menu['tabs'] as $k=>$v): ?>
                                <li<? if($v['active']) { ?> class="active"<? } ?>>
                                     <a href="<?= $v['url'] ?>" class="<? if($v['active']) { ?>active <? } ?>main">
                                        <?= $v['title'] ?>
                                     </a>
                                     <ul class="nav nav-list sub<? if( ! $v['subtabs']) { ?> empty<? } ?>"<? if( ! $v['active']) { ?> style="display: none;"<? } ?>>
                                        <? $i=1; $j = sizeof($v['subtabs']);
                                           foreach($v['subtabs'] as $kk=>$vv):
                                             $last = ($i++==$j);
                                        ?>
                                            <li>
                                                <? if( ! $vv['separator'] ) { ?>
                                                    <a href="<?= $vv['url'] ?>" class="<? if($vv['active']) { ?>active <? } if($last) { ?> last<? } ?>"><?= $vv['title'] ?></a>
                                                <? } else { ?>
                                                    <hr size="1" />
                                                <? }
                                                if( ! empty($vv['rlink']) ) { ?><a href="<?= $vv['rlink']['url'] ?>" class="rlink hidden-phone hidden-tablet"><i class="icon-plus"></i></a><? } ?>
                                            </li>
                                        <? endforeach; ?>
                                     </ul>
                                </li>
                            <? endforeach; ?>
                            <li class="divider"></li>
                            <li><a class="main logout" href="<?= tplAdmin::adminLink('logout','users') ?>"><?= _t('', 'Выход') ?> &rarr; </a></li>
                        </ul>
                        </div>
                    </div>
                    <div class="span9">
                        <div id="content-side">
                        <? if ($page['custom']) {
                             echo $centerblock;
                           } else {
                             echo tplAdmin::blockStart($page['title'], $page['icon'], $page['attr'], $page['link'], $page['fordev']);
                             echo $centerblock;
                             echo tplAdmin::blockStop();
                           } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="push"></div>
    </div>
    <div id="footer">
        <hr />
        <footer>
            <p class="pull-right">Сделано в <a href="https://tamaranga.com" target="_blank">Tamaranga</a></p>
        </footer>
    </div>
</body>
</html>