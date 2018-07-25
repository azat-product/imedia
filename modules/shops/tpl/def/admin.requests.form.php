<?php
    /**
     * @var $this Shops
     */
    $aData = HTML::escape($aData, 'html', array('name','phone','email'));
    $edit = ! empty($id);
?>
<form name="ShopsRequestsForm" id="ShopsRequestsForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="edit" />
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr>
    <td class="row1 right" width="100"><span class="field-title bold"><?= _t('shops', 'Магазин:'); ?></span></td>
    <td class="row1" width="5"></td>
    <td class="row2">
        <? if($shop_id && ! empty($shop)) { ?>
            <a href="#" class="nolink" onclick="return bff.shopInfo(<?= $shop_id ?>);"><?= $shop['title'] ?></a>
            <a href="<?= $shop['link'] ?>" class="linkout but" target="_blank"></a>
        <? } else { ?>
        <span>?</span>
        <? } ?>
    </td>
</tr>
<? if ($user_id) { ?>
<tr>
    <td class="row1 right"><span class="field-title"><?= _t('users', 'Пользователь:'); ?></span></td>
    <td class="row1"></td>
    <td class="row2">
        <? if ( ! empty($user) ) { ?>
            <a href="#"  class="ajax<?= ($user['blocked'] ? ' text-error':'') ?>" onclick="return bff.userinfo(<?= $user['user_id'] ?>);"><?= $user['email'] ?></a>
        <? } else { ?>
            <span>?</span>
        <? } ?>
    </td>
</tr>
<? } else { ?>
<tr>
    <td class="row1 right"><span class="field-title"><?= _t('users', 'Имя:'); ?></span></td>
    <td class="row1"></td>
    <td class="row2"><?= $name ?></td>
</tr>
<tr>
    <td class="row1 right"><span class="field-title"><?= _t('', 'Телефон:'); ?></span></td>
    <td class="row1"></td>
    <td class="row2"><?= $phone ?></td>
</tr>
<tr>
    <td class="row1 right"><span class="field-title"><?= _t('', 'E-mail:'); ?></span></td>
    <td class="row1"></td>
    <td class="row2"><a href="mailto:<?= HTML::escape($email) ?>"><?= $email ?></a></td>
</tr>
<? } ?>
<? bff::hook('shops.admin.shop.request.form', array('edit'=>$edit,'data'=>&$aData)) ?>
<tr>
    <td colspan="3">
        <hr class="cut" />
    </td>
</tr>
<tr>
    <td class="row1 right"><span class="field-title"><?= _t('', 'Описание:'); ?></span></td>
    <td class="row1"></td>
    <td class="row2">
        <textarea class="stretch" rows="8" id="request-description" name="description"><?= $description ?></textarea>
    </td>
</tr>
<tr class="footer">
    <td class="row1" colspan="2"></td>
    <td class="row2">
        <input type="button" class="btn button cancel" value="<?= _te('', 'Назад к списку'); ?>" onclick="jShopsRequestsFormManager.action('cancel');" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
var jShopsRequestsForm =
(function(){
    var $progress, $form, formChk, id = parseInt(<?= $id ?>);
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $progress = $('#ShopsRequestsFormProgress');
        $form = $('#ShopsRequestsForm');
        
    });
    return {
        del: function()
        {
            if( id > 0 ) {
                bff.ajaxDelete('sure', id, ajaxUrl+'&act=delete&id='+id,
                    false, {progress: $progress, repaint: false, onComplete:function(){
                        bff.success('<?= _t('', 'Запись успешно удалена'); ?>');
                        jShopsRequestsFormManager.action('cancel');
                        jShopsRequestsList.refresh();
                    }});
            }
        },
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            bff.ajax(ajaxUrl, $form.serialize(), function(data){
                if(data && data.success) {
                    bff.success('<?= _t('', 'Данные успешно сохранены'); ?>');
                    if(returnToList || ! id) {
                        jShopsRequestsFormManager.action('cancel');
                        jShopsRequestsList.refresh( ! id);
                    }
                }
            }, $progress);
        },
        onShow: function ()
        {
            formChk = new bff.formChecker($form);
        }
    };
}());
</script>