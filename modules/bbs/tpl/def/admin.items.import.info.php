<?php
/**
 * @var $this BBS
 * @var $sett_user array
 * @var $sett_shop array
 */
 extract($settings, EXTR_PREFIX_ALL | EXTR_REFS, 'sett');
?>
<div id="popupBBSItemImportInfo" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title"><?= _t('bbs', 'Информация об импорте №[id]', array('id' => $id)); ?></div>
        <div class="ipopup-content" style="width:500px;">
            <table class="admtbl tbledit">
                <tr>
                    <th width="150" style="height: 1px;"></th>
                    <th width="12" style="height: 1px;"></th>
                    <th style="height: 1px;"></th>
                </tr>
                <? if( ! empty($parent_id)): ?>
                <tr>
                    <td class="row1 field-title right"><?= _t('bbs', 'Периодический импорт:'); ?></td>
                    <td></td>
                    <td class="row2">
                        <a href="" onclick="return jBbsImportsList.importInfo(<?= $parent_id ?>);">#<?= $parent_id ?></a>
                    </td>
                </tr>
                <? endif; ?>
                <tr>
                    <td class="row1 field-title right"><?= _t('bbs', 'Категория:'); ?></td>
                    <td></td>
                    <td class="row2">
                        <?= ! empty($settings['cat_title']) ? $settings['cat_title'] : _t('bbs.import', 'Не указана') ?>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title right"><?= _t('users', 'Пользователь:'); ?></td>
                    <td></td>
                    <td class="row2">
                        <a href="javascript:void(0);" onclick="return bff.userinfo(<?= $user_id ?>);" class="ajax<? if($user['blocked']){ ?> clr-error<? } ?>"><?= $user['email'] ?></a>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title right"><?= _t('bbs', 'Объявления:'); ?></td>
                    <td></td>
                    <td class="row2">
                        <span class="desc"><?= _t('bbs', 'всего:'); ?>&nbsp;</span><span><?= $items_total ?></span>
                        <span class="desc"><?= _t('bbs', 'обработано:'); ?>&nbsp;</span><span class="text-success"><?= $items_processed ?></span>
                        <? if($items_ignored > 0){ ?><span class="desc"><?= _t('bbs', 'пропущено:'); ?>&nbsp;</span><span class="text-error"><?= $items_ignored ?></span><? } ?>
                    </td>
                </tr>
                <? bff::hook('bbs.admin.import.info.header', $aData); ?>
                <tr>
                    <td colspan="3"><hr class="cut" /></td>
                </tr>
                <tr>
                    <td class="row1 field-title bold right"><?= _t('bbs', 'Настройки импорта:'); ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="row1 field-title right"><?= _t('users', 'Пользователь:'); ?></td>
                    <td></td>
                    <td class="row2">
                        <a href="javascript:void(0);" onclick="return bff.userinfo(<?= $sett_user['user_id'] ?>);" class="ajax"><?= $sett_user['email'] ?></a>
                    </td>
                </tr>
                <? if ( ! empty($sett_shop)) { ?>
                <tr>
                    <td class="row1 field-title right"><?= _t('bbs', 'Магазин:'); ?></td>
                    <td></td>
                    <td class="row2">
                        <a href="javascript:void(0);" onclick="return bff.shopInfo(<?= $sett_shop['id'] ?>);" class="ajax"><?= $sett_shop['title'] ?></a>
                    </td>
                </tr>
                <? } ?>
                <tr>
                    <td class="row1 field-title right"><?= _t('bbs', 'Статус объявлений:'); ?></td>
                    <td></td>
                    <td class="row2">
                        <?php
                            if ($sett_state === BBS::STATUS_PUBLICATED) echo _t('bbs', 'опубликованы');
                            elseif ($sett_state === BBS::STATUS_PUBLICATED_OUT) echo _t('bbs', 'сняты с публикации');
                        ?>
                    </td>
                </tr>
                <? if($periodic){?>
                    <tr>
                        <td class="row1 field-title right"><?= _t('bbs', 'Url для скачивания:'); ?></td>
                        <td></td>
                        <td class="row2">
                            <?= tpl::truncate($periodic_url, 60, '...', true); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right"><?= _t('bbs', 'Период обновления:'); ?></td>
                        <td></td>
                        <td class="row2">
                            <?= BBSItemsImport::importPeriodOptions()[$periodic_timeout]['title'] ?>
                        </td>
                    </tr>
                <?}?>
                <? bff::hook('bbs.admin.import.info.settings', $aData); ?>
            </table>
            <div class="ipopup-content-bottom">
                <ul class="right">
                    <li><span class="desc"><?= $user_ip; ?></span></li>
                    <li><?= _t('bbs', 'статус:'); ?> <span class="bold"><?= $status_title; ?></span></li>
                    <li><span class="post-date" title="<?= _te('', 'Created'); ?>"><?= tpl::date_format2($created, true); ?></span></li>
                </ul>
            </div>
        </div>
    </div>
</div>