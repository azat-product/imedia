<?php namespace bff;

/**
 * Класс обработки ошибок
 * @version 0.53
 * @modified 9.feb.2018
 *
 * Ключи необходимых шаблонов в TPL_PATH:
 *  - error.layout      - Ошибки: Layout шаблон
 *  - error.common      - Ошибки: cтандартный шаблон ошибок
 *  - error.404         - Ошибки: 404 ошибка
 *  - error.exception   - Ошибки: исключения
 *  - message.success   - Сообщения: "Успешно"
 *  - message.forbidden - Сообщения: "В доступе отказано"
 */

use Psr\Http\Message\ResponseInterface;

class Errors extends Singleton
{
    /** @var array список обработанных ошибок */
    protected $errors = array();
    /** @var array список полей(ключей), с которыми связаны ошибки */
    protected $fields = array();
    /** @var bool сворачивать блок ошибки автоматически по таймауту */
    protected $isAutohide = true;
    /** @var array необходимые шаблоны в TPL_PATH */
    protected $templates = array(
        # Ошибки:
        'error.layout'      => 'error', # Layout шаблон
        'error.common'      => 'error.common', # Стандартный шаблон ошибок
        'error.404'         => 'error.404', # 404 ошибка
        'error.exception'   => 'error.exception', # Исключения
        # Сообщения:
        'message.success'   => 'message.success', # сообщение "Успешно"
        'message.forbidden' => 'message.forbidden', # сообщение "В доступе отказано"
    );
    /** @var bool подавлять warning'и */
    protected $suppressWarnings = false;

    # коды ошибок
    const SUCCESS       = 1;
    const IMPOSSIBLE    = 4;
    const UNKNOWNRECORD = 402;
    const ACCESSDENIED  = 403;
    const RELOAD_PAGE   = 114;
    const DEMO_LIMITED  = 117;

    # код ошибки загрузки файла
    const FILE_UPLOAD_ERROR   = 1; # Ошибка загрузки файла
    const FILE_WRONG_SIZE     = 2; # Некорректный размер файла
    const FILE_MAX_SIZE       = 3; # Файл превышает масимально допустимый размер
    const FILE_DISK_QUOTA     = 4; # Ошибка загрузки файла (превышена квота на диске)
    const FILE_WRONG_TYPE     = 5; # Запрещенный тип файла
    const FILE_WRONG_NAME     = 6; # Некорректное имя файла
    const FILE_ALREADY_EXISTS = 7; # Файл с таким названием уже был загружен ранее
    const FILE_MAX_DIMENTION  = 8; # Изображение слишком большое по ширине/высоте

    /**
     * @return Errors
     */
    public static function i()
    {
        return parent::i();
    }

    /**
     * @return \Whoops\Run
     */
    public static function whoops()
    {
        static $i;
        if (!isset($i)) {
            if (class_exists('\Whoops\Run')) {
                $i = new \Whoops\Run();
                if (\Request::isAJAX()) {
                    $i->pushHandler(new \Whoops\Handler\JsonResponseHandler())->register();
                } else {
                    $i->pushHandler(new \Whoops\Handler\PrettyPageHandler())->register();
                }
            }
        }
        return $i;
    }

    protected function __construct()
    {
        set_error_handler(array($this, 'triggerErrorHandler'), E_ALL);
        //register_shutdown_function( array($this, 'triggerShutdownHandler') );
        \bff::hook('errors.init', $this);
    }

    /**
     * Переопределение шаблонов
     * @param array $templates список шаблонов: array(ключ шаблона => название шаблона в директории TPL_PATH, ...)
     */
    public function setTemplates(array $templates = array())
    {
        foreach ($templates as $k => $v) {
            if (!empty($v) && isset($this->templates[$k])) {
                $this->templates[$k] = $v;
            }
        }
    }

    /**
     * Сохраняем сообщение об ошибке
     * @param string|integer $error текст ошибки или ключ(например Errors::SUCCESS)
     * @param mixed $system
     *      boolean::true - системная ошибка
     *      string::'key' - не системная, ключ ошибки
     *      array::['key'=>'value'] - массив ключ-значение для подмены в тексте ошибки
     * @param mixed $key ключ ошибки или имя input-поля
     * @return Errors объект
     */
    public function set($error, $system = false, $key = null)
    {
        # подготавливаем текст ошибки
        if (is_int($error)) {
            $message = $this->getSystemMessage($error);
        } else {
            $message = $error;
        }
        # подставляем значения в текст
        if (is_array($system) && !empty($system) && is_string($message)) {
            $message = strtr($message, $system);
        }

        $errorData = array('sys' => ($system === true), 'errno' => $error, 'msg' => $message);
        if (!isset($key) && is_string($system)) {
            $key = $system;
        }
        if (isset($key)) {
            $this->errors[$key] = $errorData;
            $this->field($key);
        } else {
            $this->errors[] = $errorData;
        }

        if ($system === true) {
            \bff::log($message);
        }

        return $this;
    }

    /**
     * Получаем сообщения об ошибках
     * @param bool $onlyMessages только текст
     * @param bool $excludeSystem исключая системные
     */
    public function get($onlyMessages = true, $excludeSystem = true)
    {
        if (BFF_DEBUG || FORDEV) {
            $excludeSystem = false;
        }

        if ($onlyMessages) {
            if (empty($this->errors)) {
                return array();
            }
            $res = array();
            foreach ($this->errors as $k => $v) {
                if ($excludeSystem && $v['sys']) {
                    continue;
                }
                $res[$k] = $v['msg'];
            }

            return $res;
        } else {
            if ($excludeSystem) {
                $res = array();
                foreach ($this->errors as $k => $v) {
                    if ($v['sys']) {
                        continue;
                    }
                    $res[$k] = $v;
                }

                return $res;
            } else {
                return $this->errors;
            }
        }
    }

    /**
     * Получаем текст последней ошибки
     * @return string|boolean
     */
    public function getLast()
    {
        if (empty($this->errors)) {
            return false;
        }
        $return = end($this->errors);
        reset($this->errors);

        return (isset($return['msg']) ? $return['msg'] : false);
    }

    /**
     * Помечаем ключ поля(нескольких полей), с которым связаны добавленные ошибки
     * @param string|array $key ключ поля(нескольких полей)
     */
    public function field($key)
    {
        if (is_string($key)) {
            $this->fields[] = $key;
        } else {
            if (is_array($key)) {
                foreach ($key as $k) {
                    $this->fields[] = $k;
                }
            }
        }
    }

    /**
     * Помечаем список ключей полей, с которым связаны добавленные ошибки
     * @return array
     */
    public function fields()
    {
        return array_unique($this->fields);
    }

    /**
     * Помечаем невозможность выполнения операции
     * @return Errors
     */
    public function impossible()
    {
        return $this->set(static::IMPOSSIBLE);
    }

    /**
     * Помечаем ошибку доступа
     * @return Errors
     */
    public function accessDenied()
    {
        return $this->set(static::ACCESSDENIED);
    }

    /**
     * Помечаем ошибку (ID редактируемой записи некорректный)
     * @return Errors
     */
    public function unknownRecord()
    {
        return $this->set(static::UNKNOWNRECORD);
    }

    /**
     * Помечаем ошибку (требуется перезагрузка страницы)
     * @return Errors
     */
    public function reloadPage()
    {
        return $this->set(static::RELOAD_PAGE);
    }

    /**
     * Помечаем ошибку (действуют демо ограничения)
     * @return Errors
     */
    public function demoLimited()
    {
        return $this->set(static::DEMO_LIMITED);
    }

    /**
     * Помечаем успешность выполнения действия
     * @return Errors
     */
    public function success()
    {
        $this->set(static::SUCCESS);
        $_GET['errno'] = $_POST['errno'] = static::SUCCESS;

        return $this;
    }

    /**
     * Получаем успешность выполнения действия
     * @return bool
     */
    public function isSuccess()
    {
        $errorNumber = \bff::input()->getpost('errno', TYPE_UINT);
        $success = ($errorNumber == static::SUCCESS || (!$errorNumber && $this->no()));
        if ($errorNumber > 0 && $this->no()) {
            $this->set($errorNumber);
        }

        return $success;
    }

    /**
     * Получаем информацию о наличии ошибок
     * @param string $hook ключ хука
     * @param array|mixed $hookData данные для хук-вызова
     * @return bool true - нет; false - есть
     */
    public function no($hook = '', $hookData = array())
    {
        if (!empty($hook) && is_string($hook)) {
            \bff::hook($hook, $hookData);
        }
        return (sizeof($this->errors) == 0);
    }

    /**
     * Обнуляем информацию о существующих ошибках
     */
    public function clear()
    {
        $this->errors = array();
    }

    /**
     * Помечаем необходимость автоматического сворачивания блока ошибок
     * @param bool|null $hide true/false - помечаем требуемое состояние, NULL - получаем текущее
     * @return bool
     */
    public function autohide($hide = null)
    {
        if (is_null($hide)) {
            return $this->isAutohide;
        } else {
            return ($this->isAutohide = $hide);
        }
    }

    /**
     * Отображаем 404 HTTP ошибку
     * @param ResponseInterface|null $response объект ответа
     */
    public function error404($response = null)
    {
        \SEO::i()->robotsIndex(false);
        $this->errorHttp(404, $response);
    }

    /**
     * Отображаем HTTP ошибку
     * @param int $errorCode код http ошибки, например: 404
     * @param ResponseInterface|null $response объект ответа или NULL
     * @param mixed $template название PHP шаблона или FALSE ('error.common')
     */
    public function errorHttp($errorCode, $response = null, $template = false)
    {
        if (is_null($response)) {
            $response = new \Response();
        }
        $errorCode = intval($errorCode);
        $data = array('errno' => $errorCode, 'title' => _t('errors', 'Внутренняя ошибка сервера'), 'message' => '');
        switch ($errorCode) {
            case 401:
            {
                $response = $response->withStatus(401);
                $response = $response->withHeader('WWW-Authenticate', 'Basic realm="'.\Request::host(SITEHOST).'"');
                $data['title'] = _t('errors', 'Доступ запрещен');
                $data['message'] = _t('errors', 'Вы должны ввести корректный логин и пароль для получения доступа к ресурсу.');
            }
            break;
            case 403:
            {
                # пользователь не прошел аутентификацию, запрет на доступ (Forbidden).
                $response = $response->withStatus(403);
                $data['title'] = _t('errors', 'Доступ запрещен');
                $data['message'] = _t('errors', 'Доступ к указанной странице запрещен');
            }
            break;
            case 404:
            {
                $response = $response->withStatus(404);
                if (empty($template)) {
                    $template = $this->templates['error.404'];
                }
                $data['title'] = _t('errors', 'Страница не найдена!');
                $data['message'] = _t('errors', 'Страницы, на которую вы попытались войти не существует.');
            }
            break;
            default:
            {
                if (!empty($errorCode)) {
                    $response = $response->withStatus($errorCode);
                    $data['title'] = _t('errors', 'Внутренняя ошибка сервера');
                    $data['message'] = _t('errors', 'Произошла внутренняя ошибка сервера ([code])', array('code'=>$errorCode));
                } else {
                    $data['title'] = _t('errors', 'Внутренняя ошибка сервера');
                    $data['message'] = _t('errors', 'Произошла внутренняя ошибка сервера');
                }
            }
            break;
        }

        \bff::hook('errors.http.error', $errorCode, array(
            'data' => &$data, 'response' => &$response, 'template' => &$template,
        ));
        if ($response->getBody()->isWritable()) {
            $response->getBody()->write($this->viewError($data, $template));
        }
        \bff::respond($response, true);
    }

    /**
     * Выводим ошибку
     * @param array $data данные об ошибке: errno, title, message
     * @param mixed $templateName название шаблона или FALSE ('error.common')
     * @return string HTML
     */
    public function viewError(array $data = array(), $templateName = false)
    {
        if (\bff::adminPanel()) {
            return $data['title'].'<br />'.$data['message'];
        }
        $data['centerblock'] = \View::template((!empty($templateName) ? $templateName : $this->templates['error.common']), $data);

        return \View::renderLayout($data, $this->templates['error.layout']);
    }

    /**
     * Отображаем уведомление "Успешно..." (frontend)
     * @param string $title заголовок сообщения
     * @param string $message текст сообщения
     * @return string HTML
     */
    public function messageSuccess($title, $message)
    {
        return $this->viewMessage($title, $message, false, $this->templates['message.success']);
    }

    /**
     * Отображаем уведомление об "Ошибке..." (frontend)
     * @param string $title заголовок сообщения
     * @param string $message текст сообщения
     * @param bool $auth требуется авторизация
     * @return string HTML
     */
    public function messageForbidden($title, $message, $auth = false)
    {
        return $this->viewMessage($title, $message, $auth, $this->templates['message.forbidden']);
    }

    /**
     * Отображаем сообщение (frontend)
     * @param string $title заголовок сообщения
     * @param string $message текст сообщения
     * @param bool $auth требуется авторизация
     * @param mixed $templateName название шаблона или FALSE ('message.forbidden')
     * @return string HTML
     */
    public function viewMessage($title, $message = '', $auth = false, $templateName = false)
    {
        $data = array('title' => $title, 'message' => $message, 'auth' => $auth);

        return \View::template((!empty($templateName) ? $templateName : $this->templates['message.forbidden']), $data);
    }

    /**
     * Перехватываем trigger_error и пишем в лог ошибок (Logger::DEFAULT_FILE)
     * @param integer $errorCode код ошибки
     * @param string $message текст ошибки
     * @param string $errorFile файл, в котором произошла ошибка
     * @param string $errorLine строка файла, на которой произошла ошибка
     */
    public function triggerErrorHandler($errorCode, $message, $errorFile, $errorLine)
    {
        if ($errorCode == E_WARNING && $this->suppressWarnings) {
            return;
        }
        if (in_array($errorCode, array(
                E_USER_ERROR,
                E_USER_WARNING,
                E_USER_NOTICE,
                E_STRICT,
                E_ERROR,
                E_NOTICE,
                E_WARNING
            )
        )
        ) {
            $this->set($message . '<br />' . $errorFile . ' [' . $errorLine . ']', true);
        }
        \bff::log("$message > $errorFile [$errorLine]");
    }

    /**
     * Перехватываем shutdown_function
     */
    public function triggerShutdownHandler()
    {
        $lastError = error_get_last();
        if ($lastError['type'] === E_ERROR) {
            $this->triggerErrorHandler(E_ERROR, $lastError['message'], $lastError['file'], $lastError['line']);
        }
    }

    /**
     * Подавлять ошибки
     * @param boolean $suppress
     */
    public function suppressWarnings($suppress)
    {
        $this->suppressWarnings = $suppress;
    }

    /**
     * Сохраняем сообщение об ошибке загрузки
     * @param integer $uploadErrorCode код ошибки загрузки
     * @param array $params доп. параметры ошибки
     * @param mixed $system boolean::true - системная ошибка, string::'key' - не системная, ключ ошибки
     * @param mixed $key ключ ошибки или имя input-поля
     * @return Errors
     */
    public function setUploadError($uploadErrorCode, array $params = array(), $system = false, $key = null)
    {
        return $this->set($this->getUploadErrorMessage($uploadErrorCode, $params), $system, $key);
    }

    /**
     * Получаем текст ошибки загрузки файла по коду
     * @param integer $uploadErrorCode код ошибки загрузки
     * @param array $params доп. параметры ошибки
     * @return string текст ошибки
     */
    public function getUploadErrorMessage($uploadErrorCode, array $params = array())
    {
        switch ($uploadErrorCode) {
            case static::FILE_UPLOAD_ERROR:
                $message = _t('upload', 'Ошибка загрузки файла', $params);
                break;
            case static::FILE_WRONG_SIZE:
                $message = _t('upload', 'Некорректный размер файла', $params);
                break;
            case static::FILE_MAX_SIZE:
                $message = _t('upload', 'Файл превышает масимально допустимый размер', $params);
                break;
            case static::FILE_DISK_QUOTA:
                $message = _t('upload', 'Ошибка загрузки файла, обратитесь к администратору', $params);
                break;
            case static::FILE_WRONG_TYPE:
                $message = _t('upload', 'Запрещенный тип файла', $params);
                break;
            case static::FILE_WRONG_NAME:
                $message = _t('upload', 'Некорректное имя файла', $params);
                break;
            case static::FILE_ALREADY_EXISTS:
                $message = _t('upload', 'Файл с таким названием уже был загружен ранее', $params);
                break;
            case static::FILE_MAX_DIMENTION:
                $message = _t('upload', 'Изображение слишком большое по ширине/высоте', $params);
                break;
            default:
                $message = _t('upload', 'Ошибка загрузки файла');
                break;
        }
        return $message;
    }

    /**
     * Получаем текст ошибки по коду ошибки
     * @param integer $errorCode код ошибки
     * @return string текст ошибки
     */
    public function getSystemMessage($errorCode)
    {
        switch ($errorCode) {
            case static::SUCCESS: # Operation is successfull
                return _t('system', 'Операция выполнена успешно');
                break;
            case static::ACCESSDENIED: # Access denied
                return _t('system', 'В доступе отказано');
                break;
            case static::IMPOSSIBLE: # Unable to complete operation
                return _t('system', 'Невозможно выполнить операцию');
                break;
            case static::UNKNOWNRECORD: # Unable to complete operation
                return _t('system', 'Невозможно выполнить операцию');
                break;
            case static::RELOAD_PAGE: # Reload page and retry
                return _t('system', 'Обновите страницу и повторите попытку');
                break;
            case static::DEMO_LIMITED: # This operation is not allowed in demo mode
                return _t('system', 'Данная операция недоступна в режиме просмотра демо-версии');
                break;
            default: # Unknown error
                return _t('system', 'Неизвестная ошибка');
                break;
        }
    }
}