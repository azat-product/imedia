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
<div class="c-carousel" id="j-bbs-index-<?= $type ?>-block">
  <div class="c-carousel-heading">
    <div class="c-carousel-heading-nav">
      <span class="c-carousel-arrow c-carousel-heading-nav-arrow j-prev"><i class="fa fa-chevron-left"></i></span>
      <span class="c-carousel-arrow c-carousel-heading-nav-arrow j-next"><i class="fa fa-chevron-right"></i></span>
    </div>
    <div class="index__heading__title"><?= $title ?></div>
  </div>
  <div id="j-bbs-index-<?= $type ?>-carousel" class="owl-carousel">
    <?php foreach ($items as &$v) {
      // Gallery Item Template
      echo View::template('search.item.gallery', array('item' => &$v), 'bbs');
    }
    unset ($v); ?>
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
        rewind: true,
        margin: 15,
        nav: false,
        dots: true,
        responsive: {
          0: {items: 1},
          767: {items: 2},
          991: {items: 3},
          1199: {items: 4}
        }
      });
      $block.on('click', '.j-prev', function () {
        $carousel.owlCarousel().trigger('prev.owl.carousel');
      });
      $block.on('click', '.j-next', function () {
        $carousel.owlCarousel().trigger('next.owl.carousel');
      });
    }
  });
  <?php js::stop(); ?>
</script>