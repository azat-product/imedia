<?php

class BillsModel_ extends BillsModelBase
{

    /**
     * Список счетов по фильтру (frontend)
     * @param array $aFilter фильтр списка
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function billsList(array $aFilter, $bCount, $sqlLimit = '', $sqlOrder = 'created DESC')
    {
        $aFilter = $this->prepareFilter($aFilter, 'B');
        if ($bCount) {
            return (integer)$this->db->one_data('SELECT COUNT(B.id)
                                FROM ' . TABLE_BILLS . ' B
                                ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        $data = $this->db->select('SELECT B.*, DATE(B.created) as created_date
                          FROM ' . TABLE_BILLS . ' B
                          ' . $aFilter['where']
            . (!empty($sqlOrder) ? ' ORDER BY B.' . $sqlOrder : '')
            . $sqlLimit,
            $aFilter['bind']
        );
        if (empty($data)) {
            return array();
        }
        foreach ($data as &$v) {
            $v['is_minus'] = in_array($v['type'], array(Bills::TYPE_OUT_ADMIN, Bills::TYPE_OUT_SERVICE));
        } unset($v);
        # получение данных по item_id
        $itemsData = array();
        foreach (array('bbs', 'shops') as $module) {
            $moduleSvc = Svc::model()->svcIdByModule($module);
            $itemsData[$module] = array();
            foreach ($data as &$v) {
                if ($v['item_id'] && in_array($v['svc_id'], $moduleSvc)) {
                    $itemsData[$module][] = $v['item_id'];
                }
            }
            unset($v);
            if (!empty($itemsData[$module])) {
                switch ($module) {
                    case 'bbs':
                    {
                        $itemsData[$module] = array();
                    }
                    break;
                    case 'shops':
                    {
                        $itemsData[$module] = array();
                    }
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Метод обрабатывающий ситуацию с удалением пользователя
     * @param integer $userID ID пользователя
     * @param array $options доп. параметры удаления
     */
    public function onUserDeleted($userID, array $options = array())
    {
        if (empty($userID)) return;
        $this->db->update(TABLE_BILLS, array('user_id' => 0), array('user_id' => $userID));
    }
}