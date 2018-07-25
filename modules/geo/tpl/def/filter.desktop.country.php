<?php
/**
 * Фильтр по региону: Шаг после выбора страны - выбор области/городов (desktop)
 * @var $this Geo
 * @var $step integer шаг
 * @var $regions array список областей (для списка областей)
 * @var $cities array список городов (для списка городов)
 * @var $region array данные об области (для списка городов)
 * @var $cols integer кол-во столбцов
 * @var $in_col integer кол-во записей в столбце
 */

$spanClass = array(1=>'span12', 2=>'span6', 3=>'span4', 4=>'span3', 5=>'span3', 6=>'span2');
$spanClass = ( isset($spanClass[$cols]) ? $spanClass[$cols] : 'span3' );

# Выбор области(региона)
if ($step == 1)
{
    ?>
    <ul class="f-navigation__region_change__links row-fluid">
    <?
    $break_column = $in_col;
    $i = 0; $col_i = 1;
    ?><li class="pull-left part <?= $spanClass ?>"><?
    foreach($regions as $letter=>$v)
    {
    letter1:
        if($i == $break_column) { $col_i++;
            ?></li><li class="pull-left part <?= $spanClass ?>"><?
            if ($col_i < $cols) $break_column += $in_col;
        } ?>
        <ul class="rel">
            <li class="abs letter"><?= $letter ?></li><?
        while(list($k,$vv) = each($v)) {
            ?><li><a title="<?= $vv['title'] ?>" href="<?= $vv['link'] ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $vv['pid'] ?>,key:'<?= $vv['keyword'] ?>'}"><span><?= $vv['title'] ?></span></a></li><?
            if( ++$i == $break_column && key($v) !== NULL ) { ?></ul><? goto letter1; }
        } ?>
        </ul><?
    }
    ?>
    </li></ul><?
}

# Выбор города
else if ($step == 2)
{
    if(empty($noregions)){
    ?>
    <fieldset class="row-fluid">
        <div class="span9">
            <b><?= $region['title'] ?></b><br />
            <?= _t('filter', 'Искать объявления по <a [attr]>всему региону</a>', array('attr'=>'href="'.$region['link'].'" class="j-f-region-desktop-st2-region" data="{id:'.$region['id'].',pid:0,type:\'region\',title:\''.HTML::escape($region['title'], 'js').'\'}"')) ?>
        </div>
        <div class="span3"><a href="#" class="ajax change pull-right j-f-region-desktop-back"><?= _t('filter','Изменить регион') ?></a></div>
    </fieldset>
    <hr />
    <? } ?>
    <ul class="f-navigation__region_change__links row-fluid">
    <?
    $break_column = $in_col;
    $i = 0; $col_i = 1;
    ?><li class="pull-left part <?= $spanClass ?>"><?
    foreach($cities as $letter=>$v)
    {
    letter2:
        if($i == $break_column) { $col_i++;
            ?></li><li class="pull-left part <?= $spanClass ?>"><?
            if ($col_i < $cols) $break_column += $in_col;
        }
        ?><ul class="rel">
            <li class="abs letter"><?= $letter ?></li><?
            while(list($k,$vv) = each($v)) {
                ?><li><a href="<?= $vv['link'] ?>" class="<? if($vv['main'] > 0) { ?>main <? } ?><? if($vv['active']) { ?>active<? } ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $region['id'] ?>}" title="<?= $vv['title'] ?>"><span><?= $vv['title'] ?></span></a></li><?
                if( ++$i == $break_column && key($v) !== NULL ) { ?></ul><? goto letter2; }
            } ?>
          </ul><?
    }
    ?>
    </li>
    </ul>
    <div class="clearfix"></div><?
}