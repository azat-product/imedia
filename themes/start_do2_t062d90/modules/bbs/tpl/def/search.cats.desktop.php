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
<?php if($step == 1) { # STEP 1 ?>
<div class="l-categories">
  <div class="dropdown-menu-heading">
    <a class="dropdown-menu-close close" href="#"><i class="fa fa-times"></i></a>
    <div class="dropdown-menu-heading-title"><?= _t('filter','Выберите категорию') ?></div>
    <?php if (DEVICE_DESKTOP) { ?>
    <span><?= number_format($total, 0, '.', ' ') ?> <?= tpl::declension($total, _t('filter','объявление;объявления;объявлений'), false) ?> - <a href="<?= BBS::url('items.search') ?>" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter','Все категории'), 'js') ?>'}"><?= _t('filter','смотреть все объявления') ?> &raquo;</a></span>
    <?php } ?>
  </div>
  <div class="l-categories-wrap">
    <ul class="l-categories-items">
      <?php if (DEVICE_PHONE) { ?>
      <li>
        <a href="<?= BBS::url('items.search') ?>" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter','Все категории'), 'js') ?>'}">
          <?= _t('filter','смотреть все объявления') ?>
        </a>
      </li>
      <?php } ?>
      <?php foreach($cats as $v){ ?>
      <li>
        <a href="<?= $v['l'] ?>" class="j-main" data="{id:<?= $v['id'] ?>,subs:<?= $v['subs'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}">
          <span class="l-categories-items-i-img">
            <img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" />
          </span>
          <span class="l-categories-items-i-name"><?= $v['t'] ?></span>
        </a>
      </li>
      <?php } ?>
    </ul>
  </div>
</div>
<?php } else if($step == 2) { # STEP 2 ?>
<div class="l-categories">
  <div class="dropdown-menu-heading">
    <a class="dropdown-menu-close close" href="#"><i class="fa fa-times"></i></a>
    <div class="dropdown-menu-heading-container">
      <?php if (DEVICE_DESKTOP) { ?>
      <div class="dropdown-menu-heading-container-img">
        <a href="<?= $parent['link'] ?>" class="img j-parent" data="{id:<?= $parent['id'] ?>,pid:<?= $parent['pid'] ?>,subs:1,title:'<?= HTML::escape($parent['title'], 'js') ?>'}"><img src="<?= $parent['icon'] ?>" alt="<?= $parent['title'] ?>" /></a>
      </div>
      <?php } ?>
      <div class="dropdown-menu-heading-container-content">
        <span>
          <?php if( $parent['main'] ) { ?>
          <a href="#" class="link-ajax j-back" data="{prev:0}">
            &laquo; <span><?= _t('filter','Вернуться к основным категориям') ?></span>
          </a>
          <?php } else { ?>
          <a href="#" class="link-ajax j-back" data="{prev:<?= $parent['pid'] ?>}">&laquo; <span><?= _t('filter','Вернуться назад') ?></span></a>
          <?php } ?>
        </span>
        <div class="dropdown-menu-heading-title"><?= $parent['title'] ?></div>
        <span><?php echo '<a href="'.$parent['link'].'" data="{id:'.$parent['id'].',pid:'.$parent['pid'].',subs:1,title:\''.HTML::escape($parent['title'], 'js').'\'}" class="j-f-cat-desktop-step2-parent">'.number_format($parent['items'], 0, '.', ' ').'</a>&nbsp;', tpl::declension($parent['items'], _t('filter','объявление;объявления;объявлений'), false) ?></span>
      </div>
    </div>
  </div>
  <div class="l-categories-list-wrapper">
    <?php
      $cols = 1; $colsClass = 12; $catsTotal = sizeof($cats);
      foreach (array(24=>1,25=>2,60=>3,300=>4) as $k=>$v) {
        if ($catsTotal<=$k) { $cols = $v; $colsClass = (12 / $v); break; }
      }
      $cats = ( $cols > 1 ? array_chunk($cats, ceil( $catsTotal / $cols ) ) : array($cats) );
      foreach($cats as $catsChunk):
        ?><div class="l-categories-list-wrapper-in"><ul class="l-categories-list"><?php
      foreach($catsChunk as $v):
        ?><li><a href="<?= $v['l'] ?>" class="j-sub<?php if($v['active']) { ?> active<?php } ?>" data="{id:<?= $v['id'] ?>,pid:<?= $parent['id'] ?>,subs:<?= $v['subs'] ?>,lvl:<?= $v['lvl'] ?>,title:'<?= HTML::escape($v['t'], 'js') ?>'}"><span class="cat-name"><?= $v['t'] ?></span><?php if($v['subs']) { ?> &raquo;<?php } ?></a></li><?php
      endforeach; ?>
    </ul></div>
    <?php  endforeach; ?>
  </div>
</div>
<?php } ?>