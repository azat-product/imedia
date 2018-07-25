<?php
/**
 * Кабинет пользователя: Мои объявления - layout
 * @var $this BBS
 * @var $f array фильтр
 * @var $cats array категории
 * @var $cat_active array текущая категория
 * @var $status array статусы
 * @var $list string список объявлений (HTML)
 * @var $pgn string постраничная навигация (HTML)
 * @var $pgn_pp array варианты кол-ва на страницу
 * @var $total integer всего объявлений
 * @var $device string текущее устройство bff::DEVICE_
 */

tpl::includeJS(array('history'), true);
tpl::includeJS('bbs.my', false, 6);
$f['qq'] = HTML::escape($f['qq']);
?>

<form action="" id="j-my-items-form">
  <input type="hidden" name="c" value="<?= $f['c'] ?>" id="j-my-items-cat-value" />
  <input type="hidden" name="status" value="<?= $f['status'] ?>" id="j-my-items-status-value" />
  <input type="hidden" name="page" value="<?= $f['page'] ?>" />
  <input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-items-pp-value" />

  <!-- List Filter -->
  <div class="usr-content-top">
    <div class="input-group usr-content-top-search">
      <input type="text" class="form-control" name="qq" value="<?= $f['qq'] ?>" class="j-q" />
      <span class="input-group-btn">
        <button type="button" class="btn btn-default j-q-submit"><i class="fa fa-search"></i></button>
      </span>
    </div>
    <ul class="nav nav-pills nav-pills-sm" id="j-my-items-cat">
      <li class="dropdown">
        <a class="j-cat-dropdown" data-toggle="dropdown" href="javascript:void(0);">
          <b class="j-cat-title"><?= $cat_active['title'] ?></b>
          <i class="fa fa-caret-down"></i>
        </a>
        <ul class="dropdown-menu j-cat-list">
          <?= $cats ?>
        </ul>
      </li>
      <?php foreach($status as $k=>$v) { ?>
        <li class="<?php if($f['status'] == $k) { ?> active<?php } ?> j-status-options">
            <a href="#" class="j-status-option" data-value="<?= $k ?>">
              <?= $v['title'] ?>
              <span class="nav-pills-label j-counter"><?= $counters[$k] ?></span>
          </a>
        </li>
      <?php } ?>
    </ul>
  </div>

  <!-- Listings Actions -->
  <div class="usr-ads-actions" id="j-my-items-sel-actions" style="display: none;">
    <div class="j-my-items-sel-actions-<?= bff::DEVICE_DESKTOP ?> j-my-items-sel-actions-<?= bff::DEVICE_TABLET ?>">
      <div class="usr-ads-actions-count">
        <?= _t('bbs.my', 'Выбрано'); ?>
        <span class="dropdown">
          <a href="#" data-toggle="dropdown" class="link-ajax"><span><strong class="j-sel-title"></strong></span></a>
          <ul class="dropdown-menu">
            <li><a href="#" class="j-mass-select" data-act="all-page"><?= _t('bbs.my', 'выбрать все на странице'); ?></a></li>
            <li><a href="#" class="j-mass-select" data-act="all"><?= _t('bbs.my', 'выбрать все'); ?></a></li>
            <li><a href="#" class="j-mass-select" data-act="cancel"><?= _t('bbs.my', 'отменить выбор'); ?></a></li>
          </ul>
        </span>
        :
      </div>
      <ul class="usr-ads-actions-list j-sel-actions" data-status="1" style="display: none;">
        <li><a href="#" class="j-sel-action" data-act="mass-unpublicate"><?= _t('bbs.my', 'Снять с публикации') ?></a></li>
        <li><a href="#" class="j-sel-action" data-act="mass-refresh"><?= _t('bbs.my', 'Продлить') ?></a></li>
        <?php if (BBS::svcUpFreePeriod() > 0) { ?><li><a href="#" class="j-sel-action" data-act="mass-up-free"><?= _t('bbs.my', 'Поднять бесплатно') ?></a></li><?php } ?>
      </ul>
      <ul class="usr-ads-actions-list j-sel-actions" data-status="2" style="display: none;">
        <li><a href="#" class="j-sel-action" data-act="mass-delete"><?= _t('bbs.my', 'Удалить') ?></a></li>
      </ul>
      <ul class="usr-ads-actions-list j-sel-actions" data-status="3" style="display: none;">
        <li><a href="#" class="j-sel-action" data-act="mass-publicate"><?= _t('bbs.my', 'Активировать') ?></a></li>
          <li><a href="#" class="j-sel-action" data-act="mass-delete"><?= _t('bbs.my', 'Удалить') ?></a></li>
      </ul>
      
    </div>
  </div><!-- /.usr-ads-actions -->

  <?php if(BBS::limitsPayedEnabled()) { ?>
  <div class="alert alert-warning <?= empty($limitsPayed) ? 'hidden' : '' ?>" id="j-alert-limits-payed">
    <?= _t('bbs.my', 'Вы достигли лимита активных объявлений'); ?>
    <div class="mrgt10">
      <a href="#" class="btn btn-warning" id="j-limits-payed-info" data-shop="<?= $shop ?>"><?= _t('', 'Детальнее'); ?></a>
    </div>
  </div>
  <?php } ?>

  <?php if($shop && Shops::abonementEnabled()) { ?>
  <div class="alert alert-warning <?= empty($shopAbonement) ? 'hidden' : '' ?>" id="j-alert-shop-abonement">
    <?= _t('bbs.my', 'Вы достигли лимита активных объявлений'); ?>
    <div class="mrgt10">
      <a href="<?= Users::url('my.settings', array('t' => 'abonement', 'abonement' => 1)) ?>" class="btn btn-warning"><?= _t('', 'Детальнее'); ?></a>
    </div>
  </div>
  <?php } ?>

  <?php if( ! $this->errors->no()) { $errors = $this->errors->get(); ?>
  <div class="alert alert-error pd15">
    <?= join('<br />', $errors); ?>
  </div>
  <?php } ?>

  <?php if( ! empty($messages)) { ?>
  <div class="alert alert-info pd15">
    <?= join('<br />', $messages); ?>
  </div>
  <?php } ?>

  <!-- Ads List -->
  <div class="usr-ads-list" id="j-my-items-list">
    <div class="j-my-items-list-<?= bff::DEVICE_DESKTOP ?> j-my-items-list-<?= bff::DEVICE_TABLET ?> j-my-items-list-<?= bff::DEVICE_PHONE ?>">
      <?= $list; ?>
    </div>
  </div>

  <!-- Pagination -->
  <div class="usr-pagination">
    <div id="j-my-items-pp" class="usr-pagination-dropdown dropdown<?= ( ! $total ? ' hide' : '' ) ?>">
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
    <div id="j-my-items-pgn">
      <?= $pgn ?>
    </div>
  </div>

</form>

<script type="text/javascript">
<?php js::start(); ?>
$(function(){
jMyItems.init(<?= func::php2js(array(
  'lang' => array(
    'sel_selected' => '[items]',
    'sel_items_desktop' => _t('', 'объявление;объявления;объявлений'),
    'sel_items_tablet' => _t('', 'объявление;объявления;объявлений'),
    'sel_items_phone' => _t('', 'об-е;об-я;об-й'),
    'delete_confirm' => _t('bbs.my', 'Удалить объявление?'),
    'delete_confirm_mass' => _t('bbs.my', 'Удалить отмеченные объявления?'),
    'up_auto_on' => _t('bbs.my', 'Включить автоподнятие'),
    'up_auto_off' => _t('bbs.my', 'Настроить автоподнятие'),
    ),
  'status' => $status,
  'total'  => $total,
  'ajax' => true,
  )) ?>);
});
<?php js::stop(); ?>
</script>