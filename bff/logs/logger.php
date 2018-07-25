<?php namespace bff\logs;

/**
 * Логгер (PSR-3)
 * @version 0.21
 * @modified 1.may.2018
 */

use Monolog\Logger as MonologLogger;
use bff\logs\handlers\RotatingFileHandler;
use Monolog\Handler\FirePHPHandler;
use Monolog\Formatter\LineFormatter;

class Logger extends MonologLogger
{
    /**
     * Файл логов по-умолчанию
     */
    const DEFAULT_FILE = 'errors.log';

    /**
     * @param string $name имя канала или файла
     * @param string|boolean $fileName имя файла или полный путь к файлу
     * @param int $level уровень логирования (sys: log.level)
     * @param int|bool $maxFiles максимально возможное кол-во файлов (sys: log.maxFiles)
     * @param array $handlers
     * @param array $processors
     * @return Logger
     */
    public static function factoryRotatingFile($name, $fileName = false, $level = Logger::DEBUG, $maxFiles = false, array $handlers = array(), array $processors = array())
    {
        if (empty($fileName)) {
            $fileName = $name;
        }
        if (mb_stripos($fileName, DIRECTORY_SEPARATOR) === false) {
            $fileName = \PATH_BASE . 'files' . DS . 'logs' . DS . $fileName;
        }
        if (empty($level)) {
            $level = \config::sys('log.level', Logger::DEBUG);
        }
        if (empty($maxFiles)) {
            $maxFiles = \config::sys('log.maxFiles', 3);
        }
        $logger = new self($name, $handlers, $processors);

        # rotatig file handler
        $rotatigFile = new RotatingFileHandler($fileName, $maxFiles, $level, true, 0664);
        $formatter = new LineFormatter(\config::sys('log.formatter.format', LineFormatter::SIMPLE_FORMAT));
        $formatter->ignoreEmptyContextAndExtra(true);
        if (\config::sys('log.formatter.allowInlineLineBreaks', false)) {
            $formatter->allowInlineLineBreaks(true);
        }
        $rotatigFile->setFormatter($formatter);
        $logger->pushHandler($rotatigFile);

        # php handler
        $logger->pushHandler(new FirePHPHandler());

        return $logger;
    }

    /**
     * Получаем содержимое log-файла
     * @param int $limit ограничение максимального кол-ва строк, 0 -  без ограничений
     * @param string|bool $name имя канала или false - текущее
     * @return array
     */
    public function getRotatingFilesContent($limit = 0, $name = false)
    {
        if (empty($name)) {
            $name = $this->getName();
        }

        $records = array(); $i = 0;
        foreach ($this->getHandlers() as $h) {
            if ( ! is_a($h, RotatingFileHandler::class)) {
                continue;
            }
            $files = $h->getFilesList();
            foreach ($files as $f) {
                $file = file($f); if ($file === false) { continue; }
                $file = array_reverse($file);
                foreach ($file as $line) {
                    preg_match('/\[(?P<date>.*)\] (?P<channel>'.preg_quote($name).').(?P<level>\w+): (?P<message>.*+)/', $line, $data);
                    if (isset($data['date'])) {
                        $records[] = '['.$data['date'].'] '.$data['level'].': '.$data['message'];
                        $i++;
                    }
                    if ($limit > 0 && $i >= $limit) { break 3; }
                }
            }
        }
        return $records;
    }
}