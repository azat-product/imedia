<?php
/**
 * Блог: список постов - список
 * @var $this Blog
 * @var $list array список постов
 */
$lang_more = _t('blog', 'Читать дальше');
?>
<div class="bl-list">

  <?php foreach ($list as $v) { $v['link'] = Blog::urlDynamic($v['link']); ?>
  <div class="bl-list-item">
    <div class="bl-date"><?= tpl::dateFormat($v['created'], '%d.%m.%Y в %H:%M') ?></div>
    <?php if ($v['preview']) { ?>
        <a href="<?= $v['link'] ?>" class="bl-list-item-img">
          <img src="<?= BlogPostPreview::url($v['id'], $v['preview'], BlogPostPreview::szList) ?>">
        </a>
    <?php } ?>
    <h3 class="bl-list-item-title">
      <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
    </h3>
    <div class="bl-list-item-text">
      <?= $v['textshort'] ?>
    </div>
    <div class="bl-list-item-more">
      <a href="<?= $v['link'] ?>"><?= $lang_more ?></a> &rarr;
    </div>
  </div>
<?php } ?>

</div>