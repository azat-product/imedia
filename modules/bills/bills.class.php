<?php

class Bills_ extends BillsBase
{
    public function my_bill()
    {
        if ($this->input->getpost('pay', TYPE_UINT) > 0) {
            return $this->my_pay();
        }

        return $this->my_history();
    }

    protected function my_history()
    {
        if (!User::id()) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }

        $this->security->setTokenPrefix('bills-my-history');

        $aData = array(
            'list'   => array(),
            'pgn'    => '',
            'pgn_pp' => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>15)), 'c' => 15),
                25 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>25)), 'c' => 25),
                50 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>50)), 'c' => 50),
            ),
        );
        $f = $this->input->postgetm(array(
                'page' => TYPE_UINT, # страница
                'pp'   => TYPE_INT, # кол-во на страницу
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        if (!isset($aData['pgn_pp'][$f_pp])) {
            $f_pp = 15;
        }

        $aFilter = array('user_id' => User::id(), 'status' => self::STATUS_COMPLETED);
        $nTotal = $this->model->billsList($aFilter, true);
        $oPgn = new Pagination($nTotal, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);

        if ($nTotal > 0) {
            $aData['pgn'] = $oPgn->view(array(), tpl::PGN_COMPACT);
            $aData['list'] = $this->model->billsList($aFilter, false, $oPgn->getLimitOffset());
        }

        $aData['curr'] = Site::currencyDefault();
        $aData['statusText'] = array(
            static::TYPE_IN_PAY => _t('bills', 'Пополнение счета'),
            static::TYPE_IN_GIFT => _t('bills', 'Пополнение счета'),
            static::TYPE_OUT_SERVICE => _t('bills', 'Оплата усуги'),
        );
        $aData['list'] = $this->viewPHP($aData, 'my.history.list');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'pgn'   => $aData['pgn'],
                    'list'  => $aData['list'],
                    'total' => $nTotal,
                )
            );
        }

        $aData['f'] = & $f;
        $aData['list_empty'] = ($nTotal <= 1);
        $aData['balance'] = $this->security->getUserBalance();
        $aData['page'] = $oPgn->getCurrentPage();

        return $this->viewPHP($aData, 'my.history');
    }

    protected function my_pay()
    {
        if (!User::id()) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }

        $this->security->setTokenPrefix('bills-my-pay');

        # данные о доступных способах оплаты
        $paySystems = static::getPaySystems(false);
        $ps = $this->input->getpost('ps', TYPE_STR);
        if (!$ps || !array_key_exists($ps, $paySystems)) {
            $ps = key($paySystems);
        }
        foreach ($paySystems as $k => &$v) {
            $v['active'] = ($k == $ps);
        }
        unset($v);

        # сумма к оплате в валюте по-умолчанию
        $amount = $this->input->getpost('amount', TYPE_UNUM);

        if (Request::isAJAX()) {
            $amount = round($amount, 2);
            $response = array('amount' => $amount);

            do {

                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$amount) {
                    $this->errors->set(_t('svc', 'Сумма пополнения указана некорректно'), 'amount');
                    break;
                }

                $ps_id = $paySystems[$ps]['id'];
                $ps_way = $paySystems[$ps]['way'];
                # конвертируем сумму в валюту для оплаты по курсу
                $pay = static::getPayAmount($amount, $ps);
                bff::hook('bills.pay.submit', $amount, $ps, $pay);

                # создаем счет: "Пополнение счета"
                $billID = $this->createBill_InPay(User::id(), $this->security->getUserBalance(),
                    $amount, # сумма в валюте сайте
                    $pay['amount'], # сумма к оплате
                    $pay['currency'], # валюта суммы к оплате
                    self::STATUS_WAITING,
                    $ps_id, $ps_way,
                    _t('bills', 'Пополнение счета через [system]', array('system' => $this->getPaySystemTitle($ps_id)))
                );
                if (!$billID) {
                    $this->errors->set(_t('svc', 'Ошибка пополнения счета, обратитесь к администрации'));
                    break;
                }

                # формируем форму запроса для системы оплаты
                $response['form'] = $this->buildPayRequestForm($ps_id, $ps_way, $billID, $pay['amount']);

            } while (false);

            $this->ajaxResponseForm($response);
        }

        $aData['psystems'] = & $paySystems;
        $aData['amount'] = $amount;
        $aData['balance'] = $this->security->getUserBalance();

        return $this->viewPHP($aData, 'my.pay');
    }

    # ---------------------------------------------------------------------------------

    /**
     * Обработка запроса от системы оплаты
     * @param string ::get 'psystem' система оплаты
     * @return string
     */
    public function processPayRequest()
    {
        $sPaySystem = $this->input->get('psystem', TYPE_NOTAGS);
        $sPayRequestMethod = $sPaySystem . '_request';
        bff::hook('bills.pay.process', $sPaySystem);

        $aData = $this->getPaySystemData($sPaySystem);
        if (!method_exists($this, $sPayRequestMethod)) {
            $this->log('Данный способ оплаты отключен: ' . $aData['title'].' ['.$sPaySystem.']');

            return $this->payError('off');
        } else {
            $this->$sPayRequestMethod();
        }
    }

    /**
     * Оплата счёта на основе данных от платёжной системы
     * @param int $nBillID ID счета (в таблице TABLE_BILLS)
     * @param float|int $fMoney сумма счета (оплачиваемая)
     * @param int $nPaySystem ID системы оплаты
     * @param mixed $mDetails детали от платежной системы
     * @param array $mExtra доп.параметры (если необходимо)
     * @return mixed
     */
    public function processBill($nBillID, $fMoney = 0, $nPaySystem = 0, $mDetails = false, $aExtra = array())
    {
        $sPaySystem = $this->getPaySystemTitle($nPaySystem);
        # Проверяем ID счета
        if (!is_numeric($nBillID) || $nBillID <= 0) {
            $this->log($sPaySystem . ': некорректный номер счета, #' . $nBillID);

            return $this->payError('wrong_bill_id');
        }

        $aBill = $this->model->billData($nBillID, array(
                'user_id',
                'psystem',
                'status',
                'amount',
                'money',
                'svc_id',
                'svc_activate',
                'svc_settings',
                'item_id'
            )
        );
        if (empty($aBill)) {
            $this->log($sPaySystem . ': Оплачен несуществующий счёт #' . $nBillID);

            return $this->payError('pay_error');
        }

        # Проверяем доступность способа оплаты
        if ($nPaySystem !== intval($aBill['psystem'])) {
            $this->log($sPaySystem . ': Cчёт #' . $nBillID . ' выставлен для оплаты другой системой оплаты (' . $this->getPaySystemTitle($aBill['psystem']) . ')');

            return $this->payError('pay_error');
        }

        # Проверяем статус счета
        if ($aBill['status'] == self::STATUS_CANCELED ||
            $aBill['status'] == self::STATUS_COMPLETED
        ) {
            $this->log($sPaySystem . ': Оплачен уже ранее оплаченный счёт или счёт с другим статусом, #' . $nBillID);

            return $this->payError('pay_error');
        }

        # Проверка суммы
        if ($fMoney < $aBill['money']) {
            $this->log("$sPaySystem: Сумма оплаты($fMoney) счета #$nBillID меньше выставленной ранее({$aBill['money']})");

            return $this->payError('amount_error');
        }

        # Закрываем счет:
        # - обновляем статус на "завершен"
        # - помечаем дату оплаты текущей
        if (!$this->completeBill($nBillID, true, array(
                'user_id' => $aBill['user_id'],
                'amount'  => $aBill['amount'],
                'add'     => true
            ), $mDetails
        )
        ) {
            return _t('bills', 'Ошибка закрытия счета #[id]', array('id' => $nBillID));
        } else {
            # - пополняем счет пользователя на сумму
            $this->updateUserBalance($aBill['user_id'], $aBill['amount'], true);

            # отправим почтовое уведомление пользователю
            $user = Users::model()->userData($aBill['user_id'], array('name', 'email', 'balance', 'user_id', 'user_id_ex', 'last_login', 'lang'));
            $this->locale->setCurrentLanguage($user['lang'], true);
            $aMailData = array(
                'name'    => $user['name'],
                'email'   => $user['email'],
                'user_id' => $aBill['user_id'],
                'amount'  => $aBill['amount'].' '.Site::currencyDefault(),
                'balance' => $user['balance'].' '.Site::currencyDefault(),
                'auth_link' => static::url('my.history', array('alogin' => Users::loginAutoHash($user))),
            );
            bff::sendMailTemplate($aMailData, 'users_balance_plus', $user['email'], false, '', '', $user['lang']);
            $this->locale->setCurrentLanguage(LNG, true);
        }

        do {

            # Активируем услугу, если:
            # - оплачивалась услуга (svc_id > 0)
            # - если отмечена необходимость ее активации после оплаты (svc_activate = 1)
            $nSvcID = $aBill['svc_id'];
            if ($nSvcID > 0 && !empty($aBill['svc_activate'])) {
                $oSvc = $this->svc();
                $aSvcData = $oSvc->model->svcData($nSvcID);
                if (!empty($aSvcData)) {
                    # Активируем услугу
                    # Снимаем деньги со счета пользователя
                    $mResult = $oSvc->activate($aSvcData['module'], $nSvcID, $aSvcData, $aBill['item_id'], $aBill['user_id'],
                        $aBill['amount'], $aBill['money'], $aBill['svc_settings']);
                    if ($mResult === false) {
                        $this->log('Ошибка активации услуги: #' . $nSvcID . ', счет: ' . $nBillID);
                        break;
                    }
                } else {
                    $this->log('Ошибка активации услуги: #' . $nSvcID . ', счет: ' . $nBillID);
                    break;
                }
            }

        } while (false);

        return true;
    }

    # ---------------------------------------------------------------------------------

    public function success()
    {
        bff::hook('bills.pay.success');
        $sTitle = _t('bills', 'Пополнение счета');
        $sMessage = _t('bills', 'Вы успешно пополнили счет');

        if (User::id()) {
            $sMessage .= _t('bills', '<br/>На вашем счету: <a href="[link]">[balance] [curr]</a>',
                array(
                    'link'    => static::url('my.history'),
                    'balance' => $this->security->getUserBalance(true),
                    'curr'    => Site::currencyDefault(),
                )
            );
        }

        return $this->showSuccess($sTitle, $sMessage);
    }

    public function fail()
    {
        bff::hook('bills.pay.fail');
        $sPaySystemKey = $this->input->get('w', TYPE_NOTAGS);
        $aPaySystemTitle = $this->getPaySystemTitle($sPaySystemKey);

        $sTitle = _t('bills', 'Оплата счета');
        $sMessage = _t('bills', 'Ошибка оплаты счета');
        if (!empty($sPaySystemKey)) {
            $sMessage .= _t('bills', ' системой "[way]"', array(
                    'way' => $aPaySystemTitle
                )
            );
        }

        return $this->showForbidden($sTitle, $sMessage);
    }

    # Вспомогательный результирующий метод для системы оплаты LiqPay
    public function result()
    {
        bff::hook('bills.pay.result');
        $id = $this->input->get('id', TYPE_UINT);
        do {
            if (!$id) {
                break;
            }

            $billData = $this->model->billData($id, array('status','type','payed'));
            if (empty($billData)) {
                break;
            }

            if ($billData['status'] != static::STATUS_COMPLETED ||
                $billData['type'] != static::TYPE_IN_PAY ||
                $billData['payed'] == '0000-00-00 00:00:00' ||
                (time() - strtotime($billData['payed']) > 3600)) {
                break;
            }

            return $this->success();
        } while(false);

        return $this->fail();
    }

}