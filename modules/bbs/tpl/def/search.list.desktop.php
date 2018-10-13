<?php
/**
 * Поиск объявлений: список (desktop, tablet)
 * @var $this BBS
 * @var $list_type integer тип списка BBS::LIST_TYPE_
 * @var $items array объявления
 * @var $showBanners boolean выводить баннеры в списке
 * @var $mapBlock boolean отрисовывать блок карты или только список
 * @var $mapVertical boolean вертикальный вид отображения карты
 */

$lng_fav_in  = _te('bbs', 'Добавить в избранное');
$lng_fav_out = _te('bbs', 'Удалить из избранного');
$lng_photo   = _te('bbs', 'фото');
$lng_fixed   = _te('bbs', 'топ');
$lng_quick   = _te('bbs', 'срочно');
$fav_active_url  = bff::url('/img/fav-active.svg');

$listBanner = function($listPosition) use ($showBanners) {
    if ($showBanners) {
        $html = Banners::view('bbs_search_list', array('list_pos' => $listPosition));
        if ($html) {
            return '<div class="sr-page__list__item">'.$html.'</div> <div class="spacer"></div>';
        }
    }
    return '';
};

# Список:
if ($list_type == BBS::LIST_TYPE_LIST) { ?>
<div class="sr-page__list sr-page__list_desktop hidden-phone">
    <?php $n = 1;
    foreach($items as &$item) { ?>

    <?= $listBanner($n++); ?>

    <?= $this->searchListItemBlock($item, $list_type); ?>

    <?php } unset($item); ?>
    <?= $last = $listBanner(Banners::LIST_POS_LAST); ?>
    <?= ! $last ? $listBanner($n) : '' ?>
</div>
<?php }

# Галерея:
else if($list_type == BBS::LIST_TYPE_GALLERY) { ?>
<div class="sr-page__gallery sr-page__gallery_desktop hidden-phone">
<?php $i = 1;
    foreach($items as &$item) {
    if ($i == 1) { ?><div class="thumbnails"><?php } ?>

       <?= $this->searchListItemBlock($item, $list_type); ?>

       <?php if( $i++ == 3 ) { ?></div><?php $i = 1; } else { ?><div class="spacer"></div><?php }
       } unset($item);
       if( $i!=1 ) { ?></div><?php }
    ?>
</div>
<?php }

# Карта:
else if($list_type == BBS::LIST_TYPE_MAP) {
    if ( ! $mapVertical) {
 if ($mapBlock) { ?>
<div class="sr-page__map sr-page__map_desktop rel">
    <div class="row-fluid">
        <div class="sr-page__map__list span5 j-maplist" style="height: 500px; overflow: auto;">
<?php } ?>
        <?php foreach ($items as $k=>&$item) { ?>
        <div class="sr-page__map__list__item<?php if($item['svc_marked']){ ?> selected<?php } ?> rel j-maplist-item" data-index="<?= $k ?>">
            <span class="num"><?= $item['num'] ?>.</span>
            <?php if ($item['imgs']) { ?><a class="thumb-preview<?php if($item['imgs']>1) { ?> thumb-preview_multi<?php } ?>" href="<?= $item['link'] ?>"><span class="thumb-preview_cover"><?= $item['imgs'] ?></span><?= $lng_photo ?></a><?php } ?>
            <?php if ($item['fav']) { ?>
            <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
            <?php } else { ?>
            <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $item['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
            <?php } ?>
            <div class="sr-page__map__list__item_title"><?php if($item['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<?php } ?><a href="<?= $item['link'] ?>"><?= $item['title'] ?></a></div>
            <?php if($item['price_on']) { ?>
            <p class="sr-page__gallery__item_price">
                <strong><?= $item['price'] ?></strong>
                <small><?= $item['price_mod'] ?></small>
            </p>
            <?php } ?>
            <p>
                <small>
                    <?= $item['cat_title'] ?><br />
                    <i class="fa fa-map-marker"></i> <?= $item['city_title'] ?><?= ! empty($item['district_title']) ? ', '.$item['district_title'] : '' ?>, <?= $item['addr_addr'] ?>
                </small>
            </p>
        </div>
        <?php } unset($item); ?>
<?php if ($mapBlock) { ?>
        </div>
        <div class="sr-page__map_ymap span7 j-map">
            <div class="sr-page__map__controls">
                <span id="j-search-map-toggler"><span class="j-search-map-toggler-arrow">&laquo;</span> <a href="javascript:void(0);" class="ajax j-search-map-toggler-link"><?= _t('search', 'больше карты'); ?></a></span>
            </div>
            <div style="height: 500px; width: 100%;" class="j-search-map-desktop j-search-map-tablet"></div>
        </div>
    </div>
    <div class="row-fluid hide">
        <div class="sr-page__map_tablet_listarrow span5 visible-tablet">
            <a href="javascript:void(0);" class="sr-page__map__list__item_down block " >
                <i class="fa fa-chevron-down"></i>
            </a>
        </div>
    </div>
</div>
<?php } } else { ?>
<?php if($mapBlock) { ?>
        <div class="hidden-phone sr-page__map_ymap j-map">
            <div style="height: 500px; width: 100%;" class="j-search-map-desktop j-search-map-tablet"></div>
        </div>
        <div class="hidden-phone j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>"><div class="j-maplist">
<?php } ?>
    <div class="sr-page__list sr-page__list_desktop sr-page__list_map">
        <?php $n = 1;
        foreach($items as $k=> &$item) { ?>

        <?= $listBanner($n++); ?>

        <?= $this->searchListItemBlock($item, BBS::LIST_TYPE_LIST, ['attr'=>['class'=>'j-maplist-item', 'data-index'=>$k]]); ?>

        <div class="spacer"></div>
        <?php } unset($item); ?>
        <?= $last = $listBanner(Banners::LIST_POS_LAST); ?>
        <?= ! $last ? $listBanner($n) : '' ?>
    </div>
    <?php if ($mapBlock) { ?></div></div><?php } ?>
<?php } }