<?php
    /**
     * @var $this Help
     */
    foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>" data-numlevel="<?= $v['numlevel'] ?>" data-pid="<?= $v['pid'] ?>">
        <td class="small"><?= $id ?></td>
        <td class="left" style="padding-left:<?= ($v['numlevel']*15-10) ?>px;">
            <a class="but category-expand folder<?= (!$v['subs'] ? '_ua' : '') ?> but-text" data-id="<?= $id ?>" href="#"><?= $v['title'] ?></a>
        </td>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td>
            <a class="but <?= ($v['enabled']?'un':'') ?>block category-toggle" title="<?= _te('','Enabled') ?>" href="#" data-type="enabled" data-id="<?= $id ?>"></a>
            <a class="but edit category-edit" title="<?= _te('','Edit') ?>" href="#" data-id="<?= $id ?>"></a>
            <a class="but del category-del" title="<?= _te('','Delete') ?>" href="#" data-id="<?= $id ?>"></a>
        </td>
    </tr>
<? endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords">
        <td colspan="4">
            <?= _t('', 'Nothing found') ?>
        </td>
    </tr>
<? endif;