<?php
/**
 * Помощь: поиск вопроса
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $questions array список вопросов
 * @var $pgn string постраничная навигация (HTML)
 * @var $f array фильтр: q - строка поиска
 */
$lang_more = _t('help', 'Подробнее');
?>

<?php if(DEVICE_DESKTOP_OR_TABLET) {
  echo tpl::getBreadcrumbs($breadCrumbs);
} ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= _t('help', 'Результаты поиска по запросу "[query]":', array('query'=>HTML::escape($f['q']))) ?></h1>
    </div>
    
    <?php if ( ! empty($questions)) { ?>
        <ul class="hl-list-search">
          <?php foreach($questions as &$v) { ?>
          <li><div class="hl-list-search-num"><?= $num++ ?>.</div>
            <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
            <div class="hl-list-search-text">
              <div><?= $v['textshort'] ?></div>
              <?php if(!$v['content_no']){ ?><a href="<?= $v['link'] ?>" class="link-ico"><span><?= $lang_more ?></span> <i class="fa fa-angle-right c-link-icon"></i></a><?php } ?>
            </div>
          </li>
          <?php } unset($v); ?>
        </ul>
    <?php } else { ?>
        <div class="alert alert-info"><?= _t('help', 'По запросу "[query]" ничего не найдено', array('query'=>HTML::escape($f['q']))) ?></div>
    <?php } ?>

  </div><!-- /.l-mainLayout-content -->
</div><!-- /.container -->