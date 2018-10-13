<?php

/**
 * Используемые таблицы:
 * TABLE_REGIONS - таблица регионов (города, области/регионы, страны)
 * TABLE_REGIONS_METRO - таблица веток/станций метро
 * TABLE_REGIONS_DISTRICTS - таблица районов города
 * TABLE_REGIONS_GEOIP - таблица соответствия IP <> регионам (городам)
 */

class GeoModelBase extends Model
{
    public $langRegions = array(
        'title' => TYPE_NOTAGS, # название
    );

    public $langDistricts = array(
        'title' => TYPE_NOTAGS, # название
    );

    public $langMetro = array(
        'title' => TYPE_NOTAGS, # название
    );

    # ----------------------------------------------------------------------------------------------------
    # Регионы

    /**
     * Формируем список регионов (стран / областей / городов)
     * @param int|array $numLevel тип региона(нескольких регионов) (Geo::lvl_)
     * @param array $filter фильтр регионов
     * @param int $selectedID текущий активный регион (из выбираемого списка) или 0
     * @param int $limit лимит
     * @param string $orderBy порядок сортировки
     * @param array $opts доп. параметры
     * @return mixed
     */
    public function regionsList($numLevel, array $filter, $selectedID = 0, $limit = 0, $orderBy = '', array $opts = array())
    {
        \func::array_defaults($opts, array(
            'ttl' => 60, # кешировать запрос (сек)
        ));
        $bind = array();
        if (is_array($numLevel)) {
            $filter[':numlevel'] = 'R.numlevel IN (' . join(',', $numLevel) . ')';
        } else {
            $filter['numlevel'] = $numLevel;
        }
        if ( ! empty($selectedID) && $selectedID > 0 && isset($filter['enabled'])) {
            unset($filter['enabled']);
            $filter[':EnabledOrSel'] = '(R.enabled = 1 OR R.id = :sel)';
            $bind[':sel'] = $selectedID;
        }

        $filter = $this->prepareFilter($filter, 'R', $bind);

        return $this->db->select_key('SELECT R.*, R.title_' . LNG . ' as title,
                               P.keyword as pkey, P.title_' . LNG . ' as ptitle
                      FROM ' . TABLE_REGIONS . ' R
                         LEFT JOIN ' . TABLE_REGIONS . ' P ON R.pid = P.id
                      ' . $filter['where'] . '
                      ORDER BY ' . (!empty($orderBy) ? $orderBy : 'R.main DESC, R.num') . '
                      ' . (!empty($limit) ? $this->db->prepareLimit(0, $limit) : ''), 'id', $filter['bind'], $opts['ttl']
        );
    }

    /**
     * Формируем список регионов (стран / областей / городов) - adm
     * @param int $nCountryID ID страны
     * @param bool $bCount только подсчет кол-ва
     * @param array $aFilter фильтр регионов
     * @param array $aBind
     * @param string $sqlOrder
     * @param string $sqlLimit
     * @return mixed
     */
    public function regionsListing($nCountryID, $bCount, $aFilter = array(), $aBind = array(), $sqlOrder = '', $sqlLimit = '')
    {
        $aFilter['country'] = $nCountryID;
        $aFilter = $this->prepareFilter($aFilter, false, $aBind);
        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(*)
                                      FROM ' . TABLE_REGIONS . '
                                      ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        return $this->db->select('SELECT id, pid, title_' . LNG . ' as title, enabled, keyword, main, metro, num
                                  FROM ' . TABLE_REGIONS . '
                                  ' . $aFilter['where'] . '
                                  ' . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '') .
            $sqlLimit, $aFilter['bind']
        );
    }

    public function regionsExportList($nNumlevel, array $aFilter = array())
    {
        switch($nNumlevel)
        {
            case Geo::lvlCountry:
            case Geo::lvlRegion:
            case Geo::lvlCity:
                $aFilter['numlevel'] = $nNumlevel;
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->select('SELECT id, title_' . LNG . ' as title, metro
                    FROM ' . TABLE_REGIONS . '
                    ' . $aFilter['where'] . '
                    ORDER BY main DESC, num',
                    $aFilter['bind']
                );
                break;
            case Geo::lvlDistrict:
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->select_key('SELECT id, city_id, title_' . LNG . ' as title
                    FROM ' . TABLE_REGIONS_DISTRICTS . '
                    '.$aFilter['where'].'
                    ORDER BY 3', 'id', $aFilter['bind']
                );
                break;
            case Geo::lvlMetro:
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->select_key('SELECT id, pid, title_' . LNG . ' as title, branch
                    FROM ' . TABLE_REGIONS_METRO . '
                    '.$aFilter['where'].'
                    ORDER BY pid, num', 'id', $aFilter['bind']
                );
                break;
        }
    }

    /**
     * Получение данных о регионе
     * @param array $aFilter фильтр
     * @param bool $bEdit для редактирования
     * @return mixed
     */
    public function regionData(array $aFilter, $bEdit = false)
    {
        $aFilter = $this->prepareFilter($aFilter, 'R');
        $aData = $this->db->one_array('SELECT R.*, P.keyword as pkey
                FROM ' . TABLE_REGIONS . ' R
                    LEFT JOIN ' . TABLE_REGIONS . ' P ON R.pid = P.id
                ' . $aFilter['where'], $aFilter['bind']
        );
        if (!empty($aData)) {
            if ($bEdit) {
                $this->db->langFieldsSelect($aData, $this->langRegions);
                if (isset($aData['declension'])) {
                    $aData['declension'] = func::unserialize($aData['declension']);
                }
            } else {
                $aData['title'] = $aData['title_' . LNG];
                if (isset($aData['declension'])) {
                    $aData['declension'] = func::unserialize($aData['declension']);
                    $aData['declension'] = (isset($aData['declension'][LNG]) ? $aData['declension'][LNG] : '');
                }
                if (empty($aData['declension'])) {
                    $aData['declension'] = $aData['title'];
                }
            }
        }

        return $aData;
    }

    /**
     * Получаем ID региона(города) по IP адресу исходя из данных в таблице TABLE_REGIONS_GEOIP
     * @param string|bool $sIpAddr IP адрес или FALSE - текущий
     * @return mixed
     */
    public function regionDataByIp($sIpAddr = false)
    {
        switch($provider = config::sysAdmin('geo.ip.location.provider', 'ipgeobase.ru', TYPE_STR))
        {
            case 'geoipbase.ru':
            case 'ipgeobase.ru':
            {
                $nRegionID = 0;
                $nIpAddr = ($sIpAddr !== false ? sprintf("%u", ip2long($sIpAddr)) : Request::remoteAddress(true));
                if (!empty($nIpAddr)) {
                    $nRegionID = $this->db->one_data('SELECT G.city_id
                        FROM ' . TABLE_REGIONS_GEOIP . ' G
                        WHERE G.range_start <= :ip
                        ORDER BY G.range_start DESC
                        LIMIT 1', array(':ip' => $nIpAddr)
                    );
                    if (empty($nRegionID)) {
                        $nRegionID = 0;
                    }
                }
                return $this->regionData(array('geo_id' => $nRegionID));
            } break;
            case 'maxmind.com':
            {
                $nRegionID = $this->geoipMaxmind($sIpAddr);
                if (!$nRegionID) return array();
                return $this->regionData(array('id' => $nRegionID));
            } break;
            default: # custom
            {
                $regionData = bff::filter('geo.ip.location.region', false, $sIpAddr, $provider);
                if (is_array($regionData)) {
                    return $regionData;
                }
            } break;
        }
        return array();
    }

    /**
     * Получаем ID региона(города) по IP адресу используя maxmind
     * @param string|bool $ip IP адрес или FALSE - текущий
     * @return integer
     */
    public function geoipMaxmind($ip = false)
    {
        # http://dev.maxmind.com/geoip/legacy/install/city/
        # http://dev.maxmind.com/geoip/legacy/geolite/

        $fn = PATH_BASE . 'files' . DS . 'maxmind' . DS . 'GeoLiteCity.dat';
        if (!file_exists($fn)) {
            bff::log(_t('geo','проверьте наличие файла /files/maxmind/GeoLiteCity.dat для корректной работы метода geoipMaxmind'));
            return 0;
        }
        if (!function_exists('geoip_open')) {
            require_once modification(PATH_CORE . 'external' . DS . 'maxmind' . DS . 'geoipcity.inc');
            require_once modification(PATH_CORE . 'external' . DS . 'maxmind' . DS . 'geoipregionvars.php');
        }
        if (!function_exists('geoip_open')) {
            return 0;
        }

        $gi = geoip_open($fn, GEOIP_STANDARD);
        if (!$ip) {
            $ip = Request::remoteAddress();
        }

        # 0) Определим регион по ip, используя api maxmind
        $record = geoip_record_by_addr($gi, $ip);
        geoip_close($gi);

        if (empty($record)) return 0;
        if (empty($record->country_code)) return 0; # maxmind не вернул страну

        # 1) найдем страну в базе
        $country = $this->regionData(array('country_code' => mb_strtolower($record->country_code), 'numlevel' => Geo::lvlCountry));
        if (empty($country['id'])) {
            return 0;
        }
        $country = $country['id'];

        # 2) определим область
        global $GEOIP_REGION_NAME;
        if (empty($record->region) || empty($GEOIP_REGION_NAME[$record->country_code][$record->region])) {
            return $country;    # maxmind не вернул область
        }
        $regName = $GEOIP_REGION_NAME[$record->country_code][$record->region];
        # найдем область в базе
        $region = $this->regionData(array('title_en' => $regName, 'country' => $country, 'numlevel' => Geo::lvlRegion));
        if (empty($region)) {
            $region = $this->regionData(array(
                array('R.title_alt LIKE :title', ':title' => '%'.$regName.'%'),
                'numlevel' => Geo::lvlRegion,
                'country' => $country,
            ));
            if (empty($region)) {
                return $country;
            }
        }
        $region = $region['id'];

        # 3) определим город
        if (empty($record->city)) {
            return $region; # maxmind не вернул город
        }
        # найдем город в базе
        $city = $this->regionData(array('title_en' => $record->city, 'country' => $country, 'numlevel' => Geo::lvlCity, 'pid' => $region));
        if (empty($city)) {
            $city = $this->regionData(array(
                array('R.title_alt LIKE :title', ':title' => '%'.$record->city.'%'),
                'country' => $country,
                'numlevel' => Geo::lvlCity,
                'pid' => $region
            ));
            if (empty($city)) {
                return $region;
            }
        }
        return $city['id'];
    }

    public function regionNumlevel($nRegionID)
    {
        return (int)$this->db->one_data('SELECT numlevel FROM ' . TABLE_REGIONS . '
                WHERE id = :id', array(':id' => $nRegionID)
        );
    }

    public function regionSave($nRegionID, array $aData = array())
    {
        if (empty($aData)) {
            return false;
        }

        $this->db->langFieldsModify($aData, $this->langRegions, $aData);
        if (isset($aData['declension'])) {
            $aData['declension'] = serialize($aData['declension']);
        }

        if ($nRegionID > 0) {
            return $this->db->update(TABLE_REGIONS, $aData, array('id' => $nRegionID));
        } else {
            # в случае если регионы не используются, получаем порядковый номер исходя из городов входящих в страну
            if ($aData['numlevel'] == Geo::lvlCity && !$aData['pid'] && !Geo::manageRegions(Geo::lvlRegion)) {
                $nNum = $this->db->one_data('SELECT MAX(num) FROM ' . TABLE_REGIONS . ' WHERE country = :country', array(':country' => $aData['country']));
            } else {
                $nNum = $this->db->one_data('SELECT MAX(num) FROM ' . TABLE_REGIONS . ' WHERE pid = :pid', array(':pid' => $aData['pid']));
            }
            $aData['num'] = intval($nNum) + 1;

            if (!empty($aData['main'])) {
                $nMain = $this->db->one_data('SELECT MAX(main) FROM ' . TABLE_REGIONS . '
                                    WHERE main > 0 AND numlevel = :nl', array(':nl' => $aData['numlevel'])
                );
                $aData['main'] = intval($nMain) + 1;
            }

            return $this->db->insert(TABLE_REGIONS, $aData, 'id');
        }
    }

    /**
     * Обновление данных регионов по фильтру
     * @param array $update обновляемые данные
     * @param array $filter параметры фильтра
     * @param array $opts:
     *   array 'bind' доп. параметры подставляемые в запрос
     *   string|array 'orderBy' условие запроса ORDER BY
     *   integer|string|array 'limit' лимит выборки, например: 15
     *   array 'cryptKeys' шифруемые столбцы
     * @return integer кол-во обновленных регионов
     */
    public function regionsUpdateByFilter(array $filter, array $update, array $opts = array())
    {
        # default options:
        \func::array_defaults($opts, array(
            'context'   => '?',
            'tag'       => '',
            'bind'      => array(),
            'orderBy'   => false,
            'limit'     => false,
            'cryptKeys' => array(),
        ));

        # tag
        if (!empty($opts['tag'])) {
            $this->db->tag($opts['tag']);
        }

        return $this->db->update(TABLE_REGIONS, $update, $filter, $opts['bind'], $opts['cryptKeys'], $opts);
    }

    public function regionsRotate(array $aCond, $sOrderField = 'num')
    {
        if (!empty($aCond)) {
            $aCond = ' AND ' . join(' AND ', $aCond);
        }

        return $this->db->rotateTablednd(TABLE_REGIONS, $aCond, 'id', $sOrderField);
    }

    public function regionToggle($nRegionID, $sField = 'enabled')
    {
        switch ($sField) {
            case 'enabled':
            {
                return $this->toggleInt(TABLE_REGIONS, $nRegionID);
            }
            break;
            case 'main':
            {
                $aData = $this->db->one_array('SELECT pid, main, numlevel FROM ' . TABLE_REGIONS . ' WHERE id = :id LIMIT 1', array(':id' => $nRegionID));
                if (empty($aData)) {
                    return false;
                }
                $aUpdate = array();
                if ($aData['main']) {
                    $aUpdate['main'] = 0;
                } else {
                    $nMain = $this->db->one_data('SELECT MAX(main) FROM ' . TABLE_REGIONS . '
                                    WHERE main > 0 AND numlevel = :nl', array(':nl' => $aData['numlevel'])
                    );
                    $aUpdate['main'] = intval($nMain) + 1;
                }

                return $this->db->update(TABLE_REGIONS, $aUpdate, array('id' => $nRegionID));
            }
            break;
        }
    }

    public function regionDelete($nRegionID)
    {
        return $this->db->delete(TABLE_REGIONS, array('(id = :id OR pid = :id)'), array(':id' => $nRegionID));
    }

    /**
     * Проверка на уникальность URL-keyword'a региона
     * @param string $sKeyword URL-keyword
     * @param integer $nRegionID ID региона
     * @param integer $nRegionLevel тип региона (Geo::lvl_)
     * @return string
     */
    public function regionKeywordIsUnique($sKeyword, $nRegionID, $nRegionLevel)
    {
        if (empty($sKeyword)) {
            return false;
        }
        $aData = $this->regionData(array(
                'keyword' => $sKeyword,
                array('R.id != :id', ':id' => $nRegionID)
            )
        );

        return empty($aData);
    }

    public function regionParents($nRegionID)
    {
        $aResult = array(
            'db'   => array('reg1_country' => 0, 'reg2_region' => 0, 'reg3_city' => 0),
            'keys' => array('region' => '', 'city' => '')
        );

        do {
            if (!$nRegionID) {
                break;
            }

            $aData = $this->db->one_array('SELECT R1.id, R1.pid, R1.keyword, R1.country, R1.numlevel as lvl,
                                R2.keyword as parent_keyword
                            FROM ' . TABLE_REGIONS . ' R1
                                LEFT JOIN ' . TABLE_REGIONS . ' R2 ON R1.pid = R2.id
                            WHERE R1.id = :id', array(':id' => $nRegionID)
            );
            if (empty($aData)) {
                break;
            }

            switch ($aData['lvl']) {
                case Geo::lvlCountry:
                {
                    $aResult['db']['reg1_country'] = $nRegionID;
                }
                break;
                case Geo::lvlRegion:
                {
                    $aResult['db']['reg1_country'] = $aData['pid'];
                    $aResult['db']['reg2_region'] = $nRegionID;
                    $aResult['keys']['region'] = $aData['keyword'];
                }
                break;
                case Geo::lvlCity:
                {
                    $aResult['db']['reg1_country'] = $aData['country'];
                    $aResult['db']['reg2_region'] = $aData['pid'];
                    $aResult['keys']['region'] = $aData['parent_keyword'];
                    $aResult['db']['reg3_city'] = $nRegionID;
                    $aResult['keys']['city'] = $aData['keyword'];
                }
                break;
            }

        } while (false);

        return $aResult;
    }

    public function regionsCountriesAndRegions($bEnabled = true)
    {
        $aData = $this->db->select('SELECT R.id, R.pid, R.title_' . LNG . ' as title
                               FROM ' . TABLE_REGIONS . ' R, ' . TABLE_REGIONS . ' R2
                               WHERE R.numlevel IN(' . Geo::lvlCountry . ',' . Geo::lvlRegion . ')
                                 ' . ($bEnabled ? ' AND R.enabled = 1 ' : '') . '
                                 AND (R.pid = 0 OR (R.pid = R2.id' . ($bEnabled ? ' AND R2.enabled = 1' : '') . '))
                               ORDER BY R.main DESC, R.num'
        );

        return $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
    }

    # ---------------------------------------------------------------------------------
    # Районы

    /**
     * Формируем список районов города - frontend
     * @param int $nCityID ID города
     * @param array $aFilter фильтр районов
     * @param array $aBind
     * @param string $sLang ключ локализации
     * @return mixed
     */
    public function districtsList($nCityID, array $aFilter = array(), array $aBind = array(), $sLang = LNG)
    {
        if ($nCityID && ! isset($aFilter['city_id'])) {
            $aFilter['city_id'] = $nCityID;
        }
        $aFilter = $this->prepareFilter($aFilter, false, $aBind);

        return $this->db->select_key('SELECT id, title_' . $sLang . ' as t, ybounds, ypoly, city_id
                                  FROM ' . TABLE_REGIONS_DISTRICTS . '
                                  ' . $aFilter['where'] . '
                                  ORDER BY 2', 'id', $aFilter['bind']
        );
    }

    public function districtsListing($nCityID)
    {
        return $this->db->select('SELECT id, city_id, title_' . LNG . ' as title
                           FROM ' . TABLE_REGIONS_DISTRICTS . '
                           WHERE city_id = :city
                           ORDER BY title_' . LNG, array(':city' => $nCityID)
        );
    }

    public function districtData($nDistrictID, $bEdit = false)
    {
        $aData = $this->db->one_array('SELECT *
                FROM ' . TABLE_REGIONS_DISTRICTS . '
                WHERE id = :id', array(':id' => $nDistrictID)
        );
        if ($bEdit) {
            $this->db->langFieldsSelect($aData, $this->langDistricts);
        }

        return $aData;
    }

    public function districtSave($nDistrictID, $nCityID, array $aData = array())
    {
        if (empty($aData)) {
            return false;
        }

        $this->db->langFieldsModify($aData, $this->langDistricts, $aData);

        $aData['city_id'] = $nCityID;
        if ($nDistrictID > 0) {
            return $this->db->update(TABLE_REGIONS_DISTRICTS, $aData, array('id' => $nDistrictID));
        } else {
            return $this->db->insert(TABLE_REGIONS_DISTRICTS, $aData, 'id');
        }
    }

    public function districtDelete($nDistrictID)
    {
        return $this->db->delete(TABLE_REGIONS_DISTRICTS, $nDistrictID);
    }

    # ---------------------------------------------------------------------------------
    # Метро

    public function metroListing($nCityID) // adm
    {
        $aData = $this->db->select('SELECT id, pid, title_' . LNG . ' as title, color, branch
                                FROM ' . TABLE_REGIONS_METRO . '
                                WHERE city_id = :city
                                ORDER BY pid, num', array(':city' => $nCityID)
        );

        if (!empty($aData)) {
            $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
        }

        return $aData;
    }

    public function metroList($nCityID, $bGetBranches = true) // frontend
    {
        if ($bGetBranches) {
            $aData = $this->db->select('SELECT id, pid, title_' . LNG . ' as t, color, branch as b
                                    FROM ' . TABLE_REGIONS_METRO . '
                                    WHERE city_id = :city
                                    ORDER BY pid, num', array(':city' => $nCityID)
            );

            if (!empty($aData)) {
                $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'st');
            }
        } else {
            $aData = $this->db->select('SELECT id, title_' . LNG . ' as t, color
                                    FROM ' . TABLE_REGIONS_METRO . '
                                    WHERE city_id = :city AND branch = 0
                                    ORDER BY num', array(':city' => $nCityID)
            );
            $aData = func::array_transparent($aData, 'id', true);
        }

        return $aData;
    }

    public function metroSave($nMetroID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        $this->db->langFieldsModify($aData, $this->langMetro, $aData);

        if ($nMetroID > 0) {
            return $this->db->update(TABLE_REGIONS_METRO, $aData, array('id' => $nMetroID));
        } else {
            $nNum = (int)$this->db->one_data('SELECT MAX(num)
                            FROM ' . TABLE_REGIONS_METRO . ' WHERE pid = :pid', array(':pid' => $aData['pid'])
            );
            $aData['num'] = $nNum + 1;

            return $this->db->insert(TABLE_REGIONS_METRO, $aData);
        }
    }

    public function metroData($nMetroID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT * FROM ' . TABLE_REGIONS_METRO . ' WHERE id = :id', array(':id' => $nMetroID));
            if (!empty($aData)) {
                $this->db->langFieldsSelect($aData, $this->langMetro);
            }
        } else {
            if (Geo::$useMetroBranches) {
                $aData = $this->db->one_array('SELECT S.id, S.pid, S.city_id, S.title_' . LNG . ' as title, B.title_' . LNG . ' as branch_title, B.color as branch_color
                    FROM ' . TABLE_REGIONS_METRO . ' S
                         LEFT JOIN ' . TABLE_REGIONS_METRO . ' B ON B.id = S.pid
                    WHERE S.id = :id', array(':id' => $nMetroID)
                );
            } else {
                $aData = $this->db->one_array('SELECT S.id, S.pid, S.city_id, S.title_' . LNG . ' as title
                    FROM ' . TABLE_REGIONS_METRO . ' S
                    WHERE S.id = :id', array(':id' => $nMetroID)
                );
            }
        }

        return $aData;
    }

    public function metroBranchesOptions($nCityID, $nSelectedID = 0, $mEmpty = false)
    {
        $aData = $this->db->select('SELECT id, title_' . LNG . ' as title
                    FROM ' . TABLE_REGIONS_METRO . '
                    WHERE city_id = :city
                      AND pid = 0
                      AND branch = 1
                    ORDER BY num
                    ', array(':city' => $nCityID)
        );

        return HTML::selectOptions($aData, $nSelectedID, $mEmpty, 'id', 'title');
    }

    /**
     * Список городов с метро
     * @param integer|bool $nCountryID ID страны или false (страна по-умолчанию)
     * @return mixed
     */
    public function metroCities($nCountryID = false)
    {
        if ($nCountryID === false) {
            $nCountryID = Geo::defaultCountry();
        }

        return $this->db->select('SELECT id, title_' . LNG . ' as title
                    FROM ' . TABLE_REGIONS . '
                    WHERE numlevel = ' . Geo::lvlCity . '
                      AND metro = 1
                      AND country = :country
                    ORDER BY main, num', array(':country' => $nCountryID)
        );
    }

    /**
     * Удаление станции метро
     * @param int $nMetroID ID станции/ветки метро
     * @return bool
     */
    public function metroDelete($nMetroID)
    {
        $res = $this->db->delete(TABLE_REGIONS_METRO, $nMetroID);

        return !empty($res);
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_REGIONS           => array('type' => 'fields', 'fields' => $this->langRegions, 'fields-serialized' => array('declension'), 'title' => _t('geo', 'Страны / области / города')),
            TABLE_REGIONS_METRO     => array('type' => 'fields', 'fields' => $this->langMetro, 'title' => _t('geo', 'Метро')),
            TABLE_REGIONS_DISTRICTS => array('type' => 'fields', 'fields' => $this->langDistricts, 'title' => _t('geo', 'Районы')),
        );
    }
}