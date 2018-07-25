<?php
/**
 * Фильтр по региону: Выбор города (desktop)
 * @var $this Geo
 * @var $cities array список городов
 * @var $total integer кол-во городов
 * @var $link_all string ссылка на все города
 */
?>
<? if ($total <= 10) { ?>
<div id="j-f-region-desktop-popup" class="f-navigation__region_change dropdown-block box-shadow abs hide" style="width:180px; left:10px;">
    <div id="j-f-region-desktop-st1" class="f-navigation__region_change_main hide" style="width: 700px;"></div>
    <div id="j-f-region-desktop-st2" class="f-navigation__region_change_sub hidden-phone" style="width: 700px;">

    <fieldset class="row-fluid">
        <div class="span12">
            <a href="<?= $link_all ?>" class="j-f-region-desktop-st2-region" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter', 'Все регионы'), 'js') ?>'}"><?= _t('filter', 'Все города') ?></a>
            <hr />
        </div>
    </fieldset>
    <ul class="f-navigation__region_change__links row-fluid">
        <? foreach($cities as $v) { ?>
            <li><a href="<?= $v['link'] ?>" class="<? if($v['active']) { ?>active<? } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $v['pid'] ?>}" title="<?= $v['title'] ?>"><span><?= $v['title'] ?></span></a></li>
        <? } ?>
    </ul>
    <div class="clearfix"></div>

    </div>
</div>
<? } else { ?>
<div id="j-f-region-desktop-popup" class="f-navigation__region_change dropdown-block box-shadow abs hide">
    <div id="j-f-region-desktop-st1" class="f-navigation__region_change_main hide" style="width: 700px;"></div>
    <div id="j-f-region-desktop-st2" class="f-navigation__region_change_sub hidden-phone" style="width: 700px;">

    <fieldset class="row-fluid">
        <div class="span12">
            <?= _t('filter', 'Искать объявления во <a [attr]>всех городах</a>', array('attr'=>'href="'.$link_all.'" class="j-f-region-desktop-st2-region" data="{id:0,pid:0,title:\''.HTML::escape(_t('filter', 'Все регионы'), 'js').'\'}"')) ?>
        </div>
    </fieldset>
    <ul class="f-navigation__region_change__links row-fluid">
    <?
    $break_column = $in_col;
    $i = 0; $col_i = 1;
    ?><li class="pull-left part <?= $cols_class ?>"><?
    foreach($cities_letters as $letter=>$v)
    {
    letter:
        if($i == $break_column) { $col_i++;
            ?></li><li class="pull-left part <?= $cols_class ?>"><?
            if ($col_i < $cols) $break_column += $in_col;
        }
        ?><ul class="rel">
            <li class="abs letter"><?= $letter ?></li><?
            $v_cnt = count($v);
            while(list($k,$vv) = each($v)) {
                ?><li><a href="<?= $vv['link'] ?>" class="<? if($vv['active']) { ?>active<? } ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $vv['pid'] ?>}" title="<?= $vv['title'] ?>"><span><?= $vv['title'] ?></span></a></li><?
                if( ++$i == $break_column && key($v) !== NULL ) { ?></ul><? goto letter; }
            } ?>
          </ul><?
    }
    ?>
    </li>
    </ul>
    <div class="clearfix"></div>

    </div>
</div>
<? } ?>