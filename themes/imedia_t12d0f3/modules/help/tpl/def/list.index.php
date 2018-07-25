<?php
/**
 * Помощь: главная
 * @var $this Help
 * @var $breadCrumbs array хлебные крошки
 * @var $titleh1 string заголовок H1 или пустая строка
 * @var $items array список
 * @var $favs array избранные вопросы
 * @var $seotext string SEO-текст
 */
?>

<?php if(DEVICE_DESKTOP_OR_TABLET) {
  echo tpl::getBreadcrumbs($breadCrumbs);
} ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content">
    <div class="l-pageHeading">
      <h1 class="l-pageHeading-title"><?= (!empty($titleh1) ? $titleh1 : _t('help', 'Помощь по проекту')) ?></h1>
    </div>
    
    <div class="hl-list">
      <?php foreach($items as &$v){ ?>
      <?php if ( ! empty($v['subcats'])) { ?>
      <div class="hl-list-category">
        <h2 class="hl-list-title"><?= $v['title'] ?></h2>
        <ul class="hl-list-items">
          <?php foreach($v['subcats'] as $c) { ?>
          <li><a href="<?= Help::url('cat', array('keyword'=>$c['keyword'])) ?>"><?= $c['title'] ?></a></li>
          <?php } ?>
        </ul>
      </div>
      <?php } else if($v['questions']) { ?>
      <div class="hl-list-category">
        <h2 class="hl-list-title"><?= $v['title'] ?></h2>
        <ul class="hl-list-items">
          <?php foreach($v['questions_list'] as $q) { ?>
          <li><a href="<?= Help::urlDynamic($q['link']) ?>"><?= $q['title'] ?></a></li>
          <?php } ?>
        </ul>
      </div>
      <?php } ?>
      <?php } unset($v); ?>

      <?php if( ! empty($favs)) { ?>
      <div class="hl-list-category">
        <h2 class="hl-list-title"><?= _t('help', 'Частые вопросы') ?></h2>
        <ul class="hl-list-items">
          <?php foreach($favs as $v) { ?>
          <li><a href="<?= Help::urlDynamic($v['link']) ?>"><?= $v['title'] ?></a></li>
          <?php } ?>
        </ul>
      </div>
      <?php } ?>

    </div>

  </div><!-- /.l-mainLayout-content -->

</div><!-- /.l-mainLayout -->
<?php if(!empty($seotext)) { ?>
<div class="l-seoText">
  <?= $seotext ?>
</div>
<?php } ?>