<?php
    /**
     * Необходимые данные Магазина:
     * id, status, status_prev, status_changed, moderated, blocked_reason
     */

    /**
     * Форма статуса магазина
     * @var $this Shops
     */

    $blocked = ($status == Shops::STATUS_BLOCKED);
    $is_popup = ! empty($is_popup);
?>

<script type="text/javascript">
var jShopStatus = (function(){
    var $block, $buttons, $progress, url = '<?= $this->adminLink('ajax&act=', 'shops'); ?>';
    var $blocked_reason;
    var data = {id: intval(<?= $id ?>), blocked: intval(<?= ($blocked ? 1 : 0) ?>)};
    $(function(){
        $block = $('#j-s-status-block');
        $progress = $block.find('.j-s-status-progress');
        $blocked_reason = $block.find('.j-s-blocked-reason');
        $buttons = $block.find('.j-s-status-buttons');
    });

    return {
        approve: function(){
            bff.ajax(url+'shop-status-approve', data, function(resp){
                if(resp && resp.success) {
                   location.reload();
                }
            }, $progress);
        },
        activate: function(){
            if( ! bff.confirm('sure')) return;
            bff.ajax(url+'shop-status-activate', data, function(resp){
                if(resp && resp.success) {
                    location.reload();
                }
            }, $progress);
        },
        deactivate: function(){
            if( ! bff.confirm('sure')) return;
            bff.ajax(url+'shop-status-deactivate', data, function(resp){
                if(resp && resp.success) {
                    location.reload();
                }
            }, $progress);
        },
        changeBlocked: function(step, block)
        {
            switch(step)
            {
                case 1: { // заблокировать / изменить блокировку
                    $block.find('.j-s-blocked').hide();
                    $block.find('.j-s-blocked-error, .j-s-blocking').show(0, function(){
                        $blocked_reason.focus();
                        $buttons.hide();
                    });
                } break;
                case 2: { // отменить
                    if(data.blocked == 1) {
                        $block.find('.j-s-blocked').show();
                        $block.find('.j-s-blocking').hide();
                    } else {
                        $block.find('.j-s-blocked-error').hide();
                        $buttons.show();
                    }
                } break;
                case 3: { // сохранить
                    data.blocked_reason = $blocked_reason.val();
                    bff.ajax(url+'shop-status-block', data, function(resp){
                        if(resp && resp.success)
                        {
                            data.blocked = resp.blocked;
                            if( ! block) {
                                $block.find('.j-s-blocked-error').hide();
                                data.blocked = 0;
                            } else {
                                data.blocked_reason = resp.reason;
                                $blocked_reason.val( resp.reason );
                                $block.find('.j-s-blocked-text').html( resp.reason );
                                $block.find('.j-s-blocked-error').show();
                                jShopStatus.changeBlocked(2);
                                $buttons.hide();
                            }
                            if(resp.hasOwnProperty('reload')) {
                                location.reload();
                            }
                        }
                    }, $progress);
                } break;
                case 4: { // разблокировать
                    if (!bff.confirm('sure')) break;
                    data.unblock = 1;
                    jShopStatus.changeBlocked(3);
                    data.unblock = 0;
                } break;
            }
            return false;
        }
    };
}());
</script>

<div id="j-s-status-block"<? if(!$is_popup) { ?>  class="well well-small" style="margin: 10px 0 0 0;"<? } ?>>
    <table class="admtbl tbledit">
        <tr>
            <td class="row1 field-title<? if($is_popup) { ?> right<? } ?>" style="width:<?= ( $is_popup ? 133 : 100 ) ?>px;"><?= _t('shops', 'Статус магазина:'); ?></td>
            <td class="row2"><strong><?
                switch($status) {
                    case Shops::STATUS_ACTIVE: { echo _t('shops', 'Активен'); } break;
                    case Shops::STATUS_NOT_ACTIVE: { echo _t('shops', 'Неактивен'); } break;
                    case Shops::STATUS_BLOCKED: { echo ($moderated == 0?_t('shops', 'Ожидает проверки (был заблокирован)'):_t('shops', 'Заблокирован')); } break;
                    case Shops::STATUS_REQUEST: { echo _t('shops', 'Заявка на открытие'); } break;
                }
            ?></strong><? if($status_changed != '0000-00-00 00:00:00'){ ?>&nbsp;&nbsp;<span class="desc">(<?= tpl::date_format2($status_changed, true, true); ?>)</span><? } ?></td>
        </tr>
        <tr>
            <td class="row1" colspan="2">
                <div class="alert alert-danger j-s-blocked-error <?= (!$blocked ? 'hidden':'') ?>">
                    <div><?= _t('shops', 'Причина блокировки:'); ?></div>
                    <div class="clear"></div>
                    <div class="j-s-blocked">
                        <span class="j-s-blocked-text"><?= (!empty($blocked_reason) ? $blocked_reason :'?') ?></span> - <a href="javascript:void(0);" onclick="jShopStatus.changeBlocked(1,0); return false;" class="ajax desc"><?= _t('', 'изменить'); ?></a>
                    </div>
                    <div class="j-s-blocking" style="display: none;">
                        <textarea name="blocked_reason" class="autogrow j-s-blocked-reason" style="height:60px; min-height:60px;"><?= $blocked_reason; ?></textarea>
                        <a onclick="return jShopStatus.changeBlocked(3, 1);" class="btn btn-mini btn-success" href="javascript:void(0);"><?= (!$blocked ? _t('shops', 'продолжить'):_t('shops', 'изменить причину')) ?></a>
                        <? if($blocked) { ?><a onclick="return jShopStatus.changeBlocked(4);" class="btn btn-success btn-mini" href="javascript:void(0);"><?= _t('shops', 'разблокировать'); ?></a><? } ?>
                        <a onclick="return jShopStatus.changeBlocked(2);" class="btn btn-mini" href="javascript:void(0);"><?= _t('', 'Cancel'); ?></a>
                    </div>
                </div>
            </td>
        </tr>
        <? if( ! ($moderated && $blocked) ) { ?>
        <tr class="j-s-status-buttons">
            <td class="row1" colspan="2" <? if( $is_popup ) { ?> style="padding-left: 20px;" <? } ?>>
               <?
               if ($moderated == 0) { ?>
                    <input class="btn btn-mini btn-success success button" type="button" onclick="jShopStatus.approve();" value="<?= ($blocked ? _t('shops', 'проверено, все впорядке') : ($status == Shops::STATUS_REQUEST ? _t('shops', 'подтвердить заявку') : _t('shops', 'проверено')) ) ?>" />
               <? } else {
                   if ( $moderated == 2 ) {
                        ?><input class="btn btn-mini btn-success success button" type="button" onclick="jShopStatus.approve();" value="<?= _te('shops', 'проверено'); ?>" /> <?
                   }
                   if ($status == Shops::STATUS_NOT_ACTIVE) {
                        ?><input class="btn btn-mini submit button" type="button" onclick="jShopStatus.activate();" value="<?= _te('shops', 'активировать'); ?>" /> <?
                   } else if ($status == Shops::STATUS_ACTIVE) {
                        ?><input class="btn btn-mini submit button" type="button" onclick="jShopStatus.deactivate();" value="<?= _te('shops', 'деактивировать'); ?>" /> <?
                   }
               }
               if ( ! $blocked) { ?>
                   <a class="btn btn-mini text-error" onclick="jShopStatus.changeBlocked(1); return false;"><?= _t('shops', 'заблокировать'); ?></a>
               <? } ?>
               <div class="progress j-s-status-progress" style="margin: 0 8px 3px; display: none;"></div>
            </td>
        </tr>
        <? } ?>
        <? bff::hook('shops.admin.shop.form.status', array('is_popup'=>$is_popup,'data'=>&$aData)) ?>
    </table>
</div>