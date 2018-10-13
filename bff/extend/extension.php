<?php namespace bff\extend;

/**
 * Плагинизация: расширение
 * @abstract
 * @version 2.43
 * @modified 31.aug.2018
 * @copyright Tamaranga
 */

const EXTENSION_TYPE_PLUGIN = 1;
const EXTENSION_TYPE_THEME  = 2;
const EXTENSION_TYPE_MODULE = 3;

use \Logger;
use bff\utils\Files;

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
    protected $extension_config_templates = array();
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
    protected $extension_dependencies_status = true;

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
     * CSS файлы стилей доступные для редактирования в настройках
     * @var array
     */
    protected $extension_css_edit = [];

    /**
     * Расширение является аддоном, если указано одно и более расширение
     * @var array
     */
    protected $extension_addon_for = [];

    /**
     * Список объектов аддонов данного расширения
     * @var \SplObjectStorage
     */
    protected $extension_addons = [];

    /**
     * Получение идентификатора расширения
     * @param boolean $fallbackToName возвращать имя в случае если идентификатор не указан
     * @param boolean $fallbackParent поиск идентификаторав у parent-расширений
     * @return string
     */
    public function getExtensionId($fallbackToName = false, $fallbackParent = false)
    {
        if (!empty($this->extension_id)) {
            return $this->extension_id;
        }
        if ($fallbackParent) {
            if ($this->isTheme() && $this->isChildTheme()) {
                # Идентификатор исходной темы:
                return $this->getParentTheme()->getExtensionId($fallbackToName, $fallbackParent);
            }
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
     * Является ли расширение темой
     * @return bool
     */
    public function isTheme()
    {
        return $this->extension_type === EXTENSION_TYPE_THEME;
    }

    /**
     * Является ли расширение плагином
     * @return bool
     */
    public function isPlugin()
    {
        return $this->extension_type === EXTENSION_TYPE_PLUGIN;
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
     * Расширение является аддоном
     * @return boolean
     */
    public function isAddon()
    {
        return ! empty($this->extension_addon_for);
    }

    /**
     * Заявляем аддон для расширения
     * @param string $id идентификатор расширения или THEME_BASE
     * @param string $version версия расширения
     */
    public function setAddonFor($id, $version)
    {
        if ( ! empty($id) && ! empty($version)) {
            $this->extension_addon_for[$id] = array('version'=>$version);
        }
    }

    /**
     * Поддерживает ли аддон указанное расширение
     * @param \bff\extend\Extension $extension объект расширения
     * @return boolean
     */
    public function isAddonFor($extension)
    {
        return array_key_exists((is_object($extension) ? $extension->getExtensionId(true) : $extension), $this->extension_addon_for);
    }

    /**
     * Закрепляем связь расширения с аддоном
     * @param \bff\extend\Extension $addon
     * @return boolean
     */
    public function addAddon($addon)
    {
        if (is_array($this->extension_addons)) {
            $this->extension_addons = new \SplObjectStorage();
        }
        if ( ! $this->extension_addons->contains($addon)) {
            $this->extension_addons->attach($addon, $addon->getName());
            return true;
        }
        return false;
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
     * Метод вызываемый при старте расширения
     */
    public function extensionStart()
    {
        foreach ($this->extension_config_templates as $id => &$template) {
            if ($template['onStart'] !== null && ! empty($template['settings'])) {
                call_user_func($template['onStart'], $template['settings']);
            }
        } unset($template);
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
        if ( ! array_key_exists('tabs', $options)) {
            $options['tabs'] = array();
        }
        if ( ! empty($this->extension_config_templates)) {
            $tabsPlus = [];
            $settingsCount = sizeof($settings);
            foreach ($this->extension_config_templates as $id=>$template) {
                if (empty($template['settings'])) continue;
                if ( ! empty($template['keys.overrideBy']) && $settingsCount > 0) {
                    foreach ($template['keys.overrideBy'] as $k) {
                        if (array_key_exists($k, $settings)) {
                            continue 2;
                        }
                    }
                }
                foreach ($template['settings'] as &$sett) {
                    $sett = \config::merge($template['template'], $sett);
                    if (empty($sett['tab'])) { $settingsCount++; }
                    $settings[$sett['config.key']] = &$sett;
                } unset($sett);
                if ( ! empty($template['tab']['key'])) {
                    $tabsPlus[$template['tab']['key']] = $template['tab'];
                }
            }
            if ($settingsCount > 0 && sizeof($tabsPlus) > 0 && empty($options['tabs'])) {
                $options['tabs']['def'] = ['title'=>_t('ext', 'Общие')];
                foreach ($tabsPlus as $k=>$v) {
                    $options['tabs'][$k] = $v;
                }
            }
        }
        $this->extension_config = $settings;
        $this->extension_config_options = array_merge($this->extension_config_options, $options);
        \config::extension($this->extension_name, $this->extension_config, $this);
    }

    /**
     * Регистрация шаблона настроек расширения
     * @param string $id уникальный ключ шаблона
     * @param array $config настройки шаблона: [
     *      string 'key' - ключ настройки по умолчанию
     *      array 'keys.overrideBy' - ключи настроек расширения, подавляющие данную шаблонную настройку
     *      array 'template' - шаблон [
     *          string 'title' - название настройки
     *          string 'input' - тип поля: 'select', 'checkbox', 'number', ...
     *          int 'type' - тип данных: TYPE_NOTAGS, TYPE_UINT, TYPE_BOOL ...
     *          ...
     *      ]
     *      array 'tab' - таб настройки [
     *          string 'key' - ключ таба
     *          string 'title' - название таба
     *      ]
     * ]
     * @param \Closure $onStart функция вызываемая в момент старта плагина/темы
     * @param array $settings шаблонные настройки по умолчанию: [
     *      //
     * ]
     */
    protected function configSettingsTemplateRegister($id, array $config, \Closure $onStart = null, array $settings = [])
    {
        $this->extension_config_templates[$id] = array_merge([
            'template'        => [],
            'key'             => '_'.$id,
            'tab'             => [],
            'keys.overrideBy' => [],
            'onStart'         => $onStart,
            'settings'        => [],
        ], $config);
        if ( ! empty($settings)) {
            $this->configSettingsTemplate($id, $settings, false);
        }
    }

    /**
     * Определяем шаблонные настройки
     * @param string $id уникальный ключ шаблона
     * @param array $settings настройки
     * @param bool $extend дополнить
     * @return boolean
     */
    protected function configSettingsTemplate($id, array $settings, $extend = false)
    {
        if ( ! array_key_exists($id, $this->extension_config_templates)) {
            return false;
        }
        $template = &$this->extension_config_templates[$id]['settings'];
        if ( ! $extend) {
            $template = array();
        }
        foreach ($settings as $key=>$params) {
            if (empty($key) || !is_string($key) || !is_array($params)) {
                continue;
            }
            if ($extend && array_key_exists($key, $template)) {
                $template[$key] = \config::merge($template[$key], $params);
            } else {
                $template[$key] = $params;
            }
            if (empty($template[$key]['config.key'])) {
                $template[$key]['config.key'] = $this->extension_config_templates[$id]['key'].'.'.$key;
            }
        }
        return true;
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
                    'custom' => true,
                    'priority' => (isset($options['priority']) ? $options['priority'] : 1),
                );
                return $tabs;
            },
            $hookPrefix.'tabs.content' => function($data) use ($content, $tabKey, $options) {
                echo '<div class="j-tab j-tab-'.$tabKey.($data['tabActive'] !== $tabKey ? ' hidden':'').'">';
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
                if ( ! empty($setting['lang'])) {
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
        /** @var \bff\extend\ExtensionImage $obj */
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
        if ($obj === false) {
            return array();
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
     * Формирование абсолютного пути к файлу в public директории расширения
     * @param string $file относительный путь к файлу
     * @param boolean $mod разрешать модифицикацию
     * @param boolean $custom путь к custom версии файла
     * @return string
     */
    public function pathPublic($file, $mod = true, $custom = false)
    {
        $file = PATH_PUBLIC.(!$mod && $custom ? 'custom'.DS : '').static::dir($this->extension_type).DS.$this->getName().DS.ltrim($file, DS.' ');
        if ($mod) {
            return modification($file);
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
     * @param array|boolean $filesOnly только файлы, список доступных расширений
     * @return array
     */
    public function pathStructure($path = '/', $filesOnly = false)
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
                function ($v) use ($filter, $filesOnly) {
                  if ($v->isFile()) {
                      if (is_array($filesOnly) && ! in_array($v->getExtension(), $filesOnly)) {
                          return false;
                      }
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
            if ($v->isFile()) {
                $structure[] = $iterator->getSubPathName();
            } else if ($v->isDir() && $filesOnly === false) {
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
     * Регистрация автозагрузки по алиасу расширения /(plugins|themes)/{alias}/
     * @param Extension $extension объект расширения
     * @param string $alias алиас расширения
     * @return boolean
     */
    public static function registerAliasAutoloader($extension, $alias)
    {
        $sep = '\\';
        $classPrefix = static::dir($extension->getExtensionType()).$sep.trim($alias, $sep);
        return spl_autoload_register(function($className) use ($classPrefix, $sep, $extension) {
            $className = ltrim($className, $sep);
            if (mb_stripos($className, $classPrefix) === 0) {
                $className = str_replace($sep, DS, str_replace($classPrefix.$sep, $extension->path(DS), $className));
                if (file_exists($className.'.php')) {
                    require_once $className.'.php';
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Регистрируем дополнительный модуль
     * @param string $name название модуля (латиницей)
     * @param string $path путь к директории модуля, относительно директории расширения
     * @param array $opts дополнительные параметры
     * @return boolean удалось ли зарегистрировать модуль
     */
    public function moduleRegister($name, $path = '', array $opts = array())
    {
        return \bff::i()->moduleRegister($name, $this->path($path), $opts);
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
        if ($this->extension_type === EXTENSION_TYPE_THEME) {
            return \bff::url('/' . ltrim($file, '/ '), $version);
        } else {
            return \bff::url('/' . static::dir($this->extension_type) . '/' . $this->getName() . '/' . ltrim($file, '/ '), $version);
        }
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
     * Устанавливаем/получаем CSS файлы стилей доступные для редактирования в настройках
     * @param array|null $files список файлов, или вернуть текущие (null)
     *  [
     *      'key' => [
     *          'path'  => '/static/css/main.css',
     *          'save'  => false(readonly) | true(src+custom) | 'custom'(custom only),
     *          'title' => 'main.css',
     *      ],
     *      ...
     *  ]
     * @param boolean $extend дополнить текущий список
     * @return array
     */
    public function cssEdit($files = null, $extend = false)
    {
        if (is_array($files)) {
            $update = [];
            foreach ($files as $key=>$file) {
                if (!is_string($key) || !is_array($file) || empty($file['path'])) {
                    continue;
                }
                # key
                $file['key'] = $key;
                # save
                if ( ! isset($file['save']) || ! in_array($file['save'], [false, true, 'custom'], true)) {
                    $file['save'] = true;
                }
                # readonly
                $file['readonly'] = ($file['save'] === false);
                # path: custom
                if ( ! $file['readonly'] && ! isset($file['path_custom'])) {
                    $file['path_custom'] = $this->pathPublic(strtr($file['path'], [
                        $this->extension_path . 'static' . DS => DS,
                        $this->extension_path                 => DS,
                    ]), false, true);
                }
                $update[$key] = $file;
            }
            if ($extend) {
                $this->extension_css_edit = array_merge($this->extension_css_edit, $update);
            } else {
                $this->extension_css_edit = $update;
            }
        } else {
            if ($this->isTheme() && $this->isChildTheme()) {
                $parentCSS = $this->getParentTheme()->cssEdit();
                $mainKey = static::CSS_FILE_MAIN;
                if (isset($parentCSS[$mainKey])) {
                    $this->extension_css_edit[$mainKey] = array_merge($parentCSS[$mainKey], [
                        'priority' => -1,
                        'readonly' => true,
                        'save'     => false,
                    ]);
                }
            }
        }
        return $this->extension_css_edit;
    }

    /**
     * Загружаем/сохраняем содержимое CSS файла для редактирования
     * @param string $fileKey ключ файла
     * @param string|bool $content содержимое для сохранения, false - получить текущее содержимое
     * @return string|bool содержимое файла или false ошибка
     */
    public function cssEditContent($fileKey, $save = false)
    {
        if ($save !== false && is_string($save))
        {
            if (empty($this->extension_css_edit[$fileKey]['path'])) {
                return false;
            }
            $file = $this->extension_css_edit[$fileKey];
            if ($file['readonly']) {
                return false;
            }
            $paths = [];
            $paths[] = $file['path_custom'];
            if ($file['save'] === true) {
                $paths[] = $file['path'];
            }
            if (is_string($save)) {
                \bff\utils\TextParser::cleanUtf8($save);
            }
            $success = false;
            foreach ($paths as $path) {
                if ( ! file_exists($path)) {
                    $pathDir = dirname($path);
                    if ( ! is_dir($pathDir)) {
                        if ( ! Files::makeDir($pathDir, 0775, true)) {
                            continue;
                        }
                    }
                }
                if (file_put_contents($path, $save) !== false) {
                   $success = true;
                }
            }
            return $success;
        } else {
            $content = '';
            do {
                if (empty($this->extension_css_edit[$fileKey]['path'])) {
                    break;
                }
                $file = $this->extension_css_edit[$fileKey];
                $path = $file['path'];
                if (!$file['readonly'] && !empty($file['path_custom']) && file_exists($file['path_custom'])) {
                    $path = $file['path_custom'];
                }
                if (file_exists($path)) {
                    if ($save === -1) {
                        header('Content-disposition: attachment; filename="'.pathinfo($path, PATHINFO_BASENAME).'"');
                        header('Content-type: text/plain');
                        header('Content-Length: '.filesize($path));
                        readfile($path);
                        \bff::shutdown();
                    }
                    $content = file_get_contents($path);
                    if ($content !== false) {
                        \bff\utils\TextParser::cleanUtf8($content);
                    } else {
                        $content = '';
                    }
                }
            } while (false);
            return $content;
        }
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
            try {
                $list = include $file;
                if (empty($list) || !is_array($list)) {
                    $list = array();
                }
            } catch(\Throwable $e) {
                $this->log($e->getMessage() . ', ' . $e->getFile() . ' [' . $e->getCode() . ']');
            } catch(\Exception $e) {
                $this->log($e->getMessage() . ', ' . $e->getFile() . ' [' . $e->getCode() . ']');
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
        $dst = $this->pathPublic('', false);
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
     * @param array $opts дополнительные настройки
     * @return string
     */
    public function lang($message, array $params = array(), $escape = false, $language = null, array $opts = array())
    {
        # translate
        $message = $this->locale->translate($this->extension_name, $message, $params, $language, $opts);

        # escape
        if ($escape === false) {
            return $message;
        }
        return \HTML::escape($message, ($escape === true ? 'html' : $escape));
    }

    /**
     * Поиск перевода для фразы интерфейса расширения в админ. панели
     * @param string $message фраза
     * @param array $params подстановочные данные
     * @param boolean $escape выполнять квотирование, false - не выполнять, 'html' (true), 'js'
     * @param string $language язык локализации, null - текущий
     * @param array $opts дополнительные настройки
     * @return string
     */
    public function langAdmin($message, array $params = array(), $escape = false, $language = null, array $opts = array())
    {
        # translate
        $message = $this->locale->translate($this->extension_name.'/admin', $message, $params, $language, $opts);

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
     * @param array $opts доп. настройки
     * @return boolean
     */
    public function routeAdd($id, array $settings = array())
    {
        if (empty($id)
            || !isset($settings['pattern'])
            || !isset($settings['callback'])
            ) {
            return false;
        }
        return \bff::router()->add($id, $settings);
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