<?php
/**
 * Блог: просмотр поста
 * @var $this Blog
 * @var $breadCrumbs array хлебные крошки
 * @var $created string дана создания поста
 * @var $title string заголовок
 * @var $content string содержание (HTML)
 * @var $tags array теги
 * @var $share_code string код шаринга в соц. сетях
 * @var $next array ссылка на следующий пост
 */
 $bannerRight = Banners::view('blog_view_right');
?>

<?= tpl::getBreadcrumbs($breadCrumbs); ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content<?php if (DEVICE_DESKTOP && $bannerRight) { ?> has-sidebar<?php } ?>">
    <div class="l-pageHeading">
      <div class="bl-date"><?= tpl::dateFormat($created, _t('blog','%d.%m.%Y в %H:%M')) ?></div>
      <h1 class="l-pageHeading-title"><?= $title ?></h1>
    </div>

    <?= $content ?>

    <?php if ( ! empty($tags) ) { ?>
    <div class="c-tags c-tags_spacing">
      <?php foreach ($tags as $v) { ?>
      <a href="<?= $v['link'] ?>" class="c-tag"><?= $v['tag'] ?></a>
      <?php } ?>
    </div>
    <?php } ?>
    <?php if ( ! empty($share_code) ) { ?>
    <div class="mrgt15 mrgb15">
      <?= $share_code ?>
    </div>
    <?php } ?>
    <a href="<?= Blog::url('index') ?>" class="link-ico">&lsaquo; <span><?= _t('blog', 'Назад в блог') ?></span></a>
    <?php if ( ! empty($next)) { ?>
      <a href="<?= $next['link'] ?>" class="link-ico"><span><?= _t('blog', 'Следующая запись') ?></span> &rsaquo;</a>
    <?php } ?>
  </div><!-- /.l-mainLayout-content -->

  <?php if (DEVICE_DESKTOP && $bannerRight) { ?>
    <!-- Sidebar -->
    <div class="l-mainLayout-sidebar">
      <div class="l-banner-v">
        <?= $bannerRight ?>
      </div>
    </div>
  <?php } ?>

</div><!-- /.l-mainLayout -->