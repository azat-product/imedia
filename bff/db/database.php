<?php namespace bff\db;

/**
 * Класс работы с базой данных
 * @version  4.11
 * @modified 29.jun.2018
 * @copyright Tamaranga
 *
 * config::sys:
 * - db.type - тип базы данных, допустимые варианты: 'pgsql', 'mysql'
 * - db.host - host базы данных, например 'localhost'
 * - db.port - порт для подключения к базеданных, например: 5432(pgsql), 3306(mysql)
 * - db.name - название базы данных
 * - db.user - имя пользователя для подключения к базе данных
 * - db.pass - пароль для подключения к базе данных
 * - db.charset - кодировка для работы с базой данных, рекомендуемая: 'UTF8'
 * - db.prefix - 'bff_' - префикс таблиц в базе данных
 */

use bff\utils\Files as Files;

class Database_
{
    public /** @var string Название базы данных */
        $dbname,
        /** @var string Тип базы данных */
        $backend,
        /** @var \PDO объект */
        $pdo,
        /** @var mixed Результат последнего запроса */
        $result;

    protected /** Параметры подключения */
        $dns, $user, $pass, $charset,
        /** @var bool Отслеживаем начало транзакции */
        $trans = false,
        /** @var bool Auto-commit режим */
        $auto = true,
        /** @var array Настройки \PDO */
        $attr = array(),
        /** @var int Кол-во строк, затронутых последним запросом */
        $rows = 0,
        /** @var bool Обрабатывать шифрование */
        $crypt = false,
        /** @var string Ключ шифрования */
        $cryptKey = '',
        /** @var mixed Тег запроса */
        $tag = false;

    /** Настройки статистики */
    protected $stats = array('cache' => array(), 'query' => array(), 'time' => 0);
    public $statEnabled = false;

    /** @var string|bool Тип cache драйвера, false - не использовать кеширование */
    protected $cacheDriver = false;

    /** @var \bff\base\Locale */
    protected $locale;

    /**
     * Конструктор
     */
    public function __construct()
    {
        if (!extension_loaded('pdo')) {
            $this->error('bff\db\Database: PDO extension is not loaded', false);
            exit;
        }

        $this->statEnabled = BFF_DEBUG;

        $this->locale = \bff\DI::get('locale');

        // $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // $this->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        // $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        $this->setAttribute(\PDO::ATTR_PERSISTENT, false);
    }

    /**
     * Деструктор
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Установка параметров подключения
     * @param string|array $config префикс настроек подключения(string) или массив настроек(array)
     * @return boolean
     */
    public function connectionConfig($config)
    {
        if (is_string($config)) {
            $config = \config::sys(array(), array(), $config, true);
        }
        if (empty($config) || !isset($config['type'])) {
            throw new \Exception('bff\db\Database: Connection settings are wrong');
        }

        switch ($config['type']) {
            case 'pgsql':
                $this->dns = 'pgsql:host=' . $config['host'] . ' port=' . $config['port'] . ' dbname=' . $config['name'];
                break;
            case 'mysql':
            default:
                $this->dns = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['name'] . ';charset=' . $config['charset'];
                break;
        }

        $this->user = $config['user'];
        $this->pass = $config['pass'];
        $this->charset = $config['charset'];
        $this->backend = $config['type'];
        $this->dbname = $config['name'];

        if (!empty($config['cache'])) {
            $this->setCacheDriver($config['cache']);
        }

        return true;
    }

    /**
     * Выполнение подлючения к базе данных
     * @return bool
     * @throws \Exception
     */
    public function connect()
    {
        if (isset($this->pdo)) {
            return false;
        }

        try {
            if ($this->backend == 'mysql') {
                # correct rowCount() (since php.version 5.3)
                $this->setAttribute(\PDO::MYSQL_ATTR_FOUND_ROWS, true);
                $this->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '.$this->charset);
            }

            $this->pdo = new \PDO($this->dns, $this->user, $this->pass, $this->attr);
            if ($this->backend == 'pgsql') {
                $this->pdo->exec('SET CLIENT_ENCODING TO ' . $this->charset);
            }
        } catch (\PDOException $e) {
            $sError = _t('system','Ошибка подключения к базе данных [host] - [error]', array('host'=>\Request::host(), 'error'=>$e->getMessage()));
            $this->error($sError, false);
            throw new \Exception($sError, $e->getCode(), $e);
        }
    }

    /**
     * Проверка статуса подключения
     * @return boolean
     */
    public function isConnected()
    {
        return isset($this->pdo);
    }

    /**
     * Завершение подключения
     */
    public function disconnect()
    {
        $this->pdo = null;

        return true;
    }

    /**
     * Поддержка шифрования
     * @param bool|null $cryptEnabled bool - включить/выключить поддержку шифрования; null - получить текущие настройки шифрования
     * @param string $cryptKey - новый ключ шифрования
     * @return string предыдущий ключ шифрования
     */
    public function crypt($cryptEnabled = null, $cryptKey = '')
    {
        $currentCryptKey = $this->cryptKey;
        if (is_bool($cryptEnabled)) {
            $this->crypt = $cryptEnabled;
            $this->cryptKey = $cryptKey;
        }

        return $currentCryptKey;
    }

    /**
     * Шифрование: шифрованние данных
     * @param string $data данные
     * @param bool $base64 использовать base64 обвертку
     * @return string
     */
    public function crypt_encrypt($data, $base64 = true)
    {
        if ( ! is_scalar($data)) {
            if (is_resource($data)) {
                throw new \Exception('bff\db\Database: Unable to encrypt recource data');
            } else {
                $data = serialize($data);
            }
        }
        $result = $this->one_data('SELECT BFF_ENCRYPT(:data)', array(':data'=>$data));
        return ( $base64 ? base64_encode($result) : $result );
    }

    /**
     * Шифрование: шифрование данных с ключем
     * @param string $data данные
     * @param string $cryptKey ключ шифрования
     * @param bool $base64 использовать base64 обвертку
     * @return mixed
     */
    public function crypt_encrypt_key($data, $cryptKey, $base64 = true)
    {
        $prevEnabled = $this->crypt;
        $prevKey = $this->crypt(true, $cryptKey);
        $data = $this->crypt_encrypt($data, $base64);
        $this->crypt($prevEnabled, $prevKey);
        return $data;
    }

    /**
     * Шифрование: расшифровка данных
     * @param string $data данные
     * @param bool $base64 использовать base64 обвертку
     * @return mixed
     */
    public function crypt_decrypt($data, $base64 = true)
    {
        $result = $this->one_data('SELECT BFF_DECRYPT(:data)', array(':data'=>( $base64 ? base64_decode($data) : $data)));
        if (mb_stripos($result, 'a:') === 0) { // serialized Array
            $result = \func::unserialize($result);
        } else if (mb_stripos($result, 'O:') === 0) { // serialized Object
            $result = unserialize($result);
        }
        return $result;
    }

    /**
     * Шифрование: расшифровка данных с ключем
     * @param string $data данные
     * @param string $cryptKey ключ шифрования
     * @param bool $base64 использовать base64 обвертку
     * @return mixed
     */
    public function crypt_decrypt_key($data, $cryptKey, $base64 = true)
    {
        $prevEnabled = $this->crypt;
        $prevKey = $this->crypt(true, $cryptKey);
        $data = $this->crypt_decrypt($data, $base64);
        $this->crypt($prevEnabled, $prevKey);
        return $data;
    }

    /**
     * Логирование ошибки
     * @param string $sMessage текст ошибки
     * @param boolean $bDebugBacktrace получать backtrace
     */
    protected function error($sMessage = '', $bDebugBacktrace = false)
    {
        if ($bDebugBacktrace) {
            $aBacktrace = debug_backtrace(false);
            foreach ($aBacktrace as $v) {
                if (!empty($v['file']) && !empty($v['line']) && !empty($v['class'])) {
                    $sMessage .= "\n<br /> {$v['file']} [{$v['line']}]";
                }
            }
        }
        trigger_error($sMessage, E_USER_ERROR);
    }

    /**
     * Проверка SQLSTATE на наличие ошибок
     * @param mixed $pdoQuery
     * @return bool
     */
    protected function errorCheck($pdoQuery = false, array $opts = array())
    {
        # Проверяем SQLSTATE
        foreach (array($this->pdo, $pdoQuery) as $obj) {
            if ($obj !== false && $obj->errorCode() != \PDO::ERR_NONE) {
                if ($this->trans && $this->auto)
                    $this->rollback();
                $error = $obj->errorInfo();
                $message = '[SQL Error] ( ' . (@$error[0] . '.' . (isset($error[1]) ? $error[1] : '?')) . ' : ' . (isset($error[2]) ? $error[2] : '');
                if (isset($opts['query'])) {
                    $message .= PHP_EOL . print_r($opts['query'], true);
                }
                if (isset($opts['bind'])) {
                    $message .= PHP_EOL . print_r($opts['bind'], true);
                }
                $message .= ' )';
                $this->error($message, true);

                return false;
            }
        }

        return true;
    }

    /**
     * Устанавливаем тег для ближайшего запроса
     * @param string $key ключ тега
     * @param array $data доп. данные
     * @return $this
     */
    public function tag($key, array $data = array())
    {
        $hook = 'db.query.'.$key.'.data';
        if (\bff::hooksAdded($hook)) {
            $data = \bff::filter($hook, $data);
        }
        $this->tag = array('key'=>$key, 'data'=>&$data);
        return $this;
    }

    /**
     * Установка типа cache драйвера, false - выключить кеширование
     * @param string|boolean $driver
     */
    public function setCacheDriver($driver)
    {
        $this->cacheDriver = $driver;
    }

    /**
     * Инициализация Cache
     * @param integer $ttl
     * @return bool|\bff\cache\Cache
     */
    protected function cache($ttl = 0)
    {
        if (empty($this->cacheDriver)) return false;

        $obj = \Cache::singleton('db', $this->cacheDriver);
        if (is_object($obj) && method_exists($obj, 'setLifeTime')) {
            $obj->setLifeTime($ttl);
        }
        return $obj;
    }

    /**
     * Генератор кеш ключа на основе строки
     * @param string $str строка
     * @return string
     */
    protected function cacheKey($str)
    {
        return str_pad(base_convert(sprintf('%u', crc32($str)), 10, 36), 7, '0', STR_PAD_LEFT);
    }

    // ------------------------------------------------------------------------
    // Управление транзакцией

    /**
     * Стартуем SQL транзакцию
     * @param boolean $auto
     */
    public function begin($auto = false)
    {
        if (!$this->pdo) $this->connect();
        $this->pdo->beginTransaction();
        $this->trans = true;
        $this->auto = $auto;
    }

    /**
     * Откатываем SQL транзакцию
     */
    public function rollback()
    {
        if (!$this->pdo) $this->connect();
        $this->pdo->rollback();
        $this->trans = false;
        $this->auto = true;
    }

    /**
     * Коммитим SQL транзакцию
     */
    public function commit()
    {
        if (!$this->pdo) $this->connect();
        $this->pdo->commit();
        $this->trans = false;
        $this->auto = true;
    }

    // ------------------------------------------------------------------------
    // Собираем статистику

    /**
     * Статистика: кол-во выполненных запросов
     * @param bool $bTotalCount true - возвращать общее кол-во, false - возвращать список выполненных запросов
     * @return integer|array
     */
    public function statQueryCnt($totalCount = true)
    {
        if ($totalCount) {
            $total = 0;
            foreach ($this->stats['query'] as $v) {
                $total += $v['n'];
            }

            return $total;
        } else {
            return $this->stats['query'];
        }
    }

    /**
     * Статистика: вывод статистики запросов на экран
     * @param boolean $bDebug использовать debug-метод
     */
    public function statPrint($debug = false)
    {
        if ($debug) {
            debug($this->stats);
        }
        echo 'query cnt: ' . $this->statQueryCnt() . '<br /> total time: ' . number_format($this->stats['time'], 4) . ' ';
    }

    /**
     * Статистика: получение статистики запросов
     * @return array
     */
    public function statGet()
    {
        return $this->stats;
    }

    /**
     * Сбор статистики
     * @param string $query текст запроса к базе
     * @param integer $timeStart время старта выполнения запроса
     * @param boolean $cached результат берется из кеша
     * @param integer $backtraceLevel backtrace-уровень
     */
    protected function stat($query, $timeStart, $cached = false, $backtraceLevel = 2)
    {
        $timeProcessed = (microtime(true) - $timeStart);
        $this->stats['time'] += $timeProcessed;

        if (!isset($this->stats['query'][$query])) {
            $this->stats['query'][$query] = array('n' => 0, 't' => array(), 'tt' => 0, 'c' => 0, 'ctt' => 0);
        }

        $data = &$this->stats['query'][$query];
        $data['n']++;
        if ($cached) {
            $data['c']++;
            $data['ctt'] += $timeProcessed;
            return;
        }
        $data['t'][] = number_format($timeProcessed, 4);
        $data['tt'] += $timeProcessed;

        if (BFF_DEBUG) {
            $aBacktrace = debug_backtrace(false);
            if (isset($aBacktrace[$backtraceLevel]['file'])) {
                $data['file'] = $aBacktrace[$backtraceLevel]['file'];
                $data['line'] = $aBacktrace[$backtraceLevel]['line'];
            }
        }
    }

    // ------------------------------------------------------------------------
    // Обрабатываем SQL запросы

    /**
     * Обрабатываем (выполняем) SQL запросы
     * @param string|array $query запросы
     * @param array $bind аргументы
     * @param int $ttl срок годности кеш версии в секундах
     * @param integer|boolean $fetchType \PDO::FETCH_NUM, \PDO::FETCH_ASSOC, \PDO::FETCH_BOTH, \PDO::FETCH_OBJ
     * @param string $fetchFunc
     * @param array $prepareOptions
     * @return array
     */
    public function exec($query, array $bind = null, $ttl = 0, $fetchType = false, $fetchFunc = 'fetchAll', array $prepareOptions = array())
    {
        if (!$this->pdo) $this->connect();

        $batch = is_array($query);
        if ($batch) {
            if (!$this->trans && $this->auto)
                $this->begin(true);
            if (is_null($bind)) {
                $bind = array();
                for ($i = 0; $i < count($query); $i++) {
                    $bind[] = null;
                }
            }
        } else {
            $query = array($query);
            $bind = array($bind);
        }

        foreach (array_combine($query, $bind) as $cmd => $arg) {
            if ($this->tag !== false) {
                $cmd = \bff::filter('db.query.'.$this->tag['key'], $cmd, array('bind'=>&$arg,'data'=>&$this->tag['data']));
            }
            if ($this->crypt) {
                $cmd = preg_replace('/BFF\_(EN|DE)CRYPT\(([^\)]+)\)/xisu', 'AES_${1}CRYPT(${2}, :bff_crypt_key)', $cmd, -1, $crypts);
                if ($crypts > 0 && !isset($arg[':bff_crypt_key'])) $arg[':bff_crypt_key'] = $this->cryptKey;
            }

            if ($this->statEnabled) {
                $time = microtime(true);
            }

            if ($ttl && ($cache = $this->cache($ttl))) {
                $cacheKey = $this->cacheKey($cmd . var_export($arg, true));
                if (($this->result = $cache->get($cacheKey)) !== false) {
                    if ($this->statEnabled) {
                        $this->stat($cmd, $time, true, 3);
                    }
                    continue;
                }
            }

            if (is_null($arg))
                $query = $this->pdo->query($cmd);
            else {
                $query = $this->pdo->prepare($cmd, $prepareOptions);
                if (is_object($query)) {
                    foreach ($arg as $key => $value) {
                        if (!(is_array($value) ?
                            $query->bindValue($key, $value[0], $value[1]) :
                            $query->bindValue($key, $value, $this->type($value)))
                        ) {
                            break;
                        }
                    }
                    $query->execute();
                }
            }
            # Проверяем SQLSTATE
            if (!$this->errorCheck($query, array('query'=>$cmd,'bind'=>$arg))) {
                if ($this->tag !== false) { $this->tag = false; }
                return false;
            }

            if ($fetchType !== false || preg_match('/^\s*(?:SELECT|PRAGMA|SHOW|EXPLAIN)\s/i', $cmd)) {
                if ($fetchFunc !== false) {
                    $this->result = $query->$fetchFunc($fetchType);
                    $this->rows = $query->rowCount();
                } else {
                    $this->result = $query;
                }
            } else {
                $this->rows = $this->result = $query->rowCount();
            }
            if ($ttl && $cache && $fetchFunc !== false) {
                $cache->set($cacheKey, $this->result, $ttl);
            }
            # Считаем кол-во выполненных запросов
            if ($this->statEnabled) {
                $this->stat($cmd, $time, false, 3);
            }
        }
        if ($batch || $this->trans && $this->auto)
            $this->commit();

        if ($this->tag !== false) { $this->tag = false; }

        return $this->result;
    }

    /**
     * Выполняем INSERT запрос
     * @param string $table название таблицы
     * @param array $fields массив параметров для вставки
     * @param string|array|boolean $returnID возвращать ID вновь добавленной записи (pgsql: несколько данных)
     * @param array $bind доп. параметры запроса для bind'a
     * @param array $cryptKeys ключи параметров, требующие шифрования
     * @return integer ID добавленной записи
     */
    public function insert($table, array $fields = array(), $returnID = 'id', $bind = array(), array $cryptKeys = array())
    {
        $f = $v = '';
        foreach ($fields as $field => $val) {
            $f .= ($f ? ',' : '') . $field;
            $v .= ($v ? ',' : '') . ($this->crypt && in_array($field, $cryptKeys) ? 'BFF_ENCRYPT(:' . $field . ')' : ':' . $field);
            $bind[':' . $field] = array($val, $this->type($val));
        }
        if (!$bind) return false;
        $query = 'INSERT INTO ' . $table . ' (' . $f . ') VALUES (' . $v . ')';
        if ($returnID !== false) {
            if ($this->isPgSQL()) {
                # RETURNING expression (pgsql)
                if (is_string($returnID)) {
                    $query .= ' RETURNING ' . $returnID;
                    list($fetchType, $fetchFunc) = array(0, 'fetchColumn'); // one_data
                } elseif (is_array($returnID)) {
                    $query .= ' RETURNING ' . join(', ', $returnID);
                    list($fetchType, $fetchFunc) = array(\PDO::FETCH_ASSOC, 'fetch'); // one_array
                }

                return $this->exec($query, $bind, 0, $fetchType, $fetchFunc);
            } else {
                # lastInsertId (mysql, ...)
                $this->exec($query, $bind);

                return $this->insert_id($table, $returnID);
            }
        } else {
            return $this->exec($query, $bind);
        }
    }

    /**
     * Получаем ID последней добавленной записи
     * @param string $table название таблицы
     * @param string $columnName название поля ID
     * @param string $sequencePostfix окончание в названии последовательности(sequence) (tableName_FieldName)
     * @return integer
     */
    public function insert_id($table = '', $columnName = 'id', $sequencePostfix = '_seq')
    {
        $result = (int)$this->pdo->lastInsertId($table . ($columnName ? '_' . $columnName : '') . $sequencePostfix);

        return (empty($result) ? 0 : $result);
    }

    /**
     * Выполняем UPDATE запрос
     * @param string $table название таблицы
     * @param array $fields массив параметров для обновления
     * @param array|string|integer $conditions условия WHERE
     * @param array $bind доп. параметры для подстановки в запрос
     * @param array $cryptKeys ключи параметров, требующие шифрования
     * @param array $options:
     *     string|array 'orderBy' порядок сортировки
     *     string|array|integer 'limit' условие LIMIT
     *     boolean 'returnQuery' возвращать запрос без выполнения
     * @return boolean|mixed
     */
    public function update($table, array $fields = array(), $conditions = '', $bind = array(), array $cryptKeys = array(), array $options = array())
    {
        $set = '';
        foreach ($fields as $field => $val) {
            if (is_int($field) && is_string($val)) {
                $set .= ($set ? ',' : '') . $val;
            } else {
                $set .= ($set ? ',' : '') . $field . '=' . ($this->crypt && in_array($field, $cryptKeys) ? 'BFF_ENCRYPT(:' . $field . ')' : ':' . $field);
                $bind[':' . $field] = array($val, $this->type($val));
            }
        }
        if ($set) {
            if (is_numeric($conditions)) {
                $bind[':id'] = array($conditions, $this->type($conditions));
                $conditions = 'id=:id'; # ID condition
            } else if (is_array($conditions)) {
                $where = array();
                foreach ($conditions as $field => $val) {
                    if (is_array($val)) {
                        $where[] = \Model::condition($field, $val, $bind);
                    } else {
                        if (is_int($field) && is_string($val)) {
                            $where[] = $val; # special conditions (multi conditions)
                        } else {
                            $bindKey = ':' . $field;
                            while (isset($bind[$bindKey])) $bindKey .= 'A'; # исключаем повторение с bind-данными из $fields
                            $where[] = $field . '=' . $bindKey; # field condition
                            $bind[$bindKey] = array($val, $this->type($val));
                        }
                    }
                }
                $conditions = join(' AND ', $where);
            } else {
                # string - raw condition
            }

            # order by
            if ( ! empty($options['orderBy'])) {
                $orderBy = $options['orderBy'];
                if (is_array($orderBy)) $orderBy = join(', ', $orderBy);
                $orderBy = ' ORDER BY '.strval($orderBy);
            } else {
                $orderBy = '';
            }
            # limit
            if ( ! empty($options['limit'])) {
                $limit = $options['limit'];
                if (is_array($limit)) {
                    if ( ! empty($limit)) {
                        $limit = $this->prepareLimit(false, reset($limit));
                    } else {
                        $limit = '';
                    }
                } else if (is_numeric($limit)) {
                    $limit = $this->prepareLimit(false, strval($limit));
                }
            }
            $query = 'UPDATE ' . $table . ' SET ' . $set . ($conditions ? (' WHERE ' . $conditions) : '') . $orderBy . (!empty($limit) ? ' '.$limit : '');
            if ( ! empty($options['returnQuery'])) {
                return array(
                    'query' => $query,
                    'bind'  => $bind,
                );
            }

            return $this->exec($query, $bind);
        }

        return false;
    }

    /**
     * Выполняем DELETE запрос
     * @param string $table название таблицы
     * @param array|integer $conditions условия WHERE или ID (в таком случае WHERE id = ID)
     * @param array $bind доп. параметры запроса для bind'a
     * @return boolean
     */
    public function delete($table, $conditions = array(), array $bind = array())
    {
        $where = '';
        if (is_int($conditions)) {
            $where .= 'id=:id'; # ID condition
            $bind[':id'] = array($conditions, $this->type($conditions));
        } else if (is_array($conditions)) {
            foreach ($conditions as $field => $val) {
                if (is_array($val)) {
                    $where .= ($where ? ' AND ' : '') . $this->prepareIN($field, $val); # IN - only integers
                } else {
                    if (is_int($field) && is_string($val)) {
                        $where .= ($where ? ' AND ' : '') . $val; # special conditions (multi conditions)
                    } else {
                        $where .= ($where ? ' AND ' : '') . $field . '=:' . $field; # field condition
                        $bind[':' . $field] = array($val, $this->type($val));
                    }
                }
            }
        }
        if ($where) {
            return $this->exec('DELETE FROM ' . $table . ' WHERE ' . $where, $bind);
        }

        return false;
    }

    /**
     * Получаем несколько строк из таблицы
     * @param string $query текст запроса
     * @param array|null $bindParams параметры запроса или null
     * @param integer $ttl ttl
     * @param integer $fetchType \PDO::FETCH_NUM, \PDO::FETCH_ASSOC, \PDO::FETCH_BOTH, \PDO::FETCH_OBJ
     * @param string $fetchFunc
     * @return mixed @see self::exec @method
     */
    public function select($query, $bindParams = null, $ttl = 0, $fetchType = \PDO::FETCH_ASSOC, $fetchFunc = 'fetchAll')
    {
        return $this->exec($query, $bindParams, $ttl, $fetchType, $fetchFunc);
    }

    /**
     * Получаем несколько строк из таблицы построчно, с последующей обработкой в callback-функции
     * @param string $query текст запроса
     * @param array $bindParams параметры запроса
     * @param callable $callback функция обработчик
     * @param array|integer $options [
     *     boolean 'exclusive' => true Использовать новое подключение к БД
     *     integer 'fetchType' => \PDO::FETCH_ASSOC | \PDO::FETCH_NUM | \PDO::FETCH_BOTH | \PDO::FETCH_OBJ
     * ]
     */
    public function select_iterator($query, array $bindParams, callable $callback, $options = array())
    {
        if (is_numeric($options)) {
            $options = array('fetchType'=>$options);
        }
        \func::array_defaults($options, array(
            'fetchType' => \PDO::FETCH_ASSOC,
            'exclusive' => true,
        ));

        $exclusive = ! empty($options['exclusive']);
        $fetchType = $options['fetchType'];

        if ($exclusive) {
            $self = \bff\DI::get('database_factory');
            $self->connectionConfig('db');
            $self->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $self->connect();
        } else {
            $self = $this;
            $self->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        $stmt = $self->exec($query, $bindParams, 0, $fetchType, false/*PDOStatement*/, array(
            \PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL
        ));
        if ($stmt !== false) {
            while ($row = $stmt->fetch($fetchType, \PDO::FETCH_ORI_NEXT)) {
                if ($callback($row) === false) {
                    break;
                }
            }
            $stmt->closeCursor();
            $stmt = null;
        }

        if ($exclusive) {
            $self->disconnect();
        } else {
            $self->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /**
     * Получаем несколько строк из таблицы, с последующей группировкой по столбцу
     * @param string $query текст запроса
     * @param string $key поле для ключа
     * @param array|null $bindParams параметры запроса или null
     * @param integer $ttl ttl
     * @return mixed @see self::exec @method
     */
    public function select_key($query, $key = 'id', $bindParams = null, $ttl = 0)
    {
        $tmp = $this->select($query, $bindParams, $ttl);
        if (empty($key)) return $tmp;
        $data = array();
        if (!empty($tmp)) {
            foreach ($tmp as $d) {
                $data[$d[$key]] = $d;
            }
            unset($tmp);
        }

        return $data;
    }

    /**
     * Получаем один столбец из таблицы
     * @param string $query текст запроса
     * @param array|null $bindParams параметры запроса или null
     * @param integer $ttl ttl
     * @return mixed @see self::exec @method
     */
    public function select_one_column($query, $bindParams = null, $ttl = 0)
    {
        return $this->exec($query, $bindParams, $ttl, \PDO::FETCH_COLUMN, 'fetchAll');
    }

    /**
     * EX: Формирование SELECT запроса из одной таблицы
     * @param string $table название таблицы
     * @param array $fields список столбцов
     * @param string|array $where условия WHERE
     * @param array $options:
     *     string 'prefix' префикс таблицы
     *     string|array 'groupBy' порядок группировки
     *     string|array 'orderBy' порядок сортировки
     *     string|array|integer 'limit' условие LIMIT
     *     array 'cryptKeys' шифруемые столбцы
     * @return array
     */
    public function select_prepare($table, array $fields = array(), $where = array(), array $options = array())
    {
        $tablePrefix = (!empty($options['prefix']) ? $options['prefix'] : '');

        # cryptKeys:
        $cryptKeys = (!empty($options['cryptKeys']) ? $options['cryptKeys'] : array());

        # select
        $select = array();
        if (sizeof($fields) === 1 && current($fields) === '*' && !empty($cryptKeys)) {
            $fields = array_merge($fields, $cryptKeys);
        }
        foreach ($fields as $field) {
            $select[] = ($this->crypt && in_array($field, $cryptKeys) && $field!='*' ? 'BFF_DECRYPT(' . $field . ') as '.$field : $field);
        }
        $select = ($select ? join(', ', $select) : '*');
        # where
        $filter = \Model::filter((is_array($where) ? $where : array($where)), $tablePrefix);
        # group by
        if ( ! empty($options['groupBy'])) {
            $groupBy = $options['groupBy'];
            if (is_array($groupBy)) {
                $groupBy = join(', ', $groupBy);
            }
            $groupBy = ' GROUP BY '.$groupBy;
        } else {
            $groupBy = '';
        }
        # order by
        if ( ! empty($options['orderBy'])) {
            $orderBy = $options['orderBy'];
            if (is_array($orderBy)) $orderBy = join(', ', $orderBy);
            $orderBy = ' ORDER BY '.strval($orderBy);
        } else {
            $orderBy = '';
        }
        # limit
        if ( ! empty($options['limit'])) {
            $limit = $options['limit'];
            if (is_array($limit)) {
                if (sizeof($limit) == 1) {
                    $limit = $this->prepareLimit(0, reset($limit));
                } else if (sizeof($limit) > 1) {
                    $limit = $this->prepareLimit(reset($limit), next($limit));
                } else {
                    $limit = '';
                }
            } else if (is_numeric($limit)) {
                $limit = $this->prepareLimit(0, strval($limit));
            }
        }
        return array(
            'query' => 'SELECT '.$select.' FROM ' . $table . ' '.$tablePrefix.' ' . $filter['where'] . $groupBy . $orderBy . (!empty($limit) ? ' '.$limit : ''),
            'bind'  => $filter['bind'],
        );
    }

    /**
     * EX: Получаем несколько строк из таблицы, с последующей группировкой по столбцу
     * @param string $table название таблицы
     * @param array $fields список столбцов
     * @param string|array $where условия WHERE
     * @param string|array $orderBy порядок сортировки
     * @param string $limit условие LIMIT
     * @param array $cryptKeys шифруемые столбцы
     * @param integer $ttl ttl
     * @return mixed @see self::exec @method
     */
    public function select_rows($table, array $fields = array(), $where = array(), $orderBy = '', $limit = '', array $cryptKeys = array(), $ttl = 0)
    {
        $select = $this->select_prepare($table, $fields, $where, array('orderBy'=>$orderBy,'limit'=>$limit,'cryptKeys'=>$cryptKeys));
        return $this->exec($select['query'], $select['bind'], $ttl, \PDO::FETCH_ASSOC, 'fetchAll');
    }

    /**
     * EX: Получаем все строки из таблицы пошагово, с последующей обработкой в callback-функции
     * @param string $table название таблицы
     * @param array $fields список столбцов
     * @param array $where условия WHERE
     * @param string|array $orderBy порядок сортировки
     * @param array $cryptKeys шифруемые столбцы
     * @param callback $callback функция обработчик
     * @param integer $step кол-во обрабатываемых записей за шаг
     * @return integer общее кол-во записей в таблице
     */
    public function select_rows_chunked($table, array $fields = array(), array $where = array(), $orderBy = '', array $cryptKeys = array(), $callback = 0, $step = 100)
    {
        $total = $this->select_rows_count($table, $where);
        if ($total > 0 && is_callable($callback)) {
            if ($total < $step) {
                $rows = $this->select_rows($table, $fields, $where, $orderBy, '', $cryptKeys);
                if ( ! empty($rows)) {
                    $callback($rows);
                }
            } else {
                for ($i=0;$i<=$total;$i+=$step) {
                    $rows = $this->select_rows($table, $fields, $where, $orderBy, array($i, $step), $cryptKeys);
                    if ( ! empty($rows)) {
                        if ($callback($rows) === false) {
                            break;
                        }
                    }
                }
            }
        }
        return $total;
    }

    /**
     * EX: Получаем все строки из таблицы построково, с последующей обработкой в callback-функции
     * @param string $table название таблицы
     * @param array $fields список столбцов
     * @param array $where условия WHERE
     * @param string|array $orderBy порядок сортировки
     * @param string $limit условие LIMIT
     * @param array $cryptKeys шифруемые столбцы
     * @param callback $callback функция обработчик
     */
    public function select_rows_iterator($table, array $fields = array(), array $where = array(), $orderBy = '', $limit = '', array $cryptKeys = array(), $callback = 0)
    {
        if (is_callable($callback)) {
            $select = $this->select_prepare($table, $fields, $where, array('orderBy'=>$orderBy,'limit'=>$limit,'cryptKeys'=>$cryptKeys));
            $this->select_iterator($select['query'], $select['bind'], $callback);
        }
    }

    /**
     * EX: Получаем несколько строк из таблицы, с последующей группировкой по столбцу
     * @param string $table название таблицы
     * @param string $key столбец, по которому выполняется группировка
     * @param array $fields список столбцов
     * @param string|array $where условия WHERE
     * @param string|array $orderBy порядок сортировки
     * @param string $limit условие LIMIT
     * @param array $cryptKeys шифруемые столбцы
     * @param integer $ttl ttl
     * @return mixed @see self::select_key @method
     */
    public function select_rows_key($table, $key, array $fields = array(), $where = array(), $orderBy = '', $limit = '', array $cryptKeys = array(), $ttl = 0)
    {
        $select = $this->select_prepare($table, $fields, $where, array('orderBy'=>$orderBy,'limit'=>$limit,'cryptKeys'=>$cryptKeys));
        return $this->select_key($select['query'], $key, $select['bind'], $ttl);
    }

    /**
     * EX: Получаем один столбец из таблицы
     * @param string $table название таблицы
     * @param string $field название столбца
     * @param string|array $where условия WHERE
     * @param string|array $orderBy порядок сортировки
     * @param string $limit условие LIMIT
     * @param boolean $cryptedField столбец шифруется
     * @param integer $ttl ttl
     * @return mixed @see self::exec @method
     */
    public function select_rows_column($table, $field, $where = array(), $orderBy = '', $limit = '', $cryptedField = false, $ttl = 0)
    {
        $field = ( is_array($field) ? array_slice($field,0,1) : array(strval($field)) );
        $select = $this->select_prepare($table, $field, $where, array('orderBy'=>$orderBy,'limit'=>$limit,'cryptKeys'=>($cryptedField ? $field : array())));
        return $this->exec($select['query'], $select['bind'], $ttl, \PDO::FETCH_COLUMN, 'fetchAll');
    }

    /**
     * EX: Получаем кол-во записей в таблице
     * @param string $table название таблицы
     * @param array $where условия WHERE
     * @param array|integer $options доп. параметры или ttl
     * @return mixed @see self::exec @method
     */
    public function select_rows_count($table, array $where = array(), $options = 0)
    {
        if (is_numeric($options)) {
            $options = array('ttl'=>intval($options));
        } else if (!isset($options['ttl'])) {
            $options['ttl'] = 0;
        }

        $select = $this->select_prepare($table, array('COUNT(*)'), $where, $options);
        if (!empty($options['returnQuery'])) {
            return $select;
        }

        return (int)$this->exec($select['query'], $select['bind'], $options['ttl'], 0, 'fetchColumn');
    }

    /**
     * EX: Получаем строку из таблицы
     * @param string $table название таблицы
     * @param array $fields список столбцов
     * @param string|array $where условия WHERE
     * @param string|array $orderBy порядок сортировки
     * @param string $limit условие LIMIT
     * @param array $cryptKeys шифруемые столбцы
     * @param integer $ttl ttl
     * @return mixed @see self::exec @method
     */
    public function select_row($table, array $fields = array(), $where = array(), $orderBy = '', $limit = '', array $cryptKeys = array(), $ttl = 0)
    {
        $select = $this->select_prepare($table, $fields, $where, array('orderBy'=>$orderBy,'limit'=>$limit,'cryptKeys'=>$cryptKeys));
        return $this->exec($select['query'], $select['bind'], $ttl, \PDO::FETCH_ASSOC, 'fetch');
    }

    /**
     * EX: Получаем данные из таблицы из одного столбца
     * @param string $table название таблицы
     * @param string $field название столбца
     * @param string|array $where условия WHERE
     * @param string|array $orderBy порядок сортировки
     * @param string $limit условие LIMIT
     * @param boolean $cryptedField столбец шифруется
     * @param integer $ttl ttl
     * @return mixed @see self::exec @method
     */
    public function select_data($table, $field, $where = array(), $orderBy = '', $limit = '', $cryptedField = false, $ttl = 0)
    {
        $field = ( is_array($field) ? array_slice($field,0,1) : array(strval($field)) );
        $select = $this->select_prepare($table, $field, $where, array('orderBy'=>$orderBy,'limit'=>$limit,'cryptKeys'=>($cryptedField ? $field : array())));
        return $this->exec($select['query'], $select['bind'], $ttl, 0, 'fetchColumn');
    }

    /**
     * Получаем данные из таблицы из одного поля
     * @param string $query текст запроса
     * @param array $bindParams параметры запроса
     * @param integer $ttl ttl
     * @return mixed @see self::exec @method
     */
    public function one_data($query, $bindParams = null, $ttl = 0)
    {
        return $this->exec($query, $bindParams, $ttl, 0, 'fetchColumn');
    }

    /**
     * Получаем строку из таблицы
     * @param string $query текст запроса
     * @param array|null $bindParams параметры подставляемые в запрос или null
     * @param integer $ttl ttl
     * @param integer $fetchType \PDO::FETCH_NUM; \PDO::FETCH_ASSOC; \PDO::FETCH_BOTH; \PDO::FETCH_OBJ
     * @return mixed @see self::exec @method
     */
    public function one_array($query, $bindParams = null, $ttl = 0, $fetchType = \PDO::FETCH_ASSOC)
    {
        return $this->exec($query, $bindParams, $ttl, $fetchType, 'fetch');
    }

    /**
     * Возвращаем кол-во строк затронутых последним запросом
     * @return integer
     */
    public function rows()
    {
        return $this->rows;
    }

    /**
     * Возвращаем тип данных \PDO, определенный по значению
     * @param mixed $val значение
     * @return integer
     */
    public function type($val)
    {
        foreach (
            array(
                'null'   => 'NULL',
                'bool'   => 'BOOL',
                'string' => 'STR',
                'int'    => 'INT',
                'float'  => 'STR'
            ) as $php => $pdo)
            if (call_user_func('is_' . $php, $val))
                return constant('PDO::PARAM_' . $pdo);

        return \PDO::PARAM_LOB;
    }

    /**
     * Получаем схему таблицы
     * @param string $table имя таблицы
     * @param integer $ttl ttl
     * @return array
     */
    public function schema($table, $ttl = 0)
    {
        $cmd = array(
            'mysql' => array(
                'SHOW columns FROM `' . $this->dbname . '`.' . $table . ';',
                'Field',
                'Key',
                'PRI',
                'Type'
            ),
            'mssql|sybase|dblib|pgsql|ibm|odbc' => array(
                'SELECT c.column_name AS field,' .
                'c.data_type AS type,t.constraint_type AS pkey ' .
                'FROM information_schema.columns AS c ' .
                'LEFT OUTER JOIN ' .
                'information_schema.key_column_usage AS k ON ' .
                'c.table_name=k.table_name AND ' .
                'c.column_name=k.column_name ' .
                ($this->dbname ?
                    ('AND ' .
                        (preg_match('/^pgsql$/', $this->backend) ?
                            'c.table_catalog=k.table_catalog' :
                            'c.table_schema=k.table_schema') . ' ') : '') .
                'LEFT OUTER JOIN ' .
                'information_schema.table_constraints AS t ON ' .
                'k.table_name=t.table_name AND ' .
                'k.constraint_name=t.constraint_name ' .
                ($this->dbname ?
                    ('AND ' .
                        (preg_match('/pgsql/', $this->backend) ?
                            'k.table_catalog=t.table_catalog' :
                            'k.table_schema=t.table_schema') . ' ') : '') .
                'WHERE ' .
                'c.table_name=\'' . $table . '\'' .
                ($this->dbname ?
                    ('AND ' .
                        (preg_match('/pgsql/', $this->backend) ?
                            'c.table_catalog' : 'c.table_schema') .
                        '=\'' . $this->dbname . '\'') : '') .
                ';',
                'field',
                'pkey',
                'PRIMARY KEY',
                'type'
            ),
            'sqlite2?' => array(
                'PRAGMA table_info(' . $table . ');',
                'name',
                'pk',
                1,
                'type'
            ),
        );
        $match = false;
        foreach ($cmd as $backend => $val)
            if (preg_match('/' . $backend . '/', $this->backend)) {
                $match = true;
                break;
            }
        if (!$match) {
            $this->error(_t('system','Данный тип баз данных не поддерживается'), false);

            return false;
        }
        $result = $this->exec($val[0], null, $ttl);
        if (!$result) {
            $this->error(_t('system','Не удалось получить схему для данной таблицы "[table]"', array('table'=>$table)), false);

            return false;
        }

        return array(
            'result' => $result,
            'field'  => $val[1],
            'pkname' => $val[2],
            'pkval'  => $val[3],
            'type'   => $val[4]
        );
    }

    /**
     * Создаем Table объект
     * @param string $table Название таблицы
     * @return Table объект
     */
    public function table($table)
    {
        return new Table($table, $this);
    }

    // --------------------------------------------------------------

    /**
     * Проверка существования таблицы
     * @param string $table название таблицы
     * @return boolean
     */
    public function isTable($table)
    {
        switch ($this->getDriverName()) {
            case 'pgsql':
                $result = $this->select_one_column('SELECT table_name FROM information_schema.tables WHERE table_schema = :shema',
                    array(':shema' => 'public')
                );
                foreach ($result as $v) {
                    if ($v == $table) return true;
                }
                break;
            case 'mysqli':
            case 'mysql':
                $result = $this->one_data('SHOW TABLES LIKE :table', array(':table' => $table));
                if (!empty($result)) return true;
                break;
        }
        return false;
    }

    /**
     * Получаем текущую дату/время в SQL формате
     * @param boolean $quote выполнять квотирование
     * @return string
     */
    public function getNOW($quote = true)
    {
        static $date;
        if (!isset($date)) {
            $date = date('Y-m-d H:i:s');
        }

        return ($quote ? $this->str2sql($date) : $date);
    }

    /**
     * Получаем текущую дату/время в SQL формате
     * @param boolean $quote выполнять квотирование
     * @return string
     */
    public function now($quote = false)
    {
        return $this->getNOW($quote);
    }

    /**
     * Выполняем квотирование строки
     * @param string $value
     * @return string
     */
    public function str2sql($value)
    {
        return $this->pdo->quote($value);
    }

    /**
     * Подготовка параметров UPDATE запроса (@deprecated, используем self::update)
     * @param string $queryData @ref итоговый sql запрос
     * @param array $data данные
     * @param array $keysNotPrepare названия полей не требующих sql:prepare
     * @return boolean
     */
    public function prepareUpdateQuery(&$queryData, $data, $keysNotPrepare = array())
    {
        if (empty($data)) return '';

        $queryData = array();
        foreach ($data as $key => $value) {
            if (!empty($value) || $value == 0) {
                $queryData[] = $key . ' = ' . (!empty($keysNotPrepare) && ($keysNotPrepare === true || in_array($key, $keysNotPrepare)) ? $value : $this->str2sql($value)) . ' ';
            }
        }
        $queryData = join(', ', $queryData);

        return !empty($queryData);
    }

    /**
     * Подготовка параметров INSERT запроса (@deprecated, используем self::insert)
     * @param string $fields @ref поля базы данных
     * @param string $values @ref данные в виде sql запроса
     * @param array $data данные
     * @return boolean
     */
    public function prepareInsertQuery(&$fields, &$values, $data)
    {
        $fields = array();
        $values = array();

        foreach ($data as $key => $value) {
            if ((!empty($value) || $value == 0) && !is_array($value)) {
                $fields[] = $key;
                $values[] = $this->str2sql($value);
            }
        }

        $fields = join(', ', $fields);
        $values = join(', ', $values);

        return (!empty($fields) && !empty($values));
    }

    /**
     * Подготовка LIMIT
     * @param integer|boolean $offset
     * @param integer $limit
     * @return string SQL
     */
    public function prepareLimit($offset, $limit)
    {
        switch ($this->getDriverName()) {
            case 'pgsql':
                return " LIMIT " . (empty($limit) ? 'ALL' : $limit) . ($offset !== false ? " OFFSET $offset " : '');
                break;
            case 'mysqli':
            case 'mysql':
            default:
                return ' LIMIT '. ($offset !== false ? $offset . ', ' : '') . (empty($limit) ? '18446744073709551615' : $limit) . " ";
                break;
        }
    }

    /**
     * Строит IN или NOT IN sql строку сравнения
     * @param string $sField название колонки для сравнения
     * @param array $aValues массив значений - разрешенных (IN) или запрещенных (NOT IN)
     * @param boolean $bNot true: NOT IN (), false: IN ()
     * @param boolean $bAllowEmptySet true - разрешить массив $aValues быть пустым, эта функция вернет 1=1 или 1=0
     * @param boolean $bIntegers приводит значения к integer
     * @return mixed
     */
    public function prepareIN($sField, $aValues, $bNot = false, $bAllowEmptySet = true, $bIntegers = true)
    {
        if (!sizeof($aValues)) {
            if (!$bAllowEmptySet) {
                $this->error(_t('system','No values specified for SQL IN comparison'), true);
            } else {
                return (($bNot) ? '1=1' : '1=0');
            }
        }

        if (!is_array($aValues)) {
            $aValues = array($aValues);
        }

        if (sizeof($aValues) == 1) {
            @reset($aValues);

            return $sField . ($bNot ? ' <> ' : ' = ') . ($bIntegers ? intval(current($aValues)) : $this->str2sql(current($aValues)));
        } else {
            if ($bIntegers) {
                $aValues = array_map('intval', $aValues);
            } else {
                $aValues = array_map(array($this, 'str2sql'), $aValues);
            }

            return $sField . ($bNot ? ' NOT IN ' : ' IN ') . '(' . implode(',', $aValues) . ')';
        }
    }

    /**
     * Выполняет множественную вставку
     * @param string $table таблица
     * @param array $data многомерный массив для вставки
     * @param array $cryptKeys ключи параметров, требующие шифрования
     * @return boolean false - если запрос не выполнялся.
     */
    public function multiInsert($table, array $data, array $cryptKeys = array())
    {
        if (empty($data)) return false;

        $aResult = array();
        $bind = array();
        $i = 1;
        foreach ($data as $data2) {
            # Если массив не многомерный выполняем нормальный INSERT запрос
            if (!is_array($data2)) {
                return $this->insert($table, $data, false, array(), $cryptKeys);
            }

            $aPlaceholders = array();
            foreach ($data2 as $key => $var) {
                $key1 = ":$key$i"; #:name1
                $aPlaceholders[] = ($this->crypt && in_array($key, $cryptKeys) ? 'BFF_ENCRYPT(' . $key1 . ')' : $key1);
                $bind[$key1] = array($var, $this->type($var));
                $i++;
            }

            $aResult[] = '(' . join(',', $aPlaceholders) . ')';
        }

        if (empty($aResult)) return false;

        $first = reset($data);
        return $this->exec('INSERT INTO ' . $table . ' (' . join(',', array_keys($first)) . ')
                            VALUES ' . join(', ', $aResult), $bind
        );
    }

    /**
     * Добавление столбца в таблицу, если столбца не существует
     * @param string $table имя таблицы
     * @param string $name имя столбца
     * @param string $definition параметры столбца
     * @return boolean
     */
    public function columnAdd($table, $name, $definition)
    {
        $success = false;
        do {
            if (empty($table)) {
                $this->error('Добавление столбца. Не указано название таблицы');
                break;
            }
            if (empty($name)) {
                $this->error('Добавление столбца. Таблица "'.$table.'", не указано название столбца');
                break;
            }
            if (empty($definition)) {
                $this->error('Добавление столбца. Таблица "'.$table.'", для столбца "'.$name.'" не указаны параметры столбца');
                break;
            }
            if (!$this->isTable($table)) {
                $this->error('Добавление столбца. Таблица "'.$table.'" не найдена, добавление столбца "'.$name.'"');
                break;
            }
            $schema = $this->schema($table);
            if (empty($schema['result'])) {
                $this->error('Добавление столбца. Схема для таблицы "'.$table.'" не найдена, добавление столбца "'.$name.'"');
                break;
            }
            foreach ($schema['result'] as $v) {
                if ($v['Field'] == $name) {
                    # стобец с таким наванием {$name} уже существует в таблице {$table}
                    break 2;
                }
            }

            $this->exec('ALTER TABLE '.$table.' ADD `'.$name.'` '.$definition);
            $success = true;
        } while (false);

        return $success;
    }

    /**
     * Удаление столбца из таблицы, если столбец существует
     * @param string $table имя таблицы
     * @param string $name имя столбца
     * @return boolean
     */
    public function columnDelete($table, $name)
    {
        $success = false;
        do {
            if (empty($table)) {
                $this->error('Удаление столбца. Не указано название таблицы');
                break;
            }
            if (empty($name)) {
                $this->error('Удаление столбца. Таблица "'.$table.'", не указано название столбца');
                break;
            }

            if (!$this->isTable($table)) {
                $this->error('Удаление столбца. Таблица "'.$table.'" не найдена, удаление столбца "'.$name.'"');
                break;
            }
            $schema = $this->schema($table);
            if (empty($schema['result'])) {
                $this->error('Удаление столбца. Схема для таблицы "'.$table.'" не найдена');
                break;
            }
            foreach ($schema['result'] as $v) {
                if ($v['Field'] == $name) {
                    $this->exec('ALTER TABLE '.$table.' DROP `'.$name.'`');
                    $success = true;
                    break 2;
                }
            }
        } while (false);

        return $success;
    }

    /**
     * Сортировка записей (для js-компонента tablednd)
     * @param string $table название таблицы
     * @param string $sAdditionalQuery дополнительные параметры запроса
     * @param string $sIDField название id-поля
     * @param string $sOrderField название num-поля (поля сортировки), например 'num'
     * @param boolean $bTree сортировке в дереве
     * @param string $sPIDField название pid-поля
     * @param string $sPrefix префикс входящих данных
     * @return boolean
     */
    public function rotateTablednd($table, $sAdditionalQuery = '', $sIDField = 'id', $sOrderField = 'num', $bTree = false, $sPIDField = 'pid', $sPrefix = 'dnd-')
    {
        do {
            /**
             * dragged  - перемещаемый елемент
             * target   - елемент 'до' или 'после' которого, оказался перемещаемый елемент (сосед)
             * position - новая позиция перемещаемого елемента относительно 'target' елемента
             */

            $nDraggedID = intval(str_replace($sPrefix, '', (!empty($_POST['dragged']) ? $_POST['dragged'] : '')));
            if ($nDraggedID <= 0) break;

            $nNeighboorID = intval(str_replace($sPrefix, '', (!empty($_POST['target']) ? $_POST['target'] : '')));
            if ($nNeighboorID <= 0) break;

            $sPosition = (isset($_POST['position']) ? trim($_POST['position']) : '');
            if (empty($sPosition) || !in_array($sPosition, array('after', 'before')))
                break;

            # сортируем
            $aNeighboorData = $this->one_array("SELECT $sIDField, $sOrderField" . ($bTree ? ", $sPIDField" : '') . " FROM $table WHERE $sIDField=$nNeighboorID $sAdditionalQuery LIMIT 1");
            if (!$aNeighboorData) return false;

            if ($sPosition == 'before') { # before
                $this->exec("UPDATE $table SET $sOrderField = (CASE WHEN $sIDField=$nDraggedID THEN {$aNeighboorData[$sOrderField]} ELSE $sOrderField+1 END)
                                WHERE ($sOrderField>={$aNeighboorData[$sOrderField]} OR $sIDField=$nDraggedID) 
                                      " . ($bTree ? " AND $sPIDField = " . $aNeighboorData[$sPIDField] : '') . " $sAdditionalQuery"
                );
            } else { # after
                $this->exec("UPDATE $table SET $sOrderField = (CASE WHEN $sIDField=$nDraggedID THEN {$aNeighboorData[$sOrderField]}+1 ELSE $sOrderField+1 END)
                                WHERE ($sOrderField>{$aNeighboorData[$sOrderField]} OR $sIDField=$nDraggedID) 
                                      " . ($bTree ? " AND $sPIDField = " . $aNeighboorData[$sPIDField] : '') . " $sAdditionalQuery"
                );
            }

            return true;

        } while (false);

        return false;
    }

    /**
     * Конвертируем список строк в древовидную структуру (дерево)
     * @param array $rows Двухуровневый массив строк, полученных из базы
     * @param string $idName название id-поля
     * @param string $pidName название pid-поля
     * @param string $childrenName название ключа для вложенных элементов
     * @return array конвертируем массив (дерево).
     */
    public function transformRowsToTree($rows, $idName, $pidName, $childrenName = 'childnodes')
    {
        if (empty($rows)) return $rows;
        $children = array(); # children of each ID
        $ids = array();
        # Collect who are children of whom.
        foreach ($rows as $i => $r) {
            $row =& $rows[$i];
            $id = $row[$idName];
            if ($id === null) {
                # Rows without an ID are totally invalid and makes the result tree to
                # be empty (because PARENT_ID = null means "a root of the tree"). So
                # skip them totally.
                continue;
            }
            $pid = $row[$pidName];
            if ($id == $pid) $pid = null;
            $children[$pid][$id] =& $row;
            if (!isset($children[$id])) $children[$id] = array();
            $row[$childrenName] =& $children[$id];
            $ids[$id] = true;
        }
        # Root elements are elements with non-found PIDs.
        $tree = array();
        foreach ($rows as $i => $r) {
            $row =& $rows[$i];
            $id = $row[$idName];
            $pid = $row[$pidName];
            if ($pid == $id) $pid = null;
            if (!isset($ids[$pid])) {
                $tree[$row[$idName]] =& $row;
            }
            //unset($row[$idName]); 
            //unset($row[$pidName]);
        }

        return $tree;
    }

    /**
     * Получаем ID всех parent-записей в таблице со структурой дерева id-pid
     * @param string $table название таблицы
     * @param integer $nID ID текущей записи (для которой необходимо получить parent-записи)
     * @param int $nDepth ограничитель по глубине
     * @param string $sIDField название id-поля
     * @param string $sPIDField название pid-поля
     * @return array
     */
    public function getAdjacencyListParentsID($table, $nID, $nDepth = 0, $sIDField = 'id', $sPIDField = 'pid')
    {
        if (!$nDepth) $nDepth = 20;

        $fields = array();
        $joins = array();
        $where = '';
        for ($i = 0; $i < $nDepth; $i++) {
            # Алиасы для таблицы.
            $alias = 't' . sprintf("%02d", $i);
            $aliasPrev = $i > 0 ? 't' . sprintf("%02d", $i - 1) : null;
            # Список полей для алиаса.
            $fields[] = "$alias.$sPIDField";

            # LEFT JOIN только для второй и далее таблиц!
            if ($aliasPrev)
                $joins[] = "LEFT JOIN $table $alias ON ($alias.$sIDField = $aliasPrev.$sPIDField)";
            else
                $joins[] = "$table $alias";
            # Условие поиска.
            if (!$i) {
                $where = "$alias.$sIDField = $nID";
            }
        }

        $query = 'SELECT ' . join(', ', $fields) . ' FROM ' . join(' ', $joins) . ' WHERE ' . $where;
        $tmp = $this->exec($query, null, 0, \PDO::FETCH_NUM, 'fetch'); //one_array
        $res = array();
        if (!empty($tmp)) {
            foreach ($tmp as $v) {
                if (!empty($v)) {
                    $res[] = $v;
                } else break;
            }
        }

        return $res;
    }

    /**
     * Получаем ID всех child-записей в таблице со структурой дерева id-pid
     * @param string $table название таблицы
     * @param integer $nID ID текущей записи (для которой необходимо получить child-записи)
     * @param int $nDepth ограничитель по глубине
     * @param string $sIDField название id-поля
     * @param string $sPIDField название pid-поля
     * @return array
     */
    public function getAdjacencyListChildrensID($table, $nID, $nDepth = 0, $sIDField = 'id', $sPIDField = 'pid')
    {
        if (!$nDepth) $nDepth = 20;

        $fields = array();
        $joins = array();
        $where = '';
        for ($i = 0; $i < $nDepth; $i++) {
            # Алиасы для таблицы.
            $alias = 't' . sprintf("%02d", $i);
            $aliasPrev = $i > 0 ? 't' . sprintf("%02d", $i - 1) : null;
            # Список полей для алиаса.
            $fields[] = "$alias.$sIDField";

            # LEFT JOIN только для второй и далее таблиц!
            if ($aliasPrev)
                $joins[] = "LEFT JOIN $table $alias ON ($alias.$sPIDField = $aliasPrev.$sIDField)";
            else
                $joins[] = "$table $alias";
            # Условие поиска.
            if (!$i) {
                $where = "$alias.$sIDField = $nID";
            }
        }

        $query = 'SELECT ' . join(', ', $fields) . ' FROM ' . join(' ', $joins) . ' WHERE ' . $where;
        $tmp = $this->exec($query, null, 0, \PDO::FETCH_NUM, 'fetchAll'); //select
        $res = array();
        if (!empty($tmp)) {
            foreach ($tmp as $t) {
                foreach ($t as $v) {
                    if (!empty($v)) {
                        if ($v != $nID) $res[] = $v;
                    } else break;
                }
            }
        }

        return $res;
    }

    /**
     * Формирование FULLTEXT запроса для mysql
     * @param string $sQ строка поиска
     * @param mixed $fields строка перечисление полей, учествующих в поиске, например 'content,title,mdescription'
     * @return string
     */
    public function prepareFulltextQuery($sQ, $fields = false)
    {
        # избавляемся от знаков *
        $sQ = str_replace('*', '', $sQ);

        # избавляемся от знаков -
        if (strpos($sQ, '-') !== false) {
            $sQ = str_replace('-', ' ', $sQ);
            $sQ = preg_replace("/\s+/", " ", $sQ); // и от двойных пробелов
            $sQ = rtrim($sQ); // и от последнего пробела
        }

        # добавляем к каждому слову *
        if (strpos($sQ, ' ') !== false) {
            $aWords = explode(' ', $sQ);
            $sQ = '';
            foreach ($aWords as $v) {
                if (strlen($v) > 2) {
                    $sQ .= $v . '* ';
                }
            }
        } else {
            $sQ .= '*';
        }

        if ($fields !== false) {
            return " MATCH($fields) AGAINST (" . $this->str2sql("$sQ") . " IN BOOLEAN MODE) ";
        } else {
            return $sQ;
        }
    }

    /**
     * Проверка ключа / Формирование ключа исходя из заголовка
     * @param string $sKeyword keyword
     * @param string $sTitle заголовок
     * @param string $table название таблицы
     * @param integer|null $nExceptRecordID исключая записи (ID)
     * @param string $sKeywordField название keyword-поля в таблице
     * @param string $sIDField название id-поля в таблице
     * @return string ключ
     */
    public function getKeyword($sKeyword = '', $sTitle = '', $table = false, $nExceptRecordID = null, $sKeywordField = 'keyword', $sIDField = 'id')
    {
        if (empty($sKeyword) && !empty($sTitle)) {
            $sKeyword = mb_strtolower(\func::translit($sTitle));
        }
        $sKeyword = preg_replace('/[^\p{L}\w0-9_\-]/iu', '', $sKeyword);

        if (empty($sKeyword)) {
            \Errors::i()->set(_t('', 'Укажите keyword'));
        } else {
            if ($table !== false) {
                $nFoundID = $this->isKeywordExists($sKeyword, $table, $nExceptRecordID, $sKeywordField, $sIDField);
                if (!empty($nFoundID))
                    \Errors::i()->set(_t('', 'Указанный keyword уже используется'));
            }
        }

        return $sKeyword;
    }

    /**
     * Проверка существования ключа
     * @param string $sKeyword keyword
     * @param string $table название таблицы
     * @param integer|null $nExceptRecordID исключая записи (ID)
     * @param string $sKeywordField название keyword-поля в таблице
     * @param string $sIDField название id-поля в таблице
     * @return integer
     */
    public function isKeywordExists($sKeyword, $table, $nExceptRecordID = null, $sKeywordField = 'keyword', $sIDField = 'id')
    {
        return $this->one_data('SELECT ' . $sIDField . '
                                FROM ' . $table . '
                                WHERE ' . (!empty($nExceptRecordID) ? ' ' . $sIDField . '!=' . intval($nExceptRecordID) . ' AND ' : '') . '
                                   ' . $sKeywordField . ' = :key  LIMIT 1', array(':key' => $sKeyword)
        );
    }

    // ------------------------------------------------------------------------
    // PostgreSQL Special Methods

    /**
     * Рестарт последовательности (SEQUENCE) в PostgreSQL
     * @param string $table название таблицы
     * @param string $columnName название столбца (для которого создана последовательность)
     * @param string $sequencePostfix постфикс
     * @param integer $nRestartWith счетчик, с которого необходимо выполнить рестарт
     */
    public function pgRestartSequence($table = '', $columnName = 'id', $sequencePostfix = '_seq', $nRestartWith = 1)
    {
        if (empty($nRestartWith) || $nRestartWith <= 0) $nRestartWith = 1;
        $this->exec('ALTER SEQUENCE "' . $table . ($columnName ? '_' . $columnName : '') . $sequencePostfix . '" RESTART WITH ' . $nRestartWith);
    }

    /**
     * Работа с ARRAY полем в PostgreSQL
     *  если $mData массив - формируем строку для сохранения в базу
     *  если $mData строка - формируем массив
     * @param string|array $mData
     * @param boolean $bJavascript результа необходим для дальнейшей работы в Javascript
     * @return array|string
     */
    public function pgArrayInt($mData, $bJavascript = false)
    {
        if (is_array($mData)) {
            if ($bJavascript) {
                return '[' . join(',', $mData) . ']';
            } else {
                $result = array();
                foreach ($mData as $v) {
                    if (is_array($v)) {
                        $result[] = $this->pgArrayInt($v, false);
                    } else {
                        if (!is_numeric($v)) { // quote only non-numeric values
                            $v = '"' . str_replace('"', '\\"', $v) . '"'; // escape double quote
                        }
                        $result[] = $v;
                    }
                }

                return '{' . implode(',', $result) . '}'; // format
            }
        } else {
            if (!is_string($mData)) $mData = '';
            $mData = (!empty($mData) ? trim($mData, '{}') : '');

            return ($bJavascript ? ('[' . $mData . ']')
                : ($mData != '' ? explode(',', $mData) : array()));
        }
    }

    // ------------------------------------------------------------------------
    // Методы для работы с языковыми таблицами (данными)

    /**
     * Формирование и выполнение INSERT-запроса
     * @param integer|array $id ID записи или массив параметров
     * @param array $data данные
     * @param array $fields ключи допустимых языковых полей
     * @param string $table название языковой таблицы
     * @param string $aLocales список ключей локализаций или false - все доступные
     * @return mixed
     */
    public function langInsert($id, $data, $fields, $table, $aLocales = array())
    {
        if (empty($fields)) return false;

        $sqlFields = array();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $sqlFields[] = $key;
            }
        }

        if (empty($sqlFields)) return false;

        $res = 0;
        $aInsert = (is_array($id) ? $id : array('id' => $id));
        $aLocales = (empty($aLocales) ? $this->locale->getLanguages() : $aLocales);
        foreach ($aLocales as $lang) {
            $sqlInsert = $aInsert;
            $sqlInsert['lang'] = $lang;
            foreach ($sqlFields as $key) {
                $sqlInsert[$key] = !empty($data[$key][$lang]) ? $data[$key][$lang] : '';
            }

            if ($this->insert($table, $sqlInsert, false)) {
                $res++;
            }
        }

        return $res;
    }

    /**
     * Формирование и выполнение UPDATE-запроса
     * @param integer|array $id ID записи или массив параметров
     * @param array $data данные
     * @param array $fields ключи допустимых языковых полей
     * @param string $table название языковой таблицы
     * @return mixed
     */
    public function langUpdate($id, $data, $fields, $table)
    {
        if (empty($fields)) return false;

        $sqlFields = array();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $fields)) {
                $sqlFields[] = $key;
            }
        }

        if (empty($sqlFields)) return false;

        $aNewLocales = array();
        $aCond = (is_array($id) ? $id : array('id' => $id));
        foreach ($this->locale->getLanguages() as $lang) {
            $sqlUpdate = array();
            foreach ($sqlFields as $key) {
                $sqlUpdate[$key] = $data[$key][$lang];
            }
            if (!empty($sqlUpdate)) {
                $aCond['lang'] = $lang;
                $res = $this->update($table, $sqlUpdate, $aCond);
                if (empty($res)) {
                    $aNewLocales[] = $lang;
                }
            }
        }
        if (!empty($aNewLocales)) {
            $this->langInsert($id, $data, $fields, $table, $aNewLocales);
        }
    }

    /**
     * Получение языковых данных (дополняем ими параметр $data)
     * @param integer|array $id ID записи или массив параметров
     * @param array $data @ref данные
     * @param array $fields ключи допустимых языковых полей
     * @param string $table название языковой таблицы
     * @return void
     */
    public function langSelect($id, &$data, $fields, $table)
    {
        if (empty($fields)) return;

        $fields = array_keys($fields);
        $aLocales = array_fill_keys($this->locale->getLanguages(), '');
        foreach ($fields as $key) {
            $data[$key] = $aLocales;
        }

        $sql = (is_array($id) ? \Model::filter($id) :
            array('where' => ' WHERE id = :id', 'bind' => array(':id' => $id)));
        $dataLang = $this->select('SELECT ' . join(', ', $fields) . ', lang FROM ' . $table . $sql['where'], $sql['bind']);
        foreach ($dataLang as $v) {
            foreach ($fields as $key) {
                $data[$key][$v['lang']] = $v[$key];
            }
        }
    }

    /**
     * Формирование "AND связки" с языковой таблицей
     * @param boolean $and добавлять "AND"
     * @param string $tablePrefix префикс таблицы с которой связываем
     * @param string $langPrefix префикс языковой таблицы
     * @param mixed $lang keyword языка или false (текущий)
     * @return string
     */
    public function langAnd($and = true, $tablePrefix = 'I', $langPrefix = 'L', $lang = false)
    {
        return ($and ? ' AND ' : '') . $tablePrefix . '.id = ' . $langPrefix . '.id AND ' . $langPrefix . '.lang = ' . $this->pdo->quote(($lang === false ? $this->locale->getCurrentLanguage() : $lang)) . ' ';
    }

    /**
     * Формируем bind языковых данных для UPDATE/INSERT запроса
     * В случае если языковые данные находятся в той же таблице что и основные данные
     * @param array $data данные
     * @param array $keys ключи допустимых языковых полей
     * @param array $bind @ref результат формирования
     * @return array
     */
    public function langFieldsModify($data, array $keys, &$bind)
    {
        if (empty($data) || empty($keys)) return array();

        $languages = $this->locale->getLanguages();
        $unbind = ($data === $bind);
        foreach ($keys as $key => $type) {
            if (isset($data[$key])) {
                foreach ($languages as $lng) {
                    if (isset($data[$key][$lng])) {
                        $bind[$key . '_' . $lng] = ($type == TYPE_ARRAY || $type > TYPE_CONVERT_SINGLE ? serialize($data[$key][$lng]) : $data[$key][$lng]);
                    }
                }
                # если $bind является ссылкой на $data
                if ($unbind && isset($bind[$key])) unset($bind[$key]);
            }
        }

        return $bind;
    }

    /**
     * Преобразование языковых данных в массиве $data
     * В случае если языковые данные находятся в той же таблице что и основные данные
     * @param array $data @ref
     * @param array $keys ключи c языковыми данными
     * @return void
     */
    public function langFieldsSelect(&$data, $keys)
    {
        if (empty($keys)) return;
        if (empty($data)) {
            $aLocales = array_fill_keys($this->locale->getLanguages(), '');
            foreach ($keys as $key => $type) {
                $data[$key] = $aLocales;
            }

            return;
        }
        foreach ($this->locale->getLanguages() as $lng) {
            foreach ($keys as $key => $type) {
                $key2 = $key . '_' . $lng;
                if (isset($data[$key2])) {
                    $data[$key][$lng] = ($type == TYPE_ARRAY || $type > TYPE_CONVERT_SINGLE ? unserialize($data[$key2]) : $data[$key2]);
                    unset($data[$key2]);
                } else {
                    $data[$key][$lng] = '';
                }
            }
        }
    }

    // ------------------------------------------------------------------------
    //PDO

    public function getPDO()
    {
        return $this->pdo;
    }

    public function getPersistent()
    {
        return $this->pdo->getAttribute(\PDO::ATTR_PERSISTENT);
    }

    public function setPersistent($value)
    {
        return $this->setAttribute(\PDO::ATTR_PERSISTENT, $value);
    }

    /**
     * Получаем имя текущего драйвера работы с базой
     * @return string
     */
    public function getDriverName()
    {
        static $cache;
        if (!isset($cache)) {
            $cache = mb_strtolower($this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        }

        return $cache;
    }

    /**
     * Является ли текущей база mysql
     * @return boolean
     */
    public function isMySQL()
    {
        // mysql, mysqli
        return (strpos($this->getDriverName(), 'mysql') == 0);
    }

    /**
     * Является ли текущей база pgsql
     * @return boolean
     */
    public function isPgSQL()
    {
        return ($this->getDriverName() == 'pgsql');
    }

    /**
     * Устанавливаем \PDO атрибут
     * @param mixed $name ключ атрибута
     * @param mixed $value значение
     * @return boolean
     */
    public function setAttribute($name, $value)
    {
        if ($this->pdo instanceof \PDO)
            return $this->pdo->setAttribute($name, $value);
        else {
            $this->attr[$name] = $value;

            return true;
        }
    }

    /**
     * Запуск миграций
     * @param string $action 'migrate', 'rollback', 'seed', 'status'
     * @param string $options ['configuration' => '', 'environment' => 'production', 'target', 'name', 'seed', ...]
     * @param boolean|string $output выводить результат на экран (при вызове из консоли)
     * @return string
     */
    public function migrations($action, array $options = array(), $output = false)
    {
        $configDefault = \config::file('phinx', true);

        # array => file
        if (isset($options['configuration']) && is_array($options['configuration'])) {
            $configDefaultData = require $configDefault;
            $config = \config::sys([], [], 'db');
            $configDB = array(
                'adapter' => $config['db.type'],
                'host'    => $config['db.host'],
                'name'    => $config['db.name'],
                'user'    => $config['db.user'],
                'pass'    => $config['db.pass'],
                'port'    => $config['db.port'],
                'charset' => $config['db.charset'],
            );
            $configDefaultData['environments']['production'] = $configDB;
            $configDefaultData['environments']['development'] = $configDB;
            $configFileName = Files::tempFile(false, 'dbmgrt');
            file_put_contents($configFileName, '<?php '.PHP_EOL.' return ' . var_export(array_merge($configDefaultData, $options['configuration']), true) . ';');
            $options['configuration'] = $configFileName;
        }

        if (!isset($options['configuration'])) {
            $options['configuration'] = $configDefault;
        }
        if (!isset($options['environment'])) {
            $options['environment'] = 'production';
        }
        $target = (isset($options['target']) ? $options['target'] : null);

        $command = false;
        switch ($action)
        {
            case 'create': {
                $name = (isset($options['name']) ? ucfirst($options['name']) : '?');
                $command = array(
                    $action,
                    '-c' => $options['configuration'], '-p' => 'php',
                    'name' => $name,
                );
            } break;
            case 'migrate': {
                $command = array(
                    $action,
                    '-e' => $options['environment'],
                    '-c' => $options['configuration'], '-p' => 'php',
                );
                if ($target) {
                    $command['-t'] = $target;
                }
            } break;
            case 'rollback': {
                $command = array(
                    $action,
                    '-e' => $options['environment'],
                    '-c' => $options['configuration'], '-p' => 'php',
                );
                if (isset($target)) {
                    $command['-t'] = $target;
                }
            } break;
            case 'seed': {
                $command = array(
                    $action,
                    '-e' => $options['environment'],
                    '-c' => $options['configuration'], '-p' => 'php',
                );
                if ($target) {
                    $command['-t'] = $target;
                }
                $seed = (isset($options['seed']) ? $options['seed'] : null);
                if ($seed) {
                    $command ['-s'] = (array) $seed;
                }
            } break;
            case 'seed-create': {
                $name = (isset($options['name']) ? ucfirst($options['name']) : '?');
                $command = array(
                    'seed:create',
                    '-c' => $options['configuration'], '-p' => 'php',
                    'name' => $name,
                );
            } break;
            case 'status': {
                $command = array(
                    $action,
                    '-e' => $options['environment'],
                    '-c' => $options['configuration'], '-p' => 'php',
                    '-f' => 'json',
                );
            } break;
            default: {
                $result = false;
            } break;
        }
        if ($command) {
            try {
                if ($output === true) {
                    $phinx = new \Phinx\Console\PhinxApplication();
                    $phinx->run(
                        new \Symfony\Component\Console\Input\ArrayInput($command)
                    );
                } else {
                    if (is_string($output) && !empty($output)) {
                        $streamFile = $output;
                        $streamFileKeep = true;
                    } else {
                        $streamFile = Files::tempFile(false, 'dbmgrt');
                    }
                    $stream = fopen($streamFile, 'w+');
                    $phinx = new \Phinx\Console\PhinxApplication();
                    $phinx->doRun(
                        new \Symfony\Component\Console\Input\ArrayInput($command),
                        new \Symfony\Component\Console\Output\StreamOutput($stream)
                    );
                    $result = stream_get_contents($stream, -1, 0);
                }
            } catch (\Exception $e) {
                $result = $e->getMessage();
            }
            if (isset($stream)) {
                fclose($stream);
                if ( ! isset($streamFileKeep)) {
                    unlink($streamFile);
                }
            }
        }

        if (isset($configFileName)) {
            unlink($configFileName);
        }

        return $result;
    }
}