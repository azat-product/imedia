<?php
/**
 * Блок категорий на главной (phone)
 * @var $step integer шаг выбора
 * @var $cats array категории
 * @var $parent array данные о категории выше (для шага №2)
 */

?>
<?php if($step == 1) { # ШАГ 1 ?>
<div class="index-categories-m">
  <?php $i=0; foreach($cats as $v): ?>
  <a class="index-categories-m-item j-main<?php if(++$i == sizeof($cats)) { ?> last<?php } ?>" href="<?= $v['l'] ?>" data="{id:<?= $v['id'] ?>,pid:0,subs:<?= $v['subs'] ?>}" title="<?= $v['t'] ?>">
    <span class="index-categories-m-item-img">
      <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
    </span>
    <span class="index-categories-m-item-name">
      <?= $v['t'] ?>
    </span>
    <span class="index-categories-m-item-arrow">
      <i class="fa fa-chevron-right"></i>
    </span>
  </a>
<?php endforeach; ?>
</div>
<?php } else if($step == 2) { # ШАГ 2 ?>
<div class="index-categories-m">
  <div class="index-categories-m-item">
    <span class="index-categories-m-item-img">
      <a href="<?= $parent['link'] ?>" class="j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></a>
    </span>
    <span class="index-categories-m-item-name">
      <a href="#" class="backto block j-back" data="{prev:<?= ( $parent['main'] ? 0 : $parent['pid'] ) ?>}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
      <div><strong><?= $parent['title'] ?></strong></div>
    </span>
  </div>
  <ul class="index-categories-m-sub">
    <li><a href="<?= $parent['link'] ?>" class="all j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>}"><?= _t('filter', 'Все подкатегории') ?>&nbsp;&raquo;</a></li>
    <?php foreach($cats as $v): ?>
    <li>
      <a href="<?= $v['l'] ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}" class="j-sub" title="<?= $v['t'] ?>">
        <i class="fa fa-chevron-right index-categories-m-sub-i-arrow"></i>
        <?= $v['t'] ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php } ?>