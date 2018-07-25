<?php
/**
 * Кабинет пользователя: Импорт объявлений - layout
 * @var $this BBS
 * @var $list array список
 * @var $list_total integer всего записей в списке
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 */
tpl::includeJS('bbs.my.import', false, 6);
?>
<h2 class="l-pageSubheading"><?= _t('bbs.import', 'Импорт объявлений') ?></h2>

<form action="" id="j-my-import-form" class="form-horizontal" enctype="multipart/form-data">
  <input type="hidden" name="sAction" value="import" />
  <div class="form-group">
    <label class="col-sm-3 control-label"><?= _t('bbs.import', 'Категория') ?></label>
    <div class="col-md-6 col-sm-9">
      <input type="hidden" name="cat_id" class="j-cat-value" value="0" />
      <div class="dropdown">
        <div class="j-cat-select-link-selected" style="display: none;">
          <img class="abs j-icon" alt="" src="" />
          <a href="#" class="j-cat-select-link j-title"></a>
        </div>
        <div class="form-group-noinput j-cat-select-link-empty">
          <a href="#" class="link-ajax j-cat-select-link"><span><?= _t('bbs.import', 'Выберите категорию') ?></span> <i class="fa fa-chevron-down"></i></a>
        </div>
        <div class="dropdown-menu j-cat-select-popup">
          <div class="j-cat-select-step1-desktop">
            <?= $this->catsList('form', bff::DEVICE_DESKTOP, 0); ?>
          </div>
          <div class="j-cat-select-step2-desktop hide"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label class="col-sm-3 control-label"><?= _t('bbs.import', 'Статус объявлений') ?><span class="required-mark">*</span></label>
    <div class="col-sm-9 j-cat-types">
      <div class="radio-inline">
        <label><input name="status" value="<?= BBS::STATUS_PUBLICATED ?>" type="radio" class="j-cat-type j-required" checked="checked"> <?= _t('ibbs.import', 'Опубликованы') ?></label>
      </div>
      <div class="radio-inline">
        <label><input name="status" value="<?= BBS::STATUS_PUBLICATED_OUT ?>" type="radio" class="j-cat-type j-required"> <?= _t('bbs.import', 'Сняты с публикации') ?></label>
      </div>
    </div>
  </div>
  <?php if(BBS::formPublicationPeriod()) { $publicationPeriodOpts = $this->publicationPeriodOptions($daysDefault); ?>
  <div class="form-group j-publicate-period">
    <label for="import-period" class="col-sm-3 control-label"><?= _t('bbs.import', 'Период публикации') ?><span class="required-mark">*</span></label>
    <div class="col-md-3 col-sm-6 j-cat-types">
      <select name="publicate_period" id="import-period" class="form-control"><?= HTML::selectOptions($publicationPeriodOpts, $daysDefault, false, 'days', 't') ?></select>
    </div>
  </div>
  <?php } ?>
  <?php if(BBS::importUrlEnabled()) { ?>
  <div class="form-group">
    <label class="col-sm-3 control-label"><?= _t('bbs.import', 'Источник') ?><span class="required-mark">*</span></label>
    <div class="col-sm-9 j-import-types">
      <div class="radio-inline">
        <label><input name="type" value="<?= BBSItemsImport::TYPE_FILE ?>" type="radio" class="j-import-type j-required" checked="checked" /> <?= _t('bbs.import', 'Файл') ?></label>
      </div>
      <div class="radio-inline">
        <label><input name="type" value="<?= BBSItemsImport::TYPE_URL ?>" type="radio" class="j-import-type j-required" /> <?= _t('bbs.import', 'Ссылка на файл') ?></label>
      </div>
    </div>
  </div>
  <?php } ?>
  <div class="form-group j-file-import">
   <label class="col-sm-3 control-label"><?= _t('', 'Файл') ?><span class="required-mark">*</span></label>
   <div class="col-sm-9">
      <div class="form-group-noinput j-attach-block">
        <div class="upload-btn j-upload" style="width: 300px;">
          <span class="upload-mask">
            <input type="file" name="file" class="j-upload-file" />
          </span>
          <a href="#" onclick="return false;" class="link-ajax"><span><?= _t('', 'Выбрать файл, до 10мб') ?></span></a>
        </div>
        <div class="j-cancel hide">
          <span class="j-cancel-filename"></span>
          <a href="#" class="link-ajax link-red j-cancel-link"><i class="fa fa-times"></i> <span><?= _t('', 'отмена') ?></span></a>
        </div>
      </div>
    </div>
  </div>
  <?php if(BBS::importUrlEnabled()) { ?>
  <div class="form-group j-url-import hide">
    <label for="import-url" class="col-sm-3 control-label"><?= _t('', 'URL') ?><span class="required-mark">*</span></label>
    <div class="col-md-6 col-sm-9">
      <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-globe"></i></span>
        <input type="text" id="import-url" name="url" value=""  class="form-control j-url-input" placeholder="<?= _te('bbs.import', 'Укажите URL-путь к файлу импорта, например http://site.com/import.xml') ?>" maxlength="1500" />
      </div>
    </div>
  </div>
  <div class="form-group j-url-import hide">
    <label for="j-url-import-period" class="col-sm-3 control-label"><?= _t('', 'Период обработки') ?><span class="required-mark">*</span></label>
    <div class="col-md-3 col-sm-6">
      <select id="j-url-import-period" name="period" class="form-control"><?= BBSItemsImport::importPeriodOptions(true) ?></select>
    </div>
  </div>
  <?php } ?>
  <div class="form-group">
    <div class="col-sm-9 col-sm-offset-3">
      <input type="submit" class="btn btn-success j-submit" value="<?= _te('bbs.import', 'Импортировать'); ?>" data-loading-text="<?= _te('','Подождите...'); ?>" disabled="disabled" />
      <input type="button" class="btn btn-default j-template" data-ext="xml" value="<?= BBS::importCsvFrontendEnabled() ? _te('bbs.import', 'Скачать шаблон xml') : _te('bbs.import', 'Скачать шаблон'); ?>" disabled="disabled" />
      <?php if (BBS::importCsvFrontendEnabled()): ?>
        <input type="button" class="btn btn-default j-template" data-ext="csv" value="<?= _te('bbs.import', 'Скачать шаблон csv'); ?>" disabled="disabled" />
      <?php endif; ?>
      <?= bff::filter('bbs.import.cabinet.buttons', ''); ?>
    </div>
  </div>

</form>

<?= $periodic ?>

<?php if ( $list_total > 0 ) { ?>
<h2 class="l-pageSubheading"><?= _t('bbs.import','Периодический импорт'); ?></h2>
<form action="" id="j-my-import-history-form">
  <input type="hidden" name="page" value="0">
  <input type="hidden" name="pp" value="15" id="j-my-import-history-pp-value">

  <div class="table-responsive">
    <table class="table table-bordered">
      <head>
        <tr>
          <th style="width: 170px;" class="align-left"><?= _t('bbs.import','Категория'); ?></th>
          <th style="width: 110px;" class="text-center"><?= _t('bbs.import','Объявлений'); ?></th>
          <th style="width: 110px;" class="text-center"><?= _t('bbs.import','Обработано'); ?></th>
          <th><?= _t('bbs.import','Комментарий'); ?></th>
          <th style="width: 100px;" class="text-center"><?= _t('bbs.import','Статус'); ?></th>
          <th style="width: 90px;" class="text-center"><?= _t('bbs.import','Действие'); ?></th>
        </tr>
      </head>
      <tbody id="j-my-import-history-list">
        <?= $list ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ( $list_total > 15 ) { ?>
  <div class="usr-pagination">
    <div id="j-my-import-history-pp" class="usr-pagination-dropdown dropdown">
      <a class="btn btn-default j-pp-dropdown" data-toggle="dropdown" href="#">
        <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
        <b class="caret"></b>
      </a>
      <ul class="dropdown-menu pull-right">
        <?php foreach($pgn_pp as $k=>$v): ?>
        <li><a href="#" class="<?php if($k == $f['pp']) { ?>active <?php } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
      <?php endforeach; ?>
      </ul>
    </div>
    <div id="j-my-import-history-pgn">
      <?= $pgn ?>
    </div>
  </div>
  <?php } } ?>
</form>
<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jBBSMyImport.init(<?= func::php2js(array(
            # category
      'catsRootID' => BBS::CATS_ROOTID,
      'catsMain'   => $this->catsList('form', 'init'),
      'typeFile'   => BBSItemsImport::TYPE_FILE,
      'typeUrl'    => BBSItemsImport::TYPE_URL,
      'statusPublicated' => BBS::STATUS_PUBLICATED,
            # lang
      'lang' => array(
        'wrongFormat'   => _t('bbs.import', 'Разрешено импортировать только файлы в формате xml'),
        'success'       => _t('bbs.import', 'Импортирование объявлений было успешно инициировано'),
        'delete_confirm'=> _t('bbs.import', 'Вы действительно хотите удалить импорт?'),
        ),
      )) ?>);
    <?php if( ! empty($errors)): if(is_array($errors)){ $errors = join('<br />', $errors); } ?>app.alert.error('<?= $errors ?>');
  <?php endif; ?>
});
  <?php js::stop(); ?>
</script>