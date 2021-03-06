<?php
/**
 * Список объявлений: вид строчный список
 * @var $this BBS
 * @var $item array данные объявления
 */
?>
<div class="sr-list-item<?php if ($item['svc_marked']){ ?> selected<?php } ?>">
  <div class="sr-list-item-left">
    <div class="sr-list-item-date c-date">
      <?= $item['publicated'] ?>
    </div>
    <?php if ($item['fav']) { ?>
        <a href="javascript:void(0);" class="c-fav sr-list-item-fav active j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= _te('bbs', 'Удалить из избранного') ?>"><i class="fa fa-star j-i-fav-icon"></i></a>
    <?php } else { ?>
        <a href="javascript:void(0);" class="c-fav sr-list-item-fav j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= _te('bbs', 'Добавить в избранное') ?>"><i class="fa fa-star-o j-i-fav-icon"></i></a>
    <?php } ?>
  </div>
  <div class="sr-list-item-img">
    <a href="<?= $item['link'] ?>" title="<?= $item['title'] ?>" class="sr-glItem-img<?php if ($item['imgs'] > 1) { ?> sr-glItem-img_multiple<?php } ?>">
      <img src="<?= $item['img_s'] ?>" alt="<?= $item['title'] ?>" />
    </a>
  </div>
  <div class="sr-list-item-content">
    <div class="sr-list-item-heading">
      <?php if ($item['svc_quick']) { ?><span class="label label-warning"><?= _t('bbs', 'срочно') ?></span><?php } ?>
      <div class="sr-list-item-heading-title"><a href="<?= $item['link'] ?>"><?= $item['title'] ?></a></div>
    </div>
    <div class="sr-glItem-subtext">
      <span class="sr-glItem-subtext-i"><?= $item['cat_title'] ?></span>
      <?php if( ! empty($item['descr_list'])): ?>
        <?= $item['descr_list'] ?>
      <?php endif; ?>
      <span class="sr-glItem-subtext-i"><?php if ( ! empty($item['city_title'])): ?><i class="fa fa-map-marker"></i> <?= $item['city_title'] ?><?= ! empty($item['district_title']) ? ', '.$item['district_title'] : ''?><?php endif; ?></span>
    </div>

  </div>
  <div class="list-item-right">
    <div class="c-price sr-list-item-price">
      <?php if ($item['price_on']) { ?>
          <?= $item['price'] ?>
          <div class="c-price-sub"><?= $item['price_mod'] ?></div>
      <?php } ?>
    </div>
  </div>
</div>