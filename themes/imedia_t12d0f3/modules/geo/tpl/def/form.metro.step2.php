<?php
/**
 * Фильтр по региону: выбор станции метро
 * @var $this Geo
 * @var $id integer ID ветки метро
 * @var $t string название ветки метро
 * @var $city_id integer ID города
 * @var $st array список станций метро
 */

?>
<div class="dropdown-menu-heading dropdown-menu-heading_sm">
  <span>
    <a href="#" class="backto link-ajax j-back">&laquo; <span><?= _t('item-form', 'Выбрать другую ветку') ?></span></a>
  </span>
  <div class="dropdown-menu-heading-title">
    <span class="c-metro-ico" style="background-color: <?= $color ?>"></span>
    <?= $t ?>
  </div>
</div>

<ul class="dropdown-menu-list">
  <?php foreach($st as $k=>$v): ?>
  <li><a href="#" class="j-station" data="{id:<?= $v['id'] ?>,branch:<?= $id ?>,city:<?= $city_id ?>}"><?= $v['t'] ?></a></li>
  <?php endforeach; ?>
</ul>