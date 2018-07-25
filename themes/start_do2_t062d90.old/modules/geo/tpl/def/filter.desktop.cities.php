<?php
/**
 * Фильтр по региону: Выбор города (desktop)
 * @var $this Geo
 * @var $cities array список городов
 * @var $total integer кол-во городов
 * @var $link_all string ссылка на все города
 */
?>
<?php if ($total <= 10) { ?>
<div id="j-f-region-desktop-popup" class="dropdown-menu l-regions">
  <div id="j-f-region-desktop-st2">
    <ul class="l-regions-list l-regions-list_noletter">
      <li><a href="<?= $link_all ?>" class="j-f-region-desktop-st2-region" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter', 'Все регионы'), 'js') ?>'}"><?= _t('filter', 'Все города') ?></a></li>
      <?php foreach($cities as $v) { ?>
      <li class="<?php if($v['active']) { ?>active<?php } ?>"><a href="<?= $v['link'] ?>" data="{id:<?= $v['id'] ?>,pid:<?= $v['pid'] ?>}" title="<?= $v['title'] ?>"><?= $v['title'] ?></a></li>
      <?php } ?>
    </ul>
  </div>
</div>
<?php } else { ?>
<div id="j-f-region-desktop-popup" class="dropdown-menu l-regions">
  <div id="j-f-region-desktop-st2">
    <div class="dropdown-menu-heading">
      <?= _t('filter', 'Искать объявления во <a [attr]>всех городах</a>', array('attr'=>'href="'.$link_all.'" class="j-f-region-desktop-st2-region" data="{id:0,pid:0,title:\''.HTML::escape(_t('filter', 'Все регионы'), 'js').'\'}"')) ?>
    </div>
    <div class="l-regions-content">
      <?php
      $break_column = $in_col;
      $i = 0; $col_i = 1;
      ?><div class="l-regions-content-column"><?php
      foreach($cities_letters as $letter=>$v)
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
          ?><li class="<?php if($vv['main'] > 0) { ?>main <?php } ?><?php if($vv['active']) { ?>active<?php } ?>"><a href="<?= $vv['link'] ?>" data="{id:<?= $vv['id'] ?>,pid:<?= $vv['pid'] ?>}" title="<?= $vv['title'] ?>"><?= $vv['title'] ?></a></li><?php
          if( ++$i == $break_column && key($v) !== NULL ) { ?></ul><?php goto letter; }
        } ?>
      </ul><?php
    }
    ?>
  </div></div>

</div>
</div>
<?php } ?>