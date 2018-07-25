<?php
/**
 * Фильтр по региону: Выбор страны (desktop)
 * @var $this Geo
 * @var $countries array список стран
 */
?>
<ul class="dropdown-menu-list f-navigation__country_change__links">
    <?php foreach($countries as $v): ?>
    <li><a href="<?= $v['link'] ?>" data="{id:<?= $v['id'] ?>,pid:0,key:'<?= $v['keyword'] ?>', noregions:<?= ! empty($v['filter_noregions']) ? 1: 0 ?>}"><?= $v['title'] ?></a></li>
    <?php endforeach; ?>
</ul>

