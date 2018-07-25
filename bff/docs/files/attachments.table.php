<?php namespace bff\files;

/**
 * Компонент управляющий загрузкой / сохранением / удалением нескольких файлов вложений
 *
 * Для хранения информации о загруженных файлах
 * используется отдельная таблица {$tableAttachments}
 *
 * Структура таблицы записей {$tableRecords} предполагает наличие следующий обязательных столбцов:
 * id - ID записи, настройка {$tableRecords_id}
 * attachcnt - счетчик кол-ва загруженных файлов, настройка {$tableRecords_cnt}
 *
 * @abstract
 * @version 0.25
 * @modified 9.jan.2016
 */

abstract class AttachmentsTable extends \Component
{
    /** @var integer ID пользователя */
    protected $userID = 0;
    /** @var integer ID записи */
    protected $recordID = 0;

    /**
     * Кол-во символов в генерируемой части названия сохраняемого файла
     * @var integer
     */
    protected $filenameLetters = 6;
    /**
     * Раскладывать ли файлы в зависимости от recordID, по папкам folder = (recordID / 1000)
     * @var boolean
     */
    protected $folderByID = false;

    /** @var string Путь к файлам */
    protected $path = '';
    /** @var string Путь ко временным файлам */
    protected $pathTmp = '';
    /** @var string URL к файлам */
    protected $url = '';
    /** @var string URL ко временным файлам */
    protected $urlTmp = '';
    /** @var boolean сообщать об ошибках */
    protected $assignErrors = true;

    /** @var string Название таблицы для хранения данных о записи */
    protected $tableRecords = '';
    protected $tableRecords_id = 'id'; # поле для хранения данных о записи
    protected $tableRecords_cnt = 'attachcnt';

    /** @var string Название таблицы для хранения данных о загруженных файлах */
    protected $tableAttachments = '';
    protected $tableAttachments_record = 'item_id'; # поле для хранения ID записи в {$tableAttachments}

    /**
     * Максимально доступное кол-во файлов у одной записи
     * 0 - неограничено
     * @var integer
     */
    protected $limit = 5;

    /**
     * Максимально допустимый размер файла
     * @example: 5242880 - 5мб, 4194304 - 4мб, 3145728 - 3мб, 2097152 - 2мб
     */
    protected $maxSize = 5242880;

    public function __construct($recordID = 0)
    {
    }

    abstract protected function initSettings();

    public function setRecordID($recordID)
    {
    }

    public function setUserID($userID)
    {
    }

    /**
     * Получение максимально допустимого размера файла
     * @param boolean $format применить форматирование
     * @param boolean $formatExtTitle полное название объема данных (при форматировании)
     * @return mixed
     */
    public function getMaxSize($format = false, $formatExtTitle = false)
    {
    }

    /**
     * Устанавливаем максимально допустимый размер
     * @param integer $maxSize размер в байтах
     */
    public function setMaxSize($maxSize)
    {
    }

    /**
     * Получение максимально доступного кол-ва файлов у одной записи
     * @return integer
     */
    public function getLimit()
    {
    }

    /**
     * Установка максимально доступного кол-ва файлов у одной записи
     * @param $limit integer
     * @return integer
     */
    public function setLimit($limit)
    {
    }

    /**
     * Сообщать об ошибках при загрузке
     * @param boolean $assign
     */
    public function setAssignErrors($assign)
    {
    }

    /**
     * Формирование URL
     * @param array $attach : filename - название файла gen(N).ext, dir - # папки, srv - ID сервера
     * @param boolean $tmp tmp-файла
     * @return string|array URL
     */
    public function getURL(array $attach, $tmp = false)
    {
    }

    /**
     * Формирование пути к файлу
     * @param array $attach : filename - название файла gen(N).ext, dir - # папки, srv - ID сервера
     * @param boolean $tmp tmp-файл
     * @return string Путь
     */
    protected function getPath(array $attach, $tmp = false)
    {
    }

    /**
     * Загрузка файла при помощи QQ-загрузчика
     * @param array $extraData дополнительные данные
     * @return mixed
     */
    public function uploadQQ(array $extraData = array())
    {
    }

    /**
     * Загрузка нескольких файлов стандартным методом
     * @param string $inputName имя file-поля в массиве $_FILES
     * @param array $extraData дополнительные данные
     * @return array данные о загруженных файлах
     */
    public function uploadFILES($inputName = 'attach', array $extraData = array())
    {
    }

    /**
     * Переносим temp-прикрепления в постоянную папку
     * @param string $inputName ключ в массиве $_POST, тип TYPE_ARRAY_STR
     * @param boolean $edit используем при редактировании записи
     * @return boolean
     */
    public function saveTmp($inputName = 'attach', $edit = false)
    {
    }

    /**
     * Сохранение порядка файлов
     * @param array $aAttachments данные об файлах array(imageID=>Filename, ...) или array(filename, ...)
     * @param boolean $bContainsTmp - $aAttachments может содержать tmp (соответственно сохраняем порядок только всех не-tmp файлов)
     * @return boolean
     */
    public function saveOrder($aAttachments, $bContainsTmp = true)
    {
    }

    /**
     * Удаление прикрепления
     * @param integer $attachID ID прикрепления
     * @return boolean
     */
    public function deleteAttach($attachID)
    {
    }

    /**
     * Удаление всех прикреплений связанных с записью
     * @param boolean $updateQuery актуализировать ли данные о прикреплениях записи (после их удаления)
     * @return boolean
     */
    public function deleteAllAttachments($updateQuery = false)
    {
    }

    /**
     * Удаление файла
     * @param array $fileData информация о файле
     * @param boolean $tmp временный файл
     * @return boolean
     */
    protected function deleteFile($fileData, $tmp = false)
    {
    }

    /**
     * Удаление временного файла(-ов)
     * @param string|array $fileName имя файла (нескольких файлов)
     * @return boolean
     */
    public function deleteTmpFile($fileName)
    {
    }

    /**
     * Формирование ID сервера хранения
     * @return integer ID сервера
     */
    protected function getRandServer()
    {
    }

    /**
     * Формирование название директории исходя из ID записи
     * @return string название директории
     */
    protected function getDir()
    {
    }

    /**
     * Получаем данные о загруженных и сохраненных на текущий момент файлах
     * @param mixed $count кол-во файлов, false - если не знаем
     * @return array данные о файлах или FALSE
     */
    public function getData($count = false)
    {
    }

    /**
     * Сохраняем данные о записи
     * @param integer $recordID ID записи
     * @param array $recordData данные
     * @return mixed
     */
    protected function saveRecordData($recordID, array $recordData)
    {
    }

    /**
     * Получаем данные о записи
     * @param integer $recordID ID записи
     * @return array
     */
    protected function loadRecordData($recordID)
    {
    }

    /**
     * Получаем данные о временных файлах
     * @return array
     */
    protected function getTmpAttachments()
    {
    }

    /**
     * Получаем данные о файле вложения по его ID
     * @param integer $attachID
     * @return array
     */
    protected function getAttachmentData($attachID)
    {
    }
}