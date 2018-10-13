<?php
    // tuid, popup
    // user_id,
    // -- superadmin, session_id, last_activity
    // activated, -- activate_key, activate_expire
    // blocked, blocked_reason
    $jObject = 'jUserStatus'.($popup ? 'Popup' : '');
?>

<div id="u_status_block<?= ($popup ? '_popup' : '') ?>">
    <? if ( ! $activated) { ?>
    <div class="alert alert-info j-u-status-activate text-center" style="margin-bottom:5px;">
        <span class="bold"><?= _t('', 'неактивирован'); ?></span> <a class="ajax desc" onclick="<?= $jObject ?>.activate(this); return false;">(<?= _t('', 'активировать'); ?>)</a>
    </div>
    <? } ?>
    <? if ($fake && $popup) { ?>
    <div class="alert text-center">
        <span class="bold"><?= _t('', 'сгенерированный пользователь'); ?></span>
    </div>
    <? } ?>

    <div class="alert alert-error <?= ( ! $blocked ? ' hidden':'') ?> u_status_block" style="margin-bottom: 5px; padding: 10px 20px;">
        <div><?= _t('users', 'Причина блокировки:'); ?></div>
        <div class="u_blocked">
            <span class="u_blocked_text"><?= ( ! empty($blocked_reason) ? nl2br($blocked_reason) :'?') ?></span> - <a href="javascript:void(0);" onclick="<?= $jObject ?>.changeBlocked(1,0); return false;" class="ajax desc"><?= _t('', 'изменить'); ?></a>
        </div>
        <div class="u_blocking" style="display: none;">
            <textarea name="blocked_reason" class="stretch u_blocked_reason" style="height:60px; min-height:60px;"><?= $blocked_reason; ?></textarea>
            <a onclick="<?= $jObject ?>.changeBlocked(3,1); return false;" class="btn btn-mini btn-success" href="javascript:void(0);"><?= ( ! $blocked ? _t('', 'продолжить'):_t('', 'изменить причину')) ?></a>
            <a onclick="<?= $jObject ?>.changeBlocked(2); return false;" class="btn btn-mini" href="javascript:void(0);"><?= _t('', 'отмена'); ?></a>
        </div>
    </div>
    <? bff::hook('users.admin.user.form.status', array('data'=>&$aData,'popup'=>$popup,'blockID'=>'u_status_block'.($popup ? '_popup' : ''))); ?>
</div>

<script type="text/javascript">
var <?= $jObject ?> =
(function(){
    var inited = false, form, popup = <?= ($popup ? 'true' : 'false') ?>, $block, $progress, $blocked_reason, process = false;
    var $userinfoPopup = false;
    var data = {id: <?= $user_id ?>, blocked: <?= ($blocked ? 1 : 0) ?>, tuid: '<?= $tuid ?>'};
    var url = '<?= $this->adminLink('ajax&act='); ?>';
    $(function(){
        $block = $('#u_status_block'+(popup ? '_popup' : ''));
        $progress = $block.find('.u_status_progress');
        $blocked_reason = $block.find('.u_blocked_reason');
        $userinfoPopup = $('#j-users-userinfo-popup');
        inited = true;
    });

    function changeBlocked(step, block, $btn)
    {
        if( ! inited) return false;

        if(step == 1) // заблокировать / изменить блокировку
        {
            $block.find('.u_blocked').hide();
            $block.find('.u_status_block, .u_blocking').show(0, function(){
                if(popup) $.fancybox.resize();
                $blocked_reason.focus();
            });
        }
        else if(step == 2) //отменить
        {
            if(data.blocked == 1) {
                $block.find('.u_blocking').hide();
                $block.find('.u_blocked').show();
            } else {
                $block.find('.u_status_block').hide();
            }
            if(popup) $.fancybox.resize();
        }
        else if(step == 3) //сохранить
        {
            if(process) return false;
            var blockedPrevious = data.blocked;
            data.blocked_reason = $blocked_reason.val();
            data.blocked = block;
            bff.ajax(url+'user-block', data, function(resp, errors)
            {
                if( errors.length > 0) {
                    $block.find('.u_status_block').hide();
                    data.blocked = blockedPrevious;
                    return;
                }

                block = data.blocked = resp.blocked;

                $block.parent().find('.u_unblock_lnk, .u_block_lnk').hide().filter('.u_'+(block==1?'un':'')+'block_lnk').show();
                if( block == 0 ) {
                    $block.find('.u_status_block').hide();
                    if(popup) {
                        $('#u'+data.id).removeClass('block').addClass('unblock');
                        $.fancybox.resize();
                        if(popup) $userinfoPopup.find('.j-act-block, .j-act-unblock').toggle();
                    }
                } else {
                    if(popup) $('#u'+data.id).removeClass('unblock').addClass('block');
                    $blocked_reason.val( $block.find('.u_blocked_text').html(resp.reason).text() );
                    $block.find('.u_status_block').show();
                    <?= $jObject ?>.changeBlocked(2);
                    if(popup) $userinfoPopup.find('.j-act-block').hide().end().find('.j-act-unblock').show();
                }
            }, function(p){ process = p; $progress.toggle(); });
        }
        return false;
    }

    return {
        changeBlocked: changeBlocked,
        block: function() {
            changeBlocked(1);
            return false;
        },
        unblock: function($btn){
            if(bff.confirm('sure'))
                changeBlocked(3,0, $btn);
            return false;
        },
        activate: function(activateButton){
             if(bff.confirm('sure'))
             bff.ajax(url+'user-activate', data, function(resp){
                if(resp && resp.success) {
                    $(activateButton).hide();
                    $block.parent().find('.u_block_links').show();
                    var $activateBlock = $block.find('.j-u-status-activate');
                    $.scrollTo($activateBlock, {offset:-100,duration:200});
                    $activateBlock.hide();
                    bff.success('<?= _t('users', 'Аккаунт был успешно активирован'); ?>');
                }
             });
             return false;
        }
    };
}());
</script>