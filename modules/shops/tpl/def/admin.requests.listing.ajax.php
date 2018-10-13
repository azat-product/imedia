<?php
    /**
     * @var $this Shops
     */
    foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2).($v['viewed'] ? ' desc' : '') ?>">
        <td class="small"><?= $id ?></td>
        <td class="left">
            <? if($v['user_id']) { ?>
            <a class="<? if($v['viewed']){ ?> desc<? } ?>" href="javascript:void(0);" onclick="return bff.userinfo(<?= $v['user_id'] ?>);"><?= $v['user_email'] ?></a>
            <? } else { echo $v['email']; } ?></td>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td><a href="<?= $this->adminLink('ban', 'users') ?>" class="desc"><?= long2ip($v['user_ip']) ?></a></td>
        <td>
            <a class="but edit request-edit" title="<?= _te('','Edit') ?>" href="javascript:void(0);" data-id="<?= $id ?>"></a>
            <a class="but del request-del" title="<?= _te('', 'Delete') ?>" href="javascript:void(0);" data-id="<?= $id ?>"></a>
        </td>
    </tr>
<? endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords">
        <td colspan="5">
            <?= _t('', 'Nothing found') ?>
        </td>
    </tr>
<? endif;