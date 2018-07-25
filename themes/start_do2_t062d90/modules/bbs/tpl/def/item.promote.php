<?php
/**
 * Форма продвижения объявления
 * @var $this BBS
 * @var $item array данные объявления
 * @var $from string источник перехода на страницу продвижения
 * @var $svc array данные об услугах
 * @var $svc_id integer ID текущей выбранной услуги
 * @var $svc_prices array настройки цен на услуги
 * @var $ps array способы оплаты
 * @var $user_balance integer текущий баланс пользователя
 * @var $curr string текущая валюта оплаты
 */

tpl::includeJS('bbs.promote', false, 6);
$lang_free = _t('bbs', 'бесплатно');
?>


<form class="form-horizontal" action="" id="j-item-promote-form">
  <input type="hidden" name="ps" value="<?= $ps_active_key ?>" class="j-ps-value" />
  <input type="hidden" name="from" value="<?= HTML::escape($from) ?>" />

  <div class="l-pageHeading">
    <h2 class="l-pageHeading-title">
      1. <?php if($from == 'new' && $svc_id) { ?><?= _t('bbs', 'Подтверждение выбранной услуги') ?><?php } else { ?><?= _t('bbs', 'Выберите услугу') ?><?php } ?>
    </h2>
  </div>

  <div class="help-block">
    <a href="<?= $item['link'].'?from=promote' ?>" target="_blank"><?= $item['title'] ?></a>
  </div>

  <div class="l-svc j-svc-block">
    <?php $i=1; foreach($svc as $v) { ?>
    <div class="l-svc-item<?php if($v['active']){ ?> active<?php } ?><?php if($i++ != sizeof($svc)) { ?> l-svc-item-promo-top<?php } else { ?> last<?php } ?> j-svc-item" data-price="<?= $v['price'] ?>" data-id="<?= $v['id'] ?>">
      <label>
        <span class="l-svc-item-top" style="background-color: <?= $v['color'] ?>">
          <input type="radio" name="svc"<?php if($v['disabled']){ ?> disabled="disabled"<?php } ?><?php if($v['active']){ ?> checked="checked"<?php } ?> autocomplete="off" value="<?= $v['id'] ?>" class="j-check" />
          <span class="l-svc-item-price">
            <?php
            if ( $v['id'] == BBS::SERVICE_UP && $item['svc_up_activate'] > 0 ) {
              echo _t('bbs', 'оплачено: <b>[up]</b>', array('up'=>$item['svc_up_activate']));
            } else if ( $v['id'] == BBS::SERVICE_PRESS && $item['svc_press_status'] > 0 ) {
              if($item['svc_press_status'] == BBS::PRESS_STATUS_PAYED) {
                ?><b><span class="hidden-xs"><?= _t('bbs', 'ожидает публикации'); ?></span><span class="visible-xs"><?= _t('bbs', 'опл.') ?></span></b><?php
              } else if($item['svc_press_status'] == BBS::PRESS_STATUS_PUBLICATED) {
                ?><span class="hidden-xs"><?= _t('bbs', 'опубликовано') ?>&nbsp;</span><b><?= tpl::date_format2($item['svc_press_date'], true) ?></b><?php
              }
            } else {
              if( ! $v['price']){ echo $lang_free; } else { ?><strong><?= tpl::currency($v['price']) ?></strong> <?= $curr ?><?php }
            }
            ?>
          </span>
          <span class="l-svc-item-icon"><img src="<?= $v['icon_s'] ?>" alt="" /></span>
          <span class="l-svc-item-title"><?= $v['title_view'] ?></span>
          <?php if($v['id'] == BBS::SERVICE_PRESS && $item['svc_press_date_last'] != '0000-00-00'): ?>
          <span class="grey hidden-xs"><?= _t('bbs', '(предыдущая публикация [date])', array('date' => tpl::date_format2($item['svc_press_date_last'], true))) ?></span>
        <?php endif; ?>
        </span>
        <span class="l-svc-item-descr<?php if(! $v['active'] ){ ?> hide<?php } ?> j-svc-descr">
        <?php if ($v['id'] == BBS::SERVICE_FIX && ! empty($v['period_type']) && $v['period_type'] == BBS::SVC_FIX_PER_DAY) {
          $days = $this->input->get('fix_days', TYPE_UINT); if($days <= 0) $days = config::sysAdmin('bbs.services.fix.days.default', 1, TYPE_UINT); ?>
          <div class="mrgb10">
            <?= _t('bbs', 'Закрепить на [input] день', array('input'=>
              '<input class="input-mini text-center" value="'.$days.'" type="number" name="fix_days" min="1" max="999" />'
              ))?>
          </div>
          <?php } ?>
          <?= $v['id'] == BBS::SERVICE_UP ? $svc_autoup_form : '' ?>
          <?= nl2br($v['description']) ?>
          <?php switch($v['id']) {
            case BBS::SERVICE_MARK: {
              if($item['svc'] & $v['id']) {
                ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_marked_to'], true, true))); ?><?php
              }
            } break;
            case BBS::SERVICE_FIX: {
              if($item['svc'] & $v['id']) {
                ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_fixed_to'], true, true))); ?><?php
              }
            } break;
            case BBS::SERVICE_QUICK: {
              if($item['svc'] & $v['id']) {
                ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_quick_to'], true, true))); ?><?php
              }
            } break;
            case BBS::SERVICE_PREMIUM: {
              if($item['svc'] & $v['id']) {
                ?><br /><br /><?= _t('bbs', 'Услуга активирована до <b>[date]</b>', array('date'=>tpl::date_format2($item['svc_premium_to'], true, true))); ?><?php
              }
            } break;
          } ?>
        </span>
      </label>
    </div>
    <?php } ?>
  </div>

  <div class="j-ps-block mrgt20 hide">
    <div class="l-pageHeading">
      <h2 class="l-pageHeading-title">
        2. <?= _t('bbs', 'Выберите способ оплаты') ?>
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

  <h2 class="l-pageSubheading"><?= _t('bbs', 'Всего к оплате') ?>: <b class="j-total">0</b> <?= $curr ?></h2>

  <input type="submit" class="btn btn-success j-submit" value="<?= _te('bbs', 'Продолжить') ?>" />
  <span class="btn btn-default" onclick="history.back();"><?= _t('', 'Отмена') ?></span>

</form>
<div id="j-item-promote-form-request" style="display: none;"></div>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jBBSItemPromote.init(<?= func::php2js(array(
      'lang' => array(
        'svc_select' => _t('bbs', 'Выберите услугу'),
        'ps_select' => _t('bbs', 'Выберите способ оплаты'),
        ),
      'user_balance' => $user_balance,
      'items_total' => 1,
      'svc_prices' => $svc_prices,
      'svc_id' => $svc_id,
      'svc_fix_id' => BBS::SERVICE_FIX,
      )) ?>);
  });
  <?php js::stop(); ?>
</script>
<?php
