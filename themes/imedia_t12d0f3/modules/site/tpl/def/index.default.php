<?php
/**
 * Главная страница: вид по-умолчанию (список категорий + подкатегорий)
 * @var $this Site
 * @var $cats array категории
 */
?>
<div class="index-categories">
  <div class="row">
    <?php $i = 0; foreach ($cats as $k=>$v) { ?>
    <div class="col-md-6">
      <div class="index-categories-item <?= $i%2 ?>">
        <div class="index-categories-item-img">
          <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt="" /></a>
        </div>
        <div class="index-categories-item-content">
          <div class="index-categories-item-title">
            <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
           
          </div>
          <?php if($v['subn']): ?>
            <div class="index-categories-item-links">
              <?php $j = 0; foreach($v['sub'] as $vv) { ?><a href="<?= $vv['l'] ?>"><?= $vv['t'] ?></a><?php if($j++ < $v['subv']) echo '; '; } ?>
              <?php if($v['subn'] > $v['subv']){ ?> ...<?php } ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php if($i++%2) { ?>
  </div>
  <div class="row">
    <?php } ?>
    <?php } ?>
  </div><!-- /.row -->
</div><!-- /.index-categories -->