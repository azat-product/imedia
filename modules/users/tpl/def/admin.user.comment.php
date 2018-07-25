<?php
    // popup, user_id, admin_comment
?>

<div id="userAdminCommentBlock<?= ($popup ? 'Popup' : '') ?>">
    <div class="alert alert-info j-view" style="margin-bottom:5px; padding: 10px 20px;<? if(!mb_strlen(trim($admin_comment))){ ?> display: none;<? } ?>">
        <span class="j-view-content"><?= nl2br($admin_comment) ?></span>
         - <a data-action="edit" class="ajax desc j-action" href="#"><?= _t('', 'изменить'); ?></a>
    </div>
    <div class="alert alert-info j-edit" style="display: none; margin-bottom: 5px; padding: 10px 20px;">
        <textarea name="admin_comment" class="stretch j-edit-content" style="height:60px; min-height:60px;"><?= HTML::escape($admin_comment); ?></textarea>
        <a data-action="save" class="btn btn-mini btn-success j-action" href="#"><?= _t('', 'Save') ?></a>
        <a data-action="cancel" class="btn btn-mini j-action" href="#"><?= _t('', 'Cancel'); ?></a>
    </div>
</div>

<script type="text/javascript">
$(function(){
    var popup = <?= ($popup ? 'true' : 'false') ?>;
    var data = {id: <?= $user_id ?>, tuid: '<?= $tuid ?>'};
    var url = '<?= $this->adminLink('ajax&act='); ?>';

    var $block = $('#userAdminCommentBlock'+(popup ? 'Popup' : ''));
    var $toggler = $('#userAdminCommentBlockToggler');
    var $view = $block.find('.j-view');
    var $cont = $view.find('.j-view-content');
    var $edit = $block.find('.j-edit');
    $block.on('click', '.j-action', function(){
        switch ($(this).data('action')) {
            case 'save': {
                data['admin_comment'] = $edit.find('.j-edit-content').val();
                bff.ajax(url+'user-admin-comment', data, function(resp, errors) {
                    if( errors.length > 0) {
                        return;
                    }
                    $cont.html(resp.comment);
                    $edit.hide();
                    $view.show();
                    if (!popup) {
                        $toggler.hide();
                        if (!$cont.text().length) {
                            $view.hide();
                            $toggler.show();
                        }
                    }
                }, function(p){});
            } break;
            case 'edit': {
                $view.hide();
                $edit.show();
            } break;
            case 'cancel': {
                $edit.hide();
                $view.show();
                if (!popup) {
                    $toggler.hide();
                    if (!$cont.text().length) {
                        $view.hide();
                        $toggler.show();
                    }
                }
            } break;
        }
        return false;
    });
});
</script>