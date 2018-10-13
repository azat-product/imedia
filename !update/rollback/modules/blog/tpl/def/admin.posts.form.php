<?php
    /**
     * @var $this Blog
     */
    $aData = HTML::escape($aData, 'html', array('cat_id'));
    $edit = ! empty($id);
    $aTabs = bff::filter('blog.admin.post.form.tabs', array(
        'info' => _t('', 'Основные'),
        'seo' => _t('', 'SEO'),
    ), array('edit'=>$edit, 'data'=>&$aData));
?>
<form name="BlogPostsForm" id="BlogPostsForm" action="<?= $this->adminLink(null) ?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<input type="hidden" name="cat_id" id="post-cat_id" value="<?= $cat_id ?>" />
<div class="tabsBar" id="BlogPostsFormTabs">
    <? foreach($aTabs as $k=>$v) { ?>
        <span class="tab<? if($k == 'info') { ?> tab-active<? } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>
<div class="j-tab j-tab-info">
    <table class="admtbl tbledit">
    <? if(Blog::categoriesEnabled()){ ?>
    <tr>
        <td class="row1 field-title" width="100"><?= _t('', 'Категория') ?><span class="required-mark">*</span>:</td>
        <td class="row2">
            <div class="left">
               <select name="cat_id" id="post-cat"><?= $cats ?></select>
            </div>
            <div class="right desc">
                <? if($edit && $modified!='0000-00-00 00:00:00' ) { ?>
                    <?= _t('', 'последние изменения: [date]', array('date'=>tpl::date_format2($modified, true))) ?>
                <? } ?>
            </div>
            <div class="clear clearfix"></div>
        </td>
    </tr>
    <? } ?>
    <?= $this->locale->buildForm($aData, 'blog-post','
    <tr>
        <td class="row1 field-title" width="100">'._t('blog','Заголовок').':</td>
        <td class="row2">
            <input class="stretch lang-field" type="text" id="post-title-<?= $key ?>" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" />
        </td>
    </tr>
    <tr>
        <td class="row1"><span class="field-title">'._t('blog','Краткое описание').':</span></td>
        <td class="row2">
            <?= tpl::jwysiwyg($aData[\'textshort\'][$key], \'textshort[\'.$key.\']\', 0, 125); ?>
        </td>
    </tr>
    '); ?>
    <tr>
        <td class="row1" colspan="2">
            <?= $publicator->form($content, $id, 'content', 'jBlogPostsFormPublicator'); ?>
        </td>
    </tr>
    <tr>
        <td class="row1 field-title"><?= _t('blog','Превью') ?>:</td>
        <td class="row2">
            <input type="file" name="preview" size="17" <? if( ! empty($preview)){ ?>style="display:none;" <? } ?> />
            <? if( ! empty($preview)) { ?>
                <div style="margin: 5px 0;">
                    <input type="hidden" name="preview_del" id="preview_delete_flag" value="0" />
                    <img id="shop_logo" src="<?= $preview_list ?>" alt="" /><br />
                    <a href="#" class="ajax desc cross but-text" onclick="return jBlogPostsForm.deletePreview(this);"><?= _t('','delete') ?></a>
                </div>
            <? } ?>
        </td>
    </tr>
    <? if(Blog::tagsEnabled()){ ?>
    <tr>
        <td class="row1"><span class="field-title"><?= _t('blog','Теги') ?>:</span></td>
        <td class="row2">
            <?= $this->postTags()->tagsForm($id, $this->adminLink('posts&act=tags-suggest'), '700'); ?>
        </td>
    </tr>
    <? } ?>
    <? bff::hook('blog.admin.post.form', array('edit'=>$edit,'data'=>&$aData)); ?>
    <tr>
        <td class="row1"><span class="field-title"><?= _t('blog', 'Отображается') ?>:</span></td>
        <td class="row2">
            <label class="checkbox"><input type="checkbox" id="post-enabled" name="enabled"<? if($enabled){ ?> checked="checked"<? } ?> /></label>
        </td>
    </tr>
    <tr class="footer">
        <td colspan="2">

        </td>
    </tr>
    </table>
</div>
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'view'); ?>
</div>
<? bff::hook('blog.admin.post.form.tabs.content', array('edit'=>$edit,'data'=>&$aData,'tabs'=>$aTabs)); ?>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success j-btn-submit" value="<?= _te('', 'Save') ?>" onclick="jBlogPostsForm.save(false);" />
    <? if($edit) { ?><input type="submit" class="btn btn-success j-btn-submit-back" value="<?= _te('', 'Save and back') ?>" onclick="jBlogPostsForm.save(true);" /><? } ?>
    <? if($edit) { ?><input type="button" class="btn btn-danger j-btn-delete" value="<?= _te('', 'Delete') ?>" onclick="jBlogPostsForm.del(); return false;" /><? } ?>
    <input type="button" class="btn button j-btn-cancel" value="<?= _te('', 'Cancel') ?>" onclick="jBlogPostsFormManager.action('cancel');" />
</div>
</form>

<script type="text/javascript">
var jBlogPostsForm =
(function(){
    var $progress, $form, id = parseInt(<?= $id ?>);
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';
    var returnToList = false;

    $(function(){
        $progress = $('#BlogPostsFormProgress');
        $form = $('#BlogPostsForm');

        // tabs
        $form.find('#BlogPostsFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });

        bff.iframeSubmit($form, function(data){
            if(data && data.success) {
                bff.success('<?= _t('','Данные успешно сохранены') ?>');
                jBlogPostsFormPublicator.ajaxSave();
                if(returnToList || ! id) {
                    jBlogPostsFormManager.action('cancel');
                    jBlogPostsList.refresh( ! id);
                }else if(id && data.reload){
                    jBlogPostsFormManager.action('edit', id);

                }
            }
        },{
            url: ajaxUrl,
            progress: $progress,
            beforeSubmit:function(){
                <? if(Blog::categoriesEnabled()) { ?>
                if(intval($form.find('#post-cat').val()) == 0) {
                    bff.error('<?= _t('categories','Выберите категорию') ?>');
                    return false;
                }
                <? } ?>
                return true;
            }
        });

    });

    return {
        del: function()
        {
            if( id > 0 ) {
                bff.ajaxDelete('sure', id, ajaxUrl+'&act=delete&id='+id,
                    false, {progress: $progress, repaint: false, onComplete:function(){
                        bff.success('<?= _t('','Запись успешно удалена') ?>');
                        jBlogPostsFormManager.action('cancel');
                        jBlogPostsList.refresh();
                    }});
            }
        },
        save: function(retToList)
        {
            returnToList = retToList;
        },
        onShow: function()
        {

        },
        onLang: function(key)
        {
            jBlogPostsFormPublicator.setLang(key);
        },
        deletePreview: function(link)
        {
            if (confirm('<?= _t('blog','Удалить текущее изображение?') ?>')) {
                var $block = $(link).parent();
                $block.hide().find('#preview_delete_flag').val(1);
                $block.prev().show();
                return false;
            }
        }
    };
}());
</script>