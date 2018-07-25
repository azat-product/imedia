<?php

tpl::includeJS(array('tablednd'), true);
$urlAction = $this->adminLink('types&cat_id='.$cat_id.'&act=');

echo tplAdmin::blockStart('Типы');
?>
<table class="table table-condensed table-hover admtbl tblhover" id="types_listing">
    <?= $list ?>
</table>

<div style="margin-top: 8px;">
    <div class="left">
        <input type="button" class="btn btn-success button submit" onclick="jCategoryTypes.add();" value="<?= _te('', 'Добавить тип'); ?>" />
    </div>
    <div class="right desc" style="width:80px; text-align:right;">
        <span id="progress" style="margin-left:5px; display:none;" class="progress"></span>
        &nbsp;&nbsp; &darr; &uarr;
    </div>
    <div class="clear clearfix"></div>
</div>

<script type="text/html" id="type_form_tmpl">
<div class="ipopup" style="width:300px;">
<div class="ipopup-wrapper">
<div class="ipopup-title"><% if(edit){ %><?= _t('', 'Редактирование'); ?><% }else{ %><?= _t('', 'Добавление'); ?><% } %> <?= _t('bbs', 'типа'); ?></div>
<div class="ipopup-content">   
    <form action="" method="post">
        <input type="hidden" name="act" value="<%=act%>" />
        <input type="hidden" name="cat_id" value="<%=cat_id%>" />
        <input type="hidden" name="type_id" value="<%=id%>" />  
        <table class="admtbl tbledit">
        <%=form%>
        <tr class="footer">
            <td colspan="2" style="text-align: center;">
                <input type="button" class="btn btn-success button submit" value="<?= _te('', 'Save') ?>" onclick="jCategoryTypes.submit(this.form);" />
                <input type="button" class="btn button cancel" value="<?= _te('', 'Cancel') ?>" onclick="$.fancybox.close();" />
            </td>
        </tr>
        </table>
    </form>
</div> 
</div>
</div>
</script>

<script type="text/javascript">
var jCategoryTypes = (function(){
    var $progress, $list, formTpl, catID = intval(<?= $cat_id ?>);

    $(function(){
        $progress = $('#progress');
        $list = $('#types_listing');
        formTpl = bff.tmpl('type_form_tmpl');
        bff.rotateTable($list, '<?= $urlAction; ?>rotate', '#progress');
    });

    function add()
    {
        bff.ajax('<?= $urlAction; ?>form&type_id=0',{edit:0},function(data){
            if(data && data.success) {
                data.edit = 0; data.act = 'add'; data.cat_id = catID;
                $.fancybox( formTpl(data) );
            }
        }, $progress);
        return false;
    }
    
    function del(id, link)
    {
        bff.ajaxDelete('sure', id, '<?= $urlAction; ?>delete&type_id='+id,
            link, {progress: $progress, repaint: false});
        return false;
    }
    
    function edit(id)
    {
        bff.ajax('<?= $urlAction; ?>form&type_id='+id, {edit:1}, function(data){
            if(data && data.success) {
                data.edit = 1; data.act = 'edit'; data.cat_id = catID;
                $.fancybox( formTpl(data) );
            }
        }, $progress);
        return false;
    }
    
    function toggle(id, $link)
    {
        bff.ajaxToggle(id, '<?= $urlAction; ?>toggle&type_id='+id,
               {link: $link, progress: $progress});
               return false;
    }

    function submit(form)
    {
        bff.ajax('<?= $urlAction; ?>', $(form).serialize(), function(data){
            if(data && data.success) {
                $list.html(data.list);
                $list.tableDnDUpdate();
                $.fancybox.close();
            }
        }, $progress);
    }
    
    return {add: add, edit: edit, del: del, toggle:toggle, submit:submit};
}());
</script>
<?= tplAdmin::blockStop(); ?>