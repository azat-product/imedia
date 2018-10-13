<?php
/**
 * Поиск объявлений: список (desktop, tablet)
 * @var $this BBS
 * @var $list_type integer тип списка BBS::LIST_TYPE_
 * @var $items array объявления
 * @var $showBanners boolean выводить баннеры в списке
 */

$lng_fav_in = _te('bbs', 'Добавить в избранное');
$lng_fav_out = _te('bbs', 'Удалить из избранного');
$lng_photo = _te('bbs', 'фото');
$lng_quick = _te('bbs', 'срочно');

$listBanner = function($listPosition) use ($showBanners) {
    if ($showBanners) {
        $html = Banners::view('bbs_search_list', array('list_pos' => $listPosition));
        if ($html) {
            return '<div class="sr-page__list__item">'.$html.'</div> <div class="spacer"></div>';
        }
    }
    return '';
};

if($list_type == BBS::LIST_TYPE_LIST) { ?>
<div class="sr-page__list sr-page__list_desktop hidden-phone">
    <? $n = 1;
    foreach($items as &$v) { ?><?= $listBanner($n++); ?>
    <div class="sr-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?>">
        <table>
            <tr>
                <td class="sr-page__list__item_date hidden-tablet">
                    <span><?= $v['publicated'] ?></span>
                    <? if($v['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <? } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <? } ?>
                </td>
                <td class="sr-page__list__item_img">
                    <? if( $v['imgs'] ) { ?>
                    <span class="rel inlblk">
                        <a class="thumb stack rel inlblk" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                            <img class="rel br2 zi3 shadow" src="<?= $v['img_s'] ?>" alt="<?= $v['title'] ?>" />
                            <? if($v['imgs'] > 1) { ?>
                            <span class="abs border b2 shadow">&nbsp;</span>
                            <span class="abs border r2 shadow">&nbsp;</span>
                            <? } ?>
                        </a>
                    </span>
                    <? } else { ?>
                    <a class="thumb stack rel inlblk" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                        <img class="rel br2 zi3 shadow" src="<?= $v['img_s'] ?>" alt="<?= $v['title'] ?>" />
                    </a>
	                <? } ?>
                </td>
                <td class="sr-page__list__item_descr">
                    <h3><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></h3>
                    <p><small><?= $v['cat_title'] ?></small></p>
                    <? if( ! empty($v['descr_list'])): ?><small><?= $v['descr_list'] ?></small><? endif; ?>
                    <? if( ! empty($v['city_title'])): ?><small><i class="fa fa-map-marker"></i> <?= $v['city_title'] ?><?= ! empty($v['district_title']) ? ', '.$v['district_title'] : ''?></small><? endif; ?>
                </td>
                <td class="sr-page__list__item_price">
                    <? if($v['price_on']) { ?>
                        <?if ($v['price']) { ?><strong><?= $v['price'] ?></strong><? } ?>
                        <?if ($v['price_mod']) { ?><small><?= $v['price_mod'] ?></small><? } ?>
                    <? } ?>
                    <div class="visible-tablet pull-right">
                        <? if($v['fav']) { ?>
                        <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                        <? } else { ?>
                        <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                        <? } ?>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="spacer"></div>
    <? } unset($v); ?>
    <?= $last = $listBanner(Banners::LIST_POS_LAST); ?>
    <?= ! $last ? $listBanner($n) : '' ?>
</div>
<? } else if($list_type == BBS::LIST_TYPE_GALLERY) { ?>
<div class="sr-page__gallery sr-page__gallery_desktop hidden-phone">
<?  $i = 1;
    foreach($items as &$v) {
    if( $i == 1 ) { ?><div class="thumbnails"><? } ?>
        <div class="sr-page__gallery__item thumbnail rel span4<? if($v['svc_marked']){ ?> selected<? } ?>">
            <? if($v['fav']) { ?>
            <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
            <? } else { ?>
            <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
            <? } ?>
            <div class="sr-page__gallery__item_img align-center">
                <a class="thumb stack rel inlblk" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                    <img class="rel br2 zi3 shadow" src="<?= $v['img_m'] ?>" alt="<?= $v['title'] ?>" />
                    <? if($v['imgs'] > 1) { ?>
                    <span class="abs border b2 shadow">&nbsp;</span>
                    <span class="abs border r2 shadow">&nbsp;</span>
                    <? } ?>
                </a>
            </div>
            <div class="sr-page__gallery__item_descr">
                <h4><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></h4>
                <p class="sr-page__gallery__item_price">
                    <? if($v['price_on']) { ?>
                        <strong><?= $v['price'] ?></strong>
                        <small><?= $v['price_mod'] ?></small>
                    <? } ?>
                </p>
                <p><small>
                    <?= $v['cat_title'] ?><br />
                    <? if( ! empty($v['city_title'])): ?><i class="fa fa-map-marker"></i> <?= $v['city_title'] ?><?= ! empty($v['district_title']) ? ', '.$v['district_title'] : ''?><? endif; ?>
                </small></p>
            </div>
        </div>
        <? if( $i++ == 3 ) { ?></div><? $i = 1; } else { ?><div class="spacer"></div><? }
       } unset($v);
       if( $i!=1 ) { ?></div><? }
    ?>
</div>
<? } else if($list_type == BBS::LIST_TYPE_MAP) {
    if (!config::get('bbs-map-vertical', false)) {
 if (!Request::isAJAX()) { ?>
<div class="sr-page__map sr-page__map_desktop rel">
    <div class="row-fluid">
        <div class="sr-page__map__list span5 j-maplist" style="height: 500px; overflow: auto;">
<? } ?>
        <? foreach ($items as $k=>&$v) { ?>
        <div class="sr-page__map__list__item<? if($v['svc_marked']){ ?> selected<? } ?> rel j-maplist-item" data-index="<?= $k ?>">
            <span class="num"><?= $v['num'] ?>.</span>
            <? if ($v['imgs']) { ?><a class="thumb-preview<? if($v['imgs']>1) { ?> thumb-preview_multi<? } ?>" href="<?= $v['link'] ?>"><span class="thumb-preview_cover"><?= $v['imgs'] ?></span><?= $lng_photo ?></a><? } ?>
            <? if ($v['fav']) { ?>
            <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
            <? } else { ?>
            <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
            <? } ?>
            <h5><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></h5>
            <? if($v['price_on']) { ?>
            <p class="sr-page__gallery__item_price">
                <strong><?= $v['price'] ?></strong>
                <small><?= $v['price_mod'] ?></small>
            </p>
            <? } ?>
            <p>
                <small>
                    <?= $v['cat_title'] ?><br />
                    <i class="fa fa-map-marker"></i> <?= $v['city_title'] ?><?= ! empty($v['district_title']) ? ', '.$v['district_title'] : '' ?>, <?= $v['addr_addr'] ?>
                </small>
            </p>
        </div>
        <? } unset($v); ?>
<? if (!Request::isAJAX()) { ?>
        </div>
        <div class="sr-page__map_ymap span7 j-map">
            <div class="sr-page__map__controls">
                <span id="j-search-map-toggler"><span class="j-search-map-toggler-arrow">&laquo;</span> <a href="#" class="ajax j-search-map-toggler-link"><?= _t('search', 'больше карты'); ?></a></span>
            </div>
            <div style="height: 500px; width: 100%;" class="j-search-map-desktop j-search-map-tablet"></div>
        </div>
    </div>
    <div class="row-fluid hide">
        <div class="sr-page__map_tablet_listarrow span5 visible-tablet">
            <a href="#" class="sr-page__map__list__item_down block " >
                <i class="fa fa-chevron-down"></i>
            </a>
        </div>
    </div>
</div>
<? } } else { ?>
<? if( ! Request::isAJAX() ) { ?>
        <div class="hidden-phone sr-page__map_ymap j-map">
            <div style="height: 500px; width: 100%;" class="j-search-map-desktop j-search-map-tablet"></div>
        </div>
        <div class="hidden-phone j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>"><div class="j-maplist">
<? } ?>
    <div class="sr-page__list sr-page__list_desktop sr-page__list_map">
        <? $n = 1;
        foreach($items as $k=> &$v) { ?><?= $listBanner($n++); ?>
        <div class="sr-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?> j-maplist-item" data-index="<?= $k ?>">
            <table>
                <tr>
                    <td class="sr-page__list__item_date hidden-tablet">
                        <span><?= $v['publicated'] ?></span>
                        <? if($v['fav']) { ?>
                        <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                        <? } else { ?>
                        <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                        <? } ?>
                    </td>
                    <td class="sr-page__list__item_img">
                        <? if( $v['imgs'] ) { ?>
                        <span class="rel inlblk">
                            <a class="thumb stack rel inlblk" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                                <img class="rel br2 zi3 shadow" src="<?= $v['img_s'] ?>" alt="<?= $v['title'] ?>" />
                                <? if($v['imgs'] > 1) { ?>
                                <span class="abs border b2 shadow">&nbsp;</span>
                                <span class="abs border r2 shadow">&nbsp;</span>
                                <? } ?>
                            </a>
                        </span>
                        <? } ?>
                    </td>
                    <td class="sr-page__list__item_descr">
                        <h3><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></h3>
                        <p><small>
                            <?= $v['cat_title'] ?><br />
                            <? if( ! empty($v['city_title'])): ?><i class="fa fa-map-marker"></i> <?= $v['city_title'] ?><?= ! empty($v['district_title']) ? ', '.$v['district_title'] : ''?><? endif; ?>
                        </small></p>
                    </td>
                    <td class="sr-page__list__item_price">
                        <? if($v['price_on']) { ?>
                            <?if ($v['price']) { ?><strong><?= $v['price'] ?></strong><? } ?>
                            <?if ($v['price_mod']) { ?><small><?= $v['price_mod'] ?></small><? } ?>
                        <? } ?>
                        <div class="visible-tablet pull-right">
                            <? if($v['fav']) { ?>
                            <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                            <? } else { ?>
                            <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                            <? } ?>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="spacer"></div>
        <? } unset($v); ?>
        <?= $last = $listBanner(Banners::LIST_POS_LAST); ?>
        <?= ! $last ? $listBanner($n) : '' ?>
    </div>
    <? if( ! Request::isAJAX() ) { ?></div></div><? } ?>
<? } }