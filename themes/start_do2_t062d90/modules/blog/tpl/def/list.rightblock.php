<?php
/**
 * Блог: список постов - правый блок
 * @var $this Blog
 * @var $categories array список категорий (если включены)
 * @var $tags array список тегов (если включены)
 * @var $favs array список избранных постов
 */
?>

<?php // Categories
if (Blog::categoriesEnabled() && ! empty($categories)) { ?>
<div class="l-mainLayout-sidebar-item">
  <div class="l-mainLayout-sidebar-title"><?= _t('blog', 'Категории') ?></div>
  <ul class="l-mainLayout-sidebar-nav">
    <?php foreach ($categories as &$v) { ?>
        <?php if ($v['active']) { ?>
            <li class="active"><a class="l-mainLayout-sidebar-nav-remove" href="<?= Blog::url('index') ?>"><i class="fa fa-times"></i></a> <span><?= $v['title'] ?></span></li>
        <?php } else { ?>
            <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
        <?php } ?>
    <?php } unset($v); ?>
  </ul>
</div>
<?php } ?>

<?php // Tags
if (Blog::tagsEnabled() && ! empty($tags)) { ?>
<div class="l-mainLayout-sidebar-item">
  <div class="l-mainLayout-sidebar-title"><?= _t('blog', 'Теги') ?></div>
  <div class="c-tags">
    <?php foreach ($tags as &$v) { ?>
        <?php if ($v['active']) { ?>
            <a href="<?= Blog::url('index') ?>" class="c-tag active"><?= $v['tag'] ?> <i class="fa fa-times"></i></a>
        <?php } else { ?>
            <a href="<?= $v['link'] ?>" class="c-tag"><?= $v['tag'] ?></a>
        <?php } ?>
    <?php } unset($v); ?>
  </div>
</div>
<?php } ?>

<?php // Favorites
if ( ! empty($favs)) { ?>
<div class="l-mainLayout-sidebar-item">
  <div class="l-mainLayout-sidebar-title"><?= _t('blog', 'Избранные') ?></div>
  <ul class="list-unstyled">
    <?php foreach ($favs as &$v) { ?>
        <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
    <?php } unset($v); ?>
  </ul>
</div>
<?php } ?>

<?php // Banner
if ($bannerRight = Banners::view('blog_search_right')) { ?>
<div class="l-banner-v">
  <?= $bannerRight ?>
</div>
<?php } ?>