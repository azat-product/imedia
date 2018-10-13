<?php

abstract class BillsBase_ extends BillsModule
{
    /** @var BillsModel */
    public $model = null;

    public function init()
    {
        parent::init();

        # включаем доступные системы оплаты:
        $this->psystemsAllowed = array(
            self::PS_ROBOX,
            self::PS_WM,
            self::PS_W1,
            self::PS_PAYPAL,
            self::PS_LIQPAY,
            self::PS_YANDEX_MONEY,
        );

        /**
         * Настройки доступных систем оплаты указываются в файле [/config/sys.php]
         * Полный список доступных настроек указан в BillsModuleBase::init методе [/bff/modules/bills/base.php]
         * Формат: 'bills.[ключ системы оплаты].[ключ настройки]'
         * Пример: 'bills.robox.test' - тестовый режим системы оплаты Robokassa
         *
         * URL для систем оплат:
         * Result:  http://example.com/bill/process/(robox|wm)
         * Success: http://example.com/bill/success
         * Fail:    http://example.com/bill/fail
         */
    }

    public function sendmailTemplates()
    {
        $currencyDefault = Site::currencyDefault();
        $aTemplates = array(
            'users_balance_plus' => array(
                'title'       => _t('bills','Пополнение счета'),
                'description' => _t('bills','Уведомление, отправляемое <u>пользователю</u> в случае успешного пополнения счета'),
                'vars'        => array(
                    '{name}'    => _t('users','Имя пользователя'),
                    '{email}'   => _t('','Email'),
                    '{amount}'  => _t('bills','Сумма пополнения [tip]', array('tip'=>'<div class="desc">100 '.$currencyDefault.'</div>')),
                    '{balance}' => _t('bills','Текущий баланс [tip]', array('tip'=>'<div class="desc">100 '.$currencyDefault.'</div>')),
                    '{auth_link}' => _t('users','Ссылка для авторизации'),
                ),
                'impl'        => true,
                'priority'    => 20,
                'enotify'     => 0, # всегда
            ),
            'users_balance_admin_plus' => array(
                'title'       => _t('bills','Пополнение счета администратором'),
                'description' => _t('bills','Уведомление, отправляемое <u>пользователю</u> в случае пополнения счета администратором'),
                'vars'        => array(
                    '{name}'    => _t('users','Имя пользователя'),
                    '{email}'   => _t('','Email'),
                    '{amount}'  => _t('bills','Сумма пополнения [tip]', array('tip'=>'<div class="desc">100 '.$currencyDefault.'</div>')),
                    '{balance}' => _t('bills','Текущий баланс [tip]', array('tip'=>'<div class="desc">100 '.$currencyDefault.'</div>')),
                    '{description}' => _t('bills','Описание'),
                    '{auth_link}' => _t('users','Ссылка для авторизации'),
                ),
                'impl'        => true,
                'priority'    => 21,
                'enotify'     => 0, # всегда
            ),
            'users_balance_admin_minus' => array(
                'title'       => _t('bills','Списание со счета администратором'),
                'description' => _t('bills','Уведомление, отправляемое <u>пользователю</u> в случае списания средств со счета администратором'),
                'vars'        => array(
                    '{name}'    => _t('users','Имя пользователя'),
                    '{email}'   => _t('','Email'),
                    '{amount}'  => _t('bills','Сумма пополнения [tip]', array('tip'=>'<div class="desc">100 '.$currencyDefault.'</div>')),
                    '{balance}' => _t('bills','Текущий баланс [tip]', array('tip'=>'<div class="desc">100 '.$currencyDefault.'</div>')),
                    '{description}' => _t('bills','Описание'),
                    '{auth_link}' => _t('users','Ссылка для авторизации'),
                ),
                'impl'        => true,
                'priority'    => 22,
                'enotify'     => 0, # всегда
            ),
        );

        return $aTemplates;
    }

    /**
     * Метод обрабатывающий ситуацию с удалением пользователя
     * @param integer $userID ID пользователя
     * @param array $options доп. параметры удаления
     */
    public function onUserDeleted($userID, array $options = array())
    {
        $this->model->onUserDeleted($userID, $options);
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts доп. параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        return bff::router()->url('bills-'.$key, $opts, ['dynamic'=>$dynamic,'module'=>'bills']);
    }

    public static function getPaySystems($bBalanceUse = false, $bPromotePage = false)
    {
        $logoUrl = SITEURL_STATIC . '/img/ps/';
        $aData = array(
            'robox'    => array(
                'id'           => self::PS_ROBOX,
                'way'          => '',
                'logo_desktop' => $logoUrl . 'robox.png',
                'logo_phone'   => $logoUrl . 'robox.png',
                'title'        => _t('bills', 'Robokassa'),
                'currency_id'  => 2, # рубли
            ),
            'wm'       => array(
                'id'           => self::PS_WM,
                'way'          => 'wmz',
                'logo_desktop' => $logoUrl . 'wm.png',
                'logo_phone'   => $logoUrl . 'wm.png',
                'title'        => _t('bills', 'Webmoney'),
                'currency_id'  => 3, # доллары
            ),
            'terminal' => array(
                'id'           => self::PS_W1,
                'way'          => 'terminal',
                'logo_desktop' => $logoUrl . 'w1.png',
                'logo_phone'   => $logoUrl . 'w1.png',
                'title'        => _t('bills', 'Терминал'),
                'currency_id'  => 2, # рубли
            ),
            'paypal' => array(
                'id'           => self::PS_PAYPAL,
                'way'          => '',
                'logo_desktop' => $logoUrl . 'paypal.png',
                'logo_phone'   => $logoUrl . 'paypal.png',
                'title'        => _t('bills', 'Paypal'),
                'currency_id'  => 3, # доллары
                'enabled'      => true, # true - включен, false - выключен
            ),
            'liqpay' => array(
                'id'           => self::PS_LIQPAY,
                'way'          => '',
                'logo_desktop' => $logoUrl . 'liqpay.png',
                'logo_phone'   => $logoUrl . 'liqpay.png',
                'title'        => _t('bills', 'Liqpay'),
                'currency_id'  => 1, # гривны
                'enabled'      => true, # true - включен, false - выключен
            ),
            'yandex' => array(
                'id'           => self::PS_YANDEX_MONEY,
                'way'          => 'PC',
                'logo_desktop' => $logoUrl . 'ym_logo.gif',
                'logo_phone'   => $logoUrl . 'ym_logo.gif',
                'title'        => _t('bills', 'С кошелька'),
                'currency_id'  => 2, # рубли
                'enabled'      => true, # true - включен, false - выключен
            ),
            'yandexAC' => array(
                'id'           => self::PS_YANDEX_MONEY,
                'way'          => 'AC',
                'logo_desktop' => $logoUrl . 'ym_logo.gif',
                'logo_phone'   => $logoUrl . 'ym_logo.gif',
                'title'        => _t('bills', 'Банковская карта'),
                'currency_id'  => 2, # рубли
                'enabled'      => true, # true - включен, false - выключен
            ),
            'yandexMC' => array(
                'id'           => self::PS_YANDEX_MONEY,
                'way'          => 'MC',
                'logo_desktop' => $logoUrl . 'ym_logo.gif',
                'logo_phone'   => $logoUrl . 'ym_logo.gif',
                'title'        => _t('bills', 'С мобильного'),
                'currency_id'  => 2, # рубли
                'enabled'      => false, # true - включен, false - выключен
            ),
        );
        if ($bBalanceUse) {
            $aData = array(
                    'balance' => array(
                        'id'           => self::PS_UNKNOWN,
                        'way'          => '',
                        'logo_desktop' => Site::logoURL('bills.paysystems.list.balance.desktop'),
                        'logo_phone'   => Site::logoURL('bills.paysystems.list.balance.phone'),
                        'title'        => _t('bills', 'Счет [name]', array('name' => Site::title('bills.paysystems.balance'))),
                    )
                ) + $aData;
        }

        $aData = bff::filter('bills.pay.systems.user', $aData, array('balanceUse'=>$bBalanceUse, 'promotePage'=>$bPromotePage, 'logoUrl'=>$logoUrl));

        # Исключаем выключенные из списка
        foreach ($aData as $k=>&$v) {
            if (isset($v['enabled']) && empty($v['enabled'])) {
                unset($aData[$k]); continue;
            }
        } unset($v);
        # сортируем по приоритетности
        func::sortByPriority($aData);

        return $aData;
    }
}