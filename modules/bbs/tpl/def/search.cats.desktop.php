<?php
    /**
     * Поиск объявлений: фильтр категорий (desktop, tablet)
     * @var $this BBS
     * @var $step integer шаг
     * @var $cats array категории
     * @var $parent array данные о категории выше (шаг №2)
     * @var $total integer общее кол-во объявлений (шаг №1)
     */
?>
<? if($step == 1) { # ШАГ 1 ?>
<div class="f-msearch__categories__title">
    <div class="pull-left">
        <p class="title"><strong><?= _t('filter','Выберите категорию') ?></strong></p>
        <span class="count f12"><?= number_format($total, 0, '.', ' ') ?> <?= tpl::declension($total, _t('filter','объявление;объявления;объявлений'), false) ?> - <a href="<?= BBS::url('items.search') ?>" class="j-all" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter','Все категории'), 'js') ?>'}"><?= _t('filter','смотреть все объявления') ?> &raquo;</a></span>
    </div>
    <div class="pull-right"><a class="close" href="#"><i class="fa fa-times"></i></a></div>
    <div class="clearfix"></div>
</div>
<div class="f-msearch__categories__list">
    <ul>
        <? foreach($cats as $v){ ?>
        <li>
            <a href="<?= $v['l'] ?>" class="block j-main" data="{id:<?= $v['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
                <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
                <div class="cat-name"><?= $v['t'] ?></div>
            </a>
        </li>
        <? } ?>
    </ul>
    <div class="clearfix"></div>
</div>
<? } else if($step == 2) { # ШАГ 2 ?>
<div class="f-msearch__categories__title">
    <div class="pull-left">
        <a href="<?= $parent['link'] ?>" class="img j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>,subs:1,title:'<?= HTML::escape($parent['title'], 'js') ?>'}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></a>
        <div class="subcat">
            <? if( $parent['main'] ) { ?>
            <a href="#" class="backto block j-back" data="{prev:0}">&laquo; <?= _t('filter','Вернуться к основным категориям') ?></a>
            <? } else { ?>
            <a href="#" class="backto block j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
            <? } ?>
            <p class="title"><strong><?= $parent['title'] ?></strong></p>
            <span class="count f11 hidden-phone"><? echo '<a href="'.$parent['link'].'" data="{id:'.$parent['id'].',pid:'.$parent['pid'].',subs:1,title:\''.HTML::escape($parent['title'], 'js').'\'}" class="j-f-cat-desktop-step2-parent">'.number_format($parent['items'], 0, '.', ' ').'</a>&nbsp;', tpl::declension($parent['items'], _t('filter','объявление;объявления;объявлений'), false) ?></span>
        </div>
    </div>
    <div class="pull-right"><a class="close" href="#"><i class="fa fa-times"></i></a></div>
    <div class="clearfix"></div>
</div>
<div class="f-msearch__subcategories__list">
    <ul>
        <?
            $cols = 1; $colsClass = 12; $catsTotal = sizeof($cats);
            foreach (array(24=>1,25=>2,60=>3,300=>4) as $k=>$v) {
                if ($catsTotal<=$k) { $cols = $v; $colsClass = (12 / $v); break; }
            }
            $cats = ( $cols > 1 ? array_chunk($cats, ceil( $catsTotal / $cols ) ) : array($cats) );
            foreach($cats as $catsChunk):
                ?><li class="span<?= $colsClass ?>"><ul><?
                    foreach($catsChunk as $v):
                        ?><li><a href="<?= $v['l'] ?>" class="j-sub<? if($v['active']) { ?> active<? } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,lvl:<?= $v['lvl'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><span class="cat-name"><?= $v['t'] ?></span><? if($v['subs']) { ?> &raquo;<? } ?></a></li><?
                    endforeach; ?>
                  </ul></li>
        <?  endforeach; ?>
    </ul>
    <div class="clearfix"></div>
</div>
<? } ?>