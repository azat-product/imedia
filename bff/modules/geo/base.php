<?php

abstract class GeoModuleBase extends Module
{
    /** @var GeoModelBase */
    public $model = null;
    protected $securityKey = '2a27ae38a37d160c3b41a1d4e30ea715';

    # Уровни, определяющие тип региона (в таблице TABLE_REGIONS)
    const lvlCountry  = 1; # страны - таблица TABLE_REGIONS
    const lvlRegion   = 2; # регионы/области - таблица TABLE_REGIONS
    const lvlCity     = 3; # города - таблица TABLE_REGIONS
    const lvlDistrict = 4; # районы - таблица TABLE_REGIONS_DISTRICTS
    const lvlMetro    = 5; # метро+ветки - таблица TABLE_REGIONS_METRO

    /** @var array Возможность редактирования в админ-панели:
     * lvlCountry  - стран
     * lvlRegion   - регионов/областей
     * lvlCity     - городов
     * lvlDistrict - районов городов
     * lvlMetro    - станций(веток) метро
     */
    public static $useRegions = array();
    /** @var bool Использовать ветки метро */
    public static $useMetroBranches = true;

    # Региональные настройки приложения
    public static $defaultCountry = 1;
    public static $defaultRegion = 0;
    public static $defaultCity = 0;

    # Yandex.Maps
    public static $ymapsJS = 'https://api-maps.yandex.ru/2.0/?load=package.full&lang=ru-RU&onerror=onYMapError';
    public static $ymapsCoordOrder = 'longlat';
    public static $ymapsDefaultCoords = '30.5223,50.4511';

    /**
     * Тип карт
     */
    const MAPS_TYPE_GOOGLE = 'google'; # Google Maps
    const MAPS_TYPE_YANDEX = 'yandex'; # Yandex Карты

    public function init()
    {
        parent::init();
        $this->module_title = _t('geo','Гео');
    }

    /**
     * @return Geo
     */
    public static function i()
    {
        return bff::module('geo');
    }

    /**
     * @return GeoModelBase
     */
    public static function model()
    {
        return bff::model('geo');
    }

    public static function manageRegions($lvl)
    {
        return !empty(static::$useRegions) && in_array($lvl, static::$useRegions);
    }

    /**
     * Инициализация доп. полей регионов
     * @param array|int $nLevel тип региона (несколько типов) Geo::lvl...
     * @param array|bool $extraFields наcтройки доп. полей:
     *    [
     *      ['title' => Название поля в форме,
     *       'field' => Название поля в базе данных,
     *       'type'  => Тип поля, доступны:
     *           'text' - однострочное текстовое поле
     *           'textarea' - многострочное текстовое поле
     *           'wy' - Wysiwyg,
     *       'validateType' => Тип валидации (TYPE_, по-умолчанию: TYPE_NOTAGS)
     *      ], ...
     *    ]
     * @return array|bool
     */
    public function regionsFormExtraFields($nLevel, $extraFields = false)
    {
        static $settings;
        if (empty($extraFields) || !is_array($extraFields)) {
            return (isset($settings[$nLevel]) ? $settings[$nLevel] : array());
        } else {
            foreach ($extraFields as &$f) {
                $f = array_merge(array('field'=>'','type'=>'text','validateType'=>TYPE_NOTAGS,'attr'=>array()), $f);
                if (empty($f['field']) || in_array($f['field'], array('id','pid','country','main','numlevel','keyword','metro'))) continue;
                foreach (array('class','style') as $aa) if (!isset($f['attr'][$aa])) $f['attr'][$aa] = '';
                $f['attr']['class'] .= ' stretch j-input';
                switch ($f['type']) {
                    case 'textarea':
                        if (empty($f['attr']['style'])) {
                            $f['attr']['style'] = 'min-height:85px;';
                        }
                        break;
                    case 'wy':
                        $f['attr']['class'] .= ' j-wy';
                        $f['validateType'] = TYPE_STR;
                        if (empty($f['attr']['style'])) {
                            $f['attr']['style'] = 'height:100px;';
                        }
                        break;
                    case 'checkbox':
                        $f['attr']['class'] = '';
                        $f['validateType'] = TYPE_BOOL;
                        break;
                }
                $f['attr'] = HTML::attributes($f['attr']);
            } unset($f);
            if (is_array($nLevel)) {
                foreach ($nLevel as $lvl) {
                    $settings[$lvl] = $extraFields;
                }
            } else {
                $settings[$nLevel] = $extraFields;
            }
        }
    }

    /**
     * Cписок регионов
     * @param int $nLevel тип региона Geo::lvl...
     * @param int $nParentRegionID ID основного региона или 0
     * @return array
     */
    public function regionsList($nLevel, $nParentRegionID = 0)
    {
        return $this->model->regionsList($nLevel, array('pid' => $nParentRegionID));
    }

    /**
     * Выпадающий список регионов
     * @param int $nLevel тип региона Geo::lvl...
     * @param int|bool $nSelectedID ID выбранного региона
     * @param int $nParentRegionID ID основного региона или 0
     * @param array|bool $mEmptyOpt @see HTML::selectOptions
     * @return string
     */
    public function regionsOptions($nLevel, $nSelectedID = false, $nParentRegionID = 0, $mEmptyOpt = false)
    {
        $aRegions = $this->regionsList($nLevel, $nParentRegionID);

        return HTML::selectOptions($aRegions, $nSelectedID, $mEmptyOpt, 'id', 'title');
    }

    /**
     * Получаем данные о регионе по ID
     * @param int $nRegionID ID региона(города/области/страны)
     * @param mixed $mCache , если не NULL => сохраняем в кеш
     * @return mixed
     */
    public static function regionData($nRegionID, $mCache = null)
    {
        static $cache = array();
        if (isset($cache[$nRegionID])) {
            return $cache[$nRegionID];
        }
        if (!is_null($mCache)) {
            return ($cache[$nRegionID] = $mCache);
        }
        $aData = static::model()->regionData(array('id' => $nRegionID));
        if (!empty($aData)) {
            static::regionDataByKeyword($aData['keyword'], $aData); # кешируем для поиска по Keyword
        }

        return ($cache[$nRegionID] = $aData);
    }

    /**
     * Получаем данные о регионе по Keyword
     * @param string $sRegionKeyword Keyword региона(города/области)
     * @param mixed $mCache , если не NULL => сохраняем в кеш
     * @return mixed
     */
    public static function regionDataByKeyword($sRegionKeyword, $mCache = null)
    {
        static $cache = array();
        if (isset($cache[$sRegionKeyword])) {
            return $cache[$sRegionKeyword];
        }
        if (!is_null($mCache)) {
            return ($cache[$sRegionKeyword] = $mCache);
        }
        $aData = static::model()->regionData(array('keyword' => $sRegionKeyword, 'numlevel >= ' . self::lvlCountry));
        if (!empty($aData)) {
            static::regionData($aData['id'], $aData); // кешируем для поиска по ID
        }

        return ($cache[$sRegionKeyword] = $aData);
    }

    /**
     * Получаем название региона по ID
     * @param int $nRegionID ID региона(города/области)
     * @param string $mEmptyText текст по-умолчанию, для случая если неудалось определить регион по ID
     * @return string
     */
    public static function regionTitle($nRegionID, $mEmptyText = '')
    {
        if (!$nRegionID) {
            return $mEmptyText;
        }
        $aData = static::regionData($nRegionID);
        if (empty($aData) || !isset($aData['title'])) {
            return $mEmptyText;
        }

        return $aData['title'];
    }

    /**
     * Получаем, является ли регион городом
     * @param int|array $mRegionID ID региона или данные о регионе (полученные методом Geo::regionData или Geo::regionDataByKeyword)
     * @return bool
     */
    public static function isCity($mRegionID)
    {
        if (empty($mRegionID)) {
            return false;
        }
        $aData = (is_array($mRegionID) ? $mRegionID : static::regionData($mRegionID));
        if (empty($aData) || !isset($aData['numlevel'])) {
            return false;
        }

        return ($aData['numlevel'] == self::lvlCity);
    }

    /**
     * Получаем, есть ли в регионе метро
     * @param int $nRegionID ID города
     * @return bool
     */
    public static function hasMetro($nRegionID)
    {
        if (empty($nRegionID)) {
            return false;
        }
        $aData = static::regionData($nRegionID);
        if (empty($aData) || !isset($aData['numlevel'])) {
            return false;
        }

        return ($aData['numlevel'] == self::lvlCity && !empty($aData['metro']));
    }

    /**
     * Получаем список веток и станций метро по ID города
     * @param int $nCityID ID города
     * @param int $nSelectedMetroID ID выбранной станции
     * @param string|bool $sHTML формировать html
     * @param string|bool $templateName название шаблона (без расширения ".php")
     * @param string|bool $moduleName название модуля / плагина к которому относится шаблон
     * @return array
     */
    public static function cityMetro($nCityID = 0, $nSelectedMetroID = 0, $sHTML = false, $templateName = false, $moduleName = false)
    {
        $aResult = array(
            'data'    => array(), # данные о ветках + станциях метро города(tree)
            'html'    => '', # html формат
            'city_id' => $nCityID,
            'sel'     => array(
                'id'      => $nSelectedMetroID, # ID выбранной станции метро
                'branch'  => array(), # данные о выбранной ветке метро
                'station' => array() # данные о выбранной станции метро
            )
        );

        do {
            if (empty($nCityID)) {
                break;
            }

            $aData = static::regionData($nCityID);
            if (empty($aData) || !isset($aData['numlevel'])) {
                break;
            }

            # это город + помечено наличие метро(metro=1)
            if (!($aData['numlevel'] == self::lvlCity && !empty($aData['metro']))) {
                break;
            }

            # получаем список веток+станций метро города(tree)
            $aMetro = static::model()->metroList($nCityID, static::$useMetroBranches);
            if (empty($aMetro)) {
                break;
            }

            # формируем
            foreach ($aMetro as $k => $v) {
                # помечаем данные о выбранной ветке / станции
                if (static::$useMetroBranches) {
                    if (!empty($v['st'][$nSelectedMetroID])) {
                        $aBranch = $v;
                        unset($aBranch['st']);
                        $aStation = $v['st'][$nSelectedMetroID];
                        unset($aStation['st']);
                        $aResult['sel']['station'] = $aStation;
                        $aResult['sel']['branch'] = $aBranch;
                    }
                } else {
                    if ($k == $nSelectedMetroID) {
                        $aResult['sel']['station'] = $v;
                    }
                }
            }

            $aResult['data'] = $aMetro;

            # формируем HTML
            if ($sHTML !== false) {
                if ($sHTML === true) {
                    # для adm результат формируем в select::options
                    if (bff::adminPanel()) {
                        $sHTML = 'select';
                    }
                }
                if ($sHTML === 'select') {
                    $sHTML = '';
                    if (!$nSelectedMetroID) {
                        $sHTML .= '<option value="0">' . _t('geo', 'Выберите станцию') . '</option>';
                    }
                    if (static::$useMetroBranches) {
                        foreach ($aMetro as $v) {
                            $sHTML .= '<optgroup label="' . $v['t'] . '">';
                            $sHTML .= HTML::selectOptions($v['st'], $nSelectedMetroID, false, 'id', 't');
                            $sHTML .= '</optgroup>';
                        }
                    } else {
                        $sHTML .= HTML::selectOptions($aMetro, $nSelectedMetroID, false, 'id', 't');
                    }
                    $aResult['html'] = $sHTML;
                } else {
                    # для frontend используем шаблон
                    $aResult['html'] = View::template($templateName, $aResult, $moduleName);
                }
            }

        } while (false);

        # не удалось получить данные о выбранной станции метро
        # считаем что id станции указан некорректно
        if ($nSelectedMetroID && empty($aResult['sel']['station'])) {
            $aResult['sel']['id'] = 0;
        }

        return $aResult;
    }

    /**
     * ID страны по-умолчанию
     * @return mixed
     */
    public static function defaultCountry()
    {
        return config::sysAdmin('geo.default.country', static::$defaultCountry, TYPE_UINT);
    }

    /**
     * ID региона по-умолчанию
     * @return mixed
     */
    public static function defaultRegion()
    {
        return config::sys('geo.default.region', static::$defaultRegion, TYPE_UINT);
    }

    /**
     * ID города по-умолчанию
     * @return mixed
     */
    public static function defaultCity()
    {
        return config::sys('geo.default.city', static::$defaultCity, TYPE_UINT);
    }

    /**
     * Тип карт
     * @return mixed
     */
    public static function mapsType()
    {
        return config::sysAdmin('geo.maps.type', self::MAPS_TYPE_YANDEX, TYPE_STR);
    }

    /**
     * Перехват для актуализации кеша
     * @apram
     */
    public function resetCache($mLevel = false, $mExtra = '')
    {
        # для сброса кеша, необходимо переопределить данный метод
        # в модуле приложения
    }

    /**
     * Обработка копирования данных локализации
     */
    public function onLocaleDataCopy($from, $to)
    {
        Cache::singleton('geo')->flush('geo');
    }
}