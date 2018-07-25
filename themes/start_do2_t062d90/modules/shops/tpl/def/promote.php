<?php
/**
 * Форма продвижения магазина
 * @var $this Shops
 * @var $shop array данные магазина
 * @var $from string источник перехода на страницу продвижения: 'new', ...
 * @var $svc array данные об услугах
 * @var $svc_id integer ID текущей выбранной услуги
 * @var $svc_prices array настройки цен на услуги
 * @var $ps array способы оплаты
 * @var $user_balance integer текущий баланс пользователя
 * @var $curr string текущая валюта оплаты
 */

tpl::includeJS('shops.promote', false, 3);
$promoteAbonement = Shops::abonementEnabled() && ! empty($abonements);
?>


<form class="form-horizontal" action="" id="j-item-promote-form">
  <input type="hidden" name="ps" value="<?= $ps_active_key ?>" class="j-ps-value" />
  <input type="hidden" name="from" value="<?= HTML::escape($from) ?>" />
  <?php if($promoteAbonement): ?>
  <input type="hidden" name="svc" value="<?= $svc_id ?>" />
  <div class="l-pageHeading">
    <h2 class="l-pageHeading-title">
      <?= _t('shops', '1. Выберите тарифный план'); ?>
    </h2>
  </div>
  <?= $abonements ?>
  <?php else: ?>
  <div class="l-pageHeading">
    <h2 class="l-pageHeading-title">
      1. <?php if($from == 'new' && $svc_id) { ?><?= _t('shops', 'Подтверждение выбранной услуги') ?><?php } else { ?><?= _t('shops', 'Выберите услугу') ?><?php } ?>
    </h2>
    <div class="l-pageHeading-subtext">
      <a href="<?= $shop['link'].'?from=promote' ?>" target="_blank"><?= $shop['title'] ?></a>
    </div>
  </div>
  <div class="l-svc j-svc-block">
    <?php $i=1; foreach($svc as $v) { if($v['id'] == Shops::SERVICE_ABONEMENT) continue; ?>
    <div class="l-svc-item<?php if($v['active']){ ?> active<?php } ?><?php if($i++ != sizeof($svc)) { ?> l-svc-item-promo-top<?php } else { ?> last<?php } ?> j-svc-item" data-price="<?= $v['price'] ?>" data-id="<?= $v['id'] ?>">
      <label>
        <span class="l-svc-item-top" style="background-color: <?= $v['color'] ?>">
          <input type="radio" name="svc"<?php if($v['disabled']){ ?> disabled="disabled"<?php } ?><?php if($v['active']){ ?> checked="checked"<?php } ?> autocomplete="off" value="<?= $v['id'] ?>" class="j-check" />
          <span class="l-svc-item-price">
            <?php if( ! $v['price']){ echo _t('shops', 'бесплатно'); } else { ?><strong><?= $v['price'] ?></strong> <?= $curr ?>
            <?php } ?>
          </span>
          <span class="l-svc-item-icon"><img src="<?= $v['icon_s'] ?>" alt="" /></span>
          <span class="l-svc-item-title"><?= $v['title_view'] ?></span>
        </span>
        <span class="l-svc-item-descr<?php if(! $v['active'] ){ ?> hide<?php } ?> j-svc-descr">
          <?= nl2br($v['description']) ?>
          <?php switch($v['id']) {
            case Shops::SERVICE_MARK: {
              if(($shop['svc'] & $v['id']) && $shop['svc_marked_to'] != Shops::SVC_TERMLESS_DATE) {
                ?><br /><br /><?= _t('shops', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($shop['svc_marked_to'], true, true))); ?><?php
              }
            } break;
            case Shops::SERVICE_FIX: {
              if(($shop['svc'] & $v['id']) && $shop['svc_fixed_to'] != Shops::SVC_TERMLESS_DATE) {
                ?><br /><br /><?= _t('shops', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($shop['svc_fixed_to'], true, true))); ?><?php
              }
            } break;
            default: {
              bff::hook('shops.svc.active.shop.promote', $v, array('data'=>&$aData));
            } break;
          } ?>
        </span>
      </label>
    </div>
    <?php } # foreach ?>
  </div>
  <?php endif; ?>

  <div class="j-ps-block mrgt20 hide">
    <div class="l-pageHeading">
      <h2 class="l-pageHeading-title">
        2. <?= _t('shops', 'Выберите способ оплаты') ?>
      </h2>
    </div>
    <div class="l-payMethods_inline">
      <?php foreach($ps as $key=>$v) {if (isset($v['enabled']) && !$v['enabled']) continue; ?>
      <div class="l-payMethods-item<?php if($v['active']) { ?> active<?php } ?> j-ps-item j-ps-item-<?= $key ?>" data-key="<?= $key ?>">
        <div class="l-payMethods-item-in">
          <div class="l-payMethods-item-logo">
            <img src="<?= $v['logo_desktop'] ?>" alt="" />
          </div>
          <div class="l-payMethods-item-content">
            <label class="radio">
              <input type="radio" name="ps" autocomplete="off" value="<?= $key ?>"<?php if($v['active']) { ?> checked="checked" <?php } ?> class="l-payMethods-item-radio j-radio" />&nbsp;<?= $v['title'] ?>
            </label>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>  
  </div>
  <h2 class="l-pageSubheading"><?= _t('shops', 'Всего к оплате') ?>: <b class="j-total">0</b> <?= $curr ?></h2>
  <input type="submit" class="btn btn-success j-submit" value="<?= _te('shops', 'Продолжить') ?>" />
  <span class="btn btn-default" onclick="history.back();"><?= _t('', 'Отмена') ?></span>
</form>
<div id="j-item-promote-form-request" style="display: none;"></div>

<script type="text/javascript">
<?php js::start(); ?>
$(function(){
jShopsShopPromote.init(<?= func::php2js(array(
  'lang' => array(
    'svc_select' => _t('shops', 'Выберите услугу'),
    'ps_select' => _t('shops', 'Выберите способ оплаты'),
    ),
  'user_balance' => $user_balance,
  'items_total' => 1,
  'svc_prices' => $svc_prices,
  'svc_id' => $svc_id,
  'svc_abon_selected' => $this->input->postget('abonID', TYPE_INT),
  'abonement' => $promoteAbonement,
  )) ?>);
});
<?php js::stop(); ?>
</script>