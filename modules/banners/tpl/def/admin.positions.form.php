<?php
    $aData = HTML::escape($aData, 'html', array('title','keyword','width','height'));
    $edit = ! empty($id);
?>
<form name="BannersPositionsForm" id="BannersPositionsForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr class="required">
    <td class="row1" width="115"><span class="field-title"><?= _t('', 'Название'); ?><span class="required-mark">*</span>:</span></td>
    <td class="row2">
        <input class="stretch" type="text" id="position-title" name="title" value="<?= $title ?>" maxlength="200" />
    </td>
</tr>
<tr class="required<?php if(!FORDEV) { ?> hidden<?php } ?>">
    <td class="row1 field-title"><?= _t('', 'Keyword'); ?><span class="required-mark">*</span>:</span></td>
    <td class="row2">
        <input class="text-field" type="text" id="position-keyword" name="keyword" value="<?= $keyword ?>" maxlength="50" />
    </td>
</tr>
<tr>
    <td class="row1 field-title"><?= _t('banners', 'Ширина'); ?>:</td>
    <td class="row2">
        <input class="short" type="text" id="position-width" name="width" value="<?= $width ?>" maxlength="25" />
        <div class="help-inline">px</div>
    </td>
</tr>
<tr>
    <td class="row1 field-title"><?= _t('banners', 'Высота'); ?>:</td>
    <td class="row2">
        <input class="short" type="text" id="position-height" name="height" value="<?= $height ?>" maxlength="25" />
        <div class="help-inline">px</div>
    </td>
</tr>
<tr style="height: 33px;">
    <td class="row1 field-title"><?= _t('banners', 'Ротация'); ?>:</td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" id="position-rotation" name="rotation"<?php if($rotation){ ?> checked="checked"<?php } ?> /></label>
        <span id="j-rotation-visible" <?= ! $rotation || $filter_list_pos ? 'class="displaynone"' : '' ?>><?= _t('banners', 'отображать одновременно баннеров:'); ?>
            <input type="number" name="rotation_visible" value="<?= ! empty($rotation_visible) ? $rotation_visible : 1 ?>" min="1" max="10" style="width: 40px;"/>
        </span>
    </td>
</tr>
<tr<?php if(!FORDEV) { ?> class="hidden" <?php } ?>>
    <td class="row1 field-title"><?= _t('banners', 'Фильтры'); ?>:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-filter_sitemap" name="filter_sitemap"<?php if($filter_sitemap){ ?> checked="checked"<?php } ?> /><?= _t('banners', 'раздел сайта'); ?></label>
        <label class="checkbox"><input type="checkbox" id="position-filter_region" name="filter_region"<?php if($filter_region){ ?> checked="checked"<?php } ?> /><?= _t('banners', 'регион'); ?></label>
        <label class="checkbox"><input type="checkbox" id="position-filter_list_pos" name="filter_list_pos"<?php if($filter_list_pos){ ?> checked="checked"<?php } ?> /><?= _t('banners', '№ позиции в списке'); ?></label>
        <label class="checkbox"><input type="checkbox" id="position-filter_category" name="filter_category"<?php if($filter_category){ ?> checked="checked"<?php } ?> /><?= _t('banners', 'категория'); ?></label>
    </td>
</tr>
<tr id="position-filter_category_module"<?php if(!$filter_category || sizeof($category_modules) == 1 || !FORDEV){ ?> class="hidden"<?php } ?>>
    <td class="row1"><span class="field-title"><?= _t('banners', 'Модуль категорий:'); ?></span></td>
    <td class="row2">
        <select name="filter_category_module" class="input-medium"><?= HTML::selectOptions($category_modules, $filter_category_module) ?></select>
    </td>
</tr>
<tr<?php if( ! Banners::FILTER_AUTH_USERS ) { ?> class="hidden"<?php } ?>>
    <td class="row1 field-title"><?= _t('', 'Пользователи'); ?>:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-filter_auth_users" name="filter_auth_users"<?php if($filter_auth_users){ ?> checked="checked"<?php } ?> /><?= _t('banners', 'скрывать для авторизованных пользователей'); ?></label>
    </td>
</tr>
<? bff::hook('banners.admin.position.form', array('edit'=>$edit,'data'=>&$aData)); ?>
<tr>
    <td class="row1 field-title"><?= _t('','Enabled') ?>:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-enabled" name="enabled"<?php if($enabled){ ?> checked="checked"<?php } ?> /></label>
    </td>
</tr>
<tr class="footer">
    <td colspan="2">
        <input type="submit" class="btn btn-success button submit" value="<?= _te('', 'Save') ?>" onclick="jBannersPositionsForm.save(false);" />
        <?php if($edit) { ?><input type="button" class="btn btn-success button submit" value="<?= _te('', 'Save and back') ?>" onclick="jBannersPositionsForm.save(true);" /><?php } ?>
        <?php if($edit && FORDEV) { ?><input type="button" onclick="jBannersPositionsForm.del(); return false;" class="btn btn-danger button delete" value="<?= _te('', 'Delete') ?>" /><?php } ?>
        <input type="button" class="btn button cancel" value="<?= _te('', 'Cancel') ?>" onclick="jBannersPositionsFormManager.action('cancel');" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
var jBannersPositionsForm =
(function(){
    var $progress, $form, formChk, id = <?= $id ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $progress = $('#BannersPositionsFormProgress');
        $form = $('#BannersPositionsForm');
        $form.on('click', '#position-filter_category', function(){
            $form.find('#position-filter_category_module').toggle($(this).is(':checked'));
        });

        var $rotVis = $('#j-rotation-visible');
        var $posRot = $('#position-rotation');
        var $filPos = $('#position-filter_list_pos');

        $posRot.change(function(){
            toggleRotVis();
        });

        $filPos.change(function(){
            toggleRotVis();
        });

        function toggleRotVis(){
            $rotVis.toggleClass('displaynone', ! $posRot.is(':checked') || $filPos.is(':checked'));
        }
    });
    return {
        del: function()
        {
            if (id > 0) {
                bff.redirect('<?= $this->adminLink('position_delete&id=') ?>'+id);
            }
        },
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            bff.ajax(ajaxUrl, $form.serialize(), function(data,errors){
                if(data && data.success) {
                    bff.success('<?= _t('','Данные успешно сохранены') ?>');
                    if(returnToList || ! id) {
                        jBannersPositionsFormManager.action('cancel');
                        jBannersPositionsList.refresh( ! id);
                    }
                }
            }, $progress);
        },
        onShow: function()
        {
            formChk = new bff.formChecker( $form );
        }
    };
}());
</script>