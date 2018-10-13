<?php

define('TABLE_LANDING_PAGES',      DB_PREFIX . 'landingpages');
define('TABLE_LANDING_PAGES_LANG', DB_PREFIX . 'landingpages_lang');
define('TABLE_REDIRECTS',          DB_PREFIX . 'redirects');

class SEOModelBase extends Model
{
    /** @var SeoBase */
    protected $controller;
    public $langLandingPages = array(
        'mtitle'        => TYPE_NOTAGS,  # Meta Title
        'mkeywords'     => TYPE_NOTAGS,  # Meta Keywords
        'mdescription'  => TYPE_NOTAGS,  # Meta Description
    );

    public function init()
    {
        parent::init();

        if (SEO::landingPagesEnabled()) {
            # Добавляем доп. поля посадочных страниц
            $extraFields = SEO::landingPagesFields();
            if (!empty($extraFields)) {
                foreach ($extraFields as $k=>$v) {
                    if (is_string($k) && !isset($this->langLandingPages[$k])) {
                        $this->langLandingPages[$k] = (!empty($v['type']) && $v['type'] == 'wy' ? TYPE_STR : TYPE_NOTAGS);
                    }
                }
            }
        }
    }

    # --------------------------------------------------------------------
    # Посадочные страницы

    /**
     * Список страниц (admin)
     * @param array $aFilter фильтр списка страниц
     * @param bool $bCount только подсчет кол-ва страниц
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function landingpagesListing(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'LP');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(LP.id) FROM '.TABLE_LANDING_PAGES.' LP '.$aFilter['where'], $aFilter['bind']);
        }

        if (empty($sqlOrder)) {
            $sqlOrder = 'LP.id DESC';
        }

        return $this->db->select('SELECT LP.id, LP.landing_uri, LP.original_uri, LP.title, LP.enabled, LP.joined
               FROM '.TABLE_LANDING_PAGES.' LP
               '.$aFilter['where']
               .' ORDER BY '.$sqlOrder
               .$sqlLimit, $aFilter['bind']);
    }

    /**
     * Список страниц (frontend)
     * @param array $aFilter фильтр списка страниц
     * @param bool $bCount только подсчет кол-ва страниц
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function landingpagesList(array $aFilter = array(), $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        if ( ! $bCount) $aFilter[':lang'] = $this->db->langAnd(false, 'LP', 'LPL');
        $aFilter = $this->prepareFilter($aFilter, 'LP');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(LP.id) FROM '.TABLE_LANDING_PAGES.' LP '.$aFilter['where'], $aFilter['bind']);
        }

        $aData = $this->db->select('SELECT LP.id
                                  FROM '.TABLE_LANDING_PAGES.' LP, '.TABLE_LANDING_PAGES_LANG.' LPL
                                  '.$aFilter['where'].'
                                  '.( ! empty($sqlOrder) ? ' ORDER BY '.$sqlOrder : '').'
                                  '.$sqlLimit, $aFilter['bind']);

        if ( ! empty($aData))
        {
            //
        }

        return $aData;
    }

    /**
     * Формирование данных о посадочных страницах для файла Sitemap.xml
     * @param boolean $callback
     * @param string $priority приоритетность url
     * @param array $opts доп. параметры
     * @return array|callable [['l'=>'url страницы','m'=>'дата последних изменений'],...]
     */
    public function landingpagesSitemapXmlData($callback = true, $priority = '', array $opts = array())
    {
        if ($callback) {
            return function($count = false, callable $callback = null) use ($priority) {
                if ($count) {
                    return $this->db->one_data('SELECT COUNT(*) FROM '.TABLE_LANDING_PAGES.' WHERE enabled = 1');
                } else {
                    $languageKey = $this->locale->getDefaultLanguage();
                    $this->db->select_iterator('
                        SELECT landing_uri AS l, DATE_FORMAT(modified, :format) as m
                        FROM '.TABLE_LANDING_PAGES.'
                        WHERE enabled = 1
                        ORDER BY modified DESC',
                        array(':format' => '%Y-%m-%d'),
                        function (&$row) use ($languageKey, &$callback, $priority) {
                            $row['m'] = '';
                            $row['l'] = bff::urlBase(false, $languageKey).$row['l'];
                            if ( ! empty($priority)) {
                                $row['p'] = $priority;
                            }
                            $callback($row);
                        });
                }
                return false;
            };
        }

        $aData = $this->db->select('SELECT landing_uri AS l, DATE_FORMAT(modified, :format) as m
                                  FROM '.TABLE_LANDING_PAGES.'
                                  WHERE enabled = 1
                                  ORDER BY modified DESC', array(
                                    ':format' => '%Y-%m-%d',
                                  ));
        if (!empty($aData)) {
            $languageKey = $this->locale->getDefaultLanguage();
            foreach ($aData as &$v) {
                $v['m'] = '';
                $v['l'] = bff::urlBase(false, $languageKey).$v['l'];
                if ( ! empty($priority)) {
                    $v['p'] = $priority;
                }
            } unset ($v);
            return $aData;
        } else {
            return array();
        }
    }

    /**
     * Получение данных страницы
     * @param integer $nLandingpageID ID страницы
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function landingpageData($nLandingpageID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT LP.*
                    FROM '.TABLE_LANDING_PAGES.' LP
                    WHERE LP.id = :id',
                    array(':id'=>$nLandingpageID));
            if ( ! empty($aData)) {
                $this->db->langSelect($nLandingpageID, $aData, $this->langLandingPages, TABLE_LANDING_PAGES_LANG);
            }
        } else {
            //
        }
        return $aData;
    }

    /**
     * Получение данных страницы по URI
     * @param string $landingUri URI посадочной страницы
     * @param boolean $enabledOnly только включенные
     * @return array|boolean
     */
    public function landingpageDataByURI($landingUri, $enabledOnly = true)
    {
        if (empty($landingUri)) {
            return false;
        }
        $aData = $this->db->one_array('SELECT P.*, PL.*
                FROM '.TABLE_LANDING_PAGES.' P,
                     '.TABLE_LANDING_PAGES_LANG.' PL
                WHERE P.landing_uri = :uri'.($enabledOnly ? ' AND P.enabled = 1' : '').$this->db->langAnd(true, 'P', 'PL'),
                array(':uri'=>$landingUri));
        if (empty($aData)) {
            return false;
        }
        return $aData;
    }

    /**
     * Поиск подходящей посадочной страницы на основе вариантов URI текущего запроса
     * @param array $requestVariations варианты URI текущего запроса
     * @param boolean $enabledOnly только включенные
     * @return array|boolean
     */
    public function landingpageDataByRequest(array $requestVariations, $enabledOnly = true)
    {
        if (empty($requestVariations)) {
            return false;
        }

        $uriFilter = array(); $i = 1;
        foreach ($requestVariations as $v) {
            $uriFilter[':from'.$i++] = $v;
        }

        $data = $this->db->select('SELECT P.*, PL.*
                FROM '.TABLE_LANDING_PAGES.' P,
                     '.TABLE_LANDING_PAGES_LANG.' PL
                WHERE P.landing_uri IN ('.join(', ', array_keys($uriFilter)).')'.($enabledOnly ? ' AND P.enabled = 1' : '').$this->db->langAnd(true, 'P', 'PL').'
                LIMIT 10',
                $uriFilter);
        if (empty($data)) {
            return false;
        }

        # Сортируем по приоритету
        if (sizeof($data) > 1) {
            $sort = array();
            foreach ($data as $k=>&$v) {
                $sort[$k] = $v['priority'] = array_search($v['landing_uri'], $requestVariations, true);
                if (!isset($v['is_relative'])) $v['is_relative'] = 1;
                if (!isset($v['joined'])) $v['joined'] = 0;
            } unset($v);
            array_multisort($sort, SORT_ASC, $data);
        }

        return reset($data);
    }

    /**
     * Сохранение страницы
     * @param integer $nLandingpageID ID страницы
     * @param array $aData данные страницы
     * @return boolean|integer
     */
    public function landingpageSave($nLandingpageID, array $aData)
    {
        if (empty($aData)) return false;

        if ($nLandingpageID > 0)
        {
            $aData['modified'] = $this->db->now(); # Дата изменения

            $res = $this->db->update(TABLE_LANDING_PAGES, array_diff_key($aData, $this->langLandingPages), array('id'=>$nLandingpageID));

            $this->db->langUpdate($nLandingpageID, $aData, $this->langLandingPages, TABLE_LANDING_PAGES_LANG);

            return ! empty($res);
        }
        else
        {
            $aData['created']  = $this->db->now(); # Дата создания
            $aData['modified'] = $this->db->now(); # Дата изменения
            $aData['user_id']  = User::id(); # Пользователь
            $aData['user_ip']  = Request::remoteAddress(true); # IP адрес

            $nLandingpageID = $this->db->insert(TABLE_LANDING_PAGES, array_diff_key($aData, $this->langLandingPages));
            if ($nLandingpageID > 0) {
                $this->db->langInsert($nLandingpageID, $aData, $this->langLandingPages, TABLE_LANDING_PAGES_LANG);
                //
            }
            return $nLandingpageID;
        }
    }

    /**
     * Переключатели страницы
     * @param integer $nLandingpageID ID страницы
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function landingpageToggle($nLandingpageID, $sField)
    {
        switch ($sField) {
            case 'enabled': { # Включен
                return $this->toggleInt(TABLE_LANDING_PAGES, $nLandingpageID, $sField, 'id');
            } break;
        }
    }

    /**
     * Удаление страницы
     * @param integer $nLandingpageID ID страницы
     * @return boolean
     */
    public function landingpageDelete($nLandingpageID)
    {
        if (empty($nLandingpageID)) return false;
        $res = $this->db->delete(TABLE_LANDING_PAGES, array('id'=>$nLandingpageID));
        if ( ! empty($res)) {
            $this->db->delete(TABLE_LANDING_PAGES_LANG, array('id'=>$nLandingpageID));
            return true;
        }
        return false;
    }

    # --------------------------------------------------------------------
    # Редиректы

    /**
     * Список редиректов (admin)
     * @param array $aFilter фильтр списка
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function redirectsListing(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'R');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(R.id) FROM '.TABLE_REDIRECTS.' R '.$aFilter['where'], $aFilter['bind']);
        }

        return $this->db->select('SELECT R.*
               FROM '.TABLE_REDIRECTS.' R
               '.$aFilter['where']
               .( ! empty($sqlOrder) ? ' ORDER BY '.$sqlOrder : '')
               .$sqlLimit, $aFilter['bind']);
    }

    /**
     * Получение данных о редиректах по подстроке запроса
     * @param array $requestVariations варианты URI текущего запроса
     * @param boolean $enabledOnly только включенные
     * @return array
     */
    public function redirectsByRequest($requestVariations, $enabledOnly = true)
    {
        if (empty($requestVariations)) {
            return array();
        }

        $fromFilter = array(); $i = 1;
        foreach ($requestVariations as $v) {
            $fromFilter[':from'.$i++] = $v;
        }

        $data = $this->db->select('SELECT R.id, R.from_uri, R.to_uri, R.status, R.add_extra, R.add_query, R.is_relative
                FROM '.TABLE_REDIRECTS.' R
                WHERE R.from_uri IN ('.join(', ', array_keys($fromFilter)).')'.($enabledOnly ? ' AND R.enabled = 1' : '').'
                LIMIT 10', $fromFilter);
        if (empty($data)) {
            return array();
        }

        # Сортируем по приоритету
        if (sizeof($data) > 1) {
            $sort = array();
            foreach ($data as $k=>&$v) {
                $sort[$k] = $v['priority'] = array_search($v['from_uri'], $requestVariations, true);
            } unset($v);
            array_multisort($sort, SORT_ASC, $data);
        }

        return reset($data);
    }

    /**
     * Получение данных о редиректе
     * @param integer $nRedirectID $nRedirectID редиректа
     * @return array
     */
    public function redirectData($nRedirectID)
    {
        return $this->db->one_array('SELECT R.*
                FROM '.TABLE_REDIRECTS.' R
                WHERE R.id = :id',
                array(':id'=>$nRedirectID));
    }

    /**
     * Сохранение редиректа
     * @param integer $nRedirectID ID редиректа
     * @param array $aData данные
     * @return boolean|integer
     */
    public function redirectSave($nRedirectID, array $aData)
    {
        if (empty($aData)) return false;

        if ($nRedirectID > 0)
        {
            $aData['modified'] = $this->db->now(); # Дата изменения

            $res = $this->db->update(TABLE_REDIRECTS, $aData, array('id'=>$nRedirectID));

            return ! empty($res);
        }
        else
        {
            $aData['created']  = $this->db->now(); # Дата создания
            $aData['modified'] = $this->db->now(); # Дата изменения
            $aData['user_id']  = User::id(); # Пользователь
            $aData['user_ip']  = Request::remoteAddress(true); # IP адрес

            $nRedirectID = $this->db->insert(TABLE_REDIRECTS, $aData);
            return $nRedirectID;
        }
    }

    /**
     * Переключатели редиректа
     * @param integer $nRedirectID ID редиректа
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function redirectToggle($nRedirectID, $sField)
    {
        switch ($sField) {
            case 'enabled': { # Включен
                return $this->toggleInt(TABLE_REDIRECTS, $nRedirectID, $sField, 'id');
            } break;
        }
    }

    /**
     * Удаление редиректа
     * @param integer $nRedirectID ID редиректа
     * @return boolean
     */
    public function redirectDelete($nRedirectID)
    {
        if (empty($nRedirectID)) return false;
        $res = $this->db->delete(TABLE_REDIRECTS, array('id'=>$nRedirectID));
        if ( ! empty($res)) {
            return true;
        }
        return false;
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_LANDING_PAGES => array('type' => 'table', 'fields' => $this->langLandingPages, 'title' => _t('seo', 'Посадочные страницы')),
        );
    }
}