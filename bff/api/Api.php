<?php namespace bff\api;

use Component, Logger, bff;
use bff\api\exception\AccessDeniedException;
use bff\api\exception\AuthException;
use bff\api\exception\DataException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Базовый контроллер обработки API запросов
 * @version 0.35
 * @modified 20.jul.2018
 */
class Api extends Component
{
    /**
     * Объект запроса
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * ID пользователя
     * @var integer
     */
    protected $userID;

    /**
     * Менеджер обработки API запросов
     * @var App
     */
    protected $app;

    /**
     * Константы ошибок
     */
    const ERROR_NO_ACTION           = 1; # Действие указано некорректно
    const ERROR_EXTERNAL            = 2; # Другая ошибка
    const ERROR_DATA_JSON_DECODE    = 3; # Ошибка работы с входящими JSON данными
    const ERROR_AUTH                = 4; # Ошибка авторизаукии
    const ERROR_TOKEN               = 5; # Некорректный токен
    const ERROR_TOKEN_EXPIRE        = 6; # Просроченный токен
    const ERROR_ACCESS_DENIED       = 7; # В доступе отказано
    const ERROR_DATA_INCORRECT      = 8; # Некорретные запрашиваемые данные

    /**
     * Конструктор
     * @param ServerRequestInterface $request
     * @param integer $userID ID пользователя
     */
    public function __construct(ServerRequestInterface $request = null)
    {
        $this->init();

        if (is_null($request)) {
            $request = bff::request();
        }
        $this->setRequest($request);
    }

    /**
     * Устанавливаем объект запроса
     * @param ServerRequestInterface $request объект запроса
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Устанавливаем менеджер обработки API запросов
     * @param App $app
     */
    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * Проверка метода текущего запроса на соответствие указанному
     * @param string $method
     * @return bool
     */
    protected function isRequestMethod($method)
    {
        return ($this->request->getMethod() === $method);
    }

    /**
     * Проверка соответствия текущего запроса GET запросу
     * @return bool
     */
    public function isGET()
    {
        return $this->isRequestMethod('GET');
    }

    /**
     * Проверка соответствия текущего запроса POST запросу
     * @return bool
     */
    public function isPOST()
    {
        return $this->isRequestMethod('POST');
    }

    /**
     * Проверка соответствия текущего запроса PUT запросу
     * @return bool
     */
    public function isPUT()
    {
        return $this->isRequestMethod('PUT');
    }

    /**
     * Проверка соответствия текущего запроса DELETE запросу
     * @return bool
     */
    public function isDELETE()
    {
        return $this->isRequestMethod('DELETE');
    }

    /**
     * Проверка соответствия текущего запроса PATCH запросу
     * @return bool
     */
    public function isPATCH()
    {
        return $this->isRequestMethod('PATCH');
    }

    /**
     * Проверка соответствия текущего запроса OPTIONS запросу
     * @return bool
     */
    public function isOPTIONS()
    {
        return $this->isRequestMethod('OPTIONS');
    }

    /**
     * Устанавливаем текущего пользователя
     * @param int $id ID пользователя
     */
    public function setUser($id)
    {
        $this->userID = $id;
    }

    /**
     * Проверяем авторизован ли пользователь
     * @param integer|boolean $id ID пользователя для проверки на соответствие текущему авторизованному
     * @param string $message текст сообщения
     * @throws AuthException|AccessDeniedException
     */
    public function userOnly($id = false, $message = '')
    {
        if (is_numeric($id) && $id > 0) {
            # Текущий авторизованный пользователь совпадает с требуемым
            if ($this->userID == $id) {
                return true;
            }
            $this->accessDenied($message);
        }
        # Пользователь неавторизован
        if ( ! $this->userID) {
            $this->authRequired($message);
        }
    }

    /**
     * Постраничность
     * @param array $data @ref
     * @param int $limit @ref
     * @param array $result @ref
     * @return bool
     */
    protected function pagination(& $data, & $limit, & $result)
    {
        # items per page
        $perPage = $this->param('perPage', TYPE_UINT);
        if ($perPage >= 100) {
            $perPage = 100;
        } else if ($perPage <=0) {
            $perPage = 20;
        }

        # current page
        $page = $this->param('page', TYPE_UINT, 0);
        if ($page <= 1) $page = 0;

        # sql limit
        $limit = ' LIMIT '.($page > 0 ? $page * $perPage.',' : '').$perPage;

        # result
        $result['page'] = $page + 1;
        $result['perPage'] = $perPage;

        # first page
        return $page < 1;
    }

    /**
     * Постраничность: кол-во записей на страницу
     * @param array $data @ref
     * @param int $default по умолчанию
     * @param int $limit максимально возможное кол-во
     * @return int
     */
    protected function perPage($default = 20, $limit = 100)
    {
        $value = $this->param('perPage', TYPE_UINT);
        if ($value > 0) {
            if ($value >= $limit) {
                return $limit;
            }
            return $value;
        }
        return $default;
    }

    /**
     * Получаем значение фильтра
     * @param string $name название фильтра
     * @param integer $type тип данных
     * @param mixed $default значение по умолчанию
     * @param array $validation дополнительные параметры валидации
     * @return mixed
     */
    protected function param($name, $type = TYPE_NOCLEAN, $default = null, array $validation = [])
    {
        return $this->app->param($name, $type, $default, $validation);
    }

    /**
     * Валидация списка требуемых данных
     * @param array $filter настройки фильтра требуемых данных
     * @param array $allowed список доступных данных
     * @return array
     */
    protected function filter(array $filter, array $allowed)
    {
        $result = array();

        do {
            if (empty($filter)) break;
            if (empty($allowed)) break;

            foreach ($allowed as $k => $v) {
                if ( ! isset($v['type'])) continue;
                if ( ! isset($filter[ $k ])) continue;
                $result[ $k ] = $this->input->clean($filter[ $k ], $v['type']);
                unset($filter[ $k ]);
            }
            if ( ! empty($filter)) {
                $filter = array_keys($filter);
                $this->dataIncorrect(_t('api','Unknown filters: [filters]',array('filters'=>join(', ', $filter))));
            }

        } while(false);

        return $result;
    }

    /**
     * Устанавливаем ошибку
     * @param integer $errorCode код ошибки
     * @param string $text текст ошибки
     */
    protected function error($errorCode, $text = '')
    {
        $text = trim($text);
        if (empty($text)) {
            $text = $this->errorMessage($errorCode);
        }
        $this->errors->set($text, false, $errorCode);
    }

    /**
     * Конвертируем код ошибки в текст
     * @param integer $errorCode код ошибки
     * @return string
     */
    protected function errorMessage($errorCode)
    {
        switch ($errorCode) {
            case static::ERROR_NO_ACTION:        return _t('api','no action');
            case static::ERROR_DATA_JSON_DECODE: return _t('api','json decode error');
            case static::ERROR_TOKEN:            return _t('api','token is incorrect');
            case static::ERROR_TOKEN_EXPIRE:     return _t('api','token expired');
            case static::ERROR_ACCESS_DENIED:    return _t('api','access denied');
            case static::ERROR_AUTH:             return _t('api','auth required');
        }
        return _t('api','unknown error');
    }

    /**
     * Логирование сообщений
     * @param mixed $message текст сообщения
     * @param int|array $level уровень логирования или данные контекста
     * @param array $context данные контекста
     * @return bool
     */
    protected function log($message, $level = Logger::ERROR, array $context = [])
    {
        return bff::log($message, $level, 'api.log', $context);
    }

    /**
     * Исключение: ошибка аутентификации
     * @param string $message текст сообщения
     * @throws AuthException
     */
    public function authRequired($message = '')
    {
        throw new AuthException($message);
    }

    /**
     * Исключение: ошибка данных
     * @param string $message текст сообщения
     * @throws DataException
     */
    public function dataIncorrect($message = '')
    {
        throw new DataException($message);
    }

    /**
     * Исключение: в доступе отказано
     * @param string $message текст сообщения
     * @throws AccessDeniedException
     */
    public function accessDenied($message = '')
    {
        throw new AccessDeniedException($message);
    }
}