<?php namespace bff\api;

use bff, config, Request, Users;
use bff\exception\BaseException;
use bff\api\exception\AccessDeniedException;
use bff\api\exception\AuthException;
use bff\api\exception\DataException;

/**
 * Менеджер обработки API запросов
 * @version 0.53
 * @modified 20.jul.2018
 */
class App extends Api
{
    /**
     * Версия API
     * @var string
     */
    protected $version = '1';

    /**
     * Таблица хранения токенов сессий
     */
    protected $tableTokens = DB_PREFIX.'api_tokens';

    /**
     * Хост роутов (example.com)
     * @var string
     */
    protected $routesHost = '';

    /**
     * Протокол роутов (http, https)
     * @param string $scheme
     */
    protected $routesScheme = '';

    /**
     * Префикс роутов
     * @var string
     */
    protected $routesPrefix = '';

    protected $requestParams = [];

    public function init()
    {
        parent::init();

        $this->setApp($this);
        $this->setVersion(config::sysAdmin('api.version', '1', TYPE_STR));
        $this->setRoutesHost(config::sysAdmin('api.routes.host', '', TYPE_STR));
        $this->setRoutesScheme(config::sysAdmin('api.routes.scheme', Request::scheme(), TYPE_STR));
        $this->setRoutesPrefix(config::sysAdmin('api.routes.prefix', '/api/v{version}/', TYPE_STR));
    }

    /**
     * Устанавливаем версию API
     * @param string $version версия API
     * @return string
     */
    public function setVersion($version)
    {
        $this->version = strval($version);
    }

    /**
     * Префикс роутов
     * @param string $prefix
     */
    public function setRoutesPrefix($prefix)
    {
        $this->routesPrefix = str_replace('{version}', $this->version, strval($prefix));
    }

    /**
     * Хост роутов
     * @param string $host
     */
    public function setRoutesHost($host)
    {
        $this->routesHost = str_replace('{host}', SITEHOST, strval($host));
    }

    /**
     * Протокол роутов
     * @param string $scheme
     */
    public function setRoutesScheme($scheme)
    {
        $this->routesScheme = strval($scheme);
    }

    /**
     * Инициализируем роуты приложения
     */
    protected function routesInit()
    {
        $version = $this->version;
        $host    = $this->routesHost;
        $group   = $this->routesPrefix;
        $idPrefix  = 'api-v'.$version.'-';
        $this->router->group($group, function() use ($idPrefix, $host) {
            # App
            $routes = $this->router->parseClass(static::class);
            foreach ($routes as $name=>$v) {
                if ( ! empty($host)) { $v['host'] = $host; }
                $v['callback'] = ['class'=>$this, 'method'=>$name];
                $this->router->add('api/'.$name, $v);
            }
            # Modules
            foreach (bff::i()->getModulesList() as $moduleName=>$moduleParams) {
                $moduleApi = "modules\\$moduleName\\Api";
                if (class_exists($moduleApi)) {
                    $routes = $this->router->parseClass($moduleApi);
                    foreach ($routes as $name=>$v) {
                        if ( ! empty($host)) { $v['host'] = $host; }
                        $v['callback'] = ['class'=>$moduleApi, 'method'=>$name];
                        $this->router->add('api/modules/'.$moduleName.'/'.$name, $v);
                    }
                }
            }
            # Plugins / Themes
            # TODO
        });
    }

    /**
     * Требуется ли выполнять старт API приложения
     * @return boolean
     */
    public function isApiRequest()
    {
        if ($this->routesPrefix !== ''
         && mb_stripos($this->request->getRequestTarget(), $this->routesPrefix) !== 0) {
            return false;
        }
        if ($this->routesHost !== ''
         && $this->routesHost !== $this->request->getHeaderLine('host')) {
            return false;
        }
        if ($this->request->getHeaderLine('content-type') !== 'application/json') {
            //return false;
        }
        return true;
    }

    /**
     * Обрабатываем входящий запрос
     */
    public function run()
    {
        # Инициализируем роуты
        $this->routesInit();

        $response = array();
        do {

            # Поиск подходящего роута
            $this->router->setRequest($this->request);
            $route = $this->router->search();
            if ($route['route'] === false) {
                $this->error(static::ERROR_NO_ACTION);
                break;
            }

            # Проверка типа запроса
            if ($this->request->getHeaderLine('content-type') !== 'application/json') {
                $this->error(static::ERROR_DATA_INCORRECT, _t('api','Wrong request content type, should be application/json'));
                break;
            }
            # Получаем входящие данные
            $params = file_get_contents('php://input');
            if (!empty($params)) {
                $params = json_decode($params, true);
                if ($params === null && json_last_error() !== JSON_ERROR_NONE) {
                    $this->error(static::ERROR_DATA_JSON_DECODE, json_last_error_msg());
                    break;
                }
            } else {
                $params = [];
            }
            $this->requestParams = &$params;

            # Проверяем access-токен указанный в запросе
            $token = $this->token();
            if (!empty($token)) {
                # Инициируем сессию
                if (!$this->sessionInit($token)) {
                    break;
                }
            }

            # Выполняем запрос к контроллеру API
            try {

                $call = $route['route']['callback'];
                if (is_string($call['class'])) {
                    $call['class'] = new $call['class']($this->request);
                    $call['class']->setUser($this->userID);
                    $call['class']->setApp($this);
                }
                if (!method_exists($call['class'], $call['method'])) {
                    $this->error(static::ERROR_NO_ACTION);
                    break;
                }
                $response['data'] = call_user_func_array([
                    $call['class'],
                    $call['method'],
                ], $route['params']
                );

            } catch (AuthException $e) {
                $this->error(static::ERROR_AUTH, $e->getMessage());
                break;
            } catch (DataException $e) {
                $this->error(static::ERROR_DATA_INCORRECT, $e->getMessage());
                break;
            } catch (AccessDeniedException $e) {
                $this->error(static::ERROR_ACCESS_DENIED, $e->getMessage());
                break;
            } catch (BaseException $e) {
                $this->error(static::ERROR_EXTERNAL, $e->getMessage());
                break;
            }

        } while(false);

        if ($this->errors->no()) {
            $response['status'] = 'success';
        } else {
            $response['status'] = 'error';
            $errors = $this->errors->get(true, false);
            $response['error_code'] = array_keys($errors);
            $response['error_reason'] = array_values($errors);
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        bff::shutdown();
    }

    /**
     * Получаем значение входящего параметра
     * @param string $name название параметра
     * @param integer $type тип данных
     * @param mixed $default значение по умолчанию
     * @param array $validation доп. валидация
     * @return mixed
     */
    public function param($name, $type = TYPE_NOCLEAN, $default = null, array $validation = [])
    {
        if (array_key_exists($name, $_GET)) {
            $value = $this->input->get($name, $type, $validation);
            if (empty($value) && $default !== null) {
                return $default;
            }
            return $value;
        } else if (array_key_exists($name, $this->requestParams)) {
            $value = $this->input->clean($this->requestParams[$name], $type, true, $validation);
            if (empty($value) && $default !== null) {
                return $default;
            }
            return $value;
        } else {
            return $default;
        }
    }

    /**
     * Получаем значение из заголовка запроса
     * @param string $key ключ значения
     * @return string
     */
    public function header($key)
    {
        return $this->request->getHeaderLine('x-bff-api-'.$key);
    }

    /**
     * Формирование URL для требуемого метода API
     * @param string $classMethod название контроллера + метода: 'auth', 'modules/site/method',
     * @param array $params параметры подставляемые в строку роута
     * @param array $opts доп. параметры для формирования полного URL
     */
    public function url($classMethod = '', array $params = [], array $opts = [])
    {
        if ( ! empty($this->routesScheme) && ! array_key_exists('scheme', $opts)) {
            $opts['scheme'] = $this->routesScheme;
        }
        if ( ! empty($this->routesHost) && ! array_key_exists('host', $opts)) {
            $opts['host'] = $this->routesHost;
        }
        return $this->router->url('api/'.$classMethod, $params, $opts);
    }

    /**
     * Аутентификация пользователя
     * @route /auth
     * @route /auth2
     * @return array|bool
     */
    public function auth()
    {
        $email = $this->param('email', TYPE_NOTAGS);
        $password = $this->param('password', TYPE_PASS);

        if (empty($email)) {
            $this->error(static::ERROR_AUTH, _t('api', 'Email is empty'));
            return false;
        }
        if ( ! $this->input->isEmail($email)) {
            $this->error(static::ERROR_AUTH, _t('api', 'Email is invalid'));
            return false;
        }
        if (empty($password)) {
            $this->error(static::ERROR_AUTH, _t('api', 'Password is empty'));
            return false;
        }
        $user = Users::model()->userSessionData(['email' => $email]);

        if (empty($user['id']) || ! $this->security->compareString($user['password'], $this->security->getUserPasswordMD5($password, $user['password_salt']))) {
            $this->error(static::ERROR_AUTH, _t('api', 'Email or password is incorrect'));
            return false;
        }

        $tokens = $this->tokensGenerate();
        if ($tokens === false) {
            $this->error(static::ERROR_EXTERNAL);
            return false;
        }
        $userID = $user['id'];
        $save = $tokens + array(
            'last_activity' => $this->db->now(),
            'last_ip'       => Request::remoteAddress(true),
        );

        $exist = $this->db->select_row($this->tableTokens, ['*'], ['user_id'=>$userID]);
        if (empty($exist)) {
            $this->db->insert($this->tableTokens, $save + ['user_id' => $userID]);
        } else {
            $this->db->update($this->tableTokens, $save, ['user_id' => $userID]);
        }
        return $tokens;
    }

    /**
     * Обновляем токены
     * @route /refresh
     * @return array|bool
     */
    public function refresh()
    {
        $tokenRefresh = $this->token();
        if (empty($tokenRefresh)) {
             $this->error(static::ERROR_TOKEN);
             return false;
        }
        return $this->tokenRefresh($tokenRefresh);
    }

    /**
     * Получаем значение токена из заголовка запроса
     * @return string
     */
    protected function token()
    {
        $value = $this->header('token');
        return $this->input->clean($value, TYPE_NOTAGS);
    }

    /**
     * Время жизни access-токена
     * @return int в секундах
     */
    protected function tokenAccessTimeout()
    {
        return config::sys('api.token.access.timeout', 10800, TYPE_UINT); # 3 * 60 * 60
    }

    /**
     * Время жизни refresh-токена
     * @return int в секундах
     */
    protected function tokenRefreshTimeout()
    {
        return config::sys('api.token.refresh.timeout', 2592000, TYPE_UINT); # 30 * 24 * 60 * 60
    }

    /**
     * Генерируем токен (access и refresh)
     * @return array|bool
     */
    protected function tokensGenerate()
    {
        $result = array();
        $generate = function($compare, $try = 10) {
            for ($i = 0; $i < $try; $i++) {
                $token = md5(uniqid(mt_rand(), true).time());
                $exist = $this->db->select_rows_count($this->tableTokens, [$compare=>$token]);
                if ( ! $exist) {
                    return $token;
                }
            }
            return false;
        };
        $accessToken = $generate('access_token');
        if ($accessToken !== false) {
            $result['access_token'] = $accessToken;
            $result['access_expire'] = date('Y-m-d H:i:s', time() + $this->tokenAccessTimeout());
        } else {
            $this->log('tokensGenerate: unable to generate access token');
            return false;
        }
        $refreshToken = $generate('refresh_token');
        if ($refreshToken !== false) {
            $result['refresh_token'] = $refreshToken;
            $result['refresh_expire'] = date('Y-m-d H:i:s', time() + $this->tokenRefreshTimeout());
        } else {
            $this->log('tokensGenerate: unable to generate refresh token');
            return false;
        }
        return $result;
    }

    /**
     * Обновляем токены
     * @param string $token refresh-токен
     * @return array|bool
     */
    protected function tokenRefresh($token)
    {
        if (empty($token)) {
            $this->error(static::ERROR_TOKEN);
            return false;
        }
        # Получаем данные по refresh-токену
        $data = $this->db->select_row($this->tableTokens, ['user_id','refresh_expire'], ['refresh_token' => $token]);
        if (empty($data['user_id'])) {
            $this->error(static::ERROR_TOKEN);
            return false;
        }
        # Токен просрочен
        if (strtotime($data['refresh_expire']) < time()) {
            $this->error(static::ERROR_TOKEN_EXPIRE);
            return false;
        }

        # Генерируем новый токен
        $tokens = $this->tokensGenerate();
        if ($tokens === false) {
            $this->error(static::ERROR_EXTERNAL);
            return false;
        }

        # Сохраняем обновленные access+refresh токены
        $save = $tokens + [
            'last_activity' => $this->db->now(),
            'last_ip'       => Request::remoteAddress(true),
        ];
        $res = $this->db->update($this->tableTokens, $save, [
            'user_id' => $data['user_id'],
        ]);
        if (empty($res)) {
            $this->log('token refresh failed', $save);
            $this->error(static::ERROR_EXTERNAL);
            return false;
        }
        return $tokens;
    }

    /**
     * Инициируем сессию пользователя исходя из полученного access-токена в запросе
     * @param string $tokenAccess токен
     * @return boolean true или false (ошибка)
     */
    protected function sessionInit($tokenAccess)
    {
        if (empty($tokenAccess)) {
            return false;
        }
        $data = $this->db->select_row($this->tableTokens, ['user_id','access_expire'], ['access_token' => $tokenAccess]);
        if (empty($data['user_id'])) {
            $this->error(static::ERROR_TOKEN);
            return false;
        }

        # Токен просрочен
        if (strtotime($data['access_expire']) < time()) {
            $this->error(static::ERROR_TOKEN_EXPIRE);
            return false;
        }

        # Фиксируем ID текущего пользователя
        $userID = (int)$data['user_id'];
        $this->setUser($userID);

        # Обновляем дату последнего запроса
        $this->db->update($this->tableTokens, [
            'last_activity' => $this->db->now(),
            'last_ip'       => Request::remoteAddress(true),
        ], ['user_id' => $userID]);

        $this->security->setSessionApi(
            [$this, 'sessionGet'],
            [$this, 'sessionSet']
        );

        return true;
    }

    /**
     * Получаем данные из сессии
     * @param string $key ключ данных
     * @param mixed $default значение по умолчанию
     * @return mixed
     */
    public function sessionGet($key, $default = '')
    {
        if ( ! $this->userID) {
            $this->authRequired();
        }
        $session = $this->sessionLoad($this->userID);
        return isset($session[$key]) ? $session[$key] : $default;
    }

    /**
     * Сохранение данных в сессию
     * @param string $key ключ данных
     * @param mixed $value значение
     */
    public function sessionSet($key, $value)
    {
        if ( ! $this->userID) {
            $this->authRequired();
        }
        $session = $this->sessionLoad($this->userID);
        $session[$key] = $value;
        $this->sessionSave($this->userID, $session);
    }

    /**
     * Загрузка данных сессии
     * @param integer $userID ID пользователя
     * @return array
     */
    protected function sessionLoad($userID)
    {
        $data = $this->db->select_data($this->tableTokens, 'session', ['user_id'=>$userID]);
        if (empty($data)) {
            return array();
        }
        $data = json_decode(base64_decode($data), true);
        return $data;
    }

    /**
     * Сохранение данных сессии
     * @param integer $userID ID пользователя
     * @param array $data данные сессии
     */
    protected function sessionSave($userID, $data)
    {
        $this->db->update($this->tableTokens, [
            'session' => base64_encode(json_encode($data)),
        ], [
            'user_id' => $userID,
        ]);
    }
}