<?php
    /**
     * @var $this Bbs
     */
    $statusList = $this->itemsImport()->getStatusList();

    foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>">
        <td class="small"><?= $id ?></td>
        <td class="left"><?= ! empty($v['cat_title']) ? $v['cat_title'] : _t('bbs.import', 'Не указана') ?></td>
        <td><span title="<?= _te('bbs', 'обработано'); ?>"><?= $v['items_processed'] ?></span> / <span title="<?= _te('bbs', 'всего объявлений'); ?>"><?= $v['items_total'] ?></span></td>
        <td class="left"><?= $v['comment_text'] ?></td>
        <td><span title="<?= tpl::date_format2($v['status_changed'], true, true) ?>"><?= $statusList[$v['status']] ?></span></td>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td>
            <? if( ! empty($v['filename'])): ?>
                <a href="<?= $v['filename'] ?>" target="_blank" title="<?= _te('bbs', 'Скачать'); ?>" class="but icon icon-download"></a>
            <? else: ?>
                <a class="but" href="javascript:void(0);"></a>
            <? endif; ?>
            <a href="javascript:void(0);" onclick="return jBbsImportsList.importInfo(<?= $id ?>);" class="but sett" title="<?= _te('bbs', 'Информация'); ?>"></a>
            <a href="javascript:void(0);" onclick="return bff.userinfo(<?= $v['user_id'] ?>);" class="but userlink" title="<?= _te('users', 'Пользователь'); ?>" style="padding:0px;"></a>
            <?php
               if(in_array($v['status'], array(BBSItemsImport::STATUS_WAITING,BBSItemsImport::STATUS_PROCESSING)) && $v['is_admin'] > 0){ ?>
                <a class="but del item-del" href="javascript:void(0);" title="<?= _te('', 'Cancel'); ?>" rel="<?= $id ?>"></a>
            <? } else { ?>
                <a class="but" href="javascript:void(0);"></a>
            <? } ?>
        </td>
    </tr>
<? endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords">
        <td colspan="7">
            <?= _t('', 'Nothing found') ?>
        </td>
    </tr>
<? endif;