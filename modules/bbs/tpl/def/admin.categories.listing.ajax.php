<?php

$urlCatAdd = $this->adminLink('categories_add&pid=');
$urlCatEdit = $this->adminLink('categories_edit&id=');
$urlItemsListing = $this->adminLink('listing&status=0&cat=');

if (!Request::isAJAX()) {
    ?>
    <tr id="dnd-<?= BBS::CATS_ROOTID ?>" data-numlevel="0" data-pid="0">
        <td style="padding-left:5px;" class="left"><span class="desc"><?= _t('bbs', 'Базовая категория'); ?></span></td>
        <td></td>
        <td></td>
        <td></td>
        <td>
            <a href="javascript:void(0);" class="but"></a>
            <a class="but sett" onclick="return bbsCatAct(<?= BBS::CATS_ROOTID ?>, 'dyn');" href="#" title="<?= _te('bbs', 'Дин. свойства'); ?>"></a>
            <a href="javascript:void(0);" class="but"></a>
            <a href="javascript:void(0);" class="but"></a>
            <a href="javascript:void(0);" class="but"></a>
        </td>
    </tr>
    <?
}

foreach($cats as $v)
{
    $id = $v['id']; $isNode = $v['node']; $isVirtual = !empty($v['virtual_ptr']);
?>
<tr id="dnd-<?= $id ?>" data-numlevel="<?= $v['numlevel'] ?>" data-pid="<?= $v['pid'] ?>">
    <td style="padding-left:<?= ($v['numlevel']*15-10) ?>px;" class="left">
        <a onclick="return bbsCatAct(<?= $id ?>,'<?= ($isVirtual ? 'edit' : 'c') ?>');" class="but folder<? if(!$isNode) { ?>_ua<? } ?> but-text"><?= $v['title'] ?></a>
        <? if($isVirtual): ?>
            <a class="chain but" onclick="return bbsCatAct(<?= $v['virtual_ptr'] ?>,'edit');" title="<?= HTML::escape($v['virtual_name']) ?>"></a>
        <? endif ?>
    </td>
    <td><a href="<?= $urlItemsListing.$id ?>"><?= $v['items'] ?></a></td>
    <td><? if( $v['addr'] ) { ?> <i class="icon-ok disabled"></i><? } ?></td>
    <td><? if( $v['price'] ) { ?> <i class="icon-ok disabled"></i><? } ?></td>
    <td><a class="but <? if( $v['enabled']) { ?>un<? } ?>block" href="#" onclick="return bbsCatAct(<?= $id ?>, 'toggle', this);" title="<?= _te('', 'Вкл/выкл'); ?>"></a>
        <a class="but sett" onclick="return bbsCatAct(<?= ($isVirtual ? $v['virtual_ptr'] : $id) ?>, 'dyn');" href="#" title="<?= _te('bbs', 'Дин. свойства'); ?>"></a>
        <a class="but edit" href="<?= $urlCatEdit.$id ?>" title="<?= _te('','Edit') ?>"></a>
        <? if($v['numlevel'] >= $deep) : ?>
            <a href="javascript:void(0);" class="but"></a>
        <? elseif($isVirtual): ?>
            <a href="javascript:void(0);" class="but add disabled" title="<?= _te('bbs', 'Виртуальная категория не может содержать подкатегорий') ?>"></a>
        <? else: ?>
            <a class="but add" href="<?= $urlCatAdd.$id ?>" title="<?= _te('','Add') ?>"></a>
        <? endif ?>
        <? if($isNode && ! FORDEV) { ?><a class="but" href="#"></a><? } else { ?>
            <a class="but del" href="#" onclick="return bbsCatAct(<?= $id ?>, 'del', this);" title="<?= _te('', 'Delete') ?>"></a>
        <? } ?>
    </td>
</tr>
<? }