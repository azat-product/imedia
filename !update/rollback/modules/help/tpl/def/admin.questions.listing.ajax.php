<?php
    /**
     * @var $this Help
     */
    foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>">
        <td class="small"><?= $id ?></td>
        <td class="left"><a class="but linkout" href="<?= Help::urlDynamic($v['link']) ?>" target="_blank"></a> <?= $v['title'] ?></td>
        <td><? if($v['cat_id']) { ?><a href="<?= $this->adminLink('questions&cat='.$v['cat_id']) ?>" class="small desc"><?= $v['cat_title'] ?></a><? } else { ?>?<? } ?></td>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td>
            <a class="but <?= (!$v['fav']?'un':'') ?>fav question-toggle" title="<?= _te('','Избранные') ?>" href="#" data-type="fav" data-toggle-type="fav" data-id="<?= $id ?>"></a>
            <a class="but <?= ($v['enabled']?'un':'') ?>block question-toggle" title="<?= _te('','Enabled') ?>" href="#" data-type="enabled" data-id="<?= $id ?>"></a>
            <a class="but edit question-edit" title="<?= _te('','Edit') ?>" href="#" data-id="<?= $id ?>"></a>
            <a class="but del question-del" title="<?= _te('','Delete') ?>" href="#" data-id="<?= $id ?>"></a>
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