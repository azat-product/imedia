<?php
/**
 * Поиск магазинов: список - простой список
 * @var $this Shops
 * @var $items array список магазинов
 */

$socialTypes = Shops::socialLinksTypes();
$lang_shop_items = _t('shops', 'объявление;объявления;объявлений');
$lang_contacts_show = _t('shops', 'Показать контакты');
$listBanner = function($positionNumber) use ($device) {
  if ($device == bff::DEVICE_PHONE) {
    $html = Banners::view('shops_search_list_mobile', array('list_pos' => $positionNumber));
    if ($html) {
      return '<div class="banner-m">'.$html.'</div>';
    }
  } else {
    $html = Banners::view('shops_search_list', array('list_pos' => $positionNumber));
    if ($html) {
      return '<div class="sh-list-item">'.$html.'</div>';
    }
  }
  return '';
};

?>
<div class="sh-list">
  <?php $n = 1;
  foreach($items as &$v) { ?>
  <?= $listBanner($n++); ?>

  <div class="sh-list-item<?php if ($v['svc_marked']) { ?> selected<?php } ?> j-shop" data-ex="<?= $v['ex'] ?>">
    <div class="sh-list-item-content">
      <h3 class="sh-list-item-title">
        <a href="<?= $v['link'] ?>" title="<?= $v['title'] ?>"><?= $v['title'] ?></a>
      </h3>
      <div class="sh-list-item-descr">
        <?= tpl::truncate($v['descr'], 170, '...', true) ?>
      </div>
      <div class="sh-list-item-more hidden-xs">
        <a href="<?= $v['link'] ?>"><?= tpl::declension($v['items'], $lang_shop_items) ?> &rsaquo;</a>
      </div>
    </div>
    <div class="sh-list-item-r">
      <?php if ($v['logo'] && DEVICE_DESKTOP_OR_TABLET) { ?>
      <div class="sh-list-item-logo">
        <a href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
          <img src="<?= $v['logo'] ?>" alt="<?= $v['title'] ?>" />
        </a>
      </div>
      <?php } ?>
      <div class="sh-list-item-contact">
        <?php if ($v['has_contacts']) { ?>
          <div class="sh-list-item-contact-item">
            <i class="fa fa-bell sh-list-item-contact-item-ico"></i> <a href="#" class="link-ajax j-contacts-ex" data-device="<?= bff::DEVICE_DESKTOP ?>"><span><?= $lang_contacts_show ?></span></a>
            <div class="dropdown-menu shown sh-list-item-contact-dropdown hide j-contacts"></div>
          </div>
        <?php } ?>
        <?php if ($v['region_id']) { ?>
          <div class="sh-list-item-contact-item">
            <i class="fa fa-map-marker sh-list-item-contact-item-ico"></i> <?= $v['region_title'] ?>
          </div>
        <?php } ?>
        <?php if ( ! empty($v['site'])) { ?>
          <div class="sh-list-item-contact-item">
            <i class="fa fa-globe sh-list-item-contact-item-ico"></i> <a href="<?= bff::urlAway($v['site']) ?>" rel="nofollow noopener" target="_blank"><?= str_replace(array('https://','http://','www.'), '', $v['site']) ?></a>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <?php } unset($v); ?>
  <?= ($last = $listBanner(Banners::LIST_POS_LAST)); ?>
  <?= ( ! $last ? $listBanner($n) : '') ?><?php
  if (empty($items)) {
    echo $this->showInlineMessage(_t('shops', 'Список магазинов пустой'));
  } ?>
</div>