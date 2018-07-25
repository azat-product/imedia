<?php namespace bff\extend;

/**
 * Плагинизация: расширение
 * @abstract
 * @version 1.71
 * @modified 8.mar.2018
 */

const EXTENSION_TYPE_PLUGIN = 1;
const EXTENSION_TYPE_THEME  = 2;
const EXTENSION_TYPE_MODULE = 3;

use \Logger;

trait Extension
{
    /**
     * Зарегистрированный идентификатор расширения
     * @var string
     */
    protected $extension_id = '';

    /**
     * Тип расширения (EXTENSION_TYPE_)
     * @var integer
     */
    protected $extension_type = 0;

    /**
     * Название расширения: 'plugin.{name}', 'theme.{name}'
     * @var string
     */
    protected $extension_name = '';

    /**
     * Поля настроек расширения
     */
    protected $extension_config = array();
    protected $extension_config_options = array();

    /**
     * Путь к директории расширения (с завершающим слешом /)
     * @var string
     */
    protected $extension_path = '';

    /**
     * Расширение упаковано в PHAR архив
     * @var boolean
     */
    protected $extension_is_phar = false;

    /**
     * Совместимость расширения
     * @var bool|array
     */
    protected $extension_compatible = true;

    /**
     * Зависимость от других расширений
     * @var array
     */
    protected $extension_dependencies = array();

    /**
     * Статус наличия всех необходимых зависимостей подходящих версий
     * @var bool true - все зависимости в требуемых версиях присутствуют
     */
    protected $extension_dependencies_status = false;

    /**
     * Объект логгера
     * @var \Logger
     */
    protected $extension_logger = null;

    /**
     * Режим тестирования
     * @var bool
     */
    protected $extension_testmode = false;

    /**
     * Список предупреждений для пользователя
     * @var array
     */
    protected $extension_warnings = array();

    /**
     * Получение идентификатора расширения
     * @param boolean $fallbackToName возвращать имя в случае если идентификатор не указан
     * @return string
     */
    public function getExtensionId($fallbackToName = false)
    {
        if (!empty($this->extension_id)) {
            return $this->extension_id;
        }
        return ($fallbackToName ? $this->getName() : '');
    }

    /**
     * Расширение было зарегистрировано
     * @return bool
     */
    public function isRegistered()
    {
        return (!empty($this->extension_id) && mb_strlen($this->extension_id) == 40);
    }

    /**
     * Расширение было включено в режиме тестирования
     * @return bool
     */
    public function isTestmode()
    {
        return !empty($this->extension_testmode);
    }

    /**
     * Получение типа расширения
     * @param boolean $asText в форме текста
     * @return integer
     */
    public function getExtensionType($asText = false)
    {
        if ($asText) {
            switch ($this->extension_type) {
                case EXTENSION_TYPE_PLUGIN: return 'plugins';
                case EXTENSION_TYPE_THEME:  return 'themes';
                case EXTENSION_TYPE_MODULE: return 'modules';
            }
            return '';
        }
        return $this->extension_type;
    }

    /**
     * Установка совместимости
     * Допустимые форматы: 'A.B.C' (>=), ['do'=>'A.B.C'] (>=), ['do'=>['A.B.C','A.B.C']] (=)
     * @param bool|array $compatible
     */
    protected function setCompatible($compatible)
    {
        $this->extension_compatible = $compatible;
    }

    /**
     * Проверка совместимости расширения с текущей версией продукта
     * @return bool совместимо (true)
     */
    public function isCompatible()
    {
        return \bff::dev()->productVersionCompatible($this->extension_compatible);
    }

    /**
     * Установка зависимостей
     * @param array $dependencies
     * [
     *   'extension_id' => [
     *      'type'    => \bff\extend\EXTENSION_TYPE_PLUGIN, # тип расширения
     *      'version' => '1.0.0', # минимально требуемая версия
     *      'title'   => 'Название расширения',
     *   ],
     *   ...
     * ]
     * @param boolean $status проверка на наличие зависимостей была успешно пройдена
     */
    public function setDependencies(array $dependencies = array())
    {
        $this->extension_dependencies = $dependencies;
    }

    /**
     * Получение списка зависимостей
     * @return array
     */
    public function getDependencies()
    {
        return $this->extension_dependencies;
    }

    /**
     * Установка статуса зависимостей
     * @param boolean $status
     */
    public function setDependenciesStatus($status)
    {
        $this->extension_dependencies_status = $status;
    }

    /**
     * Получение статуса зависимостей
     * @return boolean
     */
    public function getDependenciesStatus()
    {
        return $this->extension_dependencies_status;
    }

    /**
     * Добавление предупреждений
     * @param string|array $message
     */
    protected function setWarnings($message)
    {
        if (is_array($message)) {
            foreach ($message as $v) {
                if (is_string($v)) {
                    $this->extension_warnings[] = $v;
                }
            }
        } else if (is_string($message)) {
            $this->extension_warnings[] = $message;
        }
    }

    /**
     * Очистка списка предупреждений
     * @return array список предупреждений
     */
    protected function clearWarnings()
    {
        $list = $this->extension_warnings;
        $this->extension_warnings = array();
        return $list;
    }

    /**
     * Получение списка предупреждений
     * @return array
     */
    public function getWarnings()
    {
        return $this->extension_warnings;
    }

    /**
     * Инициируем событие расширения
     * @param string $action
     * @param array $data
     */
    public function triggerEvent($action, array $data = array())
    {
        return $this->triggerEventTo($this, $action, $data);
    }

    /**
     * Инициируем событие расширения
     * @param Extension $extension
     * @param string $action
     * @param array $data
     */
    public function triggerEventTo($extension, $action, array $data = array())
    {
        return \bff::filter( join('.', [$extension->getExtensionType(true), $action, $extension->getName()]), false, $data);
    }

    /**
     * Поля настроек расширения
     * @param array $settings настройки
     * 'уникальный ключ' => [
     *  'title'   => 'Название поля в форме',
     *  'input'   => 'Тип поля в форме' - доступны: 'checkbox', 'select', 'password', 'text', 'textarea'
     *  'options' => 'Варианты значений для типа 'select' (выпадающий список)':
     *    [
     *      'ключ' => ['title'=>'название']
     *      ...
     *    ],
     *  'default' => 'Значение по-умолчанию',
     * ]
     * @param array $options дополнительные параметры: [
     *  'tabs' => []
     *  'titleRow' => 120, // Ширина столбца с названиями полей настроек
     * ]
     */
    protected function configSettings(array $settings, array $options = array())
    {
        $this->extension_config = $settings;
        $this->extension_config_options = $options;
        \config::extension($this->extension_name, $this->extension_config, $this);
    }

    /**
     * Дополнительный таб настроек
     * @param string $name название таба
     * @param callable|string $content функция возвращающая HTML (callable) или путь к файлу шаблона (string)
     * @param callable $submit обработчик сабмита формы
     * @param array $options:
     *   integer 'priority' - приоритет вывода таба
     */
    protected function settingsTab($name, $content, callable $submit, array $options = array())
    {
        if (!\bff::adminPanel() || !is_string($name)) {
            return;
        }
        $hookPrefix = 'extensions.'.$this->getExtensionType(true).'.'.$this->getName().'.settings.';
        $tabKey = 'custom_'.mb_strtolower(\func::translit($name));
        \bff::hooksBulk(array(
            $hookPrefix.'tabs' => function($tabs) use ($name, $tabKey, $options) {
                $tabs[$tabKey] = array(
                    'title' => $name,
                    'priority' => (isset($options['priority']) ? $options['priority'] : 1),
                );
                return $tabs;
            },
            $hookPrefix.'tabs.content' => function($data) use ($content, $tabKey, $options) {
                echo '<div class="j-tab j-tab-'.$tabKey.' hidden">';
                if (is_callable($content)) {
                    echo call_user_func($content, $data, $options);
                } else if (is_string($content)) {
                    $data['config'] = $this->config(array());
                    echo $this->viewPHP($data, $content);
                }
                if (!empty($data['submitButtons'])) {
                    echo $data['submitButtons'];
                }
                echo '</div>';
            },
            $hookPrefix.'submit' => function($data) use ($tabKey, $submit, $options) {
                if (is_callable($submit) && $this->input->post('tab', TYPE_STR) === $tabKey) {
                    call_user_func($submit, $data, $options);
                }
            },
        ));
    }

    /**
     * Получение настройки расширения по ключу
     * @param string|array $key ключ (несколько ключей)
     * @param mixed $default значение по-умолчанию
     * @param string $language ключ языка, для мультиязычных данных (null - текущий)
     * @return mixed
     */
    public function config($key, $default = '', $language = null)
    {
        if (is_string($key)) {
            if (isset($this->extension_config[$key])) {
                $setting = &$this->extension_config[$key];
                if (isset($setting['dynamic'])) {
                    return call_user_func($setting['dynamic'], $setting, $default, $language);
                }
                if ($setting['lang']) {
                    if (!is_null($language)) {
                        return (isset($setting['data_edit'][$language]) && $setting['data_edit'][$language] !== '' ? $setting['data_edit'][$language] : $default);
                    } else {
                        return ($setting['data'] !== '' ? $setting['data'] : $default);
                    }
                }
                return $setting['data'];
            } else {
                return $default;
            }
        } else if (is_array($key)) {
            $data = array();
            if (empty($key)) {
                foreach($this->extension_config as $k=>$v) {
                    $key[$k] = (isset($v['default']) ? $v['default'] : '');
                }
            }
            foreach ($key as $k=>$v) {
                if (is_string($k)) {
                    # $key = ['key1'=>'default', 'key2'=>'default', ...]
                    $data[$k] = $this->config($k, $v, $language);
                } else if (is_string($v)) {
                    # $key = ['key1','key2', ...], $default = default
                    $data[$v] = $this->config($v, $default, $language);
                }
            }
            return $data;
        }
        return $default;
    }

    /**
     * Сохранение настройки расширения
     * @param string|array $key ключ
     * @param mixed $value значение
     */
    public function configUpdate($key, $value = '')
    {
        if (\config::extensionSave($key, $value, $this->extension_name, $this->extension_config)) {
            if (is_array($key)) {
                foreach ($key as $k=>$v) {
                    $this->extension_config[$k]['data'] = $v;
                }
            } else {
                $this->extension_config[$key]['data'] = $value;
            }
        }
    }

    /**
     * Проверка наличия настройки расширения по ключу
     * @param string $key ключ
     * @return bool
     */
    public function configExists($key)
    {
        return isset($this->extension_config[$key]);
    }

    /**
     * Перегрузка внутренних настроек расширения
     * @param string $key ключ
     * @param mixed $value значение по-умолчанию
     * @return mixed
     */
    protected function configInternal($key, $default = '')
    {
        return \bff::filter($this->extension_name.'.'.strval($key), $default);
    }

    /**
     * Объект настройки типа 'image'
     * @param string $key ключ настройки расширения (configSettings)
     * @param boolean|string|array $size ключ требуемого размера
     * @return \bff\extend\ExtensionImage|string|array|boolean
     */
    public function configImages($key, $size = false)
    {
        $obj = false;
        if ( isset($this->extension_config[$key]['object']) &&
              is_a($this->extension_config[$key]['object'], '\bff\extend\ExtensionImage')) {
            $obj = $this->extension_config[$key]['object'];
        }
        if ($size === false) {
            return $obj;
        }
        if ($obj === false || empty($this->extension_config[$key]['data'])) {
            return (is_array($size) ? array() : '');
        }
        if ($obj->getLimit() < 2) {
            return $obj->getURL(current($this->extension_config[$key]['data']), $size);
        } else {
            $url = array();
            foreach ($this->extension_config[$key]['data'] as $file) {
                $url[] = $obj->getURL($file, $size);
            }

            return $url;
        }
    }

    /**
     * Объект настройки типа 'file'
     * @param string $key ключ настройки расширения (configSettings)
     * @param boolean $data возвращать данные о загруженных файлах
     * @return \bff\extend\ExtensionFile|string|array|boolean
     */
    public function configFiles($key, $data = false)
    {
        $obj = false;
        if ( isset($this->extension_config[$key]['object']) &&
              is_a($this->extension_config[$key]['object'], '\bff\extend\ExtensionFile')) {
            $obj = $this->extension_config[$key]['object'];
        }
        if ($data === false) {
            return $obj;
        }
        return $obj->loadData();
    }

    /**
     * Логирование сообщение
     * @param string|array $message сообщение
     * @param string|integer $level уровень логирования
     * @param array $context данные контекста
     * @return bool
     */
    public function log($message, $level = Logger::ERROR, array $context = array())
    {
        if (is_array($message)) {
            $message = print_r($message, true);
        }
        return $this->logger()->log($level, $message, $context);
    }

    /**
     * Получение сообщений расширения записанных в логи
     * @param int $limit кол-во строк, 0 - все доступные
     * @return array
     */
    public function getLogs($limit = 0)
    {
        return $this->logger()->getRotatingFilesContent($limit);
    }

    /**
     * Объект логгера, выполняющий функцию логирования
     * @return Logger
     */
    protected function logger()
    {
        if (is_null($this->extension_logger)) {
            $this->extension_logger = \bff::logger($this->extension_name, 'extensions.log');
        }
        return $this->extension_logger;
    }

    /**
     * Формирование абсолютного пути к файлу в директории расширения
     * @param string $file относительный путь к файлу
     * @param boolean $mod разрешать модифицикацию
     * @return string
     */
    public function path($file, $mod = true)
    {
        $file = $this->extension_path . ltrim($file, DS.' ');
        if ($mod) {
            if ($this->isPhar()) {
                $pathNormal = str_replace('phar://', '', $file);
                $fileMod = modification($pathNormal);
                if ($fileMod !== $pathNormal) {
                    return $fileMod;
                }
            } else {
                return modification($file);
            }
        }
        return $file;
    }

    /**
     * Формирование абсолютного пути к директории хранения файлов загружаемых расширением
     * @param boolean $public true - директория доступная по ссылке, false - недоступная, для хранения системных файлов
     * @param boolean $asUrl в формате URL (public only)
     * @return string
     */
    public function pathUpload($public = true, $asUrl = false)
    {
        $name = $this->getName();
        if ($public) {
            if ($asUrl) {
                return \bff::url('extensions/'.$name);
            }
            return \bff::path('extensions'.DS.$name);
        } else {
            return PATH_BASE.'files'.DS.'extensions'.DS.$name.DS;
        }
    }

    /**
     * Формирование структуры файлов по указанному пути
     * @param string $path относительный путь
     * @return array
     */
    public function pathStructure($path = '/')
    {
        $path = $this->extension_path . ltrim($path, DS.' ');
        $structure = array();

        $filter = array(
            'file' => [],
            'dir'  => ['.git','.svn'],
        );
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                function ($v) use ($filter) {
                  if ($v->isFile()) {
                      return !in_array($v->getFilename(), $filter['file']);
                  }
                  if ($v->isDir()) {
                      return !in_array($v->getFilename(), $filter['dir']);
                  }
                  return false;
                }
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $v) {
            if ($v->isDir() || $v->isFile()) {
                $structure[] = $iterator->getSubPathName();
            }
        }

        return $structure;
    }

    /**
     * Расширение упаковано в PHAR архив
     * @return boolean
     */
    public function isPhar()
    {
        return $this->extension_is_phar;
    }

    /**
     * Получение названия директории расширения по типу
     * @param integer $extensionType тип расширения
     * @return string
     */
    public static function dir($extensionType)
    {
        switch ($extensionType) {
            case EXTENSION_TYPE_PLUGIN: return 'plugins';
            case EXTENSION_TYPE_THEME:  return 'themes';
            case EXTENSION_TYPE_MODULE: return 'modules';
        }
        return '';
    }

    /**
     * Формирование URL файла
     * @param string $file название файла
     * @param mixed $version версия файла или false
     * @return string
     */
    public function url($file, $version = false)
    {
        if (BFF_DEBUG) {
            # Поиск: file.js => file-debug.js
            if (mb_substr(mb_strtolower($file), -3) === '.js') {
                $fileInfo = pathinfo($file);
                if (!empty($fileInfo['filename']) &&
                    mb_substr($fileInfo['filename'], -6) !== '-debug'
                ) {
                    $fileDebug = str_replace(
                        $fileInfo['filename'] . '.' . $fileInfo['extension'],
                        $fileInfo['filename'] . '-debug.' . $fileInfo['extension'], $file
                    );
                    if (is_file(PATH_PUBLIC . static::dir($this->extension_type) . DS . $this->getName() . DS . ltrim($fileDebug, DS . ' '))) {
                        $file = $fileDebug;
                    }
                }
            }
        }
        return \bff::url('/'.static::dir($this->extension_type).'/'.$this->getName().'/'.ltrim($file, '/ '), $version);
    }

    /**
     * Подключение javascript файла расширения
     * Файлы при этом следует хранить в ".../директория расширения/static/file.js"
     * @param string $file название javascript файла
     * @param mixed $version версия файла или false
     * @return string
     */
    public function js($file, $version = false, $priority = NULL)
    {
        \bff::hookAdd('js.extra', function() use ($file, $version) {
            \tpl::includeJS($this->url($file, $version), false);
        }, $priority);
    }

    /**
     * Подключение javascript файла расширения в панели администатора
     * @param string $file название javascript файла
     * @param mixed $version версия файла или false
     * @return string
     */
    public function jsAdmin($file, $version = false, $priority = NULL)
    {
        \bff::hookAdd('admin.js.extra', function() use ($file, $version) {
            \tpl::includeJS($this->url($file, $version), false);
        }, $priority);
    }

    /**
     * Подключение css файла расширения
     * Файлы при этом следует хранить в ".../директория расширения/static/file.css"
     * @param string $file название css файла
     * @param mixed $version версия файла или false
     * @return string
     */
    public function css($file, $version = false, $priority = NULL)
    {
        \bff::hookAdd('css.extra', function() use ($file, $version) {
            \tpl::includeCSS($this->url($file, $version), false);
        }, $priority);
    }

    /**
     * Подключение css файла расширения в панели администатора
     * @param string $file название css файла
     * @param mixed $version версия файла или false
     * @return string
     */
    public function cssAdmin($file, $version = false, $priority = NULL)
    {
        \bff::hookAdd('admin.css.extra', function() use ($file, $version) {
            \tpl::includeCSS($this->url($file, $version), false);
        }, $priority);
    }

    /**
     * Объявление модов
     * @return array [
     *   array 'list' - список модов
     * ]
     */
    public function mods()
    {
        $file = $this->path('mods.php');
        if (file_exists($file)) {
            $list = include $file;
            if (empty($list) || !is_array($list)) {
                $list = array();
            }
        }
        return array(
            'list' => (isset($list) ? $list : array()),
        );
    }

    /**
     * Обновление файлов статики расширения
     * @param boolean $install
     */
    public function refreshStatic($install)
    {
        $src = $this->extension_path.'static'.DS;
        $dst = PATH_PUBLIC.static::dir($this->extension_type).DS.$this->getName().DS;
        if ($install) {
            $this->installStatic($src, $dst, true);
        } else {
            $this->uninstallStatic($dst, $src, false);
        }
    }

    /**
     * Копирование файлов статики расширения
     * @param string $src исходный путь
     * @param string $dst конечный путь
     * @param bool $rewrite переписывать существующие
     * @return bool
     */
    protected function installStatic($src, $dst, $rewrite = true)
    {
        $mode = 0777;
        if ( ! is_dir($src)) {
            return false;
        }
        if ( ! file_exists($dst)) {
            if ( ! mkdir($dst, $mode, true)) {
                $this->errors->set(_t('system', 'Ошибка создания директории [path]', array('path' => $dst)));
                return false;
            }
        }
        $filter = \bff::filter('extension.static.install.ignore', array(
            'file' => ['.gitignore'],
            'dir'  => ['src','.git','.svn'],
        ));
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
                function ($v) use ($filter) {
                  if ($v->isFile()) {
                      return !in_array($v->getFilename(), $filter['file']);
                  }
                  if ($v->isDir()) {
                      return !in_array($v->getFilename(), $filter['dir']);
                  }
                  return false;
                }
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $v) {
            $to = $dst . $iterator->getSubPathName();
            if (($toExists = file_exists($to)) && !$rewrite) {
                continue;
            }
            if ($v->isDir()) {
                if ( ! file_exists($to) && ! mkdir($to, $mode)) {
                    $this->errors->set(_t('system', 'Ошибка создания директории [path]', array('path' => $to)));
                    return false;
                }
            } else if ($v->isLink()) {
                continue;
            } else {
                $modifyTime = filemtime($v);
                if ($toExists && (filesize($v) === filesize($to)) && ($modifyTime === filemtime($to))) {
                    continue;
                }
                if ( ! copy($v, $to)) {
                    $this->errors->set(_t('system', 'Ошибка создания файла [path]', array('path' => $to)));
                    return false;
                } else {
                    touch($to, $modifyTime);
                }
            }
        }
    }

    /**
     * Удаление файлов статики расширения
     * @param string $dir директория для удаления
     * @param string $dirOriginal оригинальная директория файлов
     * @param bool $keepChanges не выполнять удаление измененных и новых файлов
     * @return bool
     */
    protected function uninstallStatic($dir, $dirOriginal, $keepChanges = true)
    {
        if ( ! is_dir($dir)) {
            return true;
        }
        if ( ! is_readable($dir)) {
            $this->errors->set(_t('system', 'Ошибка удаления директории [path]', array('path' => $dir)));
            return false;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        $filesSkip = false;
        foreach ($iterator as $v) {
            $file = $v->getRealPath();
            if ($v->isDir()) {
                if ($filesSkip) {
                    if (!is_readable($file)) continue;
                    $handle = opendir($file);
                    while (false !== ($entry = readdir($handle))) {
                        if ($entry != '.' && $entry != '..') {
                            continue 2;
                        }
                    }
                }
                if ( ! rmdir($file)) {
                    $this->errors->set(_t('system', 'Ошибка удаления директории [path]', array('path' => $file)));
                }
            } else {
                if ($keepChanges) {
                    $fileOriginal = $dirOriginal . $iterator->getSubPathName();
                    if ( ! file_exists($fileOriginal)) {
                        $filesSkip = true; continue;
                    }
                    if (md5_file($fileOriginal) !== md5_file($file)) {
                        $filesSkip = true; continue;
                    }
                }
                if ( ! unlink($file)) {
                    $filesSkip = true;
                    $this->errors->set(_t('system', 'Ошибка удаления файла [path]', array('path' => $file)));
                }
            }
        }
        if ($filesSkip) {
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    return true;
                }
            }
        }
        if ( ! rmdir($dir)) {
            $this->errors->set(_t('system', 'Ошибка удаления директории [path]', array('path' => $dir)));
        }
    }

    /**
     * Установка SQL из файла
     * @param string $file абсолютный путь к файлу
     * @return bool
     */
    protected function installSqlFile($file)
    {
        if (!file_exists($file)) {
            return false;
        }
        $sql = file_get_contents($file);
        if (empty($sql)) {
            return false;
        }
        $sql = str_replace('/*TABLE_PREFIX*/', \DB_PREFIX, $sql);
        $res = $this->db->exec($sql);

        return ($res !== false);
    }

    /**
     * Содержимое файла инструкции расширения
     * Приоритет:
     * 1) {fileName}-{currentLanguage}.md
     * 2) {fileName}-{defaultLanguage}.md
     * 3) {fileName}.md
     * @param string $fileName имя файла без расширения
     * @return bool|string
     */
    public function instructionFile($fileName = 'readme')
    {
        $fileNames = array();
        $fileNames[] = $fileName.'-'.$this->locale->getCurrentLanguage();
        $fileNames[] = $fileName.'-'.$this->locale->getDefaultLanguage();
        $fileNames[] = $fileName;

        foreach ($fileNames as $v) {
            $file = $this->path($v.'.md');
            if (file_exists($file)) {
                return file_get_contents($file);
            }
        }
        return false;
    }

    /**
     * Поиск перевода для фразы
     * @param string $message фраза
     * @param array $params подстановочные данные
     * @param boolean $escape выполнять квотирование, false - не выполнять, 'html' (true), 'js'
     * @param string $language язык локализации, null - текущий
     * @return string
     */
    public function lang($message, array $params = array(), $escape = false, $language = null)
    {
        # translate
        $message = $this->locale->translate($this->extension_name, $message, $params, $language);

        # escape
        if ($escape === false) {
            return $message;
        }
        return \HTML::escape($message, ($escape === true ? 'html' : $escape));
    }

    /**
     * Поиск перевода для фразы интерфейса дополнения в админ. панели
     * @param string $message фраза
     * @param array $params подстановочные данные
     * @param boolean $escape выполнять квотирование, false - не выполнять, 'html' (true), 'js'
     * @param string $language язык локализации, null - текущий
     * @return string
     */
    public function langAdmin($message, array $params = array(), $escape = false, $language = null)
    {
        # translate
        $message = $this->locale->translate($this->extension_name.'/admin', $message, $params, $language);

        # escape
        if ($escape === false) {
            return $message;
        }
        return \HTML::escape($message, ($escape === true ? 'html' : $escape));
    }

    /**
     * Язык расширения по-умолчанию, null - язык сайта по-умолчанию
     * @return string ключ языка: 'en', ...
     */
    public function getLangDefault()
    {
        return null;
    }

    /**
     * Формирование полного названия метода вызываемого роутом
     * @param string|callable $callback название метода плагина
     * @param string $params дополнительные параметры получаемые из URL
     * @return string|callable
     */
    public function routeAction($callback, $params = '')
    {
        if (is_string($callback) && mb_stripos($callback, '::') === false) {
            return $this->getName() . '/' . $callback . '/' . $params;
        } else {
            return $callback;
        }
    }

    /**
     * Добавление роута
     * @param string $id идентификатор роута
     * @param array $settings параметры роута
     * @return \Hook|boolean
     */
    public function routeAdd($id, array $settings = array())
    {
        if (empty($id)
            || !isset($settings['pattern'])
            || !isset($settings['callback'])
            ) {
            return false;
        }
        return \bff::hooks()->routes(function ($routes) use ($id, $settings) {
            $routes[$id] = $settings;
            return $routes;
        });
    }

    /**
     * Формирование URL для вызова метода плагина
     * @param string $methodName название публичного метода плагина
     * @param array $options доп. параметры: module, query, query-ignore, escape
     * @param boolean|null $adminPanel контекст вызова null - текущий, true - admin panel, false - frontend
     */
    public function urlAction($methodName, array $options = array(), $adminPanel = null)
    {
        if (is_null($adminPanel)) {
            $adminPanel = \bff::adminPanel();
        }
        $moduleName = (!empty($options['module']) ? $options['module'] : $this->getName());
        if ( ! $adminPanel) {
            $url = '/index.php?s='.$moduleName.'&ev='.$methodName;
        } else {
            $url = \tpl::adminLink($methodName, $moduleName);
        }
        if (!empty($options['query'])) {
            $url .= \Module::urlQuery($options['query'], (isset($options['query-ignore']) ? $options['query-ignore'] : array()), '&');
        }
        return (!empty($options['escape']) ? \HTML::escape($url, $options['escape']) : $url);
    }

    /**
     * Добавление пункта меню в админ. панели
     * @param string $tab название основного раздела меню
     * @param string $title название пункта меню
     * @param string $method название метода плагина
     * @param bool $isVisible показывать пункт меню по-умолчанию
     * @param int $priority приоритет, определяет порядок подразделов в пределах раздела
     * @param array $options дополнительные настройки @see CMenu::assign
     */
    public function adminMenu($tab, $title, $method, $isVisible = true, $priority = 999, array $options = array())
    {
        \bff::hooks()->adminMenuBuild(function() use ($tab, $title, $method, $isVisible, $priority, $options){
            \bff::adminMenu()->assign($tab, $title, $this->getName(), $method, $isVisible, $priority, $options);
        });
    }

    /**
     * Формирование php-шаблона расширения
     * @param array $aData @ref данные, которые необходимо передать в шаблон
     * @param string $templateName название шаблона, без расширения ".php"
     * @param string|boolean $templateDir путь к шаблону или false - используем путь к директории текущего расширения
     * @param boolean $display отображать(true), возвращать результат(false)
     * @return string
     */
    public function viewPHP(array &$aData, $templateName, $templateDir = false, $display = false)
    {
        return \View::render(function($filePath_) use (&$aData){
                extract($aData, EXTR_REFS);
                require $filePath_;
            },
            $aData, $templateName,
            (empty($templateDir) ? $this->extension_path : $templateDir),
            'view.'.$this->extension_name, # view.{type}.{name}
            $display
        );
    }
}