<?php

?>
    <thead>
        <tr class="header nodrag nodrop">
        <? if(FORDEV){ ?><th width="30"><?= _t('', 'ID'); ?></th><? } ?>
            <th class="left"><?= _t('bbs', 'Название'); ?></th>
            <th width="250"><?= _t('bbs', 'Категория'); ?></th>
            <th width="80"><?= _t('', 'Action') ?></th>
        </tr>
    </thead>
<?

$cols = 3; if(FORDEV) $cols++;
if( ! empty($types))
{   $i=1;
    foreach($types as $v) { $inherited = $v['cat_id']!=$cat_id; ?>
    <tr class="row<?= ($i++%2); ?><? if($inherited): ?> nodrag nodrop<? endif; ?>" id="dnd-<?= $v['id']; ?>">
    <?  if(FORDEV): ?><td><?= $v['id']; ?></td><? endif; ?>
        <td class="left"><?= $v['title']; ?></td>
        <td><a href="<?= $this->adminLink('categories_edit&id='.$v['cat_id']) ?>" target="_blank" class="desc"><?= $v['cat_title']; ?></a></td>
        <td>
            <a class="but <? if($v['enabled']): ?>un<? endif; ?>block<? if($inherited): ?> disabled<? endif; ?>" href="javascript:void(0);" onclick="return jCategoryTypes.toggle(<?= $v['id']; ?>, this);"></a>
            <a class="but edit<? if($inherited): ?> disabled<? endif; ?>" title="<?= _te('', 'редактировать'); ?>" href="javascript:void(0);" onclick="return jCategoryTypes.edit(<?= $v['id']; ?>);"></a>
            <? if( !$inherited){ ?>
            <a class="but del" title="<?= _te('', 'Delete'); ?>" href="javascript:void(0);" onclick="return jCategoryTypes.del(<?= $v['id']; ?>, this);"></a>
            <? }else{ ?>
            <a class="but"></a>
            <? } ?>
        </td>
    </tr>
    <?
    }
} else {
?>
<tr class="norecords">
    <td colspan="<?= $cols; ?>"><?= _t('bbs', 'нет типов в указанной категории'); ?></td>
</tr>
<? }