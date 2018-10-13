<?php   
    $urlEdit = $this->adminLink('edit&id=');
?>
<? foreach($list as $k=>$v) { $id = $v['id']; ?>
<tr class="row<?= $k%2 ?>">
    <td><? if($f['status'] == BBS::PRESS_STATUS_PAYED): ?><label class="checkbox inline"><input type="checkbox" name="i[]" value="<?= $id ?>" class="check j-item-check" /></label><? endif; ?></td>
    <td><a href="javascript:void(0);" onclick="return bff.iteminfo(<?= $id ?>);"><?= $id ?></a></td>
    <td class="left">
        <a class="linkout but" href="<?= BBS::urlDynamic($v['link'], array('from'=>'adm')) ?>" target="_blank"></a><?= $v['title'] ?>
        <br /><span class="desc"><?= $v['cat_title'] ?></span>
    </td>
    <td><? if(BBS::PRESS_ON && $v['svc_press_status'] == BBS::PRESS_STATUS_PAYED): ?>
            <span class="desc"><?= _t('bbs', 'ожидает'); ?></span>
        <? else: ?>
            <?= ($f['status'] == BBS::PRESS_STATUS_PUBLICATED_EARLIER ? tpl::date_format2($v['svc_press_date_last']) : tpl::date_format2($v['svc_press_date'])) ?>
        <? endif; ?>
    </td>
    <td>
        <a class="but images<? if( ! $v['imgcnt']){ ?> disabled<? } ?>" href="<?= $urlEdit.$id.'&tab=images' ?>" title="фото: <?= $v['imgcnt'] ?>"></a>
        <a class="but edit" href="<?= $urlEdit.$id ?>"></a>
    </td>
</tr>
<? } if(empty($list)) { ?>
<tr class="norecords">
    <td colspan="5"><?= _t('', 'Nothing found') ?></td>
</tr>
<? } ?>