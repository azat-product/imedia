<?php
/**
 * Кабинет пользователя: Импорт объявлений - список периодически обрабатываемых задач
 * @var $this BBS
 * @var $periodic array список задач
 */

if (empty($periodic)) return;

$period = BBSItemsImport::importPeriodOptions();
?>
<div class="u-cabinet__sub-navigation">
    <div class="u-cabinet__sub-navigation_desktop u-cabinet__sub-navigation_bill hidden-phone">
        <div class="pull-left">
            <h3><?= _t('bbs.import','Периодический импорт'); ?></h3>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="u-cabinet__sub-navigation_mobile u-cabinet__sub-navigation_bill visible-phone">
        <div class="pull-left"><h3><?= _t('bbs.import','Периодический импорт'); ?></h3></div>
        <div class="clearfix"></div>
    </div>
</div>
<div class="u-bill__list">
    <table>
        <tbody>
        <tr>
            <th style="padding-left: 10px;" class="align-left"><?= _t('bbs.import','URL'); ?></th>
            <th style="width: 110px;"><?= _t('bbs.import','Период'); ?></th>
            <th style="width: 110px;"><?= _t('bbs.import','Обработано'); ?></th>
            <th style="padding-left: 10px;" class="align-left"></th>
            <th style="width: 90px;"><?= _t('bbs.import','Действие'); ?></th>
        </tr>
        </tbody>
        <tbody id="j-my-import-period-list">
        <?php foreach($periodic as $v): ?>
            <tr>
                <td class="u-bill__list__descr"><?= $v['periodic_url'] ?></td>
                <td class="align-center"><?= $period[ $v['periodic_timeout'] ]['title'] ?></td>
                <td class="align-center"><?= $v['items_processed'] ?></td>
                <td class="align-center"><?= $v['comment'] ?></td>
                <td class="align-center">
                    <a href="" class="j-delete" data-id="<?=$v['id']?>"><i class="icon-remove"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>