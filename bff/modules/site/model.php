<?php

/**
 * Используемые таблицы:
 * TABLE_PAGES - таблица статических страниц
 * TABLE_PAGES_LANG - таблица статических страниц (мультиязычность)
 * TABLE_COUNTERS - таблица счетчиков
 * TABLE_CURRENCIES - таблица валют
 * TABLE_CURRENCIES_LANG - таблица валют (мультиязычность)
 */

use bff\utils\Files;

define('TABLE_SITE_REQUESTS', DB_PREFIX . 'site_requests');

class SiteModelBase extends Model
{
    # --------------------------------------------------------------------
    # страницы

    public $langPage = array(
        'title'        => TYPE_NOTAGS, # Название
        'content'      => TYPE_STR,    # Содержание
        'mtitle'       => TYPE_NOTAGS, # Meta title
        'mkeywords'    => TYPE_NOTAGS, # Meta keywords
        'mdescription' => TYPE_NOTAGS, # Meta description
    );

    public function pagesListing()
    {
        return $this->db->select('SELECT I.id, L.title, I.filename, I.issystem, I.noindex
                    FROM ' . TABLE_PAGES . ' I, ' . TABLE_PAGES_LANG . ' L
                    WHERE ' . $this->db->langAnd(false) . '
                    ORDER BY L.title'
        );
    }

    public function pageData($nPageID)
    {
        $aData = $this->db->one_array('SELECT P.*, U.login as modified_login
                                FROM ' . TABLE_PAGES . ' P
                                    LEFT JOIN ' . TABLE_USERS . ' U ON P.modified_uid = U.user_id
                                WHERE P.id = :id', array(':id' => $nPageID)
        );
        if (!empty($aData)) {
            $this->db->langSelect($nPageID, $aData, $this->langPage, TABLE_PAGES_LANG);
        }

        return $aData;
    }

    public function pageDataView($sFilename)
    {
        return $this->db->one_array('SELECT P.*, PL.*
                FROM ' . TABLE_PAGES . ' P
                    LEFT JOIN ' . TABLE_PAGES_LANG . ' PL ON ' . $this->db->langAnd(false, 'P', 'PL') . '
                WHERE P.filename = :filename
            ', array(':filename' => $sFilename)
        );
    }

    public function pageDataForSitemap($nPageID, $aFields = array('id', 'filename'))
    {
        if (empty($aFields)) {
            $aFields = array('*');
        } else {
            if (!is_array($aFields)) {
                $aFields = array($aFields);
            }
        }

        return $this->db->one_array('SELECT ' . join(', ', $aFields) . '
                    FROM ' . TABLE_PAGES . ' WHERE id = :id',
            array(':id' => $nPageID)
        );
    }

    public function pageSave($nPageID, $aData)
    {
        $aSave = array(
            'modified'     => $this->db->now(),
            'modified_uid' => $this->security->getUserID(),
            'mtemplate'    => $aData['mtemplate'],
            'noindex'      => $aData['noindex'],
        );

        if (FORDEV) {
            $aSave['issystem'] = $aData['issystem'];
        }
        if (isset($aData['content_publicator'])) {
            $aSave['content_publicator'] = $aData['content_publicator'];
        }
        if (isset($aData['created_uid'])) {
            $aSave['created_uid'] = $aData['created_uid'];
        }

        if ($nPageID > 0) {
            $this->db->langUpdate($nPageID, $aData, $this->langPage, TABLE_PAGES_LANG);
            $this->db->update(TABLE_PAGES, $aSave, array('id' => $nPageID));
            $sFilename = $this->db->one_data('SELECT filename FROM ' . TABLE_PAGES . ' WHERE id = :id', array(':id' => $nPageID));
        } else {
            $aSave['filename'] = $sFilename = $aData['filename'];
            $aSave['created'] = $this->db->now();
            $nPageID = $this->db->insert(TABLE_PAGES, $aSave);
            if ($nPageID) {
                $this->db->langInsert($nPageID, $aData, $this->langPage, TABLE_PAGES_LANG);
            }
        }

        if ($nPageID > 0) {
            Files::putFileContent($this->pagePath($sFilename), serialize($aData));
        }

        return $nPageID;
    }

    public function pageDelete($nPageID)
    {
        $aData = $this->db->one_array('SELECT filename, issystem FROM ' . TABLE_PAGES . ' WHERE id=:id', array(':id' => $nPageID));
        if (empty($aData)) {
            return Errors::IMPOSSIBLE;
        }

        if (($aData['issystem'] && !FORDEV)) {
            return Errors::ACCESSDENIED;
        }

        $sPath = $this->pagePath($aData['filename']);
        if (file_exists($sPath)) {
            unlink($sPath);
        }
        $this->db->delete(TABLE_PAGES, $nPageID);
        $this->db->delete(TABLE_PAGES_LANG, $nPageID);

        return Errors::SUCCESS;
    }

    public function pagePath($sFilename)
    {
        return (Site::$pagesPath . $sFilename . Site::$pagesExtension);
    }

    public function pageFilenameExists($sFilename)
    {
        $res = $this->db->one_data('SELECT filename FROM ' . TABLE_PAGES . '
                               WHERE filename=:fn LIMIT 1',
            array(':fn' => $sFilename)
        );

        return !empty($res);
    }

    # --------------------------------------------------------------------
    # счетчики

    /**
     * Счетчики
     * @param array $aFilter фильтр списка счетчиков
     * @param bool $bCount только подсчет кол-ва счетчиков
     * @param array $aBind подстановочные данные
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function countersListing(array $aFilter, $bCount = false, $aBind = array(), $sqlLimit = '', $sqlOrder = '') //admin
    {
        $aFilter = $this->prepareFilter($aFilter, 'C', $aBind);

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(C.id) FROM ' . TABLE_COUNTERS . ' C ' . $aFilter['where'], $aFilter['bind']);
        }

        return $this->db->select('SELECT C.id, C.created, C.title, C.code, C.enabled
               FROM ' . TABLE_COUNTERS . ' C
               ' . $aFilter['where']
            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
            . $sqlLimit, $aFilter['bind']
        );
    }

    public function countersView()
    {
        return $this->db->select('SELECT id, code FROM ' . TABLE_COUNTERS . ' WHERE enabled = 1 ORDER BY num', null, 60);
    }

    /**
     * Получение данных счетчика
     * @param int $nCounterID ID счетчика
     * @param bool $bEdit при редактировании
     * @return array
     */
    public function counterData($nCounterID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT C.*
                    FROM ' . TABLE_COUNTERS . ' C
                    WHERE C.id = :id',
                array(':id' => $nCounterID)
            );

        } else {
            //
        }

        return $aData;
    }

    /**
     * Сохранение счетчика
     * @param int $nCounterID ID счетчика
     * @param array $aData данные счетчика
     * @return bool|int
     */
    public function counterSave($nCounterID, $aData)
    {
        if (empty($aData)) {
            return false;
        }

        if ($nCounterID > 0) {
            $res = $this->db->update(TABLE_COUNTERS, $aData, array('id' => $nCounterID));

            return !empty($res);
        } else {
            $aData['num'] = ((int)$this->db->one_data('SELECT MAX(num) FROM ' . TABLE_COUNTERS)) + 1;
            $aData['created'] = $this->db->now(); # Дата создания

            $nCounterID = $this->db->insert(TABLE_COUNTERS, $aData);
            if ($nCounterID > 0) {
                //
            }

            return $nCounterID;
        }
    }

    /**
     * Переключатели счетчика
     * @param int $nCounterID ID счетчика
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function counterToggle($nCounterID, $sField)
    {
        switch ($sField) {
            case 'enabled': # Включен
                return $this->toggleInt(TABLE_COUNTERS, $nCounterID, $sField, 'id');
                break;
        }
    }

    /**
     * Перемещение счетчика
     * @param string $sOrderField поле, по которому производится сортировка
     * @param string $aCond дополнительные условия
     * @return mixed @see rotateTablednd
     */
    public function countersRotate($sOrderField, $aCond = '')
    {
        if (!empty($aCond)) {
            $aCond = ' AND ' . (is_array($aCond) ? join(' AND ', $aCond) : $aCond);
        }

        return $this->db->rotateTablednd(TABLE_COUNTERS, $aCond, 'id', $sOrderField);
    }

    /**
     * Удаление счетчика
     * @param int $nCounterID ID счетчика
     * @return bool
     */
    public function counterDelete($nCounterID)
    {
        if (empty($nCounterID)) {
            return false;
        }
        $res = $this->db->delete(TABLE_COUNTERS, $nCounterID);
        if (!empty($res)) {
            return true;
        }

        return false;
    }

    # --------------------------------------------------------------------
    # валюты

    public $langCurrencies = array(
        'title'       => TYPE_NOTAGS, # название
        'title_short' => TYPE_NOTAGS, # название, сокращенное
        'title_decl'  => TYPE_NOTAGS, # название, для склонения
    );

    public function currencyListing()
    {
        return $this->db->select('SELECT I.id, I.enabled, L.title
                                FROM ' . TABLE_CURRENCIES . ' I,
                                     ' . TABLE_CURRENCIES_LANG . ' L
                                WHERE ' . $this->db->langAnd(false) . '
                                ORDER BY I.num'
        );
    }

    public function currencySave($nCurrencyID, $aData)
    {
        if ($nCurrencyID > 0) {
            $this->db->update(TABLE_CURRENCIES, array(
                    'keyword' => $aData['keyword'],
                    'rate'    => $aData['rate'],
                    'enabled' => $aData['enabled'],
                ), array('id' => $nCurrencyID)
            );

            $this->db->langUpdate($nCurrencyID, $aData, $this->langCurrencies, TABLE_CURRENCIES_LANG);
        } else {
            $nNum = (int)$this->db->one_data('SELECT MAX(num) FROM ' . TABLE_CURRENCIES);
            $nNum = $nNum + 1;

            $nCurrencyID = $this->db->insert(TABLE_CURRENCIES, array(
                    'keyword' => $aData['keyword'],
                    'rate'    => $aData['rate'],
                    'enabled' => $aData['enabled'],
                    'num'     => $nNum,
                )
            );

            if ($nCurrencyID > 0) {
                $this->db->langInsert($nCurrencyID, $aData, $this->langCurrencies, TABLE_CURRENCIES_LANG);
            }
        }
    }

    /**
     * Получаем данные о валюте (всех валютах)
     * @param integer|bool $mCurrencyID ID валюты или false - получаем данные о всех доступных валютах
     * @param bool $bEdit
     * @param bool $bEnabledOnly только включенные валюты
     * @return mixed
     */
    public function currencyData($mCurrencyID, $bEdit = false, $bEnabledOnly = true)
    {
        if ($mCurrencyID === false) {
            static $cache = array();
            $cacheKey = $this->locale->getCurrentLanguage() . '-' . ($bEnabledOnly ? 'enabled' : 'any');
            if (isset($cache[$cacheKey])) {
                return $cache[$cacheKey];
            }
            $cache[$cacheKey] = $this->db->select_key('SELECT I.*, L.title_short, L.title, L.title_decl, 0 as a
                                    FROM ' . TABLE_CURRENCIES . ' I, ' . TABLE_CURRENCIES_LANG . ' L
                                    WHERE '.($bEnabledOnly ? 'I.enabled = 1 '.$this->db->langAnd() : $this->db->langAnd(false)) . '
                                    ORDER BY I.num', 'id', null, ($bEdit ? 0 : 60)
            );
            foreach ($cache[$cacheKey] as $k => $v) {
                $cache[$cacheKey][$k]['is_sign'] = (preg_match('/[a-zа-я]/xiu', $v['title_short']) === 0);
            }

            return $cache[$cacheKey];
        } else {
            if ($bEdit) {
                $aData = $this->db->one_array('SELECT * FROM ' . TABLE_CURRENCIES . ' WHERE id = :id', array(':id' => $mCurrencyID));
                if (!empty($aData)) {
                    $this->db->langSelect($mCurrencyID, $aData, $this->langCurrencies, TABLE_CURRENCIES_LANG);
                }
            } else {
                $aData = $this->db->one_array('SELECT I.*, L.title_short, L.title, L.title_decl
                                        FROM ' . TABLE_CURRENCIES . ' I, ' . TABLE_CURRENCIES_LANG . ' L
                                        WHERE I.id = :id ' . $this->db->langAnd(),
                    array(':id' => $mCurrencyID)
                );
            }
        }

        return $aData;
    }

    public function currencyToggle($nCurrencyID)
    {
        return $this->toggleInt(TABLE_CURRENCIES, $nCurrencyID);
    }

    public function currencyDelete($nCurrencyID)
    {
        $res = $this->db->delete(TABLE_CURRENCIES, $nCurrencyID);
        if ($res) {
            $this->db->delete(TABLE_CURRENCIES_LANG, $nCurrencyID);

            return true;
        }

        return false;
    }

    # --------------------------------------------------------------------
    # Проверка запросов

    /**
     * Помечаем дату и время последнего выполненного запрос для указанного действия + userID или IP
     * @param string $actionKey ключ действия
     * @param integer $userID ID пользователя
     * @param string $ipAddress IP адрес запроса
     * @param integer $retryCounter Счетчик кол-ва повторов
     */
    public function requestSet($actionKey, $userID, $ipAddress, $retryCounter = 0)
    {
        $this->db->exec('INSERT INTO ' . TABLE_SITE_REQUESTS . '
            (user_action, user_id, user_ip, created, counter)
            VALUES (:user_action, :user_id, :user_ip, :created, :counter)', array(
                ':user_action' => $actionKey,
                ':user_id'     => $userID,
                ':user_ip'     => $ipAddress,
                ':created'     => $this->db->now(),
                ':counter'     => $retryCounter,
            )
        );
    }

    /**
     * Обновляем данные запроса по фильтру
     * @param array $aFilter фильтр
     * @param array $aUpdate данные для обновления
     * @return boolean
     */
    public function requestUpdate(array $aFilter, array $aUpdate)
    {
        if (empty($aFilter) || empty($aUpdate)) {
            return false;
        }
        $res = $this->db->update(TABLE_SITE_REQUESTS, $aUpdate, $aFilter);
        return !empty($res);
    }

    /**
     * Получаем последний выполненный запрос для указанного действия + userID или IP
     * @param string $actionKey ключ действия
     * @param integer $userID ID пользователя
     * @param string $ipAddress IP адрес запроса
     * @param boolean $retryCounter проверка кол-ва попыток
     * @return integer|array дата выполнения запроса(данные о последнем запросе) или 0
     */
    public function requestGet($actionKey, $userID, $ipAddress, $retryCounter = false)
    {
        $aFilter = array(
            'user_action' => $actionKey
        );
        if ($userID) {
            $aFilter['user_id'] = $userID;
        } else {
            $aFilter['user_ip'] = $ipAddress;
        }
        $aFilter = $this->prepareFilter($aFilter);
        if ($retryCounter) {
            $aLast = $this->db->one_array('SELECT * FROM ' . TABLE_SITE_REQUESTS . '
                ' . $aFilter['where'].'
                ORDER BY created DESC
                LIMIT 1', $aFilter['bind']
            );
            return (empty($aLast) ? 0 : $aLast);
        } else {
            $sLast = $this->db->one_data('SELECT MAX(created) FROM ' . TABLE_SITE_REQUESTS . '
                ' . $aFilter['where'], $aFilter['bind']
            );
            return (empty($sLast) ? 0 : strtotime($sLast));
        }
    }

    /**
     * Очистка неактуальных запросов
     */
    public function requestsClear()
    {
        $this->db->exec('DELETE FROM '.TABLE_SITE_REQUESTS.' WHERE created < :older', array(
            ':older' => date('Y-m-d H:i:s', strtotime('-2 days')),
        ));
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_PAGES      => array('type' => 'table', 'fields' => $this->langPage, 'title' => _t('site', 'Страницы')),
            TABLE_CURRENCIES => array('type' => 'table', 'fields' => $this->langCurrencies, 'title' => _t('site', 'Валюты')),
        );
    }
}