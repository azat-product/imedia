<?php
/**
 * @var $this Site
 */
foreach ($list as $k=>$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>">
        <td class="small"><?= $id ?></td>
        <td class="left">
            <?= $v['src'] ?>
        </td>
        <td class="left">
            <?= $v['dest'] ?>
        </td>
        <td>
            <a class="but edit j-edit" title="<?= _te('', 'Edit') ?>" href="javascript:void(0);" data-id="<?= $id ?>"></a>
            <a class="but del j-del" title="<?= _te('', 'Delete') ?>" href="javascript:void(0);" data-id="<?= $id ?>"></a>
        </td>
    </tr>
<? endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords j-empty">
        <td colspan="4">
            <?= _t('', 'ничего не найдено'); ?>
        </td>
    </tr>
<? endif;
