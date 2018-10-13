<?php
/**
 * Поиск объявлений: список (phone)
 * @var $this BBS
 * @var $list_type integer тип списка BBS::LIST_TYPE_
 * @var $items array объявления
 * @var $showBanners boolean выводить баннеры в списке
 */

$lng_fav_in = _te('bbs', 'Добавить в избранное');
$lng_fav_out = _te('bbs', 'Удалить из избранного');
$lng_quick = _t('bbs', 'срочно');

$listBanner = function($listPosition) use ($showBanners) {
    if ($showBanners) {
        $html = Banners::view('bbs_search_list_mobile', array('list_pos' => $listPosition));
        if ($html) {
            return '<div class="m-banner">'.$html.'</div>';
        }
    }
    return '';
};

# Список:
if ($list_type == BBS::LIST_TYPE_LIST) { ?>
<div class="sr-page__list sr-page__list_mobile visible-phone">
    <? $n = 1;
    foreach($items as &$v) { ?><?= $listBanner($n++); ?>
    <div class="sr-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?>">
        <table>
            <tr>
                <td colspan="2" class="sr-page__list__item_descr">
                    <div class="sr-page__list__item_title"><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></div>
                    <? if($v['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <? } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <? } ?>
                </td>
            </tr>
            <tr>
                <td class="sr-page__list__item_date">
                <?php if ($v['publicated_up']): ?>
                    <span class="ajax j-tooltip" data-toggle="tooltip" data-container="body" data-placement="bottom" data-html="true" data-original-title="<div class='text-left'><?= _te('search', 'Обновлено: [date]', ['date'=>$v['publicated_last']]); ?></div> <div class='text-left'><?= _te('search', 'Размещено: [date]', ['date'=>$v['publicated']]); ?></div>"><?= $v['publicated_last'] ?></span>
                <?php else: ?>
                    <?= $v['publicated'] ?>
                <?php endif; ?>
                </td>
                <td class="sr-page__list__item_price">
                    <? if($v['price_on']) { ?>
                        <?if ($v['price']) { ?><strong><?= $v['price'] ?></strong><? } ?>
                        <?if ($v['price_mod']) { ?><small><?= $v['price_mod'] ?></small><? } ?>
                    <? } ?>
                </td>
            </tr>
        </table>
    </div>
    <? } unset($v); ?>
    <?= $last = $listBanner(Banners::LIST_POS_LAST); ?>
    <?= ! $last ? $listBanner($n) : '' ?>
</div>
<? }

# Галерея:
else if($list_type == BBS::LIST_TYPE_GALLERY) { ?>
<div class="sr-page__gallery sr-page__gallery_mobile visible-phone">
<?  $i = 1;
    foreach($items as &$v) {
    if( $i == 1 ) { ?><div class="thumbnails"><? } ?>
        <div class="sr-page__gallery__item thumbnail span4<? if($v['svc_marked']){ ?> selected<? } ?>">
            <div class="sr-page__gallery__item_img align-center">
                <a class="thumb stack rel inlblk" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                    <img class="rel br2 zi3 shadow" src="<?= $v['img_m'] ?>" alt="<?= $v['title'] ?>">
                    <? if($v['imgs'] > 1) { ?>
                    <span class="abs border b2 shadow">&nbsp;</span>
                    <span class="abs border r2 shadow">&nbsp;</span>
                    <? } ?>
                </a>
            </div>
            <div class="sr-page__gallery__item_descr">
                <div class="sr-page__gallery__item_title">
                    <? if($v['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <? } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <? } ?>
                    <? if($v['svc_quick']) { ?><span class="label label-warning"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
                </div>
                <p class="sr-page__gallery__item_price">
                    <? if($v['price_on']) { ?>
                        <strong><?= $v['price'] ?></strong>
                        <small><?= $v['price_mod'] ?></small>
                    <? } ?>
                </p>
            </div>
        </div>
        <? if( $i++ == 3 ) { ?></div><? $i = 1; } else { ?><div class="spacer"></div><? }
       } unset($v);
       if( $i!=1 ) { ?></div><? }
    ?>
</div>
<? }

# Карта:
else if ($list_type == BBS::LIST_TYPE_MAP) {
    if (!BBS::filterVertical()) { ?>
    <? if ($mapBlock) { ?>
    <div class="sr-page__map sr-page__map_mobile visible-phone">
        <div class="sr-page__map_ymap span12">
            <div class="j-search-map-phone" style="height: 300px; width: 100%;"></div>
        </div>
    </div>
    <div class="sr-page__list sr-page__list_mobile j-maplist visible-phone">
    <? } ?>
    <? foreach($items as &$v) { ?>
    <div class="sr-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?>">
        <table>
            <tr>
                <td colspan="2" class="sr-page__list__item_descr">
                    <div class="sr-page__list__item_title"><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></div>
                    <? if($v['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <? } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <? } ?>
                </td>
            </tr>
            <tr>
                <td class="sr-page__list__item_date"><?= $v['publicated'] ?></td>
                <td class="sr-page__list__item_price">
                    <? if($v['price_on']) { ?>
                        <?if ($v['price']) { ?><strong><?= $v['price'] ?></strong><? } ?>
                        <?if ($v['price_mod']) { ?><small><?= $v['price_mod'] ?></small><? } ?>
                    <? } ?>
                </td>
            </tr>
        </table>
    </div>
    <? } unset($v); ?>
    <? if ($mapBlock) { ?>
    </div>
    <? } ?>
<? } else { ?>
    <? if ($mapBlock) { ?>
        <div class="visible-phone sr-page__map_ymap j-map">
            <div style="height: 300px; width: 100%;" class="j-search-map-phone"></div>
        </div>
        <div class="visible-phone j-list-<?= bff::DEVICE_PHONE ?>"><div class="j-maplist">
    <? } ?>
    <div class="sr-page__list sr-page__list_mobile visible-phone">
        <? foreach($items as &$v) { ?>
        <div class="sr-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?>">
            <table>
                <tr>
                    <td colspan="2" class="sr-page__list__item_descr">
                        <div class="sr-page__list__item_title"><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></div>
                        <? if($v['fav']) { ?>
                        <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                        <? } else { ?>
                        <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                        <? } ?>
                    </td>
                </tr>
                <tr>
                    <td class="sr-page__list__item_date"><?= $v['publicated'] ?></td>
                    <td class="sr-page__list__item_price">
                        <? if($v['price_on']) { ?>
                            <?if ($v['price']) { ?><strong><?= $v['price'] ?></strong><? } ?>
                            <?if ($v['price_mod']) { ?><small><?= $v['price_mod'] ?></small><? } ?>
                        <? } ?>
                    </td>
                </tr>
            </table>
        </div>
        <? } unset($v); ?>
    </div>
    <? if ($mapBlock) { ?></div></div><? } ?>
<? } }