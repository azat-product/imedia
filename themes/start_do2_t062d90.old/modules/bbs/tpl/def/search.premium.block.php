<?php
/**
 * Блок премиум объявлений
 * @var $this BBS
 * @var $items array объявления
 */
?>
<div class="sr-vip">
  <div class="sr-vip-title"><?= _t('search', 'Премиум объявления'); ?></div>
  <ul class="sr-vip-content">
    <?php foreach($items as $v): ?>
      <li>
        <a href="<?= $v['link'] ?>">
          <?php if( ! empty($v['img_m'])): ?><div class="sr-vip-content-img"><img src="<?= $v['img_m'] ?>" alt="<?= $v['title'] ?>"></div><?php endif; ?>
          <div class="sr-vip-content-title"><?= $v['title'] ?></div>
          <div class="sr-vip-content-price">
            <?php if($v['price_on']) { ?>
            <strong><?= $v['price'] ?></strong>
            <small><?= $v['price_mod'] ?></small>
            <?php } ?>
          </div>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>