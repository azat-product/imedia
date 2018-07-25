<?php namespace bff\extend;

/**
 * Плагинизация: настройки расширения - загрузка файлов
 * @version 0.24
 * @modified 28.mar.2018
 */

use \bff\files\Attachment;

class ExtensionFile extends \Component
{
    /** @var null|Attachment */
    protected $attach = null;

    /**
     * Ключ для хранения данных в таблице TABLE_CONFIG
     * @var string
     */
    protected $configKey = '';

    /** @var string путь к директории хранения файлов */
    protected $path = '';
    /** @var string URL путь к директории хранения файлов */
    protected $url = '';
    /** @var int максимально допустимый объем файла (в байтах) */
    protected $maxSize = 10485760; # 10 mb
    /** @var string список разрешенных расширений файлов */
    protected $extensionsAllowed = 'jpg,jpeg,gif,png,bmp,tiff,ico,odt,doc,docx,docm,xls,xlsx,xlsm,ppt,rtf,pdf,djvu,zip,gzip,gz,7z,rar,txt,xml';
    /** @var boolean сообщать об ошибках */
    protected $assignErrors = false;
    /** @var boolean
     * true - файлы доступны по прямому URL (хранятся в /public_html/files/extensions/{extension-name}/)
     * false - файлы НЕдоступны по прямому URL (хранятся в /files/extensions/{extension-name}/)
     */
    protected $publicStore = true;

    public function __construct($settings = array())
    {
        $this->init();
        $this->setSettings($settings);
        if (empty($this->path)) {
            if ($this->isPublicStore()) {
                $this->path = \bff::path('extensions');
            } else {
                $this->path = PATH_BASE.'files'.DS.'extensions'.DS;
            }
        }
        if (empty($this->url)) {
            $this->url = \bff::url('extensions');
        }
        if (empty($settings['attach'])) {
            $this->attach = new Attachment($this->path, $this->maxSize);
            $this->attach->setAllowedExtensions((
                is_array($this->extensionsAllowed) ?
                    $this->extensionsAllowed :
                    explode(',', $this->extensionsAllowed)
            ));
            $this->attach->setFiledataAsString(false);
            $this->attach->setCheckFreeDiskSpace(false);
            $this->attach->setAssignErrors($this->assignErrors);
        }
    }

    /**
     * Загрузка файла стандартным методом
     * @param string $inputName имя file-поля
     */
    public function uploadFILES($inputName)
    {
        $file = $this->attach->uploadFILES($inputName, 1);
        $this->saveData((!empty($file) ? array($file) : array()), true);
        return $file;
    }

    /**
     * Загрузка файла при помощи QQ-загрузчика
     */
    public function uploadQQ()
    {
        $file = $this->attach->uploadQQ();
        $this->saveData((!empty($file) ? array($file) : array()), true);
        return $file;
    }

    /**
     * Формирование ссылки на файл
     * @param array $fileData
     */
    public function getURL($fileData)
    {
        return $this->url . $fileData['filename'];
    }

    /**
     * Устанавливаем максимально допустимый размер вложения (в байтах)
     * @param integer $maxSize 0 - без ограничения
     */
    public function setMaxSize($maxSize = 0)
    {
        $this->attach->setMaxSize($maxSize);
    }

    /**
     * Получение максимально допустимого размера файла
     * @param boolean $format применить форматирование
     * @param boolean $formatExtTitle полное название объема данных (при форматировании)
     * @return mixed
     */
    public function getMaxSize($format = false, $formatExtTitle = false)
    {
        $maxSize = $this->attach->getMaxSize();
        return ($format ? \tpl::filesize($maxSize, $formatExtTitle) : $maxSize);
    }

    /**
     * Получение максимально доступного кол-ва загружаемых файлов
     * @return integer
     */
    public function getLimit()
    {
        return 1;
    }

    /**
     * Получаем способ хранения файлов
     * @return boolean
     */
    public function isPublicStore()
    {
        return $this->publicStore;
    }

    /**
     * Сохранение данных о загруженных файлах
     * @param mixed $files данные
     * @param boolean $deletePrev удалять предыдущие
     */
    protected function saveData($files, $deletePrev = true)
    {
        if ($deletePrev) {
            $prev = $this->loadData();
            if (!empty($prev)) {
                foreach ($prev as $v) {
                    if (empty($v['filename'])) { continue; }
                    $path = $this->path . $v['filename'];
                    if (is_file($path)) {
                        unlink($path);
                    }
                }
            }
        }
        if (empty($files) || !is_array($files)) {
            $files = array();
        }
        \config::save($this->configKey, $files, true);
    }

    /**
     * Удаление данных о загруженных файлах
     * @param array $fileNames
     */
    public function deleteData($fileNames = array())
    {
        $this->saveData([], true);
    }

    /**
     * Получение данных о загруженных файлах
     * @return array
     */
    public function loadData()
    {
        $data = \config::get($this->configKey, array(), TYPE_ARRAY);
        if (empty($data)) {
            $data = array();
        } else {
            foreach ($data as $k=>$v) {
                if (isset($v['filename'])) {
                    $data[$k]['url'] = $this->getURL($v);
                } else {
                    unset($data[$k]);
                }
            }
        }
        return $data;
    }
}