<?php

class SEOModel_ extends SEOModelBase
{
    /**
     * Формирование данных о посадочных страницах для файла Sitemap.xml
     * @param boolean $callback
     * @param string $priority приоритетность url
     * @param array $opts доп параметры
     * @return array|callable [['l'=>'url страницы','m'=>'дата последних изменений'],...]
     */
    public function landingpagesSitemapXmlData($callback = true, $priority = '', array $opts = array())
    {
        if (empty($opts['subdomains'])) {
            $opts['subdomains'] = array();
        }

        $filter = array();
        if (isset($opts['filter'])) {
            $filter = $opts['filter'];
        }

        if ( ! isset($filter['enabled'])) {
            $filter['enabled'] = 1;
        }
        $from = ' FROM '.TABLE_LANDING_PAGES.' P ';
        if (isset($filter['withBBSItems'])) {
            $from .= ', '.TABLE_BBS_ITEMS_COUNTERS.' C ';
            $filter[':jcounters'] = array(
                'P.joined_module = :module AND P.joined = C.cat_id AND C.region_id = :region',
                ':module' => 'bbs-cats',
                ':region' => $filter['withBBSItems'],
            );
        }
        unset($filter['withBBSItems']);

        if ( ! empty($filter['notJoined'])) {
            $filter[':not_joined'] = 'P.joined_module IS NULL';
        }
        unset($filter['notJoined']);

        $filter = $this->prepareFilter($filter);

        if ($callback) {
            return function($count = false, callable $callback = null) use ($priority, $from, $filter, $opts) {
                if ($count) {
                    return $this->db->one_data('SELECT COUNT(*) '.$from.$filter['where'], $filter['bind']);
                } else {
                    $languageKey = $this->locale->getDefaultLanguage();
                    $filter['bind'][':format'] = '%Y-%m-%d';
                    $this->db->select_iterator('
                        SELECT landing_uri AS l, DATE_FORMAT(modified, :format) as m
                        '.$from.$filter['where'].'
                        ORDER BY modified DESC', $filter['bind'],
                        function (&$row) use ($languageKey, &$callback, $priority, $opts) {
                            $row['m'] = '';
                            $row['l'] = bff::urlBase(false, $languageKey, $opts['subdomains']).$row['l'];
                            if ( ! empty($priority)) {
                                $row['p'] = $priority;
                            }
                            $callback($row);
                        });
                }
                return false;
            };
        }

        $filter['bind'][':format'] = '%Y-%m-%d';
        $aData = $this->db->select('
                        SELECT landing_uri AS l, DATE_FORMAT(modified, :format) as m
                        '.$from.$filter['where'].'
                        ORDER BY modified DESC', $filter['bind']);
        if (!empty($aData)) {
            $languageKey = $this->locale->getDefaultLanguage();
            foreach ($aData as &$v) {
                $v['m'] = '';
                $v['l'] = bff::urlBase(false, $languageKey, $opts['subdomains']).$v['l'];
                if ( ! empty($priority)) {
                    $v['p'] = $priority;
                }
            } unset ($v);
            return $aData;
        } else {
            return array();
        }
    }

}