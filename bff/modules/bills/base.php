<?php

abstract class BillsModuleBase extends Module
{
    /** @var BillsModelBase */
    public $model = null;
    protected $securityKey = 'ae2a940f5b3ebc7ce37bb936e4f217b7';

    # статус счета
    const STATUS_WAITING    = 1; # ожидает
    const STATUS_COMPLETED  = 2; # завершен
    const STATUS_CANCELED   = 3; # отменен
    const STATUS_PROCESSING = 4; # обрабатывается (принят на обработку)

    # тип счета
    const TYPE_IN_PAY      = 1; # пополнение счета - оплата
    const TYPE_IN_GIFT     = 2; # пополнение счета - подарок
    const TYPE_OUT_ADMIN   = 3; # списание со счета администратором
    const TYPE_OUT_SERVICE = 5; # активация услуги / пакета услуг

    # система оплаты (payment system)
    const PS_UNKNOWN = 0;  # ?
    const PS_WM      = 1;  # WebMoney
    const PS_ROBOX   = 2;  # RoboKassa
    const PS_ZPAY    = 4;  # Z-Payment
    const PS_RBK     = 8;  # RBK
    const PS_W1      = 16; # W1
    const PS_PAYPAL  = 32; # PayPal
    const PS_LIQPAY  = 64; # LiqPay
    const PS_YANDEX_MONEY = 128; # Yandex.Money

    /** @var array доступные системы оплаты */
    protected $psystemsAllowed = array();
    /** @var array данные о системах оплаты */
    protected $psystemsData = array();

    public function init()
    {
        parent::init();

        $this->module_title = _t('bills','Счета');

        $this->psystemsData = array(
            static::PS_UNKNOWN => array('id' => static::PS_UNKNOWN, 'title' => _t('bills','Неизвестная'), 'key' => 'unknown'),
            static::PS_WM      => array('id' => static::PS_WM,      'title' => 'Webmoney', 'key' => 'wm', 'desc' => ''),
            static::PS_ROBOX   => array('id' => static::PS_ROBOX,   'title' => 'Робокасса', 'key' => 'robox', 'desc' => ''),
            static::PS_ZPAY    => array('id' => static::PS_ZPAY,    'title' => 'Z-Pay', 'key' => 'zpay', 'desc' => ''),
            static::PS_RBK     => array('id' => static::PS_RBK,     'title' => 'RBKMoney', 'key' => 'rbkmoney', 'desc' => ''),
            static::PS_W1      => array('id' => static::PS_W1,      'title' => 'W1', 'key' => 'w1', 'desc' => ''),
            static::PS_PAYPAL  => array('id' => static::PS_PAYPAL,  'title' => 'PayPal', 'key' => 'paypal', 'desc' => ''),
            static::PS_LIQPAY  => array('id' => static::PS_LIQPAY,  'title' => 'Liqpay', 'key' => 'liqpay', 'desc' => ''),
            static::PS_YANDEX_MONEY => array('id' => static::PS_YANDEX_MONEY, 'title' => 'Yandex.Money', 'key' => 'yandex', 'desc' => ''),
        );

        # полный список доступных настроек систем оплаты:
        /*
            # WebMoney
            'bills.wm.id'         => '000', //webmoney xml интерфейсы
            'bills.wm.secret'     => '000', //webmoney xml интерфейсы
            'bills.wm.result'     => '', // LMI_RESULT_URL
            'bills.wm.success'    => '', // LMI_SUCCESS_URL
            'bills.wm.success_method'=> '', // LMI_SUCCESS_METHOD ('GET','POST')
            'bills.wm.fail'       => '', // LMI_FAIL_URL
            'bills.wm.fail_method'=> '', // LMI_FAIL_METHOD ('GET','POST')
            'bills.wm.wmz'        => '000', // идентификатор Z кошелька получателя
            'bills.wm.wmz_secret' => '000', // секретный ключ Z кошелька получателя
            'bills.wm.wme'        => '000', // идентификатор E кошелька получателя
            'bills.wm.wme_secret' => '000', // секретный ключ E кошелька получателя

            # Robokassa
            'bills.robox.test' => true, // тестовый режим (true|false)
            'bills.robox.login' => '', // Идентификатор магазина
            'bills.robox.pass1' => '', // Пароль #1
            'bills.robox.pass2' => '', // Пароль #2

            # Z-Payment
            'bills.zpay.id'  => '000',
            'bills.zpay.key' => '000',

            # RBK
            'bills.rbkmoney.id'  => '000',
            'bills.rbkmoney.key' => '000',

            # W1
            'bills.w1.id' => '',
            'bills.w1.secret' => '',
                # ID валюты:
                #  643 — Российские рубли
                #  710 — Южно-Африканские ранды
                #  840 — Американские доллары
                #  980 — Украинские гривны
                #  398 — Казахстанские тенге
            'bills.w1.currency' => 980,
                # Способы оплаты (в системе):
                # Платежные терминалы России - CashTerminalRUB
                # Платежные терминалы Украины - CashTerminalUAH
                # Платежные терминалы Молдовы - CashTerminalMDL
                # Яндекс.Деньги - YandexMoneyRUB
                # WebMoney - WebMoneyRUB, WebMoneyUAH, WebMoneyUSD
                # QIWI Кошелек - QiwiWalletRUB
                # Кредитные карты - CreditCardRUB, CreditCardUAH, CreditCardUSD, CreditCardEUR
            'bills.w1.ways' => array('CashTerminalUAH','CreditCardUAH','WebMoneyUAH','WebMoneyUSD','YandexMoneyRUB'), // способы оплаты через w1
            'bills.w1.success' => '',
            'bills.w1.fail' => '',

            # Paypal
            'bills.paypal.button_id' => '', // ID кнопки оплаты PayPal, например 'UMTZ9XW8PDPE8'
            'bills.paypal.host' => 'www.paypal.com', // Домен PayPal, 'www.sandbox.paypal.com' - для тестирования

            # LiqPay
            'bills.liqpay.public_key' => '', // Публичный ключ, например 'i539429889'
            'bills.liqpay.private_key' => '', // Приватный ключ, например 'W2UHWJPWpOWrqUJH9TEJK31zrTxj5jzCkwuALfw9'
            'bills.liqpay.currency' => 'UAH', // Валюта платежа, возможные значения: 'UAH', 'RUB', 'USD', 'EUR'

            № Yandex.Money
            'bills.yandex.money.purse'  => '', // Номер кошелька, например '411015109864170'
            'bills.yandex.money.secret' => '', // Секрет, например 'Yb4tKMTfj7Qe35qPEz5E4IoH'
        */
    }

    /**
     * @return Bills
     */
    public static function i()
    {
        return bff::module('bills');
    }

    /**
     * @return BillsModel
     */
    public static function model()
    {
        return bff::model('bills');
    }

    /**
     * Проверка доступности системы оплаты
     * @param int $nPaySystem Bills::PS_
     * @return bool
     */
    public function isPaySystemAllowed($nPaySystem)
    {
        if (empty($this->psystemsAllowed)
            || empty($nPaySystem)
            || $nPaySystem === self::PS_UNKNOWN
            || !in_array($nPaySystem, $this->psystemsAllowed)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Получаем данные о доступных платежных системах оплаты
     * @return mixed
     */
    public function getPaySystemsDataAllowed()
    {
        return $this->getPaySystemData($this->psystemsAllowed);
    }

    /**
     * Получаем данные о системе оплаты
     * @param string|int|array $mPaySystem ключ системы оплаты или Bills::PS_
     * @param string|boolean $mDataKey ключ необходимых данных или FALSE
     * @return int|string
     */
    public function getPaySystemData($mPaySystem, $mDataKey = false)
    {
        $data = bff::filter('bills.pay.systems.data', $this->psystemsData);
        if (is_int($mPaySystem)) {
            $nPaySystem = $mPaySystem;
        } elseif (is_string($mPaySystem)) {
            $nPaySystem = self::PS_UNKNOWN;
            foreach ($data as $k => $v) {
                if ($v['key'] == $mPaySystem) {
                    $nPaySystem = $k;
                }
            }
        } elseif (is_array($mPaySystem)) {
            $aResult = array();
            foreach ($mPaySystem as $k) {
                if (isset($data[$k])) {
                    $aResult[$k] = $data[$k];
                }
            }

            return $aResult;
        }

        if (!isset($data[$nPaySystem])) {
            $nPaySystem = self::PS_UNKNOWN;
        }
        if (!empty($mDataKey)) {
            if (isset($data[$nPaySystem][$mDataKey])) {
                return $data[$nPaySystem][$mDataKey];
            } else {
                return '';
            }
        } else {
            return $data[$nPaySystem];
        }
    }

    /**
     * Получаем название системы оплаты
     * @param string|int $mPaySystem ключ системы оплаты или Bills::PS_
     * @return string
     */
    public function getPaySystemTitle($mPaySystem)
    {
        return $this->getPaySystemData($mPaySystem, 'title');
    }

    /**
     * Создание счета, тип: пополнение счета
     * @param integer $nUserID ID пользователя
     * @param float $fUserBalance текущий баланс пользователя
     * @param float $fAmount сумма счета (виртуальная)
     * @param float $fMoney сумма счета (оплачиваемая)
     * @param integer $nCurrencyID ID валюты
     * @param integer $nStatus статус счета: Bills::STATUS_
     * @param integer $nPaySystem система оплаты: Bills::PS_
     * @param string $sPaySystemWay способ оплаты в системе
     * @param string $sDescription описание
     * @param integer $nSvcID ID оплачиваемой услуги или 0
     * @param boolean $bSvcActivate активировать услугу сразу после оплаты счета
     * @param integer $nItemID ID записи (для которой активируется услуга) или 0
     * @param array $aSvcSettings дополнительные параметры услуги/нескольких услуг
     * @return integer ID счета
     */
    public function createBill_InPay($nUserID, $fUserBalance, $fAmount, $fMoney, $nCurrencyID,
        $nStatus, $nPaySystem, $sPaySystemWay = '',
        $sDescription = '', $nSvcID = 0, $bSvcActivate = false, $nItemID = 0, array $aSvcSettings = array()
    ) {
        $nBillID = $this->model->billSave(0, array(
                'user_id'      => $nUserID,
                'user_balance' => $fUserBalance,
                'psystem'      => $nPaySystem,
                'psystem_way'  => $sPaySystemWay,
                'type'         => self::TYPE_IN_PAY,
                'status'       => $nStatus,
                'amount'       => $fAmount,
                'money'        => $fMoney,
                'currency_id'  => $nCurrencyID,
                'description'  => $sDescription,
                'svc_id'       => $nSvcID,
                'svc_activate' => ($bSvcActivate ? 1 : 0),
                'svc_settings' => $aSvcSettings,
                'item_id'      => $nItemID,
            )
        );
        if (empty($nBillID)) {
            $this->log(strtr('Неудалось создать счет: пополнение счета пользователя #[user] на сумму [amount]', [
                '[user]' => $nUserID, '[amount]'=>$fAmount,
            ]));
            $nBillID = 0;
        }

        return $nBillID;
    }

    /**
     * Создание счета, тип: активация услуги
     * @param integer $nSvcID ID услуги
     * @param integer $nItemID ID записи (для которой активируется услуга) или 0
     * @param integer $nUserID ID пользователя
     * @param float|integer $fUserBalance текущий баланс пользователя
     * @param float $fAmount сумма счета (виртуальная)
     * @param float $fMoney сумма счета (оплачиваемая)
     * @param integer $nStatus статус счета: Bills::STATUS_
     * @param string $sDescription описание счета
     * @param array $aSvcSettings дополнительные параметры услуги/нескольких услуг
     * @return integer ID счета
     */
    public function createBill_OutService($nSvcID, $nItemID, $nUserID, $fUserBalance, $fAmount,
        $fMoney, $nStatus, $sDescription = '', array $aSvcSettings = array()
    ) {
        $aData = array(
            'user_id'      => $nUserID,
            'user_balance' => $fUserBalance,
            'type'         => self::TYPE_OUT_SERVICE,
            'status'       => $nStatus,
            'amount'       => $fAmount,
            'money'        => $fMoney,
            'description'  => $sDescription,
            'svc_id'       => $nSvcID,
            'svc_settings' => $aSvcSettings,
            'item_id'      => $nItemID,
        );

        if ($nStatus === self::STATUS_COMPLETED) {
            # в случае если счет со статусом "завершен"
            # помечаем дату оплаты счета текущим временем
            $aData['payed'] = $this->db->now();
        }

        $nBillID = $this->model->billSave(0, $aData);
        if (empty($nBillID)) {
            $this->log(strtr('Неудалось создать счет: активация услуги #[svc], пользователь #[user], объект #[item]', [
                '[user]' => $nUserID, '[svc]'=>$nSvcID, '[item]'=>$nItemID,
            ]));
            $nBillID = 0;
        }

        return $nBillID;
    }

    /**
     * Создание счета, тип: списание со счета администратором
     * @param integer $nUserID ID пользователя
     * @param float $fUserBalance текущий баланс пользователя
     * @param float $fAmount сумма счета (виртуальная)
     * @param string $sDescription описание
     * @return integer ID счета
     */
    public function createBill_OutAdmin($nUserID, $fUserBalance, $fAmount, $sDescription = '')
    {
        $nBillID = $this->model->billSave(0, array(
            'user_id'      => $nUserID,
            'user_balance' => $fUserBalance,
            'type'         => self::TYPE_OUT_ADMIN,
            'status'       => self::STATUS_COMPLETED,
            'amount'       => $fAmount,
            'currency_id'  => Site::currencyDefault('id'),
            'description'  => $sDescription,
            'payed'        => $this->db->now(),
        ));
        if (empty($nBillID)) {
            $this->log(strtr('Неудалось списать со счета пользователя #[user], сумму [amount]', [
                '[user]' => $nUserID, '[amount]' => $fAmount,
            ]));
            $nBillID = 0;
        }

        return $nBillID;
    }

    /**
     * Создание счета, тип: подарок
     * @param integer $nUserID ID пользователя
     * @param float $fUserBalance текущий баланс пользователя
     * @param float $fAmount сумма счета (виртуальная)
     * @param string $sDescription описание
     * @return integer ID счета
     */
    public function createBill_InGift($nUserID, $fUserBalance, $fAmount, $sDescription = '')
    {
        $nBillID = $this->model->billSave(0, array(
                'user_id'      => $nUserID,
                'user_balance' => $fUserBalance,
                'type'         => self::TYPE_IN_GIFT,
                'status'       => self::STATUS_COMPLETED,
                'amount'       => $fAmount,
                'currency_id'  => Site::currencyDefault('id'),
                'description'  => $sDescription,
                'payed'        => $this->db->now(),
            )
        );
        if (empty($nBillID)) {
            $this->log(strtr('Неудалось создать счет: подарок, пользователь #[user], сумма [amount]', [
                '[user]' => $nUserID, '[amount]'=>$fAmount,
            ]));
            $nBillID = 0;
        }

        return $nBillID;
    }

    /**
     * Закрываем счет
     * @param int $nBillID ID счета
     * @param bool $bMarkPayed помечаем дату оплаты счета
     * @param int|array|bool $mUserBalance :
     *      - integer|float - текущая сумма на счету пользователя
     *      - array - ['user_id'=>ID пользователя,'amount'=>сумма (виртуальная),'add'=>true - добавить, false - вычесть]
     *      - FALSE - не обновлять информацию о счете пользователя
     * @param mixed|array|string $mDetails доп. детали счета
     * @return bool
     */
    public function completeBill($nBillID, $bMarkPayed = false, $mUserBalance = false, $mDetails = false)
    {
        $aUpdate = array('status' => self::STATUS_COMPLETED);
        # помечаем дату оплаты счета
        if ($bMarkPayed) {
            $aUpdate['payed'] = $this->db->now();
        }
        # помечаем текущий баланс пользователя
        if (!empty($mUserBalance)) {
            if (is_array($mUserBalance)) {
                if (!empty($mUserBalance['user_id'])) {
                    $nBalance = bff::model('users')->userBalance($mUserBalance['user_id']);
                    if ($mUserBalance['add']) { # прибавляем к балансу
                        $aUpdate['user_balance'] = $nBalance + $mUserBalance['amount'];
                    } else { # снимаем с баланса
                        $aUpdate['user_balance'] = $nBalance - $mUserBalance['amount'];
                    }
                }
            } else {
                $aUpdate['user_balance'] = $mUserBalance;
            }
        }
        # помечаем доп.детали
        if ($mDetails !== false) {
            $aUpdate['details'] = (is_array($mDetails) ? serialize($mDetails) : $mDetails);
        }

        return $this->model->billSave($nBillID, $aUpdate);
    }

    /**
     * Обновление состояния счета пользователя
     * @param integer $nUserID ID пользователя
     * @param integer|float $fAmount сумма (виртуальная)
     * @param boolean $bIncrement true - добавить к счету, false - вычесть из счета
     * @return boolean
     */
    public function updateUserBalance($nUserID, $fAmount, $bIncrement)
    {
        if (!$nUserID) {
            return true;
        }

        $sAction = ($bIncrement ? '+' : '-');
        $fAmount = floatval($fAmount);

        $bSuccess = bff::model('users')->userSave($nUserID, array("balance = balance $sAction $fAmount"));
        if (!$bSuccess) {
            $this->log(strtr('Неудалось обновить баланс пользователя #[user], сумма [amount]', [
                '[user]' => $nUserID, '[amount]'=>$sAction.' '.$fAmount,
            ]));

            return false;
        } else {
            if ($this->security->isCurrentUser($nUserID)) {
                # помечаем необходимость обновления данных в сессии
                $this->security->expire();
            }
        }

        return true;
    }

    /**
     * Формирование формы запроса к системе оплаты
     * @param int $nPaySystem система оплаты Bills::PS_
     * @param string $sPaySystemWay способ оплаты (в системе оплаты)
     * @param int $nBillID id счета
     * @param float $fAmout сумма (оплачиваемая)
     * @param array $aExtra доп. информация
     */
    public function buildPayRequestForm($nPaySystem, $sPaySystemWay, $nBillID, $fAmout, array $aExtra = array())
    {
        $sPaySystemKey = $this->getPaySystemData($nPaySystem, 'key');
        $aPaySystemSetting = config::sys(array(), array(), 'bills.' . $sPaySystemKey, true);
        $aData = array();
        foreach ($aPaySystemSetting as $k => $v) {
            $aData[$sPaySystemKey . '_' . $k] = $v;
        }
        $aData['psystem'] = $nPaySystem;
        $aData['psystem_way'] = $sPaySystemWay;
        $aData['bill_id'] = $nBillID;
        $aData['bill_description'] = _t('bills','Оплата счета #[bill.id] ([site.title])', array(
            'bill.id' => $nBillID,
            'site.title' => Site::title('bills.description.payform')
        ));
        $aData['extra'] = $aExtra;
        $aData['amount'] = $fAmout;
        if ($nBillID) {
            $aData['bill_data'] = $aBillData = $this->model->billData($nBillID);
            $aData['svc_id'] = 0;
            if (!empty($aBillData['svc_id'])) {
                $aData['svc_id'] = $aBillData['svc_id'];
                $aData['svc_data'] = Svc::model()->svcData($aBillData['svc_id']);
            }
        }

        if (!empty($aExtra['template'])) {
            $form = $this->viewPHP($aData, $aExtra['template']);
        } else {
            $form = $this->viewPHP($aData, 'pay.request.form', $this->module_dir_tpl_core);
        }
        return bff::filter('bills.pay.form', $form, $nPaySystem, $aData);
    }

    /**
     * Формирование суммы для оплаты (оплачиваемая)
     * @param float $amount сумма оплаты (виртуальная)
     * @param string $paySystemKey ключ системы оплаты {static::getPaySystems}
     * @return array ['amount'=>сумма для оплаты, 'currency'=>валюта для оплаты]
     */
    public static function getPayAmount($amount, $paySystemKey)
    {
        $currencyDefault = Site::currencyDefault('id');
        $result = array(
            'amount'   => $amount,
            'currency' => $currencyDefault,
        );
        # сумма оплаты указана некорректно
        if ($amount <= 0 || !method_exists('Bills', 'getPaySystems')) {
            return $result;
        }

        $paySystems = static::getPaySystems(true);

        if (# внутренний счет (в основной валюте)
            $paySystemKey === 'balance'
            # валюта для данного способа оплаты не указана > считаем в основной валюте
            || empty($paySystems[$paySystemKey]['currency_id'])
            # валюта данного способа оплаты является основной валютой > сумма уже указана в основной валюте
            || $paySystems[$paySystemKey]['currency_id'] == $currencyDefault) {
            return $result;
        }

        # конвертируем в валюту оплаты
        $amountPay = Site::currencyPriceConvert($amount, $currencyDefault, $paySystems[$paySystemKey]['currency_id'], false);
        if (fmod($amountPay, 1) > 0) {
            $amountPay = round($amountPay, 2);
        }
        return array(
            'amount'   => $amountPay,
            'currency' => $paySystems[$paySystemKey]['currency_id'],
        );
    }

    /**
     * Формирование текста ошибки, возникающей в процессе обработки запроса от платежной системы
     * @param string $sKey ключ ошибки
     * @param mixed $mPrint
     * @return string
     */
    public function payError($sKey, $mPrint = false)
    {
        $sMessage = '';
        switch ($sKey) {
            case 'off':
                $sMessage = _t('bills', 'Cпособ оплаты отключен');
                break;
            case 'no_params':
                $sMessage = _t('bills', 'Не переданы параметры');
                break;
            case 'crc_error':
                $sMessage = _t('bills', 'Неверная контрольная сумма');
                break;
            case 'amount_error':
                $sMessage = _t('bills', 'Неверная сумма');
                break;
            case 'wrong_bill_id':
                $sMessage = _t('bills', 'Неверный формат номера счёта');
                break;
            case 'pay_error':
                $sMessage = _t('bills', 'Ошибка оплаты счёта');
                break;
            case 'demo_fobidden':
                $sMessage = _t('bills', 'Демо режим запрещён');
                break;
        }
        if ($mPrint === true) {
            echo $this->errors->viewError(array(
                    'title'   => _t('bills', 'Ошибка оплаты счёта'),
                    'message' => $sMessage
                ), 'error.pay'
            );
            exit;
        } elseif ($mPrint === 2) {
            echo $sMessage;
            exit;
        }

        return $sMessage;
    }

    /**
     * Формируем список доступных статусов счета
     * @param array $aSkipStatus список статусов, которые необходимо исключить из результата
     * @return string
     */
    protected function getStatusData($aSkipStatus = array())
    {
        $aData = bff::filter('bills.status.list', array(
            self::STATUS_COMPLETED  => _t('bills', 'завершен'),
            self::STATUS_WAITING    => _t('bills', 'незавершен'),
            self::STATUS_CANCELED   => _t('bills', 'отменен'),
            self::STATUS_PROCESSING => _t('bills', 'обрабатывается'),
        ));

        if (!empty($aSkipStatus)) {
            $aData = array_diff_key($aData, $aSkipStatus);
        }

        return $aData;
    }

    /**
     * Формируем список статусов счета в виде select:options
     * @param mixed $nSelected ID выбранного статуса
     * @param array $aSkipStatus список статусов, которые необходимо исключить из результата
     * @param mixed $mEmptyOption @see HTML::selectOptions
     * @return string
     */
    protected function getStatusOptions($nSelected = null, $aSkipStatus = array(), $mEmptyOption = false)
    {
        $aStatus = $this->getStatusData($aSkipStatus);

        return HTML::selectOptions($aStatus, $nSelected, $mEmptyOption);
    }

    /**
     * Формируем список типов счета
     * @param mixed $nSelected ID выбранного типа
     * @param array $aSkipTypes список типов, которые необходимо исключить из результата
     * @param mixed $mEmptyOption @see HTML::selectOptions
     * @return string
     */
    protected function getTypeOptions($nSelected = null, $aSkipTypes = array(), $mEmptyOption = false)
    {
        $aTypes = array(
            self::TYPE_IN_PAY      => _t('bills', 'пополнение счета'),
            self::TYPE_OUT_ADMIN   => _t('bills', 'списание со счета'),
            self::TYPE_IN_GIFT     => _t('bills', 'подарок'),
            self::TYPE_OUT_SERVICE => _t('bills', 'оплата услуги')
        );

        if (!empty($aSkipTypes)) {
            foreach ($aSkipTypes as $k) {
                if (isset($aTypes[$k])) {
                    unset($aTypes[$k]);
                }
            }
        }

        return HTML::selectOptions($aTypes, $nSelected, $mEmptyOption);
    }

    /**
     * Получаем кошелек webmoney
     * @param string $sWebmoneyWay способ оплаты webmoney: 'wmz', 'wme', 'wmr', 'wmu', ...
     * @param boolean $bSecretKey true - возвращать секретный ключ, false - возвращать номер кошелька
     * @return string
     */
    protected function wm_purse($sWebmoneyWay, $bSecretKey = false)
    {
        return config::sys('bills.wm.' . $sWebmoneyWay . ($bSecretKey ? '_secret' : ''));
    }

    /**
     * Логирование процесса оплаты
     * @param string|array $message текст или данные для логирования
     * @param integer $level текст или данные для логирования
     * @param array $context
     * @return mixed
     */
    protected function log($message, $level = Logger::ERROR, array $context = array())
    {
        return bff::log($message, $level, 'bills.log', $context);
    }
}