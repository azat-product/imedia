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
<div id="j-f-region-desktop-popup" class="dropdown-menu l-regions">
  <div id="j-f-region-desktop-st2">
    <div class="dropdown-menu-heading">
      <?= _t('filter', 'Искать объявления по <a [attr]>всему региону</a>', array('attr'=>'href="'.$region['link'].'" class="j-f-region-desktop-st2-region" data="{id:0,pid:0,title:\''.HTML::escape(_t('filter', 'Все регионы'),'js').'\'}"')) ?>
    </div>
    <div class="l-regions-content">
      <?php
      $break_column = $in_col;
      $i = 0; $col_i = 1;
      ?><div class="l-regions-content-column"><?php
      foreach($cities as $letter=>$v)
      {
        letter:
        if($i == $break_column) { $col_i++;
          ?></div><div class="l-regions-content-column"><?php
          if ($col_i < $cols) $break_column += $in_col;
        }
        ?>
        <ul class="l-regions-list">
          <li class="l-regions-list-letter"><?= $letter ?></li><?php
        $v_cnt = count($v);
        while(list($k,$vv) = each($v)) {
          ?><li class="<?php if($vv['main'] > 0) { ?>main <?php } ?><?php if($vv['active']) { ?>active<?php } ?>"><a href="<?= $vv['link'] ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $region['id'] ?>}" title="<?= $vv['title'] ?>"><span><?= $vv['title'] ?></span></a></li><?php
          if( ++$i == $break_column && key($v) !== NULL ) { ?></ul><?php goto letter; }
        } ?>
      </ul><?php
    }
    ?>
  </div>
    </div>

</div>
</div>