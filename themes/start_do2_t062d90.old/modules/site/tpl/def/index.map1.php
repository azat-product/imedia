<?php
/**
 * Главная страница
 * @var $this Site
 * @var $titleh1 string заголовок H1
 * @var $centerBlock string центральный блок
 * @var $last string блок последних / премиум объявлений (HTML)
 * @var $seotext string SEO-текст
 */
?>

<div class="row">
  <div class="col-md-8">
    <div class="row">
      <?php $catCnt = 0; foreach ($cats as $k=>$v) { ?>
      <div class="col-sm-6">
        <div class="index-categories-item <?= $catCnt%2 ?>">
          <div class="index-categories-item-img">
            <a href="<?= $v['l'] ?>" class="img"><img src="<?= $v['i'] ?>" alt="" /></a>
          </div>
          <div class="index-categories-item-content">
            <div class="index-categories-item-title">
              <a href="<?= $v['l'] ?>"><?= $v['t'] ?></a>
              <span class="index-categories-item-title-count">(<?= $v['items'] ?>)</span>
            </div>
          </div>
        </div>
      </div>
      <?php if($catCnt++%2) { ?>
    </div>
    <div class="row">
      <?php } ?>
      <?php } ?>
    </div><!-- /.row -->
  </div>
  <div class="col-md-4">
    <?php if (empty($map)) { ?>
      <div class="index-map__nomap"><?= _t('site','Для данного региона карта еще недоступна.') ?></div>
    <?php } else { ?>
      <div class="index-map index-map__ukr hidden-xs mrgt15">
        <?= $map ?>
      </div>
    <?php } ?>
    <?php if (!empty($regions)) { ?>
      <ul class="index-cities">
        <?php $i = 0;
        foreach ($regions as $k => $reg) {
          if($reg['numlevel'] != Geo::lvlCity) continue;
          if (++$i > 8) break; ?>
          <li><a href="<?= $reg['l'] ?>"><strong><?= $reg['title'] ?></strong></a></li>
          <?php unset($regions[$k]);
        } ?>
      </ul>
      <?php if($catCnt > 8): $catCnt -= 8; $catCnt = ceil($catCnt / 2) * 8; $i = 0; ?>
      <ul class="index-cities">
        <?php foreach ($regions as $k => $reg) {
          if($reg['numlevel'] != Geo::lvlCity) continue;
          if(++$i > $catCnt) break; ?>
          <li><a href="<?= $reg['l'] ?>"><?= $reg['title'] ?></a></li>
        <?php } ?>
      </ul>
      <?php endif; ?>
    <?php } ?>
  </div>
</div>
