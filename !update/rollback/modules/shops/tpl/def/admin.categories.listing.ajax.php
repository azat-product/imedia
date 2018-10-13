<?php

$urlCatAdd = $this->adminLink('categories_add&pid=');
$urlCatEdit = $this->adminLink('categories_edit&id=');
$urlShopsListing = $this->adminLink('listing&cat=');

foreach($cats as $v)
{
    $id = $v['id']; $isNode = $v['node'];
?>
<tr id="dnd-<?= $id ?>" data-numlevel="<?= $v['numlevel'] ?>" data-pid="<?= $v['pid'] ?>">
    <td style="padding-left:<?= ($v['numlevel']*15-10) ?>px;" class="left">
        <a onclick="return shopsCatAct(<?= $id ?>,'c');" class="but folder<? if( ! $isNode) { ?>_ua<? } ?> but-text"><?= $v['title'] ?></a>
    </td>
    <td><a href="<?= $urlShopsListing.$v['id'] ?>"><?= $v['shops'] ?></a></td>
    <td class="left"><a class="but <? if( $v['enabled']) { ?>un<? } ?>block" href="#" onclick="return shopsCatAct(<?= $id ?>, 'toggle', this);" title="<?= _te('', 'Вкл/выкл'); ?>"></a>
        <a class="but edit" href="<?= $urlCatEdit.$id ?>" title="<?= _te('','Edit') ?>"></a>
        <? if($v['numlevel'] >= $deep) { ?><? }
           else if (!$isNode && $v['shops']) { ?><? } else { ?>
            <a class="but add" href="<?= $urlCatAdd.$id ?>" title="<?= _te('','Add') ?>"></a>
        <? } ?>
        <? if( ! $isNode) { ?>
            <a class="but del" href="#" onclick="return shopsCatAct(<?= $id ?>, 'del', this);" title="<?= _te('', 'Delete') ?>"></a>
        <? } ?>
    </td>
</tr>
<? }