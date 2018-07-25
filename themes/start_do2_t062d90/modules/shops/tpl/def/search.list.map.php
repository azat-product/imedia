<?php
/**
 * Поиск магазинов: список - карта
 * @var $this Shops
 * @var $items array список магазинов
 * @var $device string тип устройства bff::DEVICE_
 */

$is_ajax = Request::isAJAX();

?>
<?php if ( ! $is_ajax) {
if (empty($items)) {
  echo $this->showInlineMessage(_t('shops', 'Список магазинов пустой'));
  return;
}
?>

<div class="sh-map">
  <div class="sh-map-list j-maplist">
  <?php } # ! $is_ajax ?>
    <?php foreach($items as $k=>&$v) { ?>
    <div class="sh-map-list-item j-maplist-item" data-index="<?= $k ?>">
      <div class="sh-map-list-item-num"><?= $v['num'] ?>.</div>
      <div class="sh-map-list-item-content">
        <h3 class="sh-map-list-item-title">
          <a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
        </h3>
        <div class="sh-map-list-item-descr">
          <?= tpl::truncate($v['descr'], 150, '...', true) ?>
        </div>
      </div>
    </div>
    <?php } unset($v); ?>
  
  <?php if( ! $is_ajax ) { ?>
  </div><!-- /.sh-map-list -->
  
  <!-- map -->
  <div class="sh-map-main sh-map-main_short j-map" data-short-class="sh-map-main_short">
    <div class="sh-map-main-controls">
      <span class="sh-map-main-controls-item" id="j-search-map-toggler">
        <span class="j-search-map-toggler-arrow">&laquo;</span> <a href="#" class="link-ajax j-search-map-toggler-link"><span><?= _t('search', 'больше карты'); ?></span></a>
      </span>
    </div>
    <div class="j-search-map-desktop j-search-map-tablet sh-map-main-container"></div>
  </div>

</div><!-- /.sh-map -->
<?php } # ! $is_ajax ?>