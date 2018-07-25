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
    <div class="index-banner">
      <h2 class="index-banner-title"><?= _t('site','Быстро, легко и бесплатно') ?></h2>
      <div class="index-banner-btn">
        <a href="<?= BBS::url('item.add') ?>" class="btn btn-success"><i class="fa fa-plus"></i> <?= _t('site','Подать объявление') ?></a>
      </div>
      <ul class="index-banner-list">
        <li><?= _t('site','Бесплатно и <strong>без регистрации</strong>') ?></li>
        <li><?= _t('site','До <strong>[photo_ctn] фотографий</strong> в объявлении',array('photo_ctn' => BBS::itemsImagesLimit())) ?></li>
        <?php if (!BBS::formPublicationPeriod()) { ?>
        <li><?= _t('site','Активно до <strong>[expire]</strong>',array('expire' => tpl::declension(config::get('bbs_item_publication_period', 30, TYPE_UINT),_t('','день;дня;дней')))) ?></li>
        <?php } ?>
      </ul>
    </div>
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
