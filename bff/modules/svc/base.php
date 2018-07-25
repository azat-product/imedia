<?php

abstract class SvcModuleBase extends Module
{
    /** @var SvcModelBase */
    public $model = null;
    protected $securityKey = '727773e8de5306e77cc8bd3dafe2ef3c';

    # тип
    const TYPE_SERVICE     = 1; # услуга
    const TYPE_SERVICEPACK = 2; # пакет услуг

    public function init()
    {
        parent::init();
        $this->module_title = _t('svc','Услуги');
    }

    /**
     * @return Svc
     */
    public static function i()
    {
        return bff::module('svc');
    }

    /**
     * @return SvcModel
     */
    public static function model()
    {
        return bff::model('svc');
    }

    /**
     * Активируем услугу/пакет услуг
     * @param string $sModuleName название модуля, в котором выполняется активация услуги
     * @param integer $nSvcID ID услуги / пакета услуг
     * @param array|mixed $aSvcData данные об активируемой услуге/пакете услуг
     * @param integer $nItemID ID записи
     * @param integer $nUserID ID пользователя
     * @param integer|boolean $nSvcPrice стоимость услуги / пакета услуг или FALSE (получить из настроек)
     * @param integer|boolean $fMoney стоимость услуги / пакета услуг для оплаты или FALSE (получить из настроек)
     * @param array $aSvcSettings @ref дополнительные параметры
     * @return array
     */
    function activate($sModuleName, $nSvcID, $aSvcData = array(), $nItemID, $nUserID,
        $nSvcPrice = false, $fMoney = false, array &$aSvcSettings = array())
    {
        $sUserError = _t('', 'Ошибка активации услуги');

        do {
            try {
                $oModule = bff::module($sModuleName);
            } catch (\Exception $e) {
                bff::log($e->getMessage());
                $this->errors->set($sUserError);
                break;
            }

            if (!$oModule instanceof IModuleWithSvc) {
                bff::log('Модуль "' . $sModuleName . '" должен реализовать интерфейс "IModuleWithSvc" [svc::activate]');
                $this->errors->set($sUserError);
                break;
            }

            if (empty($aSvcData)) {
                $aSvcData = $this->model->svcData($nSvcID);
            }
            if (empty($aSvcData) || $aSvcData['module'] != $sModuleName) {
                bff::log('Активируемая услуга не связана с модулем "' . $sModuleName . '" [svc::activate]');
                $this->errors->set($sUserError);
                break;
            }
            if ($nSvcPrice === false) {
                $nSvcPrice = $aSvcData['price'];
            }
            if ($fMoney === false) {
                $fMoney = $nSvcPrice;
            }

            $nUserBalance = $this->users()->model->userBalance($nUserID);
            $mSuccess = $oModule->svcActivate($nItemID, $nSvcID, $aSvcData, $aSvcSettings);
            if ($mSuccess !== false) {
                if ($mSuccess === 2) {
                    # 1) создаем закрытый счет активации услуги без списывания средств
                    $nBillID = $this->bills()->createBill_OutService($nSvcID, $nItemID, $nUserID, $nUserBalance,
                        0, 0, Bills::STATUS_COMPLETED, $oModule->svcBillDescription($nItemID, $nSvcID, false, $aSvcSettings),
                        $aSvcSettings
                    );
                } else {
                    # 1) создаем закрытый счет активации услуги
                    $nBillID = $this->bills()->createBill_OutService($nSvcID, $nItemID, $nUserID, ($nUserBalance - $nSvcPrice),
                        $nSvcPrice, $fMoney, Bills::STATUS_COMPLETED, $oModule->svcBillDescription($nItemID, $nSvcID, false, $aSvcSettings),
                        $aSvcSettings
                    );
                    # 2) снимаем деньги со счета пользователя
                    $this->bills()->updateUserBalance($nUserID, $nSvcPrice, false);
                }

                if (!empty($aSvcSettings['callback'])) {
                    if (is_string($aSvcSettings['callback'])) {
                        if (method_exists($oModule, $aSvcSettings['callback'])) {
                            call_user_func(array($oModule, $aSvcSettings['callback']), $nBillID);
                        }
                    }
                }

                return true;
            }

        } while (false);

        return false;
    }

    /**
     * Метод, вызываемый по-крону
     */
    function cron()
    {
        if (!bff::cron()) {
            return;
        }

        /**
         * Вызываем метод "svcCron", в тех модулях, в которых он реализован
         */
        bff::i()->callModules('svcCron');
    }

    function getSvcTypes()
    {
        $aTypes = array(
            self::TYPE_SERVICE => array(
                'id'           => self::TYPE_SERVICE,
                'title_select' => _t('svc','Услуга'),
                'title_list'   => _t('svc','Услуги'),
            ),
        );

        return $aTypes;
    }

}