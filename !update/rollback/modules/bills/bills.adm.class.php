<?php

class Bills_ extends BillsBase
{
    /**
     * Обработка события пополнения/списания со счета пользователя администратором
     * @param integer $userID ID пользователя
     * @param integer $billID ID счета
     * @param boolean $notify Отправлять уведомление
     */
    public function onUserBalanceAdmin($userID, $billID, $notify)
    {
        # отправим почтовое уведомление пользователю
        $user = Users::model()->userData($userID, array('name', 'email', 'balance', 'user_id', 'user_id_ex', 'last_login', 'lang'));
        $bill = $this->model->billData($billID, array('type', 'amount', 'description'));
        if (empty($user) || empty($bill)) return;

        if ($notify)
        {
            $this->locale->setCurrentLanguage($user['lang'], true);
            $mailData = array(
                'name'    => $user['name'],
                'email'   => $user['email'],
                'user_id' => $userID,
                'amount'  => $bill['amount'].' '.Site::currencyDefault(),
                'balance' => $user['balance'].' '.Site::currencyDefault(),
                'description' => $bill['description'],
                'auth_link' => static::url('my.history', array('alogin' => Users::loginAutoHash($user))),
            );
            if ($bill['type'] == static::TYPE_IN_GIFT) {
                bff::sendMailTemplate($mailData, 'users_balance_admin_plus', $user['email'], false, '', '', $user['lang']);
            }
            else if ($bill['type'] == static::TYPE_OUT_ADMIN) {
                bff::sendMailTemplate($mailData, 'users_balance_admin_minus', $user['email'], false, '', '', $user['lang']);
            }
            $this->locale->setCurrentLanguage(LNG, true);
        }
    }

    public function processPayRequest()
    {
    }

    protected function processBill($nBillID, $fMoney = 0, $nPaySystem = 0, $mDetails = false, $aExtra = array())
    {
        return true;
    }
}