<?php
/**
 * Поиск магазинов: фильтр категорий (desktop, tablet)
 * @var $this Shops
 * @var $step integer шаг
 * @var $cats array категории
 * @var $parent array данные о категории выше (шаг №2)
 * @var $total integer общее кол-во магазинов (шаг №1)
 */

$lang_shops = _t('shops','магазин;магазина;магазинов');
?>
<?php if($step == 1) { # ШАГ 1 ?>
<div class="l-categories">
  <div class="dropdown-menu-heading">
    <a class="dropdown-menu-close close" href="#"><i class="fa fa-times"></i></a>
    <div class="dropdown-menu-heading-title"><?= _t('shops','Выберите категорию') ?></div>
    <?php if (DEVICE_DESKTOP) { ?>
      <span><?php echo ( $total>=1000 ? number_format($total, 0, '', ' ') : $total), '&nbsp;', tpl::declension($total, $lang_shops, false) ?> - <a href="<?= Shops::url('search') ?>" class="j-all" data="{id:0,pid:0,title:'<?= HTML::escape(_t('shops','Все категории'), 'js') ?>'}"><?= _t('filter','смотреть все магазины') ?> &raquo;</a></span>
    <?php } ?>
  </div>
  <div class="l-categories-wrap">
    <ul class="l-categories-items">
      <?php if (DEVICE_PHONE) { ?>
      <li>
        <a href="<?= Shops::url('search') ?>" class="j-all" data="{id:0,pid:0,title:'<?= HTML::escape(_t('shops','Все категории'), 'js') ?>'}">
          <?= _t('filter','смотреть все магазины') ?>
        </a>
      </li>
      <?php } ?>
      <?php foreach($cats as $v) { ?>
      <li>
        <a href="<?= $v['l'] ?>" class="block j-main" data="{id:<?= $v['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
          <span class="l-categories-items-i-img">
            <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
          </span>
          <span class="l-categories-items-i-name"><?= $v['t'] ?></span>
        </a>
      </li>
      <?php } ?>
    </ul>
  </div>
</div>
<?php } else if($step == 2) { # ШАГ 2 ?>
<div class="l-categories">
  <div class="dropdown-menu-heading">
    <a class="dropdown-menu-close close" href="#"><i class="fa fa-times"></i></a>
    <div class="dropdown-menu-heading-container">
      <?php if (DEVICE_DESKTOP) { ?>
      <div class="dropdown-menu-heading-container-img">
        <a href="<?= $parent['link'] ?>" class="img j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>,subs:1,title:'<?= HTML::escape($parent['title'], 'js') ?>'}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></a>
      </div>
      <?php } ?>
      <div class="dropdown-menu-heading-container-content">
        <span>
          <?php if( $parent['main'] ) { ?>
          <a href="#" class="backto block j-back" data="{prev:0}">&laquo; <?= _t('filter','Вернуться к основным категориям') ?></a>
          <?php } else { ?>
          <a href="#" class="backto block j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
          <?php } ?>
        </span>
        <div class="dropdown-menu-heading-title"><?= $parent['title'] ?></div>
        <span><?php echo '<a href="'.$parent['link'].'" data="{id:'.$parent['id'].',pid:'.$parent['pid'].',subs:1,title:\''.HTML::escape($parent['title'], 'js').'\'}" class="j-parent">'.number_format($parent['shops'], 0, '.', ' ').'</a>&nbsp;', tpl::declension($parent['shops'], $lang_shops, false) ?></span>
      </div>
    </div>
  </div>

  <div class="l-categories-list-wrapper">
    <?php
    $cats = ( sizeof($cats) > 6 ? array_chunk($cats, round( sizeof($cats) / 2 ) ) : array($cats) );
    foreach($cats as $catsChunk):
      ?><div class="l-categories-list-wrapper-in"><ul class="l-categories-list"><?php
    foreach($catsChunk as $v):
      ?><li><a href="<?= $v['l'] ?>" class="j-sub<?php if($v['active']) { ?> active<?php } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= ($cut2levels ? 0 : $v['subs']) ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><span class="cat-name"><?= $v['t'] ?></span><?php if($v['subs'] && ! $cut2levels) { ?> &raquo;<?php } ?></a></li><?php
    endforeach; ?>
    </ul></div>
  <?php  endforeach; ?>
  </div>
</div>
<?php } ?>