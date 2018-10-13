<?php
/**
 * Блок объявлений на главной
 * @var $this BBS
 * @var $title string заголовок блока
 * @var $items array данные об объявлениях
 * @var $type string тип объявлений: 'last', 'premium'
 */
 $lng_fav_in  = _te('bbs', 'Добавить в избранное');
 $lng_fav_out = _te('bbs', 'Удалить из избранного');
 $lng_quick   = _t('bbs', 'срочно');
 $fav_active_url = bff::url('/img/fav-active.svg');
?>
<div class="index-latest" id="j-bbs-index-<?= $type ?>-block">
    <div class="index__heading">
        <div class="customNavigation">
            <a class="prev j-prev"><i class="fa fa-chevron-left"></i></a>
            <a class="next j-next"><i class="fa fa-chevron-right"></i></a>
        </div>
        <div class="l-content__title"><?= $title ?></div>
    </div>
    <div class="spacer"></div>
    <div class="sr-page__gallery sr-page__gallery_desktop">
        <div id="j-bbs-index-<?= $type ?>-carousel" class="thumbnails owl-carousel">
            <?php foreach($items as &$item) {
                echo $this->searchListItemBlock($item, BBS::LIST_TYPE_GALLERY, ['attr' => ['class' => 'owl-item index-latest__item']]);
            } unset($item); ?>
        </div>
    </div>
</div>
<script type="text/javascript">
<?php
    tpl::includeCSS('owl.carousel', true);
    tpl::includeJS('owl.carousel.min', false);
?>
<?php js::start(); ?>
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
        $block.find('.j-next').on('click',function(){
            $carousel.trigger('owl.next');
        });
        $block.find('.j-prev').on('click',function(){
            $carousel.trigger('owl.prev');
        });
    }
});
<?php js::stop(); ?>
</script>