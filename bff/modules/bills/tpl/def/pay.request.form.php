<?php

/**
 * @var $this Bills
 * @var $psystem integer тип системы оплаты
 * @var $psystem_way string способ оплаты в рамках системы оплаты
 * @var $bill_id integer ID счета
 * @var $bill_description string описание (название) счета
 * @var $amount integer сумма счета
 * @var $extra array доп. параметры
 */

switch($psystem)
{
    # Webmoney (http://www.webmoney.ru/)
    case Bills::PS_WM:
    {
        echo '<form accept-charset="cp1251" method="POST" action="https://merchant.webmoney.ru/lmi/payment.asp">
                <input type="hidden" name="LMI_PAYMENT_AMOUNT" type="hidden" value="'.$amount.'" />
                <input type="hidden" name="LMI_PAYMENT_DESC" value="'.$bill_description.'" />
                <input type="hidden" name="LMI_PAYMENT_NO" value="'.$bill_id.'" />
                <input type="hidden" name="LMI_PAYEE_PURSE" value="'.$this->wm_purse($psystem_way).'" />
                <input type="hidden" name="LMI_SIM_MODE" value="0" />
                '.(!empty($wm_result)?'<input type="hidden" name="LMI_RESULT_URL" value="'.$wm_result.'" />':'').'
                '.(!empty($wm_success)?'<input type="hidden" name="LMI_SUCCESS_URL" value="'.$wm_success.'" />':'').'
                '.(!empty($wm_success_method)?'<input type="hidden" name="LMI_SUCCESS_METHOD" value="'.$wm_success_method.'" />':'').'
                '.(!empty($wm_fail)?'<input type="hidden" name="LMI_FAIL_URL" value="'.$wm_fail.'" />':'').'
                '.(!empty($wm_fail_method)?'<input type="hidden" name="LMI_FAIL_METHOD" value="'.$wm_fail_method.'" />':'').'
              </form>';
    } break;


    # Robokassa (http://www.roboxchange.com)
    case Bills::PS_ROBOX:
    {
        // Подсчёт Robox CRC
        $robox_crc = md5($robox_login.':'.$amount.':'.$bill_id.':'.$robox_pass1);

        $robox_url = 'https://auth.robokassa.ru/Merchant/Index.aspx';

        echo '<form action="'.$robox_url.'" method="POST">
                <input type="hidden" name="MrchLogin" value="'.$robox_login.'" />
                <input type="hidden" name="OutSum" value="'.$amount.'" />
                <input type="hidden" name="InvId" value="'.$bill_id.'" />
                <input type="hidden" name="Desc" value="'.$bill_description.'" />
                <input type="hidden" name="SignatureValue" value="'.$robox_crc.'" />
                <input type="hidden" name="IsTest" value="'.($robox_test?1:0).'" />
                <input type="hidden" name="IncCurrLabel" value="" />
                <input type="hidden" name="Culture" value="Ru" />
            </form>';
    } break;


    # W1 (http://www.w1.ru/)
    case Bills::PS_W1:
    {
        $fields = array(
            'WMI_MERCHANT_ID' => $w1_id,
            'WMI_PAYMENT_AMOUNT' => round($amount, 2),
            'WMI_PAYMENT_NO'  => $bill_id,
            'WMI_CURRENCY_ID' => $w1_currency,
            'WMI_DESCRIPTION' => $bill_description,
        );
        # Формируем SUCCESS_URL:
        # 1) из указанного при инициализации формы ($extra['success'])
        # 2) из указанного в настройках модуля (w1_success)
        if( ! empty($extra['success']) ) {
            $w1_success = $extra['success'];
        }
        if( ! empty($w1_success) ) {
            $fields['WMI_SUCCESS_URL'] = $w1_success;
        }

        if( ! empty($w1_fail) ) {
            $fields['WMI_FAIL_URL'] = $w1_fail;
        }

        # Помечаем доступные способы оплаты W1:
        # 1) из указанных при инициализации формы $psystem_way
        # 2) из указанных в настройках модуля (w1_ways)
        $w1_ways = ( ! empty($psystem_way) ? $psystem_way :
                     ( ! empty($w1_ways) ? $w1_ways : false) );

        if( ! empty($w1_ways) ) {
            if( is_array($w1_ways) ) {
                $fields['WMI_PTENABLED'] = $w1_ways;
            }
        }

        $fields['WMI_SIGNATURE'] = $this->w1_signature( $fields );

        echo '<form action="https://www.walletone.com/checkout/default.aspx" method="POST" accept-charset="UTF-8">';
        foreach($fields as $key => $val)
        {
            if (is_array($val)) {
               foreach($val as $value) {
                    echo '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
               }
            } else {
               echo '<input type="hidden" name="'.$key.'" value="'.$val.'" />';
            }
        }
        echo '</form>';
    } break;

    # PayPal (https://www.paypal.com/)
    case Bills::PS_PAYPAL:
    {
        echo '<form action="https://'.config::sys('bills.paypal.host', 'www.paypal.com', TYPE_STR).'/cgi-bin/webscr" method="post" target="_top">
            <input type="hidden" name="cmd" value="_s-xclick" />
            <input type="hidden" name="hosted_button_id" value="'.config::sys('bills.paypal.button_id', '', TYPE_NOTAGS).'" />
            <input type="hidden" name="custom" value="'.$bill_id.'" />
            <input type="hidden" name="quantity" value="'.round($amount * 100).'" />
        </form>';
    } break;

    # LiqPay (https://www.liqpay.com/)
    case Bills::PS_LIQPAY:
    {
        $api_version = 3;
        $public = config::sys('bills.liqpay.public_key', '', TYPE_STR);
        $private = config::sys('bills.liqpay.private_key', '', TYPE_STR);
        $pay = array(
            'version'        => $api_version,
            'public_key'     => $public,
            'action'         => 'pay',
            'amount'         => $amount,
            'currency'       => 'UAH',
            'description'    => $bill_description,
            'order_id'       => $bill_id,
            'result_url'     => SITEURL.'/bill/result?id='.$bill_id.'&ps='.Bills::PS_LIQPAY.'&w=liqpay',
            //'sandbox'        => 1,
        );

        $json = base64_encode(json_encode($pay));
        $signature = base64_encode(sha1($private.$json.$private,1));
        echo '<form method="POST" action="https://www.liqpay.com/api/'.$api_version.'/checkout" accept-charset="utf-8">
              <input type="hidden" name="data" value="'.$json.'"/>
              <input type="hidden" name="signature" value="'.$signature.'"/></form>';
    } break;

    # Yandex.Money (https://money.yandex.ru/)
    # https://money.yandex.ru/doc.xml?id=526991 (есть комиссия)
    case Bills::PS_YANDEX_MONEY:
    {
        $purse = config::sys('bills.yandex.money.purse', '', TYPE_STR);
        echo '<form method="POST" action="https://money.yandex.ru/quickpay/confirm.xml">
            <input type="hidden" name="receiver" value="'.$purse.'" />
            <input type="hidden" name="formcomment" value="'.$bill_description.'" />
            <input type="hidden" name="short-dest" value="'.$bill_description.'" />
            <input type="hidden" name="label" value="'.$bill_id.'" />
            <input type="hidden" name="quickpay-form" value="shop" />
            <input type="hidden" name="targets" value="транзакция '.$bill_id.'" />
            <input type="hidden" name="sum" value="'.$amount.'" />
            <input type="hidden" name="paymentType" value="'.$psystem_way.'" />
            <input type="hidden" name="successURL" value="'.SITEURL.'/bill/result?id='.$bill_id.'&ps='.Bills::PS_YANDEX_MONEY.'&w=yandex" />
        </form>';
    } break;
}