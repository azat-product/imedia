<?php
/**
 * @var $this Bbs
 */
$period = BBSItemsImport::importPeriodOptions();
foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>">
        <td class="small"><a class="j-periodic" href="#" data-id="<?= $id ?>"><?= $id ?></a></td>
        <td class="left"><?= tpl::truncate($v['periodic_url'], 55, '...', true); ?></td>
        <td><?= $period[ $v['periodic_timeout'] ]['title'] ?></td>
        <td><?= $v['items_processed'] ?></td>
        <td class="left"><?= $v['comment'] ?></td>
        <td>
            <a href="#" onclick="return jBbsImportsList.importInfo(<?= $id ?>);" class="but sett" title="<?= _te('bbs', 'Информация'); ?>"></a>
            <a href="#" onclick="return bff.userinfo(<?= $v['user_id'] ?>);" class="but userlink" title="<?= _te('users', 'Пользователь'); ?>" style="padding:0px;"></a>
            <a class="but del item-del" href="#" title="<?= _te('', 'Cancel'); ?>" rel="<?= $id ?>"></a>
        </td>
    </tr>
<? endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords">
        <td colspan="6">
            <?= _t('', 'Nothing found') ?>
        </td>
    </tr>
<? endif;
