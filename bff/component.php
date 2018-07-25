<?php namespace bff;

/**
 * Базовый абстрактный класс компонента
 * @abstract
 * @version 0.342
 * @modified 11.sep.2017
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
     * @core-doc
     * @return mixed
     */
    public function init()
    {
        if ($this->getIsInitialized()) {
            return false;
        }

        $this->errors = \bff::DI('errors');
        $this->security = \bff::DI('security');
        $this->db = \bff::DI('database');
        $this->locale = \bff::DI('locale');
        $this->input = \bff::DI('input');
        if (\bff::adminPanel() && \config::sys('admin.smarty.enabled', false)) {
            $this->sm = \CSmarty::i();
        }

        $this->setIsInitialized();

        return true;
    }

    /**
     * Инициализирован ли компонент
     * @core-doc
     * @return bool
     */
    public function getIsInitialized()
    {
        return $this->_initialized;
    }

    /**
     * Помечаем инициализацию компонента
     * @core-doc
     * @return void
     */
    public function setIsInitialized()
    {
        $this->_initialized = true;
    }

    /**
     * Получение настроек компонента
     * @core-doc
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
     * @core-doc
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
     * Формируем ответ на ajax-запрос
     * @core-doc
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
     * @core-doc
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
        $this->ajaxResponse($data, $format, $nativeJsonEncode, $escapeHTML);
    }

    /**
     * Формируем ответ на ajax-запрос (для формы отправленной через bff.iframeSubmit)
     * @core-doc
     * @param array $data response data
     * @see $this->ajaxResponseForm
     */
    protected function iframeResponseForm(array $data = array())
    {
        $this->ajaxResponseForm($data, 2, false, true);
    }
}