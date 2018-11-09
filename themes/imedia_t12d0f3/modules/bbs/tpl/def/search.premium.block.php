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
<div class="c-carousel c-carousel-imedia pdb35" >
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
                    <div class="slider-premium__bg-box">
                        <span style="background: url(<?= $v['img_m'] ?>);" class="slider-premium__bg"></span>
                    </div>
                    <div class="slider-premium__title" title="<?= $v['title'] ?>">
                        <?= $v['title'] ?>
                    </div>
                    <div class="">
                        <? $aAvarageItemRatingData = ['value' => $v['avarage_rating_value']]; ?>
                        <div style="margin-bottom: -10px;">
                            <?= _t('view', 'Средняя оценка:') ?>
                        </div>
                        <?= BBS::i()->viewPHP($aAvarageItemRatingData, 'item.rating.avarage'); ?>
                    </div>
                </div>
            </a>
            <? if($i == $count): ?>
                </div>
                <? $i = 0; ?>
            <? endif; ?>
        <? endforeach; ?>
        <? if ($i <= $count): ?>
<!--            </div>-->
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