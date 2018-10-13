<?php
    /**
     * @var $this InternalMail
     */
    tpl::includeJS(array('autocomplete'), true);
?>

<script type="text/javascript">
//<![CDATA[
$(function(){
    var user_id = intval(<?= $user['id'] ?>);
    var $submit = $('#j-my-spy-listing-user-submit');
    var $filter = $('#im-spy-listing-pgn');
    var $cancel = $('#j-my-spy-listing-user-cancel');
    $('#j-my-spy-listing-user-email').autocomplete('<?= $this->adminLink('ajax&act=recipients'); ?>',
    {valueInput: '#j-my-spy-listing-user-id', cancel: $cancel, width: false, onSelect: function(id){
        $submit.prop({disabled:(id<=0)});
        $filter.find('.j-user').val(id);
        $filter.find('.j-offset').val(0);
        $filter.submit();
    }});
    $cancel.on('click', function(){
        if( user_id > 0 ) {
            $filter.find('.j-user').val(0);
            $filter.find('.j-offset').val(0);
            $filter.submit();
        }
    });
    $submit.on('click', function(){
        $filter.submit();
    });
});

var pgn = new bff.pgn('#im-spy-listing-pgn', {type:'prev-next'});
//]]>
</script>

<div class="actionBar">
    <input type="hidden" name="u" value="<?= $user['id'] ?>" id="j-my-spy-listing-user-id" />
    <span class="relative">
        <input type="text" id="j-my-spy-listing-user-email" class="autocomplete input-large" placeholder="<?= _te('internalmail', 'Введите e-mail пользователя'); ?>" value="<?= HTML::escape($user['email']) ?>" />
        <a href="javascript:void(0);" id="j-my-spy-listing-user-cancel" class="<?= ( ! $user['id'] ? 'hide' : '') ?>" style="position: absolute; top:-4px; right:-17px;"><i class="icon-remove disabled"></i></a>
    </span>
    <input type="button" class="btn btn-small hide" value="<?= _te('internalmail', 'показать сообщения'); ?>" id="j-my-spy-listing-user-submit" />
</div>

<table class="table table-hover table-condensed table-striped admtbl tblhover">
<?
$sConversationURL = $this->adminLink('spy_conv&u='.$user['id'].'&i=');
foreach($contacts as $v)
{
    $userID = $v['user_id'];
?>
<tr>
    <td class="left">
        <a href="<?= $sConversationURL.$userID.'&shop='.$v['shop_id'] ?>"><img src="<?= $v['avatar'] ?>" class="left img-polaroid" style="margin-right: 8px;" width="50" alt="" /></a>
        <div style="margin-bottom: 3px;">
            <a href="javascript:void(0);" class="ajax" onclick="return bff.userinfo(<?= $userID ?>);"><?= ( ! empty($v['name']) ? $v['name'] : $v['login'] ) ?></a>
            <? if( ! $v['activated'] ) { ?>&nbsp;<span class="disabled">[<?= _t('', 'неактивирован'); ?>]</span><? } ?>
        </div>
        <a href="<?= $sConversationURL.$userID.'&shop='.$v['shop_id'] ?>" class="label"><?= tpl::declension($v['msgs_total'], _t('internalmail', 'сообщение;сообщения;сообщений')) ?></a>
    </td>
    <td class="right desc" style="padding-right: 20px; padding-top: 5px; vertical-align: top;">
        <div class="small"><?= tpl::date_format2($v['lastmsg']['created'], true, true, ' ', ', ') ?></div>
    </td>
</tr>
<? } if( empty($contacts) ) { ?>
<tr class="norecords">
    <td><?= _t('internalmail', 'для указанного пользователя ничего не найдено'); ?></td>
</tr>
<? } ?>
</table>

<form action="<?= $this->adminLink(null) ?>" method="get" name="pagenation" id="im-spy-listing-pgn">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="u" value="<?= $user['id'] ?>" class="j-user" />
    <input type="hidden" name="page" value="<?= $page ?>" class="j-offset" />
</form>
<div><?= $pgn ?></div>