<?php
    /**
     * @var $img BBSItemImages
     */
    tpl::includeJS(array('ui.sortable','qquploader'), true);
    $edit = ($id > 0);
?>

<form method="post" action="" id="item-form-images">
<div class="relative">
    <div class="left">
        <div class="desc">
            <?= _t('bbs', 'Выберите фотографии на Вашем компьютере<br />
            Размер одной фотографий не должен превышать [size].', array('size' => $img->getMaxSize(true))); ?>
            <div id="item-img-upload" style="height: 20px; width: 160px"><a href="javascript:void(0);" class="ajax"><?= _t('', 'Загрузить фотографии'); ?></a></div>
            <div id="item-img-uploader" style="margin-top: 10px;"></div>
        </div>
    </div>
    <div class="clear"></div>
    <span id="progress-images" class="progress" style="display:none; position: absolute; right:0; top:5px;"></span>
</div>

<ul id="item-images" style="display: block; margin: 0;">
<? foreach($images as $v) { if($v) { $imageID = $v['id']; $fn = $v['filename']; $rotate = $img->rotateAvailable($v); ?>
    <li class="wimg wimg<?= $imageID; ?> relative left" style="margin-right: 5px;">
        <input type="hidden" class="imgfn" name="img[<?= $imageID ?>]" rel="<?= $imageID ?>" value="<?= $fn ?>" />
        <a href="<?= $img->getURL($v, BBSItemImages::szView, false); ?>" rel="wimg-group" class="thumbnail">
            <img src="<?= $img->getURL($v, BBSItemImages::szSmall, false); ?>" alt="" />
        </a>
        <a class="but cross" style="position:absolute;right:2px;top:7px;" href="javascript:void(0);" onclick="if(confirm('<?= _t('bbs', 'Удалить изображение?'); ?>')) return jItemImages.del('<?= $imageID; ?>', '<?= $fn ?>'); return false;"></a>
        <? if($rotate): ?><a class="but refresh" style="position:absolute;left:7px;top:7px;" href="javascript:void(0);" onclick="return jItemImages.rotate('<?= $imageID; ?>', '<?= $fn ?>');"></a><? endif; ?>
    </li>
<? } } if(empty($images) && false) { ?><li style="margin:15px; font-weight: bold; width:100%; text-align: center;"><?= _t('bbs', 'нет фотографий'); ?></li><? } ?>
</ul>
<div class="clearfix"></div>

<div class="nophoto-hide" style="<? if($imgcnt<1){ ?>display:none;<? } ?>">
    <p class="desc" style="margin: 5px 0;"><?= _t('bbs', 'перетяните фото для изменения порядка'); ?><strong>&nbsp;&nbsp;&harr;</strong></p>
    <div>
        <? if($edit) { ?>
            <input type="button" class="btn btn-success button submit" value="<?= _te('bbs', 'Сохранить порядок'); ?>" onclick="jItemImages.save();" />
            <input type="button" class="btn btn-danger delete button submit" style="display: none;" onclick="if(confirm('<?= _t('bbs', 'Удалить все фотографии?'); ?>')) jItemImages.delAll(true, []);" value="<?= _te('bbs', 'Удалить все фотографии'); ?>" />
        <? } ?>
    </div>
</div>

</form>

<script type="text/javascript">
var jItemImages = (function(){
    var url = '<?= $this->adminLink('img&item_id='.$id.'&act='); ?>';
    var $form, $progress, uploader, $img, id = intval(<?= $id ?>);
    
    $(function(){
        $form = $('#item-form-images');
        $progress = $('#form-progress');
        $img = $('#item-images', $form);
        
        // init uploader
        uploader = new qq.FileUploaderBasic({
            button: $('#item-img-upload', $form).get(0),
            action: url+'upload',
            limit: <?= $img->getLimit(); ?>,
            uploaded: <?= $imgcnt ?>,
            multiple: true,
            onSubmit: function(id, fileName) {
                $progress.show();
            },
            onComplete: function(id, fileName, data) {
                if(data && data.success) {
                    onImageUpload(data);
                } else {
                    if(data.errors) {
                        bff.error( data.errors );
                    }
                }
                if( ! uploader.getInProgress()) {
                    $progress.hide();
                }
                return true;
            }
        });
        
        initRotate(false);

        $('a[rel=wimg-group]', $form).fancybox();

        <? if( ! $edit) { ?>
        var lostProcessed = false;
        $(window).on('beforeunload', function(){
            if(!lostProcessed && id===0) {
                var $fn = $img.find('input.imgfn');
                if($fn.length > 0) {
                    lostProcessed = true;
                    var fn = [];
                    $fn.each(function(){
                        fn.push($(this).val());
                    });
                    jItemImages.delAll(false, fn);
                }
            }
        });
        <? } ?>
    });
    
    function initRotate(update)
    {
        if(update === true) {
            $img.sortable('refresh');
            $('a[rel=wimg-group]', $img).fancybox();
            onPhotosCountChanged();
        } else {
            $img.sortable();
        }
    }
    
    function onImageUpload(data)
    {
        var imageID = data.id;  

        if(uploader.getUploaded() == 0) $img.find('li').remove();

        var html = '<li class="wimg wimg'+imageID+' relative left">'+
                '<input type="hidden" class="imgfn" name="img['+imageID+']" rel="'+imageID+'" value="'+(data.filename)+'" />'+
                '<a href="'+(data['<?= BBSItemImages::szView ?>'])+'" class="thumbnail" rel="wimg-group"><img src="'+(data['<?= BBSItemImages::szSmall ?>'])+'" /></a>'+
                '<a class="but cross" style="position: absolute;right:2px;top:7px;" href="javascript:void(0);" onclick="if(confirm(\'<?= _t('bbs', 'Удалить изображение?'); ?>\')) return jItemImages.del('+imageID+', \''+(data.filename)+'\'); return false;"></a>';
        if(intval(data.rotate)){
            html = html+'<a class="but refresh" style="position:absolute;left:7px;top:7px;" href="javascript:void(0);" onclick="return jItemImages.rotate('+imageID+', \''+(data.filename)+'\');"></a>';
        }
        html = html+'</li>';
        $img.append(html);
        initRotate(true);
    }

    function onPhotosCountChanged()
    {
        var cnt = $img.find('li').length;
        if(cnt > 0) {
            $form.find('.nophoto-hide').show();
        } else {
            $form.find('.nophoto-hide').hide();
        }
    }

    return {
        del: function(imageID, imageFilename)
        {
            bff.ajax(url+'delete',{image_id: <? if($edit || $img->isExternalSave()){ ?>imageID<? } else { ?>0<? } ?>, filename: imageFilename}, function(data){
                if(data && data.success) {
                    $form.find('li.wimg'+imageID).remove();
                    uploader.decrementUploaded();
                    initRotate(true);
                }
            }, $progress);
            return false;
        },
        delAll: function(async, filenames)
        {
            bff.ajax(url+'delete-all', {filenames: filenames}, function(data){
                if(data && data.success) {
                    $img.empty();
                    uploader.resetUploaded();
                    initRotate(true);
                }
            }, $progress, {async: async});
            return false;
        },
        save: function()
        {
            bff.ajax(url+'saveorder', $form.serialize(), function(data){
                if(data.success) {
                    bff.success('<?= _t('bbs', 'Порядок успешно сохранен'); ?>');
                }
            }, $progress);
        },
        rotate: function(imageID, imageFilename)
        {
            bff.ajax(url+'rotate',{image_id: imageID, filename: imageFilename}, function(data){
                if(data && data.success) {
                    var $bl = $form.find('li.wimg'+imageID);
                    $bl.find('img').attr('src', data['<?= BBSItemImages::szSmall ?>']);
                    $bl.find('[name="img['+imageID+']"]').val(data.filename);
                    $bl.find('.thumbnail').attr('href', data['<?= BBSItemImages::szView ?>']);
                    $bl.find('a.cross').attr('onclick', 'if(confirm(\'<?= _t('', 'Удалить изображение?'); ?>\')) return jItemImages.del('+imageID+', \''+data.filename+'\'); return false;');
                    $bl.find('a.refresh').attr('onclick', 'return jItemImages.rotate('+imageID+', \''+(data.filename)+'\');');
                }
            }, $progress);
            return false;
        }
    }
}());
</script>