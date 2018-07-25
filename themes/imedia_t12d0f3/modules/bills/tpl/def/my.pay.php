<?php
/**
 * Кабинет: Счет - пополнение счета
 * @var $this Bills
 * @var $amount integer|float сумма к оплате
 * @var $psystems array системы оплаты
 */
tpl::includeJS('bills.my', false, 4);

$urlHistory = Bills::url('my.history');
$urlPay = Bills::url('my.pay');
?>

<div class="usr-content-top">
  <a href="<?= $urlHistory ?>" class="link-ico"><i class="fa fa-chevron-left"></i> <span><?= _t('bills', 'История операций') ?></span></a>
</div>

<!-- Foem -->
<div class="usr-bill-pay" id="j-my-pay-form-block">
    <form action="" id="j-my-pay-form-<?= bff::DEVICE_DESKTOP ?>">
      
      <div class="usr-bill-pay-row">
        <div class="usr-bill-pay-title"><?= _t('bills', 'На какую сумму вы хотите пополнить счёт?') ?></div>
        <div class="form-group usr-bill-pay-input">
          <div class="usr-bill-pay-input-in">
            <input type="text" id="bill-input" name="amount" value="<?= $amount ?>" class="form-control input-sm j-required" />
            <label for="bill-input"><?php echo Site::currencyDefault(); ?></label>
          </div>
        </div>
      </div>

      <div class="usr-bill-pay-row">
        <div class="usr-bill-pay-title"><?= _t('bills', 'Выберите способ оплаты') ?></div>
        <div class="l-payMethods_inline text-center">
          <?php foreach($psystems as $key=>$v) { if (isset($v['enabled']) && !$v['enabled']) continue; ?>
            <div class="l-payMethods-item<?php if($v['active']) { ?> active<?php } ?> j-ps-item" data-key="<?= $key ?>">
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

      <input type="submit" class="btn btn-primary j-submit" value="<?= _te('bills', 'Продолжить') ?>" />
    </form>
    <div id="j-my-pay-form-request" style="display: none;"></div>
</div>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jBillsMyPay.init(<?= func::php2js(array(
      'lang' => array(),
      'url_submit' => $urlPay,
      )) ?>);
  });
  <?php js::stop(); ?>
</script>