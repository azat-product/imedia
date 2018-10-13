<?php

/**
 * Поиск объявлений средствами Sphinx
 */
class ShopsSearchSphinx_ extends \bff\db\Sphinx
{
    public function init()
    {
        $this->moduleID(2);

        return parent::init();
    }

    public static function enabled()
    {
        return config::sysAdmin('shops.search.sphinx', false, TYPE_BOOL) && parent::enabled();
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
        # shopsSource:
        $settings['sources']['shopsSource'] = array(
            ':extends' => 'baseSource',
            'sql_query_range' => 'SELECT MIN(id), MAX(id) FROM '.TABLE_SHOPS,
            'sql_range_step'  => 1000,
            # задержка в миллисекундах между индексируемыми порциями данных
            'sql_ranged_throttle' => 0,

            'sql_attr_uint' => array(
                'user_id',
                'status',
                'moderated',
                'reg1_country',
                'reg2_region',
                'reg3_city',
                'svc_fixed',
            ),
            'sql_attr_float' => array(
                'addr_lat',
            ),
            'sql_attr_timestamp' => array(
                'created',
            ),
            'sql_attr_multi' => array(
                'uint in_cat from query; \\
        SELECT shop_id, category_id FROM '.TABLE_SHOPS_IN_CATEGORIES,
                'uint in_cat_bbs from query; \\
        SELECT shop_id, category_id FROM '.TABLE_SHOPS_IN_CATEGORIES_BBS,
            ),
        );

        $query = array(
            'id', 'user_id', 'status', 'moderated',
            'reg1_country', 'reg2_region', 'reg3_city', 'addr_lat',
            ':title' => 'GROUP_CONCAT({prefix-lang}.title SEPARATOR " ") AS title',
            ':descr' => 'GROUP_CONCAT({prefix-lang}.descr SEPARATOR " ") AS descr',
            'svc_fixed',
            ':created' => 'UNIX_TIMESTAMP({prefix}.created) as created',
        );

        $queryPrefix = 's';
        $queryPrefixLang = 'l';
        foreach ($query as $k=>$v) {
            if (is_string($k)) {
                $query[$k] = strtr($v, array(
                    '{prefix}' => $queryPrefix,
                    '{prefix-lang}' => $queryPrefixLang,
                ));
            } else {
                $query[$k] = $queryPrefix.'.'.$v;
            }
        }

        # shopsSourceMain:
        $settings['sources']['shopsSourceMain'] = array(
            ':extends' => 'shopsSource',
            'sql_query_pre' => array(
                'SET CHARACTER_SET_RESULTS='.$settings['charset'],
                'SET NAMES '.$settings['charset'],
                'UPDATE '.$settings['table'].' SET indexed = NOW() WHERE counter_id = '.$this->moduleID(),
            ),
            'sql_query' => ' \\
            SELECT '.join(', ', $query).' \\
            FROM '.TABLE_SHOPS.' s, '.TABLE_SHOPS_LANG.' l \\
            WHERE s.id = l.id AND s.modified<NOW() AND s.id>=$start AND s.id<=$end \\
            GROUP BY s.id'
        );

        # shopsSourceDelta:
        $settings['sources']['shopsSourceDelta'] = array(
            ':extends' => 'shopsSource',
            'sql_query_pre' => array(
                'SET CHARACTER_SET_RESULTS='.$settings['charset'],
                'SET NAMES '.$settings['charset'],
            ),
            'sql_query' => ' \\
            SELECT '.join(', ', $query).' \\
            FROM '.TABLE_SHOPS.' s, '.TABLE_SHOPS_LANG.' l \\
            WHERE s.id = l.id AND s.modified >= (SELECT indexed FROM '.$settings['table'].' WHERE counter_id = '.$this->moduleID().') \\
                AND s.id>=$start AND s.id<=$end \\
            GROUP BY s.id'
        );

        # shopsIndexMain:
        $main = array(
            'source'   => 'shopsSourceMain',
        );
        $this->wordformsConfig($main);
        $settings['indexes']['shopsIndexMain'] = array_merge($settings['indexes']['indexTemplate'], $main);

        # shopsIndexDelta:
        $settings['indexes']['shopsIndexDelta'] = array(
            ':extends' => 'shopsIndexMain',
            'source'   => 'shopsSourceDelta',
        );
    }

    /**
     * Поиск магазинов
     * @param string $query строка поиска
     * @param array $filter дополнительные фильтры
     * @param integer $catID ID категории
     * @param boolean $count только подсчет кол-ва
     * @param integer $limit лимит результатов на страницу
     * @param integer $offset смещение вывода результатов
     * @return array|integer
     */
    public function searchShops($query, $filter, $catID, $count = false, $limit = 1, $offset = 0)
    {
        $options = array('field_weights' => '(title=60,descr=40)');
        $query = $this->prepareSearchQuery($query, $options);

        $bind = array(':q' => $query);
        $where = array();
        $select = array();

        # статус
        if (isset($filter['status'])) {
            $where[] = '`status` = :status';
            $bind[':status'] = (int)$filter['status'];
        }

        # модерация
        if (isset($filter[':mod'])) {
            $where[] = 'moderated > 0';
        }

        # регион
        foreach (array('reg1_country', 'reg2_region', 'reg3_city') as $v) {
            if (isset($filter[$v])) {
                $where[] = $v.' = :'.$v;
                $bind[':'.$v] = (int)$filter[$v];
            }
        }

        # только с координатами
        if (isset($filter[':addr'])) {
            $where[] = 'addr_lat != 0';
        }

        # с учетом категории
        if ($catID > 0) {
            $field = Shops::categoriesEnabled() ? 'in_cat' : 'in_cat_bbs';
            $where[] = '`'.$field.'` = :cat';
            $bind[':cat'] = (int)$catID;
        }

        $opt = array();
        foreach ($options as $k => $v) {
            $opt[] = $k.'='.$v;
        }

        $order = '';
        if (!$count) {
            $select[] = 'svc_fixed, WEIGHT() AS w';
            $order = ' ORDER BY svc_fixed DESC, w DESC';
        }
        # http://sphinxsearch.com/docs/current/sphinxql-select.html
        $prefix = static::prefix();
        $data = $this->exec('
            SELECT id '.(!empty($select) ? ', '.join(', ', $select) : '').'
            FROM '.$prefix.'shopsIndexMain, '.$prefix.'shopsIndexDelta
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
                    return $v['Value'];
                }
            }
        }

        return $data;
    }
}