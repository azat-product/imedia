<?php
/**
 * Помощь: просмотр вопроса
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $title string заголовок
 * @var $content string содержание (HTML)
 * @var $questions_other array список похожих вопросов
 */
?>

<?= tpl::getBreadcrumbs($breadCrumbs); ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= $title ?></h1>
    </div>

    <?= $content ?>

    <?php if ( ! empty($questions_other)) { ?>
    <div class="hl-list-category mrgt30">
      <h2 class="hl-list-title"><?= _t('help', 'Другие вопросы из этого раздела') ?></h2>
      <ul class="hl-list-items">
        <?php foreach ($questions_other as $v) { ?>
            <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
        <?php } ?>
      </ul>
    </div>
    <?php } ?>

  </div>
</div>