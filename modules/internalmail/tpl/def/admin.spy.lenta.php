<?php

    tpl::includeJS(array('autocomplete'), true);
?>

<div class="actionBar">
    <input type="hidden" name="u" value="<?= $user['id'] ?>" id="j-my-spy-lenta-user-id" />
    <span class="relative">
        <input type="text" id="j-my-spy-lenta-user-email" class="autocomplete input-large" placeholder="<?= _te('internalmail', 'Введите e-mail пользователя'); ?>" value="<?= HTML::escape($user['email']) ?>" />
        <a href="#" id="j-my-spy-lenta-user-cancel" class="<?= ( ! $user['id'] ? 'hide' : '') ?>" style="position: absolute; top:-4px; right:-17px;"><i class="icon-remove disabled"></i></a>
    </span>
    <a href="#" id="j-my-spy-lenta-user-block-all-btn" class="btn btn-small right <?= ( ! $user['id'] ? 'hide' : '') ?>"><?= _t('internalmail', 'заблокировать все сообщения пользователя') ?></a>
</div>

<table class="table table-hover table-condensed table-striped admtbl tblhover">
<thead>
    <tr class="header">
        <th class="left" width="160"><?= _t('internalmail', 'Отправитель'); ?></th>
        <th width="70"><?= _t('internalmail', 'Кому'); ?></th>
        <th class="left"><?= _t('internalmail', 'Сообщение'); ?></th>
        <th width="135"><?= _t('', 'Дата'); ?></th>
        <th class="right"></th>
    </tr>
</thead>
<tbody id="j-im-spy-lenta-list">
    <?= $list ?>
</tbody>
</table>

<form action="<?= $this->adminLink(null) ?>" method="get" name="filters" id="j-im-spy-lenta-filter">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="u" value="<?= $user['id'] ?>" class="j-user" />
    <input type="hidden" name="page" value="1" class="j-page-value" />
    <div class="j-pages"><?= $pgn ?></div>
</form>

<script type="text/javascript">
//<![CDATA[
    $(function()
    {
        var $list = $('#j-im-spy-lenta-list');
        var $filter = $('#j-im-spy-lenta-filter');
        var pageID = 1;
        $filter.on('click', '.j-page', function(e){ nothing(e);
            $filter.find('.j-page-value').val($(this).data('page'));
            updateList();
        });
        function updateList()
        {
            var filterQuery = $filter.attr('action')+'?'+$filter.serialize();
            bff.ajax(filterQuery, {}, function(data){
                if(data){
                    $list.html(data.list);
                    $filter.find('.j-pages').html(data.pgn);
                    if (bff.h) {
                        window.history.pushState({}, document.title, filterQuery);
                    }
                }
            }, function(){
                $list.toggleClass('disabled');
            });
        }

        // message block
        $list.on('click', 'a.im-spy-block', function(){
            var id = intval($(this).data('id'));
            if (id > 0) {
                var params = {link:this};
                bff.ajaxToggle(id, '<?= $this->adminLink('ajax&act=toggle-blocked&id='); ?>'+id, params);
            }
            return false;
        });

        // user filter
        var user_id = intval(<?= $user['id'] ?>);
        var $userCancel = $('#j-my-spy-lenta-user-cancel');
        var $userBlockAll = $('#j-my-spy-lenta-user-block-all-btn');
        $('#j-my-spy-lenta-user-email').autocomplete('<?= $this->adminLink('ajax&act=recipients'); ?>',
        {valueInput: '#j-my-spy-lenta-user-id', cancel: $userCancel, width: false, onSelect: function(id){
            $filter.find('.j-user').val(id); user_id = id;
            $filter.find('.j-page-value').val(1);
            $userBlockAll.toggleClass('hide', id<1);
            updateList();
        }});
        $userCancel.on('click', function(){
            if (user_id > 0) {
                $filter.find('.j-user').val(0);
                $filter.find('.j-page-value').val(1);
                updateList();
            }
        });
        $userBlockAll.on('click', function(e){ nothing(e);
            if (user_id > 0 && bff.confirm('sure')) {
                bff.ajax('<?= $this->adminLink('ajax&act=block-all'); ?>', {u:user_id}, function(r){
                    if (r && r.success) {
                        bff.success(r.message);
                        updateList();
                    }
                });
            }
        });
    });
//]]>
</script>