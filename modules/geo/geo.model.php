<?php

class GeoModel_ extends GeoModelBase
{
    /*
     * Получение информации по региону, включая название родителя
     * @param int|array $mNumlevel тип региона(нескольких регионов) (Geo::lvl_)
     * @param array $aFilter фильтр
     * @param string $lang язык выборки
     * @param string $sOrderBy
     */
    public function regionsListingData($mNumlevel, $aFilter = array(),$lang = LNG, $sOrderBy = '')
    {
        $aBind = array();
        if (is_array($mNumlevel)) {
            $aFilter[':numlevel'] = 'R.numlevel IN (' . join(',', $mNumlevel) . ')';
        } else {
            $aFilter['numlevel'] = $mNumlevel;
        }

        $aFilter = $this->prepareFilter($aFilter, 'R', $aBind);
        return $this->db->tag('geo-regions-listing-data', array('filter'=>&$aFilter))->select_key('SELECT R.id, R.pid, R.title_' . $lang . ' as title, R.enabled,
                                    R.keyword, R.main, R.metro, R.num, P.title_' . $lang . ' as parentTitle
                      FROM ' . TABLE_REGIONS . ' R
                         LEFT JOIN ' . TABLE_REGIONS . ' P ON R.pid = P.id
                      ' . $aFilter['where'] . '
                      ORDER BY ' . (!empty($sOrderBy) ? $sOrderBy : 'R.main DESC, R.num'), 'id', $aFilter['bind']
        );
    }
    
    /*
     * Получение списка станций метро по фильтру
     * @param array $aFilter фильтр
     * @param string $lang язык выборки
     */
    public function metroStationsList($aFilter = array(),$lang = LNG)
    {
        $aFilter = $this->prepareFilter($aFilter, '');
        return $this->db->tag('geo-metro-stations-list-data', array('filter'=>&$aFilter))->select('SELECT id,city_id, title_' . $lang . ' as title
                                  FROM ' . TABLE_REGIONS_METRO . '
                                  ' . $aFilter['where'], $aFilter['bind']
        );
    }

}
