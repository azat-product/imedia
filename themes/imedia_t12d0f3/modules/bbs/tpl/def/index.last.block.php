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
$count = 36;
if(DEVICE_PHONE) {
    $count = 9;
}
?>
<div class="c-carousel c-carousel-imedia pdb35" id="j-bbs-index-<?= $type ?>-block">
  <div class="c-carousel-heading">

    <h2><?= $title ?></h2>
  </div>
  <div id="j-bbs-index-<?= $type ?>-carousel" class="mrgt20">
      <? $i = 0; ?>
      <? foreach ($items as &$v): ?>
          <? if($i == 0): ?>
              <div class="slider-premium">
          <? endif; ?>
          <? $i++; ?>

            <? echo View::template('search.item.gallery.premium', array('item' => &$v), 'bbs'); ?>

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
  <?
  tpl::includeCSS('slick', true);
  tpl::includeJS('slick.min', false);
  ?>
  <?php js::start(); ?>
  $(function(){
      $('#j-bbs-index-<?= $type ?>-carousel').slick({
          adaptiveHeight: true,
          prevArrow: '<i class="fa fa-chevron-left arrow-slider" aria-hidden="true"></i>\n',
          nextArrow: '<i class="fa fa-chevron-right arrow-slider" aria-hidden="true"></i>\n'
      });
  });
  <?php js::stop(); ?>
</script>