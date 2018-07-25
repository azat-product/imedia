<?php

/**
 * Компонент управляющий сохранением/удалением одного изображения
 *
 * Информация о загруженном изображении сохраняется в таблицу {$table} в
 * поля:
 *  {$fieldImage} - имя загруженного файла (без префикса)
 *  {$fieldCrop} - координаты выбранной области изображения (jcrop)
 *
 * @abstract
 * @version 0.5
 * @modified 16.sep.2015
 */

abstract class CImageUploader_ extends Component
{
    /** @var integer ID записи */
    protected $recordID = 0;

    /** @var string Название таблицы */
    protected $table = '';
    /** @var string Название поля для хранения ID записи */
    protected $fieldID = 'id';
    /** @var string Название поля для хранения имени файла */
    protected $fieldImage = 'img';
    /** @var string Название поля для хранения параметров кропа */
    protected $fieldCrop = 'img_crop';
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
     * @var integer
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
     * @example array(
     *      key => array(
     *          'o'=> @param boolean - сохранять ли в оригинальных размерах,
     *          'width'=> @param integer - ширина,
     *          'height'=> @param integer - высота,
     *          ... параметры для bff\img\Thumbnail
     *      ), ...
     * )
     * @var array
     */
    protected $sizes = array();

    /**
     * Кол-во символов в названии сохранямого файла
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
     * Качество конечного изображения (1-100)
     * @var integer
     */
    protected $quality = 90;

    /**
     * Раскладывать ли файлы в зависимости от recordID
     * по папкам folder = (recordID / 1000)
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

    abstract protected function initSettings();

    /**
     * Загрузка(сохранение/обновление) изображения, методом $_FILES
     * @param string $sInput input file name
     * @param boolean $bDeletePrevious удалять предыдущее изображение
     * @param boolean $bDoUpdateQuery сохранить изменения в БД
     * @return array информация об успешно загруженном файле изображения или FALSE в случае ошибки
     */
    public function uploadFILES($sInput, $bDeletePrevious = true, $bDoUpdateQuery = false)
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения при помощи QQ-загрузчика
     * @param string $sInput input file name
     * @param boolean $bDeletePrevious удалять предыдущее изображение
     * @param boolean $bDoUpdateQuery сохранить изменения в БД
     * @return array информация об успешно загруженном файле изображения или FALSE в случае ошибки
     */
    public function uploadQQ($bDeletePrevious = true, $bDoUpdateQuery = false)
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения через компонент SWFUploader
     * @param boolean $bDeletePrevious удалять предыдущее изображение
     * @param boolean $bDoUpdateQuery сохранить изменения в БД
     * @return array информация об успешно загруженном файле изображения или FALSE в случае ошибки
     */
    public function uploadSWF($bDeletePrevious = true, $bDoUpdateQuery = false)
    {
    }

    /**
     * Загрузка(сохранение/обновление) изображения по URL ссылке
     * @param string $sURL ссылка на изображение
     * @param boolean $bDeletePrevious удалять предыдущее изображение
     * @param boolean $bDoUpdateQuery сохранить изменения в БД
     * @return array информация об успешно загруженном файле изображения или FALSE в случае ошибки
     */
    public function uploadURL($sURL, $bDeletePrevious = true, $bDoUpdateQuery = false)
    {
    }

    /**
     * Переносим temp-изображения в постоянную папку
     * @param string $sFilename имя файла temp-изображения
     * @param boolean $bDoUpdateQuery сохранить изменения в БД
     * @return boolean
     */
    public function untemp($sFilename, $bDoUpdateQuery = false)
    {
    }

    /**
     * Удаление изображения
     * @param boolean $bUpdateRecord обновлять запись в БД
     * @param string $sFilename имя файла или FALSE(берется из БД)
     * @return boolean
     */
    public function delete($bUpdateRecord = true, $sFilename = false)
    {
    }

    /**
     * Удаление tmp изображения
     * @param string $sFilename имя файла
     * @return boolean
     */
    public function deleteTmp($sFilename)
    {
    }

    /**
     * Установка допустимых размеров изображения по ширине/высоте
     * @param integer $nMinWidth минимальная ширина изображения или FALSE
     * @param integer $nMaxWidth максимальная ширина изображения или FALSE
     * @param integer $nMinHeight минимальная высота изображения или FALSE
     * @param integer $nMaxHeight максимальная высота изображения или FALSE
     */
    public function setDimensions($nMinWidth = false, $nMaxWidth = false, $nMinHeight = false, $nMaxHeight = false)
    {
    }

    /**
     * Сообщать об ошибках
     * @param boolean $bAssign
     */
    public function setAssignErrors($bAssign)
    {
    }

    /**
     * Формирование URL изображения
     * @param string $sFilename имя файла
     * @param string|array $sSize префикс размера или массив префиксов размеров
     * @param boolean $bTmp tmp-изображение
     * @return string URL
     */
    public function getURL($sFilename, $sSize, $bTmp = false)
    {
    }

    /**
     * Установка названия поля для хранения имени файла
     * @param string $sField
     */
    public function setFieldImage($sField)
    {
    }

    /**
     * Установка максимально допустимого размера файла
     * @param integer $nMaxSize размер в байтах
     */
    public function setMaxSize($nMaxSize)
    {
    }

    /**
     * Получение максимально допустимого размера файла
     * @param boolean $bFormat применить форматирование
     * @param boolean $bFormatExtTitle полное название объема данных (при форматировании)
     * @return mixed
     */
    public function getMaxSize($bFormat = false, $bFormatExtTitle = false)
    {
    }

    /**
     * Возвращаем результат загрузки
     * @param string $sUploadType тип загрузки: 'files','qq','swf','url'
     * @param array|bool $aUploadResult результат загрузки, ответ полученный от upload_ метода
     * @param array $aSizes размеры (в для которых необходимо сформировать URL изображения)
     */
    public function doResponse($sUploadType, $aUploadResult, array $aSizes)
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