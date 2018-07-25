<?php
/**
 * @var $this Plugin
 */
    $aData['submitButtons'] = '';
?>

<?php if ($this->isEnabled()) { ?>
<div id="j-settings-testform">
    <textarea class="stretch" type="text" name="input_data" style="height: 100px;" placeholder="<?= $this->langAdmin('Введите текст для проверки', array(), true); ?>"></textarea>

    <div class="j-out well well-small hidden" style="margin-top: 5px;"></div>

    <div style="margin-top: 5px;">
        <input type="button" class="j-submit-testform btn btn-small btn-success button" value="<?= $this->langAdmin('Протестировать', array(), true); ?>" />
        <input type="button" class="btn btn-small button cancel j-cancel" value="<?= _te('','Cancel'); ?>" />
    </div>
</div>

<script type="text/javascript">
    $(function(){
        var $block = $('#j-settings-testform');
        var ajaxUrl = '<?= $this->adminLink('testData', $this->getName()) ?>';
        $block.on('click', '.j-submit-testform', function(e){
            var $el = $(this);
            bff.ajax(ajaxUrl, {input_data:$block.find('[name="input_data"]').val()}, function(data){
                if (data && data.success) {
                     $block.find('.j-out').html(data.output_data).show();
                }
            });
        });
    });
</script>
<?php } else { ?>
    <div class="alert alert-info"><?= $this->langAdmin('Включите плагин для возможности его тестирования'); ?></div>
<?php } ?>