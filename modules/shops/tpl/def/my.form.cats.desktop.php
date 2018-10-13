<?php
/**
 * Форма магазина: выбор категорий (desktop, tablet)
 * @var $this Shops
 * @var $step integer шаг
 * @var $cats array категории
 * @var $parent array данные о категории выше (для шага №2)
 */
?>
<? if($step == 1) { # ШАГ 1 ?>
<div class="i-formpage__catselect__popup__mainlist_desktop hidden-phone">
    <? foreach($cats as $v): ?>
        <a href="javascript:void(0);" class="j-main" data="{id:<?= $v['id'] ?>,pid:<?= $v['pid'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
            <img src="<?= $v['i'] ?>" alt="" />
            <div><?= $v['t'] ?></div>
        </a>
    <? endforeach; ?>
    <div class="clearfix"></div>
</div>
<? } else if($step == 2) { # ШАГ 2 ?>
<div class="i-formpage__catselect__popup__sublist_desktop hidden-phone">
    <div class="f-msearch__categories__title">
        <div class="pull-left">
            <a href="javascript:void(0);" class="img j-back" data="{prev:<?= $parent['pid'] ?>}"><img src="<?= $parent['icon'] ?>" alt="" /></a>
            <div class="subcat">
                <a href="javascript:void(0);" class="backto block j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <?= ( $parent['main'] ? _t('shops','Вернуться к основным категориям') : _t('shops','Вернуться назад') ) ?></a>
                <p class="title"><strong><?= $parent['title'] ?></strong></p>
            </div>
        </div>
        <div class="pull-right"><a class="close" href="javascript:void(0);">&times;</a></div>
        <div class="clearfix"></div>
    </div>
    <div class="f-msearch__subcategories__list">
        <ul>
            <?
            $cats = ( sizeof($cats) > 6 ? array_chunk($cats, round( sizeof($cats) / 2 ) ) : array($cats) );
            foreach($cats as $catsChunk):
                ?><li class="span<?= (sizeof($cats) == 2 ? '6' : '12') ?>"><ul><?
                    foreach($catsChunk as $v):
                        ?><li><a href="javascript:void(0);" class="j-sub<? if($v['active']) { ?> active<? } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><span><?= $v['t'] ?></span><? if($v['subs']) { ?> &raquo;<? } ?></a></li><?
                    endforeach; ?>
                  </ul></li>
            <?  endforeach; ?>
        </ul>
        <div class="clearfix"></div>
    </div>
</div>
<? } ?>