<?php
/**
 * Список объявлений магазина: layout
 * @var $this BBS
 * @var $empty boolean список пустой
 * @var $items array объявления
 * @var $cats array категории фильтра
 * @var $cat_active array текущая категория
 * @var $f array фильтр
 * @var $pgn string постраничная навигация (HTML)
 * @var $device string текущее устройство bff::DEVICE_
 */
if ($empty) {
  echo $this->showInlineMessage(_t('bbs', 'Список объявлений магазина пустой'));
  return;
}
tpl::includeJS(array('history'), true);
tpl::includeJS('bbs.items', false);
?>
<a name="search_list"></a>
<form action="" id="j-shop-view-items-list">
  <input type="hidden" name="c" value="<?= $f['c'] ?>" class="j-cat-value" />
  <input type="hidden" name="page" value="<?= $f['page'] ?>" />

  <div class="mrgb15 flex flex_aic">
    <ul class="nav nav-pills j-cat mrgr10">
      <li class="dropdown">
        <a class="btn btn-default btn-sm j-cat-dropdown" data-toggle="dropdown" href="#">
          <strong class="j-cat-title"><?= $cat_active['title'] ?></strong>
          <b class="caret"></b>
        </a>
        <ul class="dropdown-menu">
          <?php foreach ($cats as $v):
          if (empty($v['sub'])) {
            ?><li><a href="#" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?php
          } else {
            ?><li class="dropdown-menu-nav-header"><?= $v['title'] ?></li><?php
            foreach ($v['sub'] as $vv) {
              ?><li><a href="#" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?php
            }
          }
          endforeach; ?>
        </ul>
      </li>
    </ul>

  <? if( ! empty($items) ) : ?>
      <span>
          <input <?=$sort_by_rating ? 'checked="checked"' : '' ?> type="checkbox" name="sort_by_rating" onclick="return makeSortByRating();">
          <span><?=_t('', ' по рейтингу')?></span>
      </span>
  <? endif;?>
  </div>

  <!-- Listings -->
  <div class="j-list">
    <div class="j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?> j-list-<?= bff::DEVICE_PHONE ?>">
      <?php
        echo $this->searchList(bff::DEVICE_DESKTOP, $f['lt'], $items);
      ?>
    </div>
  </div>

</form>

<!-- Pagination -->
<div id="j-shop-view-items-pgn">
  <?= $pgn ?>
</div>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jBBSItemsList.init({
        list: '#j-shop-view-items-list',
        pgn:  '#j-shop-view-items-pgn',
        lang: {},
        ajax: true
      });
  });
  <?php js::stop(); ?>
</script>
<?php
tpl::includeJS(array('bbs.shops.sort.raiting'), false);
