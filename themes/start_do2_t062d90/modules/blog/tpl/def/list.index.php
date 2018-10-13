<?php
/**
 * Блог: список постов - главная
 * @var $this Blog
 * @var $breadCrumbs array хлебные крошки
 * @var $titleh1 string заголовок H1
 * @var $list string список (HTML)
 * @var $pgn string постраничная навигация (HTML)
 * @var $rightBlock string правый блок (HTML)
 * @var $page integer номер текущей страницы
 * @var $seotext string SEO-текст
 */
?>

<?= tpl::getBreadcrumbs($breadCrumbs); ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content<?php if (DEVICE_DESKTOP_OR_TABLET && $rightBlock) {  ?> has-sidebar<?php } ?>">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= (!empty($titleh1) ? $titleh1 : _t('blog', 'Блог проекта')) ?></h1>
    </div>
    <?= $list ?>
    <?= $pgn ?>
  </div><!-- /.l-mainLayout-content -->

  <?php if (DEVICE_DESKTOP_OR_TABLET && $rightBlock) {  ?>
    <!-- Sidebar -->
    <div class="l-mainLayout-sidebar">
      <?= $rightBlock ?>
    </div>
  <?php } ?>

</div><!-- /.l-mainLayout -->

<?php if($page <= 1 && !empty($seotext)) { ?>
<div class="l-seoText">
  <?= $seotext ?>
</div>
<?php } ?>