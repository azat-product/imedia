<?php

$urlSpyConv = $this->adminLink('spy_conv&u=');
foreach($list as $v)
{
    $from_userinfo = 'return bff.userinfo('.$v['from_id'].');';
    $to_userinfo = 'return bff.userinfo('.$v['to_id'].');';
?>
<tr>
    <td class="row1 left">
        <a href="#" onclick="<?= $from_userinfo ?>" style="margin-right: 2px;"><img src="<?= $v['from_avatar'] ?>" class="img-circle" width="25" alt="" /></a>
        <a href="#" class="ajax" onclick="<?= $from_userinfo ?>"><?= ( ! empty($v['from_name']) ? $v['from_name'] : $v['from_login'] ) ?></a>
    </td>
    <td>
        <a href="#" onclick="<?= $to_userinfo ?>" title="<?= HTML::escape($v['to_login']) ?>"><img src="<?= $v['to_avatar'] ?>" class="img-circle" width="25" alt="" /></a>
    </td>
    <td class="left">
        <div style="max-width: 430px; word-wrap: break-word;"><a href="<?= $urlSpyConv.$v['from_id'].'&shop='.$v['shop_id'].'&i='.$v['to_id'] ?>"<? if(!$v['is_new']){ ?> class="desc"<? } ?>><?= tpl::truncate(strip_tags($v['message']), 100); ?></a></div>
    </td>
    <td>
        <span class="small"><?= tpl::date_format2($v['created'], true); ?></span>
    </td>
    <td>
        <a class="but <? if( ! $v['blocked'] ) { ?>un<? } ?>block im-spy-block" title="<?= _te('internalmail', 'Блокировка'); ?>" href="#" data-id="<?= $v['id'] ?>"></a>
    </td>
</tr>
<? } if( empty($list) ) { ?>
<tr class="norecords">
    <td colspan="5"><?= _t('internalmail', 'сообщений не найдено'); ?></td>
</tr>
<? } ?>