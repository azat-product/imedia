<?php
if (empty($svc)) {
    ?><div style="padding:5px 0;" class="desc"><?= _t('shops', 'Нет активированных на текущий момент услуг'); ?></div><?
} else {
    $svc = intval($svc);
    if (Shops::abonementEnabled() && ($svc_abonement_id)) { ?>
        <div style="margin: 10px 0">
            <?= _t('shops', 'Активирован тариф [title]', array('title' => '<b>"'.$abonement['title'].'"</b>')) ?>&nbsp;<?= $svc_abonement_termless ? _t('shops', 'бессрочно') : _t('', 'до [date]', array('date' => tpl::date_format2($svc_abonement_expire))) ?>
        <br /><?= _t('shops', 'Включает:'); ?>
            <ul>
                <li>- <?= _t('shops', 'лимиты: [publicated] из [limit]', array('publicated'=>'<span title="publicated">'.$publicated.'</span>','limit'=>($abonement['items'] ? $abonement['items'] : '&infin;'))); ?></li>
                <? if($abonement['import']){ ?><li>- <?= _t('shops', 'импорт объявлений'); ?></li><? } ?>
                <? if($abonement['svc_fix']){ ?><li>- <?= _t('shops', 'услугу "Закрепление"'); ?></li> <? } ?>
                <? if($abonement['svc_mark']){ ?><li>- <?= _t('shops', 'услугу "Выделение"'); ?></li> <? } ?>
            </ul>
        </div>
    <? }
    if ($svc & Shops::SERVICE_MARK && $svc_marked_to != Shops::SVC_TERMLESS_DATE) { ?>
        <div style="margin: 10px 0"><?= _t('shops', 'Выделено до [date]', array('date' => '<b>'.tpl::date_format3($svc_marked_to, 'd.m.Y H:i').'</b>')); ?></div>
    <? }
    if ($svc & Shops::SERVICE_FIX && $svc_fixed_to != Shops::SVC_TERMLESS_DATE) { ?>
        <div style="margin: 10px 0"><?= _t('shops', 'Закреплено до [date]', array('date' => '<b>'.tpl::date_format3($svc_fixed_to, 'd.m.Y H:i').'</b>')); ?></div>
    <? }
 }
?>
<? bff::hook('shops.admin.shop.form.svc', array('data'=>&$aData)); ?>
<div style="margin: 10px 0"><a href="<?= $this->adminLink('listing&item='.$id, 'bills') ?>"><?= _t('shops', 'История активации услуг магазина #[id]', array('id'=>$id)); ?></a></div>
<?
