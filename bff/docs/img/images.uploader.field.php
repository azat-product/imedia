<?php

/**
 * Компонент управляющий загрузкой / сохранением / удалением нескольких изображений
 * Для хранения используются поля в таблице записи
 * @abstract
 * @version 0.1
 * @modified 24.dec.2015
 */

abstract class CImagesUploaderField_ extends CImagesUploader
{
    /** @var integer ID пользователя */
    protected $userID = 0;

    /** @var string Название таблицы для хранения данных о записи */
    protected $table = '';
    protected $field_id     = 'id';
    protected $field_images = 'img';
    protected $field_count  = 'imgcnt';

    /**
     * Максимально доступное кол-во изображений у одной записи
     * 0 - неограничено
     * @var integer
     */
    protected $limit = 5;

    public function __construct($recordID = 0)
    {
    }

    abstract protected function initSettings();

    public function setUserID($nUserID)
    {
    }

    /**
     * Получение максимально доступного кол-ва изображений у одной записи
     * @return integer
     */
    public function getLimit()
    {
    }

    /**
     * Установка максимально доступного кол-ва изображений у одной записи
     * @param $nLimit integer
     */
    public function setLimit($nLimit)
    {
    }

    /**
     * Переносим tmp-изображения в постоянную папку
     * @param string|array $mFieldname ключ в массиве $_POST, тип TYPE_ARRAY_STR или filename-массив
     * @param boolean $bEdit используем при редактировании записи
     */
    public function saveTmp($mFieldname = 'img', $bEdit = false)
    {
    }

    /**
     * Сохранение порядка изображений
     * @param array $images данные об изображениях array(filename, ...)
     * @return boolean
     */
    public function saveOrder(array $images)
    {
    }

    /**
     * Удаление изображений
     * @param array $images данные об изображениях array(filename, ...)
     * @return integer кол-во удаленных изображений
     */
    public function deleteImages(array $images)
    {
    }

    /**
     * Удаление изображения
     * @param string $imageFilename имя файла изображения
     * @return boolean
     */
    public function deleteImage($imageFilename)
    {
    }

    /**
     * Удаление всех изображений связанных с записью
     * @param boolean $updateQuery актуализировать ли данные о изображениях записи (после их удаления)
     * @return boolean
     */
    public function deleteAllImages($updateQuery = false)
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
     * Сохраняем данные о записи
     * @param integer $recordID ID записи
     * @param array $recordData данные
     * @return mixed
     */
    protected function saveRecordData($recordID, array $recordData)
    {
    }

    /**
     * Получаем данные о загруженных и сохраненных на текущий момент изображениях
     * @param mixed $nCount кол-во изображений, false - если не знаем
     * @return array данные об изображениях или FALSE
     */
    public function getData($nCount = false)
    {
    }

}