<?php
/**
 * Список объявлений: вид галерея
 * @var $this BBS
 * @var $item array данные объявления
 * @var $opts array доп. параметры
 */
 $lng_quick = _te('bbs', 'срочно');
 $lng_fav_in = _te('bbs', 'Добавить в избранное');
 $lng_fav_out = _te('bbs', 'Удалить из избранного');

 \HTML::attributeAdd($opts['attr'], 'class', 'sr-page__gallery__item thumbnail rel span4');
 if ($item['svc_marked']) {
     \HTML::attributeAdd($opts['attr'], 'class', 'selected');
 }

 $address = [];
 if ( ! empty($item['city_title'])) {
    $address[] = $item['city_title'];
 }
 if ( ! empty($item['district_title'])) {
    $address[] = $item['district_title'];
 }
 if ( ! empty($opts['showAddr']) && ! empty($item['addr_addr'])) {
    $address[] = $item['addr_addr'];
 }
?>

<div<?= \HTML::attributes($opts['attr']); ?>>
    <?php if($item['fav']) { ?>
    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
    <?php } else { ?>
    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
    <?php } ?>
    <div class="sr-page__gallery__item_img align-center">
        <a class="thumb stack rel inlblk" href="<?= $item['link'] ?>" title="<?= $item['title'] ?>">
            <img class="rel br2 zi3 shadow" src="<?= $item['img_m'] ?>" alt="<?= $item['title'] ?>" />
            <?php if ($item['imgs'] > 1) { ?>
            <span class="abs border b2 shadow">&nbsp;</span>
            <span class="abs border r2 shadow">&nbsp;</span>
            <?php } ?>
        </a>
    </div>
    <div class="sr-page__gallery__item_descr">
        <div class="sr-page__gallery__item_title"><?php if($item['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<?php } ?><a href="<?= $item['link'] ?>"><?= $item['title'] ?></a></div>
        <p class="sr-page__gallery__item_price">
            <?php if ($item['price_on']) { ?>
                <strong><?= $item['price'] ?></strong>
                <small><?= $item['price_mod'] ?></small>
            <?php } ?>
        </p>
        <p><small>
            <?= $item['cat_title'] ?><br />
            <?php if( ! empty($address)): ?><i class="fa fa-map-marker"></i> <?= join(', ', $address); ?><?php endif; ?>
        </small></p>
    </div>
</div>