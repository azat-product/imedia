<?php
/**
 * Форма магазина: выбор категорий (desktop, tablet)
 * @var $this Shops
 * @var $step integer шаг
 * @var $cats array категории
 * @var $parent array данные о категории выше (для шага №2)
 */
?>
<?php if($step == 1) { # ШАГ 1 ?>
<div class="dropdown-menu-in">
  <ul class="l-categories-items">
  <?php foreach($cats as $v): ?>
    <li>
      <a href="#" class="j-main" data="{id:<?= $v['id'] ?>,pid:<?= $v['pid'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
        <span class="l-categories-items-i-img">
          <img src="<?= $v['i'] ?>" alt="" />
        </span>
        <span class="l-categories-items-i-name"><?= $v['t'] ?></span>
      </a>
    </li>
  <?php endforeach; ?>
  </ul>
</div>
<?php } else if($step == 2) { # ШАГ 2 ?>
<div class="dropdown-menu-heading">
  <a class="dropdown-menu-close close" href="#"><i class="fa fa-times"></i></a>
  <div class="dropdown-menu-heading-container">
    <div class="dropdown-menu-heading-container-img">
      <a href="#" class="j-back" data="{prev:<?= $parent['pid'] ?>}"><img src="<?= $parent['icon'] ?>" alt="" /></a>
    </div>
    <div class="dropdown-menu-heading-container-content">
      <span>
        <a href="#" class="link-ajax j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <span><?= ( $parent['main'] ? _t('item-form','Вернуться к основным категориям') : _t('item-form','Вернуться назад') ) ?></span></a>
      </span>
      <div class="dropdown-menu-heading-title"><?= $parent['title'] ?></div>
    </div>
  </div>
</div>
<div class="l-categories-list-wrapper">

    <?php
    $cats = ( sizeof($cats) > 6 ? array_chunk($cats, round( sizeof($cats) / 2 ) ) : array($cats) );
    foreach($cats as $catsChunk):
      ?><div class="l-categories-list-wrapper-in"><ul class="l-categories-list"><?php
    foreach($catsChunk as $v):
      ?><li><a href="#" class="j-sub<?php if($v['active']) { ?> active<?php } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><span><?= $v['t'] ?></span><?php if($v['subs']) { ?> &raquo;<?php } ?></a></li><?php
    endforeach; ?>
  </ul></div>
<?php  endforeach; ?>

</div>
<?php } ?>