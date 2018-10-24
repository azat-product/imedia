<?php
/**
 * Поиск магазинов: layout
 * @var $this Shops
 * @var $listTypes array типы списка
 * @var $isMap boolean текущий тип списка - карта
 * @var $items array список магазинов
 * @var $f array параметры фильтра
 * @var $cat array параметры текущей категории
 * @var $show_open_link boolean отображать ссылку "открыть магазин"
 */

tpl::includeJS(array('history'), true);

extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f'); # параметры фильтра => переменные с префиксом f_

if ($isMap) {
  Geo::mapsAPI(false);
  if (Geo::mapsType() == Geo::MAPS_TYPE_GOOGLE) {
    tpl::includeJS('markerclusterer/markerclusterer', false);
  }
}

$rightBlock = (DEVICE_DESKTOP_OR_TABLET && ! $isMap && ($bannerRight = Banners::view('shops_search_right')) );

?>
<?php if (DEVICE_DESKTOP_OR_TABLET) {
  echo tpl::getBreadcrumbs($cat['crumbs'], false, 'breadcrumb');
} ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content<?php if ($rightBlock) { ?> has-sidebar<?php } ?>">
    <div id="j-shops-search-list">
      <a name="search_list"></a>
      <div class="l-pageHeading">
        <h1 class="l-pageHeading-title"><?= ( $f_c > 0 ? $cat['titleh1'] : ( ! empty($f_q) ? _t('shops', 'Результаты поиска по запросу "[query]"', array('query'=>$f_q)) : (!empty($cat['titleh1']) ? $cat['titleh1'] : _t('shops', 'Все магазины')) ) ) ?></h1>
      </div>
      <div class="sr-listTop">
        <?php
        // List View
        if( ! empty($items) ) { ?>
        <div class="sr-listTop-view">
          <div id="j-f-listtype" class="btn-group">
            <?php foreach($listTypes as $k=>$v) {
              ?><a href="javascript:void(0);" data="{id:<?= $k ?>}" data-id="<?= $k ?>" class="btn btn-default j-type<?php if($v['a']){ ?> active<?php } ?>"><i class="<?= $v['i'] ?>"></i> <span class="hidden-xs"><?= $v['t'] ?></span></a><?php
            } ?>
          </div>
        </div>
        <? if( ! empty($items) ) : ?>
            <span class="mrgl10">
                <input <?=$sort_by_rating ? 'checked="checked"' : '' ?> type="checkbox" name="sort_by_rating" onclick="return makeSortByRating();">
                <span><?=_t('', ' по рейтингу')?></span>
            </span>
        <? endif;?>
        <?php } ?>
      </div>
      
      <!-- Search Results -->
      <div class="j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>">
        <?= $this->searchList(bff::DEVICE_DESKTOP, $f_lt, $items, $num_start); ?>
      </div>
      
      <!-- Pagination -->
      <div id="j-shops-search-pgn">
        <?= $pgn ?>
      </div>

    </div><!-- /#j-shops-search-list -->
  </div><!-- /.l-mainLayout-content -->

  <?php if ($rightBlock) { ?>
  <!-- Sidebar -->
  <div class="l-mainLayout-sidebar">
    <?php if($show_open_link): ?>
      <div class="l-mainLayout-sidebar-item">
        <a href="<?= Shops::url('my.open') ?>" class="btn btn-block btn-info">
          <i class="icon-plus icon-white"></i> <?= _t('shops', 'Открыть магазин'); ?>
        </a>
      </div>
    <?php endif; ?>
    <div class="l-banner-v">
      <?= $bannerRight ?>
    </div>
  </div>
  <?php } ?>

</div><!-- /.l-mainLayout -->

<div class="l-seoText">
  <?php if($f['page'] <= 1 && isset($cat['seotext'])) echo $cat['seotext'] ?>
</div>

<script type="text/javascript">
  <?php js::start(); ?>
  <?php
  if ($isMap) {
    foreach($items as &$v) { unset($v['descr']); } unset($v);
  } else {
    $items = array();
  }
  ?>
  $(function(){
    jShopsSearch.init(<?= func::php2js(array(
      'lang'=>array(
        'map_toggle_open' => _t('shops', 'больше карты'),
        'map_toggle_close' => _t('shops', 'меньше карты'),
        'map_content_loading' => _t('shops', 'Загрузка, подождите...'),
        'map_show_items' => _t('shops', 'Показать объявления'),
        ),
      'listtype' => $listTypes,
      'items'    => $items,
      'defaultCoords' => Geo::mapDefaultCoords(true),
      'ajax'     => false,
      )) ?>);
  });
  <?php js::stop(); ?>
</script>
<?php
tpl::includeJS(array('bbs.shops.sort.raiting'), false);

# актуализируем данные формы поиска
# формируемой позже в фаблоне /tpl/filter.php
$this->searchFormData($f);