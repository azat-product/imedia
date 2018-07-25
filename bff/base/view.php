<?php namespace bff\base;

/**
 * Базовый класс работы с отображением шаблонов
 * @version 0.63
 * @modified 7.sep.2017
 */

class View
{
    /** @var string $layout текущий layout */
    protected static $layout = 'main';

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