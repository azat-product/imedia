<?php
/**
 * Статические страницы: просмотр
 * @var $this Site
 * @var $title string заголовок
 * @var $content string содержание
 */
?>

<?= tpl::getBreadcrumbs(array(array('title'=>$title,'active'=>true))); ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= $title ?></h1>
    </div>
    <div class="l-pageContent">
    	<?= $content ?>
    </div>
  </div>

</div>