<?php
/**
 * Блок перелинковки
 * @var $this BBS
 * @var $cats array категории
 * @var $regs array регионы
 */
?>
<ul class="sr-breakList hidden-xs" data-tabs="tabs">
  <?php foreach($cats as $k => $v): ?>
  <li><a href="#categories-tab-<?= $k ?>" data-toggle="tab"><?= $v['t'] ?></a></li>
<?php endforeach; ?>
</ul>

<div class="sr-breakList-content tab-content hidden-xs">
  <?php foreach($cats as $k => $v): ?>
  <div class="tab-pane" id="categories-tab-<?= $k ?>">
    <strong><?= $v['t'] ?></strong>
    <?php $links = array(); foreach($v['data'] as $vv){ $links[] = '<a href="'.$vv['link'].'">'.$vv['title'].'</a>'; } ?>
    <?= join(', ', $links); ?>
  </div>
<?php endforeach; ?>
</div>

<?php $showAll = false; if( ! empty($regs) || ! empty($crumb)): ?>
<div class="sr-bottomLocations hidden-xs">
  <?php if( ! empty($crumb)): ?>
  <ul class="sr-bottomLocations-path">
    <?php foreach($crumb as $v): ?>
    <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<?php if( ! empty($regs)):
$cols = array(); $i = 0;
foreach($regs as $v){
  $hide = '';
  if(isset($cols[$i]) && count($cols[$i]) >= 5){
    $hide =  ' class="hide"';
    $showAll = true;
  }
  $cols[$i][] = '<li'.$hide.'><a href="'.$v['link'].'"><span>'.$v['title'].'</span></a>&nbsp;('.$v['items'].')</li> ';
  $i++; if($i >= 5){ $i = 0; }
} ?>
<div class="sr-bottomLocations-list" id="j-bottomLocations">
  <?php foreach($cols as $v): ?>
  <ul class="sr-bottomLocations-list-col">
    <?= join("\n", $v) ?>
  </ul>
<?php endforeach; ?>
</div>
<?php if($showAll): ?><a href="#" class="ajax pseudo-link-ajax" id="j-bottomLocations-show"><?= _t('bbs', 'Все...'); ?></a><?php endif; ?>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if($showAll): ?>
<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    $('#j-bottomLocations-show').click(function(e){
      e.preventDefault();
      $('#j-bottomLocations ul li').removeClass('hide');
      $(this).addClass('hide');
    });
  });
  <?php js::stop(); ?>
</script>
<?php endif; ?>