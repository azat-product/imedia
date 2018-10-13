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
<? if($step == 1) { # ШАГ 1 ?>
<div class="f-msearch__categories__title">
    <div class="pull-left">
        <p class="title"><strong><?= _t('shops','Выберите категорию') ?></strong></p>
        <span class="count f12"><? echo ( $total>=1000 ? number_format($total, 0, '', ' ') : $total), '&nbsp;', tpl::declension($total, $lang_shops, false) ?> - <a href="<?= Shops::url('search') ?>" class="j-all" data="{id:0,pid:0,title:'<?= HTML::escape(_t('shops','Все категории'), 'js') ?>'}"><?= _t('filter','смотреть все магазины') ?> &raquo;</a></span>
    </div>
    <div class="pull-right"><a class="close" href="javascript:void(0);"><i class="fa fa-times"></i></a></div>
    <div class="clearfix"></div>
</div>
<div class="f-msearch__categories__list">
    <ul>
        <? foreach($cats as $v): ?>
        <li>
            <a href="<?= $v['l'] ?>" class="block j-main" data="{id:<?= $v['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
                <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
                <div class="cat-name"><?= $v['t'] ?></div>
            </a>
        </li>
        <? endforeach; ?>
    </ul>
    <div class="clearfix"></div>
</div>
<? } else if($step == 2) { # ШАГ 2 ?>
<div class="f-msearch__categories__title">
    <div class="pull-left">
        <a href="<?= $parent['link'] ?>" class="img j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>,subs:1,title:'<?= HTML::escape($parent['title'], 'js') ?>'}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></a>
        <div class="subcat">
            <? if( $parent['main'] ) { ?>
            <a href="javascript:void(0);" class="backto block j-back" data="{prev:0}">&laquo; <?= _t('filter','Вернуться к основным категориям') ?></a>
            <? } else { ?>
            <a href="javascript:void(0);" class="backto block j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
            <? } ?>
            <p class="title"><strong><?= $parent['title'] ?></strong></p>
            <span class="count f11 hidden-phone"><? echo '<a href="'.$parent['link'].'" data="{id:'.$parent['id'].',pid:'.$parent['pid'].',subs:1,title:\''.HTML::escape($parent['title'], 'js').'\'}" class="j-parent">'.number_format($parent['shops'], 0, '.', ' ').'</a>&nbsp;', tpl::declension($parent['shops'], $lang_shops, false) ?></span>
        </div>
    </div>
    <div class="pull-right"><a class="close" href="javascript:void(0);"><i class="fa fa-times"></i></a></div>
    <div class="clearfix"></div>
</div>
<div class="f-msearch__subcategories__list">
    <ul>
        <?
            $cats = ( sizeof($cats) > 6 ? array_chunk($cats, round( sizeof($cats) / 2 ) ) : array($cats) );
            foreach($cats as $catsChunk):
                ?><li class="span<?= (sizeof($cats) == 2 ? '6' : '12') ?>"><ul><?
                    foreach($catsChunk as $v):
                        ?><li><a href="<?= $v['l'] ?>" class="j-sub<? if($v['active']) { ?> active<? } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= ($cut2levels ? 0 : $v['subs']) ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><span class="cat-name"><?= $v['t'] ?></span><? if($v['subs'] && ! $cut2levels) { ?> &raquo;<? } ?></a></li><?
                    endforeach; ?>
                  </ul></li>
        <?  endforeach; ?>
    </ul>
    <div class="clearfix"></div>
</div>
<? } ?>