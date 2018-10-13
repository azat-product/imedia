<?php   
    $urlEdit = $this->adminLink('edit&id=');
    $moderationList = ($f['status'] == 3); /* На модерации */
?>
<? foreach($list as $k=>$v) { $id = $v['id']; ?>
<tr class="<? if($v['status'] == BBS::STATUS_NOTACTIVATED || $v['status'] == BBS::STATUS_DELETED) { ?>disabled<? } else if($v['status'] == BBS::STATUS_BLOCKED) { ?>text-error<? } ?>">
    <? if($moderationList): ?><td><label class="checkbox inline"><input type="checkbox" name="i[]" onclick="jItems.massModerate('check',this);" value="<?= $id ?>" class="check j-item-check" /></label></td><? endif; ?>
    <td><span class="small"><?= $id ?></span></td>
    <td class="left">
        <a class="linkout but" href="<?= BBS::urlDynamic($v['link'], array('from'=>'adm','mod'=>BBS::moderationUrlKey($id))) ?>" target="_blank"></a><a href="javascript:void(0);" onclick="return bff.iteminfo(<?= $id ?>);" class="nolink"><?= tpl::truncate($v['title'], 60, '...', true) ?></a><br />
        <a href="javascript:void(0);" class="desc" onclick="return jItems.onCategory(<?= $v['cat_id1'] ?>);"><?= $v['cat_title'] ?></a>
        <?php bff::hook('bbs.admin.items.list.view', ['id'=>$id,'item'=>&$v,'filter'=>&$f]); ?>
    </td>
    <td>
        <? # для списка "на модерации", указываем причину отправления на модерацию:
        if ($moderationList) {
            if ($v['status'] == BBS::STATUS_BLOCKED) {
                ?><i class="icon-ban-circle disabled" title="<?= _te('bbs', 'отредактировано пользователем после блокировки'); ?>"></i><?
            } else if ($v['moderated'] == 0) {
                if ($v['import']) {
                    ?><a href="javascript:void(0);" class="j-item-import-info" data-import-id="<?= $v['import'] ?>"><i class="icon-info-sign disabled" title="<?= _te('bbs', 'импортировано'); ?>"></i></a><?
                } else {
                    ?><i class="icon-plus disabled" title="<?= _te('bbs', 'новое объявление'); ?>"></i><?
                }
            } else if ($v['moderated'] == 2) {
                ?><i class="icon-pencil disabled" title="<?= _te('bbs', 'отредактировано пользователем'); ?>"></i><?
            }
        } ?>
    </td>
    <td>
        <span><?= tpl::date_format3($v['created']); ?></span>
    </td>
    <td>
        <a href="javascript:void(0);" onclick="return bff.userinfo(<?= $v['user_id'] ?>);" class="userlink"></a>&nbsp;
        <a class="but images<? if(!$v['imgcnt']){ ?> disabled<? } ?>" href="<?= $urlEdit.$id.'&tab=images' ?>" title="<?= _te('', 'фото:'); ?> <?= $v['imgcnt'] ?>"></a>
        <? if (BBS::commentsEnabled()) { ?><a class="but comm<? if(!$v['comments_cnt']){ ?> disabled<? } ?>" href="<?= $urlEdit.$id.'&tab=comments' ?>" title="<?= _te('', 'комментариев:'); ?> <?= $v['comments_cnt'] ?>"></a><? } ?>
        <a class="but edit" href="<?= $urlEdit.$id ?>"></a>
        <a class="but del item-del" href="javascript:void(0);" rel="<?= $id ?>"></a>
    </td>
</tr>
<? } if(empty($list)) { ?>
<tr class="norecords">
    <td colspan="<?= ($moderationList ? 6 : 5) ?>"><?= _t('', 'Nothing found') ?></td>
</tr>
<? }