<?php namespace bff\logs\handlers;

/**
 * Логгер: обработчик выполняющий запись в log-файлы с ротацией
 * @version 0.1
 * @modified 11.oct.2017
 */

use bff\logs\Logger;
use Monolog\Handler\StreamHandler;

class RotatingFileHandler extends StreamHandler
{
    /** @var int Максимальный размер log-файлов (в КБ) */
    protected $maxFileSize = 1024;

    /** @var int Кол-во файлов ротации log-файлов */
    protected $maxFiles = 2;

    /** @var string Директория log-файлов */
    protected $logPath;

    /** @var string Название log-файла */
    protected $logFile = 'errors.log';

    /**
     * @param string   $filePath       Full file path
     * @param int      $maxFiles       Number of files to keep
     * @param int      $level          The minimum logging level at which this handler will be triggered
     * @param Boolean  $bubble         Whether the messages that are handled can bubble up the stack or not
     * @param int|null $filePermission Optional file permissions (default (0644) are only for owner read/write)
     * @param Boolean  $useLocking     Try to lock log file before doing any writes
     */
    public function __construct($filePath, $maxFiles = 2, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        $info = pathinfo($filePath);
        $this->logFile = $info['basename'];
        $this->logPath = rtrim($info['dirname'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->setMaxFiles($maxFiles);

        parent::__construct($this->logPath.$this->logFile, $level, $bubble, $filePermission, $useLocking);
    }

    /**
     * Получение текущего значения масимально допустимого размера log-файлов
     * @return integer объем файла, в килобайтах
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    /**
     * Назначаем максимальный размер log-файлов (в КБ)
     * @param integer $value размер (в КБ)
     */
    public function setMaxFileSize($value)
    {
        if (($this->maxFileSize = (int)$value) < 1) {
            $this->maxFileSize = 1;
        }
    }

    /**
     * Получаем кол-во файлов ротации log-файлов
     * @return integer
     */
    public function getMaxFiles()
    {
        return $this->maxFiles;
    }

    /**
     * Назначаем кол-во файлов ротации log-файлов
     * @param integer $value кол-во
     */
    public function setMaxFiles($value)
    {
        if (($this->maxFiles = (int)$value) < 1) {
            $this->maxFiles = 1;
        }
    }

    /**
     * Получаем список файлов
     * @return array
     */
    public function getFilesList()
    {
        $list = array();
        $file = $this->logPath . $this->logFile;
        if (is_file($file)) {
            $list[] = $file;
        }
        $max = $this->getMaxFiles();
        for ($i = 1; $i <= $max; $i++) {
            $fileN = $file . '.' . $i;
            if (is_file($fileN)) {
                $list[] = $fileN;
            }
        }
        return $list;
    }

    /**
     * Ротация файлов
     */
    protected function rotateFiles()
    {
        $file = $this->logPath . $this->logFile;
        $max = $this->getMaxFiles();
        for ($i = $max; $i > 0; --$i) {
            $rotateFile = $file . '.' . $i;
            if (is_file($rotateFile)) {
                if ($i === $max) {
                    @unlink($rotateFile);
                } else {
                    @rename($rotateFile, $file . '.' . ($i + 1));
                }
            }
        }
        if (is_file($file)) {
            rename($file, $file . '.1');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        parent::close();

        $logFile = $this->logPath . $this->logFile;
        if (file_exists($logFile) && @filesize($logFile) > $this->getMaxFileSize() * 1024) {
            $this->rotateFiles();
        }
    }
}