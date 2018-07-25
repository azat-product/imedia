<?php
/**
 * Список объявлений: вид галерея
 * @var $this BBS
 * @var $item array данные объявления
 */
?>
<div class="sr-gallery-item<?php if ($item['svc_marked']){ ?> selected<?php } ?>">
  <?php if ($item['fav']) { ?>
    <a href="javascript:void(0);" class="c-fav sr-gallery-item-fav active j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= _te('bbs', 'Удалить из избранного') ?>"><i class="fa fa-star j-i-fav-icon"></i></a>
  <?php } else { ?>
    <a href="javascript:void(0);" class="c-fav sr-gallery-item-fav j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= _te('bbs', 'Добавить в избранное') ?>"><i class="fa fa-star-o j-i-fav-icon"></i></a>
  <?php } ?>
  <div class="sr-gallery-item-img">
    <a href="<?= $item['link'] ?>" title="<?= $item['title'] ?>" class="sr-glItem-img<?php if ($item['imgs'] > 1) { ?> sr-glItem-img_multiple<?php } ?>">
      <img src="<?= $item['img_m'] ?>" alt="<?= $item['title'] ?>" />
    </a>
  </div>
  <div class="sr-gallery-item-content">
    <div class="sr-gallery-item-heading">
      <?php if ($item['svc_quick']) { ?><span class="label label-warning"><?= _t('bbs', 'срочно') ?></span><?php } ?>
      <h3 class="sr-gallery-item-heading-title"><a href="<?= $item['link'] ?>"><?= $item['title'] ?></a></h3>
    </div>
    
    <div class="c-price sr-gallery-item-price">
      <?php if ($item['price_on']) { ?>
          <?= $item['price'] ?>
          <span class="c-price-sub"><?= $item['price_mod'] ?></span>
      <?php } ?>
    </div>
    <div class="sr-glItem-subtext">
      <span class="sr-glItem-subtext-i"><?= $item['cat_title'] ?></span>
      <span class="sr-glItem-subtext-i"><?php if ( ! empty($item['city_title'])): ?><i class="fa fa-map-marker"></i> <?= $item['city_title'] ?><?= ! empty($item['district_title']) ? ', '.$item['district_title'] : ''?><?php endif; ?></span>
    </div>
  </div>
</div>