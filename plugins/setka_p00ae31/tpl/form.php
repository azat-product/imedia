<?php
?>
<input type="hidden" name="<?= $fieldname ?>[setka]" value="<?= $content['setka'] ?>" />
<textarea  name="<?= $fieldname ?>[content]" style="display: none;"><?= func::php2js($content['content']) ?></textarea>
<input type="hidden" name="<?= $fieldname ?>[theme]" value="<?= $content['theme'] ?>" />
<input type="hidden" name="<?= $fieldname ?>[layout]" value="<?= $content['layout'] ?>" />
<div class="stk-editor" id="setka-editor"></div>
<script type="text/javascript">
<? js::start() ?>
var <?= $js_object ?>;
$(function(){
    fetch('<?= $json_url ?>').then(response => response.json()).then(response => {
        const config = response.config;
        const assets = response.assets;
        config.public_token = '<?= $build['token'] ?>';
        config.token = '<?= $build['token'] ?>';

        config.restApiUrl = '<?= $api_url ?>';

        <? if ( ! empty($content['theme']) && ! empty($content['layout'])): ?>
        config.theme = '<?= $content['theme'] ?>';
        config.layout = '<?= $content['layout'] ?>';
        <? endif; ?>
        <? if ( ! empty($images)): ?>
        const postImages = <?= func::php2js($images) ?>;
        assets.images = postImages;
        <? endif; ?>

        SetkaEditor.start(config, assets);

        <? if ( ! empty($content['content'])): ?>SetkaEditor.replaceHTML(<?= func::php2js($content['content']) ?>);<? endif; ?>
    }).catch(ex => alert(ex));

    <?= $js_object ?> = function () {
        var $form, isSaved = false;

        $(function(){
            var $setka = $('#setka-editor');
            if ( ! $setka.length) return;

            $setka.closest('.p-portfolioSection').addClass('full-width-editor');

            $form = $setka.closest('form:first');
            if ( ! $form.length) {
                $form = $setka.parent();
            }

            $form.submit(function () {
                savePrepare();
            });

            $form.on('form.hide', function (e) {
                SetkaEditor.stop();
            });
        });

        function savePrepare()
        {
            isSaved = true;
            var content = SetkaEditor.getHTML({ includeContainer: true });
            $form.find('[name="<?= $fieldname ?>[content]"]').val(content);
            var theme = SetkaEditor.getCurrentTheme();
            $form.find('[name="<?= $fieldname ?>[theme]"]').val(theme.id);
            var layout = SetkaEditor.getCurrentLayout();
            $form.find('[name="<?= $fieldname ?>[layout]"]').val(layout.id);
        }

        return {
            savePrepare:savePrepare,
            ajaxSave:function () { }
        }
    }();
});
<? js::stop() ?>
</script>
