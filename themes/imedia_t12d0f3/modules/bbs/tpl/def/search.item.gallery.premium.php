<?php
/**
 * Список объявлений: вид галерея
 * @var $this BBS
 * @var $item array данные объявления
 */
?>

<?/**/?>
<a href="<?= $item['link'] ?>" class="slider-premium__item">
    <span class="slider-premium__img" style="background: url(<?= $item['img_m'] ?>);"></span>
    <div class="slider-premium__info">
        <div class="slider-premium__price">
            <span>
                <?php if ( ! empty($item['city_title'])): ?>
                    <i class="fa fa-map-marker"></i>
                    <?= $item['city_title'] ?>
                    <?= ! empty($item['district_title']) ? ', '.$item['district_title'] : ''?>
                <?php endif; ?>
            </span>
            <span>
                <?php if ($item['price_on']) : ?>
                    <?= $item['price'] ?>
                    <?= $item['price_mod'] ?>
                <? endif; ?>
            </span>
        </div>
        <div class="slider-premium__bg-box">
            <span style="background: url(<?= $item['img_m'] ?>);" class="slider-premium__bg"></span>
        </div>
        <div class="slider-premium__title" title="<?= $item['title'] ?>">
            <?= $item['title'] ?>
        </div>

        <div class="">
            <? $aAvarageItemRatingData = ['value' => $item['avarage_rating_value']]; ?>
            <div style="margin-bottom: -10px;">
                <?= _t('view', 'Средняя оценка:') ?>
            </div>
            <?= BBS::i()->viewPHP($aAvarageItemRatingData, 'item.rating.avarage'); ?>
        </div>
    </div>
</a>
