<?php namespace bff\base;

/**
 * Базовый класс работы с отображением шаблонов
 * @version 1.3
 * @modified 23.aug.2018
 * @copyright Tamaranga
 */

class View
{
    /** @var string текущий layout */
    protected static $layout = 'main';

    /** @var array данные текущей страниц */
    protected static $pageData = [];

    /**
     * Порядок работы с содержимым блока шаблона
     * @var integer
     */
    const BLOCK_CREATE = 1;
    const BLOCK_AFTER  = 2;
    const BLOCK_BEFORE = 3;
    const BLOCK_RENDER = 4;

    /** @var array сохраненные блоки */
    protected $blocks = [];
    /** @var array открытые блоки */
    protected $blocks_open = [];

    /**
     * Получаем данные текущей страницы
     * @param string $key ключ требуемых данных
     * @param bool $default значение по умолчанию
     * @return mixed
     */
    public static function pageData($key, $default = false)
    {
        if (array_key_exists($key, static::$pageData)) {
            return static::$pageData[$key];
        }
        return $default;
    }

    /**
     * Сохраняем данные текущей страницы
     * @param string|array $key ключ данных
     * @param mixed $value значение
     */
    public static function setPageData($key, $value = false)
    {
        if (is_string($key)) {
            static::$pageData[$key] = $value;
        } else if (is_array($key)) {
            foreach ($key as $k=>$v) {
                if (is_string($k)) {
                    static::$pageData[$k] = $v;
                }
            }
        }
    }

    /**
     * Отрисовываем блок
     * @param string $id ID блока
     * @param string|callable $content содержимое блока
     * @param bool $return возвращать содержимое
     * @return mixed
     */
    public static function block($id, $content = '', $return = false)
    {
        if ( ! is_string($content) && is_callable($content, true)) {
            $content = call_user_func($content);
        }
        if (is_string($content)) {
            $filter = 'view.block.'.$id;
            if (\bff::hooksAdded($filter)) {
                $content = \bff::filter($filter, $content);
            }
            if ($return) {
                return $content;
            } else {
                echo $content;
            }
        }
    }

    /**
     * Открываем новый блок
     * @param string $id ID блока
     * @param boolean|integer $render отрисовывать содержимое блока при его закрытии
     * @return bool
     */
    public function start($id, $render = true)
    {
        if (array_key_exists($id, $this->blocks_open)) {
            return false;
        }
        if ( ! is_integer($render)) {
            $render = ($render === true ? static::BLOCK_RENDER : static::BLOCK_CREATE);
        }

        ob_start();
        ob_implicit_flush(false);
        $this->blocks_open[$id] = $render;

        return true;
    }

    /**
     * Открываем новый блок
     * @param string $id ID блока
     * @param boolean|integer $render отрисовывать содержимое блока при его закрытии
     * @return bool
     */
    public static function blockStart($id, $render = true)
    {
        return \bff::view()->start($id, $render);
    }

    /**
     * Закрываем блок
     * @return boolean
     */
    public function end()
    {
        if (empty($this->blocks_open)) {
            return false;
        }

        $content = ltrim(ob_get_clean());
        $action = end($this->blocks_open);
        $id = key($this->blocks_open);
        switch ($action)
        {
            case static::BLOCK_CREATE: {
                $this->blocks[$id] = $content;
            } break;
            case static::BLOCK_RENDER: {
                static::block($id, $content, false);
            } break;
            case static::BLOCK_AFTER: {
                $this->extend($id, $content, true);
            } break;
            case static::BLOCK_BEFORE: {
                $this->extend($id, $content, false);
            } break;
        }
        array_pop($this->blocks_open);

        return true;
    }

    /**
     * Закрываем блок
     * @return boolean
     */
    public static function blockEnd()
    {
        return \bff::view()->end();
    }

    /**
     * Расширяем содержимое блока
     * @param string $id ID блока
     * @param string $content содержимое
     * @param bool $after добавить после имеющегося содержимого
     */
    public function extend($id, $content, $after = true)
    {
        if (array_key_exists($id, $this->blocks)) {
            if ($after) {
                $this->blocks[$id] .= $content;
            } else {
                $this->blocks[$id] = $content . $this->blocks[$id];
            }
        } else {
            $this->blocks[$id] = $content;
        }
    }

    /**
     * Устанавливаем содержимое блока
     * @param string $id ID блока
     * @param string $content содержимое блока
     */
    public function set($id, $content = '')
    {
        $this->blocks[$id] = strval($content);
    }

    /**
     * Получаем содержимое блока
     * @param string $id ID блока
     * @param string $default содержимое блока по умолчанию
     */
    public function get($id, $default = '')
    {
        return static::block($id, (
            array_key_exists($id, $this->blocks) ? $this->blocks[$id] : $default
        ), true);
    }

    /**
     * Рендеринг шаблона (php)
     * @param callable $callback функция выполняющая подключение файла шаблона, принимает путь к файлу
     * @param array $data @ref данные, передаваемые в шаблон
     * @param string $templateName имя файла php шаблона без расширения
     * @param string $templateDir путь к файлу
     * @param string $hookPrefix префикс для хука
     * @param bool $display отображать
     * @return mixed|string
     */
    public static function render(callable $callback, array &$data, $templateName, $templateDir, $hookPrefix, $display = false)
    {
        # common templates (TPL_PATH)
        if (empty($templateDir) || $templateDir === TPL_PATH) {
            $templateDir = TPL_PATH;
            $hookPrefix = 'view.tpl';
        }

        # relative path
        $relPath = DS . rtrim(mb_substr($templateDir, mb_strlen(PATH_BASE)), DS.' ');

        # themes
        $themeDir = \bff::themeFile(($themeFile = ($relPath . DS . $templateName . '.php')), false);
        if ($themeDir !== false) {
            $filePath = $themeDir . $themeFile;
        } else {
            $filePath = rtrim($templateDir, DS.' ') . DS . $templateName . '.php';
        }

        # modifications
        $filePath = modification($filePath);

        # hook: data
        $hook = $hookPrefix.'.'.$templateName;
        $hookData = array(
            'data'     => &$data,
            'relPath'  => $relPath,
            'filePath' => $filePath,
            'fileName' => $templateName.'.php',
            'theme'    => !empty($themeDir),
        );
        \bff::hook($hook.'.data', $hookData);

        if (!$display) {
            ob_start();
            ob_implicit_flush(false);
            $callback($filePath);

            # hook: html filter
            if (\bff::hooksAdded($hook)) {
                unset($hookData['data']);
                return \bff::filter($hook, ltrim(ob_get_clean()), $data, $hookData);
            } else {
                return ltrim(ob_get_clean());
            }
        } else {
            $callback($filePath);
        }
    }

    /**
     * Рендеринг шаблона (php)
     * @param array $aData @ref данные, которые необходимо передать в шаблон
     * @param string $templateName название шаблона (без расширения ".php")
     * @param string|boolean $templateDir путь к шаблону или false = TPL_PATH
     * @param boolean $display отображать(true), возвращать результат(false)
     * @return mixed
     */
    public static function renderTemplate(array &$aData, $templateName, $templateDir = false, $display = false)
    {
        return static::render(function($filePath_) use (&$aData){
                extract($aData, EXTR_REFS);
                require $filePath_;
            },
            $aData, $templateName, $templateDir,
            'view.tpl', # view.tpl
            $display
        );
    }

    /**
     * Рендеринг шаблона (php)
     * @param string $templateName название шаблона (без расширения ".php")
     * @param array $data данные, которые необходимо передать в шаблон
     * @param string|bool $moduleName название модуля или плагина или false (TPL_PATH) или путь к файлу
     * @return string HTML
     */
    public static function template($templateName, array $data = array(), $moduleName = false)
    {
        if (empty($moduleName) || !is_string($moduleName)) {
            return static::renderTemplate($data, $templateName);
        }
        if (\bff::moduleExists($moduleName)) {
            return \bff::module($moduleName)->viewPHP($data, $templateName);
        } else if (\bff::pluginExists($moduleName)) {
            return \bff::plugin($moduleName)->viewPHP($data, $templateName);
        } else if (is_dir($moduleName)) {
            return static::renderTemplate($data, $templateName, $moduleName);
        }
        return '';
    }

    /**
     * Формирование пути к файлу шаблона (php)
     * @param string $templateName имя файла php шаблона без расширения
     * @param string $templateDir путь к файлу
     * @param boolean $isTheme @ref сформированный путь к файлу ссылается на файл из темы
     * @return string
     */
    public static function templatePath($templateName, $templateDir, &$isTheme = false)
    {
        # relative path
        $relPath = DS . rtrim(mb_substr($templateDir, mb_strlen(PATH_BASE)), DS.' ');
        # themes
        $themeDir = \bff::themeFile(($themeFile = ($relPath . DS . $templateName . '.php')), false);
        if ($isTheme = ($themeDir !== false)) {
            $filePath = $themeDir . $themeFile;
        } else {
            $filePath = rtrim($templateDir, DS.' ') . DS . $templateName . '.php';
        }
        # modifications
        return modification($filePath);
    }

    /**
     * Рендеринг layout шаблона (php)
     * @param array $aData @ref данные, которые необходимо передать в шаблон
     * @param string|boolean $layoutName название layout'a (без расширения ".php")
     * @param string|boolean $templateDir путь к шаблону или false - используем TPL_PATH
     * @param boolean $display отображать(true), возвращать результат(false)
     * @return mixed
     */
    public static function renderLayout(array &$aData, $layoutName = false, $templateDir = false, $display = false)
    {
        if (empty($layoutName)) {
            $layoutName = static::getLayout();
        }
        return static::renderTemplate($aData, 'layout.' . (empty($layoutName) ? 'main' : $layoutName), $templateDir, $display);
    }

    /**
     * Устанавливаем layout
     * @param string $layoutName название
     * @return string
     */
    public static function setLayout($layoutName = '')
    {
        static::$layout = $layoutName;
    }

    /**
     * Получаем текущий layout
     * @return string
     */
    public static function getLayout()
    {
        return static::$layout;
    }
}