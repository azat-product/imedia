<?php
?>
<div id="j-setka-block">
    <input type="hidden" name="<?= $fieldname ?>[setka]" value="<?= $content['setka'] ?>" />
    <textarea  name="<?= $fieldname ?>[content]" style="display: none;"><?= HTML::escape($content['content']) ?></textarea>
    <input type="hidden" name="<?= $fieldname ?>[theme]" value="<?= $content['theme'] ?>" />
    <input type="hidden" name="<?= $fieldname ?>[layout]" value="<?= $content['layout'] ?>" />

    <div style="padding: 9px; border: 1px solid #e3e3e3; border-radius: 4px; text-align: center;">
        <div style="position: relative;">
            <div style="position: absolute;left:0;right: 0;top: 70%; bottom:0;background:linear-gradient(transparent, white);"> </div>
            <div id="j-setka-content" style="max-height: 500px; overflow: hidden;"><?= $content['content'] ?></div>
        </div>
        <a href="#j-setka-modal" role="button" class="btn btn-info" data-toggle="modal" style="margin: 10px 0;">Редактор текста Setka</a>
    </div>

    <div class="modal hide fade" id="j-setka-modal" tabindex="-1" style="width: 96%; margin-left: 0; left: 2%;top:0; height: 100%;">
        <div class="modal-header" style="padding: 0 15px;">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h5>Редактор текста Setka</h5>
        </div>
        <div class="modal-body" style="max-height: 99%">
            <div class="stk-editor" id="setka-editor"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start() ?>
var <?= $js_object ?>;
$(function(){
    var images = false;
    <? if ( ! empty($images)): ?>images = <?= func::php2js($images) ?>;<? endif; ?>
    var $block = $('#j-setka-block');
    var $modal = $('#j-setka-modal');
    var $content = $('#j-setka-content');
    $modal.on('shown', function () {
        $content.html('');
        fetch('<?= $json_url ?>').then(response => response.json()).then(response => {
            const config = response.config;
            const assets = response.assets;
            config.public_token = '<?= $build['token'] ?>';
            config.token = '<?= $build['token'] ?>';
            config.restApiUrl = '<?= $api_url ?>';

            var theme = $block.find('[name="<?= $fieldname ?>[theme]"]').val();
            var layout = $block.find('[name="<?= $fieldname ?>[layout]"]').val();
            if (theme && layout) {
                config.theme = theme;
                config.layout = layout;
            }
            if (images) {
                assets.images = images;
            }

            SetkaEditor.start(config, assets);

            var content = $block.find('[name="<?= $fieldname ?>[content]"]').val();
            if (content && content.length) {
                SetkaEditor.replaceHTML(content);
            }
        }).catch(ex => alert(ex));
    });

    $modal.on('hide', function () {
        var content = SetkaEditor.getHTML({ includeContainer: true });
        $block.find('[name="<?= $fieldname ?>[content]"]').val(content);
        var theme = SetkaEditor.getCurrentTheme();
        $block.find('[name="<?= $fieldname ?>[theme]"]').val(theme.id);
        var layout = SetkaEditor.getCurrentLayout();
        $block.find('[name="<?= $fieldname ?>[layout]"]').val(layout.id);
        $content.html(content);
        SetkaEditor.stop();
        $modal.find('.modal-body').html('<div class="stk-editor" id="setka-editor"></div>');
        bff.ajax('<?= $api_url ?>/images/all', {}, function (data) {
            if (data && data.success) {
                images = data.images;
            }
        });
    });

    <?= $js_object ?> = function () {
        return {
            savePrepare:function () { },
            ajaxSave:function () { }
        }
    }();
});
<? js::stop() ?>
</script>
