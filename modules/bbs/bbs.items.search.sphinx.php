<?php

/**
 * Поиск объявлений средствами Sphinx
 */
class BBSItemsSearchSphinx_ extends \bff\db\Sphinx
{
    public function init()
    {
        $this->moduleID(1);

        return parent::init();
    }

    public static function enabled()
    {
        return config::sysAdmin('bbs.search.sphinx', false, TYPE_BOOL) && parent::enabled();
    }

    /**
     * Список индексов
     * @param boolean $prefixed с учетом префиксов
     * @return array
     */
    public function indexes($prefixed = true)
    {
        $prefix = ($prefixed ? static::prefix() : '');
        return array(
            'main'  => $prefix.'itemsIndexMain',
            'delta' => $prefix.'itemsIndexDelta',
        );
    }

    /**
     * Настройки Sphinx
     * @param array $settings исходные настройки: [
     *      'table' => 'bff_sphinx',
     *      'charset' => 'utf8',
     *      'sources' => array(), @ref
     *      'indexes' => array(), @ref
     * ]
     */
    public function moduleSettings(array $settings)
    {
        # itemsSource:
        $settings['sources']['itemsSource'] = array(
            ':extends' => 'baseSource',
            'sql_query_range' => 'SELECT MIN(id), MAX(id) FROM '.TABLE_BBS_ITEMS,
            'sql_range_step'  => 1000,
            # задержка в миллисекундах между индексируемыми порциями данных
            'sql_ranged_throttle' => 0,

            'sql_attr_uint' => array(
                'is_publicated', 'status',
                'is_moderating', 'moderated', 'import',
                'imgcnt',
                'user_id',
                'shop_id',
                'reg1_country',
                'reg2_region',
                'reg3_city',
                'addr_lat',
                'addr_lon',
                'district_id',
                'metro_id',
                'cat_id',
                'cat_type',
                'owner_type',
                'regions_delivery',
                'svc_fixed', 'svc',
            ),
            'sql_attr_float' => array(
                'price_search',
            ),
            'sql_attr_timestamp' => array(
                'created',
            ),
        );
        # categories: cat_idN
        for ($i = 1; $i <= BBS::CATS_MAXDEEP; $i++) {
            $settings['sources']['itemsSource']['sql_attr_uint'][] = 'cat_id'.$i;
        }
        # dynprops: fN
        $dynpropsSettings = array(
            'datafield_int_last'   => 15,
            'datafield_text_first' => 16,
            'datafield_text_last'  => 20,
        );
        $dynpropsSettings = array_merge($dynpropsSettings, BBS::i()->dp()->getSettings(array_keys($dynpropsSettings)));
        for ($i = 1; $i <= $dynpropsSettings['datafield_int_last']; $i++) {
            $settings['sources']['itemsSource']['sql_attr_uint'][] = 'f'.$i;
        }

        $query = array(
            'id', 'user_id', 'shop_id',
            'is_publicated'/*new*/, 'status', 'is_moderating'/*new*/, 'moderated', 'import'/*new*/,
            ':title' => 'IFNULL({prefix}.title_translates, {prefix}.title) AS title',
            ':descr' => 'IFNULL({prefix}.descr_translates, {prefix}.descr) AS descr',
            'phones'/*new*/,
            'reg1_country', 'reg2_region', 'reg3_city', 'district_id', 'metro_id', 'regions_delivery',
            'addr_addr'/*new*/, 'addr_lat'/*new*/, 'addr_lon'/*new*/,
            'imgcnt', 'price_search', 'cat_id', 'cat_type', 'owner_type', 'svc_fixed', 'svc'/*new*/,
            ':created' => 'UNIX_TIMESTAMP({prefix}.created) as created',
        );
        for ($i = 1; $i <= BBS::CATS_MAXDEEP; $i++) {
            $query[] = 'cat_id'.$i;
        }
        for ($i = 1; $i <= $dynpropsSettings['datafield_int_last']; $i++) {
            $query[] = 'f'.$i;
        }
        $queryPrefix = 'i';
        foreach ($query as $k=>$v) {
            if (is_string($k)) {
                $query[$k] = str_replace('{prefix}', $queryPrefix, $v);
            } else {
                $query[$k] = $queryPrefix.'.'.$v;
            }
        }

        # itemsSourceMain:
        $settings['sources']['itemsSourceMain'] = array(
            ':extends' => 'itemsSource',
            'sql_query_pre' => array(
                'SET CHARACTER_SET_RESULTS='.$settings['charset'],
                'SET NAMES '.$settings['charset'],
                'UPDATE '.$settings['table'].' SET indexed = NOW() WHERE counter_id = '.$this->moduleID(),
            ),
            'sql_query' => ' \\
        SELECT '.join(', ', $query).' \\
        FROM '.TABLE_BBS_ITEMS.' i \\
        WHERE i.modified<NOW() AND i.id>=$start AND i.id<=$end'
        );

        # itemsSourceDelta:
        $settings['sources']['itemsSourceDelta'] = array(
            ':extends' => 'itemsSource',
            'sql_query_pre' => array(
                'SET CHARACTER_SET_RESULTS='.$settings['charset'],
                'SET NAMES '.$settings['charset'],
                'UPDATE '.$settings['table'].' SET indexed_delta = NOW() WHERE counter_id = '.$this->moduleID(),
            ),
            'sql_query' => ' \\
        SELECT '.join(', ', $query).' \\
        FROM '.TABLE_BBS_ITEMS.' i \\
        WHERE i.modified >= (SELECT indexed FROM '.$settings['table'].' WHERE counter_id = '.$this->moduleID().') \\
          AND i.id>=$start AND i.id<=$end',
        );

        # itemsIndexMain:
        $indexes = $this->indexes(false);
        $main = array(
            'source'   => 'itemsSourceMain',
        );
        $this->wordformsConfig($main);
        $settings['indexes'][$indexes['main']] = array_merge($settings['indexes']['indexTemplate'], $main);

        # itemsIndexDelta:
        $settings['indexes'][$indexes['delta']] = array(
            ':extends' => $indexes['main'],
            'source'   => 'itemsSourceDelta',
        );
    }

    /**
     * Поиск объявлений
     * @param string $query строка поиска
     * @param array $filter дополнительные фильтры
     * @param boolean $count только подсчет кол-ва
     * @param integer $limit лимит результатов на страницу
     * @param integer $offset смещение вывода результатов
     * @return array|integer
     */
    public function searchItems($query, $filter, $count = false, $limit = 1, $offset = 0)
    {
        $options = array('field_weights' => '(title=60,descr=40)');
        $query = $this->prepareSearchQuery($query, $options);

        $bind = array(':q' => $query);
        $where = array();
        $select = array();

        # категория
        if (isset($filter[':cat-filter'])) {
            $catsData = BBS::model()->catsDataByFilter(array('id'=>$filter[':cat-filter']), array('id','numlevel'));
            foreach ($catsData as $v) {
                if ( ! empty($v['numlevel'])) {
                    $filter['cat_id' . $v['numlevel']] = intval($v['id']);
                }
            }
        }

        # регион
        if (isset($filter[':region-filter'])) {
            $regionID = $filter[':region-filter'];
            if ($regionID > 0) {
                $regionData = Geo::regionData($regionID);
                if (config::sysAdmin('bbs.search.delivery', true, TYPE_BOOL)) {
                    if (Geo::coveringType(Geo::COVERING_COUNTRIES)) {
                        switch ($regionData['numlevel']) {
                            case Geo::lvlCountry:
                                $select[] = '(reg1_country = :reg1_country OR (reg1_country = :reg1_country AND regions_delivery = 1)) AS f_deliv';
                                $bind[':reg1_country'] = (int)$regionID;
                                break;
                            case Geo::lvlRegion:
                                $select[] = '(reg2_region = :reg2_region OR (reg1_country = :reg1_country AND regions_delivery = 1)) AS f_deliv';
                                $bind[':reg1_country'] = (int)$regionData['country'];
                                $bind[':reg2_region'] = (int)$regionID;
                                break;
                            case Geo::lvlCity:
                                $select[] = '(reg3_city = :reg3_city OR (reg1_country = :reg1_country AND regions_delivery = 1)) AS f_deliv';
                                $bind[':reg1_country'] = (int)$regionData['country'];
                                $bind[':reg3_city'] = (int)$regionID;
                                break;
                        }
                    } else {
                        switch ($regionData['numlevel']) {
                            case Geo::lvlCountry:
                                $select[] = '(reg1_country = :reg1_country OR regions_delivery = 1) AS f_deliv';
                                $bind[':reg1_country'] = (int)$regionID;
                                break;
                            case Geo::lvlRegion:
                                $select[] = '(reg2_region = :reg2_region OR regions_delivery = 1) AS f_deliv';
                                $bind[':reg2_region'] = (int)$regionID;
                                break;
                            case Geo::lvlCity:
                                $select[] = '(reg3_city = :reg3_city OR regions_delivery = 1) AS f_deliv';
                                $bind[':reg3_city'] = (int)$regionID;
                                break;
                        }
                    }
                    $where[] = 'f_deliv = 1';
                } else {
                    switch ($regionData['numlevel']) {
                        case Geo::lvlCountry:
                            $filter['reg1_country'] = (int)$regionID;
                            break;
                        case Geo::lvlRegion:
                            $filter['reg2_region'] = (int)$regionID;
                            break;
                        case Geo::lvlCity:
                            $filter['reg3_city'] = (int)$regionID;
                            break;
                    }
                }
            }
        }

        # цена
        if (isset($filter[':price'])) {
            $select[] = $filter[':price'].' AS f_price';
            $where[] = 'f_price = 1';
        }

        # дин. св-ва
        if ( ! empty($filter[':dp']) && is_array($filter[':dp'])) {
            $i = 1;
            foreach ($filter[':dp'] as $v) {
                $v = str_replace('I.', '', $v);
                if (strpos($v, '&') || strpos($v, 'OR')) {
                    $select[] = $v.' AS f_dp'.$i;
                    $where[] = 'f_dp'.$i.' = 1';
                    $i++;
                } else {
                    $where[] = $v;
                }
            }
        }

        # условия: column => condition
        foreach ($filter as $key=>$value) {
            if (is_string($key) && !empty($key) && $key{0} !== ':') {
                $where[] = \Model::condition($key, $value, $bind);
            }
        }

        $opt = array();
        foreach ($options as $k => $v) {
            $opt[] = $k.'='.$v;
        }

        $order = '';
        if ( ! $count) {
            $select[] = 'svc_fixed, WEIGHT() AS w';
            $order = ' ORDER BY svc_fixed DESC, w DESC';
        } else {
            if ( ! $limit) {
                $limit = 1;
            }
        }

        # http://sphinxsearch.com/docs/current/sphinxql-select.html
        $indexes = $this->indexes();
        $data = $this->exec('
            SELECT id '.(!empty($select) ? ', '.join(', ', $select) : '').'
            FROM '.$indexes['main'].', '.$indexes['delta'].'
            WHERE MATCH (:q) '.(!empty($where) ? ' AND '.join(' AND ', $where) : '').'
            '.$order.'
            LIMIT '.($offset ? $offset.',' : '').$limit.'
            '.(!empty($opt) ? 'OPTION '.join(',', $opt) : ''),
            $bind, PDO::FETCH_COLUMN, 'fetchAll');
        if ($data === false) {
            return false;
        }

        if ($count) {
            # только подсчет кол-ва
            # http://khaletskiy.blogspot.com/2014/06/sphinx-pagination.html
            $meta = $this->select('SHOW META');
            foreach ($meta as $v) {
                if ($v['Variable_name'] == 'total') {
                    return (int)$v['Value'];
                }
            }
        }
        return $data;
    }

    /**
     * Обновляем атрибуты индексов
     * @param array $data
     * @param array $filter
     * @param array $indexes
     * @param array $options
     * @return int|mixed
     */
    public function updateAttributes($data, $filter, array $indexes = array(), array $options = array())
    {
        $list = $this->indexes();
        if (empty($indexes)) {
            $indexes = $list;
        } else {
            foreach ($indexes as $k=>$v) {
                if (isset($list[$v])) {
                    $indexes[$k] = $list[$v];
                } else if (array_search($v, $list, true)) {
                    continue;
                } else {
                    unset($indexes[$k]);
                }
            }
        }
        return parent::updateAttributes($data, $filter, $indexes, $options);
    }
}