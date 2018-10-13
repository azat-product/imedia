<?php
/**
 * Форма магазина: выбор категорий (phone)
 * @var $this Shops
 * @var $step integer шаг
 * @var $cats array категории
 * @var $parent array данные о категории выше (для шага №2)
 */
?>
<? if($step == 1) { # ШАГ 1 ?>
<div class="i-formpage__catselect__popup__mainlist_mobile visible-phone">
    <?
    $i = sizeof($cats) - 1; $j = 0;
    foreach($cats as $v): ?>
    <a class="j-main<? if($j++ == $i){ ?> last<? } ?>" href="javascript:void(0);" data="{id:<?= $v['id'] ?>,pid:<?= $v['pid'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
        <img src="<?= $v['i'] ?>" alt="" />
        <span class="inlblk"><?= $v['t'] ?></span>
        <? if($v['subs'] > 0) { ?> <i class="fa fa-chevron-right pull-right"></i><? } ?>
    </a>
    <? endforeach; ?>
</div>
<? } else if($step == 2) { # ШАГ 2 ?>
<div class="i-formpage__catselect__popup__sublist_mobile visible-phone">
    <div class="f-index__mobile__subcategories__title">
        <a href="javascript:void(0);" class="img j-back" data="{prev:<?= $parent['pid'] ?>}"><img src="<?= $parent['icon'] ?>" alt="" /></a>
        <div class="subcat">
            <a href="javascript:void(0);" class="backto ajax j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <?= _t('shops','Вернуться назад') ?></a>
            <p class="title"><strong><?= $parent['title'] ?></strong></p>
        </div>
    </div>
    <div class="f-index__mobile__subcategories__list">
        <ul>
             <?
             $i = sizeof($cats) - 1; $j = 0;
             foreach($cats as $v): ?>
             <li><a href="javascript:void(0);" class="j-sub<? if($j++ == $i){ ?> last<? } if($v['active']) { ?> active<? } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><?= $v['t'] ?><? if($v['subs'] > 0) { ?> <i class="icon-chevron-right pull-right"></i><? } ?></a></li>
             <? endforeach; ?>
        </ul>
    </div>
</div>
<? } ?>