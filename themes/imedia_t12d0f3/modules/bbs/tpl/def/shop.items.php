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
      <span class="mrgl10 sort-rating">
            <label for="sort_by_rating" >
                <input <?=$sort_by_rating ? 'checked="checked"' : '' ?> type="checkbox" id="sort_by_rating" name="sort_by_rating" onclick="return makeSortByRating();">
                <span class="sort-rating__text btn btn-default " style="padding: 9px 15px;">
                    <svg aria-hidden="true" data-prefix="fas" data-icon="sort-amount-up" class="svg-inline--fa fa-sort-amount-up fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M4.702 116.686l79.984-80.002c6.248-6.247 16.383-6.245 22.627 0l79.981 80.002c10.07 10.07 2.899 27.314-11.314 27.314H128v320c0 8.837-7.163 16-16 16H80c-8.837 0-16-7.163-16-16V144H16.016c-14.241 0-21.363-17.264-11.314-27.314zM240 96h256c8.837 0 16-7.163 16-16V48c0-8.837-7.163-16-16-16H240c-8.837 0-16 7.163-16 16v32c0 8.837 7.163 16 16 16zm-16 112v-32c0-8.837 7.163-16 16-16h192c8.837 0 16 7.163 16 16v32c0 8.837-7.163 16-16 16H240c-8.837 0-16-7.163-16-16zm0 256v-32c0-8.837 7.163-16 16-16h64c8.837 0 16 7.163 16 16v32c0 8.837-7.163 16-16 16h-64c-8.837 0-16-7.163-16-16zm0-128v-32c0-8.837 7.163-16 16-16h128c8.837 0 16 7.163 16 16v32c0 8.837-7.163 16-16 16H240c-8.837 0-16-7.163-16-16z"></path></svg>
                    <?=_t('', 'сортировать по рейтингу')?>
                </span>
            </label>
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
