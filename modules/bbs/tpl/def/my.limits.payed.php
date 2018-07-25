<?php
?>
<? if( ! empty($shopNavigation)):
$navs = array(
    1 => array('shop' => 1, 't' => _t('users', 'Магазин'), 'link' => Shops::url('my.limits.payed')),
    0 => array('shop' => 0, 't' => _t('users', 'Частные объявления'), 'link' => BBS::url('my.limits.payed')),
);
?>
<div class="u-cabinet__sub-navigation">
    <? if( DEVICE_DESKTOP_OR_TABLET ): ?>
    <div class="u-cabinet__main-navigation_desktop hidden-phone">
        <ul class="nav nav-pills">
            <? foreach($navs as $v): ?>
            <li class="u-cabinet__sub-navigation__sort<?= $shop == $v['shop'] ? '  active' : '' ?>"><a href="<?= $v['link'] ?>"><?= $v['t'] ?></a></li>
            <? endforeach; ?>
        </ul>
    </div>
    <? endif; # DEVICE_DESKTOP_OR_TABLET ?>
    <? if( DEVICE_PHONE ): ?>
    <div class="u-cabinet__main-navigation_mobile visible-phone">
        <div class="btn-group fullWidth">
            <button data-toggle="dropdown" class="btn dropdown-toggle" class="btn dropdown-toggle"> <?= $navs[$shop]['t'] ?> <i class="fa fa-caret-down"></i></button>
            <ul class="dropdown-menu">
                <? foreach($navs as $v): ?>
                    <li<? if( $shop == $v['shop'] ){ ?> class="active"<? } ?>>
                        <a href="<?= $v['link'] ?>"><?= $v['t'] ?></a>
                    </li>
                <? endforeach; ?>
            </ul>
        </div>
    </div>
    <? endif; # DEVICE_DESKTOP_OR_TABLET ?>
    <div class="clearfix"></div>
</div>
<? endif; ?>
<? if( ! empty($points)): ?>
<h3 class="hide-tail"><?= _t('bbs', 'Купленные платные пакеты'); ?></h3>
 <? foreach($points as $v):
    $cnt = $v['cnt'] - $v['free'];
    if($cnt < 0 ) $cnt = 0;
    $fst = true;
    foreach($v['limits'] as $vv):
        $rest = $cnt > $vv['items'] ? 0 : $vv['items'] - $cnt; $cnt -= $vv['items']; if($cnt < 0 ) $cnt = 0;
    ?>
    <div class="usr-package">
        <div class="usr-package__services">
            <strong><?= $v['parent'] ?></strong><br>
            <?= $v['title'] ?>
            <div class="usr-package__services__count">
                <? $am = $fst ? $vv['items'] + $v['free'] : $vv['items']; ?>
                <span class="label label-default"><?= $am ?></span> <?= tpl::declension($am, _t('bbs', 'объявление;объявления;объявлений'), false) ?> <?= $fst && $v['free'] ? _t('bbs', 'включая бесплатные') : '' ?>
            </div>
        </div>
        <div class="usr-package__stat">
            <strong><?= $rest ?></strong> <?= tpl::declension($rest, _t('bbs', 'объявление осталось;объявления осталось;объявлений осталось'), false) ?><br>
            <small><?= _t('bbs', 'Активно: [n] из [m]', array('n' => $vv['items'] - $rest, 'm' => $vv['items'])); ?></small>
            <a href="<?= BBS::url('limits.payed', array('point' => $v['cat_id'], 'shop' => $shop)) ?>" class="btn btn-info btn-block mrgt10"><?= _t('bbs', 'Расширить'); ?></a>
        </div>
        <? if($term): $days = strtotime($vv['expire']) - time(); $days = round($days / 86400); ?>
        <div class="usr-package__stat">
            <strong><?= $days ?></strong> <?= tpl::declension($days, _t('bbs', 'день остался;дня осталось;дней осталось'), false) ?><br>
            <small><?= _t('bbs', 'Активно до [date]', array('date' => tpl::date_format2($vv['expire']))); ?></small>
            <? if( ! empty($vv['allowExtend'])): ?>
                <a href="<?= BBS::url('limits.payed', array('id' => $vv['id'], 'extend' => 1)) ?>" class="btn btn-success btn-block mrgt10"><?= _t('bbs', 'Продлить'); ?></a>
            <? else: ?>
                <div data-placement="bottom" title="" data-toggle="tooltip" data-original-title="<?= _te('bbs', 'Вы не можете продлить пакет'); ?>">
                    <a class="btn btn-success btn-block mrgt10 disabled" href="#"><?= _t('bbs', 'Продлить'); ?></a>
                </div>
            <? endif; ?>
        </div>
        <? endif; ?>
    </div>
    <? $fst = false;
    endforeach;
endforeach; else: ?>
    <h3 class="hide-tail align-center"><?= _t('bbs', 'Нет активных платных пакетов'); ?></h3>
<? endif; ?>
<script type="text/javascript">
<? js::start(); ?>
$(function(){
    $('[data-toggle="tooltip"]').tooltip();
});
<? js::stop(); ?>
</script>
