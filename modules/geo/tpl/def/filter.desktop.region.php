<?php
/**
 * Фильтр по региону: выбора городов области (desktop)
 * @var $this Geo
 * @var $cities array список городов
 * @var $region array данные об области
 * @var $cols integer кол-во столбцов
 * @var $in_col integer кол-во записей в столбце
 */
# Выбор городов региона
?>
<div id="j-f-region-desktop-popup" class="f-navigation__region_change dropdown-block box-shadow abs hide">
    <div id="j-f-region-desktop-st1" class="f-navigation__region_change_main hide" style="width: 700px;"></div>
    <div id="j-f-region-desktop-st2" class="f-navigation__region_change_sub hidden-phone" style="width: 700px;">

    <fieldset class="row-fluid">
        <div class="span12">
            <?= _t('filter', 'Искать объявления по <a [attr]>всему региону</a>', array('attr'=>'href="'.$region['link'].'" class="j-f-region-desktop-st2-region" data="{id:0,pid:0,title:\''.HTML::escape(_t('filter', 'Все регионы'),'js').'\'}"')) ?>
        </div>
    </fieldset>
    <hr />
    <ul class="f-navigation__region_change__links row-fluid">
    <?
    $break_column = $in_col;
    $i = 0; $col_i = 1;
    ?><li class="pull-left part span3"><?
    foreach($cities as $letter=>$v)
    {
    letter:
        if($i == $break_column) { $col_i++;
            ?></li><li class="pull-left part span3"><?
            if ($col_i < $cols) $break_column += $in_col;
        }
        ?><ul class="rel">
            <li class="abs letter"><?= $letter ?></li><?
            $v_cnt = count($v);
            while(list($k,$vv) = each($v)) {
                ?><li><a href="<?= $vv['link'] ?>" class="<? if($vv['main'] > 0) { ?>main <? } ?><? if($vv['active']) { ?>active<? } ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $region['id'] ?>}" title="<?= $vv['title'] ?>"><span><?= $vv['title'] ?></span></a></li><?
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