<?php
/**
 * Фильтр по региону: выбор ветки метро
 * @var $this Geo
 * @var $city_id integer ID города
 * @var $data array список веток метро
 */
?>

<ul class="dropdown-menu-list">
	<?php foreach($data as $k=>$v) { ?>
	<li>
		<a href="#" class="j-branch" data="{id:<?= $v['id'] ?>,city:<?= $city_id ?>}">
	    <span class="c-metro-ico" style="background-color: <?= $v['color'] ?>"></span>
	    <?= $v['t'] ?>
		</a>
	</li>
	<?php } ?>
</ul>