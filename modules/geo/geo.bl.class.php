<?php

abstract class GeoBase_ extends GeoModule
{
    /** @var GeoModel */
    public $model = null;
    /** @var array доступные для редактирования (задействованные) geo-сущности */
    public static $useRegions = array(
        self::lvlCountry,
        self::lvlRegion,
        self::lvlCity,
        self::lvlDistrict,
        self::lvlMetro
    );

    /**
     * ID страны по-умолчанию
     */
    public static $defaultCountry = 1;

    /**
     * Тип формирования URL с учетом региона / города
     */
    const URL_SUBDOMAIN = 1; # поддомены для областей(регионов), поддомены для городов - city.region.example.com/
    const URL_SUBDIR = 2; # поддиректории для областей(регионов) или городов - example.com/city/
    const URL_NONE = 3; # регион и город не фигурируют в URL - example.com

    /**
     * Тип покрытия регионов
     */
    const COVERING_COUNTRIES = 5; # несколько стран
    const COVERING_COUNTRY = 1; # все области и города одной страны
    const COVERING_REGION = 2; # все города одной области
    const COVERING_CITIES = 3; # несколько городов (одной или нескольких областей)
    const COVERING_CITY = 4; # один город

    public function init()
    {
        parent::init();

        $this->regionsFormExtraFields(self::lvlCountry, array(
            array('field'=>'filter_noregions', 'type'=>'checkbox', 'title'=>'Фильтр региона: пропускать шаг выбора области'),
        ));
    }

    /**
     * Инициализируем фильтр по региону
     * @param mixed $key ключ требуемых данных о текущих настройках фильтра по региону или FALSE (все данные)
     * @return mixed
     */
    public static function filter($key = false)
    {
        static $inited = false, $current = array(), $country = array(), $region = array(), $city = array();
        if ( ! $inited) {
            $inited = true;

            $url = static::filterUrl();
            $city = $url['city'];
            $region = $url['region'];
            $country = $url['country'];
            # Меняем фильтр региона пользователя:
            if (
                # Исходя из текущего URL
                static::filterUrlOnly()
                ||
                # При поиске объявлений / магазинов
                bff::router()->isCurrent(array(
                    'bbs-items.search', 'bbs-items.search-geo',
                    'shops-search', 'shops-search-geo',
                ))
                ||
                # На главной региона
                ($url['id'] && bff::isIndex())
            ) {
                static::filterUser($url['id'], 'filter-init');
            }
            $user = static::regionData(static::filterUser());

            if ($user && ! static::filterUrlOnly()) {
                $city = array(); $region = array(); $country = array();
                if (static::coveringRegionCorrect($user)) {
                    if (static::isCity($user)) {
                        $city = $user;
                        $region = static::regionData($user['pid']);
                    } else {
                        if ($user['numlevel'] == static::lvlCountry) {
                            $country = $user;
                        } else {
                            $region = $user;
                        }
                    }
                }
            }

            $current = ($city ? $city : ($region ? $region : ($country ? $country : array('id' => 0))));
        }

        switch ($key) {
            case 'id':
            { # id текущего: города | региона | 0
                return (!empty($current['id']) ? $current['id'] : 0);
            }
            break;
            case 'id-city':
            { # id текущего: города | 0
                return (!empty($city['id']) ? $city['id'] : 0);
            }
            break;
            case 'id-country':
            { # id текущей: страны | 0
                return (!empty($country['id']) ? $country['id'] : 0);
            }
            break;
            case 'url':
            { # данные о городе и регионе
                return array(
                    'city'   => ($city ? $city['keyword'] : ''),
                    'region' => ($region ? $region['keyword'] : ''),
                    'country' => ($country ? $country['keyword'] : ''),
                );
            }
            break;
            default:
                return $current;
        }
    }

    /**
     * Применять фильтр по региону только исходя из указанного в URL
     * @return boolean
     */
    public static function filterUrlOnly()
    {
        return config::sysAdmin('geo.filter.url', false, TYPE_BOOL) || bff::isRobot();
    }

    /**
     * Получаем ID города/региона на основе текущего URL
     * @param mixed $key ключ требуемых данных
     * @return integer|array
     */
    public static function filterUrl($key = false)
    {
        static $inited = false, $country = 0, $region = 0, $city = 0, $id = 0, $keyword = '';
        if (!$inited) {
            $inited = true;
            if (static::coveringType(self::COVERING_CITY)) {
                $city = static::regionData(static::coveringRegion());
            } else {
                switch (static::urlType()) {
                    case self::URL_SUBDOMAIN: # поддомены
                    {
                        $host = Request::host();
                        if (preg_match('/(.*)\.' . preg_quote(SITEHOST) . '/', $host, $matches) > 0 && !empty($matches[1])) {
                            # страна / область / город
                            $data = static::regionDataByKeyword($matches[1]);
                            if ($data && !static::coveringRegionCorrect($data)) {
                                $data = false;
                            }
                            if ($data) {
                                if (isset($data['enabled']) && !$data['enabled'] && Request::isGET()) {
                                    bff::errors()->error404();
                                }
                                if ($data['numlevel'] == self::lvlCity) {
                                    $city = $data;
                                } else {
                                    if ($data['numlevel'] == self::lvlRegion) {
                                        $region = $data;
                                    } else {
                                        if ($data['numlevel'] == self::lvlCountry) {
                                            $country = $data;
                                        } else {
                                            if (Request::isGET()) {
                                                bff::errors()->error404();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;
                    case self::URL_SUBDIR: # поддиректории
                    {
                        $extra = Site::urlExtra(array(), array('before'=>'region','regexp'=>true));
                        $uri = Request::uri();
                        if (preg_match('/^\/' . (!empty($extra) ? join('\/', $extra).'\/' : '') . '([^\/]+)\/(.*)/', $uri, $matches) > 0 && !empty($matches[1])) {
                            $data = static::regionDataByKeyword($matches[1]);
                            if ($data && !static::coveringRegionCorrect($data)) {
                                $data = false;
                            }
                            if ($data) {
                                if (isset($data['enabled']) && !$data['enabled'] && Request::isGET()) {
                                    bff::errors()->error404();
                                }
                                if ($data['numlevel'] == self::lvlCity) {
                                    $city = $data;
                                } else {
                                    if ($data['numlevel'] == self::lvlRegion) {
                                        $region = $data;
                                    } else {
                                        if ($data['numlevel'] == self::lvlCountry) {
                                            $country = $data;
                                        } else {
                                            if (Request::isGET()) {
                                                bff::errors()->error404();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;
                    case self::URL_NONE: # регион не фигурирует в URL
                    {
                        # не анализируем URL
                    }
                    break;
                }
            }

            list($id, $keyword) = ($city ? array($city['id'], $city['keyword']) :
                ($region ? array($region['id'], $region['keyword']) :
                ($country ? array($country['id'], $country['keyword']) :
                    array(0, ''))));
        }

        if ($key == 'id') {
            return $id;
        } else {
            if ($key == 'keyword') {
                return $keyword;
            }
        }

        return array('city' => $city, 'region' => $region, 'country' => $country, 'id' => $id, 'keyword' => $keyword);
    }

    /**
     * Получаем / устанавливаем ID города/региона пользователя
     * @param integer|boolean $nRegionID устанавливаем ID города/региона или FALSE (получаем текущий)
     * @param string|boolean $reason причина по которой регион пользователя был обновлен
     * @return integer
     */
    public static function filterUser($nRegionID = false, $reason = false)
    {
        if (bff::isRobot()) {
            if ($nRegionID !== false) {
                return $nRegionID;
            } else {
                return 0;
            }
        }

        $cookieKey = static::regionCookie();
        if ($nRegionID !== false) {
            # обновляем куки
            if (!isset($_COOKIE[$cookieKey]) || $_COOKIE[$cookieKey] != $nRegionID) {
                Request::setCOOKIE($cookieKey, $nRegionID, config::sysAdmin('geo.filter.cookie.expire', 100, TYPE_UINT));
                if ( ! ($reason === 'filter-init' && static::ipLocationConfirm())) {
                    $_COOKIE[$cookieKey] = $nRegionID;
                }
            }
        } else {
            if (static::coveringType(self::COVERING_CITY)) {
                $nRegionID = static::coveringRegion();
            } else {
                if (!isset($_COOKIE[$cookieKey])) {
                    # определяем ID город по IP адресу пользователя
                    # - если не включено подтверждение
                    if (!static::ipLocationConfirm()) {
                        if (static::ipLocationEnabled()) {
                            $aData = static::model()->regionDataByIp();
                            if (empty($aData)) {
                                $aData = array('id' => 0);
                            } else {
                                if (!static::coveringRegionCorrect($aData)) {
                                    $aData['id'] = 0;
                                }
                            }
                            $nRegionID = $aData['id'];
                            if ($nRegionID > 0) {
                                # кешируем
                                static::regionData($aData['id'], $aData);
                                static::regionDataByKeyword($aData['keyword'], $aData);
                            }
                        } else {
                            $nRegionID = 0;
                        }
                        static::filterUser($nRegionID, 'ip-location-auto');
                    }
                } else {
                    # получаем из куков
                    $nRegionID = bff::input()->cookie($cookieKey, TYPE_UINT);
                }
            }
        }

        return $nRegionID;
    }

    /**
     * Формирование URL, с учетом фильтра по "региону"
     * @param array $opts параметры:
     *  > country - keyword/array данные страны
     *  > region - keyword/array данные региона(области)
     *  > city - keyword/array данные города
     * @param boolean $dynamic динамическая ссылка
     * @param boolean $trailingSlash завершающий слеш
     * @return string
     */
    public static function url(array $opts = array(), $dynamic = false, $trailingSlash = true)
    {
        static $urlType;
        if (!isset($urlType)) {
            $urlType = static::urlType();
        }

        # формируем URL с учетом указанного города (city), области (region)
        if (isset($opts['region']) || isset($opts['city']) || isset($opts['country'])) {
            $geo = array('city' => false, 'region' => false, 'country' => false);

            # город
            if (isset($opts['city'])) {
                $geo['city'] = (is_string($opts['city']) ? $opts['city'] : (isset($opts['city']['keyword']) ? $opts['city']['keyword'] : ''));
            }
            # область
            if (isset($opts['region'])) {
                $geo['region'] = (is_string($opts['region']) ? $opts['region'] : (isset($opts['region']['keyword']) ? $opts['region']['keyword'] : ''));
            }
            # страна
            if (isset($opts['country'])) {
                $geo['country'] = (is_string($opts['country']) ? $opts['country'] : (isset($opts['country']['keyword']) ? $opts['country']['keyword'] : ''));
            }
        } # формируем URL с учетом текущего фильтра по "региону"
        else {
            $geo = static::filter('url');
        }

        $protocol = ($dynamic ? '//' : Request::scheme() . '://');
        $sitehost = ($dynamic ? '{sitehost}' : SITEHOST . bff::locale()->getLanguageUrlPrefix(LNG, false));
        $trailingSlash = ($trailingSlash ? '/' : '');
        switch ($urlType) {
            case self::URL_SUBDOMAIN: # поддомены
            {
                switch (static::coveringType()) {
                    case self::COVERING_COUNTRY:
                    case self::COVERING_COUNTRIES:
                    {
                        $subDomain = ($geo['city'] ? $geo['city'] . '.' :
                            ($geo['region'] ? $geo['region'] . '.' :
                            ($geo['country'] ? $geo['country'] . '.' : '')));
                    }
                    break;
                    case self::COVERING_REGION:
                    case self::COVERING_CITIES:
                    {
                        # нескольких городов: регион не участвует в URL
                        $subDomain = ($geo['city'] ? $geo['city'] . '.' : '');
                    }
                    break;
                    case self::COVERING_CITY:
                    {
                        # один город: город не участвует в URL
                        $subDomain = '';
                    }
                    break;
                }

                $url = $protocol . $subDomain . $sitehost . $trailingSlash;
            }
            break;
            case self::URL_SUBDIR: # поддиректории
            {
                switch (static::coveringType()) {
                    case self::COVERING_COUNTRY:
                    case self::COVERING_COUNTRIES:
                    {
                        $subDir = ($geo['city'] ? '/' . $geo['city'] :
                            ($geo['region'] ? '/' . $geo['region'] :
                            ($geo['country'] ? '/' . $geo['country'] : '')));
                    }
                    break;
                    case self::COVERING_REGION:
                    case self::COVERING_CITIES:
                    {
                        # нескольких городов: регион не участвует в URL
                        $subDir = ($geo['city'] ? '/' . $geo['city'] : '');
                    }
                    break;
                    case self::COVERING_CITY:
                    {
                        # 1 город: город не участвует в URL
                        $subDir = '';
                    }
                    break;
                }

                $url = $protocol . $sitehost . $subDir . $trailingSlash;
            }
            break;
            case self::URL_NONE: # регион не задействован в URL
            {
                $url = $protocol . $sitehost . $trailingSlash;
            }
            break;
        }

        return bff::filter('geo.url', $url, array('opts'=>$opts, 'dynamic'=>$dynamic, 'trailingSlash'=>$trailingSlash, 'geo'=>$geo));
    }

    /**
     * Текущий тип формирования URL с учетом региона и города
     * @return mixed
     */
    public static function urlType()
    {
        return config::get('geo_url', self::URL_SUBDIR, TYPE_UINT);
    }

    /**
     * Текущий тип покрытия регионов
     * @param int|array $type >0|array - проверяем на соответствие, 0 - возвращаем текущий
     * @return bool|mixed
     */
    public static function coveringType($type = 0)
    {
        $current = config::get('geo_covering', self::COVERING_COUNTRY, TYPE_UINT);
        if (!empty($type)) {
            if (is_array($type)) {
                return in_array($current, $type);
            }

            return ($current == $type);
        }

        return $current;
    }

    /**
     * ID города/нескольких городов/области(региона)/страны в зависимости от текущего покрытия регионов
     * @param int $regionLevel >0 - на основе Geo::lvl_, 0 - в зависимости от текущего покрытия регионов
     * @return bool|mixed
     */
    public static function coveringRegion($regionLevel = 0)
    {
        if (!$regionLevel) {
            switch (static::coveringType()) {
                case self::COVERING_COUNTRIES:
                {
                    $data = strval(config::get('geo_covering_lvl' . self::lvlCountry, ''));
                    if (strpos($data, ',') !== false) {
                        $data = explode(',', $data);
                        $data = array_map('intval', $data);
                    } else {
                        $data = array(intval($data));
                    }
                    return $data;
                }
                break;
                case self::COVERING_COUNTRY:
                {
                    return config::get('geo_covering_lvl' . self::lvlCountry, static::defaultCountry(), TYPE_UINT);
                }
                break;
                case self::COVERING_REGION:
                {
                    return config::get('geo_covering_lvl' . self::lvlRegion, 0, TYPE_UINT);
                }
                break;
                case self::COVERING_CITY:
                {
                    return config::get('geo_covering_lvl' . self::lvlCity, 0, TYPE_UINT);
                }
                break;
                case self::COVERING_CITIES:
                {
                    $data = strval(config::get('geo_covering_lvl' . self::lvlCity, ''));
                    if (strpos($data, ',') !== false) {
                        $data = explode(',', $data);
                        $data = array_map('intval', $data);
                    } else {
                        $data = array(intval($data));
                    }

                    return $data;
                }
                break;
            }
            return 0;
        } else {
            return config::get('geo_covering_lvl' . $regionLevel,
                ($regionLevel == self::lvlCountry ? static::defaultCountry() : 0)
            );
        }
    }

    /**
     * Проверка данных текущего региона на соответствие текущим настройка типа покрытия регионов
     * @param array $data @ref данные о текущем региона
     * @return boolean
     */
    public static function coveringRegionCorrect(array &$data)
    {
        if (empty($data) || !$data['id']) {
            return true;
        }

        switch (static::coveringType()) {
            case self::COVERING_COUNTRIES:
            {
                $countries = static::coveringRegion();
                if (in_array($data['id'], $countries)) {
                    return true;
                }
                if (!in_array($data['country'], $countries)) {
                    return false;
                }
            }
            break;
            case self::COVERING_COUNTRY:
            {
                if ($data['country'] != static::coveringRegion()) {
                    return false;
                }
            }
            break;
            case self::COVERING_REGION:
            {
                if ($data['pid'] != static::coveringRegion()) {
                    return false;
                }
            }
            break;
            case self::COVERING_CITIES:
            {
                if (!in_array($data['id'], static::coveringRegion())) {
                    return false;
                }
            }
            break;
            case self::COVERING_CITY:
            {
                if ($data['id'] != static::coveringRegion()) {
                    return false;
                }
            }
            break;
        }

        return true;
    }

    /**
     * Autocomplete для городов/областей
     * @param bool $bReturnArray вернуть результат в виде массива
     * @return array|void
     */
    public function regionSuggest($bReturnArray = false)
    {
        $aData = $this->input->postm(array(
            # часть названия города/области (вводимая пользователем)
            'q'      => TYPE_NOTAGS,
            # выполнять поиск среди городов и областей(регионов), false - только среди городов
            'reg'    => TYPE_BOOL,
            # выполнять поиск среди стран
            'country'    => TYPE_BOOL,
            # выполнять поиск среди городов в которых есть метро
            'metro'  => TYPE_BOOL,
            # ID области(региона) или 0 (во всех областях)
            'region' => TYPE_UINT,
            # ID страны или 0 (Geo::defaultCountry())
            'country_id' => TYPE_UINT,
        ));
        extract($aData);

        $sqlTitle = 'R.title_' . LNG;
        $aFilter = array('enabled' => 1);

        $aLevels = array(self::lvlCity);
        switch (static::coveringType()) {
            case self::COVERING_COUNTRIES:
            {
                $aFilter['country'] = $country_id ? $country_id : static::coveringRegion();
                if ($country) { # + страны
                    $aLevels[] = self::lvlCountry;
                    unset($aFilter['country']);
                }
                if ($reg) { # + области
                    $aLevels[] = self::lvlRegion;
                }
            }  break;
            case self::COVERING_COUNTRY:
            {
                if ($reg) { # + области
                    $aLevels[] = self::lvlRegion;
                }
                $aFilter['country'] = static::coveringRegion();
            }
            break;
            case self::COVERING_REGION:
            {
                $aFilter['pid'] = static::coveringRegion();
            }
            break;
            case self::COVERING_CITIES:
            {
                $aFilter['id'] = static::coveringRegion();
            }
            break;
            case self::COVERING_CITY:
            {
                $aFilter['id'] = static::coveringRegion();
            }
            break;
        }

        if ($metro) {
            $aFilter['metro'] = 1;
        }
        if ($region) {
            $aFilter['pid'] = $region;
        }

        # поиск по названию
        $sQuery = $this->input->cleanSearchString($q, 50);
        $aFilter[':title'] = array('(' . $sqlTitle . ' LIKE (:q) OR R.title_alt LIKE (:qa))',
            ':q' => $sQuery . '%',
            ':qa' => '%' . $sQuery . '%',
        );

        # получаем список подходящих по названию городов
        $aResult = static::model()->regionsList($aLevels, $aFilter, 0, config::sysAdmin('geo.city.select.suggest.limit', 10, TYPE_UINT), 'R.numlevel DESC, R.main DESC, ' . $sqlTitle . ' ASC');
        if ($bReturnArray) {
            return $aResult;
        }

        $aCity = array();
        $bCountries = static::coveringType(self::COVERING_COUNTRIES);
        foreach ($aResult as $v) {
            $declension = func::unserialize($v['declension']);
            $s = array(
                $v['id'],
                $v['title'] . (!bff::adminPanel() ? '<br /><small class="grey">' . $v['ptitle'] . '</small>' : ''),
                $v['metro'],
                $v['pid'],
            );
            if ($bCountries) {
                $s[] = $v['country'];
            }
            $s[] = isset($declension[LNG]) ? $declension[LNG] : '';
            $aCity[] = $s;
        }

        $this->ajaxResponse($aCity);
    }

    /**
     * Формируем подсказку(presuggest) состоящую из основных городов, в формате JSON
     * @param int $nCountryID ID страны или 0 (Geo::defaultCountry())
     * @param bool $bReturnArray вернуть результат в виде массива
     * @param bool $limit лимитировать выборку
     * @return string|array
     */
    public static function regionPreSuggest($nCountryID = 0, $bReturnArray = false, $limit = true)
    {
        if (empty($nCountryID)) {
            $nCountryID = static::defaultCountry();
        }

        $limit = ($limit ? config::sysAdmin('geo.city.select.presuggest.limit', 15, TYPE_UINT) : 0);
        $coveringType = static::coveringType();
        $cache = Cache::singleton('geo');
        $cacheKey = 'city-presuggest-' . $coveringType . '-' .$nCountryID.'-'. LNG.'-'.$limit;
        if (($aData = $cache->get($cacheKey)) === false) {
            # получаем список предвыбранных основных городов страны
            $aFilter = array('main>0', 'enabled' => 1);
            $sOrderBy = 'main';
            switch ($coveringType) {
                case self::COVERING_COUNTRY:
                case self::COVERING_COUNTRIES:
                {
                    $aFilter['country'] = $nCountryID;
                }
                break;
                case self::COVERING_REGION:
                {
                    $aFilter['pid'] = static::coveringRegion();
                }
                break;
                case self::COVERING_CITIES:
                {
                    $aFilter['id'] = static::coveringRegion();
                    $sOrderBy = 'FIELD (R.id, ' . join(',', $aFilter['id']) . ')'; /* MySQL only */
                    unset($aFilter[0]); # main>0
                }
                break;
                case self::COVERING_CITY:
                {
                    $aFilter['id'] = static::coveringRegion();
                }
                break;
            }
            $aData = static::model()->regionsList(self::lvlCity, $aFilter, 0, $limit, $sOrderBy);
            $cache->set($cacheKey, $aData);
        }
        if ($bReturnArray) {
            return $aData;
        }

        $aResult = array();
        foreach ($aData as $v) {
            $v['declension'] = func::unserialize($v['declension']);
            $v['declension'] = (!empty($v['declension'][LNG]) ? $v['declension'][LNG] : '');
            if ($coveringType == self::COVERING_COUNTRIES){
                $aResult[] = array($v['id'], $v['title'], $v['metro'], $v['pid'], $v['country'], $v['declension']);
            } else {
                $aResult[] = array($v['id'], $v['title'], $v['metro'], $v['pid'], $v['declension']);
            }
        }

        return func::php2js($aResult, true); # возвращаем в JSON-формате для autocomplete.js
    }

    /**
     * Формируем список областей(регионов)
     * @param int $nCountryID ID страны или 0 (в стране static::defaultCountry(), если используется)
     * @param bool $bResetCache сбросить кеш
     * @return array
     */
    public static function regionList($nCountryID = 0, $bResetCache = false)
    {
        if (empty($nCountryID)) {
            $nCountryID = static::defaultCountry();
        }

        $cache = Cache::singleton('geo');
        $cacheKey = 'region-list-' . $nCountryID . '-' . LNG;
        if ($bResetCache === true || ($aData = $cache->get($cacheKey)) === false) {
            $aFilter = array('enabled' => 1);
            $aFilter['country'] = $nCountryID;
            $aData = static::model()->regionsList(self::lvlRegion, $aFilter, 0, 0, 'num');
            $cache->set($cacheKey, $aData);
        }

        return $aData;
    }

    /**
     * Формируем список городов региона
     * @param int $nRegionID ID области(региона) или 0 (список основных городов страны Geo::defaultCountry())
     * @param bool $bResetCache сбросить кеш
     * @return array
     */
    public static function cityList($nRegionID, $bResetCache = false)
    {
        $cache = Cache::singleton('geo');
        $cacheKey = 'city-list-' . $nRegionID . '-' . LNG;
        if ($bResetCache === true || ($aData = $cache->get($cacheKey)) === false) {
            $aFilter = array('enabled' => 1);
            if ($nRegionID) {
                $aFilter['pid'] = $nRegionID;
            } else {
                $aFilter['country'] = static::defaultCountry();
            }
            $aData = static::model()->regionsList(self::lvlCity, $aFilter, 0, 0, 'R.num');
            $cache->set($cacheKey, $aData);
        }

        return $aData;
    }

    /**
     * Формируем список городов
     * @param array $aCityID ID городов
     * @param bool $bResetCache сбросить кеш
     * @return array
     */
    public static function cityListByID(array $aCityID, $bResetCache = false)
    {
        $cache = Cache::singleton('geo');
        $cacheKey = 'city-list-by-id-' . join(',', $aCityID) . '-' . LNG;
        if ($bResetCache === true || ($aData = $cache->get($cacheKey)) === false) {
            $aFilter = array('enabled' => 1, 'id' => $aCityID);
            $aData = static::model()->regionsList(self::lvlCity, $aFilter, 0, 0, 'R.num');
            $cache->set($cacheKey, $aData);
        }

        return $aData;
    }

    /**
     * Формируем список стран
     * @param bool $bResetCache сбросить кеш
     * @return array
     */
    public static function countriesList($bResetCache = false)
    {
        $coveringType = static::coveringType();
        $cache = Cache::singleton('geo');
        $cacheKey = 'countries-list-'.$coveringType.'-' . LNG;
        if ($bResetCache === true || ($aData = $cache->get($cacheKey)) === false) {
            if ($coveringType === static::COVERING_COUNTRIES) {
                $aCountries = static::coveringRegion();
                $aFilter = array('enabled' => 1, 'id' => $aCountries);
                $aData = static::model()->regionsList(self::lvlCountry, $aFilter, 0, 0, 'FIELD(R.id, '.(is_array($aCountries) ? join(',', $aCountries) : strval($aCountries)).')');
            } else {
                $aFilter = array('enabled' => 1);
                $aData = static::model()->regionsList(self::lvlCountry, $aFilter);
            }
            $cache->set($cacheKey, $aData);
        }
        return $aData;
    }

    /**
     * Форма выбора города
     * @param integer $cityID ID выбранного города
     * @param boolean $isForm true - форма добавления/редактирования сущности
     * @param string $fieldName имя поля для хранения ID выбранного города
     * @param array $options доп. настройки
     * @return string HTML
     */
    public function citySelect($cityID, $isForm, $fieldName = '', array $options = array())
    {
        $aData = array(
            'covering_type' => static::coveringType(),
            'is_form'       => $isForm,
            'field_name'    => (!empty($fieldName) ? $fieldName : 'city_id'),
            'field_country_name'    => (!empty($options['field_country_name']) ? $options['field_country_name'] : 'reg1_country'),
            'options'       => $options,
        );

        if ($aData['covering_type'] == static::COVERING_CITY) {
            $aData['covering_city_id'] = static::coveringRegion();
            if (!$cityID) {
                $cityID = $aData['covering_city_id'];
            }
        }

        $aData['country_id'] = static::defaultCountry();
        if (!$cityID && $aData['covering_type'] == static::COVERING_COUNTRIES) {
            if (!isset($options['country_empty'])) {
                $options['country_empty'] = _t('geo', 'Выбрать страну');
            }
            switch ($options['form']) {
                case 'bbs-form': # форма добавления/редактирования объявлений
                    if (bff::adminPanel()) {
                        $aData['country_id'] = 0;
                    } else {
                        $aData['country_id'] = static::filter('id-country');
                        if ($aData['country_id'] > 0) {
                            $options['country_empty'] = '';
                        }
                    }
                    break;
                case 'shops-form': # форма добавления магазина
                case 'shops-settings': # настройки магазина (редактирование)
                case 'users-settings': # настройки пользователя
                case 'banners-form': # добавление банера
                    $aData['country_id'] = 0;
                    break;
            }
        }

        $aData['city_id'] = $cityID;
        $aData['city'] = static::regionData($cityID);
        if (empty($aData['city'])) {
            $aData['city'] = array('title' => '');
            if (isset($options['country_value'])) {
                $aData['country_id'] = $options['country_value'];
            }
        } else {
            $aData['country_id'] = $aData['city']['country'];
        }

        if ($aData['covering_type'] == static::COVERING_COUNTRIES) {
            $aData['country_options'] = HTML::selectOptions(static::countriesList(), $aData['country_id'], ! empty($options['country_empty']) ? $options['country_empty'] : false, 'id', 'title');
        }

        if (bff::adminPanel()) {
            return $this->viewPHP($aData, 'admin.city.select');
        } else {
            return $this->viewPHP($aData, 'city.select');
        }
    }

    /**
     * Форма выбора страны / региона / города один автокомплитер
     * @param integer $regionID ID выбранной страны / региона / города
     * @param string $fieldName имя поля для хранения ID выбранного города
     * @param array $aData доп. настройки
     * @return string HTML
     */
    public function regionSelect($regionID, $fieldName = '', array $aData = array())
    {
        $aData['field_value'] = $regionID;
        $aData['field_name'] = ! empty($fieldName) ? $fieldName : 'region_id';
        $aData['covering_type'] = static::coveringType();

        $aData['field_title'] = '';
        $aRegion = static::regionData($regionID);
        if (!empty($aRegion['title'])) {
            $aData['field_title'] = $aRegion['title'];
        }

        if (bff::adminPanel()) {
            return $this->viewPHP($aData, 'admin.region.select');
        }
        return '';
    }

    /**
     * Использовать районы города
     * @return bool
     */
    public static function districtsEnabled()
    {
        return config::sysAdmin('geo.districts', false, TYPE_BOOL);
    }

    /**
     * Формируем список районов города
     * @param int $nCityID ID города
     * @param bool $bResetCache сбросить кеш
     * @return array
     */
    public static function districtList($nCityID = 0, $bResetCache = false)
    {
        $cache = Cache::singleton('geo');
        $cacheKey = 'district-list-' . $nCityID . '-' . LNG;
        if ($bResetCache === true || ($aData = $cache->get($cacheKey)) === false) {
            $aData = static::model()->districtsList($nCityID);
            $cache->set($cacheKey, $aData);
        }

        return $aData;
    }

    /**
     * Формируем список районов города в формате select::options
     * @param int $nCityID ID города
     * @param int|bool $mSelectedID
     * @param mixed $mEmptyOption название option-пункта в случае если район не указан
     * @return string
     */
    public static function districtOptions($nCityID = 0, $mSelectedID = false, $mEmptyOption = 'Выбрать')
    {
        return HTML::selectOptions(static::districtList($nCityID), $mSelectedID, $mEmptyOption, 'id', 't');
    }

    /**
     * Координаты карты по-умолчанию
     * @param boolean $explode вернуть в качестве массива
     * @return string|array
     */
    public static function mapDefaultCoords($explode = false)
    {
        $coords = config::get('geo_default_coords');
        if (empty($coords) || strpos($coords, ',')===false) {
            $coords = static::$ymapsDefaultCoords;
        }
        return ( $explode ? explode(',', $coords) : $coords );
    }

    /**
     * Проверка координат, если не указаны, корректируем на координаты по-умолчанию
     * @param mixed $lat @ref координата latitude
     * @param mixed $lon @ref координата longitude
     */
    public static function mapDefaultCoordsCorrect(&$lat, &$lon)
    {
        if (!floatval($lat) || !floatval($lon)) {
            list($lat, $lon) = static::mapDefaultCoords(true);
        }
    }

    /**
     * Сбрасываем кеш
     * @param mixed $mLevel тип региона Geo::lvl...
     * @param string $mExtra
     */
    public function resetCache($mLevel = false, $mExtra = '')
    {
        Cache::singleton('geo')->flush('geo');
    }

    /**
     * Тип карт
     * @param boolean $includeEditor подключать редактор карты
     * @return mixed
     */
    public static function mapsAPI($includeEditor = false)
    {
        switch (static::mapsType())
        {
            case self::MAPS_TYPE_YANDEX:
                tpl::includeJS(static::$ymapsJS, false);
                break;
            case self::MAPS_TYPE_GOOGLE:
                tpl::includeJS(Request::scheme().'://maps.googleapis.com/maps/api/js?key='.config::sysAdmin('geo.maps.googleKey','',TYPE_STR).'&v=3&language='.LNG, false);
                break;
        }
        if ($includeEditor) {
            tpl::includeJS('maps.editor', true);
        }
    }

    /**
     * Включена ли функция определения региона по IP пользователя
     * @return bool
     */
    public static function ipLocationEnabled()
    {
        return config::sysAdmin('geo.ip.location', false, TYPE_BOOL);
    }

    /**
     * Спрашивать подтверждение региона пользователем
     * При включенной функции определения региона по IP
     * @return bool
     */
    public static function ipLocationConfirm()
    {
        return config::sysAdmin('geo.ip.location.confirm', false, TYPE_BOOL) && static::ipLocationEnabled() && !isset($_COOKIE[static::regionCookie()]);
    }

    /**
     * Cookie ключ для хранения текущего региона пользователя
     * @return string
     */
    public static function regionCookie()
    {
        return bff::cookiePrefix() . 'geo';
    }
}