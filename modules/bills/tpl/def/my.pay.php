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

<? # Заголовок ?>
<div class="u-cabinet__sub-navigation">
    <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <div class="u-cabinet__sub-navigation_desktop u-cabinet__sub-navigation_bill hidden-phone">
        <div class="pull-left">
            <a href="<?= $urlHistory ?>" class="ico"><i class="fa fa-chevron-left"></i> <span><?= _t('bills', 'История операций') ?></span></a>
        </div>
        <div class="pull-right">
        </div>
        <div class="clearfix"></div>
    </div>
    <? } ?>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-cabinet__sub-navigation_mobile u-cabinet__sub-navigation_bill visible-phone">
        <div class="pull-left">
            <a href="<?= $urlHistory ?>" class="ico"><i class="fa fa-chevron-left"></i> <span><?= _t('bills', 'История операций') ?></span></a>
        </div>
        <div class="pull-right">
        </div>
        <div class="clearfix"></div>
    </div>
    <? } ?>
</div>

<? # Форма оплаты ?>
<div class="u-bill__payment" id="j-my-pay-form-block">
    <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <div class="u-bill__payment_desktop hidden-phone">
        <form action="" class="form-inline" id="j-my-pay-form-<?= bff::DEVICE_DESKTOP ?>">
            <div class="u-bill__payment__summ align-center">
                <div class="u-bill__payment__title"><?= _t('bills', 'На какую сумму вы хотите пополнить счёт?') ?></div>
                <input type="text" name="amount" value="<?= $amount ?>" class="input-small j-amount j-required" />
                <input type="submit" class="btn j-submit" value="<?= _te('bills', 'Продолжить') ?>" />
            </div>
            <div class="u-bill__payment__methods align-center" style="width: 510px;">
                <div class="u-bill__payment__title"><?= _t('bills', 'Выберите способ оплаты') ?></div>
                <div class="u-bill__payment__methods__list">
                    <? foreach($psystems as $key=>$v) { if (isset($v['enabled']) && !$v['enabled']) continue; ?>
                    <div class="u-bill__add__payment__list__item<? if($v['active']) { ?> active<? } ?> j-ps-item" data-key="<?= $key ?>">
                        <div class="u-bill__add__payment__list__item__ico">
                            <img src="<?= $v['logo_desktop'] ?>" width="64" alt="" />
                        </div>
                        <div class="u-bill__add__payment__list__item__title">
                            <label class="radio">
                                <input type="radio" name="ps" autocomplete="off" value="<?= $key ?>"<? if($v['active']) { ?> checked="checked" <? } ?> class="j-radio" />&nbsp;<?= $v['title'] ?>
                            </label>
                        </div>
                    </div>
                    <? } ?>
                    <div class="clearfix"></div>
                </div>
            </div>
        </form>
    </div>
    <? } ?>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-bill__payment_mobile visible-phone">
        <form action="" class="form-inline" id="j-my-pay-form-<?= bff::DEVICE_PHONE ?>">
            <div class="u-bill__payment__summ align-center">
                <div class="u-bill__payment__title"><?= _t('bills', 'Введите сумму') ?></div>
                <input type="text" name="amount" value="<?= $amount ?>" class="input-small j-amount j-required" />
                <input type="submit" class="btn j-submit" value="<?= _te('bills', 'Продолжить') ?>" />
            </div>
            <div class="u-bill__payment__methods align-center">
                <div class="u-bill__payment__title"><?= _t('bills', 'Выберите способ оплаты') ?></div>
                <div class="u-bill__payment__methods__list">
                    <? foreach($psystems as $key=>$v) { if (isset($v['enabled']) && !$v['enabled']) continue; ?>
                    <div class="u-bill__add__payment__list__item<? if($v['active']) { ?> active<? } ?> j-ps-item" data-key="<?= $key ?>">
                        <table>
                            <tr>
                                <td class="u-bill__add__payment__list__item__radio"><input type="radio" name="ps" autocomplete="off" value="<?= $key ?>"<? if($v['active']) { ?> checked="checked" <? } ?> class="j-radio" /></td>
                                <td class="u-bill__add__payment__list__item__ico"><img src="<?= $v['logo_phone'] ?>" width="32" alt=""/></td>
                                <td class="u-bill__add__payment__list__item__title"><?= $v['title'] ?></td>
                            </tr>
                        </table>
                    </div>
                    <? } ?>
                    <div class="clearfix"></div>
                </div>
            </div>
        </form>
    </div>
    <? } ?>
    <div id="j-my-pay-form-request" class="hide"></div>
</div>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jBillsMyPay.init(<?= func::php2js(array(
            'lang' => array(),
            'url_submit' => $urlPay,
        )) ?>);
    });
<? js::stop(); ?>
</script>