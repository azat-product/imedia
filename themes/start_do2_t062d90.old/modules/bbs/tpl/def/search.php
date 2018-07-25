<?php

    /**
     * Поиск объявлений: layout
     * @var $this BBS
     * @var $f array параметры фильтра
     * @var $listTypes array доступные типы списка
     * @var $sortTypes array доступные типы сортировки
     * @var $isMap boolean текущий вид тип списка - карта
     * @var $filterVertical boolean включен вертикальный фильтр
     * @var $cat array параметры текущей категории
     * @var $items array данные объявлений
     * @var $rss array параметры RSS ссылки
     * @var $catsBlock string блок подкатегорий (HTML)
     * @var $premiumBlock string блок премиум-объявлений (HTML)
     * @var $relinkBlock string блок перелинковки (HTML)
     */

    tpl::includeJS(array('history'), true);
    if ($isMap) {
        Geo::mapsAPI(false);
        if (Geo::mapsType() == Geo::MAPS_TYPE_GOOGLE) {
            tpl::includeJS('markerclusterer/markerclusterer', false);
        }
    }

    extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

    $rightBanner = Banners::view('bbs_search_right', array('cat'=>$cat['id'], 'region'=>$f['region'])); # Баннер (справа)
    $rightBlock = $filterVertical || $rightBanner || ! empty($premiumBlock);
    $isMapVertical = false;
    if ($rightBlock && $isMap) {
        $isMapVertical = true;
        $isMap = false;
        config::set('bbs-map-vertical', true);
    }
?>

<?php if(DEVICE_DESKTOP_OR_TABLET) {
  echo tpl::getBreadcrumbs($cat['crumbs'], false, 'breadcrumb');
} ?>

<?= $catsBlock ?>

<div class="l-mainLayout">

  <!-- Content -->
  <div class="l-mainLayout-content<?php if (DEVICE_DESKTOP_OR_TABLET && $rightBlock) {  ?> has-sidebar<?php } ?>">
    <div id="j-bbs-search-list">
      <div class="l-pageHeading">
        <?php if( sizeof($cat['types']) > 1 ) { ?>
          <?php if(DEVICE_DESKTOP_OR_TABLET)
            // Desktop Category Types
            { ?>
            <ul class="l-pageHeading-tabs">
              <?php foreach($cat['types'] as $k=>$v) { ?>
              <li class="<?php if($k == $f_ct) { ?>active <?php } ?>">
                <a href="javascript:void(0);" class="j-f-cattype-desktop" data="{id:<?= $v['id'] ?>,title:'<?= HTML::escape($v['title'], 'js') ?>'}" data-id="<?= $v['id'] ?>">
                <?= $v['title'] ?>
                </a></li>
              <?php } ?>
            </ul>
          <?php } ?>
          <?php if(DEVICE_PHONE) { ?>
            <div class="l-pageHeading-tabs-m dropdown">
              <a class="btn btn-default btn-block" id="j-f-cattype-phone-dd-link" data-current="<?= $f_ct ?>" data-toggle="dropdown">
                <?= $cat['types'][$f_ct]['title'] ?> <b class="caret"></b>
              </a>
              <ul class="dropdown-menu dropdown-menu-wfull" id="j-f-cattype-phone-dd">
                <?php foreach($cat['types'] as $k=>$v) { ?>
                <li><a href="javascript:void(0);" class="j-f-cattype-phone" data="{id:<?= $k ?>,title:'<?= HTML::escape($v['title'], 'js') ?>'}"><?= $v['title'] ?></a></li>
                <?php } ?>
              </ul>
            </div>
          <?php } ?>
        <?php } ?>
        <h1 class="l-pageHeading-title"><?= ( $f_c > 0 ? $cat['titleh1'] : ( ! empty($f_q) ? _t('search', 'Результаты поиска по запросу "[query]"', array('query'=>$f_q)) : (!empty($cat['titleh1']) ? $cat['titleh1'] : _t('search', 'Поиск объявлений')) ) ) ?></h1>

      </div><!-- ./l-pageHeading -->
      
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
        <?php } ?>
        <?php
        // List Sort
        if( sizeof($sortTypes) > 1 ) { ?>
        <div class="sr-listTop-sort">
          <span class="hidden-xs"><?= _t('search', 'Сортировка') ?> : </span>
          <span class="dropdown inline-block">
            <a class="btn btn-default dropdown-toggle" id="j-f-sort-dd-link" data-current="<?= $f_sort ?>" href="javascript:void(0);">
              <span class="lnk"><?= $sortTypes[$f_sort]['t'] ?></span> <b class="caret"></b>
            </a>
            <ul class="dropdown-menu" id="j-f-sort-dd">
              <?php foreach($sortTypes as $k=>$v) { ?><li><a href="javascript:void(0);" class="j-f-sort" data="{key:'<?= $k ?>',title:'<?= HTML::escape($v['t'], 'js') ?>'}"><?= $v['t'] ?></a></li><?php } ?>
            </ul>
          </span>
        </div>
        <?php } ?>
      </div><!-- /.sr-listTop -->

      <?php if($filterVertical && DEVICE_PHONE) { ?>
      <div class="l-filterAside-m">
        <button class="btn btn-default btn-block l-filterAside-m-toggle collapsed" data-toggle="collapse" data-target="#j-filter-vertical-tablet"><?= _t('filter', 'Фильтр'); ?></button>
        <div class="l-filterAside collapse" id="j-filter-vertical-tablet">
          <?= $filterVerticalBlock; ?>
        </div>
      </div>
      <?php } ?>
      
      <!-- Search Results -->
      <div class="j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?> j-list-<?= bff::DEVICE_PHONE ?>">
        <?= $this->searchList(bff::DEVICE_DESKTOP, $f_lt, $items, array('numStart' => $num_start, 'showBanners' => true)); ?>
      </div>
      <?php if ( ! empty($rss)) { ?>
        <!-- RSS Subscription -->
        <div class="l-rssSubscribe">
          <a href="<?= $rss['link'] ?>" class="ico" target="_blank" rel="nofollow"><i class="fa fa-rss"></i> <span><?= _t('bbs', 'Подписка через RSS на "[title]"', array('title' => $rss['title'])); ?></span></a>
        </div>
      <?php } ?>
      <!-- Pagination -->
      <div id="j-bbs-search-pgn">
        <?= $pgn ?>
      </div>
    </div> <!-- /#j-bbs-search-list -->
    
  </div><!-- /.l-mainLayout-content -->

  <?php if (DEVICE_DESKTOP_OR_TABLET && $rightBlock) {  ?>
    <!-- Sidebar -->
    <div class="l-mainLayout-sidebar">
      <?php if ($filterVertical) { ?>
        <div class="l-pageHeading">
          <h2 class="l-pageHeading-title"><?= _t('filter','Фильтр') ?></h2>
        </div>
        <div class="l-filterAside" id="j-filter-vertical-desktop">
          <?= $filterVerticalBlock; ?>
        </div>
      <?php $filterVerticalBlock = ''; } ?>
      <?= ! empty($premiumBlock) ? $premiumBlock : '' ?>
      <?php if ($rightBanner) { ?>
      <div class="l-banner-v">
        <?= $rightBanner ?>
      </div>
      <?php } ?>
    </div>
  <?php } ?>

</div><!-- /.l-mainLayout -->


<?php if(DEVICE_DESKTOP_OR_TABLET) {
  // bbs/search.relink.block.php 
  echo $relinkBlock;
} ?>
<div class="l-info">
  <?php if($f['page'] <= 1 && ! empty($cat['seotext'])) echo $cat['seotext'] ?>
</div>

<script type="text/javascript">
<?php js::start(); ?>
    $(function(){
        jBBSSearch.init(<?= func::php2js(array(
            'lang'=>array(
                'range_from' => _t('filter','от'),
                'range_to'   => _t('filter','до'),
                'btn_reset'  => _t('filter','Не важно'),
                'map_toggle_open' => _t('search', 'больше карты'),
                'map_toggle_close' => _t('search', 'меньше карты'),
                'metro_declension' => _t('filter','станция;станции;станций'),
            ),
            'cattype'  => $cat['types'],
            'cattype_ex' => BBS::CATS_TYPES_EX,
            'listtype' => $listTypes,
            'sort'     => $sortTypes,
            'items'    => ( $isMap || $isMapVertical ? $items : array() ),
            'defaultCoords' => Geo::mapDefaultCoords(true),
            'isVertical' => $filterVertical,
            'isMapVertical' => $isMapVertical,
            'ajax'     => (bff::isIndex() ? false : true),
            'filterDropdownMargin' => 5,
        )) ?>);
    });
<?php js::stop(); ?>
</script>
<?php

# актуализируем данные формы поиска
# формируемой позже в фаблоне /tpl/filter.php
$this->searchFormData($f);