<?php
    /**
     * @var $this Shops
     */
    tpl::includeJS(array('tablednd'));
    $aData = HTML::escape($aData, 'html', array('keyword_edit'));
    $edit = ! empty($id);
    $aTabs = bff::filter('shops.admin.category.form.tabs', array(
        'info' => _t('shops', 'Основные'),
        'seo' => _t('', 'SEO'),
    ), array('edit'=>$edit,'data'=>&$aData));

echo tplAdmin::blockStart( $edit ? _t('shops', 'Магазины / Категории / Редактирование') : _t('shops', 'Магазины / Категории / Добавление') );
?>
<form method="post" action="" name="shopsCategoryForm" id="shopsCategoryForm" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $id ?>" />
<div class="tabsBar" id="shopsCategoryFormTabs">
    <? foreach($aTabs as $k=>$v) { ?>
        <span class="tab<? if($k == 'info') { ?> tab-active<? } ?>"><a href="javascript:void(0);" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>
<!-- таб: Основные -->
<div class="j-tab j-tab-info">
<table class="admtbl tbledit">
<tr>
    <td class="row1" width="120"><span class="field-title"><?= _t('shops', 'Основная категория'); ?></span>:</td>
    <td class="row2">
        <? if( ! $edit){ ?>
            <select name="pid"><?= $pid_options ?></select>
        <? } else { ?>
            <?
                $pid_title = array();
                if( ! empty($pid_options) ) foreach($pid_options as $v) $pid_title[] = $v['title'];
            ?>
            <p class="bold"><?= join('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', $pid_title); ?></p>
            <input type="hidden" name="pid" value="<?= $pid ?>" />
        <? } ?>
    </td>
</tr>
<?= $this->locale->buildForm($aData, 'shops-category',
'
<tr class="required">
    <td class="row1"><span class="field-title">'._t('', 'Название').'</span>:</td>
    <td class="row2"><input class="stretch lang-field" type="text" name="title[<?= $key ?>]" id="j-shops-cat-title-<?= $key ?>" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" /></td>
</tr>
'); ?>
<tr>
    <td class="row1">
        <span class="field-title"><?= _t('', 'URL Keyword'); ?></span>:<br />
        <a href="javascript:void(0);" onclick="return bff.generateKeyword('#j-shops-cat-title-<?= LNG ?>', '#j-shops-cat-keyword');" class="ajax desc small"><?= _t('', 'сгенерировать'); ?></a>
    </td>
    <td class="row2">
        <input class="stretch" type="text" maxlength="100" name="keyword_edit" id="j-shops-cat-keyword" value="<?= $keyword_edit ?>" />
    </td>
</tr>
<? bff::hook('shops.admin.category.form', array('edit'=>$edit,'data'=>&$aData)); ?>
<? if($edit && $this->model->catIsMain($id, $pid))
{
    $oIcon = Shops::categoryIcon($id);
    foreach($oIcon->getVariants() as $iconField=>$v) {
        $oIcon->setVariant($iconField);
        $icon = $v;
        $icon['uploaded'] = ! empty($aData[$iconField]);
    ?>
    <tr>
        <td class="row1">
            <span class="field-title"><?= $icon['title'] ?></span>:<? if(sizeof($v['sizes']) == 1) { $sz = current($v['sizes']); ?><br /><span class="desc"><?= ($sz['width'].'x'.$sz['height']) ?></span><? } ?>
        </td>
        <td class="row2">
            <input type="file" name="<?= $iconField ?>" <? if($icon['uploaded']){ ?>style="display:none;" <? } ?> />
            <? if($icon['uploaded']) { ?>
                <div style="margin:5px 0;">
                    <input type="hidden" name="<?= $iconField ?>_del" class="del-icon" value="0" />
                    <img src="<?= $oIcon->url($id, $aData[$iconField], $icon['key']) ?>" alt="" /><br />
                    <a href="javascript:void(0);" class="ajax desc cross but-text" onclick="return jShopsCategory.iconDelete(this);"><?= _t('', 'Delete'); ?></a>
                </div>
            <? } ?>
        </td>
    </tr>
    <? }
} ?>
</table>
</div>
<!-- таб: SEO -->
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'search-category'); ?>
</div>
<? bff::hook('shops.admin.category.form.tabs.content', array('edit'=>$edit,'data'=>&$aData,'tabs'=>$aTabs)); ?>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success button submit" value="<?= _te('', 'Save') ?>" />
    <input type="button" class="btn button cancel" value="<?= _te('', 'Cancel') ?>" onclick="bff.redirect('<?= $this->adminLink('categories_listing') ?>')" />
</div>
</form>

<script type="text/javascript">
var jShopsCategory = (function(){
    $(function(){
        new bff.formChecker( document.forms.shopsCategoryForm );
        var $form = $('#shopsCategoryForm');

        // tabs
        $form.find('#shopsCategoryFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });
    });

    return {
        iconDelete: function(link){
            var $block = $(link).parent();
            $block.hide().find('input.del-icon').val(1);
            $block.prev().show();
            return false;
        }
    };
}());
</script>
<?= tplAdmin::blockStop(); ?>