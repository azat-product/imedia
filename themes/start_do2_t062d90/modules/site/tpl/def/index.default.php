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
          <?php if ($v['items'] > 0) { ?>
          <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" /></a>
          <?php } else { ?>
          <span data-link="<?= $v['l'] ?>" class="img hidden-link"><img src="<?= $v['i'] ?>" alt="<?= $v['t'] ?>" /></span>
          <?php } ?>
        </div>
        <div class="index-categories-item-content">
          <div class="index-categories-item-title">
            <?php if ($v['items'] > 0) { ?>
            <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
            <?php } else { ?>
            <span class="hidden-link" data-link="<?= $v['l'] ?>"><?= $v['t'] ?></span>
            <?php } ?>
            <span class="index-categories-item-title-count">(<?= $v['items'] ?>)</span>
          </div>
          <?php if($v['subn']): ?>
            <div class="index-categories-item-links">
              <?php $j = 0; foreach($v['sub'] as $vv) { ?>
                <?php if ($vv['items'] > 0) { ?>
                <a href="<?= $vv['l'] ?>"><?= $vv['t'] ?></a><?php if($j++ < $v['subv']) { echo '; '; } ?>
                <?php } else { ?>
                <span class="hidden-link" data-link="<?= $vv['l'] ?>"><?= $vv['t'] ?></span><?php if($j++ < $v['subv']) { echo '; '; } ?>
                <?php } } ?>
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