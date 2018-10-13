<?php
/**
 * Фильтр по региону: выбор ветки метро
 * @var $this Geo
 * @var $city_id integer ID города
 * @var $data array список веток метро
 */

foreach($data as $k=>$v):
?>
<a href="javascript:void(0);" class="j-branch" data="{id:<?= $v['id'] ?>,city:<?= $city_id ?>}">
    <span class="i-formpage__metroselect__item" style="background-color: <?= $v['color'] ?>"></span>
    <span class="inlblk"><?= $v['t'] ?></span>
</a>
<?
endforeach;