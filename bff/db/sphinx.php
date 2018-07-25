<?php namespace bff\db;

/**
 * Класс для работы с поисковой системой Sphinx используя SphinxQL
 * @abstract
 * @version 0.42
 * @modified 4.may.2018
 */

class Sphinx_ extends \Module
{
    const TABLE           = DB_PREFIX.'sphinx';
    const TABLE_WORDFORMS = DB_PREFIX.'sphinx_wordforms';

    /** @var \PDO объект */
    protected $pdo;

    /** @var int ID модуля приложения */
    protected $moduleID = 0;

    /** @var string разделитель словоформ */
    protected $wordformsSeparator = '>';

    /**
     * Конструктор
     */
    public function __construct()
    {
        # Инициализируем модуль в качестве компонента (неполноценного модуля)
        $this->initModuleAsComponent('sphinx', PATH_CORE . 'db' . DS . 'sphinx');
        $this->init();
    }

    public function init()
    {
        parent::init();

        if (!static::enabled()) return;

        try {
            $host = \config::sysAdmin('sphinx.host', '127.0.0.1', TYPE_STR);
            $port = \config::sysAdmin('sphinx.port', '9306', TYPE_STR);
            $this->pdo = new \PDO('mysql:host='.$host.';port='.$port);
        } catch (\PDOException $e) {
            $error = _t('sphinx', 'Ошибка подключения к базе данных SphinxQL: [host], [msg]', array('host' => \Request::host(), 'msg' => $e->getMessage()));
            $this->errors->set($error);
            \bff::log($error);
        }
    }

    /**
     * Устанавливаем/получаем ID модуля
     * @param integer|bool $id
     * @return integer
     */
    protected function moduleID($id = false)
    {
        if ($id !== false) {
            $this->moduleID = $id;
        }
        return $this->moduleID;
    }

    /**
     * Поиск Sphinx включен
     * @return mixed
     */
    public static function enabled()
    {
        return \config::sysAdmin('sphinx.enabled', false, TYPE_BOOL);
    }

    /**
     * Проверка выполняется ли индексирование
     * @return bool
     */
    public function isRunning()
    {
        if ( ! static::enabled()) {
            return false;
        }
        $indexed = $this->db->select_data(static::TABLE, 'indexed', array('counter_id'=>$this->moduleID()));
        $indexed = strtotime($indexed);
        if ($indexed && (time() - $indexed < 90000 /* 25 hours */)) {
            return true;
        }
        return false;
    }

    /**
     * Префикс индексов Sphinx
     * @return string
     */
    public static function prefix()
    {
        return \config::sysAdmin('sphinx.prefix', '', TYPE_STR);
    }

    /**
     * Путь к системной директории Sphinx
     * @return string
     */
    public static function path()
    {
        return rtrim(\config::sysAdmin('sphinx.path', '/var/lib/sphinx/', TYPE_STR),DS).DS;
    }

    /**
     * Настройки модуля
     * @param array $settings исходные настройки: [
     *      'path' => '',
     *      'table' => 'bff_sphinx',
     *      'prefix' => '',
     *      'charset' => 'utf8',
     *      'sources' => array(), @ref
     *      'indexes' => array(), @ref
     * ]
     */
    public function moduleSettings(array $settings)
    {
    }

    /**
     * Метод формирующий настройки источников данных требуемые для подстановки в файл sphinx.conf
     * @param boolean $fullVersion полная версия (включает в себя секции indexer и searchd)
     * @return array
     */
    public function configSettings($fullVersion = false)
    {
        $prefix = static::prefix();
        $db = \config::sys(array(), array(), 'db', true);
        $db = array_merge(array(
            'type' => 'mysql',
            'host' => 'localhost',
            'port' => '3306',
            'name' => 'name',
            'user' => 'user',
            'pass' => 'pass',
            'charset' => 'utf8',
        ), (is_array($db) ? $db : array()));

        $sources = array();
        $indexes = array();
        $settings = array(
            'path'    => static::path(),
            'table'   => static::TABLE,
            'prefix'  => $prefix,
            'charset' => mb_strtolower($db['charset']),
            'sources' => &$sources,
            'indexes' => &$indexes,
        );

        # base source
        $sources['baseSource'] = array(
            ':extends'        => false,
            'type'            => $db['type'],
            'sql_host'        => $db['host'],
            'sql_port'        => $db['port'],
            'sql_db'          => $db['name'],
            'sql_user'        => $db['user'],
            'sql_pass'        => strtr($db['pass'], array(
                '!' => '\\!',
                '#' => '\\#',
            )),
            'sql_query_pre'   => array(
                'SET CHARACTER_SET_RESULTS='.$settings['charset'],
                'SET SESSION query_cache_type=OFF',
                'SET NAMES '.$settings['charset'],
            ),
        );

        # index template
        $indexTemplate = array(
            ':extends' => false,
            # Тип хранения аттрибутов
            'docinfo' => 'extern',
            'mlock' => '0',
            # Используемые морфологические движки
            'morphology' => 'stem_enru',
            # Кодировка данных из источника
            'charset_type' => 'utf-8',
            'charset_table' => '0..9, @, A..Z->a..z, _, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F',
            # Из данных источника HTML-код нужно вырезать
            'html_strip' => '1',
            'html_remove_elements' => 'style, script, code',
            # *test*
            'enable_star' => '1',
            # Не ндексируем части слова (инфиксы)
            'min_infix_len' => '0',
            # Храним начало слова
            'min_prefix_len' => '3',
            # Минимальный размер слова для индексации
            'min_word_len' => '3',
            # Хранить оригинальное слово в индексе
            'index_exact_words' => '1',
            # running -> ( running | *running* | =running )
            'expand_keywords' => '1',
        );
        $version =  \config::sysAdmin('sphinx.version', '2.2.1');
        if (version_compare($version, '2.2.1', '>=')) {
            unset($indexTemplate['charset_type']);
            unset($indexTemplate['enable_star']);
        }
        $indexes['indexTemplate'] = \bff::filter('sphinx.config.index.template', $indexTemplate);

        # Настройки модулей, плагинов, тем
        \bff::i()->callModules('sphinxSettings', array($settings));
        unset($settings['indexes']['indexTemplate']);

        if ($fullVersion) {
            $settings['indexer'] = array(
                # Лимит памяти, который может использавать демон-индексатор
                'mem_limit' => '64M',
            );

            $settings['searchd'] = array(
                # Адрес, на котором будет прослушиваться порт
                'listen'          => '127.0.0.1:9306:mysql41',
                'log'             => PATH_BASE.'files/logs/sphinx.searchd.log',
                'query_log'       => PATH_BASE.'files/logs/sphinx.query.log',
                'read_timeout'    => '5',
                'max_children'    => '30',
                'pid_file'        => '/var/run/sphinx/searchd.pid',
                #'max_matches'     => '100000',
                'seamless_rotate' => '1',
                'preopen_indexes' => '1',
                'unlink_old'      => '1',
                'workers'         => 'threads', # for RT to work
                'binlog_path'     => '/var/lib/sphinx',
            );
        }

        return \bff::filter('sphinx.config', $settings);
    }

    /**
     * Формирование файла конфигурации
     * @param bool $refresh обновить файл
     * @param bool $fullVersion полная версия файла
     * @return mixed
     */
    public function configFile($refresh = true, $fullVersion = false)
    {
        $settings = $this->configSettings($fullVersion);
        $template = $this->viewPHP($settings, 'config');
        if ($refresh) {
            return file_put_contents(PATH_BASE.'config'.DS.'sphinx.conf', $template);
        } else {
            return $template;
        }
    }

    /**
     * Путь к конфигурационному файлу модуля
     * @return string
     */
    protected function wordformsFile()
    {
        $path = PATH_BASE.'files'.DS.'sphinx';
        if ( ! file_exists($path)) {
            \bff\utils\Files::makeDir($path);
        }
        return $path.DS.'wordforms'.$this->moduleID().'.txt';
    }

    /**
     * Добавление словоформ в конфигурацию индекса и сохранение при необходимости
     * @param array $config @ref
     * @return bool
     */
    public function wordformsConfig(& $config)
    {
        $cnt = $this->wordformsListing();
        if ($cnt) {
            if ($this->wordformsFileUpdate()) {
                $config['wordforms'] = $this->wordformsFile();
            }
        }
        return false;
    }

    /**
     * Подготовка строки поиска
     * @param string $query строка поиска
     * @param array $options @ref опции поиска
     * @return string
     */
    protected function prepareSearchQuery($query, & $options = array())
    {
        // http://sphinxsearch.com/docs/current.html#extended-syntax
        $empty = array ('(', ')', '|', '*', '-', '!',  "'", '&', '^'); // '"'
        $query = str_replace($empty, '', $query);

        $space = array ('@', '/', '\\', '=', '~', '$', "\x00", "\n", "\r", "\x1a", "\t");
        $query = str_replace($space, ' ', $query);

        if (mb_strpos($query, '"') === 0) { # поиск точной фразы
            // http://sphinxsearch.com/blog/2013/07/23/from-api-to-sphinxql-and-back-again/
            $options['ranker'] = 'proximity';
        } else {
            $query = str_replace('"', '', $query);
            # фомируем запрос поиска каждого слова отдельно
            $words = explode(' ', $query);
            $tmp = array();
            foreach ($words as $word) {
                if (mb_strlen($word) >= 3) {
                    $tmp[] = "($word | $word*)";
                }
            }
            $query = join(' & ', $tmp);
        }
        return $query;
    }

    /**
     * Обрабатываем (выполняем) SQL запросы
     * @param string|array $query запросы
     * @param array $bind аргументы
     * @param integer|boolean $fetchType PDO::FETCH_NUM, PDO::FETCH_ASSOC, PDO::FETCH_BOTH, PDO::FETCH_OBJ
     * @param string $fetchFunc
     * @param array $prepareOptions
     * @return array|boolean
     */
    protected function exec($query, array $bind = null, $fetchType = false, $fetchFunc = 'fetchAll', array $prepareOptions = array())
    {
        if (!$this->pdo) return false;

        $batch = is_array($query);
        if ($batch) {
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

        $result = false;
        foreach (array_combine($query, $bind) as $cmd => $arg)
        {
            if (is_null($arg)) {
                $query = $this->pdo->query($cmd);
            } else {
                $query = $this->pdo->prepare($cmd, $prepareOptions);
                if (is_object($query)) {
                    foreach ($arg as $key => $value) {
                        if (!(is_array($value) ?
                            $query->bindValue($key, $value[0], $value[1]) :
                            $query->bindValue($key, $value, $this->db->type($value)))
                        ) {
                            break;
                        }
                    }
                    $query->execute();
                }
            }
            # Проверяем SQLSTATE на наличие ошибок
            foreach (array($this->pdo, $query) as $obj) {
                if ($obj !== false && $obj->errorCode() != \PDO::ERR_NONE) {
                    $error = $obj->errorInfo();
                    \bff::log('[SphinxQL Error] ( ' . (@$error[0] . '.' . (isset($error[1]) ? $error[1] : '?')) . ' : ' . (isset($error[2]) ? $error[2] : '') . ' )');
                    return false;
                }
            }

            if ($fetchType !== false || preg_match('/^\s*(?:SELECT|PRAGMA|SHOW|EXPLAIN)\s/i', $cmd)) {
                $result = $query->$fetchFunc($fetchType);
            } else {
                $result = $query->rowCount();
            }
        }

        return $result;
    }

    /**
     * Получаем несколько строк из таблицы
     * @param string $query текст запроса
     * @param array|null $bindParams параметры запроса или null
     * @param integer $fetchType PDO::FETCH_NUM, PDO::FETCH_ASSOC, PDO::FETCH_BOTH, PDO::FETCH_OBJ
     * @param string $fetchFunc
     * @return mixed
     */
    protected function select($query, $bindParams = null, $fetchType = \PDO::FETCH_ASSOC, $fetchFunc = 'fetchAll')
    {
        return $this->exec($query, $bindParams, $fetchType, $fetchFunc);
    }

    /**
     * Выполняем UPDATE запрос
     * @param string|array $indexName название индекса
     * @param array $set массив параметров для обновления
     * @param array $where условия WHERE
     * @param array $options:
     *     string|array 'orderBy' порядок сортировки
     *     string|array|integer 'limit' условие LIMIT
     * @return mixed
     */
    protected function update($indexName, array $set, array $where, array $bind = array(), array $options = array())
    {
        if (is_array($indexName)) {
            $indexName = array_shift($indexName);
        }
        $options['returnQuery'] = true;
        $query = $this->db->update($indexName, $set, $where, $bind, array(), $options);

        return $this->exec($query['query'], $query['bind']);
    }

    /**
     * Обновляем атрибуты документов
     * @param array $data атрибуты с новыми данными
     * @param array $filter фильтр
     * @param array $indexes список индексов
     * @param array $options доп. параметры
     * @return int кол-во затронутых документов
     */
    public function updateAttributes($data, $filter, array $indexes = array(), array $options = array())
    {
        if (empty($indexes)) {
            return 0;
        }
        $total = 0;
        foreach ($indexes as $v) {
            $total += (int)$this->update($v, $data, $filter, (isset($options['bind']) ? $options['bind'] : array()), $options);
        }
        return $total;
    }

    /**
     * Менеджер для управления словоформами
     * @param string $ajaxUrl URL для обработки ajax запросов
     * @return string HTML
     */
    public function wordformsManager($ajaxUrl)
    {
        $respData = function($id) {
            $data = array();
            $data['list'] = $this->wordformsListing(array('id' => $id), array('id', 'src', 'dest'), 1);
            return $this->viewPHP($data, 'admin.wordforms.listing.ajax');
        };

        $act = $this->input->postget('act', TYPE_STR);
        if (!empty($act)) {
            $response = array();
            switch ($act) {
                case 'sphinx-wordforms-add':
                {
                    $submit = $this->input->post('save', TYPE_BOOL);
                    $data = $this->wordformValidate(0, $submit);
                    if ($submit) {
                        if ($this->errors->no()) {
                            $id = $this->wordformSave(0, $data);
                            $response['html'] = $respData($id);
                        }
                    } else {
                        $data['id'] = 0;
                        $data['act'] = $act;
                        $response['html'] = $this->viewPHP($data, 'admin.wordform.form');
                    }
                } break;
                case 'sphinx-wordforms-edit':
                {
                    $id = $this->input->postget('id', TYPE_UINT);
                    if (!$id) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $submit = $this->input->post('save', TYPE_BOOL);

                    if ($submit) {
                        $data = $this->wordformValidate($id, $submit);
                        if ($this->errors->no()) {
                            $this->wordformSave($id, $data);
                            $response['html'] = $respData($id);
                        }
                    } else {
                        $data = $this->wordformData($id);
                        if (empty($data)) {
                            $this->errors->unknownRecord();
                            break;
                        }
                        $data['act'] = $act;
                        $response['html'] = $this->viewPHP($data, 'admin.wordform.form');
                    }
                } break;
                case 'sphinx-wordforms-delete':
                {
                    $id = $this->input->postget('id', TYPE_UINT);
                    if (!$id) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $this->wordformDelete($id);
                } break;
                case 'sphinx-wordforms-data':
                {
                    $id = $this->input->postget('id', TYPE_UINT);
                    if (!$id) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $response['html'] = $respData($id);
                } break;
                case 'sphinx-wordforms-many':
                {
                    $text = $this->input->postget('text', TYPE_STR);
                    if (empty($text)) {
                        $this->errors->set(_t('sphinx', 'Укажите список словоформ'));
                        break;                        
                    }
                    if ( ! mb_strpos($text, $this->wordformsSeparator)) {
                        $this->errors->set(_t('sphinx', 'Синоним и оригинал разделяются символом [separator]', array('separator'=>'"'.$this->wordformsSeparator.'"')));
                        break;
                    }
                    $cnt = $this->wordformSaveMany($text);
                    $response['msg'] = _t('sphinx', 'Добавлено [cnt]', array('cnt' => \tpl::declension($cnt, _t('sphinx', 'словоформа;словоформы;словоформ'))));
                } break;
                default:
                    $response = false;
            }

            if ($response !== false) {
                if (\Request::isAJAX()) $this->ajaxResponseForm($response);
            }
        }

        $this->input->postgetm(array(
            'page'  => TYPE_UINT,
            'q'     => TYPE_NOTAGS,
        ), $f);

        $sql = array();
        $perpage = 15;
        $data['pgn'] = '';
        $data['act'] = $act;

        if ($f['q']) {
            $sql[':q'] = array('(src LIKE :q OR dest LIKE :q)', ':q' => '%'.$f['q'].'%');
        }

        $data['orders'] = array('id'=>'asc','src'=>'asc','dest'=>'asc');
        $orderBy = '';
        $orderDirection = '';
        $f += $this->prepareOrder($orderBy, $orderDirection, 'id-asc', $data['orders']);
        $f['order'] = $orderBy.'-'.$orderDirection; $sqlOrder = "$orderBy $orderDirection";

        $nCount = $this->wordformsListing($sql);
        $oPgn = new \Pagination($nCount, $perpage, '#', 'jSphinxWordformsList.page('.\Pagination::PAGE_ID.'); return false;');
        $oPgn->setPageNeighbours(6);
        $data['pgn'] = $oPgn->view(array('arrows'=>false));
        $data['list'] = $this->wordformsListing($sql, array('id','src','dest'), $oPgn->getLimitOffset(), $sqlOrder);
        $data['list'] = $this->viewPHP($data, 'admin.wordforms.listing.ajax');

        if (\Request::isAJAX() && $act == 'sphinx-wordforms-list') {
            $this->ajaxResponseForm(array(
                'list' => $data['list'],
                'pgn'  => $data['pgn'],
            ));
        }

        $data['f'] = $f;
        $data['ajaxUrl'] = $ajaxUrl;
        $data['isRunning'] = $this->isRunning();
        $data['separator'] = $this->wordformsSeparator;
        return $this->viewPHP($data, 'admin.wordforms.listing');
    }

    /**
     * Обработка данных словоформы
     * @param integer $wordformID ID словоформы
     * @param bool $submit сохранение данных
     * @return array
     */
    protected function wordformValidate($wordformID, $submit)
    {
        $data = array();
        $params = array(
            'src'  => TYPE_TEXT,
            'dest' => TYPE_TEXT,
        );

        $this->input->postm($params, $data);

        if ($submit) {
            $this->wordformCheck($data, $wordformID);
        }
        return $data;
    }

    /**
     * Проверка параметров словоформы
     * @param array $data параметры словоформы
     * @param integer $wordformID ID словоформы или 0
     * @param bool $setErrors устанавливать ошибки
     * @return bool проверка прошла успешно (true)
     */
    protected function wordformCheck($data, $wordformID, $setErrors = true)
    {
        do {
            if (empty($data['src'])) {
                if ($setErrors) $this->errors->set(_t('sphinx', 'Введите синоним'));
                break;
            }
            if (mb_strlen($data['src']) < 3) {
                if ($setErrors) $this->errors->set(_t('sphinx', 'Слово синоним слишком короткое'));
                break;
            }
            if (empty($data['dest'])) {
                if ($setErrors) $this->errors->set(_t('sphinx', 'Введите оригинальное слово'));
                break;
            }
            if (mb_strlen($data['dest']) < 3) {
                if ($setErrors) $this->errors->set(_t('sphinx', 'Оригинальное слово слишком короткое'));
            }
            if ($data['src'] == $data['dest']) {
                if ($setErrors) $this->errors->set(_t('sphinx', 'Указанные слова не могут быть одинаковыми'));
                break;
            }

            $filter = array(
                ':exist' => array('(src = :query OR dest = :query)', ':query' => $data['src']),
            );
            if ($wordformID) {
                $filter['id'] = array('!=', $wordformID);
            }
            if ($this->wordformsListing($filter)) {
                if ($setErrors) $this->errors->set(_t('sphinx', 'Словоформа [word] уже используется', array('word' => '"'.$data['src'].'"')));
                break;
            }

            $filter = array(
                'src' => $data['dest'],
            );
            if ($wordformID) {
                $filter['id'] = array('!=', $wordformID);
            }
            if ($this->wordformsListing($filter)) {
                if ($setErrors) $this->errors->set(_t('sphinx', 'Словоформа [word] уже используется', array('word' => '"'.$data['dest'].'"')));
                break;
            }

            return true;
        } while(false);

        return false;
    }

    /**
     * Сохранение данных словоформы
     * @param integer $wordformID ID словоформы
     * @param array $data данные
     * @return mixed
     */
    protected function wordformSave($wordformID, array $data)
    {
        if (empty($data)) return false;

        if ($wordformID > 0) {
            $res = $this->db->update(static::TABLE_WORDFORMS, $data, array(
                'id' => $wordformID,
                'module' => $this->moduleID(),
            ));
            if ($res) {
                $this->wordformsFileUpdate();
            }
        } else {
            if (empty($data['created'])) {
                $data['created'] = $this->db->now();
            }
            if ( ! isset($data['module'])) {
                $data['module'] = $this->moduleID();
            }
            $res = $this->db->insert(static::TABLE_WORDFORMS, $data);
            if ($res) {
                $cnt = $this->wordformsListing();
                if ($cnt == 1) {
                    $this->configFile();
                } else {
                    $this->wordformsFileUpdate();
                }
            }
        }
        return $res;
    }

    /**
     * Пакетное добавление словоформ
     * @param string $text текст содержащий список словорм
     * @return int кол-во добавленных словоформ
     */
    protected function wordformSaveMany($text)
    {
        $cnt = 0;
        $text = explode("\n", $text);
        $save = array();
        foreach ($text as $v) {
            $row = explode($this->wordformsSeparator, $v);
            if (count($row) != 2) continue;
            $data = array(
                'src' => trim($row[0]),
                'dest' => trim($row[1]),
                'created' => $this->db->now(),
                'module' => $this->moduleID(),
            );
            if ( ! $this->wordformCheck($data, 0, false)) {
                continue;
            }
            $save[] = $data;
            $cnt++;
        }
        if ( ! empty($save)) {
            $before = $this->wordformsListing();
            $this->db->multiInsert(static::TABLE_WORDFORMS, $save);
            if ($before == 0) {
                $now = $this->wordformsListing();
                if ($now) {
                    $this->configFile();
                }
            } else {
                $this->wordformsFileUpdate();
            }
        }
        return $cnt;
    }

    /**
     * Данные словоформы
     * @param integer $wordformID ID словоформы
     * @param array $fields список полей
     * @return array
     */
    protected function wordformData($wordformID, array $fields = array())
    {
        if (empty($fields)) {
            $fields = array('*');
        }

        if ( ! $wordformID) return array();

        $data = $this->db->select_row(static::TABLE_WORDFORMS, $fields, array('id'=>$wordformID));
        if (empty($data)) return array();

        return $data;
    }

    /**
     * Удаление словоформы
     * @param integer $wordformID ID словоформы
     */
    protected function wordformDelete($wordformID)
    {
        $this->db->delete(static::TABLE_WORDFORMS, array('id' => $wordformID, 'module' => $this->moduleID()));
        $cnt = $this->wordformsListing();
        if ($cnt) {
            $this->wordformsFileUpdate();
        } else {
            $this->configFile();
        }
    }

    /**
     * Список словоформ
     * @param array $filter фильтр
     * @param array $fields список полей
     * @param string $limit SQL Limit
     * @param string $order SQL Order
     * @return mixed
     */
    protected function wordformsListing(array $filter = array(), array $fields = array(), $limit = '', $order = '')
    {
        if ( ! isset($filter['module'])) {
            $filter['module'] = $this->moduleID();
        }

        if (empty($fields)) {
            return $this->db->select_rows_count(static::TABLE_WORDFORMS, $filter);
        }

        $data = $this->db->select_rows(static::TABLE_WORDFORMS, $fields, $filter, $order, $limit);
        if (empty($data)) return array();

        return $data;
    }

    /**
     * Сохранение списка словоформ в конфигурационный файл
     * @return bool true удалось сохранить
     */
    protected function wordformsFileUpdate()
    {
        $path = $this->wordformsFile();
        $file = fopen($path, 'w');
        if ($file === false) {
            \bff::log(_t('dev', 'Невозможно создать файл "[file]"', array('file' => $path)));
            return false;
        }

        $select = $this->db->select_prepare(static::TABLE_WORDFORMS, array('src','dest'), array(
            'module' => $this->moduleID(),
        ));
        $this->db->select_iterator($select['query'], $select['bind'], function($row) use(& $file) {
            fwrite($file, $row['src'].' '.$this->wordformsSeparator.' '.$row['dest'].PHP_EOL);
        });
        fclose($file);
        return true;
    }

}