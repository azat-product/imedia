<?php
/**
 * @var $this Shops
 */
?>
<div id="j-s-info-popup" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title"><?= _t('shops', 'Информация о магазине №'); ?><?= $id ?></div>
        <div class="ipopup-content" style="width:500px;">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1 field-title right" width="134"><?= _t('shops', 'Владелец:'); ?></td>
                    <td class="row2">
                        <?= ($user_id ? '<a href="#"  class="ajax'.($user['blocked'] ? ' text-error':'').'" onclick="return bff.userinfo('.$user_id.');">'.$user['email'].'</a>' : '<i>'._t('shops', 'не указан').'</i>' ); ?>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title right" width="134"><?= _t('shops', 'Название:'); ?></td>
                    <td class="row2">
                        <a class="linkout but" href="<?= $link ?>" target="_blank"></a><?= $title ?>
                    </td>
                </tr>
                <? if(Shops::abonementEnabled() && $svc_abonement_id): ?>
                    <tr>
                        <td class="row1 field-title right"><?= _t('shops', 'Абонемент:'); ?></td>
                        <td class="row2">
                            <b>"<?= $abonement['title'] ?>"</b> <?= $svc_abonement_termless ? _t('shops', 'бессрочно') : _t('', 'до [date]', array('date' => tpl::date_format2($svc_abonement_expire))) ?>
                            <span class="desc">(<?= $publicated ?> <?= _t('', 'из'); ?> <?= $abonement['items'] ? $abonement['items'] : '&infin;' ?>)</span>
                        </td>
                    </tr>
                <? endif; ?>
                <? bff::hook('shops.admin.shop.info', array('data'=>&$aData)) ?>
                <tr>
                    <td colspan="2">
                        <?
                            $aData['is_popup'] = true;
                            echo $this->viewPHP($aData, 'admin.shops.form.status');
                        ?>
                    </td>
                </tr>
            </table>
            <div class="ipopup-content-bottom">
                <ul class="right">
                    <li><span class="post-date" title="<?= _te('shops', 'дата создания магазина'); ?>"><?= tpl::date_format3($created); ?></span></li>
                    <? if($claims_cnt){ ?><li><a href="<?= $this->adminLink('edit&tab=claims&id='.$id); ?>" class="text-error"><?= _t('shops', 'жалобы'); ?> (<?= $claims_cnt ?>)</a></li><? } ?>
                    <li><a href="<?= $this->adminLink('listing&status=7&shopid='.$id, 'bbs'); ?>"> <?= _t('shops', 'объявления'); ?> (<?= $items ?>)</a></li>
                    <li><a href="<?= $this->adminLink('edit&id='.$id); ?>" class="edit_s"> <?= _t('', 'редактировать'); ?> <span style="display:inline;" class="desc">#<?= $id ?></span></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>