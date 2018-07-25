<?php namespace bff;

/**
 * Базовый класс модели
 * @abstract
 * @version 0.41
 * @modified 28.mar.2018
 */

use bff\db\Table;

abstract class Model extends \Component
{
    /** @var \Module - ссылка на контроллер(модуль) */
    protected $controller;

    public function __construct($controller)
    {
        parent::init();

        $this->controller = $controller;
    }

    /**
     * Объект ActiveRecord
     * @param string $table название таблицы
     * @return Table
     */
    public function ar($table)
    {
        static $_ar = array();
        if (empty($_ar[$table])) {
            $_ar[$table] = new Table($table, $this->db);
        }

        return $_ar[$table];
    }

    public function getList($sTable, $aCond, $aFields = '*', $sOrder = '', $mLimit = 0, $nOffset = 0)
    {
        if (is_array($aFields)) {
            $aFields = join(', ', $aFields);
        }

        return $this->ar($sTable)->find($aCond, $aFields, $sOrder, $mLimit, $nOffset);
    }

    public function getListCount($sTable, $aCond)
    {
        return $this->ar($sTable)->count($aCond);
    }

    /**
     * Проверка на соответствие оператору сравнения
     * @param mixed $value
     * @return bool
     */
    public static function isOperator($value)
    {
        return (is_string($value) && !empty($value) && in_array(mb_strtoupper($value), array(
            '=', '<', '>', '<=', '>=', '<>', '!=', '<=>', '&', '|', '^', '<<', '>>',
            'IN', 'NOT IN', 'LIKE', 'LIKE BINARY', 'NOT LIKE', 'ILIKE', 'RLIKE', 'REGEXP', 'NOT REGEXP',
        ), true));
    }

    /**
     * Построение условия сравнения для указанной колонки
     * @param string $columnName название колонки
     * @param $value: 'int|string', [1,2,3,...], ['>=', 'value'], ['in', [1,2,3]]
     * @param array $bind @ref
     * @param string $prefix
     * @return string
     */
    public static function condition($columnName, $value, array &$bind = array(), $prefix = '')
    {
        $operator = '=';
        if (is_array($value)) {
            $operator = 'IN';
            if (sizeof($value) == 2 && static::isOperator(current($value))) {
                $operator = mb_strtoupper(array_shift($value));
                $value = reset($value);
            }
            if ($operator === 'IN' || $operator === 'NOT IN') {
                return \bff::database()->prepareIN($prefix.'`'.$columnName.'`', $value, ($operator === 'NOT IN'));
            }
        }
        $bindKey = ':'.$columnName; while (isset($bind[$bindKey])) { $bindKey .= 'A'; }
        $bind[$bindKey] = $value;
        return $prefix . '`'. $columnName . '` '.$operator.' '.$bindKey;
    }

    /**
     * Формируем SQL-фильтр @see static::filter
     * @param array $aFilter параметры
     * @param string|boolean $sPrefix префикс
     * @param array $aBind данные для биндинга
     * @return array
     */
    public function prepareFilter(array $aFilter = array(), $sPrefix = '', array $aBind = array())
    {
        return static::filter($aFilter, $sPrefix, $aBind);
    }

    /**
     * Формируем SQL-фильтр запроса
     * @param array $aFilter параметры, @examples:
     *  $aFilter['status'] = 7; (prefix+)
     *  $aFilter[':status'] = '(status IN (1,2,3))'; (as is)
     *  $aFilter[':status'] = array('(status >= :min OR status <= :max)', ':min'=>1, ':max'=>3); (as is + bind)
     *  $aFilter[] = 'status IS NOT NULL'; (prefix+)
     *  $aFilter[] = array('title LIKE :title', ':title'=>'Super Title'); (as is + bind)
     * @param string|boolean $sPrefix префикс
     * @param array $aBind данные для биндинга
     * @return array ('where'=>string,'bind'=>array|NULL)
     */
    public static function filter(array $aFilter = array(), $sPrefix = '', array $aBind = array())
    {
        $sPrefix = (!empty($sPrefix) ? $sPrefix . '.' : '');
        $sqlWhere = '';
        if (!empty($aFilter)) {
            if (is_array($aFilter)) {
                $sqlWhere = array();
                foreach ($aFilter as $key => $val) {
                    if (is_int($key)) {
                        if (is_string($val)) {
                            ## filter[] = 'status IS NOT NULL';
                            $sqlWhere[] = $sPrefix . $val;
                        } else {
                            if (is_array($val) && sizeof($val) >= 2) { // condition + binds
                                ## filter[] = array('num > :x', ':x'=>9)
                                $sqlWhere[] = array_shift($val);
                                foreach ($val as $k => $v) {
                                    $aBind[$k] = $v;
                                }
                            }
                        }
                    } else if (is_string($key)) {
                        if ($key{0} == ':') {
                            if (is_string($val)) { // condition
                                ## filter[:range] = '(total > 0 OR total < 10)'
                                $sqlWhere[] = $val;
                            } elseif (is_array($val) && sizeof($val) >= 2) { // one condition + binds
                                ## filter[:num] = array('num > :x', ':x'=>9)
                                $sqlWhere[] = array_shift($val);
                                foreach ($val as $k => $v) {
                                    $aBind[$k] = $v;
                                }
                            }
                        } else {
                            ## filter['status'] = 7;
                            ## filter['id'] = [1,2,3]; => id IN (1,2,3)
                            ## filter['id'] = ['>=',5]; => id >= 5
                            ## filter['id'] = ['LIKE','string%']; => id LIKE 'string%'
                            $sqlWhere[] = static::condition($key, $val, $aBind, $sPrefix);
                        }
                    }
                }
                $sqlWhere = 'WHERE ' . join(' AND ', $sqlWhere);
            } elseif (is_string($aFilter)) {
                $sqlWhere = 'WHERE ' . $sPrefix . $aFilter;
            }
        }

        return array('where' => " $sqlWhere ", 'bind' => (!empty($aBind) ? $aBind : null));
    }

    /**
     * Инвертирование поля типа "enabled"
     * @param string $table таблица
     * @param integer $recordID ID записи
     * @param string $fieldToggle название поля "enabled"
     * @param string $fieldID название поля "id"
     * @param bool $withRotation учитывать ротацию по полю "enabled"
     * @return mixed
     */
    public function toggleInt($table, $recordID, $fieldToggle = 'enabled', $fieldID = 'id', $withRotation = false)
    {
        if ($withRotation) {
            $aData = $this->db->one_array("SELECT $fieldToggle FROM $table WHERE $fieldID = :id", array(':id' => $recordID));
            if (empty($aData[$fieldToggle])) {
                $nMax = (int)$this->db->one_data("SELECT MAX($fieldToggle) FROM $table");

                return $this->db->update($table, array($fieldToggle => $nMax + 1), "$fieldID = :id", array(':id' => $recordID));
            } else {
                return $this->db->exec("UPDATE $table SET $fieldToggle = 0 WHERE $fieldID = :id", array(':id' => $recordID));
            }
        } else {
            if (is_array($recordID)) {
                return $this->db->update($table, array("$fieldToggle = (1 - $fieldToggle)"), array($fieldID=>$recordID));
            } else {
                return $this->db->exec("UPDATE $table SET $fieldToggle = (1 - $fieldToggle) WHERE $fieldID = :id", array(':id' => $recordID));
            }
        }
    }

}