<?php

/**
 * Компонент управляющий загрузкой / удалением нескольких изображений
 * @version 0.6
 * @modified 16.sep.2015
 */

class CImagesUploader_ extends Component
{
    /** @var integer ID записи */
    protected $recordID = 0;

    /** @var string Путь к изображениям */
    protected $path = '';
    /** @var string Путь ко временным изображениям */
    protected $pathTmp = '';
    /** @var string URL к изображениям */
    protected $url = '';
    /** @var string URL ко временным изображениям */
    protected $urlTmp = '';
    /** @var boolean сообщать об ошибках */
    protected $assignErrors = true;

    /**
     * Максимально допустимый размер файла изображения
     * @example: 5242880 - 5мб, 4194304 - 4мб, 3145728 - 3мб, 2097152 - 2мб
     */
    protected $maxSize = 4194304;

    /**
     * Минимально допустимый размер изображения по ширине/высоте
     * @vars integer
     */
    protected $minWidth = 0; // ширина (0 - не выполнять проверку)
    protected $minHeight = 0; // высота (0 - не выполнять проверку)

    /**
     * Максимально допустимый размер изображения по ширине/высоте
     * @vars integer
     */
    protected $maxWidth = 0; // ширина (0 - не выполнять проверку)
    protected $maxHeight = 0; // высота (0 - не выполнять проверку)

    /**
     * Размеры изображений
     * ! Порядок перечисления: от меньшего к большему (по размеру изображения)
     * @var array
     * @example array(
     *      key => array(
     *          'o'=> boolean сохранять ли в оригинальных размерах,
     *          'width'=> integer ширина,
     *          'height'=> integer высота,
     *          'vertical'=> настройки для вертикального изображения
     *              'width'=> integer ширина,
     *              'height'=> integer высота,
     *          ... парамметры для Thumbnail
     *      ), ...
     * )
     */
    protected $sizes = array();

    /**
     * Кол-во символов в генерируемой части названия сохраняемого файла
     * @var integer
     */
    protected $filenameLetters = 6;

    /**
     * Допустимые расширения файлов
     * Обязательно указывать 'jpg' и 'jpeg'
     * @var array
     */
    protected $extensionsAllowed = array('jpg', 'jpeg', 'gif', 'png');

    /**
     * Качество конечного JPEG изображения (1-100)
     * @var integer
     */
    protected $quality = 90;

    /**
     * Раскладывать ли файлы в зависимости от recordID, по папкам folder = (recordID / recordsLimit)
     * @var boolean
     */
    protected $folderByID = false;
    protected $folderByID_RecordsLimit = 1000;

    /**
     * Используется внешний источник хранения
     * false - хранение выполняется на этом же сервере
     * true - на другом сервере, меняется логика загрузки временных файлов
     * @var bool
     */
    protected $externalSave = false;

    public function __construct($nRecordID = 0)
    {
    }

    public function setRecordID($nRecordID)
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения, методом $_FILES
     * @param string $sInputName input file name
     * @return array информация об успешно загруженном файле изображения (@see save) или FALSE в случае ошибки
     */
    public function uploadFILES($sInputName)
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения на основе пути к уже загруженному файлу
     * @param string $filePath путь к файлу
     * @param boolean $saveOriginalFile сохранять оригинальный файл
     * @return array информация об успешно загруженном файле изображения (@see save) или FALSE в случае ошибки
     */
    public function uploadFromFile($filePath, $saveOriginalFile = true)
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения при помощи QQ-загрузчика
     * @return array информация об успешно загруженном файле изображения (@see save) или FALSE в случае ошибки
     */
    public function uploadQQ()
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения через компонент SWFUploader
     * @return array информация об успешно загруженном файле изображения (@see save) или FALSE в случае ошибки
     */
    public function uploadSWF()
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения по URL ссылке
     * @param string $sURL ссылка на изображение
     * @return array информация об успешно загруженном файле изображения (@see save) или FALSE в случае ошибки
     */
    public function uploadURL($sURL)
    {
    }

    /**
     * Переносим tmp-изображения в постоянную папку
     * @param string|array $mFieldname ключ в массиве $_POST, тип TYPE_ARRAY_STR или filename-массив
     * @param boolean $bEdit используем при редактировании записи
     * @return boolean
     */
    public function saveTmp($mFieldname = 'img', $bEdit = false)
    {
    }

    /**
     * Сохранение / нарезание файлов изображений
     * @param array $aData данные о загруженном файле:
     *   tmpfile - путь к временному избражению,
     *   ext - расширение,
     *   width - ширина изображения,
     *   height - высота изображения
     * @param array $aSizes требуемые размеры изображения
     * @param boolean $bSaveOriginal сохранять исходный файл
     * @param boolean $bTmp временная загрузка
     * @return array|boolean
     *   array - данные об изображении: [filename, width, height, dir, srv, ...]
     *   false - ошибка загрузки файла
     */
    protected function saveFile($aData, array $aSizes, $bSaveOriginal = false, $bTmp = false)
    {
    }

    /**
     * Удаление tmp изображения(-й)
     * @param string|array $mFilename имя файла (нескольких файлов)
     * @return boolean
     */
    public function deleteTmpFile($mFilename)
    {
    }

    /**
     * Удаление файлов изображения (всех размеров)
     * @param array $aImage информация о файле изображения: filename[, dir, srv]
     * @param boolean $bTmp tmp-изображение
     * @return boolean
     */
    public function deleteFile($aImage, $bTmp = false)
    {
    }

    /**
     * Устанавливаем минимально/максимально допустимые размеры изображения по ширине/высоте
     * @param integer $minWidth минимально допустимая ширина
     * @param integer $minHeight минимально допустимая высота
     * @param integer $maxWidth максимально допустимая ширина
     * @param integer $maxHeight максимально допустимая высота
     */
    public function setDimensions($minWidth = 0, $minHeight = 0, $maxWidth = 0, $maxHeight = 0)
    {
    }

    /**
     * Получаем параметры изображения
     * @param array $aImage : @see return::save
     * @param string|array $sSize префикс размера или массив префиксов размеров
     * @param boolean $bTmp tmp-изображение
     * @return array
     */
    public function getImageParams($aImage, $aSizes, $bTmp = false)
    {
    }

    /**
     * Сообщать об ошибках
     * @param boolean $assign
     */
    public function setAssignErrors($assign)
    {
    }

    public function setFolderByID($enabled)
    {
    }

    /**
     * Устанавливаем пути к изображениям
     * @param string $path постоянный путь
     * @param string $pathTmp временный путь
     */
    public function setPath($path, $pathTmp)
    {
    }

    /**
     * Формирование URL изображения
     * @param array $aImage : filename - название файла gen(N).ext, dir - # папки, srv - ID сервера
     * @param string|array $mSize префикс размера или массив префиксов размеров
     * @param boolean $bTmp tmp-изображение
     * @return string|array URL
     */
    public function getURL($aImage, $mSize, $bTmp = false)
    {
    }

    /**
     * Устанавливаем URL к изображениям
     * @param string $url постоянный URL
     * @param string $urlTmp временный URL
     */
    public function setURL($url, $urlTmp)
    {
    }

    /**
     * Формирование URL изображения (быстрый вызов)
     * @param integer $nRecordID ID записи
     * @param array $aImage : filename - название файла gen(N).ext, dir - # папки, srv - ID сервера
     * @param string $sSizePrefix префикс размера
     * @param boolean $bTmp tmp-изображение
     * @return string URL
     */
    public static function url($nRecordID, $aImage, $sSizePrefix, $bTmp = false)
    {
    }

    /**
     * Устанавливаем размеры
     * @param array $sizes
     */
    public function setSizes(array $sizes = array())
    {
    }

    /**
     * Получаем ширину изображения для необходимого размера
     * @param string $sizePrefix префикс размера
     * @return integer ширина
     */
    public function getSizeWidth($sizePrefix)
    {
    }

    /**
     * Получаем высоту изображения для необходимого размера
     * @param string $sizePrefix префикс размера
     * @return integer высота
     */
    public function getSizeHeight($sizePrefix)
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
     * Используется внешний источник хранения
     * @return bool
     */
    public function isExternalSave()
    {
        return $this->externalSave;
    }
}