<?php
    /**
     * Поиск объявлений: фильтр категорий (phone)
     * @var $this BBS
     * @var $step integer шаг
     * @var $cats array категории
     * @var $parent array данные о категории выше (шаг №2)
     */
?>
<? if($step == 1) { # ШАГ 1 ?>
<div class="f-index__mobile__categories">
    <? $i=0;
    foreach($cats as $v): ?>
    <a class="block j-main<? if(++$i == sizeof($cats)) { ?> last<? } ?>" href="<?= $v['l'] ?>" data="{id:<?= $v['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
        <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
        <span class="inlblk"><?= $v['t'] ?></span>
    </a>
    <? endforeach; ?>
</div>
<? } else if($step == 2) { # ШАГ 2 ?>
<div class="f-index__mobile__subcategories">
    <div class="f-index__mobile__subcategories__title">
        <a href="<?= $parent['link'] ?>" class="img j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>,subs:1,title:'<?= HTML::escape($parent['title'], 'js') ?>'}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></a>
        <div class="subcat">
            <? if( $parent['main'] ) { ?>
            <a href="#" class="backto ajax j-back" data="{prev:0}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
            <? } else { ?>
            <a href="#" class="backto ajax j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
            <? } ?>
            <p class="title"><strong><?= $parent['title'] ?></strong></p>
        </div>
    </div>
    <div class="f-index__mobile__subcategories__list">
        <ul>
            <li><a href="<?= $parent['link'] ?>" class="all j-f-cat-phone-step2-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>,subs:1,title:'<?= HTML::escape($parent['title'], 'js') ?>'}"><?= _t('filter','Все подкатегории') ?>&nbsp;&raquo;</a></li>
            <? foreach($cats as $v): ?>
            <li><a href="<?= $v['l'] ?>" class="j-sub<? if($v['active']) { ?> active<? } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><?= $v['t'] ?></a></li>
            <? endforeach; ?>
        </ul>
    </div>
</div>
<? } ?>