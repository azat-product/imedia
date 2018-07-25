<?php
    /**
     * @var $this Blog
     */
    foreach($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>">
        <td class="small"><?= $id ?></td>
        <td class="left"><a class="but linkout" href="<?= Blog::urlDynamic($v['link']) ?>" target="_blank"></a> <?= $v['title'] ?></td>
        <? if(Blog::categoriesEnabled()){ ?><td><? if($v['cat_id']) { ?><a href="#" class="small desc post-category" data-id="<?= $v['cat_id'] ?>" onclick="return false;"><?= $v['cat_title'] ?></a><? } else { ?>?<? } ?></td><? } ?>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td>
            <a class="but <?= (!$v['fav']?'un':'') ?>fav post-toggle" title="<?= _te('','Избранные') ?>" href="#" data-type="fav" data-toggle-type="fav" data-id="<?= $id ?>"></a>
            <a class="but <?= ($v['enabled']?'un':'') ?>block post-toggle" title="<?= _te('','Enabled') ?>" href="#" data-type="enabled" data-id="<?= $id ?>"></a>
            <a class="but edit post-edit" title="<?= _te('','Edit') ?>" href="#" data-id="<?= $id ?>"></a>
            <a class="but del post-del" title="<?= _te('','Delete') ?>" href="#" data-id="<?= $id ?>"></a>
        </td>
    </tr>
<? endforeach; unset($v);

if( empty($list) && ! isset($skip_norecords) ): ?>
    <tr class="norecords">
        <td colspan="<?= (Blog::categoriesEnabled() ? 5 : 4) ?>">
            <?= _t('', 'Nothing found') ?>
        </td>
    </tr>
<? endif;