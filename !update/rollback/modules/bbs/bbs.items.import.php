<?php

/**
 * Компонент обработки импорта / экспорта объявлений
 * @version 0.27
 * @modified 16.dec.2017
 */

use \bff\utils\Files;

class BBSItemsImport_ extends Component
{
    /** @var BBS */
    protected $bbs;
    /** @var string Путь к директории файлов импорта */
    protected $importPath = '';
    /** @var int Количество обрабатываемых объявлений за раз */
    protected $importProcessingStep = 1000;
    /** @var int Кол-во мегабайт требуемых на экспорт 1000 записей */
    protected $export_mbPer1000 = 15;
    /** @var array Ключи контактных данных при импорте csv */
    protected $csvContactsFields = [];

    const STATUS_WAITING    = 1;  # ожидает обработки
    const STATUS_PROCESSING = 2;  # обрабатывается
    const STATUS_FINISHED   = 4;  # обработка завершена
    const STATUS_CANCELED   = 8;  # отменен
    const STATUS_ERROR      = 32; # ошибка

    # Тип импорта объявления
    const TYPE_FILE = 0; # импорт файла
    const TYPE_URL  = 1; # ссылка на файл

    const ATTRIBUTE_TYPE = 'items-import-export'; # значение аттрибута type для тега bbs

    public function init()
    {
        parent::init();
        $this->bbs = BBS::i();
        $this->importPath = static::getImportPath();
        # contacts: csv fields
        foreach (Users::contactsFields(true) as $contact) {
            $this->csvContactsFields[] = 'contacts-'.$contact;
        }
    }

    /**
     * Список допустимых расширений файлов
     * @return array
     */
    public function extensionsAllowed()
    {
        return bff::filter('bbs.import.extensions.whitelist', array('xml', 'csv'));
    }

    /**
     * Полный список полей для csv файла
     * @return array
     */
    public static function availableCsvFields()
    {
        $fields = ['item-id', 'item-external', 'title', 'description', 'category-id', 'category-type',
            'geo-delivery', 'geo-city-id', 'geo-station-id', 'geo-district-id', 'geo-addr', 'geo-lat', 'geo-lon',
            'price', 'price-currency', 'price-free', 'price-exchange', 'price-mod', 'price-agreed',
            'images', 'contacts-name', 'contacts-phones', 'video'];

        # + contacts fields
        foreach (Users::contactsFields(true) as $contact) {
            $contact = 'contacts-'.$contact;
            if (!in_array($contact, $fields)) {
                $fields[] = $contact;
            }
        }

        return bff::filter('bbs.items.import.csv.available.fields', $fields);
    }

    /**
     * Включена ли асинхронная обработка файлов
     * @return bool
     */
    public static function async()
    {
        if (config::sys('bbs.import.async', true, TYPE_BOOL)) {
            if (bff::cron()) {
                return function_exists('pcntl_fork');
            }
            return true;
        }
        return false;
    }

    /**
     * Максимальное количество потоков для импорта (при включенной асинхронной обработке файлов)
     * @return integer
     */
    public static function asyncThreads()
    {
        return config::sys('bbs.import.async.threads', 5, TYPE_UINT);
    }

    /**
     * Инициализация импорта по URL
     * @param array $settings параметры импорта
     * @param integer $parentID ID задания для импорта
     * @return bool|int
     */
    public function importUrlStart(array $settings, $parentID = 0)
    {
        $ext = pathinfo($settings['url'], PATHINFO_EXTENSION);
        if ( ! in_array($ext, $this->extensionsAllowed())) {
            $this->errors->set(_t('bbs.import', 'Неизвестный формат файла'));
            return false;
        }
        $filePath = $this->importPath . func::generator(14, false) . '.'.$ext;
        \bff\utils\Files::downloadFile($settings['url'], $filePath);
        if( ! $this->errors->no()){
            return false;
        }
        $fileInfo = new SplFileInfo($filePath);
        $file['extension'] = $fileInfo->getExtension();
        $file['filesize'] = $fileInfo->getSize();
        $file['filename'] = $fileInfo->getFilename();
        $file['hash'] = md5_file($filePath);

        # проверка структуры файла
        $items = $this->checkFile($file, $settings, $cat);
        if($items === false){
            return false;
        }
        if (empty($settings['langKey'])) {
            $settings['langKey'] = LNG;
        }

        if($parentID){
            $parent = $this->bbs->model->importData($parentID);
            $save = array(
                'settings' => serialize($settings),
                'filename' => serialize($file),
                'user_id' => $settings['userId'],
                'cat_id' => !empty($cat['id']) ? $cat['id'] : 0,
                'items_total'    => $items,
                'status' => self::STATUS_WAITING,
                'status_changed' => $this->db->now(),
                'is_admin' => $parent['is_admin'],
                'created' => $this->db->now(),
                'periodic' => self::TYPE_FILE,
                'parent_id' => $parentID,
            );
        }else{
            $save = array(
                'settings' => serialize($settings),
                'user_id' => User::id(),
                'user_ip' => Request::remoteAddress(),
                'cat_id' => !empty($cat['id']) ? $cat['id'] : 0,
                'status' => self::STATUS_FINISHED,
                'status_changed' => $this->db->now(),
                'is_admin' => bff::adminPanel(),
                'created' => $this->db->now(),
                'periodic' => self::TYPE_URL,
                'parent_id' => $parentID,
                'periodic_url' => $settings['url'],
                'periodic_timeout' => $settings['period'],
                'periodic_expire' => $this->db->now(),
            );
            @unlink($filePath);
        }
        return $this->importSaveRun($save);
    }

    /**
     * Инициализация импорта из файла
     * @param string $fileKey ключ файла импорта в _FILES
     * @param array $settings параметры импорта
     *  catId  - ID категории
     *  userId - ID пользователя (владельца импортируемых объявлений)
     *  shop   - ID магазина
     *  state  - статус импортируемых объявлений
     * @return integer ID успешно созданного импорта или false
     */
    public function importStart($fileKey, array $settings)
    {
        $isAdmin = bff::adminPanel();

        # загрузка файла
        $uploader = new \bff\files\Attachment($this->importPath, 0);
        $uploader->setCheckFreeDiskSpace(false);
        $uploader->setFiledataAsString(false);
        $uploader->setAllowedExtensions($this->extensionsAllowed());
        if ($isAdmin) {
            # для администратора, ограничиваем размер файла 25mb
            $uploader->setMaxSize(config::sysAdmin('bbs.import.file.maxsize.admin', 1048576 * 25, TYPE_UINT));
        } else {
            # для фронтенда, ограничиваем размер файла 10mb
            $uploader->setMaxSize(config::sysAdmin('bbs.import.file.maxsize', 1048576 * 10, TYPE_UINT));
        }

        if ($this->errors->no()) {
            $uploader->setAssignErrors(true);
            $file = $uploader->uploadFILES($fileKey);
            if (!$this->errors->no()) {
                return false;
            }
        } else {
            $file = $uploader->uploadFILES($fileKey);
        }
        if (empty($file)) {
            $this->errors->set(_t('bbs.import', 'Не удалось загрузить файл импорта'));
            return false;
        }
        $filePath = $this->importPath . $file['filename'];
        $file['hash'] = md5_file($filePath);
        unset($file['error']);

        if (!$file || empty($file)) {
            $this->errors->set(_t('bbs.import', 'Не удалось загрузить файл импорта'));
            return false;
        }

        # проверка структуры файла
        $items = $this->checkFile($file, $settings, $cat);

        if($items === false){
            return false;
        }
        if (empty($settings['langKey'])) {
            $settings['langKey'] = LNG;
        }

        $aData = array(
            'settings'       => serialize($settings),
            'filename'       => serialize($file),
            'user_id'        => User::id(),
            'user_ip'        => Request::remoteAddress(),
            'cat_id'         => ! empty($cat['id']) ? $cat['id'] : 0,
            'items_total'    => $items,
            'status'         => self::STATUS_WAITING,
            'status_changed' => $this->db->now(),
            'is_admin'       => $isAdmin,
            'created'        => $this->db->now(),
        );

        # сохранение настроек импорта в базу
        return $this->importSaveRun($aData);
    }

    /**
     * Сохранение настроек импорта в базу
     * @param $data
     * @return bool|int
     */
    protected function importSaveRun($data)
    {
        if (empty($data)) return false;
        $importID = $this->bbs->model->importSave(0, $data);
        if ( ! static::async()) {
            return $importID;
        }
        $this->importStartTasks($importID);
        return $importID;
    }

    /**
     * Запуск потоков обработки файлов импорта.
     * @param int $importID ID задания или 0 - всех заданий
     */
    protected function importStartTasks($importID = 0)
    {
        $cronManager = bff::cronManager();
        if ( ! $cronManager->isEnabled()) return;

        # обрабатываемые и незавершенные задания
        $processing = $this->bbs->model->importListing(
            array('I.id', 'I.async_pid', 'I.user_id', 'I.settings'),
            array('status' => self::STATUS_PROCESSING, 'working' => 1, 'periodic' => 0),
            false,
            'I.status_changed'
        );
        $executing = 0;
        $users = array();
        $limit = static::asyncThreads();
        foreach ($processing as $k => &$v){
            $v['settings'] = func::unserialize($v['settings']);
            if ( ! empty($v['settings']['userId'])) {
                $v['user_id'] = $v['settings']['userId'];
            }
            # у задания указан ID процесса, и процесс присутствует в системе - задание обрабатывается другим процессом
            if ($v['async_pid'] && posix_getpgid($v['async_pid'])) {
                $users[] = $v['user_id'];  # запомним пользователей - владельцев обрабатываемых заданий
                $executing++;
                unset($processing[$k]); # из текущего процесса задание исключаем
            }
        } unset($v);

        if ($executing >= $limit) {
            # заняты все доступные слоты. ничего не делаем.
            return;
        }

        $limit -= $executing;
        $exec = array();
        # задания, ожидающие обработки
        $waiting = $this->bbs->model->importListing(
            array('I.id', 'I.user_id', 'I.settings'),
            array('status' => self::STATUS_WAITING, 'working' => 0, 'periodic' => 0),
            false,
            'I.status_changed, I.created'
        );
        foreach ($waiting as $v) {
            $v['settings'] = func::unserialize($v['settings']);
            if ( ! empty($v['settings']['userId'])) {
                $v['user_id'] = $v['settings']['userId'];
            }
            if (in_array($v['user_id'], $users)) continue;  # задание этого пользователя сейчас обрабатывается - пропускаем
            $exec[] = $v['id']; # будем обрабатывать задание
            $users[] = $v['user_id'];
        }
        # в начале стартуем новые, потом продолжаем незавершенные
        if ( ! empty($processing)) {
            foreach ($processing as $v) {
                if (in_array($v['user_id'], $users)) continue; # задание этого пользователя сейчас обрабатывается - пропускаем
                $exec[] = $v['id']; # будем обрабатывать задание
                $users[] = $v['user_id'];
            }
        }
        # если указанно, какое задание статровать
        if ($importID) {
            # и оно разрешено для старта, то его и стартуем
            if (in_array($importID, $exec)) {
                $cronManager->executeOnce('bbs', 'itemsCronImportOnce', array('id' => $importID), $importID);
            }
            return;
        }

        # стартуем все необходимые
        while ( ! empty($exec) && $limit > 0) {
            $task = array_shift($exec);
            if ( ! empty($task)) {
                $cronManager->executeOnce('bbs', 'itemsCronImportOnce', array('id' => $task), $task);
                $limit--;
            }
        }
    }

    /**
     * Запуск конкретного задания
     * @param array $params
     */
    public function importTaskOnce(array $params)
    {
        if (empty($params) || empty($params['id'])) return;

        $data = $this->bbs->model->importData($params['id']);
        if (empty($data)) return;
        if ( ! in_array($data['status'], array(static::STATUS_PROCESSING, static::STATUS_WAITING))) return;
        if ($data['async_pid'] && posix_getpgid($data['async_pid'])) return;

        $working = $this->bbs->model->importListing(
            array('I.id', 'I.async_pid'),
            array(':pid' => 'I.async_pid > 0')
        );

        if ( ! empty($working)) {
            foreach ($working as $k => $v) {
                if ( ! posix_getpgid($v['async_pid'])) {
                    unset($working[$k]);
                }
            }
            if (count($working) >= static::asyncThreads()) {
                return;
            }
        }

        $this->importContinue($params['id']);
    }

    /**
     * Проверка структуры файла импорта
     * @param array $file @ref данные о файле
     * @param array $settings @ref параметры импорта
     * @param array $cat @ref данные о категории
     * @return int кол-во объявлений в файле для импорта
     */
    protected function checkFile(& $file, & $settings, & $cat = array())
    {
        $filePath = $this->importPath.$file['filename'];
        $file['hash'] = md5_file($filePath);
        unset($file['error']);

        $methodCheck = 'checkFile'.$file['extension'];
        if (method_exists($this, $methodCheck)) {
            $items = $this->$methodCheck($filePath);
        } else {
            $this->errors->set(_t('bbs.import', 'Неизвестный формат файла'));
            return false;
        }

        if ($items == 0) {
            if ($this->errors->no()) {
                $this->errors->set(_t('bbs.import', 'Не удалось найти объявления для импорта'));
            }
            return false;
        }

        # заполним данные о категории, если указана в параметрах
        if ( ! empty($settings['catId'])) {
            $aParents = $this->bbs->model->catParentsData($settings['catId'], array('id'));
            if (empty($aParents)) {
                $this->errors->set(_t('bbs.import', 'Категория указана некорректно'));
                return false;
            }
            $cat = reset($aParents);
        }

        # проверяем пользователя - владельца объявления
        if (bff::adminPanel()){
            if (!$settings['userId']) {
                $this->errors->set(_t('bbs.import', 'Укажите владельца объявлений'));
                return false;
            }
            $aUserData = Users::model()->userData($settings['userId'], array('shop_id'));
            if (empty($aUserData)) {
                $this->errors->set(_t('bbs.import', 'Пользователь был указан некорректно'));
                return false;
            }
            # корректируем магазин
            if ($settings['shop'] > 0) {
                $_POST['shop_import'] = !empty($settings['shop']);
                $settings['shop'] = $this->bbs->publisherCheck($aUserData['shop_id'], 'shop_import');
            }
        }
        return $items;
    }

    /**
     * Проверка структуры XML файла
     * @param string $path путь к файлу
     * @return int кол-во объявлений в файле для импорта
     */
    protected function checkFileXML($path)
    {
        $items = 0;
        try {

            $fh = fopen($path, 'r');
            while (!feof($fh)) {
                $line = fgets($fh, 2000);
                $line = mb_strtolower($line);
                if (mb_strpos($line, '<bbs') !== false) break;
                if (mb_strpos($line, '<!doctype') !== false) {
                    $this->errors->set(_t('bbs.import', 'Invalid XML: Detected use of illegal DOCTYPE'));
                    return 0;
                }
            }
            fclose($fh);

            libxml_use_internal_errors(true);

            $reader = new XMLReader();

            # проверка наличия XML структуры
            if ( ! $reader->open($path)) {
                $this->errors->set(_t('bbs.import', 'Файл импорта не соответствует требуемой структуре (#1)'));
                return 0;
            }

            # проверка тега bbs
            do { $res = $reader->read(); } while ($res && $reader->name !== 'bbs');
            if ( ! $res || $reader->getAttribute('type') != static::ATTRIBUTE_TYPE) {
                $this->errors->set(_t('bbs.import', 'Файл импорта не соответствует требуемой структуре (#2)'));
                return 0;
            }

            do { $res = $reader->read(); } while ($res && $reader->name !== 'items'); # найдем первый тег items
            do { $res = $reader->read(); } while ($res && $reader->name !== 'item'); # найдем первый тег item
            # посчитаем количество тегов item
            do {
                if ($reader->name !== 'item') break;
                $items++;
                $res = $reader->next('item');
            } while ($res);

            $reader->close();

            $error = libxml_get_last_error();
            if ( ! empty($error)) {
                $this->errors->set(_t('bbs.import', 'Ошибка при анализе файла. ([msg])', array(
                    'msg' => trim($error->message).', line: ' . $error->line . ', col: ' . $error->column,
                )));
                return 0;
            }

            libxml_use_internal_errors(false);

        } catch (Exception $e) {
            $msg = $e->getMessage();
            bff::log('BBSItemsImport: XMLReader exception');
            bff::log($msg);
            return 0;
        }

        return $items;
    }

    /**
     * Проверка структуры XML файла
     * @param string $path путь к файлу
     * @return int кол-во объявлений в файле для импорта
     */
    protected function checkFileCSV($path)
    {
        if (!file_exists($path)) {
            $this->errors->set(_t('bbs.import', 'Файл импорта не найден'));
            return false;
        }

        $fs = @fopen($path, 'r');
        if ($fs === false) {
            $this->errors->set(_t('bbs.import', 'Ошибка открытия файла импорта'));
            return false;
        }

        $items = 0;
        $first = fgetcsv($fs, 0, ';', '"', '"');
        if (!in_array('title', $first) && !in_array('geo-city-id', $first)) {
            $items++;
        }
        while (($data = fgetcsv($fs, 0, ';', '"', '"')) !== false) {
            $items++;
        }
        fclose($fs);

        return $items;
    }

    /**
     * Поиск и обработка импорта по крону
     * Рекомендуемый период: раз в 7 минут
     */
    public function importCron()
    {
        if (static::async()) {
            # асинхронная обработка файлов
            $this->importStartTasks();
        } else {
            $this->importProcessingStep = 100;

            # обрабатываемый
            $task = $this->bbs->model->importListing(
                array('I.id'),
                array('status'=>self::STATUS_PROCESSING, 'working'=>1),
                1
            );

            # нет обрабатываемых - первый из ожидающих
            if (empty($task)) {
                $task = $this->bbs->model->importListing(
                    array('I.id'),
                    array('status'=>self::STATUS_WAITING, 'working'=>0),
                    1,
                    'I.created ASC'
                );
            }

            if (!empty($task)) {
                $task = reset($task);
                $this->importContinue($task['id']);
                return;
            }
        }

        $this->importPeriodicCron(); # Проверка периодического импорта
        $this->importCronCleanup(); # Удаление старых файлов
    }

    /**
     * Поиск и обработка периодического импорта по крону
     */
    public function importPeriodicCron()
    {
        $tasks = $this->bbs->model->importListing(array('I.id, I.settings, I.periodic_timeout'), array(
                'periodic' => self::TYPE_URL,
                array('periodic_expire < :now', ':now' => $this->db->now()),
                'parent_id' => 0,
        ));
        if (empty($tasks)) return;
        $types = $this->importPeriodOptions();
        foreach ($tasks as $v) {
            $task = func::unserialize($v['settings']);

            $expire = false;
            if (isset($types[ $v['periodic_timeout'] ]['time'])) {
                $expire = strtotime($types[ $v['periodic_timeout'] ]['time']);
            }
            if ( ! $expire) {
                $expire = strtotime('+ '.$v['periodic_timeout'].' day');
            }

            $this->errors->clear();
            $this->importUrlStart($task, $v['id']);
            $status = NULL;
            if ( ! $this->errors->no()) {
                $status = serialize(array(
                    'date' => date('Y.m.d H:i:s'),
                    'message' => $this->errors->get(true)
                ));
            }
            $this->bbs->model->importSave($v['id'], array(
                'periodic_expire' => date('Y-m-d H:i:s', $expire),
                'status_comment' => $status,
            ));
        }
    }

    /**
     * Удаление старых файлов
     */
    protected function importCronCleanup()
    {
        $days = config::sysAdmin('bbs.import.cleanup', 0, TYPE_UINT);
        if ( ! $days) return;

        $data = $this->bbs->model->importListing(array('I.id, I.filename'), array(
                'periodic' => self::TYPE_FILE,
                'status' => self::STATUS_FINISHED,
                array('status_changed < :date', ':date' => date('Y.m.d H:i:s', strtotime('-'.$days.' days'))),
                array('filename NOT NULL')
        ));
        if (empty($data)) return;

        foreach ($data as $v) {
            $file = func::unserialize($v['filename']);
            do {
                if (empty($file)) break;
                $filePath = $this->importPath . $file['filename'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            } while(false);
            $this->bbs->model->importSave($v['id'], array('filename' => NULL));
        }
    }

    /**
     * Обработка импорта по крону
     * @param integer $importID ID импорта
     * @return boolean
     */
    protected function importContinue($importID)
    {
        if (empty($importID)) return false;

        $aData = $this->bbs->model->importData($importID);
        if (empty($aData)) return false;

        # меняем статус на "обрабатывается"
        if ($aData['status'] == self::STATUS_WAITING) {
            $this->bbs->model->importSave($importID, array(
                'status'         => self::STATUS_PROCESSING,
                'status_changed' => $this->db->now(),
                'working'        => 1,
                'async_pid'      => getmypid(),
            ));
        } else {
            $this->bbs->model->importSave($importID, array(
                'status_changed' => $this->db->now(),
                'async_pid'      => getmypid(),
            ));
        }

        # проверяем настройки
        $aData['filename'] = func::unserialize($aData['filename']);
        if ( ! $aData['filename']) {
            $this->importError($importID, _t('bbs.import', 'Ошибка чтения параметров файла'));
            return false;
        }

        $aData['settings'] = func::unserialize($aData['settings']);
        if ( ! $aData['settings']) {
            $this->importError($importID, _t('bbs.import', 'Ошибка чтения параметров импорта'));
            return false;
        }
        $settings = & $aData['settings'];

        if (empty($settings['langKey'])) {
            $settings['langKey'] = LNG;
        }
        $file = $aData['filename'];

        # загружаем файл, проверяем его хеш
        $filePath = $this->importPath . $file['filename'];
        if (md5_file($filePath) != $file['hash']) {
            $this->importError($importID, _t('bbs.import', 'MD5 файла импорта не совпадает'));
            return false;
        }
        $aData['filePath'] = $filePath;

        # статистика
        if( ! isset($settings['stat'])) $settings['stat'] = array();
        $stat = & $settings['stat'];
        foreach (array('cat','title','success','updated') as $k) { if ( ! isset($stat[$k])) $stat[$k] = 0; }
        if (!isset($settings['success'])) { $settings['success'] = 0; }

        $aData['publicatedTo'] = $this->bbs->getItemPublicationPeriod(isset($settings['publicate_period']) ? $settings['publicate_period'] : 0);

        $aData['catFields'] = array('id', 'pid', 'subs', 'numlevel', 'numleft', 'numright', 'price', 'price_sett', 'addr',
            'keyword', 'landing_url', 'photos', 'regions_delivery');

        $aData['userData'] = Users::model()->userData($settings['userId'], array('name','phones','contacts'));
        if (empty($aData['userData'])) {
            $this->importError($importID, _t('bbs.import', 'Неудалось получить данные пользователя по id=[id]', array('id' => $settings['userId'])));
            return false;
        }

        $methodContinue = 'importContinue'.$aData['filename']['extension'];
        if (method_exists($this, $methodContinue)) {
            $result = $this->$methodContinue($aData);
        } else {
            $this->importError($importID, _t('bbs.import', 'Неизвестный тип файла'));
        }

        $aUpdate = array();
        if (empty($result['exist'])) {
            $aUpdate['status'] = self::STATUS_FINISHED;
            $aUpdate['status_changed'] = $this->db->now();
            if( ! empty($result['stat'])) {
                $aUpdate['status_comment'] = serialize($result['stat']);
            }
        } else {
            if( ! empty($result['stat'])) {
                $settings['stat'] = $result['stat'];
            }
        }

        $aUpdate['settings'] = serialize($settings);
        $aUpdate['items_processed'] = $aData['items_processed'];
        $aUpdate['items_ignored'] = $aData['items_ignored'];
        $aUpdate['async_pid'] = 0;
        $this->bbs->model->importSave($importID, $aUpdate);

        # обновляем счетчик объявлений на модерации
        if ( ! empty($aData['is_admin'])) {
            $this->bbs->moderationCounterUpdate();
        }

        if($aData['parent_id']){
            $this->bbs->model->importSave($aData['parent_id'], array('items_processed = items_processed + '.
                ( ! empty($aData['items_processed_parent']) ? $aData['items_processed_parent'] : $aData['items_processed'])));
        }

        return false;
    }

    /**
     * Обработка импорта файла XML по крону
     * @param array $aData @ref данные о задании
     * @return array
     */
    protected function importContinueXML(& $aData)
    {
        if (empty($aData['id'])) return false;
        $importID = $aData['id'];

        $processed = $aData['items_processed'];
        $ignored   = & $aData['items_ignored'];
        $isAdmin   = ! empty($aData['is_admin']);
        $settings = & $aData['settings'];
        $stat = & $settings['stat'];

        $dp = $this->bbs->dp();

        $import_catID  = $settings['catId'];
        $import_userID = $settings['userId'];
        $catFields = $aData['catFields'];

        $catsData = array();
        $catParents = array();
        $regionsData = array();
        if($import_catID){
            $catsData[$import_catID] = $this->bbs->model->catData($import_catID, $catFields);
            if(empty($catsData[$import_catID])){
                $this->importError($importID, _t('bbs.import', 'Неудалось получить данные категории по id=[id]', array('id' => $import_catID)));
                return false;
            }
        }
        $import_shopID = $settings['shop'];
        $priceEx = array(
            'free'=>BBS::PRICE_EX_FREE, 'exchange'=>BBS::PRICE_EX_EXCHANGE, 'mod'=>BBS::PRICE_EX_MOD, 'agreed'=>BBS::PRICE_EX_AGREED,
        );
        $aUserData = $aData['userData'];

        $reader = new XMLReader();

        if ( ! $reader->open($aData['filePath'])) {
            $this->importError($importID, _t('bbs.import', 'Неудалось открыть файл "[path]"', array('path' => $aData['filePath'])));
            return false;
        }
        $aMParams = array();
        $districtsEnabled = Geo::districtsEnabled();

        do{
            $res = $reader->read();
            if($reader->name == 'params'){
                $node = $reader->expand();
                $dom = new DomDocument();
                $n = $dom->importNode($node,true);
                $dom->appendChild($n);

                $params = $dom->getElementsByTagName('param');
                foreach ($params as $param) {
                    switch ($param->getAttribute('type')){
                        case $dp::typeRadioGroup:
                        case $dp::typeCheckboxGroup:
                        case $dp::typeSelect:
                            $field = (int)$param->getAttribute('field');
                            $parent = (int)$param->getAttribute('parent');
                            if ($parent > 0) {
                                $parents = $param->getElementsByTagName('parent');
                                if (!empty($parents)) {
                                    foreach ($parents as $p) {
                                        $pValue = $p->getAttribute('value');
                                        $aMParams[$field][$pValue] = array();
                                        $values = $p->getElementsByTagName('value');
                                        foreach ($values as $v) {
                                            $aMParams[$field][$pValue][$v->getAttribute('id')] = $v->nodeValue;
                                        }
                                    }
                                }
                            } else {
                                $values = $param->getElementsByTagName('value');
                                foreach ($values as $v) {
                                    $aMParams[$field][$v->getAttribute('id')] = $v->nodeValue;
                                }
                            }
                            break;
                    }
                }

                unset($dom);
            }
            if ($reader->name == 'items') {
                break;
            }
        } while ($res);

        $itemExist = true;
        $ic = 0;
        $start = time();
        if ($res && $reader->name == 'items') {
            do { $res = $reader->read(); } while ($res && $reader->name !== 'item');
            do {
                if($reader->name !== 'item')
                    break;

                do {
                    $ic++;
                    if ($ic < $processed) {
                        break;
                    }
                    if ($ic > $processed+$this->importProcessingStep) {
                        break 2;
                    }

                    $node = $reader->expand();
                    $dom = new DomDocument();
                    $n = $dom->importNode($node,true);
                    $dom->appendChild($n);
                    $data = $dom->getElementsByTagName('item')->item(0);

                    if (is_null($data)) break;

                    $item = array();
                    $catID = $import_catID;
                    $delivery = 0;

                    # ID объявления
                    $itemID = strval($data->getAttribute('id'));
                    $item['import_external_id'] = $itemID;
                    if (intval($data->getAttribute('external')) && $itemID) {
                        $itemExists = $this->bbs->model->itemDataByFilter(array('import_external_id' => $itemID, 'user_id' => $import_userID),
                            array('id', 'user_id', 'status', 'moderated', 'cat_id', 'city_id', 'video', 'name', 'contacts', 'phones', 'title_edit', 'imgcnt', 'price'));
                        if (!empty($itemExists)) {
                            $itemID = $itemExists['id'];
                            unset($item['import_external_id']);
                        }
                    } else {
                        $itemExists = $this->bbs->model->itemData((int)$itemID, array('user_id', 'status', 'moderated', 'cat_id', 'city_id', 'video',
                            'name', 'contacts', 'phones', 'title_edit', 'imgcnt', 'price'));
                    }

                    $itemID = (int)$itemID;

                    if (empty($itemExists)) {
                        $isUpdate = false; # создаем новое объявление
                        $itemID = 0;
                        $item['user_id'] = $import_userID;
                        $item['shop_id'] = $import_shopID;
                    } else {
                        $isUpdate = true; # обновляем существующее объявление
                        if (!$isAdmin) {
                            if ($itemExists['user_id'] != $import_userID) {
                                # объявление закреплено за другим пользователем, игнорируем <item>
                                $ignored++;
                                break;
                            }
                        }
                    }

                    $images = array();

                    $nodeKeys = array();
                    foreach($data->childNodes as $node){
                        $nodeKeys[] = strval($node->nodeName);
                        switch($node->nodeName){
                            case 'title': # заголовок
                                $title = strval($node->nodeValue);
                                $this->input->clean($title, TYPE_NOTAGS, true, array('len' => 100));
                                if(empty($title)){
                                    if($isUpdate){
                                        $title = $itemExists['title_edit'];
                                    }else{
                                        # добавление: заголовок не может быть пустым, игнорируем <item>
                                        $stat['title']++;
                                        $ignored++;
                                        break 3;
                                    }
                                }
                                $item['title_edit'] = $title;
                                $item['title'] = HTML::escape($title);
                                break;
                            case 'description': # описание
                                $item['descr'] = $this->input->cleanTextPlain(strval($node->nodeValue), 3000, false);
                                break;
                            case 'user': # пользователь
                                # игнорируем данные <user>
                                break;
                            case 'category': # категория
                                if($isUpdate){
                                    $catID = $itemExists['cat_id']; # обновление: не меняем категорию
                                }else{
                                    $v = (int)$node->nodeValue; # ID категории или 0
                                    if($v > 0) $catID = $v;
                                }
                                if(!isset($catsData[$catID])){
                                    $catsData[$catID] = $this->bbs->model->catData($catID, $catFields);
                                }
                                $cat_type = $node->getAttribute('type');
                                $item['cat_type'] = $cat_type == 'seek' ? BBS::TYPE_SEEK : BBS::TYPE_OFFER;
                                break;
                            case 'geo': # geo данные
                                # город
                                $city = $node->getElementsByTagName('city');
                                $item['city_id'] = ($city->length ? (int)$city->item(0)->getAttribute('id') : 0);
                                if($item['city_id'] <= 0 && $isUpdate) $item['city_id'] = $itemExists['city_id'];
                                # район города
                                if ($districtsEnabled) {
                                    $district = $node->getElementsByTagName('district');
                                    if ($district->length) {
                                        $item['district_id'] = (int)$district->item(0)->nodeValue;
                                    }
                                }
                                # станция метро
                                $station = $node->getElementsByTagName('station');
                                $item['metro_id'] = ($station->length ? (int)$station->item(0)->getAttribute('id') : 0);
                                # точный адрес
                                $addr = $node->getElementsByTagName('addr');
                                if ($addr->length) {
                                    $item['addr_addr'] = strval($addr->item(0)->nodeValue);
                                    $this->input->clean($item['addr_addr'], TYPE_NOTAGS, true, array('len' => 400));
                                }
                                # точный адрес: координаты на карте
                                $addr_lat = $node->getElementsByTagName('lat');
                                if($addr_lat->length) $item['addr_lat'] = floatval($addr_lat->item(0)->nodeValue);
                                $addr_lon = $node->getElementsByTagName('lon');
                                if($addr_lon->length) $item['addr_lon'] = floatval($addr_lon->item(0)->nodeValue);
                                if($node->hasAttribute('delivery')){
                                    $delivery = intval($node->getAttribute('delivery'));
                                }
                                break;
                            case 'price': # цена
                                $item['price'] = floatval($node->nodeValue);
                                $item['price_curr'] = (int)$node->getAttribute('currency');
                                $item['price_ex'] = BBS::PRICE_EX_PRICE;
                                foreach($priceEx as $k => $v){
                                    if((int)$node->getAttribute($k)){
                                        $item['price_ex'] += $v;
                                        break;
                                    }
                                }

                                $item['price_search'] = Site::currencyPriceConvertToDefault($item['price'], $item['price_curr']);
                                break;
                            case 'images': # изображения
                                $imagesList = $node->getElementsByTagName('image');
                                foreach($imagesList as $image){
                                    $images[] = array(
                                        'id' => ($image->hasAttribute('id') ? intval($image->getAttribute('id')) : 0),
                                        'url' => strval($image->nodeValue),
                                    );
                                }
                                unset($imagesList);
                                break;
                            case 'contacts': # контакты
                                # телефоны
                                $phones = array();
                                foreach($node->getElementsByTagName('phone') as $phone){
                                    $phones[] = strval($phone->nodeValue);
                                }
                                if(!empty($phones)){
                                    $phones = Users::validatePhones($phones, Users::i()->profilePhonesLimit);
                                }elseif($isUpdate){
                                    $phones = $itemExists['phones'];
                                }
                                $item['phones'] = $phones;

                                # контакты: имя, contacts
                                $fields = Users::contactsFields(true);
                                $fields[] = 'name';
                                foreach ($fields as $k) {
                                    $v = $node->getElementsByTagName($k);
                                    if ($v->length) {
                                        $v = strval($v->item(0)->nodeValue);
                                        if (empty($v)) {
                                            if ($isUpdate && isset($itemExists[$k])) {
                                                $v = $itemExists[$k];
                                            } else {
                                                $v = (!empty($aUserData[$k]) ? $aUserData[$k] : '');
                                            }
                                        }
                                        $item[$k] = $v;
                                    } else if($isUpdate && isset($itemExists[$k])) {
                                        $item[$k] = $itemExists[$k];
                                    }
                                }

                                foreach (Users::contactsFields(true) as $k) {
                                    if (isset($item[$k])) {
                                        $item['contacts'][$k] = $item[$k];
                                        unset($item[$k]);
                                    }
                                }
                                if (!isset($item['contacts']) || empty($item['contacts'])) {
                                    $item['contacts'] = [];
                                }

                                Users::i()->cleanUserData($item, array('name', 'contacts'));

                                break;
                            case 'video': # видео
                                $video = strval($node->nodeValue);
                                if(empty($video)){
                                    if(!$isUpdate){
                                        $item['video'] = '';
                                        $item['video_embed'] = '';
                                    }
                                }else{
                                    if($isUpdate){
                                        if($itemExists['video'] == $video){
                                            continue;
                                        }
                                    }
                                    $video = $this->bbs->itemVideo()->parse($video);
                                    if(!empty($video['video_url'])){
                                        $item['video'] = $video['video_url'];
                                        $item['video_embed'] = serialize($video);
                                    }
                                }
                                break;
                            case 'params': # дин. свойства
                                $params = $node->getElementsByTagName('param');
                                foreach($params as $param){
                                    $field = $param->getAttribute('field');
                                    $nodeValue = strval($param->nodeValue);
                                    switch($param->getAttribute('type')){
                                        case $dp::typeRadioGroup:
                                        case $dp::typeCheckboxGroup:
                                        case $dp::typeSelect:
                                            $paramValue = $param->getAttribute('value');
                                            if(empty($paramValue) && isset($aMParams[$field])){
                                                foreach($aMParams[$field] as $k => $v){
                                                    if(is_array($v)){
                                                        foreach($v as $k2 => $v2){
                                                            if($v2 == $nodeValue){
                                                                $paramValue = $k2;
                                                                break;
                                                            }
                                                        }
                                                    }else{
                                                        if($v == $nodeValue){
                                                            $paramValue = $k;
                                                        }
                                                    }
                                                }
                                            }
                                            break;
                                        default:
                                            $paramValue = $nodeValue;
                                            break;
                                    }
                                    $item['f'.$field] = $paramValue;
                                }
                                break;
                        } # ^ switch
                    } # ^ foreach

                    # проверяем наличие обязательных блоков данных в <item>
                    foreach(array('title', 'geo') as $k){
                        if(!in_array($k, $nodeKeys)){
                            $ignored++;
                            break 2; # игнорируем <item>
                        }
                    }

                    $this->importContinueSave($aData, $itemID, $item, $images, $isUpdate, $itemExists, $catsData, $regionsData, $catID, $priceEx, $delivery);

                } while(false);

                # обновляем статистику раз в минуту
                if ( ! $this->importContinueStatistic($start, $importID, $ic, $ignored)) {
                    break;
                }

                unset($dom);
                $itemExist = $reader->next('item');
            } while ($itemExist);
        }
        $aData['items_processed'] = $ic;
        $aData['items_processed_parent'] = $ic - $processed;

        return array(
            'exist'   => $itemExist,
            'stat'    => $stat,
        );
    }

    /**
     * Обработка импорта файла CSV по крону
     * @param array $aData @ref данные об импорте
     * @return array
     */
    protected function importContinueCSV(& $aData)
    {
        if (empty($aData['id'])) return false;
        $importID = $aData['id'];

        $processed = $aData['items_processed'];
        $ignored   = & $aData['items_ignored'];
        $isAdmin   = ! empty($aData['is_admin']);
        $settings = & $aData['settings'];
        $stat = & $settings['stat'];

        $import_catID  = $settings['catId'];
        $import_userID = $settings['userId'];
        $catFields = $aData['catFields'];

        $catsData = array();
        $catParents = array();
        $regionsData = array();
        if ($import_catID) {
            $catsData[$import_catID] = $this->bbs->model->catData($import_catID, $catFields);
            if (empty($catsData[$import_catID])) {
                $this->importError($importID, _t('bbs.import', 'Неудалось получить данные категории по id=[id]', array('id' => $import_catID)));
                return false;
            }
        }
        $import_shopID = $settings['shop'];
        $priceEx = array(
            'free'=>BBS::PRICE_EX_FREE, 'exchange'=>BBS::PRICE_EX_EXCHANGE, 'mod'=>BBS::PRICE_EX_MOD, 'agreed'=>BBS::PRICE_EX_AGREED,
        );
        $aUserData = $aData['userData'];

        # Полный список полей
        $available = static::availableCsvFields();
        $map = array();

        $encoding = config::sys('bbs.import.csv.encoding', 'CP1251');
        if ($this->checkBOM($aData['filePath'])) {
            $encoding = 'UTF-8';
        }

        $fs = @fopen($aData['filePath'], 'r');
        if ($fs === false) {
            $this->errors->set(_t('bbs.import', 'Ошибка открытия файла импорта'));
            return false;
        }

        $first = fgetcsv($fs, 0, ';', '"', '"');
        if ( ! in_array('title', $first) || ! in_array('geo-city-id', $first)){
            fclose($fs);
            $fs = @fopen($aData['filePath'], 'r');
            $first = config::sys('bbs.import.csv.default', 'item-id;title;description;category-id;geo-city-id;price;price-currency');
            $first = explode(';', $first);
        }
        # сформируем карту импорта
        foreach ($first as $k => $v) {
            if (in_array($v, $available)) {
                $map[$v] = $k;
            }
        }
        if (empty($map)) {
            $this->importError($importID, _t('bbs.import', 'Неудалось определить карту для импорта'));
            return false;
        }

        # Получение значений из csv массива согласно карте
        $value = function($field, & $data) use( & $map, $encoding){
            if (isset($map[$field])) {
                $k = $map[$field];
                if (isset($data[$k])) {
                    if ($encoding != 'UTF-8') {
                        $data[$k] = iconv($encoding, 'UTF-8//TRANSLIT', $data[$k]);
                    }
                    return $data[$k];
                }
            }
            return false;
        };

        $districtsEnabled = Geo::districtsEnabled();
        $ic = 0;
        $start = time();
        while (($csv = fgetcsv($fs, 0, ';', '"', '"')) !== false) {
            do {
                $ic++;
                if ($ic < $processed) {
                    break;
                }
                if ($ic > $processed+$this->importProcessingStep) {
                    break 2;
                }

                $item = array();
                $catID = $import_catID;
                $delivery = 0;
                $item['cat_type'] = BBS::TYPE_OFFER;
                $item['price_ex'] = BBS::PRICE_EX_PRICE;

                # ID объявления
                $itemID = $value('item-id', $csv);
                $item['import_external_id'] = $itemID;
                if ((int)$value('item-external', $csv) > 0 && $itemID) {
                    $itemExists = $this->bbs->model->itemDataByFilter(array('import_external_id' => $itemID, 'user_id' => $import_userID),
                        array('id', 'user_id', 'status', 'moderated', 'cat_id', 'city_id', 'video', 'name', 'contacts', 'phones', 'title_edit', 'imgcnt'));
                    if (!empty($itemExists)) {
                        $itemID = $itemExists['id'];
                        unset($item['import_external_id']);
                    }
                } else {
                    $itemExists = $this->bbs->model->itemData((int)$itemID, array('user_id', 'status', 'moderated', 'cat_id', 'city_id', 'video',
                        'name', 'contacts', 'phones', 'title_edit', 'imgcnt'));
                }

                $itemID = (int)$itemID;

                if (empty($itemExists)) {
                    $isUpdate = false; # создаем новое объявление
                    $itemID = 0;
                    $item['user_id'] = $import_userID;
                    $item['shop_id'] = $import_shopID;
                } else {
                    $isUpdate = true; # обновляем существующее объявление
                    if (!$isAdmin) {
                        if ($itemExists['user_id'] != $import_userID) {
                            # объявление закреплено за другим пользователем, игнорируем <item>
                            $ignored++;
                            break;
                        }
                    }
                }
                $images = array();
                foreach ($available as $f) {
                    $val = $value($f, $csv);
                    if(empty($val)) continue;

                    switch ($f) {
                        case 'title': # заголовок
                            $this->input->clean($val, TYPE_NOTAGS, true, array('len' => 100));
                            if (empty($val)) {
                                if ($isUpdate) {
                                    $val = $itemExists['title_edit'];
                                } else {
                                    # добавление: заголовок не может быть пустым, игнорируем <item>
                                    $stat['title']++;
                                    $ignored++;
                                    break 3;
                                }
                            }
                            $item['title_edit'] = $val;
                            $item['title'] = HTML::escape($val);
                            break;
                        case 'description': # описание
                            $item['descr'] = $this->input->cleanTextPlain($val, 3000, false);
                            break;
                        case 'category-id': # категория
                            if ($isUpdate) {
                                $catID = $itemExists['cat_id']; # обновление: не меняем категорию
                            } else {
                                if($val > 0) $catID = $val;
                            }
                            if ( ! isset($catsData[$catID])) {
                                $catsData[$catID] = $this->bbs->model->catData($catID, $catFields);
                            }
                            break;
                        case 'category-type':
                            $item['cat_type'] = $val == 'seek' ? BBS::TYPE_SEEK : BBS::TYPE_OFFER;
                            break;

                        case 'geo-city-id': # id города
                            $item['city_id'] = (int)$value('geo-city-id', $csv);
                            if ($item['city_id'] <= 0 && $isUpdate) $item['city_id'] = $itemExists['city_id'];
                            # район города
                            if ($districtsEnabled) {
                                $item['district_id'] = (int)$value('geo-district-id', $csv);
                            }
                            $item['metro_id'] = (int)$value('geo-station-id', $csv);
                            break;
                        case 'geo-addr':
                            $item['addr_addr'] = $this->input->clean($val, TYPE_NOTAGS, true, array('len' => 300));
                            break;
                        case 'geo-lat':
                            $item['addr_lat'] = floatval($val);
                            break;
                        case 'geo-lon':
                            $item['addr_lon'] = floatval($val);
                            break;
                        case 'geo-delivery':
                            $delivery = $val;
                            break;

                        case 'price':
                        case 'price-currency':
                            if(isset($item['price'])) break;
                            $item['price'] = floatval($value('price', $csv));
                            $item['price_curr'] = (int)$value('price-currency', $csv);
                            if(empty($item['price_curr'])){
                                $item['price_curr'] = Site::currencyDefault('id');
                            }
                            $item['price_search'] = Site::currencyPriceConvertToDefault($item['price'], $item['price_curr']);
                            break;
                        case 'price-free':
                            $item['price_ex'] += BBS::PRICE_EX_FREE;
                            break;
                        case 'price-exchange':
                            $item['price_ex'] += BBS::PRICE_EX_EXCHANGE;
                            break;
                        case 'price-mod':
                            $item['price_ex'] += BBS::PRICE_EX_MOD;
                            break;
                        case 'price-agreed':
                            $item['price_ex'] += BBS::PRICE_EX_AGREED;
                            break;
                        case 'images':
                            $img = explode(' ', $val);
                            foreach ($img as $v){
                                $v = trim($v);
                                if (empty($v)) continue;
                                if (filter_var($v, FILTER_VALIDATE_URL) === false) continue;
                                $images[] = array(
                                    'id' => 0,
                                    'url' => $v,
                                );
                            }
                            break;
                        case 'contacts-name':
                            $item['name'] = $val;
                            break;
                        case 'contacts-phones':
                            $phones = explode(';', $val);
                            foreach ($phones as & $v){
                                $v = trim($v);
                            } unset($v);
                            $phones = Users::validatePhones($phones, Users::i()->profilePhonesLimit);
                            if ( ! empty($phones)) {
                                $item['phones'] = $phones;
                            }
                            break;
                        case 'video': # видео
                            $video = $val;
                            if (empty($video)) {
                                if (!$isUpdate) {
                                    $item['video'] = '';
                                    $item['video_embed'] = '';
                                }
                            } else {
                                if ($isUpdate) {
                                    if ($itemExists['video'] == $video) {
                                        continue;
                                    }
                                }
                                $video = $this->bbs->itemVideo()->parse($video);
                                if (!empty($video['video_url'])) {
                                    $item['video'] = $video['video_url'];
                                    $item['video_embed'] = serialize($video);
                                }
                            }
                            break;
                        default:
                            if(in_array($f,  $this->csvContactsFields))
                                break;
                            bff::hook('bbs.items.import.csv.process.custom.field', array(
                                'field'=>$f, 'value'=>$val, 'csv'=>&$csv,
                                'item'=>&$item, 'itemExists'=>$itemExists,
                                'isUpdate'=>$isUpdate,
                            ));
                            break;
                    } # ^ switch
                } # ^ foreach

                foreach (array('name', 'phones') as $k) {
                    if (empty($item[$k])) {
                        if ($isUpdate) {
                            $item[$k] = $itemExists[$k];
                        } else {
                            $item[$k] = (!empty($aUserData[$k]) ? $aUserData[$k] : '');
                        }
                    }
                }
                foreach (Users::contactsFields(true) as $field) {
                    if ($isUpdate && isset($itemExists[$field])) {
                        $item[$field] = $itemExists[$field];
                    } else {
                        $item['contacts'][$field] = $value('contacts-'.$field, $csv);
                    }
                }

                Users::i()->cleanUserData($item, array('name', 'contacts'));

                # проверяем наличие обязательных данных
                foreach (array('title', 'city_id') as $k) {
                    if ( empty($item[$k])){
                        $ignored++;
                        break 2; # игнорируем <item>
                    }
                }

                $this->importContinueSave($aData, $itemID, $item, $images, $isUpdate, $itemExists, $catsData, $regionsData, $catID, $priceEx, $delivery);

            } while(false);

            # обновляем статистику раз в минуту
            if ( ! $this->importContinueStatistic($start, $importID, $ic, $ignored)) {
                break;
            }
        }
        fclose($fs);

        $aData['items_processed'] = $ic;
        $aData['items_processed_parent'] = $ic - $processed;

        return array(
            'exist'   => $csv !== false,
            'stat'    => $stat,
        );
    }

    /**
     * Проверка корректности данных для импортируемого объявления
     */
    protected function importContinueSave(& $aData, $itemID, & $item, & $images, $isUpdate, & $itemExists, & $catsData, & $regionsData, $catID, & $priceEx, $delivery)
    {
        # перезагружать изображения для существующих объявлений или нет
        static $imagesReload;
        if( ! isset($imagesReload)) {
            $imagesReload = config::sys('bbs.import.photos.reload', false, TYPE_BOOL);
        }
        $importID = $aData['id'];
        $ignored   = & $aData['items_ignored'];
        $settings = & $aData['settings'];
        $lang = $settings['langKey'];
        $isAdmin   = ! empty($aData['is_admin']);

        $stat = & $settings['stat'];

        $import_userID = $settings['userId'];
        $import_shopID = $settings['shop'];
        # тип владельца: частное лицо / бизнес
        $ownerType = ( $import_shopID ? BBS::OWNER_BUSINESS : BBS::OWNER_PRIVATE );
        # дата завершения публикации
        $publicatedTo = $aData['publicatedTo'];

        # 1. неудалось получить данные категории по ID
        # 2. есть подкатегории => необходимо указывать самую "глубокую" подкатегорию
        if(empty($catsData[$catID]) || $catsData[$catID]['subs'] > 0){
            $stat['cat']++; # игнорируем <item>
            $ignored++;
            return; # игнорируем <item>
        }

        # учитываем настройки категории:
        $catData = $catsData[$catID];
        # 1) подробный адрес и карта
        if (empty($catData['addr'])) {
            foreach(array('addr_addr', 'addr_lat', 'addr_lon') as $k){
                if(isset($item[$k])) unset($item[$k]);
            }
        }
        # 2) настройки цены
        if (!empty($catData['price'])) {
            if (!empty($item['price_ex'])) {
                if ($catData['price_sett']['ex'] <= BBS::PRICE_EX_PRICE) {
                    $item['price_ex'] = BBS::PRICE_EX_PRICE;
                } else {
                    foreach ($priceEx as $v) {
                        if (!($catData['price_sett']['ex'] & $v) && ($item['price_ex'] & $v)) {
                            $item['price_ex'] -= $v;
                        }
                    }
                }
            }
        } else {
            foreach ($item as $k => &$v) {
                if (strpos($k, 'price') === 0) unset($item[$k]);
            }
            unset($v);
        }
        # Проверка флага доставки в регионы
        if ($delivery && $catData['regions_delivery']) {
            $item['regions_delivery'] = 1;
        }

        # разворачиваем данные о регионе: city_id => reg1_country, reg2_region, reg3_city
        if (!isset($regionsData[$item['city_id']])) {
            $regionsData[$item['city_id']] = Geo::model()->regionParents($item['city_id']);
        }
        $item = array_merge($item, $regionsData[$item['city_id']]['db']);
        # reg_path
        if (!empty($item['regions_delivery'])) {
            $item['reg_path'] = '-'.$item['reg1_country'].'-ANY-';
        } else {
            $item['reg_path'] = '-'.join('-', $regionsData[$item['city_id']]['db']).'-';
        }

        # формируем URL объявления (@items.search@translit-ID.html)
        $item['keyword'] = mb_strtolower(func::translit($item['title']));
        $item['keyword'] = preg_replace("/\-+/", '-', preg_replace('/[^a-z0-9_\-]/', '', $item['keyword']));
        $item['link'] = BBS::url('items.search', array(
            'keyword' => $catData['keyword'],
            'landing_url' => $catData['landing_url'],
            'region' => $regionsData[$item['city_id']]['keys']['region'],
            'city' => $regionsData[$item['city_id']]['keys']['city'],
            'item' => array('id'=>($isUpdate ? $itemID : 0), 'keyword'=>$item['keyword'], 'event'=>'import-'.($isUpdate?'edit':'add')),
        ), true);

        if (!$isUpdate) {
            # подготавливаем ID категорий ОБ для сохранения в базу:
            # cat_id(выбранная, самая глубокая), cat_id1, cat_id2, cat_id3 ...
            $item['cat_id'] = $catID;
            if (!isset($catParents[$catID])) {
                $catParents[$catID] = $this->bbs->model->catParentsID($catData, true);
            }
            $item['cat_path'] = '-'.join('-', $catParents[$catID]).'-';
            foreach($catParents[$catID] as $k => $v){
                $item['cat_id'.$k] = $v;
            }
            # статус объявления
            if ($settings['state'] == BBS::STATUS_PUBLICATED)
            {
                $item['status'] = BBS::STATUS_PUBLICATED;
                $item['publicated'] = $this->db->now();
                $item['publicated_order'] = $this->db->now();
                $item['publicated_to'] = $publicatedTo;

                if ( ! $isAdmin) {
                    if ($import_shopID && bff::shopsEnabled() && Shops::abonementEnabled()) {
                        # проверим превышение лимита (абонемент)
                        if (Shops::i()->abonementLimitExceed($import_shopID)) {
                            $item['status'] = BBS::STATUS_PUBLICATED_OUT;
                            unset ($item['publicated'], $item['publicated_order'], $item['publicated_to']);
                        }
                    } else {
                        if (BBS::limitsPayedEnabled()) {
                            # проверим превышение лимита
                            $limit = BBS::model()->limitsPayedCategoriesForUser(array(
                                'user_id' => $import_userID,
                                'shop_id' => $import_shopID,
                                'cat_id' => $catID,
                            ));
                            if (!empty($limit)) {
                                $limit = reset($limit);
                                if ($limit['cnt'] >= $limit['limit']) {
                                    $item['status'] = BBS::STATUS_PUBLICATED_OUT;
                                }
                            }
                        }
                    }
                }
            } else {
                $item['status'] = BBS::STATUS_PUBLICATED_OUT;
                $item['publicated'] = $this->db->now();
            }
            if ($item['status'] === BBS::STATUS_PUBLICATED_OUT) {
                $item['publicated_to'] = $this->db->now();
            }
            $item['moderated'] = ($isAdmin ? 1 : 0);

            # помечаем как импортированное
            $item['import'] = $importID; # ID импорта
            # тип владельца
            $item['owner_type'] = $ownerType;
        } else {
            # игнорируем пустые поля
            foreach (array('descr', 'metro_id', 'addr_addr', 'addr_lat', 'addr_lon',) as $k) {
                if (isset($item[$k]) && empty($item[$k])) unset($item[$k]);
            }
            if (!$isAdmin) {
                if ($itemExists['status'] == BBS::STATUS_BLOCKED) {
                    $item['moderated'] = 0; # помечаем на модерацию (после блокировки)
                } else if($itemExists['moderated']) {
                    $item['moderated'] = 2; # помечаем на постмодерацию
                }
            }
            if (config::sysAdmin('bbs.import.update.republicate', false, TYPE_BOOL) && $itemExists['status'] == BBS::STATUS_PUBLICATED) {
                $item['publicated_to'] = $publicatedTo;
            }
        }

        if (BBS::translate()) {
            $item['lang'] = $lang;
        }

        # сохраняем объявление
        $itemID_Saved = $this->bbs->model->itemSave($itemID, $item);
        if ( ! $itemID_Saved) {
            bff::log(_t('bbs.import', 'Импорт объявлений:').($itemID
                    ? _t('bbs.import', 'ошибка обновления данных об объявлении #').$itemID :
                    _t('bbs.import', 'ошибка создания нового объявления')).', '._t('bbs.import', 'при импорте #').$importID);
            return;
        }
        if ( ! $isUpdate) {
            $itemID = $itemID_Saved;
            $stat['success']++; # кол-во добавленных
        } else {
            bff::hook('bbs.items.import.edit',array('id'=>$itemID,'data'=>&$item,'before'=>&$itemExists));
            $stat['updated']++; # кол-во обновленных
        }

        # обновим изображения
        $downloaded = array();
        $existsOldAlgoritm = true;
        if ($isUpdate && !empty($images)) {
            $oImages = $this->bbs->itemImages($itemID);
            $existsOldAlgoritm = false;
            foreach ($images as & $v) {
                $v['hash_url'] = md5($v['url']);
                if ($imagesReload) {
                    $ext = Files::getExtension($v['url'], true);
                    if (empty($ext)) $ext = 'jpg';
                    $tempFile = bff::path('tmp', 'images').func::generator(10).'.'.$ext;
                    $downloaded[ $v['hash_url'] ] = $tempFile;
                    if (Files::downloadFile($v['url'], $tempFile, false)){
                        $v['hash_file'] = $oImages->fileHash($tempFile);
                    }
                }
            }
            unset($v);
            $imgData = $oImages->getData();
            $key = 'hash_url';
            if ($imagesReload) {
                $key = 'hash_file';
            }
            foreach ($imgData as $k => $v) {
                if (empty($v['hash_file']) || empty($v['hash_url'])) {
                    if ($imagesReload) {
                        $oImages->deleteImage($v['id']);
                    } else {
                        $existsOldAlgoritm = true;
                    }
                    continue;
                }
                foreach ($images as $kk => $vv) {
                    if ($v[$key] == $vv[$key]) {
                        unset($imgData[$k]);
                        unset($images[$kk]);
                        break;
                    }
                }
            }
            if ($imagesReload && !empty($imgData)) {
                foreach ($imgData as $v) {
                    $oImages->deleteImage($v['id']);
                }
            }
        }

        # загружаем изображения
        if (!empty($images)) {
            $this->errors->clear();
            $oImages = $this->bbs->itemImages($itemID);
            $oImages->setAssignErrors(false);
            $imagesUploaded = ($isUpdate ? $itemExists['imgcnt'] : 0);
            foreach ($images as $image) {
                # учитываем максимально доступное кол-во фотографий в категории (настройка категории)
                if ($imagesUploaded >= $catData['photos']) {
                    continue;
                }
                # обновление: ID изображения указан и есть в базе, пропускаем
                if ($existsOldAlgoritm && $isUpdate && $image['id'] > 0 && $oImages->imageDataExists($image['id'])) {
                    continue;
                }

                if( ! empty($image['hash_url']) && isset($downloaded[ $image['hash_url'] ])){
                    # уже загрузили изображение ранее
                    $tempFile = $downloaded[ $image['hash_url'] ];
                } else {
                    # загружаем изображение по URL
                    $ext = Files::getExtension($image['url'], true);
                    if (empty($ext)) $ext = 'jpg';
                    $tempFile = bff::path('tmp', 'images').func::generator(10).'.'.$ext;
                    if ( ! Files::downloadFile($image['url'], $tempFile, false)){
                        continue;
                    }
                }

                $hash = ! empty($image['hash_file']) ? $image['hash_file'] : $oImages->fileHash($tempFile);
                $res = $oImages->uploadFromFile($tempFile, false);
                if ($res === false || file_exists($tempFile)) {
                    unlink($tempFile);
                }
                if ($res !== false) {
                    $imagesUploaded++;
                    if (!empty($res['id'])) {
                        $oImages->updateImageData($res['id'], array(
                            'hash_file' => $hash,
                            'hash_url' => md5($image['url']),
                        ));
                    }
                }
            }
        }

        # удалим временно загруженные файлы
        if( ! empty($downloaded)){
            foreach($downloaded as $v){
                if(file_exists($v)){
                    @unlink($v);
                }
            }
        }
    }

    /**
     * Обновление статистики в БД для работающей задачи. 1 раз в минуту
     * @param integer $start Время запуска
     * @param integer $importID ID импорта
     * @param integer $processed кол обрабонанных объявлений
     * @param integer $ignored кол пропущенных объявлений
     * @return bool продолжать импорт или завершить
     */
    protected function importContinueStatistic(& $start, $importID, $processed, $ignored)
    {
        if (time() - $start < 60) return true;
        $start = time();

        $data = $this->bbs->model->importData($importID);
        if ( ! in_array($data['status'], array(static::STATUS_WAITING, static::STATUS_PROCESSING))) {
            return false;
        }

        $this->bbs->model->importSave($importID, array(
            'items_processed'   => $processed,
            'items_ignored'     => $ignored,
        ));

        return true;
    }

    /**
     * Формирование шаблона для импорта
     * @param array $settings параметры
     */
    public function importTemplate(array $settings)
    {
        if (empty($settings['catId']))
            return;
        if (empty($settings['langKey'])) {
            $settings['langKey'] = LNG;
        }

        $catData = $this->bbs->model->catDataByFilter(
            array('id' => $settings['catId'], 'lang' => $settings['langKey']),
            array('title', 'numleft', 'numright', 'id', 'pid', 'numlevel')
        );

        if ( ! isset($settings['extension']) || ! in_array($settings['extension'], $this->extensionsAllowed())) {
            $settings['extension'] = 'xml';
        }

        $limit = 1;
        $filename = 'import'.'.'.$settings['extension'];
        header('Content-Disposition: attachment; filename=' . $filename);
        header("Content-Type: application/force-download");
        header('Pragma: private');
        header('Cache-control: private, must-revalidate');

        # формируем файл, аналогичный экспорту, с одним элементом в качестве примера
        $methodExport = 'exportFile'.$settings['extension'];
        if (method_exists($this, $methodExport)) {
            echo $this->$methodExport($catData, $settings['langKey'], $limit, BBS::STATUS_PUBLICATED);
        }
        exit;
    }

    /**
     * Отмена импорта по ID
     * @param integer $importID ID импорта
     */
    public function importCancel($importID)
    {

        $this->bbs->model->importUpdateByFilter(array(
            'status'    => array(self::STATUS_WAITING, self::STATUS_PROCESSING),
            'id'        => $importID,
        ), array(
            'status'         => self::STATUS_CANCELED,
            'status_changed' => $this->db->now(),
        ));
   }

    /**
     * Экспорт объявлений
     * @param array $settings параметры экспорта
     * @param bool $countOnly только подсчет кол-ва
     */
    public function export(array $settings, $countOnly = false)
    {
        if (empty($settings['catId'])) {
            $this->errors->set(_t('bbs.import', 'Категория указана некорректно'));
            return;
        }
        if (empty($settings['langKey'])) {
            $settings['langKey'] = LNG;
        }

        $filter = array();
        switch ((int)$settings['state']) {
            case 1: # только опубликованные
                $status = BBS::STATUS_PUBLICATED;
                $filter['is_publicated'] = 1;
                $filter['status'] = $status;
                break;
            default: # все
                $status = array(BBS::STATUS_PUBLICATED, BBS::STATUS_PUBLICATED_OUT);
                $filter['is_publicated'] = array('>=',0);
                $filter['status'] = $status;
        }

        $catData = $this->bbs->model->catDataByFilter(
            array('id' => $settings['catId'], 'lang' => $settings['langKey']),
            ( $countOnly ? array('numlevel') : array('title', 'numleft', 'numright', 'id', 'pid', 'addr', 'numlevel') )
        );

        if ($countOnly)
        {
            # выполняем расчет кол-ва необходимой для экспорта памяти и текущие настройки php

            # memory limit > байты
            sscanf(ini_get('memory_limit'), '%u%c', $number, $suffix);
            if (isset($suffix)) {
                $memoryLimit = $number * pow(1024, strpos('KMG', $suffix) + 1);
            }

            $filter[':cat-filter'] = $settings['catId'];
            $count = $this->bbs->model->itemsListExport($filter, true, array(
                'lang' => $settings['langKey'],
            ));

            # меньше 3 тыс. кол-во памяти сильно не меняется
            if ($count > 3000) {
                if ($count < 10000)
                    $c = 0.6;
                elseif ($count < 20000)
                    $c = 0.5;
                elseif ($count < 30000)
                    $c = 0.35;
                else
                    $c = 0.3;
                $memory = $count / 1000 * $this->export_mbPer1000 * $c;
            } else
                $memory = 35;

            $memory *= pow(1024, 2); # переводим в байты

            if ($memory > $memoryLimit) {
                $aResponse = array(
                    'count'   => tpl::declension($count, _t('bbs', 'объявление;объявления;объявлений')),
                    'warning' => _t('bbs.import', 'При выполнении экспорта с выбранными параметрами будет превышен лимит выделенной памяти [memoryLimit]. Требуется памяти - [memoryNeeded]',
                        array('memoryLimit'=>tpl::filesize($memoryLimit), 'memoryNeeded'=>tpl::filesize($memory)))
                );
            } else {
                $aResponse = array(
                    'count' => tpl::declension($count, _t('bbs', 'объявление;объявления;объявлений')),
                );
            }
            $this->bbs->ajaxResponseForm($aResponse);
        }

        $limit = false;


        if ( ! isset($settings['extension']) || ! in_array($settings['extension'], $this->extensionsAllowed())) {
            $settings['extension'] = 'xml';
        }

        $filename = 'export'.'.'.$settings['extension'];

        header('Content-Disposition: attachment; filename=' . $filename);
        header("Content-Type: application/force-download");
        header('Pragma: private');
        header('Cache-control: private, must-revalidate');

        $methodExport = 'exportFile'.$settings['extension'];
        if (method_exists($this, $methodExport)) {
            echo $this->$methodExport($catData, $settings['langKey'], $limit, $status);
        }

        exit;
    }

    /**
     * Генерирует XML файл для экспорта / для шаблона импорта объявлений
     * @param array $catData данные категории
     * @param string $langKey ключ языка выгрузки
     * @param mixed $limit лимит объявлений в выгрузке, false - без ограничений
     * @param int|array $status ограничение на выгрузку по статусу
     * @return string
     */
    protected function exportFileXML(array $catData, $langKey, $limit, $status)
    {
        $isAdmin = bff::adminPanel();
        $isImportTemplate = ($limit === 1);
        $dom = new DomDocument('1.0', 'UTF-8');

        # категория
        $categories = $dom->createElement('categories');
        $category = $dom->createElement('category', $catData['title']);
        $category->setAttribute('id', $catData['id']);
        $category->setAttribute('pid', $catData['pid']);
        $categories->appendChild($category);

        # подкатегории
        $subcats = $this->bbs->model->catChildsTree($catData['numleft'], $catData['numright'], $langKey, array('id', 'pid', 'title'));
        if ($subcats) {
            foreach ($subcats as $cat) {
                $category = $dom->createElement('category', $cat['title']);
                $category->setAttribute('id', $cat['id']);
                $category->setAttribute('pid', $cat['pid']);
                $categories->appendChild($category);
            }
        }

        # валюты
        $aCurrency = Site::model()->currencyData(false);
        $currencies = $dom->createElement('currencies');
        foreach ($aCurrency as $aCurr) {
            $curr = $dom->createElement('currency', $aCurr['title']);
            $curr->setAttribute('id', $aCurr['id']);
            $currencies->appendChild($curr);
        }

        # дин. свойства
        $this->bbs->dp()->setCurrentLanguage($langKey);
        $aDynprops = $this->bbs->dp()->getByOwner($catData['id'], true, true, false);
        $itemFields = array();
        $itemParamFields = array();
        if ($aDynprops) {
            $params = $dom->createElement('params');
            foreach ($aDynprops as $param) {
                $par = $dom->createElement('param');
                $par->setAttribute('id', $param['id']);
                $par->setAttribute('title', $param['title_' . $langKey]);
                $par->setAttribute('field', $param['data_field']);
                $par->setAttribute('type', $param['type']);
                $par->setAttribute('parent', 0);

                $itemParamFields[$param['data_field']] = array(
                    'title' => $param['title_' . $langKey],
                    'type' => $param['type'],
                );
                $itemFields[] = 'I.f' . $param['data_field'];

                if (!empty($param['multi'])) {
                    foreach ($param['multi'] as $value) {
                        if ($value['value'] != '0') {
                            $itemParamFields[$param['data_field']]['multi'][$value['value']] = $value['name'];
                            $valueField = $dom->createElement('value', $value['name']);
                            $valueField->setAttribute('id', $value['value']);
                            $par->appendChild($valueField);
                        }
                    }
                }

                $params->appendChild($par);

                if (!empty($param['parent']))
                {
                    $parent = &$param;
                    $children = array();
                    foreach ($parent['multi'] as $value) {
                        $children[] = array('parent_id'=>$parent['id'], 'parent_value'=>$value['value']);
                    }
                    $children = $this->bbs->dp()->getByParentIDValuePairs($children);
                    if (empty($children[$parent['id']])) continue;
                    $children = $children[$parent['id']];
                    $child = current($children);

                    $par2 = $dom->createElement('param');
                    $par2->setAttribute('id', $child['id']);
                    $par2->setAttribute('title', $parent['child_title']);
                    $par2->setAttribute('field', $child['data_field']);
                    $par2->setAttribute('type', $child['type']);
                    $par2->setAttribute('parent', $parent['id']);

                    $itemParamFields[$child['data_field']] = array(
                        'title' => $parent['child_title'],
                        'type'  => $child['type'],
                        'multi' => array(),
                        'parent' => $parent['data_field'],
                    );
                    $itemParamFieldsChild = &$itemParamFields[$child['data_field']];
                    $itemFields[] = 'I.f' . $child['data_field'];

                    if (!empty($parent['multi'])) {
                        foreach ($parent['multi'] as $value) {
                            if ($value['value'] != '0') {
                                $itemParamFieldsChild['multi'][$value['value']] = array();
                                if (empty($children[$value['value']]['multi'])) continue;
                                $selectField = $dom->createElement('parent');
                                $selectField->setAttribute('value', $value['value']);
                                $selectField->setAttribute('name', $value['name']);
                                foreach ($children[$value['value']]['multi'] as $value2) {
                                    $optionField = $dom->createElement('value', $value2['name']);
                                    $optionField->setAttribute('id', $value2['value']);
                                    $selectField->appendChild($optionField);
                                    $itemParamFieldsChild['multi'][$value['value']][$value2['value']] = $value2['name'];
                                }
                                $par2->appendChild($selectField);
                            }
                        }
                    }

                    $params->appendChild($par2);
                }
            }
        }

        # регионы, районы, станции метро
        $coveringType = Geo::coveringType();
        $aFilter = array('main>0', 'enabled' => 1);
        $sOrderBy = 'main';
        switch ($coveringType) {
            case Geo::COVERING_COUNTRY:
                $aFilter['country'] = Geo::coveringRegion();
                break;
            case Geo::COVERING_REGION:
                $aFilter['pid'] = Geo::coveringRegion();
                break;
            case Geo::COVERING_CITIES:
                $aFilter['id'] = Geo::coveringRegion();
                $sOrderBy = 'FIELD(R.id, ' . join(',', $aFilter['id']) . ')'; /* MySQL only */
                unset($aFilter[0]); # main>0
                break;
            case Geo::COVERING_CITY:
                $aFilter['id'] = Geo::coveringRegion();
                break;
        }
        $districtsEnabled = Geo::districtsEnabled();
        $aCities = Geo::model()->regionsListingData(Geo::lvlCity, $aFilter, $langKey, $sOrderBy);
        if ($aCities) {
            $aCityId = array_keys($aCities);
            $aStations = Geo::model()->metroStationsList(array('city_id' => $aCityId), $langKey);
            $aDistricts = array();
            if ($districtsEnabled) {
                $tmp = Geo::model()->districtsList(0, array('city_id' => $aCityId), array(), $langKey);
                foreach ($tmp as $v) {
                    $aDistricts[ $v['city_id'] ][ $v['id'] ] = $v;
                }
            }
            $aMetro = array();
            if ($aStations) {
                foreach ($aStations as $station) {
                    $aMetro[ $station['city_id'] ][] = $station;
                }
            }
            $cities = $dom->createElement('cities');
            foreach ($aCities as $aCity) {
                $city = $dom->createElement('city');
                $cityTitle = $dom->createElement('title', $aCity['title']);
                $city->appendChild($cityTitle);
                $city->setAttribute('id', $aCity['id']);
                $city->setAttribute('region', $aCity['parentTitle']);
                if (isset($aMetro[$aCity['id']])) {
                    $metro = $dom->createElement('metro');
                    foreach ($aMetro[$aCity['id']] as $aM) {
                        $station = $dom->createElement('station', $aM['title']);
                        $station->setAttribute('id', $aM['id']);
                        $metro->appendChild($station);
                    }
                    $city->appendChild($metro);
                }
                if (isset($aDistricts[$aCity['id']])) {
                    $districts = $dom->createElement('districts');
                    foreach ($aDistricts[$aCity['id']] as $aD) {
                        $district = $dom->createElement('district', $aD['t']);
                        $district->setAttribute('id', $aD['id']);
                        $districts->appendChild($district);
                    }
                    $city->appendChild($districts);
                }
                $cities->appendChild($city);
            }
        }

        # объявления
        $itemsFilter = array(
            'status'  => $status,
        );
        if (is_array($status)) {
            $itemsFilter['is_publicated'] = array('>=',0);
        } else if ($status === BBS::STATUS_PUBLICATED) {
            $itemsFilter['is_publicated'] = 1;
        }

        $itemsFilter[':cat-filter'] = $catData['id'];

        $aData = $this->bbs->model->itemsListExport($itemsFilter, false, array(
            'lang'   => $langKey,
            'fields' => $itemFields,
            'limit'  => $limit,
        ));
        if ($aData)
        {
            $aItemId = array_keys($aData);
            $i = new BBSItemImages();
            $aImage = $i->getItemsImagesData($aItemId);
            $imagesKey = $i->getMaxSizeKey();

            $items = $dom->createElement('items');
            foreach ($aData as &$v)
            {
                $item = $dom->createElement('item');
                $item->setAttribute('id', ($isAdmin ? $v['id'] : mt_rand(1,1000))); # ID объявления
                $item->setAttribute('external', 0);

                # заголовок
                if ( ! $isAdmin) $v['title'] = _t('bbs.import', 'Заголовок объявления');
                $item->appendChild( $dom->createElement('title', $v['title']) );
                # описание
                if ( ! $isAdmin) $v['descr'] = _t('bbs.import', 'Подробное описание объявления');
                $item->appendChild( $dom->createElement('description', $this->escapeXML($v['descr'])) );

                # пользователь (владелец объявления)
                if ($isAdmin) {
                    $user = $dom->createElement('user', $v['email']);
                    $user->setAttribute('id', $v['user_id']);
                    $user->setAttribute('shop', $v['shop_id']);
                    $item->appendChild($user);
                }

                # категория
                $cat = $dom->createElement('category', $v['cat_id']);
                if ($v['cat_id'] == $catData['id'])
                    $cat->setAttribute('title', $catData['title']);
                else
                    $cat->setAttribute('title', $subcats[$v['cat_id']]['title']);
                $cat->setAttribute('type', $v['cat_type'] == BBS::TYPE_SEEK ? 'seek' : 'offer');
                $item->appendChild($cat);

                # geo
                $geo = $dom->createElement('geo');
                $geo->setAttribute('delivery', $v['regions_delivery']);
                $geo_city = $dom->createElement('city', $v['city_title']);
                $geo_city->setAttribute('id', $v['city_id']);
                $geo_station = $dom->createElement('station', $v['metro_title']);
                $geo_station->setAttribute('id', $v['metro_id']);
                $geo->appendChild($geo_city);
                $geo->appendChild($geo_station);
                if($districtsEnabled && $v['district_id']){
                    $geo->appendChild( $dom->createElement('district', $v['district_id']) );
                }
                $geo->appendChild( $dom->createElement('addr', $this->escapeXML($v['addr_addr'])) );
                $geo->appendChild( $dom->createElement('lat', $v['addr_lat']) );
                $geo->appendChild( $dom->createElement('lon', $v['addr_lon']) );
                $item->appendChild($geo);

                # цена
                $price = $dom->createElement('price', $v['price']);
                $price->setAttribute('currency', $v['price_curr']);
                $price->setAttribute('free', ($v['price_ex'] & BBS::PRICE_EX_FREE));
                $price->setAttribute('exchange', ($v['price_ex'] & BBS::PRICE_EX_EXCHANGE));
                $price->setAttribute('mod', ($v['price_ex'] & BBS::PRICE_EX_MOD));
                $price->setAttribute('agreed', ($v['price_ex'] & BBS::PRICE_EX_AGREED));
                $item->appendChild($price);

                # изображения
                if (isset($aImage[$v['id']])) {
                    $images = $dom->createElement('images');
                    if ( ! $isAdmin) {
                        # пример пользователю: оставляем 2 изображения
                        $aImage[$v['id']] = array_slice($aImage[$v['id']], 0, 2, true);
                    }
                    $i->setRecordID($v['id']);
                    foreach ($aImage[$v['id']] as $image) {
                        $url = $i->getURL($image, $imagesKey);
                        if (mb_strpos($url, '//') === 0) {
                            $url = Request::scheme() . ':' . $url;
                        }
                        $img = $dom->createElement('image', $url);
                        $img->setAttribute('id', $image['id']);
                        $images->appendChild($img);
                    }
                    $item->appendChild($images);
                }

                # контакты: name, phones, contacts
                # игнорируем <contacts> при импорте и включенной настройке "отображать контакты объявления из профиля"
                if (!$isImportTemplate || !$this->bbs->getItemContactsFromProfile())
                {
                    $contacts = $dom->createElement('contacts');

                    # name:
                    $contacts->appendChild( $dom->createElement('name', $v['name']) );

                    # phones:
                    $v['phones'] = func::unserialize( !empty($v['phones']) ? $v['phones'] : $v['u_phones'] );
                    if ($v['phones']) {
                        $phones = $dom->createElement('phones');
                        foreach ($v['phones'] as $phone) {
                            if (is_array($phone))
                                $phone = $phone['v'];
                            $ph = $dom->createElement('phone', $phone);
                            $phones->appendChild($ph);
                        }
                        $contacts->appendChild($phones);
                    }

                    # contacts:
                    foreach (Users::contactsFields(true) as $contactKey) {
                        if ( ! empty($v['contacts'][$contactKey])) {
                            $contacts->appendChild($dom->createElement($contactKey, $v['contacts'][$contactKey]));
                        } else if ( ! empty($v['u_contacts'][$contactKey]) && ! $isAdmin) {
                            $contacts->appendChild($dom->createElement($contactKey, $v['u_contacts'][$contactKey]));
                        }
                    }

                    $item->appendChild($contacts);
                }

                # видео
                $item->appendChild( $dom->createElement('video', $this->escapeXML($v['video'])) );

                # дин. свойства
                $itemParams = $dom->createElement('params');
                foreach ($itemParamFields as $fieldId => $value) {
                    $field = 'f' . $fieldId;
                    if (!isset($value['multi'])) {
                        $paramValue = $v[$field];
                        $fieldValue = 0;
                    } else {
                        if (!empty($value['parent'])) {
                            $paramValueParent = (isset($v['f'.$value['parent']]) ? $v['f'.$value['parent']] : 0);
                            $paramValue = (isset($value['multi'][$paramValueParent][$v[$field]]) ? $value['multi'][$paramValueParent][$v[$field]] : 0);
                        } else {
                            $paramValue = (isset($value['multi'][$v[$field]]) ? $value['multi'][$v[$field]] : 0);
                        }
                        $fieldValue = $v[$field];
                    }

                    $itemParam = $dom->createElement('param', $paramValue);
                    $itemParam->setAttribute('field', $fieldId);
                    $itemParam->setAttribute('type', $value['type']);
                    $itemParam->setAttribute('value', $fieldValue);
                    $itemParam->setAttribute('title', $value['title']);

                    $itemParams->appendChild($itemParam);
                }
                $item->appendChild($itemParams);

                $items->appendChild($item);
            }
            unset($v);
        }

        $bbs = $dom->createElement('bbs');
        $bbs->setAttribute('type', self::ATTRIBUTE_TYPE);
        $bbs->appendChild( $dom->createElement('title', $this->escapeXML(Site::title('bbs.export', $langKey, SITEHOST))) );
        $bbs->appendChild( $dom->createElement('url', SITEHOST) );
        $bbs->appendChild( $dom->createElement('locale', $langKey) );
        $bbs->appendChild($categories);
        $bbs->appendChild($currencies);
        if (!empty($cities))
            $bbs->appendChild($cities);
        if (!empty($params))
            $bbs->appendChild($params);
        if (!empty($items))
            $bbs->appendChild($items);
        $dom->appendChild($bbs);

        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    /**
     * Генерирует CSV файл для экспорта / для шаблона импорта объявлений
     * @param array $catData данные категории
     * @param string $langKey ключ языка выгрузки
     * @param mixed $limit лимит объявлений в выгрузке, false - без ограничений
     * @param int|array $status ограничение на выгрузку по статусу
     * @return string
     */
    protected function exportFileCSV(array $catData, $langKey, $limit, $status)
    {
        $isAdmin = bff::adminPanel();
        $isImportTemplate = ($limit === 1);
        $encoding = config::sys('bbs.import.csv.encoding', 'CP1251');

        $out = fopen('php://output', 'w');
        if ($encoding == 'UTF-8') {
            fputs($out,  pack('CCC',0xef,0xbb,0xbf));
        }
        $fields = static::availableCsvFields();
        if (version_compare(PHP_VERSION, '5.5.4') >= 0) {
            fputcsv($out, $fields, ';', '"', '"');
        } else {
            fputcsv($out, $fields, ';', '"');
        }

        # объявления
        $itemsFilter = array(
            'status'  => $status,
        );
        if (is_array($status)) {
            $itemsFilter['is_publicated'] = array('>=',0);
        } else if ($status === BBS::STATUS_PUBLICATED) {
            $itemsFilter['is_publicated'] = 1;
        }
        $itemsFilter[':cat-filter'] = $catData['id'];

        $districtsEnabled = Geo::districtsEnabled();

        # Получение значений для csv массива
        $value = function($value) use($encoding){
            if (empty($value)) return '';
            if ($encoding != 'UTF-8') {
                $value = iconv('UTF-8', $encoding,  $value);
            }
            return $value;
        };

        $aData = $this->bbs->model->itemsListExport($itemsFilter, false, array(
            'lang'  => $langKey,
            'limit' => $limit,
        ));
        if ($aData) {
            $aItemId = array_keys($aData);
            $i = new BBSItemImages();
            $aImage = $i->getItemsImagesData($aItemId);
            $imagesKey = $i->getMaxSizeKey();

            foreach ($aData as $v) {
                $item = array();
                $item['item-id'] = $isAdmin ? $v['id'] : mt_rand(1,1000);
                $item['item-external'] = 0;

                # заголовок
                $item['title'] = $value( $isAdmin ? $v['title'] : _t('bbs.import', 'Заголовок объявления'));
                $item['description'] = $value( $isAdmin ? $v['descr'] : _t('bbs.import', 'Подробное описание объявления'));

                # категория
                $item['category-id'] = $v['cat_id'];
                $item['category-type'] = $v['cat_type'] == BBS::TYPE_SEEK ? 'seek' : 'offer';

                # geo
                $item['geo-delivery'] = $v['regions_delivery'];
                $item['geo-city-id'] = $v['city_id'];
                $item['geo-station-id'] = $v['metro_id'];
                $item['geo-district-id'] = $districtsEnabled ? $v['district_id'] : 0;
                $item['geo-addr'] = $value($v['addr_addr']);
                $item['geo-lat'] = $v['addr_lat'];
                $item['geo-lon'] = $v['addr_lon'];

                # цена
                $item['price'] = $v['price'];
                $item['price-currency'] = $v['price_curr'];
                $item['price-free'] = $v['price_ex'] & BBS::PRICE_EX_FREE ? 1 : 0;
                $item['price-exchange'] = $v['price_ex'] & BBS::PRICE_EX_EXCHANGE ? 1 : 0;
                $item['price-mod'] = $v['price_ex'] & BBS::PRICE_EX_MOD ? 1 : 0;
                $item['price-agreed'] = $v['price_ex'] & BBS::PRICE_EX_AGREED ? 1 : 0;

                $images = array();
                # изображения
                if (isset($aImage[$v['id']])) {
                    if ( ! $isAdmin) {
                        # пример пользователю: оставляем 2 изображения
                        $aImage[$v['id']] = array_slice($aImage[$v['id']], 0, 2, true);
                    }
                    $i->setRecordID($v['id']);
                    foreach ($aImage[$v['id']] as $image) {
                        $url = $i->getURL($image, $imagesKey);
                        if (mb_strpos($url, '//') === 0) {
                            $url = Request::scheme() . ':' . $url;
                        }
                        $images[] = $url;
                    }
                }
                $item['images'] = join(' ', $images);

                # контакты: name, phones, contacts
                # игнорируем <contacts> при импорте и включенной настройке "отображать контакты объявления из профиля"
                if (!$isImportTemplate || !$this->bbs->getItemContactsFromProfile()) {

                    # name:
                    $item['contacts-name'] = $value($v['name']);

                    # phones:
                    $v['phones'] = func::unserialize( !empty($v['phones']) ? $v['phones'] : $v['u_phones'] );
                    $phones = array();
                    if ($v['phones']) {
                        foreach ($v['phones'] as $phone) {
                            if (is_array($phone))
                                $phones[] = $phone['v'];
                        }
                    }
                    $item['contacts-phones'] = join(';', $phones);

                    # contacts:
                    foreach (Users::contactsFields(true) as $contactKey) {
                        if ( ! empty($v['contacts'][$contactKey])) {
                            $item['contacts-'.$contactKey] = $v['contacts'][$contactKey];
                        } else if ( ! empty($v['u_contacts'][$contactKey]) && ! $isAdmin) {
                            $item['contacts-'.$contactKey] = $v['u_contacts'][$contactKey];
                        }
                    }
                }
                $item['video'] = $v['video'];
                $save = array();
                foreach ($fields as $f) {
                    $save[$f] = isset($item[$f]) ? $item[$f] : '';
                }
                if (version_compare(PHP_VERSION, '5.5.4') >= 0) {
                    fputcsv($out, $save, ';', '"', '"');
                } else {
                    fputcsv($out, $save, ';', '"');
                }
            }
        }
        fclose($out);
    }

    /**
     * Отменяем все импорты пользователя
     * @param int $nUserID ID пользователя
     */
    public function cancelUserImport($nUserID)
    {
        $this->bbs->model->importUpdateByFilter(array(
            'status'  => array(self::STATUS_WAITING, self::STATUS_PROCESSING),
            'user_id' => $nUserID
        ), array(
            'status'         => self::STATUS_CANCELED,
            'status_changed' => $this->db->now(),
            'status_comment' => serialize(array('message'=>_t('bbs.import', 'Блокировка пользователя')))
        ));
    }

    /**
     * Закрываем задачу с кодом и комментарием ошибки
     * @param integer $importID ID импорта
     * @param string $message комментарий для администратора
     */
    protected function importError($importID, $message = '')
    {
        if (empty($importID)) return;
        if (!empty($message)) {
            $this->bbs->model->importSave($importID, array(
                'status'         => self::STATUS_ERROR,
                'status_changed' => $this->db->now(),
                'status_comment' => serialize(array('message'=>$message))
            ));
            bff::log(_t('bbs.import', 'Импорт объявлений: ошибка при обработке импорта #[id], [msg]', array('id' => $importID, 'msg' => $message)));
        }
    }

    /**
     * Возвращает путь к директории с файлами импорта (файлу импорта)
     * @param bool $url - true вернуть в виде url, false - если абсолютный путь
     * @param string $filename имя файла или пустая строка
     * @return string
     */
    public static function getImportPath($url = false, $filename = '')
    {
        if (!$url) {
            return bff::path('import') . $filename;
        } else {
            $url = bff::url('import') . $filename;
            if (mb_strpos($url, '//') === 0) {
                $url = Request::scheme() . ':' . $url;
            }
            return $url;
        }
    }

    /**
     * Формирование списка доступных статусов импорта с описанием
     * @return array
     */
    public static function getStatusList()
    {
         return array(
            self::STATUS_WAITING      => _t('bbs.import','ожидает'),
            self::STATUS_PROCESSING   => _t('bbs.import','в процессе'),
            self::STATUS_FINISHED     => _t('bbs.import','завершён'),
            self::STATUS_CANCELED     => _t('bbs.import','отменён'),
            self::STATUS_ERROR        => _t('bbs.import','завершён с ошибкой')
         );
    }

    /**
     * Генерирует XML файл для экспорта на печать по ID объявлений
     * @param array $aItemsFilter фильтр объявлений для экспорта
     * @param string $langKey ключ языка выгрузки
     * @return string
     */
    public function exportPrintXML(array $aItemsFilter, $langKey = LNG)
    {
        $dom = new \DomDocument('1.0', 'UTF-8');

        $aItemsData = $this->bbs->model->itemsListExportPrint($aItemsFilter, $langKey);
        if (!empty($aItemsData))
        {
            $i = new BBSItemImages();
            $aImage = $i->getItemsImagesData(array_keys($aItemsData));
            $imagesKey = $i->getMaxSizeKey();

            $aCurrency = Site::model()->currencyData(false);
            $aDynprops = array();

            $items = $dom->createElement('items');
            foreach ($aItemsData as &$v)
            {
                $item = $dom->createElement('item');
                $item->setAttribute('id', $v['id']); # ID объявления

                # заголовок
                $item->appendChild( $dom->createElement('title', $v['title']) );
                # описание
                $item->appendChild( $dom->createElement('description', $this->escapeXML($v['descr'])) );

                # пользователь (владелец объявления)
                $user = $dom->createElement('user', $v['email']);
                $user->setAttribute('id', $v['user_id']);
                $user->setAttribute('shop', $v['shop_id']);
                $item->appendChild($user);

                # категория
                $cat = $dom->createElement('category', $v['category']);
                $cat->setAttribute('id', $v['cat_id']);
                $cat->setAttribute('pid', $v['cat_id1']);
                $item->appendChild($cat);

                $cat = $dom->createElement('category_path', $v['category_path']);
                $item->appendChild($cat);

                # geo
                $geo = $dom->createElement('geo');
                $geo_country = $dom->createElement('country', $v['country']);
                $geo_country->setAttribute('id', $v['reg1_country']);
                $geo->appendChild($geo_country);
                $geo_region = $dom->createElement('region', $v['region']);
                $geo_region->setAttribute('id', $v['reg2_region']);
                $geo->appendChild($geo_region);
                $geo_city = $dom->createElement('city', $v['city']);
                $geo_city->setAttribute('id', $v['reg3_city']);
                $geo->appendChild($geo_city);
                if ($v['metro_id']) {
                    $geo_station = $dom->createElement('station', $v['metro']);
                    $geo_station->setAttribute('id', $v['metro_id']);
                    $geo->appendChild($geo_station);
                }
                $geo->appendChild($dom->createElement('addr', $this->escapeXML($v['addr_addr'])));
                $geo->appendChild($dom->createElement('lat', $v['addr_lat']));
                $geo->appendChild($dom->createElement('lon', $v['addr_lon']));
                $item->appendChild($geo);

                # цена
                if (!empty($aCurrency[ $v['price_curr'] ])) {
                    $price = $dom->createElement('price', $v['price']);
                    $price->setAttribute('currency', $v['price_curr']);
                    $price->setAttribute('free', ($v['price_ex'] & BBS::PRICE_EX_FREE));
                    $price->setAttribute('exchange', ($v['price_ex'] & BBS::PRICE_EX_EXCHANGE));
                    $price->setAttribute('mod', ($v['price_ex'] & BBS::PRICE_EX_MOD));
                    $price->setAttribute('agreed', ($v['price_ex'] & BBS::PRICE_EX_AGREED));
                    $price->setAttribute('title', $aCurrency[$v['price_curr']]['title']);
                    $price->setAttribute('short', $aCurrency[$v['price_curr']]['title_short']);
                    $item->appendChild($price);
                }

                # изображения
                if (isset($aImage[$v['id']])) {
                    $images = $dom->createElement('images');
                    $i->setRecordID($v['id']);
                    foreach ($aImage[$v['id']] as $image) {
                        $url = $i->getURL($image, $imagesKey);
                        if (mb_strpos($url, '//') === 0) {
                            $url = Request::scheme() . ':' . $url;
                        }
                        $img = $dom->createElement('image', $url);
                        $img->setAttribute('id', $image['id']);
                        $images->appendChild($img);
                    }
                    $item->appendChild($images);
                }

                # контакты: name, phones, contacts
                $contacts = $dom->createElement('contacts');

                # name:
                $contacts->appendChild($dom->createElement('name', $v['name']));

                # phones:
                $v['phones'] = func::unserialize( $this->bbs->getItemContactsFromProfile() ? $v['u_phones'] : $v['phones'] );
                if ($v['phones']) {
                    $phones = $dom->createElement('phones');
                    foreach ($v['phones'] as $phone) {
                        if (is_array($phone))
                            $phone = $phone['v'];
                        $ph = $dom->createElement('phone', $phone);
                        $phones->appendChild($ph);
                    }
                    $contacts->appendChild($phones);
                }

                # contacts:
                foreach (Users::contactsFields(true) as $field) {
                    if ($this->bbs->getItemContactsFromProfile() && !empty($v['u_contacts'][$field])) {
                        $contacts->appendChild($dom->createElement($field, $v['u_contacts'][$field]));
                    } else if (!empty($v['contacts'][$field])) {
                        $contacts->appendChild($dom->createElement($field, $v['contacts'][$field]));
                    }
                }
                $item->appendChild($contacts);

                # видео
                if (!empty($v['video'])) {
                    $item->appendChild($dom->createElement('video', $this->escapeXML($v['video'])));
                }

                # дин. свойства
                if (!isset($aDynprops[ $v['cat_id'] ])) {
                    $aDynprops[$v['cat_id']] = $this->bbs->dp()->getByOwner($v['cat_id'], true, true, false);
                }
                $itemParams = $dom->createElement('params');
                foreach ($aDynprops[$v['cat_id']] as &$vv) {
                    $field = 'f'.$vv['data_field'];

                    if ( ! empty($vv['multi'])) {
                        $paramValue = 0;
                        foreach ($vv['multi'] as $vvm) {
                            if ($vvm['value'] == $v[$field]) {
                                $paramValue = $vvm['name']; break;
                            }
                        }
                        $fieldValue = $v[ $field ];
                    } else {
                        $paramValue = $v[ $field ];
                        $fieldValue = false;
                    }

                    $itemParam = $dom->createElement('param', $paramValue);
                    $itemParam->setAttribute('type', $vv['type']);
                    if ($fieldValue !== false) {
                        $itemParam->setAttribute('value', $fieldValue);
                    }
                    $itemParam->setAttribute('title', $vv['title']);

                    $itemParams->appendChild($itemParam);

                    if (!empty($vv['parent']) && !empty($vv['multi']) && !empty($fieldValue)) {
                        if (!isset($vv['child_data'])) {
                            $vv['child_data'] = array();
                            foreach ($vv['multi'] as $vvm) {
                                $vv['child_data'][] = array('parent_id'=>$vv['id'], 'parent_value'=>$vvm['value']);
                            }
                            $vv['child_data'] = $this->bbs->dp()->getByParentIDValuePairs($vv['child_data']);
                            $vv['child_data'] = (!empty($vv['child_data'][$vv['id']]) ? $vv['child_data'][$vv['id']] : array());
                        }
                        if (empty($vv['child_data'])) continue;
                        $child = current($vv['child_data']);
                        $childValue = (isset($v['f'.$child['data_field']]) ? $v['f'.$child['data_field']] : 0);
                        if (empty($childValue) || empty($vv['child_data'][$fieldValue]['multi'])) continue;
                        $itemParamChild = $dom->createElement('param');
                        $itemParamChild->setAttribute('type', $child['type']);
                        $itemParamChild->setAttribute('value', $childValue);
                        $itemParamChild->setAttribute('title', $vv['child_title']);
                        foreach ($vv['child_data'][$fieldValue]['multi'] as $vvm) {
                            if ($vvm['value'] == $childValue) {
                                $itemParamChild->nodeValue = $vvm['name'];
                                $itemParams->appendChild($itemParamChild);
                                break;
                            }
                        }
                    }

                } unset ($vv);
                $item->appendChild($itemParams);

                $items->appendChild($item);
            }
            unset($v);
        }

        $bbs = $dom->createElement('bbs');
        $bbs->setAttribute('type', 'items-export-press');
        $bbs->setAttribute('date', date(DATE_ISO8601));
        $bbs->appendChild( $dom->createElement('title', $this->escapeXML(Site::title('bbs.export.print', $langKey, SITEHOST))) );
        $bbs->appendChild( $dom->createElement('url', SITEHOST) );
        $bbs->appendChild( $dom->createElement('locale', $langKey) );
        if (!empty($items)) {
            $bbs->appendChild($items);
        }
        $dom->appendChild($bbs);
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    /**
     * Период загрузки импорта файлов по URL
     * @param boolean $bSelectOptions в формате HTML::selectOptions
     * @param integer $nSelectedID ID выбранного типа
     * @return array|string
     */
    public static function importPeriodOptions($bSelectOptions = false, $nSelectedID = 0)
    {
        $aTypes = bff::filter('bbs.items.import.period.list', array(
            1 => array(
                'title' => _t('bbs.import', '1 день'),
                'time' => '+1 days',
            ),
            7 => array(
                'title' => _t('bbs.import', '7 дней'),
                'time' => '+7 days',
            ),
            14 => array(
                'title' => _t('bbs.import', '2 недели'),
                'time' => '+14 days',
            ),
            30 => array(
                'title' => _t('bbs.import', 'месяц'),
                'time' => '+30 days',
            ),
        ));
        func::sortByPriority($aTypes);
        foreach ($aTypes as $k => & $v) {
            $v['id'] = $k;
        } unset($v);
        if ($bSelectOptions) {
            return HTML::selectOptions($aTypes, $nSelectedID, false, 'id', 'title');
        }

        return $aTypes;
    }

    /**
     * Эскейпим строку для XML
     * @param string $string
     * @return string string
     */
    public function escapeXML($string)
    {
        return htmlspecialchars($string, ENT_XML1, 'UTF-8');
    }

    /**
     * Определение наличия заголовка BOM (Byte order mark) UTF8 в начале файла или строки
     * @param string $filePath путь к файлу
     * @param bool $isString флаг если строка
     * @return bool true - есть
     */
    public function checkBOM($filePath, $isString = false)
    {
        if ( ! $isString) {
            if ( ! file_exists($filePath)) {
                return false;
            }
            $filePath = file_get_contents($filePath, NULL, NULL, 0, 10);
            if ($filePath === FALSE) {
                return false;
            }
        } else {
            $filePath = strval($filePath);
        }
        return (substr($filePath,0,3) == pack("CCC",0xef,0xbb,0xbf));
    }
}