<?php
    $urlEdit = $this->adminLink('edit&id=');
?>
<? foreach($list as $k=>$v) { ?>
<tr class="row<?= $k%2 ?><? if($v['status'] == Shops::STATUS_BLOCKED) { ?> text-error<? } else if( $v['status'] == Shops::STATUS_NOT_ACTIVE ) { ?> desc<? } ?>">
    <td><?= $v['id'] ?></td>
    <td class="left">
        <a class="linkout but" href="<?= Shops::urlDynamic($v['link'], array('from'=>'adm')) ?>" target="_blank"><a href="#" onclick="return bff.shopInfo(<?= $v['id'] ?>);" class="nolink"><?= $v['title'] ?></a>
    </td>
    <td>
        <? # для списка "на модерации", указываем причину отправления на модерацию:
           if($f['status'] == 2) {
            if($v['moderated'] == 0) {
                if( $v['status'] == Shops::STATUS_BLOCKED ) {
                    ?><i class="icon-ban-circle disabled" title="<?= _te('shops', 'отредактирован пользователем после блокировки'); ?>"></i><?
                } else if( $v['status'] == Shops::STATUS_REQUEST ) {
                    ?><i class="disabled" title="<?= _te('shops', 'новый магазин'); ?>"></i><?
                }
            } elseif($v['moderated'] == 2) {
                ?><i class="icon-pencil disabled" title="<?= _te('shops', 'отредактирован пользователем'); ?>"></i><?
            }
        } ?>
    </td>
    <td><?= tpl::date_format2($v['created'], true, true); ?></td>
    <td>
        <a class="but edit" href="<?= $urlEdit.$v['id'] ?>"></a>
        <a class="but del j-act-del" href="#" data-id="<?= $v['id'] ?>"></a>
    </td>
</tr>
<? } if(empty($list)) { ?>
<tr class="norecords">
    <td colspan="5"><?= _t('', 'Nothing found') ?></td>
</tr>
<? } ?>
