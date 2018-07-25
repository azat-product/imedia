<?php
/**
 * @var $this \Theme_Start_do2_t062d90
 */

?>
<div class="j-tab j-tab-copy-start hidden">
    <div style="margin: 12px 3px;">
        <?= $this->lang('Для копирования темы укажите параметры новой темы:') ?>
    </div>
    <table class="admtbl tbledit">
        <tr>
            <td class="row1 field-title" style="width:100px;"><?= $this->lang('Название') ?>:</td>
            <td class="row2"><input type="text" name="copy_title" size="50" maxlength="250" value="<?= HTML::escape($this->getTitle().' 2') ?>" class="j-copy-title" /></td>
        </tr>
        <tr>
            <td class="row1 field-title" style="width:100px;"><?= $this->lang('Название') ?>:<br /><span class="desc"><?= $this->lang('(латиницей, a-z)') ?></span></td>
            <td class="row2"><input type="text" name="copy_name" size="50" maxlength="50" value="<?= HTML::escape('start') ?>" class="j-copy-name" pattern="[0-9a-z]*" /></td>
        </tr>
        <tr class="footer">
            <td class="row1"></td>
            <td class="row2">
                <input type="button" class="btn btn-success btn-small button j-submit-copy" value="<?= HTML::escape($this->lang('Скопировать')) ?>" />
                <input type="button" class="btn btn-small button cancel j-cancel" value="<?= _te('', 'Cancel') ?>" />
            </td>
        </tr>
    </table>
    <script type="text/javascript">
        $(function(){
            var _block = $('.j-tab-copy-start'), _form = _block.closest('form'), _process = false;
            var _url = _form.data('url') + '&copy_submit=1';
            _block.on('click', '.j-submit-copy', function(){ if (_process) return;
                var _btn = $(this);
                bff.ajax(_url, _form.serialize(), function(r){
                    if (r && r.success) {
                        _block.find('.j-copy-title,.j-copy-name').val('');
                        bff.success('<?= $this->lang('Новая тема была успешно создана', [], 'js') ?>');
                        if (r.hasOwnProperty('redirect')) {
                            bff.redirect(r.redirect, false, 1);
                        }
                    }
                }, function(p){ _process = p; _btn.toggleClass('disabled'); });
            });
        });
    </script>
</div>