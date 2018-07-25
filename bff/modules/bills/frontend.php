<?php

/**
 * Абстрактные методы:
 * - processPayRequest
 * - processBill
 */

abstract class BillsModule extends BillsModuleBase
{
    /**
     * Метод принимающий запрос от платежной системы
     * и выполняющий обработчик данной системы оплаты (*_request),
     * который в свою очередь, при успешной оплате счета, вызывает Bills::processBill
     * @return mixed
     */
    public abstract function processPayRequest();

    /**
     * Оплата счёта на основе данных от платёжной системы
     * @param int $nBillID ID счета (в таблице TABLE_BILLS)
     * @param float|int $fMoney сумма счета (оплачиваемая)
     * @param int $nPaySystem ID системы оплаты Bills::PS_
     * @param mixed $mDetails детали от платежной системы
     * @param array $mExtra доп.параметры (если необходимо)
     * @return mixed
     */
    protected abstract function processBill($nBillID, $fMoney = 0, $nPaySystem = 0, $mDetails = false, $aExtra = array());

    # --------------------------------------------------------------------
    # Система оплаты WebMoney

    protected function wm_request()
    {
        extract($_POST);

        $nBillID = intval($LMI_PAYMENT_NO);
        $aBill = $this->model->billData($nBillID, array('money', 'psystem_way'));

        if (isset($LMI_PREREQUEST) && $LMI_PREREQUEST == 1) # предварительный запрос
        {
            if (!$nBillID || empty($aBill)) {
                $this->wm_response('');
            }

            # Проверка суммы
            $LMI_PAYMENT_AMOUNT = floatval(trim($LMI_PAYMENT_AMOUNT));
            if ($aBill['money'] != $LMI_PAYMENT_AMOUNT) # сумма, которую пытается заплатить не равна указанной в счете
            {
                $this->wm_response(_t('bills', 'Неверная сумма [amount]', array('amount'=>$LMI_PAYMENT_AMOUNT)));
            }

            $sPurseCorrect = $this->wm_purse($aBill['psystem_way']);

            if ($sPurseCorrect != trim($LMI_PAYEE_PURSE)) {
                $this->wm_response(_t('bills', 'Неверный кошелек получателя [purse]', array('purse'=>$LMI_PAYEE_PURSE)));
            }

            $this->wm_response(true);

        } else {

            if (!$nBillID || empty($aBill)) {
                $this->log('Webmoney: id платежа указан некорректно: "' . $nBillID . '"');

                return $this->payError('no_params');
            }

            if (empty($LMI_HASH)) {
                $this->log('Webmoney: параметр LMI_HASH пустой: "' . $LMI_HASH . '"');

                return $this->payError('no_params');
            }

            $str = $LMI_PAYEE_PURSE . $LMI_PAYMENT_AMOUNT . $LMI_PAYMENT_NO . $LMI_MODE . $LMI_SYS_INVS_NO . $LMI_SYS_TRANS_NO .
                $LMI_SYS_TRANS_DATE . $this->wm_purse($aBill['psystem_way'], true) . $LMI_PAYER_PURSE . $LMI_PAYER_WM;

            if (mb_strtolower($LMI_HASH) === mb_strtolower(hash('sha256', $str))) {
                $mResult = $this->processBill($nBillID, $LMI_PAYMENT_AMOUNT, static::PS_WM);
                if ($mResult !== true) {
                    return $mResult;
                }
            } else {
                $this->log('Webmoney: неверная контрольная сумма "' . mb_strtolower($LMI_HASH) . '" !== "' . mb_strtolower(hash('sha256', $str)) . '"');

                return $this->payError('crc_error');
            }
        }

        return true;
    }

    protected function wm_response($mResponse)
    {
        if (is_string($mResponse)) {
            echo 'ERR: ' . $mResponse;
            exit;
        } else {
            echo 'YES';
            exit;
        }
    }

    # --------------------------------------------------------------------
    # Система оплаты RBKMoney

    protected function rbkmoney_request()
    {
        extract($_POST);

        if (empty($hash)) {
            $this->log('RBKMoney: параметр hash пустой: "' . $hash . '"');

            return $this->payError('no_params');
        }

        if ($orderId <= 0) {
            $this->log('RBKMoney: Некорректный номер счета, (#' . $orderId . ')');

            return $this->payError('wrong_bill_id');
        }

        if ($paymentStatus == 3) # статус RBKMoney: Платеж принят на обработку
        {
            $this->model->billSave($orderId, array('status' => self::STATUS_PROCESSING));

            return 'OK';
        } elseif ($paymentStatus == 5) # статус RBKMoney: Платеж зачислен
        {
            $str = config::sys('bills.rbkmoney.id') . "::$orderId::$serviceName::$eshopAccount::$recipientAmount::$recipientCurrency::$paymentStatus::$userName::$userEmail::$paymentData::" . config::sys('bills.rbkmoney.key');
            if (mb_strtolower($hash) === mb_strtolower(md5($str))) {
                $mResult = $this->processBill($orderId, $recipientAmount, static::PS_RBK);

                return ($mResult === true ? 'OK' : $mResult);
            } else {
                $this->log('RBKMoney: неверная контрольная сумма "' . mb_strtolower($hash) . '" !== "' . mb_strtolower(md5($str)) . '"');

                return $this->payError('crc_error');
            }
        }

        return $this->payError('pay_error');
    }

    # --------------------------------------------------------------------
    # Система оплаты Robox

    protected function robox_request()
    {
        $OutSum = (!empty($_REQUEST['OutSum']) ? $_REQUEST['OutSum'] : '');
        $InvId = (!empty($_REQUEST['InvId']) ? $_REQUEST['InvId'] : '');
        $crc = (!empty($_REQUEST['SignatureValue']) ? $_REQUEST['SignatureValue'] : '');

        if (empty($crc)) {
            $this->log('Robox: параметр SignatureValue пустой: "' . $crc . '"');

            return $this->payError('no_params');
        }

        if (!is_numeric($InvId)) {
            $this->log('Robox: Некорректный номер счета, (#' . $InvId . ')');

            return $this->payError('wrong_bill_id');
        }

        $pass = config::sys('bills.robox.pass2');
        $crc2 = strtoupper(md5("$OutSum:$InvId:$pass"));

        if (strtoupper($crc) === $crc2) {
            $mResult = $this->processBill($InvId, $OutSum, static::PS_ROBOX);
            if ($mResult === true) {
                echo "OK$InvId" . PHP_EOL;
                exit;
            } else {
                return $mResult;
            }
        } else {
            $this->log('Robox: неверная контрольная сумма "' . strtoupper($crc) . '" !== "' . $crc2 . '"');

            return $this->payError('crc_error');
        }
    }

    # --------------------------------------------------------------------
    # Система оплаты Z-Payment

    protected function zpay_request()
    {
        extract($_POST);

        $zpayid = config::sys('bills.zpay.id'); # $LMI_PAYEE_PURSE
        $zpkey = config::sys('bills.zpay.key'); # $LMI_SECRET_KEY

        if (empty($LMI_HASH)) {
            $this->log('Z-Payment: параметр LMI_HASH пустой: "' . $LMI_HASH . '"');

            return $this->payError('no_params');
        }

        /*
            ID магазина (LMI_PAYEE_PURSE);
            Сумма платежа (LMI_PAYMENT_AMOUNT);
            Внутренний номер покупки продавца (LMI_PAYMENT_NO);
            Флаг тестового режима (LMI_MODE);
            Внутренний номер счета в системе Z-PAYMENT (LMI_SYS_INVS_NO);
            Внутренний номер платежа в системе Z-PAYMENT (LMI_SYS_TRANS_NO);
            Дата и время выполнения платежа (LMI_SYS_TRANS_DATE);
            Merchant Key (LMI_SECRET_KEY);
            Кошелек покупателя в системе Z-PAYMENT или его e-mail (LMI_PAYER_PURSE);
            Кошелек покупателя в системе Z-PAYMENT или его e-mail (LMI_PAYER_WM).
        */

        $str = "{$LMI_PAYEE_PURSE}{$LMI_PAYMENT_AMOUNT}{$LMI_PAYMENT_NO}{$LMI_MODE}{$LMI_SYS_INVS_NO}" .
            "{$LMI_SYS_TRANS_NO}{$LMI_SYS_TRANS_DATE}{$LMI_SECRET_KEY}{$LMI_PAYER_PURSE}{$LMI_PAYER_WM}";

        if (mb_strtolower($LMI_HASH) === mb_strtolower(md5($str))) {
            return $this->processBill($LMI_PAYMENT_NO, $LMI_PAYMENT_AMOUNT, static::PS_ZPAY);
        } else {
            $this->log('Z-Payment: неверная контрольная сумма "' . mb_strtolower($LMI_HASH) . '" !== "' . mb_strtolower(md5($str)) . '"');

            return $this->payError('crc_error');
        }
    }

    # --------------------------------------------------------------------
    # Система оплаты W1

    protected function w1_request()
    {
        // чистим все поля, которые не начинаются на WMI_
        foreach ($_POST as $k => $v) {
            if (strpos($k, 'WMI_') !== 0) {
                unset($_POST[$k]);
            }
        }
        extract($_POST);

        if (empty($WMI_SIGNATURE)) {
            $this->w1_response(false, _t('bills','Отсутствует параметр WMI_SIGNATURE'));
        }
        if (empty($WMI_PAYMENT_NO)) {
            $this->w1_response(false, _t('bills','Отсутствует параметр WMI_PAYMENT_NO'));
        }
        if (!isset($WMI_ORDER_STATE)) {
            $this->w1_response(false, _t('bills','Отсутствует параметр WMI_ORDER_STATE'));
        }

        # Проверяем подпись
        $crc = $WMI_SIGNATURE;
        unset($_POST['WMI_SIGNATURE']);
        $crc2 = $this->w1_signature($_POST, false);
        if ($crc !== $crc2) {
            $this->log('W1: неверная контрольная сумма "' . $crc . '" !== "' . $crc2 . '"');
            $this->w1_response(false, $this->payError('crc_error'));
        }

        # Проверяем состояние счета (в ответе W1 корректно только ACCEPTED)
        if (strtoupper($WMI_ORDER_STATE) !== 'ACCEPTED') {
            $this->log('W1: неверное состояние(ORDER_STATE) "' . $WMI_ORDER_STATE . '" !== "ACCEPTED"');
            $this->w1_response(false, _t('bills','Неверное состояние WMI_ORDER_STATE'));
        }

        # Обрабатываем счет
        $mResult = $this->processBill($WMI_PAYMENT_NO, $WMI_PAYMENT_AMOUNT, static::PS_W1, array(
                'WMI_ORDER_ID'       => (isset($WMI_ORDER_ID) ? $WMI_ORDER_ID : ''),
                'WMI_PAYMENT_AMOUNT' => $WMI_PAYMENT_AMOUNT,
                'WMI_PAYMENT_TYPE'   => (isset($WMI_PAYMENT_TYPE) ? $WMI_PAYMENT_TYPE : ''),
                'WMI_CURRENCY_ID'    => $WMI_CURRENCY_ID,
                'WMI_TO_USER_ID'     => (isset($WMI_TO_USER_ID) ? $WMI_TO_USER_ID : ''),
                'WMI_CREATE_DATE'    => $WMI_CREATE_DATE,
                'WMI_UPDATE_DATE'    => $WMI_UPDATE_DATE,
            )
        );
        if ($mResult === true) {
            $this->w1_response('OK');
        } else {
            $this->w1_response(false, $mResult);
        }
    }

    protected function w1_signature($aFields, $bEncode = true)
    {
        # Сортировка значений внутри полей
        foreach ($aFields as $name => $val) {
            if (is_array($val)) {
                usort($val, "strcasecmp");
                $aFields[$name] = $val;
            }
        }

        # Формирование сообщения, путем объединения значений формы,
        # отсортированных по именам ключей в порядке возрастания.
        # Конвертация из текущей кодировки (UTF-8)
        # необходима только если кодировка магазина отлична от Windows-1251
        uksort($aFields, 'strcasecmp');

        $fieldValues = '';
        foreach ($aFields as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if ($bEncode) {
                        $v = iconv('utf-8', 'windows-1251', $v);
                    }
                    $fieldValues .= $v;
                }
            } else {
                if ($bEncode) {
                    $value = iconv('utf-8', 'windows-1251', $value);
                }
                $fieldValues .= $value;
            }
        }

        # Формирование значения параметра WMI_SIGNATURE, путем
        # вычисления отпечатка, сформированного выше сообщения,
        # по алгоритму MD5 и представление его в Base64
        return base64_encode(pack("H*", md5($fieldValues . config::sys('bills.w1.secret'))));
    }

    protected function w1_response($sResult = 'OK', $sDescription = false)
    {
        if (empty($sResult)) {
            $sResult = 'RETRY';
        }
        echo 'WMI_RESULT=' . strtoupper($sResult);
        if ($sDescription !== false) {
            echo '&WMI_DESCRIPTION=' . urlencode($sDescription);
        }
        if ($sResult !== 'OK') {
            $this->log('W1: bad response "'.$sDescription.'"');
        }
        exit;
    }

    # --------------------------------------------------------------------
    # Система оплаты PayPal (https://www.paypal.com/)

    protected function paypal_request()
    {
        // https://github.com/paypal/ipn-code-samples/blob/master/paypal_ipn.php
        do {
            $raw_post_data = file_get_contents('php://input');
            $raw_post_array = explode('&', $raw_post_data);
            $myPost = array();
            foreach($raw_post_array as $keyval) {
                $keyval = explode('=', $keyval);
                if (count($keyval) == 2) {
                    $myPost[$keyval[0]] = urldecode($keyval[1]);
                }
            }
            // read the post from PayPal system and add 'cmd'
            $req = 'cmd=_notify-validate';
            foreach ($myPost as $key => $value) {
                $value = urlencode(stripslashes($value));
                $req .= "&$key=$value";
            }
            // Post IPN data back to PayPal to validate the IPN data is genuine
            // Without this step anyone can fake IPN data
            $paypal_url = 'https://' . config::sys('bills.paypal.host', 'www.paypal.com', TYPE_STR) . '/cgi-bin/webscr';
            $ch = curl_init($paypal_url);
            if ($ch == false) {
                $this->log('Paypal: curl_init false');
                break;
            }
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
            $res = curl_exec($ch);

            if (curl_errno($ch) != 0) { // cURL error
                $this->log('Paypal: Can\'t connect to validate IPN message: '.curl_error($ch));
                curl_close($ch);
                break;
            }
            curl_close($ch);

            // Inspect IPN validation result and act accordingly
            // Split response headers and payload, a better way for strcmp
            $tokens = explode("\r\n\r\n", trim($res));
            $res = trim(end($tokens));
            if (strcmp($res, 'VERIFIED') == 0) {
                $payment_status = (!empty($_REQUEST['payment_status']) ? $_REQUEST['payment_status'] : '');
                if ($payment_status != 'Completed') {
                    $this->log('Paypal: incorrect payment_status: "'.$payment_status.'".');
                    break;
                }
                $mc_currency = (!empty($_REQUEST['mc_currency']) ? $_REQUEST['mc_currency'] : '');
                if ($mc_currency != config::sys('bills.paypal.currency', 'USD', TYPE_STR)) {
                    $this->log('Paypal: incorrect mc_currency ['.$mc_currency.'].');
                    break;
                }
                $billID = (!empty($_REQUEST['custom']) ? $_REQUEST['custom'] : false);
                if (!$billID) {
                    $this->log('Paypal: incorrect custom.');
                    break;
                }
                $sum = (!empty($_REQUEST['mc_gross']) ? $_REQUEST['mc_gross'] : false);
                if (!$sum) {
                    $this->log('Paypal: empty sum');
                    break;
                }
            } else if (strcmp($res, 'INVALID') == 0) {
                $this->log('Paypal: Invalid IPN. '.$req);
                break;
            } else {
                $this->log('Paypal: Incorrect IPN validation result. '.$res);
                break;
            }

            $result = $this->processBill($billID, $sum, static::PS_PAYPAL);
            if (!$result) {
                $this->log('Paypal: processBill error');
            }
        } while(false);

        exit;
    }

    # --------------------------------------------------------------------
    # Система оплаты LiqPay (https://www.liqpay.com/ru/doc)
    # https://github.com/liqpay/sdk-php/blob/master/LiqPay.php

    protected function liqpay_request()
    {
        # https://www.liqpay.com/ru/doc/callback
        $private = config::sys('bills.liqpay.private_key', '', TYPE_STR);
        $currency = config::sys('bills.liqpay.currency', '', TYPE_STR);

        $data = (!empty($_REQUEST['data']) ? $_REQUEST['data'] : '');
        $signature = (!empty($_REQUEST['signature']) ? $_REQUEST['signature'] : '');
        if ($signature != base64_encode( sha1( $private . $data . $private, 1 ) )) {
            $this->log('Liqpay: Не совпадает Signature: data = "' . $data . '" signature = "'.$signature.'"');
            exit;
        }

        $data = json_decode(base64_decode($data), true);

        $status_success = 'success';
        // $status_success = 'sandbox';  # тестовый платеж
        if (empty($data['status']) || $data['status'] != $status_success) {
            $this->log('Liqpay: Не верный статус платежа. '.print_r($data, true));
            exit;
        }
        if (!is_numeric($data['order_id'])) {
            $this->log('Liqpay: Некорректный номер счета. '.print_r($data, true));
            exit;
        }
        $invoiceID = $data['order_id'];
        if (empty($data['amount']) || empty($data['currency'])) {
            $this->log('Liqpay: Некорректная сумма. '.print_r($data, true));
            exit;
        }
        if ($data['currency'] != $currency) {
            $this->log('Liqpay: Не совпадает валюта платежа. '.print_r($data, true));
            exit;
        }
        $sum = $data['amount'];

        $res = $this->processBill($invoiceID, $sum, static::PS_LIQPAY);
        if ($res === true) {
            $this->redirect( static::url('success') );
        }
        exit;
    }

    # --------------------------------------------------------------------
    # Система оплаты Yandex.Money (https://money.yandex.ru/)
    # https://money.yandex.ru/doc.xml?id=525037

    protected function yandex_request()
    {
        # https://tech.yandex.ru/money/doc/dg/reference/notification-p2p-incoming-docpage/
        $secret = config::sys('bills.yandex.money.secret', '', TYPE_STR);
        $logPost = ' $_POST = '.print_r($_POST, true);

        if (!isset($_POST['sha1_hash'])) {
            $this->log('Yandex: Не найден sha1_hash.'.$logPost);
            exit;
        }

        $verify = array();
        foreach (array('notification_type','operation_id','amount','currency','datetime','sender','codepro','label') as $f) {
            if (isset($_POST[$f])) {
                $verify[$f] = $_POST[$f];
            } else {
                $verify[$f] = '';
            }
        }
        $label = $verify['label'];
        unset($verify['label']);
        $verify = join('&', $verify);
        $verify .= '&'.$secret;
        $verify .= '&'.$label;
        if (sha1($verify) != $_POST['sha1_hash']) {
            $this->log('Yandex: Не совпадает sha1_hash.'.$logPost);
            exit;
        }

        if (!empty($_POST['test_notification'])) {
            $this->log('Yandex: test_notification.'.$logPost);
            exit;
        }
        if (isset($_POST['unaccepted'])) {
            $unaccepted = filter_var($_POST['unaccepted'], FILTER_VALIDATE_BOOLEAN);
            if ($unaccepted) {
                $this->log('Yandex: установлен флаг unaccepted.'.$logPost);
                exit;
            }
        }
        if (isset($_POST['codepro'])) {
            $codepro = filter_var($_POST['codepro'], FILTER_VALIDATE_BOOLEAN);
            if ($codepro) {
                $this->log('Yandex: перевод защищен кодом протекции.'.$logPost);
                exit;
            }
        }
        if (!is_numeric($_POST['label'])) {
            $this->log('Yandex: Некорректный номер счета.'.$logPost);
            exit;
        }
        $invoiceID = $_POST['label'];
        if (empty($_POST['withdraw_amount'])) {
            $this->log('Yandex: Некорректная сумма.'.$logPost);
            exit;
        }
        $sum = $_POST['withdraw_amount'];

        $this->processBill($invoiceID, $sum, static::PS_YANDEX_MONEY);
        exit;
    }
}