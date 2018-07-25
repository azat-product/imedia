<?php
/**
 * Кабинет пользователя: Избранные (объявления)
 * @var $this BBS
 * @var $empty boolean список пустой
 * @var $items array объявления
 * @var $cats array категории
 * @var $cat_active array текущая категория
 * @var $f array параметры фильтра
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 * @var $total integer всего объявлений
 * @var $device string текущее устройство bff::DEVICE_
 */
  if ($empty) {
?>
<div class="alert alert-info">
  <?= _t('bbs', 'Список избранных объявлений пустой') ?>
</div>
<ul class="list-unstyled">
  <li>
    <?= _t('bbs', 'Перейти на просмотр <a [link_search]>всех объявлений</a>', array('link_search'=>'href="'.BBS::url('items.search').'"')); ?>
  </li>
  <li>
    <?= _t('bbs', 'Перейти на <a [link_home]>главную страницу</a> сайта', array('link_home'=>'href="'.bff::urlBase().'"')); ?>
  </li>
</ul>
<?php
    return;
  } else {
    tpl::includeJS('history', true);
    tpl::includeJS('bbs.items', false);
  }
?>
<?php if ( ! User::id()) { ?>
  <?= tpl::getBreadcrumbs(array(
  array('title'=>_t('bbs', 'Избранные объявления'), 'active'=>true)
  )); ?>
  <div class="l-pageHeading">
    <h1 class="l-pageHeading-title">
      <?= _t('bbs', 'Избранные объявления') ?>
    </h1>
  </div>
<?php } ?>

<form action="" id="j-my-favs-form">
  <input type="hidden" name="c" value="<?= $f['c'] ?>" class="j-cat-value" />
  <input type="hidden" name="lt" value="<?= $f['lt'] ?>" />
  <input type="hidden" name="page" value="<?= $f['page'] ?>" />
  <input type="hidden" name="pp" value="<?= $f['pp'] ?>" class="j-pp-value" />

  <!-- List Filter -->
  <div class="usr-content-top">
    <div class="usr-content-top-right">
      <a href="#" class="btn btn-default j-cleanup">
        <i class="fa fa-times"></i> <?= _t('bbs', 'Очистить избранное') ?>
      </a>
    </div>
    <ul class="nav nav-pills nav-pills-sm j-cat">
      <li class="dropdown">
        <a class="dropdown-toggle j-cat-dropdown" data-toggle="dropdown" href="javascript:void(0);">
          <b class="j-cat-title"><?= $cat_active['title'] ?></b>
          <i class="fa fa-caret-down"></i>
        </a>
        <ul class="dropdown-menu">
          <?php foreach($cats as $v) {
            if( empty($v['sub']) ) {
              ?><li><a href="#" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?php
            } else {
              ?><li class="dropdown-menu-nav-header"><?= $v['title'] ?></li><?php
              foreach($v['sub'] as $vv) {
                ?><li><a href="#" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?php
              }
            }
          } ?>
        </ul>
      </li>
    </ul>
  </div>

  <!-- Listings -->
  <div class="j-list">
    <div class="j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?> j-list-<?= bff::DEVICE_PHONE ?>">
      <?php
        echo $this->searchList(bff::DEVICE_DESKTOP, $f['lt'], $items);
      ?>
    </div>
  </div>
    
  <!-- Pagination -->
  <div class="usr-pagination">
    <?php if( $total > 15) { ?>
    <div id="j-my-favs-pp" class="usr-pagination-dropdown dropdown<?= ( ! $total ? ' hide' : '' ) ?>">
      <a class="btn btn-default j-pp-dropdown" data-toggle="dropdown" href="#">
        <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
        <b class="caret"></b>
      </a>
      <ul class="dropdown-menu pull-right">
        <?php foreach($pgn_pp as $k=>$v): ?>
          <li><a href="#" class="<?php if($k == $f['pp']) { ?>active <?php } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php } ?>
    <div id="j-my-favs-pgn">
      <?= $pgn ?>
    </div>
  </div>

</form>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jBBSItemsList.init({
        list: '#j-my-favs-form',
        pgn:  '#j-my-favs-pgn',
        pp:   '#j-my-favs-pp',
        lang: {},
        ajax: true,
        onInit: function() {
            var self = this;
            self.form.on('click', '.j-cleanup', function(e){ nothing(e);
                bff.ajax(self.listMngr.getURL(), {act:'cleanup',hash:app.csrf_token}, function(r, err){
                    if (r && r.success) {
                        location.reload();
                    } else {
                        app.alert.error(err);
                    }
                });
                return false;
            });
        }
    });
  });
  <?php js::stop(); ?>
</script>