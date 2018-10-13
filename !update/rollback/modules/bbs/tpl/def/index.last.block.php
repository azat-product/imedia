<?php
/**
 * Блок объявлений на главной
 * @var $this BBS
 * @var $title string заголовок блока
 * @var $items array данные об объявлениях
 * @var $type string тип объявлений: 'last', 'premium'
 */
 $lng_fav_in = _te('bbs', 'Добавить в избранное');
 $lng_fav_out = _te('bbs', 'Удалить из избранного');
 $lng_quick = _t('bbs', 'срочно');
?>
<div class="index-latest" id="j-bbs-index-<?= $type ?>-block">
    <div class="index-latest__heading">
        <div class="customNavigation">
            <a class="prev j-prev"><i class="fa fa-chevron-left"></i></a>
            <a class="next j-next"><i class="fa fa-chevron-right"></i></a>
        </div>
        <h2><?= $title ?></h2>
    </div>
    <div class="sr-page__gallery sr-page__gallery_desktop">
        <div id="j-bbs-index-<?= $type ?>-carousel" class="thumbnails owl-carousel">
            <? foreach($items as $v): ?>
                <div class="sr-page__gallery__item index-latest__item thumbnail rel owl-item<? if($v['svc_marked']){ ?> selected<? } ?>">
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
            <? endforeach; ?>
        </div>
    </div>
</div>
<script type="text/javascript">
<?
    tpl::includeCSS('owl.carousel', true);
    tpl::includeJS('owl.carousel.min', false);
?>
<? js::start(); ?>
$(function(){
    var $block = $('#j-bbs-index-<?= $type ?>-block');

    var $carousel = $block.find('#j-bbs-index-<?= $type ?>-carousel');
    if ($carousel.length) {
        $carousel.owlCarousel({
            itemsCustom: [
                [320, 1],
                [600, 2],
                [767, 3],
                [991, 4]
            ]
        });
        // Custom Navigation Events
        $block.find('.j-next').click(function(){
            $carousel.trigger('owl.next');
        });
        $block.find('.j-prev').click(function(){
            $carousel.trigger('owl.prev');
        });
    }
});
<? js::stop(); ?>
</script>