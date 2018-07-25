<?php
?>
<div class="relative">
    <span class="progress" id="j-progress" style="display: none; position: absolute; right: 5px; top: 10px;"></span>
    <div id="j-massend-listing"><?= $list ?></div>
</div>
<script type="text/javascript">
$(function(){
    var $list = $('#j-massend-listing');
    var $progress = $('#j-progress');
    var ajaxUrl = '<?= $this->adminLink('ajax&act=massend-') ?>';

    $list.on('click', '.j-delete', function(e){
        e.preventDefault();
        var $el = $(this);
        bff.ajaxDelete('<?= _t('sendmail', 'Удалить рассылку?'); ?>', $el.data('id'), ajaxUrl +'delete', $el, {progress:$progress});
    });

    $list.on('click', '.j-info', function(e){
        e.preventDefault();
        var $el = $(this);
        $.fancybox('', {ajax:true, href:ajaxUrl + 'info&id=' + $el.data('id')});
    });

    $list.on('click', '.j-pause', function(e){
        e.preventDefault();
        var $el = $(this);
        bff.ajax(ajaxUrl + 'pause', {id:$el.data('id')}, function(data){
            if (data && data.success) {
                bff.success('<?= _t('', 'Операция выполнена успешно') ?>');
                refreshList();
            }
        }, $progress);
    });

    $list.on('click', '.j-continue', function(e){
        e.preventDefault();
        var $el = $(this);
        bff.ajax(ajaxUrl + 'continue', {id:$el.data('id')}, function(data){
            if (data && data.success) {
                bff.success('<?= _t('', 'Операция выполнена успешно') ?>');
                refreshList();
            }
        }, $progress);
    });


    var timeout = false;
    function refreshList()
    {
        clearTimeout(timeout);
        bff.ajax('', {}, function(data){
            if (data && data.success) {
                if (data.list) {
                    $list.html(data.list);
                }
                timeout = setTimeout(function(){
                    refreshList();
                }, 30000);
            }
        }, $progress);

    }
    refreshList();

});
</script>