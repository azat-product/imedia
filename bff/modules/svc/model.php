<?php

/**
 * Используемые таблицы:
 * TABLE_SVC - таблица услуг/пакетов услуг
 */

class SvcModelBase extends Model
{
    function svcSave($nSvcID, $aData)
    {
        if (isset($aData['settings'])) {
            $aData['settings'] = base64_encode(serialize($aData['settings']));
        }

        $aData['modified'] = $this->db->now();
        $aData['modified_uid'] = $this->security->getUserID();

        if (!empty($nSvcID)) {
            return $this->db->update(TABLE_SVC, $aData, array('id' => $nSvcID));
        } else {
            # определяем порядок добавляемой услуги / пакета услуг:
            $nNum = $this->db->one_data('SELECT MAX(num) FROM ' . TABLE_SVC . ' WHERE type = :type',
                array(':type' => $aData['type'])
            );
            $aData['num'] = intval($nNum) + 1;

            # формируем ID услуги, для битового поля
            if ($aData['type'] == Svc::TYPE_SERVICE) {
                $aCurrentID = $this->db->select_one_column('SELECT id FROM ' . TABLE_SVC . ' WHERE type = :type ORDER BY id ASC',
                    array(':type' => $aData['type'])
                );
                $ID = 1;
                do {
                    if (!in_array($ID, $aCurrentID)) {
                        break;
                    }
                    $ID += $ID;
                } while (true);
                if ($ID < 4294967295) {
                    $aData['id'] = $ID;

                    return $this->db->insert(TABLE_SVC, $aData);
                } else {
                    return 0;
                }
            } else {
                return $this->db->insert(TABLE_SVC, $aData);
            }
        }
    }

    function svcDelete($nSvcID)
    {
        return $this->db->delete(TABLE_SVC, $nSvcID);
    }

    /**
     * Данные об услуге(услугах)
     * @param integer|array $nSvcID ID услуги(услуг)
     * @param array $aFields список необходимых полей
     * @param boolean $bMergeSettings объединять данные с данными из поля settings
     * @param integer $ttl ttl
     * @return mixed
     */
    function svcData($nSvcID, $aFields = array('*'), $bMergeSettings = true, $ttl = 0)
    {
        if (empty($aFields)) {
            $aFields = array('*');
        }
        if (!is_array($aFields)) {
            $aFields = array($aFields);
        }
        $aFields = join(',', $aFields);

        if (empty($nSvcID)) {
            return false;
        }
        if (is_array($nSvcID)) {
            $nSvcID = array_map('intval', $nSvcID);
            if (empty($nSvcID)) {
                return array();
            }
            $aData = $this->db->select_key('SELECT ' . $aFields . ' FROM ' . TABLE_SVC . ' WHERE id IN(' . join(',', $nSvcID) . ')', 'keyword', null, $ttl);
            if (empty($aData)) {
                bff::log('Ошибка получения информации об услугах #[' . join(',', $nSvcID) . ']');

                return array();
            }
            foreach ($aData as &$v) {
                if (isset($v['settings'])) {
                    $v['settings'] = (!empty($v['settings']) && is_string($v['settings']) ? func::unserialize($v['settings']) : array());
                    if ($bMergeSettings) {
                        $v = array_merge($v['settings'], $v);
                        unset($v['settings']);
                    }
                }
            }
            unset($v);
        } else {
            static $cache;
            $cacheKey = $nSvcID . $aFields;
            if (isset($cache[$cacheKey])) {
                return $cache[$cacheKey];
            }

            $aData = $this->db->one_array('SELECT ' . $aFields . ', settings, type FROM ' . TABLE_SVC . '
                            WHERE id = :id', array(':id' => $nSvcID), $ttl
            );
            if (empty($aData)) {
                bff::log('Ошибка получения информации об услуге #' . $nSvcID);

                return false;
            }
            $aData['settings'] = (!empty($aData['settings']) && is_string($aData['settings']) ? func::unserialize($aData['settings']) : array());
            if ($bMergeSettings) {
                $aData = array_merge($aData, $aData['settings']);
                unset($aData['settings']);
            }
            $cache[$cacheKey] = $aData;
        }

        return $aData;
    }

    /**
     * Включена ли услуга
     * @param integer $nSvcID ID услуги
     * @return boolean true - включена
     */
    function svcEnabled($nSvcID)
    {
        $aData = $this->svcData($nSvcID, 'id', true, 60);

        return !empty($aData['on']);
    }

    /**
     * Получаем стоимость услуги
     * @param integer $nSvcID ID услуги
     * @return int
     */
    function svcPrice($nSvcID)
    {
        $aData = $this->svcData($nSvcID, 'price');

        return (isset($aData['price']) ? $aData['price'] : 0);
    }

    /**
     * Список услуг (admin)
     * @param integer $nTypeID ID типа Svc::TYPE_ или 0 (всех типов)
     * @parm string|bool $mModuleName название модуля или FALSE
     * @param array $aExcludeByKeyword ключи сервисов, которые следует исключить
     * @param boolean $bMergeSettings объединять данные с данными из поля settings
     * @return array
     */
    function svcListing($nTypeID, $mModuleName = false, array $aExcludeByKeyword = array(), $bMergeSettings = true)
    {
        $aFilter = array();
        if ($nTypeID > 0) {
            $aFilter['type'] = $nTypeID;
        }
        if (!empty($mModuleName)) {
            $aFilter['module'] = $mModuleName;
        }
        $aFilter[':joinUsers'] = 'S.modified_uid = U.user_id';
        $aFilter = $this->prepareFilter($aFilter, 'S');

        $aData = $this->db->select_key('SELECT S.*, U.login as modified_login
                                    FROM ' . TABLE_SVC . ' S,
                                         ' . TABLE_USERS . ' U
                                    ' . $aFilter['where']
            . ' ORDER BY ' . (!$nTypeID ? 'S.type, ' : '') . 'S.num',
            (!empty($mModuleName) ? 'keyword' : 'id'),
            $aFilter['bind']
        );
        if (empty($aData)) {
            return array();
        }

        foreach ($aData as $k => $v) {
            if (!empty($aExcludeByKeyword) && in_array($k, $aExcludeByKeyword)) {
                unset($aData[$k]);
                continue;
            }
            $settings = (!empty($v['settings']) && is_string($v['settings']) ? func::unserialize($v['settings']) : array());
            if ($bMergeSettings) {
                if (!empty($settings)) {
                    $aData[$k] = array_merge($settings, $v);
                }
            } else {
                $aData[$k]['settings'] = $settings;
            }
        }

        return $aData;
    }

    /**
     * Список id услуг модуля
     * @parm string $sModuleName название модуля
     * @param integer $nTypeID ID типа Svc::TYPE_ или 0 (всех типов)
     * @return array
     */
    function svcIdByModule($sModuleName, $nTypeID = 0)
    {
        $aFilter = array('module' => $sModuleName);
        if ($nTypeID > 0) {
            $aFilter['type'] = $nTypeID;
        }
        $aFilter = $this->prepareFilter($aFilter, 'S');

        $aData = $this->db->select_one_column('SELECT S.id FROM ' . TABLE_SVC . ' S
                                    ' . $aFilter['where']
            . ' ORDER BY S.num',
            $aFilter['bind']
        );
        if (empty($aData)) {
            return array();
        }

        return $aData;
    }

    function svcKeywordExists($sKeyword, $sModuleName, $nSvcID = 0)
    {
        $nSvcID = $this->db->one_data('SELECT id
                                FROM ' . TABLE_SVC . '
                                WHERE keyword = :key
                                  AND module = :module
                                  AND id != :id',
            array(':key' => $sKeyword, ':module' => $sModuleName, ':id' => $nSvcID)
        );

        return !empty($nSvcID);
    }

    function svcOptions($nSelectedID = 0)
    {
        $aData = $this->db->select('SELECT id, title, module, module_title FROM ' . TABLE_SVC . ' ORDER BY id');
        if (empty($aData)) {
            return '';
        }
        foreach ($aData as $k => $v) {
            $aData[$k]['title'] = $v['title'] . ' (' .
                (!empty($v['module_title']) ? $v['module_title'] : $v['module']) . ')';
        }

        return HTML::selectOptions($aData, $nSelectedID, false, 'id', 'title');
    }

    function svcReorder($aNewOrder, $nTypeID)
    {
        if (!empty($aNewOrder)) {
            $num = 1;
            foreach ($aNewOrder as $id) {
                $this->db->update(TABLE_SVC, array('num' => $num++),
                    array('id' => $id, 'type' => $nTypeID)
                );
            }
        }
    }

    /**
     * Перемещение услуги
     * @param string $sOrderField поле, по которому производится сортировка
     * @param string $aCond дополнительные условия
     * @return mixed @see rotateTablednd
     */
    function svcRotate($sOrderField, $aCond = '')
    {
        if (!empty($aCond)) {
            $aCond = ' AND ' . (is_array($aCond) ? join(' AND ', $aCond) : $aCond);
        }

        return $this->db->rotateTablednd(TABLE_SVC, $aCond, 'id', $sOrderField);
    }

}