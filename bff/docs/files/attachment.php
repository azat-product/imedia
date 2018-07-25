<?php namespace bff\files;

/**
 * Компонент управляющий загрузкой файлов вложений
 * @version 0.361
 * @modified 16.oct.2013
 */

class Attachment_
{
    /** @var int максимально допустимый объем файла (в байтах) */
    protected $maxSize = 0;
    /** @var string путь к директории хранения файлов */
    protected $path = '';
    /** @var boolean выполнять проверку доступного места на диске */
    protected $checkDiskFreeSpace = true;
    /** @var boolean минимально допустимый размер свободного места на диске (в байтах) */
    protected $minimalDiskFreeSpace = 524288000;
    /** @var boolean сообщать об ошибках */
    protected $assignErrors = false;
    /** @var boolean возвращать данные о загруженном файле в виде строки с разделителем ";" */
    protected $filedataAsString = true;
    protected $filedataSeparator = ';';

    /**
     * @var array список разрешенных расширений файлов
     * @example:
     * 'jpg','jpeg','gif','png','bmp','tiff','ico','doc','docx','xls','rtf',
     * 'pdf','djvu','zip','gzip','gz','7z','rar','txt','sql',
     */
    protected $extensionsAllowed = array();
    /** @var array список запрещенных расширений файлов */
    protected $extensionsForbidden = array(
        'php','php2','php3','php4','php5','php6',
        'phtml','pwml','inc','asp','aspx','ascx',
        'jsp','cfm','cfc','pl','py','rb','bat',
        'exe','com','cmd','dll','so',
        'vbs','vbe','js','jse','reg','cgi','wsf','wsh',
    );

    /**
     * Инициализация
     * @param string $path путь к директории хранения файлов
     * @param integer $maxSize максимально допустимый размер вложения (в байтах)
     */
    public function __construct($path, $maxSize = 0)
    {
    }

    /**
     * Устанавливаем максимально допустимый размер вложения (в байтах)
     * @param integer $maxSize 0 - без ограничения
     */
    public function setMaxSize($maxSize = 0)
    {
    }

    /**
     * Получаем максимально допустимый размер вложения (в байтах)
     * @return integer
     */
    public function getMaxSize()
    {
    }

    /**
     * Проверка допустимости расширения файла
     * @param string $extension расширение файла
     * @return bool
     */
    public function isAllowedExtension($extension = '')
    {
    }

    /**
     * Устанавливаем список разрешенных расширений файлов
     * @param array $extensions
     */
    public function setAllowedExtensions(array $extensions = array())
    {
    }

    /**
     * Устанавливаем список запрещенных расширений файлов
     * @param array $extensions
     */
    public function setForbiddenExtensions(array $extensions = array())
    {
    }

    /**
     * Выполнять проверку свободного места на диске
     * @param boolean $check
     */
    public function setCheckFreeDiskSpace($check)
    {
    }

    /**
     * Сообщать об ошибках
     * @param boolean $assignErrors
     */
    public function setAssignErrors($assignErrors)
    {
    }

    /**
     * Формировать результат загрузки файла в виде строки
     * @param boolean $filedataAsString
     */
    public function setFiledataAsString($filedataAsString)
    {
    }

    /**
     * Загрузка файла стандартным методом
     * @param string $inputName имя file-поля
     * @param integer $limit ограничение максимального кол-ва одновременно загружаемых файлов
     * @return mixed
     */
    public function uploadFILES($inputName, $limit = 1)
    {
    }

    /**
     * Загрузка файла при помощи QQ-загрузчика
     * @return mixed
     */
    public function uploadQQ()
    {
    }

    /**
     * Формирование данных о загруженном файле
     * @param mixed $data
     * @return mixed
     */
    protected function prepareFiledata($data)
    {
    }

    /**
     * Формирование имени для загружаемого файла
     * @param string $extension расширение файла
     * @return string
     */
    protected function generateFilename($extension)
    {
    }

    /**
     * Получаем общий объем файлов в директории {$this->path}
     * @return integer
     */
    public function getDirSize()
    {
    }

}