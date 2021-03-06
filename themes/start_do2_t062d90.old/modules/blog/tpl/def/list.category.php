<?php
/**
 * Блог: список постов - категория
 * @var $this Blog
 * @var $breadCrumbs array хлебные крошки
 * @var $title string заголовок
 * @var $list string список (HTML)
 * @var $pgn string постраничная навигация (HTML)
 * @var $rightBlock string правый блок (HTML)
 */
?>

<?php if (DEVICE_DESKTOP_OR_TABLET) {
  echo tpl::getBreadcrumbs($breadCrumbs);
} ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content<?php if (DEVICE_DESKTOP_OR_TABLET && $rightBlock) {  ?> has-sidebar<?php } ?>">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= $title ?></h1>
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