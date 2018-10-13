<?php
/**
 * Форма настроек доступных способ оплаты
 * @var $inputName string
 */
 tpl::includeJS('ui.sortable', true);

 # Список валют
 $currencies = Site::model()->currencyData(false, false, false);
 $currencyDefault = Site::currencyDefault(false);

 # То, что нужно перенести в форму:
 $listCurrent = Bills::getPaySystems();

 # Список систем оплаты
/**
 * int 'id' - уникальный ID
 * string 'title' - название
 * string 'key' - уникальный ключ
 * string 'desc' - описание
 * array 'ways' - способы оплаты предоставляемые системой
 *    [
 *       'key' - уникальный ключ способа оплаты
 *       'title' - название способа оплаты
 *       'currency' - ID валюты
 *    ], []
 * int 'currency' - ID валюты по умолчанию (в которой выполняется оплата в системе)
 */
 $list = $this->getPaySystemData(true);
 if (isset($list[0])) { unset($list[0]); }
 foreach ($list as $k=>&$v) {
    if ( ! $k) { unset($list[$k]); continue; }
    if ( ! isset($v['currency'])) {
        $v['currency'] = $currencyDefault['id'];
    }
 } unset($v);
 $index = 1;
 foreach ($listCurrent as $k=>&$v) {
    $v['i'] = $index;
    $v['is_new'] = false;
    $v['title'] = HTML::escape($v['title']);
    $v['title_ps'] = ( ! empty($list[$v['id']]['title']) ?
                               $list[$v['id']]['title'] :
                               '?');
    if (empty($v['currency_id'])) {
        $v['currency_id'] = $currencyDefault['id'];
    }
    $v['currency_title'] = ( ! empty($currencies[$v['currency_id']]['title']) ?
         $currencies[$v['currency_id']]['title'] :
         $currencyDefault['title']
    );
    $v['enabled'] = (!isset($v['enabled']) || !empty($v['enabled']) ? 1 : 0);
    $index++;
 } unset($v);

 $inputName = function($index, $key) use ($inputNamePrefix) {
    return $inputNamePrefix.'['.$index.']['.$key.']';
 };

 //echo '<pre>', print_r($list, true), '</pre>'; return;
?>
<style type="text/css">
    #j-bills-paysystems-user-list .j-item, #j-bills-paysystems-user-form {
        min-height:73px; cursor:move; margin: 0 5px 5px 0; padding:10px; background-color:white; border:1px solid #d6d6d6; border-radius:4px;
    }
    #j-bills-paysystems-user-form {
        cursor: default;
    }
    #j-bills-paysystems-user-form .j-input-file input {
        width: 0.1px;
        height: 0.1px;
        opacity: 0;
        overflow: hidden;
        position: absolute;
        z-index: -1;
    }
    #j-bills-paysystems-user-list .j-item:hover {
        background-color: #f9f9f9;
    }
    #j-bills-paysystems-user-list .j-item-disabled {
        color: #999898;
        filter: grayscale(100%); -webkit-filter: grayscale(100%);
        opacity: 0.8;
    }
</style>
<script type="text/html" id="j-bills-paysystems-user-template">
<div class="j-item<% if(!enabled) { %> j-item-disabled<% } %>" data-index="<%= i %>">
    <input type="hidden" name="<?= $inputName('<%= i %>', 'id') ?>" value="<%= id %>" />
    <input type="hidden" name="<?= $inputName('<%= i %>', 'currency_id') ?>" value="<%= currency_id %>" />
    <input type="hidden" name="<?= $inputName('<%= i %>', 'new') ?>" value="<%= (is_new ? 1 : 0) %>" />
    <input type="hidden" class="j-deleted" name="<?= $inputName('<%= i %>', 'deleted') ?>" value="0" />
    <input type="hidden" class="j-enabled" name="<?= $inputName('<%= i %>', 'enabled') ?>" value="<%= enabled %>" />
    <div class="left" style="width: 120px;">
        <img class="j-preview" src="<%= logo_phone %>" alt="" style="max-height: 70px; max-width: 100px;" />
    </div>
    <div class="left">
        <table class="admtbl">
            <thead>
                <tr>
                    <td class="desc" width="105"><?= _t('', 'Название'); ?></td>
                    <td class="bold" style="cursor:default;">
                        <div class="j-item-title-view" style="cursor: text;"><%= title %></div>
                        <div class="j-item-title-edit" style="display:none;">
                            <input type="text" name="<?= $inputName('<%= i %>', 'title') ?>" class="input-xlarge" value="<%= title %>" />
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="desc"><?= _t('bills', 'Система оплаты'); ?></td>
                    <td><%= title_ps %></td>
                </tr>
                <tr>
                    <td class="desc"><?= _t('bills', 'Валюта'); ?></td>
                    <td><%= currency_title %></td>
                </tr>
            </thead>
        </table>
    </div>
    <div class="right">
        <a href="javascript:void(0);" class="j-item-toggle but dev-ext-testmode-but dev-ext-testmode-but_<%= (enabled ? 'on' : 'off') %>"></a>
        <a href="javascript:void(0);" class="j-item-delete but cross" data-new="<%= (is_new ? 1 : 0) %>"></a>
    </div>
    <div class="clear-all"></div>
</div>
</script>
<div id="j-bills-paysystems-user-list"></div>
<div id="j-bills-paysystems-user-form">
    <div class="left" style="width: 120px;">
        <label class="j-input-file relative">
            <img class="j-form-preview" style="max-height: 100px; max-width: 100px; border-radius: 4px;" src="data:image/png;base64,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gCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQjgCBu6Gk4AgEEiZAlYnQj8D/c3vQ61ne/zwAAAABJRU5ErkJggg==" alt="" />
            <i class="j-icon icon icon-upload disabled" style="position: absolute; top: 65%; left: 37%; display: none;"></i>
            <input class="j-file" type="file" style="display: none;" accept=".jpg, .jpeg, .png, .gif" />
        </label>
    </div>
    <div class="left">
        <table class="admtbl">
            <thead>
                <tr>
                    <td class="desc" width="105"><?= _t('', 'Название'); ?></td>
                    <td><input type="text" class="j-form-title input-xlarge" /></td>
                </tr>
                <tr>
                    <td class="desc"><?= _t('bills', 'Система оплаты'); ?></td>
                    <td><select class="j-form-paysystem"><?= HTML::selectOptions($list, 0, false, 'id', 'title', array('currency')); ?></select></td>
                </tr>
                <tr>
                    <td class="desc"><?= _t('bills', 'Валюта'); ?></td>
                    <td>
                        <select class="j-form-currency"><?= HTML::selectOptions($currencies, 0, false, 'id', 'title'); ?></select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <a href="javascript:void(0);" class="btn btn-mini j-form-submit"><?= _t('', 'добавить'); ?></a>
                    </td>
                </tr>
            </thead>
        </table>
    </div>
    <div class="clear-all"></div>
</div>
<script type="text/javascript">
    $(function(){
        var $form = $('#j-bills-paysystems-user-form'), formIndex = '<?= $index ?>';
        var $list = $('#j-bills-paysystems-user-list');
        var $template = $('#j-bills-paysystems-user-template');

        var listItems = <?= json_encode($listCurrent); ?>;
        for (var i in listItems) {
            $list.append(bff.tmpl($template.html(), listItems[i]));
        }

        $list.on('click', '.j-item-toggle', function(){
            var $item = $(this).closest('.j-item');
            $(this).toggleClass('dev-ext-testmode-but_off dev-ext-testmode-but_on');
            $item.toggleClass('j-item-disabled');
            $item.find('.j-enabled').val($item.hasClass('j-item-disabled') ? 0 : 1);
        }).on('click', '.j-item-delete', function(){
            var $item = $(this).closest('.j-item');
            if ($(this).data('new') == 1) {
                $item.remove();
            } else {
                if (bff.confirm('sure')) {
                    $item.hide().find('.j-deleted').val(1);
                }
            }
        }).on('click', '.j-item-title-view', function(){
            $(this).hide().next().show().find('input').focus();
        }).on('keyup', '.j-item-title-edit input', function(e){
            if (e.keyCode == 13 || e.keyCode == 27) {
                var $edit = $(this);
                $edit.parent().hide().prev().text($edit.val()).show();
            }
        });
        var $sort = $list.sortable({
            axis: 'y', cursor: 'move', scroll: false,
            update: function(event, ui) {
                //
            }
        }); $sort.sortable('refresh');
        $form.find('.j-form-preview').data('src', $form.find('.j-form-preview').attr('src'));
        $form.on('change', '.j-form-paysystem', function(){
            var $curr = $(this).find('option:selected');
            $form.find('.j-form-currency').val($curr.data('currency'));
        }).on('change', '.j-input-file input', function(){
            var input = this;
            var $preview = $form.find('.j-form-preview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $preview.attr('src', e.target.result).data('selected', 1);
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                $preview.attr('src', $preview.data('src')).data('selected', 0);
            }
        }).on('mouseenter mouseleave', '.j-input-file', function(e){
            if ( ! $form.find('.j-form-preview').data('selected')) {
                $(this).find('.j-icon').toggle(e.type === 'mouseenter');
            }
        }).on('click', '.j-form-submit', function(){
            var data = {i:formIndex, id:0, enabled: 1,
                currency_id:<?= $currencyDefault['id'] ?>,
                currency_title:'<?= HTML::escape($currencyDefault['title'],'js') ?>',
                title: '?',
                title_ps: '?',
                is_new: true
            };
            data.title = $form.find('.j-form-title').val();
            if ( ! data.title.length) { $form.find('.j-form-title').focus(); return; }
            data.id = $form.find('.j-form-paysystem').val();
            data.title_ps = $form.find('.j-form-paysystem').find('option:selected').text();
            data.currency_id = $form.find('.j-form-currency').val();
            data.currency_title = $form.find('.j-form-currency').find('option:selected').text();
            data.logo_phone = $form.find('.j-form-preview').attr('src');
            $list.append(bff.tmpl($template.html(), data));
            var $item = $list.find('.j-item[data-index="'+formIndex+'"]');
            if ($form.find('.j-form-preview').data('selected') === 1) {
                $form.find('.j-input-file input').clone().appendTo($item.find('.j-preview').parent());
                $item.find('.j-file').attr('name', '<?= $inputName('{i}', 'file') ?>'.replace('{i}', formIndex));
            }
            formIndex++;
        });
    });
</script>