<?php
    /**
     * @var $this BBS
     */
?>
<?= tplAdmin::blockStart(_t('bbs', 'Объявления / Категории / Пакетные настройки'), 'icon-th-list'); ?>
<form action="" method="post" id="j-categories-packetActions-form">
    <table class="admtbl tbledit">
        <tr>
            <td colspan="2">
                <div class="well well-small">
                    <?= _t('bbs', 'Отметьте одну или несколько из доступных настроек.<br/>
                    При сохранении выбранные настройки будут изменены во <strong>всех категориях</strong> объявлений.'); ?>
                </div>
                <br />
            </td>
        </tr>
        <tr class="j-action">
            <td class="row1" style="width:200px;">
                <label class="checkbox"><input type="checkbox" name="actions[currency_default]" class="j-action-toggler" /><?= _t('bbs', 'Валюта по-умолчанию:'); ?></label>
            </td>
            <td class="row2">
                <select name="currency_default" style="width:70px;">
                    <?= Site::currencyOptions( Site::currencyDefault('id') ); ?>
                </select>
            </td>
        </tr>
        <tr class="j-action">
            <td class="row1">
                <label class="checkbox"><input type="checkbox" name="actions[photos_max]" class="j-action-toggler" /><?= _t('', 'Фотографии:'); ?></label>
            </td>
            <td class="row2">
                <label><input class="input-mini" type="number" min="<?= BBS::itemsImagesLimit(false) ?>" max="<?= BBS::itemsImagesLimit() ?>" maxlength="2" name="photos_max" value="<?= BBS::itemsImagesLimit(false) ?>" /><span class="help-inline"> &mdash; <?= _t('bbs', 'максимально доступное кол-во фотографий в объявлении'); ?> (<?= BBS::itemsImagesLimit(false) ?> - <?= BBS::itemsImagesLimit() ?>)</span></label>
            </td>
        </tr>
        <tr class="j-action">
            <td class="row1">
                <label class="checkbox"><input type="checkbox" name="actions[list_type]" class="j-action-toggler" /><?= _t('bbs', 'Вид списка по-умолчанию:'); ?></label>
            </td>
            <td class="row2">
                <select name="list_type" style="width:100px;">
                    <option value="<?= BBS::LIST_TYPE_LIST ?>"><?= _t('bbs', 'Список'); ?></option>
                    <option value="<?= BBS::LIST_TYPE_GALLERY ?>"><?= _t('bbs', 'Галерея'); ?></option>
                    <option value="<?= BBS::LIST_TYPE_MAP ?>"><?= _t('bbs', 'Карта'); ?></option>
                </select>
            </td>
        </tr>
        <tr class="j-action">
            <td class="row1">
                <label class="checkbox"><input type="checkbox" name="actions[landingpages_auto]" class="j-action-toggler" /><?= _t('bbs', 'Избавление от /search/:'); ?></label>
            </td>
            <td class="row2">
                <?= _t('bbs', 'Для всех категорий будут созданы посадочные страницы с URL без <strong>/search/</strong>, а также редирект для существующих ссылок на посадочные.'); ?>
            </td>
        </tr>
        <? bff::hook('bbs.admin.category.packetActions.form') ?>
        <tr class="footer">
            <td colspan="2">
                <hr />
                <input type="button" class="btn btn-success button submit j-submit" value="<?= _te('', 'Save') ?>" />
                <a href="<?= $this->adminLink('categories_listing'); ?>" class="btn"><?= _t('', 'Cancel') ?></a>
            </td>
        </tr>
    </table>
</form>
<script type="text/javascript">
<?php js::start(); ?>
    $(function(){
        var $form = $('#j-categories-packetActions-form');

        $form.on('click', '.j-submit', function(){
            if ( ! $form.find('.j-action-toggler:checked').length) {
                bff.error('<?= _t('bbs', 'Отметьте как минимум одну из доступных настроек'); ?>');
                return;
            }
            if ( ! bff.confirm('sure') ) return;
            var $btn = $(this), btnTitle = $btn.prop('disabled',true).val();
            bff.ajax('<?= $this->adminLink(bff::$event) ?>', $form.serialize(), function(data, errors){
                if(data && data.success) {
                    bff.success('<?= _t('bbs', 'Обновление прошло успешно, затронуто категорий:'); ?> <strong>'+data.updated+'</strong>');
                } else {
                    bff.error(errors);
                }
            }, function(p){
                $btn.val( p ? '<?= _t('', 'Подождите...'); ?>' : btnTitle ).prop('disabled', p);
            });
        });
    });
<?php js::stop(); ?>
</script>