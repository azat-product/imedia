<?php
/**
 * Кабинет пользователя: Импорт объявлений - список периодически обрабатываемых задач
 * @var $this BBS
 * @var $periodic array список задач
 */

if (empty($periodic)) return;

$period = BBSItemsImport::importPeriodOptions();
?>

<h2 class="l-pageSubheading"><?= _t('bbs.import','Периодический импорт'); ?></h2>
<div class="table-responsive">
  <table class="table table-bordered">
    <thead>
      <tr>
        <th><?= _t('bbs.import','URL'); ?></th>
        <th style="width: 110px;" class="text-center"><?= _t('bbs.import','Период'); ?></th>
        <th style="width: 110px;" class="text-center"><?= _t('bbs.import','Обработано'); ?></th>
        <th style="width: 90px;" class="text-center"><?= _t('bbs.import','Действие'); ?></th>
      </tr>
    </thead>
    <tbody id="j-my-import-period-list">
      <?php foreach($periodic as $v): ?>
        <tr>
          <td><?= $v['periodic_url'] ?></td>
          <td class="text-center"><?= $period[ $v['periodic_timeout'] ]['title'] ?></td>
          <td class="text-center"><?= $v['items_processed'] ?></td>
          <td class="text-center">
            <a href="" class="link-red j-delete" data-id="<?=$v['id']?>"><i class="fa fa-times"></i></a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

