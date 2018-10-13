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
    <div class="pull-right"><a class="close" href="javascript:void(0);"><i class="fa fa-times"></i></a></div>
    <div class="clearfix"></div>
</div>
<div class="f-msearch__categories__list">
    <ul>
        <? foreach($cats as $v){ ?>
        <li>
            <span data-link="<?= $v['l'] ?>" class="block hidden-link j-main" data="{id:<?= $v['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>',items:<?= intval($v['items']) ?>}">
                <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
                <span class="cat-name"><?= $v['t'] ?></span>
            </span>
        </li>
        <? } ?>
    </ul>
    <div class="clearfix"></div>
</div>
<? } else if($step == 2) { # ШАГ 2 ?>
<div class="f-msearch__categories__title">
    <div class="pull-left">
        <span data-link="<?= $parent['link'] ?>" class="img hidden-link j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>,subs:1,title:'<?= HTML::escape($parent['title'], 'js') ?>',items:<?= intval($parent['items']) ?>}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></span>
        <div class="subcat">
            <? if( $parent['main'] ) { ?>
            <a href="javascript:void(0);" class="backto block j-back" data="{prev:0}">&laquo; <?= _t('filter','Вернуться к основным категориям') ?></a>
            <? } else { ?>
            <a href="javascript:void(0);" class="backto block j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <?= _t('filter','Вернуться назад') ?></a>
            <? } ?>
            <p class="title"><strong><?= $parent['title'] ?></strong></p>
            <span class="count f11 hidden-phone">
                <? if ($parent['items'] > 0) { ?>
                    <a href="<?= $parent['link'] ?>" class="j-f-cat-desktop-step2-parent"><?= number_format($parent['items'], 0, '.', ' ') ?></a>&nbsp;<?= tpl::declension($parent['items'], _t('filter','объявление;объявления;объявлений'), false) ?>
                <?php } else { ?>
                    <span data-link="<?= $parent['link'] ?>" class="hidden-link j-f-cat-desktop-step2-parent"><?= number_format($parent['items'], 0, '.', ' ') ?></span>&nbsp;<?= tpl::declension($parent['items'], _t('filter','объявление;объявления;объявлений'), false) ?>
                <?php } ?>
            </span>
        </div>
    </div>
    <div class="pull-right"><a class="close" href="javascript:void(0);"><i class="fa fa-times"></i></a></div>
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
                        ?><li><a href="<?= $v['l'] ?>" class="j-sub<? if($v['active']) { ?> active<? } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,lvl:<?= $v['lvl'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>',items:<?= intval($v['items']) ?>}"><span class="cat-name"><?= $v['t'] ?></span><? if($v['subs']) { ?> &raquo;<? } ?></a></li><?
                    endforeach; ?>
                  </ul></li>
        <?  endforeach; ?>
    </ul>
    <div class="clearfix"></div>
</div>
<? } ?>