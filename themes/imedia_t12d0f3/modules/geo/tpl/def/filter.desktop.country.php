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
  <div class="l-regions-content f-navigation__region_change__links">
    <?php
    $break_column = $in_col;
    $i = 0; $col_i = 1;
    ?><div class="l-regions-content-column"><?php
    foreach($regions as $letter=>$v)
    {
      letter1:
      if($i == $break_column) { $col_i++;
        ?></div><div class="l-regions-content-column"><?php
        if ($col_i < $cols) $break_column += $in_col;
      } ?>
      <ul class="l-regions-list">
        <li class="l-regions-list-letter"><?= $letter ?></li><?php
        while(list($k,$vv) = each($v)) {
          ?><li><a title="<?= $vv['title'] ?>" href="<?= $vv['link'] ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $vv['pid'] ?>,key:'<?= $vv['keyword'] ?>'}"><span><?= $vv['title'] ?></span></a></li><?php
          if( ++$i == $break_column && key($v) !== NULL ) { ?></ul><?php goto letter1; }
        } ?>
      </ul><?php
    }
    ?>
  </div></div><?php
}

# Выбор города
else if ($step == 2)
{
  if(empty($noregions)){
    ?>
    <div class="l-regions-heading">
      <div class="l-regions-heading-left">
        <h3 class="l-regions-heading-title"><?= $region['title'] ?></h3>
      </div>
      <div class="l-regions-heading-right">
        <span class="l-regions-heading-right-item">
          <?= _t('filter', 'Искать объявления по <a [attr]>всему региону</a>', array('attr'=>'href="'.$region['link'].'" class="j-f-region-desktop-st2-region" data="{id:'.$region['id'].',pid:0,type:\'region\',title:\''.HTML::escape($region['title'], 'js').'\'}"')) ?>
        </span>
        <span class="l-regions-heading-right-item">
          <a href="#" class="link-ajax j-f-region-desktop-back"><span><?= _t('filter','Изменить регион') ?></span></a>
        </span>
      </div>
    </div>
    <?php } ?>
    <div class="l-regions-content f-navigation__region_change__links">
      <?php
      $break_column = $in_col;
      $i = 0; $col_i = 1;
      ?><div class="l-regions-content-column"><?php
      foreach($cities as $letter=>$v)
      {
        letter2:
        if($i == $break_column) { $col_i++;
          ?></div><div class="l-regions-content-column"><?php
          if ($col_i < $cols) $break_column += $in_col;
        }
        ?><ul class="l-regions-list">
        <li class="l-regions-list-letter"><?= $letter ?></li><?php
        while(list($k,$vv) = each($v)) {
          ?><li><a href="<?= $vv['link'] ?>" class="<?php if($vv['main'] > 0) { ?>l-regions-list-item-main <?php } ?><?php if($vv['active']) { ?>active<?php } ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $region['id'] ?>}" title="<?= $vv['title'] ?>"><span><?= $vv['title'] ?></span></a></li><?php
          if( ++$i == $break_column && key($v) !== NULL ) { ?></ul><?php goto letter2; }
        } ?>
      </ul><?php
    }
    ?>
  </div>
</div>
<div class="clearfix"></div><?php
}