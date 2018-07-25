<?php

abstract class SvcModule extends SvcModuleBase
{
    /**
     * Активируем услугу/пакет услуг или формируем счет для оплаты
     * @param string $sModuleName название модуля, в котором выполняется активация услуги
     * @param integer $nSvcID ID услуги / пакета услуг
     * @param integer $nItemID ID записи
     * @param integer $nPaySystem ID системы оплаты Bills::PS_
     * @param string $sPaySystemWay способ оплаты в системе оплаты
     * @param integer|boolean $fAmount стоимость услуги / пакета услуг или FALSE (получить из настроек) - виртуальная
     * @param integer|boolean $fMoney стоимость услуги / пакета услуг для оплаты или FALSE (получить из настроек) - оплачиваемая
     * @param integer $nCurrencyID ID валюты в которой выполняется оплата
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return array
     */
    function activateOrPay($sModuleName, $nSvcID, $nItemID, $nPaySystem, $sPaySystemWay,
                           $fAmount = false, $fMoney = false, $nCurrencyID = 0, array &$aSvcSettings = array())
    {
        $aResponse = array('activated' => false, 'pay' => false);

        $oModule = bff::module($sModuleName);
        $nUserID = $this->security->getUserID();
        do {
            if (!$oModule instanceof IModuleWithSvc) {
                bff::log('Модуль "' . $sModuleName . '" должен реализовать интерфейс "IModuleWithSvc"');
                $this->errors->reloadPage();
                break;
            }

            $aSvc = $this->svc()->model->svcData($nSvcID);
            if (empty($aSvc) || $aSvc['module'] != $sModuleName) {
                $this->errors->reloadPage();
                break;
            }
            if ($fAmount === false) {
                $fAmount = $aSvc['price'];
            }
            if ($fMoney === false) {
                $fMoney = $fAmount;
            }
            if ($nCurrencyID <= 0) {
                $nCurrencyID = Site::currencyDefault('id');
            }

            $nUserBalance = User::balance();
            if ($nUserBalance >= $fAmount) {
                $aResponse['activated'] = $this->activate($sModuleName, $nSvcID, $aSvc, $nItemID, $nUserID, $fAmount, $fMoney, $aSvcSettings);
            } else {
                # создаем счет для оплаты
                $nBillID = $this->bills()->createBill_InPay($nUserID, $nUserBalance, $fAmount, $fMoney, $nCurrencyID,
                    Bills::STATUS_WAITING,
                    $nPaySystem, $sPaySystemWay,
                    _t('bills', 'Пополнение счета через [system]', array('system' => $this->bills()->getPaySystemTitle($nPaySystem))),
                    $nSvcID, true, # помечаем необходимость активации сразу после оплаты
                    $nItemID, $aSvcSettings
                );
                if (!$nBillID) {
                    $this->errors->set(_t('bills', 'Ошибка создания счета'));
                    break;
                }
                $aResponse['pay'] = true;
                $aResponse['form'] = $this->bills()->buildPayRequestForm($nPaySystem, $sPaySystemWay, $nBillID, $fMoney);
            }
        } while (false);

        return $aResponse;
    }
}