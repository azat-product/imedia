<?php

class Geo_ extends GeoBase
{
    public function ajax()
    {
        $aResponse = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            /**
             * Список выбора фильтрации по региону
             * @param int $nCountryID ID страны
             */
            case 'filter-desktop-step1':
            {
                $nCountryID = $this->input->post('region_id', TYPE_UINT);
                $aResponse['html'] = $this->filterData('desktop-country-step1', $nCountryID);
            }
            break;
            /**
             * Список выбора фильтрации по городу
             * @param int $nRegionID ID области(региона)
             */
            case 'filter-desktop-step2':
            {
                $nRegionID = $this->input->post('region_id', TYPE_UINT);
                $aResponse = $this->filterData('desktop-country-step2', $nRegionID);
            }
            break;
            /**
             * Список выбора фильтрации по городу без выбора региона
             * @param int $nRegionID ID страны
             */
            case 'filter-desktop-city-noregions':
            {
                $nRegionID = $this->input->post('region_id', TYPE_UINT);
                $aResponse = $this->filterData('desktop-country-city-noregions', $nRegionID);
            }
            break;
            /**
             * Подтверждаем текущий регион пользователя
             * @param int $nRegionID ID региона
             */
            case 'filter-confirm-region':
            {
                $nRegionID = $this->input->post('region_id', TYPE_UINT);
                $aRegionData = static::regionData($nRegionID);
                if (!empty($aRegionData)) {
                    $aResponse['success'] = static::filterUser($nRegionID, 'ip-location-confirm-finish');
                    $opts = array();
                    switch ($aRegionData['numlevel']) {
                        case static::lvlCity: $opts['city'] = $aRegionData['keyword']; break;
                        case static::lvlRegion: $opts['region'] = $aRegionData['keyword']; break;
                        case static::lvlCountry: $opts['country'] = $aRegionData['keyword']; break;
                    }
                    $aResponse['redirect'] = Geo::url($opts);
                }
            }
            break;
            /**
             * Autocomplete для городов/областей
             * - выбор фильтра региона, мобильная версия
             */
            case 'filter-phone-suggest':
            {
                $_POST['reg'] = 1; # города + области
                if (static::coveringType(static::COVERING_COUNTRIES)) {
                    $_POST['country'] = 1; # + страны
                }
                $aData = $this->regionSuggest(true);
                foreach ($aData as &$v) {
                    if ($v['numlevel'] == self::lvlCity) {
                        $v['link'] = static::url(array('region' => $v['pkey'], 'city' => $v['keyword']));
                    } else if ($v['numlevel'] == self::lvlRegion) {
                        $v['link'] = static::url(array('region' => $v['keyword'], 'city' => false));
                    } else {
                        $v['link'] = static::url(array('country' => $v['keyword']));
                    }
                }
                unset($v);
                $aData = array('list' => $aData, 'highlight' => true, 'q' => $this->input->post('q', TYPE_NOTAGS));
                $aResponse['html'] = $this->viewPHP($aData, 'filter.phone.suggest');
            }
            break;
            /**
             * Autocomplete для городов/областей
             */
            case 'region-suggest':
            {
                $this->regionSuggest(false);
            }
            break;
            /**
             * Список районов города
             * @param int $nCityID ID города
             * @param bool $bOptions true - в формате select::options, false - array
             */
            case 'districts-list':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $bOptions = $this->input->postget('opts', TYPE_BOOL);
                if (!$nCityID) {
                    $this->errors->impossible();
                    break;
                }
                if ($bOptions) {
                    $aResponse['districts'] = static::districtOptions($nCityID, 0, _t('filter', 'Не указан'));
                } else {
                    $aResponse['districts'] = static::districtList($nCityID);
                }
            }
            break;
            /**
             * Список станций метро города для формы ОБ
             * @param int $nCityID ID города
             */
            case 'form-metro':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $aData = static::cityMetro($nCityID, 0, false);
                $aResponse['data'] = $aData['data'];
                $aResponse['branches'] = $this->viewPHP($aData, 'form.metro.step1');
                $aResponse['stations'] = array();
                foreach ($aData['data'] as $k => $v) {
                    $v['city_id'] = $nCityID;
                    $aResponse['stations'][$k] = $this->viewPHP($v, 'form.metro.step2');
                }
            }
            break;
            case 'country-presuggest':
            {
                $nCountryID = $this->input->postget('country', TYPE_UINT);
                $mResult = false;
                if ($nCountryID) {
                    $aData = static::regionPreSuggest($nCountryID, true);
                    $mResult = array();
                    foreach ($aData as $v) {
                        $declension = func::unserialize($v['declension']);
                        $mResult[] = array($v['id'], $v['title'], $v['metro'], $v['pid'], isset($declension[LNG]) ? $declension[LNG] : '');
                    }
                }
                $this->ajaxResponse($mResult);
            }
            break;
            default:
            {
                $this->errors->impossible();
            }
        }
        $this->ajaxResponseForm($aResponse);
    }

    public function filterForm($deviceID = false)
    {
        if (empty($deviceID)) {
            $deviceID = bff::device();
        }
        $aData['device'] = $deviceID;

        $aData['coveringType'] = static::coveringType();
        $aData['country'] = false;
        $aData['regionID'] = 0;
        $aData['regionData'] = static::filter(); # user
        if( ! empty($aData['regionData']['id']) ) {
            $aData['regionID'] = $aData['regionData']['id'];
        }
        $aData['regionLevel'] = $aData['regionID'] ? $aData['regionData']['numlevel'] : 0;
        switch ($aData['regionLevel']) {
            case static::lvlCountry;
                $aData['country'] = $aData['regionData'];
                break;
            case static::lvlRegion:
            case static::lvlCity:
                $aData['country'] = static::regionData($aData['regionData']['country']);
                break;
        }
        $aData['noregions'] = ! empty($aData['country']['filter_noregions']);

        return $this->viewPHP($aData, 'filter.form');
    }

    public function filterData($sDataType, $nParentID = 0)
    {
        switch ($sDataType) {
            case 'desktop-countries-step0': # выбор страны (COVERING_COUNTRIES)
            {
                $aData = array();
                $aData['countries'] = static::countriesList();
                foreach ($aData['countries'] as &$v) {
                    $v['link'] = static::url(array('country' => $v['keyword']));
                } unset($v);
                return $this->viewPHP($aData, 'filter.desktop.countries');
            }
            break;
            case 'desktop-country-step1': # выбор области/региона (COVERING_COUNTRY)
            {
                $aData = static::regionList($nParentID ? $nParentID : static::coveringRegion());
                # Данные о количестве объявлений в городах
                $aItemsCounters = BBS::model()->itemsCountByFilter(array(
                    'cat_id' => 0,
                    'region_id' => array_keys($aData),
                    'delivery' => 0,
                ), array('region_id', 'items'), false, 60);
                $aItemsCounters = func::array_transparent($aItemsCounters, 'region_id', true);
                $aResult = array();
                foreach ($aData as $v) {
                    $letter = mb_substr($v['title'], 0, 1);
                    $v['link'] = static::url(array('region' => $v['keyword']));
                    $v['items'] = ! empty($aItemsCounters[ $v['id'] ]['items']) ? $aItemsCounters[ $v['id'] ]['items'] : 0;
                    $aResult[$letter][] = $v;
                }
                $nCols = 3;
                $nInCol = ceil(sizeof($aData) / $nCols);
                $aData = array('regions' => $aResult, 'step' => 1, 'cols' => $nCols, 'in_col' => $nInCol);
                return $this->viewPHP($aData, 'filter.desktop.country');
            }
            break;
            case 'desktop-country-step2': # выбор города (COVERING_COUNTRY)
            {
                $aResponse = array('html' => '', 'region' => array());
                do {
                    $nSelectedID = 0;
                    $aRegion = static::regionData($nParentID);
                    if (empty($aRegion) || !in_array($aRegion['numlevel'], array(
                                self::lvlRegion,
                                self::lvlCity
                            )
                        )
                    ) {
                        break;
                    }
                    if ($aRegion['numlevel'] == self::lvlCity) {
                        $nParentID = $aRegion['pid'];
                        $nSelectedID = $aRegion['id'];
                        $aRegion = static::regionData($nParentID);
                        if (empty($aRegion) || $aRegion['numlevel'] != self::lvlRegion) {
                            break;
                        }
                    }

                    $aRegion['link'] = static::url(array('region' => $aRegion['keyword']));

                    $aData = static::cityList($nParentID);
                    $aResult = array();
                    if (!empty($aData)) {
                        # Данные о количестве объявлений в городах
                        $aItemsCounters = BBS::model()->itemsCountByFilter(array(
                            'cat_id' => 0,
                            'region_id' => array_keys($aData),
                            'delivery' => 0,
                        ), array('region_id', 'items'), false, 60);
                        $aItemsCounters = func::array_transparent($aItemsCounters, 'region_id', true);
                        foreach ($aData as $v) {
                            $letter = mb_substr($v['title'], 0, 1);
                            $v['link'] = static::url(array('region' => $aRegion['keyword'], 'city' => $v['keyword']));
                            $v['items'] = ! empty($aItemsCounters[ $v['id'] ]['items']) ? $aItemsCounters[ $v['id'] ]['items'] : 0;
                            $v['active'] = ($nSelectedID == $v['id']);
                            $aResult[$letter][] = $v;
                        }
                    }

                    $nCols = 4;
                    if (sizeof($aData) <= 20 && sizeof($aData) > 8) $nCols = 3;
                    $aData = array(
                        'cities' => $aResult,
                        'cols'   => $nCols,
                        'in_col' => ceil(sizeof($aData) / $nCols),
                        'region' => $aRegion,
                        'step'   => 2
                    );
                    $aResponse['html'] = $this->viewPHP($aData, 'filter.desktop.country');
                } while (false);
                if (Request::isAJAX()) {
                    return $aResponse;
                } else {
                    echo $aResponse['html'];
                }
            }
            break;
            case 'desktop-country-city-noregions': # выбор города без выбора области (только основные города)
            {
                $aResponse = array('html' => '', 'region' => array());
                do {
                    $nSelectedID = 0;
                    $aRegion = static::regionData($nParentID);
                    if ($aRegion['numlevel'] != static::lvlCountry) {
                        $nSelectedID = $aRegion['id'];
                        $aRegion = static::regionData( ! empty($aRegion['country']) ? $aRegion['country'] : static::defaultCountry());
                    }
                    $aRegion['link'] = static::url(array('country' => $aRegion['keyword']));

                    $aData = $this->model->regionsList(static::lvlCity, ['enabled'=>1, 'main'=>['>', 0], 'country'=>$aRegion['id']], 0, 0, 'title ASC', ['ttl'=>600 /* 10 мин*/]);
                    $aResult = array();
                    if ( ! empty($aData)) {
                        # Данные о количестве объявлений в городах
                        $aItemsCounters = BBS::model()->itemsCountByFilter(array(
                            'cat_id' => 0,
                            'region_id' => array_keys($aData),
                            'delivery' => 0,
                        ), array('region_id', 'items'), false, 60);
                        $aItemsCounters = func::array_transparent($aItemsCounters, 'region_id', true);
                        foreach ($aData as $v) {
                            $letter = mb_substr($v['title'], 0, 1);
                            $v['link'] = static::url(array('country' => $aRegion['keyword'], 'city' => $v['keyword']));
                            $v['items'] = ! empty($aItemsCounters[ $v['id'] ]['items']) ? $aItemsCounters[ $v['id'] ]['items'] : 0;
                            $v['active'] = ($nSelectedID == $v['id']);
                            $v['main'] = 0;
                            $aResult[$letter][] = $v;
                        }
                    }

                    $nCols = 4;
                    if (sizeof($aData) <= 20 && sizeof($aData) > 8) $nCols = 3;
                    $aData = array(
                        'cities' => $aResult,
                        'cols'   => $nCols,
                        'in_col' => ceil(sizeof($aData) / $nCols),
                        'region' => $aRegion,
                        'step'   => 2,
                        'noregions' => 1,
                    );
                    $aResponse['html'] = $this->viewPHP($aData, 'filter.desktop.country');
                } while (false);
                if (Request::isAJAX()) {
                    return $aResponse;
                } else {
                    echo $aResponse['html'];
                }
            }
            break;
            case 'desktop-region': # выбор города (COVERING_REGION)
            {
                $aRegion = static::regionData(static::coveringRegion());
                if (empty($aRegion)) {
                    $aRegion = array('id' => 0, 'keyword' => '');
                }
                $aRegion['link'] = static::url(array('region' => $aRegion['keyword']));

                $aData = static::cityList($aRegion['id']);
                $nSelectedID = static::filter('id');
                $aResult = array();
                if (!empty($aData)) {
                    foreach ($aData as $v) {
                        $letter = mb_substr($v['title'], 0, 1);
                        $v['link'] = static::url(array('region' => $aRegion['keyword'], 'city' => $v['keyword']));
                        $v['active'] = ($nSelectedID == $v['id']);
                        $aResult[$letter][] = $v;
                    }
                }

                $nCols = 4;
                $aData = array(
                    'cities' => $aResult,
                    'cols'   => $nCols,
                    'in_col' => ceil(sizeof($aData) / $nCols),
                    'region' => $aRegion
                );

                return $this->viewPHP($aData, 'filter.desktop.region');
            }
            break;
            case 'desktop-cities': # выбор города (COVERING_CITIES)
            {
                $aData = static::cityListByID(static::coveringRegion());
                $nTotal = sizeof($aData);
                $nSelectedID = static::filter('id');
                $aResult = array();
                if (!empty($aData)) {
                    foreach ($aData as &$v) {
                        $letter = mb_substr($v['title'], 0, 1);
                        $v['link'] = static::url(array('city' => $v['keyword']));
                        $v['active'] = ($nSelectedID == $v['id']);
                        $aResult[$letter][] = $v;
                    }
                    unset($v);
                }

                $nCols = 4;
                $sColsClass = 'span3';
                foreach (array(
                             10 => array(1, 'span12'),
                             15 => array(2, 'span6'),
                             22 => array(3, 'span4')
                         ) as $n => $cols) {
                    if ($nTotal <= $n) {
                        $nCols = $cols[0];
                        $sColsClass = $cols[1];
                        break;
                    }
                }

                $aData = array(
                    'cities' => $aData,
                    'cities_letters' => $aResult,
                    'total' => $nTotal,
                    'cols' => $nCols,
                    'cols_class' => $sColsClass,
                    'in_col' => ceil(sizeof($aData) / $nCols),
                    'link_all' => static::url(array('region' => '')),
                );

                return $this->viewPHP($aData, 'filter.desktop.cities');
            }
            break;
            case 'phone-presuggest': # выбор города / странв
            {
                if (!static::coveringType(static::COVERING_COUNTRIES)) {
                    $nParentID = static::defaultCountry();
                }
                if ($nParentID) {
                    $aData = static::regionPreSuggest($nParentID, true);
                    foreach ($aData as &$v) {
                        if ($v['numlevel'] == self::lvlCity) {
                            $v['link'] = static::url(array('region' => $v['pkey'], 'city' => $v['keyword']));
                        } else {
                            $v['link'] = static::url(array('region' => $v['keyword'], 'city' => false));
                        }
                    }
                    unset($v);
                } else {
                    $aData = static::countriesList();
                    foreach ($aData as &$v) {
                        $v['link'] = static::url(array('country' => $v['keyword']));
                    }
                    unset($v);
                }
                $aData = array('list' => $aData, 'highlight' => false);

                return $this->viewPHP($aData, 'filter.phone.suggest');
            }
            break;
        }
    }

    public static function regionFilterByIp($ipAddr = false)
    {
        $data = static::model()->regionDataByIp($ipAddr);
        if (empty($data)) {
            $data = array('id' => 0);
        } else {
            if (!static::coveringRegionCorrect($data)) {
                $data = array('id' => 0);
            }
        }
        return $data;
    }

    /**
     * Получаем шаблон карты по ID региона
     * @param int $regionID ID региона
     * @param array $regions данные о регионах со счетчиками кол-ва объявлениий
     * @return string HTML
     */
    public function regionMap($regionID, array $regions = array())
    {
        if ( ! file_exists(View::templatePath('map.' . $regionID, $this->module_dir_tpl))) {
            return '';
        }

        if (empty($regions)) {
            $regions = $this->model->regionsList(array(static::lvlRegion, static::lvlCity), array(':reg' => '(R.country = ' . $regionID . ' AND R.main > 0) OR R.pid = ' . $regionID));
            $items = BBS::model()->itemsCountByFilter(array(
                'cat_id' => 0,
                'region_id' => array_keys($regions),
                'delivery' => 0,
            ), array('region_id', 'items'), false, 60);
            $items = func::array_transparent($items, 'region_id', true);
            if (!empty($regions)) {
                foreach ($regions as &$v) {
                    $v['items'] = ! empty($items[ $v['id'] ]['items']) ? $items[ $v['id'] ]['items'] : 0;
                    $v['l'] = BBS::url('items.search', array('region' => $v['keyword']));
                } unset($v);
            }
        }

        $aData = array('regions'=>array());
        $fields = array('id', 'title', 'items', 'l', 'numlevel');
        foreach ($regions as $v) {
            foreach ($fields as $f) {
                $aData['regions'][$v['id']][$f] = $v[$f];
            }
        }
        $aData['regions'] = array_values($aData['regions']);
        return $this->viewPHP($aData, 'map.' . $regionID);
    }

}