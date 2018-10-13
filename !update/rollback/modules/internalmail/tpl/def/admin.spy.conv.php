<?php

?>

<?= tplAdmin::blockStart(_t('internalmail', 'Сообщения / [a]Cообщения пользователя</a> / Переписка', array('a' => '<a href="'.$list_url.'">')), true); ?>

<table class="admtbl tbledit">
<tr class="row1">
    <td style="width:65px;">
        <a href="#" onclick="return bff.userinfo(<?= $u['id'] ?>);">
            <img id="im_avatar" class="img-polaroid" src="<?= $u['avatar'] ?>" alt="" width="50" />
        </a>
    </td>
    <td style="padding-bottom: 20px;">
        <a href="#" onclick="return bff.userinfo(<?= $u['id'] ?>);" class="ajax"><?= ( !empty($u['name']) ? $u['name'] : $u['login'] ) ?></a>
        <? if( ! $u['activated'] ) { ?>&nbsp;<span class="disabled">[<?= _t('', 'неактивирован'); ?>]</span><? } ?>
    </td>
    <td class="right">
        <a href="#" onclick="return bff.userinfo(<?= $i['id'] ?>);" class="ajax"><?= ( !empty($i['name']) ? $i['name'] : $i['login'] ) ?></a>
        <? if( ! $i['activated'] ) { ?>&nbsp;<span class="disabled">[<?= _t('', 'неактивирован'); ?>]</span><? } ?>
        <br />
        <span class="label"><?= tpl::declension($total, _t('internalmail', 'сообщение;сообщения;сообщений')) ?></span>
        <br />
    </td>
    <td style="width:65px;" class="right">
        <a href="#" onclick="return bff.userinfo(<?= $i['id'] ?>);">
            <img id="im_avatar" class="img-polaroid" src="<?= $i['avatar'] ?>" alt="" width="50" />
        </a>
    </td>
</tr>
</table>

<hr class="cut" />

<table class="admtbl tbledit im-conv-list" id="j-im-conv-list">
    <?= $list ?>
</table>

<form action="<?= $this->adminLink(null) ?>" method="get" name="filters" id="j-im-conv-pgn">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="u" value="<?= $u['id'] ?>" />
    <input type="hidden" name="i" value="<?= $i['id'] ?>" />
    <input type="hidden" name="page" value="1" class="j-page-value" />
    <div class="j-pages"><?= $pgn ?></div>
</form>

<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
//<![CDATA[
    $(function()
    {
        var $list = $('#j-im-conv-list');
        var $pgn = $('#j-im-conv-pgn');
        $pgn.on('click', '.j-page', function(e){ nothing(e);
            $pgn.find('.j-page-value').val( $(this).data('page') );
            bff.ajax(document.location, $pgn.serialize(), function(data){
                if(data) {
                    $list.html(data.list);
                    $pgn.find('.j-pages').html(data.pgn);
                }
            }, function(){
                $list.toggleClass('disabled');
            });
        });
        $list.on('click','.j-message-block', function(){
            var id = intval($(this).data('id'));
            var link = $(this);
            if (id > 0) {
                bff.ajax('<?= $this->adminLink('ajax&act=toggle-blocked'); ?>', {id:id}, function(){
                    link.text((link.hasClass('j-is-blocked') ? '<?= _t('', 'заблокировать'); ?>' : '<?= _t('', 'разблокировать'); ?>'));
                    link.toggleClass('j-is-blocked');
                    link.closest('td').toggleClass('disabled');
                });
            }
            return false;
        });
    });
//]]>
</script>