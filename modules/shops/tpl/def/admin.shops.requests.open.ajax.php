<?php
    $urlEdit = $this->adminLink('edit&id=');
?>
<? foreach($list as $k=>$v) { ?>
<tr class="row<?= $k%2 ?><? if($v['status'] == Shops::STATUS_BLOCKED) { ?> text-error<? } ?>">
    <td><?= $v['id'] ?></td>
    <td class="left">
        <a class="linkout but" href="<?= Shops::urlDynamic($v['link']) ?>" target="_blank"></a><a href="javascript:void(0);" onclick="return bff.shopInfo(<?= $v['id'] ?>);" class="nolink"><?= $v['title'] ?></a>
    </td>
    <td><?= tpl::date_format2($v['created'], true, true); ?></td>
    <td>
        <a class="but edit" href="<?= $urlEdit.$v['id'] ?>"></a>
        <a class="but del j-act-del" href="javascript:void(0);" data-id="<?= $v['id'] ?>"></a>
    </td>
</tr>
<? } if(empty($list)) { ?>
<tr class="norecords">
    <td colspan="4"><?= _t('', 'Nothing found') ?></td>
</tr>
<? } ?>
