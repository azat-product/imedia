<?php
/**
 * Блок категорий на главной (phone)
 * @var $step integer шаг выбора
 * @var $cats array категории
 * @var $parent array данные о категории выше (для шага №2)
 */

?>
<? if($step == 1) { # ШАГ 1 ?>
<div class="f-index__mobile__categories">
    <? $i=0; foreach($cats as $v): ?>
    <a class="block j-main<? if(++$i == sizeof($cats)) { ?> last<? } ?>" href="<?= $v['l'] ?>" data="{id:<?= $v['id'] ?>,pid:0,subs:<?= $v['subs'] ?>}" title="<?= $v['t'] ?>">
        <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
        <span class="inlblk"><?= $v['t'] ?></span>
        <i class="fa fa-chevron-right pull-right"></i>
    </a>
    <? endforeach; ?>
</div>
<? } else if($step == 2) { # ШАГ 2 ?>
<div class="f-index__mobile__subcategories">
    <div class="f-index__mobile__subcategories__title">
        <a href="<?= $parent['link'] ?>" class="img j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></a>
        <div class="subcat">
            <a href="#" class="backto block j-back" data="{prev:<?= ( $parent['main'] ? 0 : $parent['pid'] ) ?>}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
            <p class="title"><strong><?= $parent['title'] ?></strong></p>
        </div>
    </div>
    <div class="f-index__mobile__subcategories__list">
        <ul>
            <li><a href="<?= $parent['link'] ?>" class="all j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>}"><?= _t('filter', 'Все подкатегории') ?>&nbsp;&raquo;</a></li>
            <? foreach($cats as $v): ?>
            <li>
                <a href="<?= $v['l'] ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}" class="j-sub" title="<?= $v['t'] ?>"><?= $v['t'] ?> <i class="fa fa-chevron-right pull-right"></i></a>
            </li>
            <? endforeach; ?>
        </ul>
    </div>
</div>
<? } ?>