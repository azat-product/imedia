<?php

class SiteModel_ extends SiteModelBase
{
    public function currencyByFilter(array $filter, $fields = false,  $oneArray = true)
    {
        if (empty($fields)) {
            $filter = $this->prepareFilter($filter, 'C');
            return (int)$this->db->one_data('SELECT count(*) FROM ' . TABLE_CURRENCIES . ' C ' . $filter['where'], $filter['bind']);
        } else {
            $filter[] = $this->db->langAnd(false, 'C', 'L');
            $filter = $this->prepareFilter($filter);
            if ($oneArray) {
                return $this->db->one_array('SELECT ' . join(',', $fields) . ' FROM ' . TABLE_CURRENCIES . ' C, '.TABLE_CURRENCIES_LANG.' L ' .$filter['where'].' LIMIT 1', $filter['bind']);
            } else {
                return $this->db->select('SELECT ' . join(',', $fields) . ' FROM ' . TABLE_CURRENCIES . ' C, '.TABLE_CURRENCIES_LANG.' L ' . $filter['where'], $filter['bind']);
            }
        }
    }

    /**
     * Получение данных счетчиков с группировкой по позиции отображения на странице
     * @return array
     */
    public function countersViewByPosition()
    {
        $data = $this->db->select('SELECT id, code, code_position FROM ' . TABLE_COUNTERS . ' WHERE enabled = 1 ORDER BY num', null, 60);
        if ( ! empty($data)) {
            return \func::array_transparent($data, 'code_position', false);
        }
        return array();
    }
}