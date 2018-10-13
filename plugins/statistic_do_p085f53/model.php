<?php

class Plugin_Statistic_Do_p085f53_model extends Model
{
    const TABLE_STATISTIC = DB_PREFIX.'statistic_p085f53';

    /**
     * Полный пересчет статистики для поля created в таблице
     * @param integer $type тип
     * @param string $table таблица
     * @param array $fields список полей
     * @param callable|bool $isAllowInsert функция для проверки
     */
    public function fillCreated($type, $table, $fields = array(), $isAllowInsert = false)
    {
        if ($isAllowInsert === false){
            $isAllowInsert = function($row) {
                return true;
            };
        }

        $this->db->delete(static::TABLE_STATISTIC, array('type' => $type));

        $this->db->select_iterator('SELECT created FROM '.$table, array(),
            function($row) use($type, &$isAllowInsert) {
                if ( ! $isAllowInsert($row)) return;
                $d = strtotime($row['created']);
                $this->db->exec('INSERT INTO '.static::TABLE_STATISTIC.'(type, dte, value) 
                VALUES(:type, :dte, :value) 
                ON DUPLICATE KEY UPDATE value = value + 1', array(
                    ':type' => $type,
                    ':dte'  => date('Y-m-d', $d),
                    ':value' => 1,
                ));
            });
    }

    /**
     * Сохранение стат данных в БД
     * @param integer $type тип
     * @param integer $date дата unix timestamp
     * @param integer $value значение
     */
    public function save($type, $date, $value)
    {
        $this->db->exec('INSERT INTO '.static::TABLE_STATISTIC.'(type, dte, value) 
                VALUES(:type, :dte, :value) 
                ON DUPLICATE KEY UPDATE value = :value', array(
            ':type' => $type,
            ':dte'  => date('Y-m-d', $date),
            ':value' => $value,
        ));
    }

    /**
     * Инкриментирование статистики (при добавлении )
     * @param integer $type тип
     * @param integer $date дата unix timestamp (0 - сегодня)
     */
    public function increment($type, $date = 0)
    {
        if (empty($date)) {
            $date = time();
        }
        $this->db->exec('INSERT INTO '.static::TABLE_STATISTIC.'(type, dte, value) 
                VALUES(:type, :dte, 1) 
                ON DUPLICATE KEY UPDATE value = value + 1', array(
            ':type' => $type,
            ':dte'  => date('Y-m-d', $date),
        ));
    }

    /**
     * Декриментирование статистики (при удалении (возникли ошибки в процессе добавления) )
     * @param integer $type тип
     * @param integer $date дата unix timestamp (0 - сегодня)
     */
    public function decrement($type, $date = 0)
    {
        if (empty($date)) {
            $date = time();
        }
        $this->db->exec('UPDATE '.static::TABLE_STATISTIC.' 
                SET value = value - 1 
                WHERE type = :type AND dte = :dte', array(
            ':type' => $type,
            ':dte'  => date('Y-m-d', $date),
        ));
    }

    /**
     * Получение данных из таблицы статистики
     * @param integer|array $type тип
     * @param integer $from дата с которой получить unix timestamp
     * @param array $fields поля
     * @param bool $appendNotExist дополнить не существующие нулями
     * @return mixed
     */
    public function data($type, $from, $fields = array(), $appendNotExist = true)
    {
        $demo = config::sysAdmin('plugin.statistic_do.demo.data', false);
        if (is_callable($demo)) {
            return $demo($type, $from);
        }

        if (empty($fields)){
            $fields = array('dte AS d', 'value AS c');
        }

        $to = time();
        $filter = array(
            'type' => $type,
            ':from' => array('dte >= :from AND dte <= :to', ':from' => date('Y-m-d', $from), ':to' => date('Y-m-d', $to)),
        );

        $filter = $this->prepareFilter($filter);
        $data = $this->db->select('
                SELECT '.join(', ', $fields).' 
                FROM '.static::TABLE_STATISTIC.$filter['where'].' 
                ORDER BY d
            ', $filter['bind']);
        if ($appendNotExist) {
            $dte = $from;
            foreach($data as & $v) {
                if ($v['c'] < 0) {
                    $v['c'] = 0;
                    $this->db->delete(static::TABLE_STATISTIC, array('type' => $type, 'dte' => $v['d']));
                }
                $d = (int)date('Ymd', strtotime($v['d']));
                while((int)date('Ymd', $dte) < $d) {
                    $data[] = array(
                        'd' => date('Y-m-d', $dte),
                        'c' => 0,
                    );
                    $dte += 86400;
                }
                if (date('Y-m-d', $dte) == $v['d']) {
                    $dte += 86400;
                }
            } unset($v);
            while($dte <= $to) {
                $data[] = array(
                    'd' => date('Y-m-d', $dte),
                    'c' => 0,
                );
                $dte += 86400;
            }
            usort($data, function($a, $b){
                return strtotime($a['d']) > strtotime($b['d']);
            });
        }

        return $data;
    }

    /**
     * Кол. записей по фильтру в таблице TABLE_SHOP
     * @param array $filter
     * @return int
     */
    public function shopsCount($filter = array())
    {
        $filter = $this->prepareFilter($filter);
        return (int)$this->db->one_data('SELECT COUNT(*) FROM '.TABLE_SHOPS.$filter['where'], $filter['bind']);
    }

    /**
     * Расчет места занимаемого БД
     * @return mixed
     */
    public function dbUsage()
    {
        return (int)$this->db->one_data('SELECT sum(data_length + index_length) AS sum FROM information_schema.tables WHERE TABLE_SCHEMA = :db', [
            ':db' => config::sys('db.name'),
        ]);
    }
}