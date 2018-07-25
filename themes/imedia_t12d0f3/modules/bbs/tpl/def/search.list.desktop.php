<?php
/**
 * Поиск объявлений: список (desktop, tablet)
 * @var $this BBS
 * @var $list_type integer тип списка BBS::LIST_TYPE_
 * @var $items array объявления
 * @var $showBanners boolean выводить баннеры в списке
 */

// List Banner
$listBanner = function ($listPosition) use ($showBanners) {
  if ($showBanners) {
    $html = Banners::view('bbs_search_list', array('list_pos' => $listPosition));
    if ($html) {
      return '<div class="sr-list-item">' . $html . '</div>';
    }
  }
  return '';
};

// List View
if ($list_type == BBS::LIST_TYPE_LIST) { ?>
  <div class="sr-list">
    <?php $n = 1;
    foreach ($items as &$v) {
      echo $listBanner($n++);
      echo View::template('search.item.list', array('item' => &$v), 'bbs');
    }
    unset($v); ?>
    <?= ($last = $listBanner(Banners::LIST_POS_LAST)); ?>
    <?= (!$last ? $listBanner($n) : '') ?>
  </div>
<?php } // Gallery View
else if ($list_type == BBS::LIST_TYPE_GALLERY) { ?>
  <div class="sr-gallery">
  <?php $i = 1;
  foreach ($items as &$v) {
    if ($i == 1) { ?><div class="row"><?php } ?>
    <div class="col-md-4 sr-gallery-item-wrap">
      <?= View::template('search.item.gallery', array('item' => &$v), 'bbs'); ?>
    </div>
    <?php if ($i++ == 3) { ?></div><?php $i = 1;
    }
  }
  unset($v);
  if ($i != 1) { ?></div><?php }
  ?>
  </div>
<?php } // Map View
else if ($list_type == BBS::LIST_TYPE_MAP) {
  $isAJAX = Request::isAJAX();
  if (!config::get('bbs-map-vertical', false)) { # normal filter map:
    if (!$isAJAX) { ?>
      <div class="sr-map j-maplist" style="height: 500px; overflow: auto;">
    <?php } ?>
    <?php foreach ($items as $k => &$v) {
      echo View::template('search.item.map', array('item' => &$v, 'index' => $k), 'bbs');
    }
    unset($v); ?>
    <?php if (!$isAJAX) { ?>
      </div>
      <div class="sr-map j-map">
        <div style="height: 500px; width: 100%;" class="j-search-map-desktop j-search-map-tablet"></div>
      </div>
    <?php }
  } else { # vertical filter map: ?>
    <?php if (!$isAJAX) { ?>
      <div class="sr-map j-map">
        <div style="height: 500px; width: 100%;" class="j-search-map-desktop j-search-map-tablet"></div>
      </div>
      <div class="j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>"><div class="j-maplist">
    <?php } ?>
    <div class="sr-map">
      <?php $n = 1;
      foreach ($items as $k => &$v) {
        echo $listBanner($n++);
        echo View::template('search.item.map', array('item' => &$v, 'index' => $k), 'bbs');
      }
      unset($v); ?>
      <?= ($last = $listBanner(Banners::LIST_POS_LAST)); ?>
      <?= (!$last ? $listBanner($n) : '') ?>
    </div>
    <?php if (!$isAJAX) { ?>
      </div></div>
    <?php } ?>
  <?php } ?>
  <?php if (!$isAJAX) { ?>
    <script type="text/html" class="j-tpl-map-balloon-desktop">
      <div class="sr-map-baloon">
        <% if(imgs > 0) { %>
        <div class="sr-map-baloon-img">
          <a href="<%= link %>" title="<%= title %>">
            <img src="<%= img_s %>" alt="<%= title %>"/>
          </a>
        </div>
        <% } %>
        <div class="sr-map-baloon-content">
          <div class="sr-map-baloon-title">
            <a href="<%= link %>"><%= title %></a>
          </div>
          <% if(price_on) { %>
          <div class="sr-map-baloon-price c-price">
            <%= price %>
            <div class="c-price-sub"><%= price_mod %></div>
          </div>
          <% } %>
          <div class="sr-map-baloon-subtext">
            <div class="sr-map-baloon-subtext-i">
              <%= cat_title %>
            </div>
            <div class="sr-map-baloon-subtext-i">
              <i class="fa fa-map-marker"></i> <%= city_title %><% if(obj.hasOwnProperty('district_title')) { %>, <%= district_title %><% } %><% if(addr_addr.length > 0) { %>, <%= addr_addr %><% } %>
            </div>
          </div>
        </div>
      </div>
    </script>
    <script type="text/html" class="j-tpl-map-balloon-phone">
      <div class="sr-map-baloon">
        <div class="sr-map-baloon-title">
          <a href="<%= link %>"><%= title %></a>
        </div>
        <% if(price_on) { %>
        <div class="sr-map-baloon-price c-price">
          <%= price %>
          <div class="c-price-sub"><%= price_mod %></div>
        </div>
        <% } %>
        <div class="sr-map-baloon-subtext">
          <div class="sr-map-baloon-subtext-i">
            <%= cat_title %>
          </div>
        </div>
      </div>
    </script>
  <?php } ?>
<?php }