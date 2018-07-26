<?php
/**
 * Блок премиум объявлений
 * @var $this BBS
 * @var $items array объявления
 */

tpl::includeCSS('slick', true);
tpl::includeJS('slick.min', false);
$count = 36;
if(DEVICE_PHONE) {
    $count = 9;
}
?>
<div class="c-carousel" >
    <div class="c-carousel-heading">

        <h2><?= _t('search', 'Премиум объявления'); ?></h2>
    </div>
    <div id="j-bbs-index-premium-carousel" class="mrgt20">
        <? $i = 0; ?>
        <? foreach ($items as $v): ?>
            <? if($i == 0): ?>
                <div class="slider-premium">
            <? endif; ?>
            <? $i++; ?>

            <a href="<?= $v['link'] ?>" class="slider-premium__item">
                <span class="slider-premium__img" style="background: url(<?= $v['img_m'] ?>);"></span>
                <div class="slider-premium__info">
                    <div class="slider-premium__title" title="<?= $v['title'] ?>">
                        <?= $v['title'] ?>
                    </div>
                    <span style="background: url(<?= $v['img_m'] ?>);" class="slider-premium__bg"></span>
                        <div class="slider-premium__price">
                            <span>
                                <?php if ( ! empty($v['city_title'])): ?>
                                    <i class="fa fa-map-marker"></i>
                                    <?= $v['city_title'] ?>
                                    <?= ! empty($v['district_title']) ? ', '.$v['district_title'] : ''?>
                                <?php endif; ?>
                            </span>
                        <span>
                            <?php if ($v['price_on']) : ?>
                                <?= $v['price'] ?>
                                <?= $v['price_mod'] ?>
                            <? endif; ?>
                        </span>
                    </div>
                </div>
            </a>
            <? if($i == $count): ?>
                </div>
                <? $i = 0; ?>
            <? endif; ?>
        <? endforeach; ?>
        <? if ($i <= $count - 1): ?>
            </div>
        <? endif; ?>
    </div>
</div>

<script type="text/javascript">
    <?php js::start(); ?>
    $(function(){
        $('#j-bbs-index-premium-carousel').slick({
            adaptiveHeight: true,
            prevArrow: '<i class="fa fa-chevron-left arrow-slider" aria-hidden="true"></i>\n',
            nextArrow: '<i class="fa fa-chevron-right arrow-slider" aria-hidden="true"></i>\n'
        });
    });
    <?php js::stop(); ?>
</script>
<?/*
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
          <div class="sr-glItem-subtext">
              <span class="sr-glItem-subtext-i">
                  <?= $v['cat_title'] ?>
              </span>
              <span class="sr-glItem-subtext-i">
                  <? if ( ! empty($v['city_title'])): ?>
                      <i class="fa fa-map-marker"></i> <?= $v['city_title'] ?>
                    <?= ! empty($v['district_title']) ? ', '.$v['district_title'] : ''?>
                  <? endif; ?>
              </span>
          </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>*/?>