<?php namespace bff\tpl\admin;

use bff\Component;
use View;

/**
 * Компонент построения HTML блока в админ. панели
 */
abstract class Block extends Component
{
    /** @var \Module объект контроллера */
    protected $controller;

    /** @var string название метода контроллера работающего с блоком */
    protected $controllerAction;

    /** @var Block parent блок */
    protected $parent;

    /** @var string путь к директории шаблонов блока */
    private $templateDir = '';

    /** @var string название файла шаблона блока (без расширения .php) */
    private $templateName = '';

    /** @var array блок JavaScript код отображаемый сразу после блока */
    protected $templateJs = ['includes'=>[], 'code'=>[]];

    /** @var string название глобального объекта JavaScript кода отвечающего за управление блоком */
    protected $templateJsObject = '';

    /** @var string префикс CSS класса блока */
    private $cssClass = '';

    public function init()
    {
        if ( ! parent::init() ) {
            return false;
        }
        return true;
    }

    /**
     * Устанавливаем путь к шаблонам
     * @param string к директории шаблонов
     */
    public function setTemplateDir($path)
    {
        $this->templateDir = $path;
    }

    /**
     * Получаем путь к шаблонам
     * @return string
     */
    public function getTemplateDir()
    {
        return $this->templateDir;
    }

    /**
     * Устанавливаем название файла шаблона блока
     * @param string название файла без расширения '.php'
     */
    public function setTemplateName($name)
    {
        $this->templateName = $name;
    }

    /**
     * Устанавливаем контроллер и его метод
     * @param \Module $controller объект контроллера
     * @param string|null $action название метода контроллера
     */
    public function setController($controller, $action = null)
    {
        $this->controller = $controller;
        if ($action !== null) {
            $this->controllerAction = $action;
        }
    }

    /**
     * Получаем объект контроллера
     * @return \Module $controller объект контроллера
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Получаем название контроллера
     * @return string
     */
    public function getControllerName()
    {
        if ($this->controller !== null) {
            if (is_a($this->controller, '\Module')) {
                return $this->controller->module_name;
            }
        }
        return '';
    }

    /**
     * Получаем название метода контроллера
     * @return string название метода
     */
    public function getControllerAction()
    {
        return $this->controllerAction;
    }

    /**
     * Возвращаем префикс класса блока
     * @param string $plus
     * @return string
     */
    public function cssClass($plus = '')
    {
        if ($this->cssClass === '') {
            $this->cssClass = join('-', [
                'j',
                $this->getControllerName(),
                $this->getControllerAction(),
                'block',
            ]);
        }
        return $this->cssClass . $plus;
    }

    /**
     * Устанавливаем parent блок
     * @param Block $parent
     */
    protected function setParent(Block $parent)
    {
        $this->setController($parent->getController(), $parent->getControllerAction());
        $this->setTemplateDir($parent->getTemplateDir());
        $this->parent = $parent;
    }

    /**
     * Получаем parent блок
     * @return Block
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Проверка наличия parent блока
     * @return boolean
     */
    public function hasParent()
    {
        return $this->parent !== null;
    }

    /**
     * Отрисовываем блок
     * @param array $data доп. данные шаблона
     * @return string HTML
     */
    public function view(array $data = [])
    {
        return $this->render($data);
    }

    /**
     * Действия выполняемые перед отрисовкой блока
     */
    protected function beforeRender()
    {
    }

    /**
     * Отрисовка блока
     * @param array $dataExtra дополнительные данные, передаваемые в шаблон
     * @param string|boolean $templateName название шаблона или false шаблон блока по умолчанию
     * @return string HTML
     */
    protected function render(array $dataExtra = [], $templateName = false)
    {
        $this->beforeRender();

        if (empty($templateName)) {
            $templateName = $this->templateName;
        }

        $aData = $dataExtra;
        $aData['block'] = $this;
        $aData['jsObject'] = $this->templateJsObject;

        return View::render(function($filePath_) use (&$aData){
                extract($aData, EXTR_REFS);
                require $filePath_;
            },
            $aData, $templateName, $this->templateDir,
            'view.tpl', # view.tpl
            false
        ) . ( empty($dataExtra['noJsRender']) ? $this->jsRender() : '');
    }

    /**
     * Подключаем JavaScript файлы
     * @param string|array $include название библиотеки (без расширения ".js") или полный URL
     * @param bool $core подключаем версию ядра
     * @param bool $version версия подключаемого файла или FALSE
     */
    public function jsInclude($include, $core = true, $version = false)
    {
        if (empty($include)) {
            return;
        }
        if (is_array($include)) {
            foreach ($include as $v) {
                $this->templateJs['includes'][] = strval($v);
            }
        } elseif (is_string($include)) {
            $this->templateJs['includes'][] = array('file'=>$include, 'core'=>$core, 'version'=>(!$core ? $version : false));
        }
    }

    /**
     * Начало блока с JavaScript кодом
     * @param string $jsObjectName название объекта отвечающего за управление блоком
     */
    public function jsStart($jsObjectName = '')
    {
        if ( ! empty($jsObjectName) && is_string($jsObjectName)) {
            $this->templateJsObject = $jsObjectName;
        }
        ob_start();
        ob_implicit_flush(false);
    }

    /**
     * Завершаем блок с JavaScript кодом
     */
    public function jsStop()
    {
        $this->templateJs['code'][] = ltrim(ob_get_clean());
    }

    /**
     * Рендеринг блоков JavScript кода
     * @param array $attr атрибуты <script> тегов
     * @return string
     */
    public function jsRender(array $attr = array())
    {
        foreach ($this->templateJs['includes'] as $key) {
            if (is_string($key)) {
                \tpl::includeJS($key, true);
            } else if (is_array($key) && ! empty($key['file'])) {
                 \tpl::includeJS($key['file'], ! empty($key['core']), $key['version']);
            }
        }

        $return = '';
        foreach ($this->templateJs['code'] as $code) {
            if (mb_stripos($code, '<script') === false) {
                if (!array_key_exists('type', $attr)) {
                    $attr['type'] = 'text/javascript';
                }
                $return .= '<script' . \HTML::attributes($attr) . '>' . $code . '</script>';
            }
            $return .= $code;
        }
        return $return;
    }
}