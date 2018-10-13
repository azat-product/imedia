<?php namespace bff;

/**
 * Базовый абстрактный класс компонента
 * @abstract
 * @version 0.61
 * @modified 27.aug.2018
 * @copyright Tamaranga
 */
abstract class Component
{
    /** @var \Errors object */
    public $errors = null;
    /** @var \Security object */
    public $security = null;
    /** @var \bff\db\Database object */
    public $db = null;
    /** @var \bff\base\Input object */
    public $input = null;
    /** @var \bff\base\Locale object */
    public $locale = null;
    /** @var \bff\Router object */
    public $router = null;
    /** @var \View object */
    public $view = null;
    /** @var \CSmarty object */
    protected $sm = false;
    protected $tpl_vars = array();

    protected $sett = array();
    private $_initialized = false;

    /**
     * Блокируем копирование/клонирование объекта
     */
    final private function __clone()
    {
    }

    /**
     * Инициализация компонента
     * @return mixed
     */
    public function init()
    {
        if ($this->getIsInitialized()) {
            return false;
        }

        if ($this->errors === null) {
            $this->errors = \bff\DI::get('errors');
        }
        if ($this->security === null) {
            $this->security = \bff\DI::get('security');
        }
        if ($this->db === null) {
            $this->db = \bff\DI::get('database');
        }
        if ($this->input === null) {
            $this->input = \bff\DI::get('input');
        }
        if ($this->view === null) {
            $this->view = \bff\DI::get('view');
        }
        if ($this->locale === null) {
            $this->locale = \bff\DI::get('locale');
        }
        if ($this->router === null) {
            $this->router = \bff\DI::get('router');
        }
        if (\bff::adminPanel() && \config::sys('admin.smarty.enabled', false)) {
            $this->sm = \CSmarty::i();
        }

        $this->setIsInitialized();

        return true;
    }

    /**
     * Инициализирован ли компонент
     * @return bool
     */
    public function getIsInitialized()
    {
        return $this->_initialized;
    }

    /**
     * Помечаем инициализацию компонента
     * @return void
     */
    public function setIsInitialized()
    {
        $this->_initialized = true;
    }

    /**
     * Получение настроек компонента
     * @param mixed $keys
     * @param mixed $default
     * @return array
     */
    public function getSettings($keys, $default = null)
    {
        if (is_array($keys)) {
            $res = array();
            foreach ($keys as $key) {
                if (isset($this->sett[$key])) {
                    $res[$key] = $this->sett[$key];
                }
            }

            return $res;
        } elseif (is_string($keys)) {
            if (isset($this->sett[$keys])) {
                return $this->sett[$keys];
            }
            if (property_exists($this, $keys)) {
                return $this->$keys;
            }
            return $default;
        }

        return $this->sett;
    }

    /**
     * Установка настроек компонента
     * @param mixed|array $keys
     * @param mixed $value
     */
    public function setSettings($keys, $value = false)
    {
        if (is_array($keys)) {
            foreach ($keys as $k => $v) {
                if (property_exists($this, $k)) {
                    $this->$k = $v;
                }
                $this->sett[$k] = $v;
            }
        } else {
            if (property_exists($this, $keys)) {
                $this->$keys = $value;
            }
            $this->sett[$keys] = $value;
        }
    }

    /**
     * Проверка соответствия метода текущего запроса GET запросу
     * @return bool
     */
    public function isGET()
    {
        return \Request::isGET();
    }

    /**
     * Проверка соответствия метода текущего запроса POST запросу
     * @return bool
     */
    public function isPOST()
    {
        return \Request::isPOST();
    }

    /**
     * Проверка соответствия метода текущего запроса AJAX запросу
     * @param string|null $requestMethod тип запроса: 'POST', 'GET', NULL - не выполнять проверку типа
     * @return bool
     */
    public function isAJAX($requestMethod = 'POST')
    {
        return \Request::isAJAX($requestMethod);
    }

    /**
     * Формируем ответ на ajax-запрос
     * @param mixed $data response data
     * @param mixed $format response type; 0|false - raw echo, 1|true - json echo, 2 - json echo + errors
     * @param boolean $nativeJsonEncode использовать json_encode
     * @param boolean $escapeHTML енкодить html теги, при возвращении результата в iframe
     * @desc ajax ответ: если data=0,1,2 - это не ключ ошибки, а просто краткий ответ
     */
    protected function ajaxResponse($data, $format = 2, $nativeJsonEncode = false, $escapeHTML = false)
    {
        if ($format === 2) {
            $aResponse = array(
                'data'   => $data,
                'errors' => array()
            );
            if ($this->errors->no()) {
                if (is_int($data) && $data > 2) {
                    $this->errors->set($data);
                    $aResponse['errors'] = $this->errors->get(true);
                    $aResponse['data'] = '';
                }
            } else {
                $aResponse['errors'] = $this->errors->get(true);
            }
            $result = ($nativeJsonEncode ? json_encode($aResponse) : \func::php2js($aResponse));
        } elseif ($format === true || $format === 1) {
            $result = ($nativeJsonEncode ? json_encode($data) : \func::php2js($data));
        } else {
            $result = $data;
            $rawData = true;
        }

        if ($escapeHTML) {
            echo htmlspecialchars($result, ENT_NOQUOTES);
        } else {
            if (!isset($rawData)) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo $result;
        }

        \bff::shutdown();
    }

    /**
     * Формируем ответ на ajax-запрос (для формы)
     * @param array $data response data
     * @param mixed $format response type; 0|false - raw echo, 1|true - json echo, 2 - json echo + errors
     * @param boolean $nativeJsonEncode использовать json_encode
     * @param boolean $escapeHTML енкодить html теги, при возвращении результата в iframe
     * @see $this->ajaxResponse
     */
    protected function ajaxResponseForm(array $data = array(), $format = 2, $nativeJsonEncode = false, $escapeHTML = false)
    {
        $data['success'] = $this->errors->no();
        $data['fields'] = $this->errors->fields();
        foreach (['list','html'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = \bff::tagsProcess($data[$key]);
            }
        }
        $this->ajaxResponse($data, $format, $nativeJsonEncode, $escapeHTML);
    }

    /**
     * Формируем ответ на ajax-запрос (для формы отправленной через bff.iframeSubmit)
     * @param array $data response data
     * @see $this->ajaxResponseForm
     */
    protected function iframeResponseForm(array $data = array())
    {
        $this->ajaxResponseForm($data, 2, false, true);
    }
}