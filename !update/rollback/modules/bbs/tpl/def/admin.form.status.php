<?php
    /**
     * Необходимые данные ОБ:
     * id, status, status_changed, moderated, publicated, publicated_to, publicated_order
     * svc, blocked_reason
     *
     * Необходимыые данные пользователя:
     * user['blocked']
     */

    /**
     * @var $this BBS
     */

    $blocked = ($status == BBS::STATUS_BLOCKED);
    $is_popup = ! empty($is_popup);
    $is_refresh = ! empty($is_refresh);
    $reasons = BBS::blockedReasons();
?>
<?php if (!$is_refresh) { ?>
<script type="text/javascript">
var jItemStatus = (function(){
    var $block, $buttons, $progress, url = '<?= $this->adminLink('ajax&act=', 'bbs'); ?>';
    var $blocked_reason, $blocked_id, is_popup = <?= ($is_popup ? 1 : 0) ?>;
    var data = {id: <?= $id ?>, blocked: <?= ($blocked ? 1 : 0) ?>, popup: is_popup};
    $(function(){
        $block = $('#j-i-status-block');
        $progress = $block.find('.j-i-status-progress');
        $blocked_reason = $block.find('.j-i-blocked-reason');
        $blocked_id = $block.find('.j-i-blocked-id');
        $buttons = $block.find('.j-i-status-buttons');
    });

    function refreshBlock(resp)
    {
        if (resp.hasOwnProperty('html')) {
            $block.html(resp.html);
            if (is_popup) {
                jItems.refresh();
            }
        }
    }

    return {
        activate: function(){
            bff.ajax(url+'item-activate', data, function(resp){
                if (resp && resp.success) {
                    refreshBlock(resp);
                }
            });
        },
        approve: function(){
            bff.ajax(url+'item-approve', data, function(resp){
                if (resp && resp.success) {
                   refreshBlock(resp);
                }
            }, $progress);
        },
        unpublicate: function(){
            if( ! bff.confirm('sure')) return;
            bff.ajax(url+'item-unpublicate', data, function(resp){
                if (resp && resp.success) {
                    refreshBlock(resp);
                }
            }, $progress);
        },
        refresh: function(step){
            var $blockRefresh = $block.find('#i_refresh');
            switch(step)
            {
                case 0: { // показываем форму продления
                    $blockRefresh.show();
                    $buttons.hide();
                } break;
                case 1: { // сохранить
                    bff.ajax(url+'item-refresh', data, function(resp){
                        if (resp && resp.success) {
                            refreshBlock(resp);
                        }
                    }, $progress);
                } break;
                case 2: { // отмена
                    $blockRefresh.hide();
                    $buttons.show();
                } break;
            }
            return false;
        },
        changeBlocked: function(step, block)
        {
            switch(step)
            {
                case 1: { // заблокировать / изменить блокировку
                    $block.find('#i_blocked').hide();
                    $block.find('#i_blocked_error, #i_blocking_id, #i_blocking').show();
                    $buttons.hide();
                    jItemStatus.changeBlocked(5);
                } break;
                case 2: { // отменить
                    if(data.blocked == 1) {
                        $block.find('#i_blocking').hide();
                        $block.find('#i_blocking_id').hide();
                        $block.find('#i_blocking_reas').hide();
                        $block.find('#i_blocked').show();
                    } else {
                        $block.find('#i_blocked_error').hide();
                    }
                    $buttons.show();
                } break;
                case 3: { // сохранить
                    data.blocked_reason = $blocked_reason.val();
                    data.blocked_id = $blocked_id.val();
                    bff.ajax(url+'item-block', data, function(resp){
                        if(resp && resp.success)
                        {
                            data.blocked = resp.blocked;
                            if( ! block) {
                                $block.find('#i_blocked_error').hide();
                            } else {
                                $block.find('#i_blocked_text').html( resp.reason );
                                $block.find('#i_blocked_error').show();
                                jItemStatus.changeBlocked(2);
                                $buttons.hide();
                            }
                        }
                    }, $progress);
                } break;
                case 4: { // разблокировать
                    if( ! bff.confirm('sure')) break;
                    bff.ajax(url+'item-approve', data, function(resp){
                        if (resp && resp.success) {
                            refreshBlock(resp);
                        }
                    }, $progress);
                } break;
                case 5: { // выбор причины
                    var blID = intval($blocked_id.val());
                    if(blID == <?= BBS::BLOCK_OTHER ?>){
                        $block.find('#i_blocking_reas').show(0, function(){
                            $blocked_reason.focus();
                        });
                    }else{
                        $block.find('#i_blocking_reas').hide();
                    }
                }
            }
            return false;
        }
    };
}());
</script>

<div class="<? if( ! $is_popup ) { ?>well well-small<? } ?>" id="j-i-status-block">
<?php } ?>
    <table class="admtbl tbledit">
        <tr>
            <td class="row1 field-title<? if($is_popup) { ?> right<? } ?>" style="width:<?= ( $is_popup ? 133 : 105 ) ?>px;">Статус:</td>
            <td class="row2"><strong><?
                if( $user['blocked'] ) {
                    ?><?= _t('bbs', 'Аккаунт пользователя был заблокирован'); ?><?
                } else {
                    switch($status) {
                        case BBS::STATUS_NOTACTIVATED: { echo _t('bbs', 'Неактивировано'); } break;
                        case BBS::STATUS_PUBLICATED: { echo ($moderated == 0?_t('bbs', 'Ожидает проверки (было отредактировано)'):_t('bbs', 'Публикуется')); } break;
                        case BBS::STATUS_PUBLICATED_OUT: { echo _t('bbs', 'Период публикации завершился'); } break;
                        case BBS::STATUS_BLOCKED: { echo ($moderated == 0?_t('bbs', 'Ожидает проверки (было заблокировано)'):_t('bbs', 'Заблокировано')); } break;
                        case BBS::STATUS_DELETED: { echo _t('bbs', 'Удалено пользователем'); } break;
                    }
                }
            ?></strong><? if($status_changed != '0000-00-00 00:00:00'){ ?>&nbsp;&nbsp;<span class="desc">(<?= tpl::date_format2($status_changed, true, true); ?>)</span><? } ?></td>
        </tr>
        <? if ($status == BBS::STATUS_NOTACTIVATED) { ?>
        <tr>
            <td class="row1"></td>
            <td class="row2">
                <input class="btn btn-mini btn-success success button" type="button" onclick="jItemStatus.activate();" value="<?= _te('bbs', 'активировать'); ?>" />
            </td>
        </tr>
        <? } ?>
        <? if ($status != BBS::STATUS_NOTACTIVATED && ! $user['blocked'] ) { ?>
        <tr>
            <td class="row1 field-title<? if($is_popup) { ?> right<? } ?>"><?= _t('bbs', 'Период:'); ?></td>
            <td class="row2 <?= $status == BBS::STATUS_PUBLICATED_OUT ? 'desc' : '' ?>"><b><?= tpl::date_format3($publicated, 'd.m.Y').' - '.tpl::date_format3($publicated_to, 'd.m.Y'); ?></b></td>
        </tr>
        <tr>
            <td class="row1" colspan="2">
                <div class="alert alert-danger <?= (!$blocked ? 'hidden':'') ?>" id="i_blocked_error">
                    <div><?= _t('bbs', 'Причина блокировки:'); ?>
                        <div class="right desc" id="i_blocked_reason_warn" style="display:none;"></div>
                    </div>
                    <div class="clear"></div>
                    <div id="i_blocked">
                        <span id="i_blocked_text"><?= $blocked_id == BBS::BLOCK_OTHER && !empty($blocked_reason) ? $blocked_reason : (isset($reasons[$blocked_id]) ? $reasons[$blocked_id] : '?') ?></span> - <a href="#" onclick="jItemStatus.changeBlocked(1,0); return false;" class="ajax desc"><?= _t('', 'изменить'); ?></a>
                    </div>
                    <div id="i_blocking_id" style="display: none; margin: 5px 0;">
                        <select name="blocked_id" class="j-i-blocked-id" onchange="jItemStatus.changeBlocked(5);" style="width: auto;"><?= HTML::selectOptions($reasons, $blocked_id) ?></select>
                    </div>
                    <div id="i_blocking_reas" style="display: none;">
                        <textarea name="blocked_reason" class="autogrow j-i-blocked-reason" style="height:60px; min-height:60px;"><?= htmlspecialchars($blocked_reason, ENT_QUOTES, 'UTF-8', false); ?></textarea>
                    </div>
                    <div id="i_blocking" style="display: none;">
                        <a onclick="return jItemStatus.changeBlocked(3, 1);" class="btn btn-mini btn-success" href="#"><?= (!$blocked ? _t('', 'продолжить'):_t('bbs', 'изменить причину')) ?></a>
                        <? if($blocked) { ?><a onclick="return jItemStatus.changeBlocked(4);" class="btn btn-mini btn-success" href="#"><?= _t('bbs', 'разблокировать') ?></a><? } ?>
                        <a onclick="return jItemStatus.changeBlocked(2);" class="btn btn-mini" href="#"><?= _t('', 'отмена'); ?></a>
                    </div>
                </div>
                <div class="hidden alert alert-info" id="i_refresh">
                    <table class="admtbl tbledit">
                        <tr class="row1">
                            <td>
                                <?= _t('bbs', 'Выполнить продление публикации объявления до:'); ?><br /> <b>
                                <?
                                    $nRefreshPeriod = $this->getItemRefreshPeriod(
                                        ( $status === BBS::STATUS_PUBLICATED ? $publicated_to : BFF_NOW )
                                    );
                                    echo tpl::date_format2($nRefreshPeriod, true);
                                ?></b>
                            </td>
                        </tr>
                        <tr class="row1">
                            <td>
                                <a onclick="return jItemStatus.refresh(1);" class="btn btn-mini btn-success" href="#"><?= _t('bbs', 'продлить'); ?></a>
                                <a onclick="return jItemStatus.refresh(2);" class="btn btn-mini" href="#"><?= _t('', 'отменить'); ?></a>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <? if( ! ($moderated==1 && $blocked) && $status != BBS::STATUS_DELETED ) { ?>
        <tr class="j-i-status-buttons">
            <td class="row1" colspan="2" style="padding-left: <?= ($is_popup ? 90 : 110) ?>px;">
               <?
               if ($moderated == 0) { ?>
                    <input class="btn btn-mini btn-success success button" type="button" onclick="jItemStatus.approve();" value="<?= ($blocked ? _t('bbs', 'проверено, все впорядке') : _t('bbs', 'проверено')) ?>" />
               <? } else {
                   if ( $moderated == 2 ) {
                        ?><input class="btn btn-mini btn-success success button" type="button" onclick="jItemStatus.approve();" value="<?= ($blocked ? _t('bbs', 'проверено, все впорядке') : _t('bbs', 'проверено')) ?>" /> <?
                   }
                   if ($status == BBS::STATUS_PUBLICATED_OUT) {
                        ?><input class="btn btn-mini submit button" type="button" onclick="jItemStatus.refresh(0);" value="<?= _te('bbs', 'опубликовать'); ?>" /> <?
                   } else if ($status == BBS::STATUS_PUBLICATED) {
                        ?><input class="btn btn-mini submit button" type="button" onclick="jItemStatus.unpublicate();" value="<?= _te('bbs', 'снять с публикации'); ?>" /> <?
                   }
               }
               if ( ! $blocked) { ?>
                   <a class="btn btn-mini text-error" onclick="jItemStatus.changeBlocked(1); return false;" id="i_block_lnk"><?= _t('bbs', 'заблокировать'); ?></a>
               <? } ?>
               <div class="progress j-i-status-progress" style="margin: 8px 8px 0; display: none;"></div>
            </td>
        </tr>
        <? }
        } # endif: ($status != BBS::STATUS_NOTACTIVATED  && ! $user['blocked']) ?>
        <? bff::hook('bbs.admin.item.form.status', array('is_popup'=>$is_popup,'data'=>&$aData)) ?>
    </table>
<?php if (!$is_refresh) { ?>
</div>
<?php } ?>