<?php

abstract class UsersModuleBase extends Module
{
    /** @var UsersModelBase */
    public $model = null;
    protected $securityKey = 'c98e21be338bb141536b03140152c8c4';

    /** @var bool доступна ли возможность управления несистемными группами */
    protected $manageNonSystemGroups = false;

    const GROUPID_SUPERADMIN = 7;
    const GROUPID_MODERATOR  = 17;
    const GROUPID_MEMBER     = 22;

    /** @var int Минимальная длина логина */
    public $loginMinLength = 3;
    public $loginMaxLength = 20;
    /** @var int Минимальная длина пароля */
    public $passwordMinLength = 4;

    /** @var int кол-во доступных номеров телефон (в профиле) */
    public $profilePhonesLimit = 2;
    /** @var bool доступно ли редактирование "Даты рождения" в профиле */
    public $profileBirthdate = false;

    # пол
    const SEX_FEMALE = 1; # женский
    const SEX_MALE = 2; # мужской

    public function init()
    {
        parent::init();
        $this->module_title = _t('users','Пользователи');
        bff::autoloadEx(array(
            'UsersForumBase' => array('core', 'modules/users/forum.php'),
        ));
    }

    /**
     * @return Users
     */
    public static function i()
    {
        return bff::module('users');
    }

    /**
     * @return UsersModel
     */
    public static function model()
    {
        return bff::model('users');
    }

    /**
     * Выполняем авторизацию пользователя
     * @param mixed $mLogin login|email|user_id пользователя
     * @param string|bool $sField поле для первого параметра - login, email, user_id
     * @param string $sPassword пароль (исходный или hash)
     * @param bool $bPasswordHashed пароль hash формате
     * @param bool $bSetErrors сохранять ошибки
     * @return mixed:
     *  0 - ошибка в логине/пароле,
     *  1 - неактивирован,
     *  true - успешная авторизация,
     *  array('reason'=>'причина блокировки')
     */
    public function userAuth($mLogin, $sField, $sPassword, $bPasswordHashed = true, $bSetErrors = false)
    {
        $aUserFilter = array('member' => 1);
        if (empty($sField) || !in_array($sField, array('user_id', 'email', 'login'))) {
            $sField = 'login';
        }
        $aUserFilter[$sField] = $mLogin;
        if ($bPasswordHashed) {
            $aUserFilter['password'] = $sPassword;
        }

        $aData = $this->model->userSessionData($aUserFilter);

        if (!$bPasswordHashed && !empty($aData)) {
            if ($aData['password'] != $this->security->getUserPasswordMD5($sPassword, $aData['password_salt'])) {
                $aData = false;
            }
        }

        if (!$aData) {
            # пользователя с таким логином и паролем не существует
            if ($bSetErrors) {
                if ($sField == 'email') {
                    $this->errors->set(_t('users', 'E-mail или пароль указаны некорректно'));
                } else {
                    $this->errors->set(_t('users', 'Логин или пароль указаны некорректно'));
                }
            }

            return 0;
        } else {
            if ($aData['blocked'] == 1) {
                # аккаунт заблокирован
                if ($bSetErrors) {
                    $this->errors->set(_t('users', 'Аккаунт заблокирован по причине: [reason]', array('reason' => '<br />'.nl2br($aData['blocked_reason']))));
                }

                return array('reason' => $aData['blocked_reason']);
            } else {
                if ($aData['activated'] == 0) {
                    # аккаунт неактивирован
                    config::set('__users_preactivate_data', $aData);

                    return 1;
                }
            }

            if ($this->security->isCurrentUser($aData['id'])) {
                # текущий пользователь уже авторизован
                # под аккаунтом под которым необходимо произвести авторизацию
                return true;
            }

            $nUserID = bff::$userID = (integer)$aData['id'];

            # стартуем сессию пользователя
            $this->security->sessionStart();

            # обновляем статистику
            $this->model->userSave($nUserID, false, array(
                    'last_login2'   => $aData['last_login'],
                    'last_login'    => $this->db->now(),
                    'last_login_ip' => Request::remoteAddress(true),
                    'last_activity' => $this->db->now(),
                    'session_id'    => session_id()
                )
            );

            if (!empty($this->model->userStatCounters)) {
                $this->security->userCounter(null); # сохраняем счетчики в сессии
            }

            # сохраняем данные пользователя в сессию
            $this->security->setUserInfo($nUserID, array(USERS_GROUPS_MEMBER), $aData);

            # синхронизация с форумом
            $this->forum()->onUserLogin($nUserID, $aData['login'], $aData['email']);

            # хук
            bff::hook('users.user.login', $nUserID, $aData);

            return true;
        }
    }

    /**
     * Проверяем корректность логина
     * @param string $sLogin логин
     * @param int|bool $mMaxLength максимально допустимая длина логина или false($this->loginMaxLength)
     * @return bool корректный ли логин
     */
    public function isLoginCorrect($sLogin, $mMaxLength = false)
    {
        if (empty($mMaxLength)) {
            $mMaxLength = $this->loginMaxLength;
        }

        if (empty($sLogin) OR # пустой
            (strlen($sLogin) < $this->loginMinLength) OR # короткий
            (strlen($sLogin) > $mMaxLength) OR # длинный
            preg_match('/[^\_a-z0-9]+/i', $sLogin) # содержит недопустимые символы
        ) {
            return false;
        }

        return true;
    }

    /**
     * Проверяем является ли поле $sValue логином или email адресом
     * @param string $sValue @ref поле
     * @param bool $isEmail @ref результат проверки true - email, false - логин
     * @param bool $bValidate валидировать поле $sValue
     * @return bool корректное ли поле $sValue
     */
    public function isLoginOrEmail(&$sValue, &$isEmail, $bValidate = true)
    {
        $isEmail = false;
        if (mb_strpos($sValue, '@') !== false) # указали "email"
        {
            if ($bValidate) {
                if (!$this->input->isEmail($sValue, false)) {
                    $this->errors->set(_t('users', 'E-mail адрес указан некорректно'), 'email');

                    return false;
                }
            }
            $isEmail = true;
        } else { # указали "login"
            if ($bValidate) {
                if (!$this->isLoginCorrect($sValue)) {
                    $this->errors->set(_t('users', 'Логин указан некорректно'), 'login');

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Формируем выпадающие списки для выбора дня рождения
     * @param mixed $birthDate дата рождения
     * @param int $yearMin минимальный год рождения
     * @param mixed $emptyOptions пустые значения по-умолчанию
     * @return array
     */
    public function getBirthdateOptions($birthDate = '', $yearMin = 1930, $emptyOptions = false)
    {
        if (is_array($birthDate)) {
            if (isset($birthDate['year'])) {
                list($year, $month, $day) = array($birthDate['year'], $birthDate['month'], $birthDate['day']);
            } else {
                list($year, $month, $day) = array(0,0,0);
            }
        } else {
            list($year, $month, $day) = ( ! empty($birthDate) && $birthDate !== '0000-00-00' && $birthDate !== '1901-01-01' ? explode(',', date('Y,n,j', strtotime($birthDate))) : array(0,0,0) );
        }
        $months = $this->locale->getMonthTitle(); unset($months[0]);
        $result = array(
            'days'   => range(1, 31),
            'months' => $months,
            'years'  => range(date('Y') - 5, $yearMin),
        );

        if ( ! empty($emptyOptions)) {
            if ( ! is_array($emptyOptions)) {
                $emptyOptions = array(_t('', 'день'), _t('', 'месяц'), _t('', 'год'));
            }
            $result['days']   = array(-1=>$emptyOptions[0]) + $result['days'];
            $result['months'] = array( 0=>$emptyOptions[1]) + $result['months'];
            $result['years']  = array(-1=>$emptyOptions[2]) + $result['years'];
        }

        array_walk($result['days'], function(&$value, $key, $selectedValue) {
            $value = '<option value="'.($key+1).'"'.($value == $selectedValue ? ' selected="selected"':'').'>'.$value.'</option>';
        }, $day);
        array_walk($result['months'], function(&$option, $value, $selectedValue) {
            $option = '<option value="'.$value.'"'.($value == $selectedValue ? ' selected="selected"':'').'>'.$option.'</option>';
        }, $month);
        array_walk($result['years'], function(&$value, $key, $selectedValue) {
            $value = '<option value="'.intval($value).'"'.($value == $selectedValue ? ' selected="selected"':'').'>'.$value.'</option>';
        }, $year);

        $result['days'] = join('', $result['days']);
        $result['months'] = join('', $result['months']);
        $result['years'] = join('', $result['years']);

        return $result;
    }

    /**
     * Проверяем статус "online" по времени последней активности
     * @param mixed $mDatetime время последней активности (TABLE_USERS_STAT::last_activity)
     * @return bool true - online, false - offline
     */
    public static function isOnline($mDatetime)
    {
        if (empty($mDatetime)) {
            return false;
        }
        if (is_string($mDatetime)) {
            $mDatetime = strtotime($mDatetime);
        }

        return ((BFF_NOW - $mDatetime) < config::sys('users.activity.timeout', 600));
    }

    /**
     * Проверяет забанен ли пользователь по id, ip, email. Если параметры не переданы, берем из текущей сессии.
     * @param string|array|bool $sUserIP строка с одним IP или массив IP-адресов, если true - текущий IP адрес
     * @param string|bool $sUserEmail E-mail пользователя или FALSE
     * @param int|bool $nUserID ID пользователя или FALSE
     * @param bool $bReturn возвращать результат или выводим сообщение и останавливаем выполнение скрипта.
     */
    public function checkBan($sUserIP = false, $sUserEmail = false, $nUserID = false, $bReturn = true)
    {
        if ($sUserIP === true) {
            $sUserIP = Request::remoteAddress();
        }

        $banned = false;
        $aQueryWhere = array();

        $sQuery = 'SELECT ip, uid, email, exclude, reason, finished FROM ' . TABLE_USERS_BANLIST . ' WHERE ';

        if ($sUserEmail === false) {
            $aQueryWhere[] = "email = ''";
        }

        if ($sUserIP === false) {
            $aQueryWhere[] = "(ip = '' OR exclude = 1)";
        }

        if ($nUserID === false) {
            $aQueryWhere[] = '(uid = 0 OR exclude = 1)';
        } else {
            $sql = '(uid = ' . $nUserID;

            if ($sUserEmail !== false) {
                $sql .= " OR email <> ''";
            }

            if ($sUserIP !== false) {
                $sql .= " OR ip <> ''";
            }

            $aQueryWhere[] = $sql . ')';
        }

        $sQuery .= (sizeof($aQueryWhere) ? implode(' AND ', $aQueryWhere) : '');
        if (defined('CID')) {
            $sQuery .= ' AND cid IN (0,' . CID . ')';
        }
        $aResult = $this->db->select($sQuery . ' ORDER BY exclude ASC');

        $banTriggeredBy = 'user';
        foreach ($aResult as $ban) {
            if ($ban['finished'] && $ban['finished'] < BFF_NOW) {
                continue;
            }

            $ip_banned = false;
            if (!empty($ban['ip'])) {
                if (!is_array($sUserIP)) {
                    $ip_banned = preg_match('#^' . str_replace('\*', '.*?', preg_quote($ban['ip'], '#')) . '$#i', $sUserIP);
                } else {
                    foreach ($sUserIP as $userIP) {
                        if (preg_match('#^' . str_replace('\*', '.*?', preg_quote($ban['ip'], '#')) . '$#i', $userIP)) {
                            $ip_banned = true;
                            break;
                        }
                    }
                }
                if ($ip_banned && !empty($ban['exclude'])) {
                    $ip_banned = false;
                    #echo 'unbanned by <strong>ip</strong><br />'; //debug
                }
            }

            if ((!empty($ban['uid']) && intval($ban['uid']) == $nUserID) ||
                $ip_banned ||
                (!empty($ban['email']) && preg_match('#^' . str_replace('\*', '.*?', preg_quote($ban['email'], '#')) . '$#i', $sUserEmail))
            ) {
                if (!empty($ban['exclude'])) {
                    $banned = false;
                    break;
                } else {
                    $banned = true;
                    $banData = $ban;

                    if (!empty($ban['uid']) && intval($ban['uid']) == $nUserID) {
                        $banTriggeredBy = 'user';
                    } else {
                        if ($ip_banned) {
                            $banTriggeredBy = 'ip';
                        } else {
                            $banTriggeredBy = 'email';
                        }
                    }
                    # Не делаем break, т.к. возможно есть exclude правило для этого юзера
                    # echo 'banned by <strong>'.$banTriggeredBy.'</strong><br />'; //debug
                }
            }
        }

        //echo '<u>banned result: '.($banned?1:0).'</u>'; exit; //debug

        if ($banned && !$bReturn) {
            $aMessageLang = array(
                'BAN_PERMISSION' => _t('users', 'Для получения дополнительной информации %2$sсвяжитесь с администратором%3$s.'),
                'BAN_TIME'       => _t('users', 'Доступ до <strong>%1$s</strong>.<br /><br />Для получения дополнительной информации %2$sсвяжитесь с администратором%3$s.'),
                'bannedby_email' => _t('users', 'Доступ закрыт для вашего адреса email.'),
                'bannedby_ip'    => _t('users', 'Доступ закрыт для вашего IP-адреса.'),
                'bannedby_user'  => _t('users', 'Доступ закрыт для вашей учётной записи.'),
            );

            $tillDate = ($banData['finished'] ? date('Y-m-d H:i', $banData['finished']) : '');

            $message = sprintf($aMessageLang[($banData['finished'] ? 'BAN_TIME' : 'BAN_PERMISSION')], $tillDate, '<a href="mailto:' . config::sysAdmin('mail.admin') . '">', '</a>');
            $message .= ($banData['reason'] ? '<br /><br /> Причина: <strong>' . $banData['reason'] . '</strong>' : '');
            $message .= '<br /><br /><em>' . $aMessageLang['bannedby_' . $banTriggeredBy] . '</em>';

            session_destroy();

            echo $this->showForbidden(_t('users', 'Доступ закрыт'), $message);
            exit;
        }

        return ($banned && $banData['reason'] ? $banData['reason'] : $banned);
    }

    /**
     * Иницилизация компонента работы с аватарами
     * @return UsersAvatar component
     */
    public function avatar($nUserID)
    {
        static $i;
        if (!isset($i)) {
            $i = new UsersAvatar();
        }
        $i->setRecordID($nUserID);

        return $i;
    }

    /**
     * Иницилизация компонента работы с форумами
     * @return UsersForumBase
     */
    public function forum()
    {
        static $i;
        if (!isset($i)) {
            $i = new UsersForumBase();
            $i->init();
        }

        return $i;
    }

    /**
     * Завершение сеанса пользователя по ID
     * @param integer $nUserID ID пользователя
     * @param boolean|null $mAdminPanel
     * @return boolean
     */
    public function userSessionDestroy($nUserID, $mAdminPanel = null)
    {
        if (empty($nUserID) || $nUserID < 0) {
            return false;
        }

        if ($mAdminPanel && bff::adminPanel() && $this->security->isCurrentUser($nUserID)) {
            $this->security->sessionDestroy();
            return true;
        }

        $aData = $this->model->userDataByFilter(array('user_id' => $nUserID), array('session_id'));
        if (empty($aData['session_id'])) {
            return false;
        }

        $this->security->impersonalizeSession($aData['session_id'], null, true, $mAdminPanel);
        $this->model->userSave($nUserID, false, array('session_id' => ''));

        return true;
    }

    /**
     * Обновление данных сеанса пользователя по ID
     * @param integer $nUserID ID пользователя
     * @param array $aSessionData данные для записи
     * @param boolean|null $mAdminPanel
     * @return boolean
     */
    public function userSessionUpdate($nUserID, array $aSessionData = array(), $mAdminPanel = null)
    {
        if (empty($nUserID) || $nUserID < 0) {
            return false;
        }

        if ($mAdminPanel && bff::adminPanel() && $this->security->isCurrentUser($nUserID)) {
            $this->security->updateUserInfo($aSessionData);

            return true;
        }

        $aUserData = $this->model->userDataByFilter(array('user_id' => $nUserID), array('session_id'));
        if (empty($aUserData['session_id'])) {
            return false;
        }

        $this->security->impersonalizeSession($aUserData['session_id'], $aSessionData, false, $mAdminPanel);

        return true;
    }

    /**
     * Коррекция настроек авторизации через соц. сети
     * @param array $opts
     * @return mixed
     */
    public static function socialConfig($opts = array())
    {
        $sysPrefix = 'users.social.config.';
        $config = \config::file('social');
        $config['base_url'] = static::url('login.social');
        $ds = DS;
        foreach ($config['providers'] as $k=>&$v) {
            if ( ! empty($opts['path']) && ! file_exists($opts['path'].'hybridauth'.$ds.'hybridauth'.$ds.'Hybrid'.$ds.'Providers'.$ds.$k.'.php')) {
                if ( ! isset($v['wrapper'])) {
                    $v['wrapper'] = array(
                        'class' => 'Hybrid_Providers_'.$k,
                        'path'  => $opts['path'].'hybridauth'.$ds.'additional-providers'.$ds.'hybridauth-'.mb_strtolower($k).$ds.'Providers'.$ds.$k.'.php',
                    );
                }
            }
            $v['enabled'] = \config::sysAdmin($sysPrefix.$k.'.enabled', !empty($v['enabled']), TYPE_BOOL);
            foreach ($v['keys'] as $kk=>$vv) {
                $v['keys'][$kk] = \config::sysAdmin($sysPrefix.$k.'.keys.'.$kk, $vv, ($kk === 'secret' ? TYPE_PASS : TYPE_STR));
            }
            if (isset($v['scope'])) {
                $v['scope'] = \config::sysAdmin($sysPrefix.$k.'.scope', $v['scope'], TYPE_STR);
            }
        } unset($v);
        return $config;
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('avatars', 'images') => 'dir', # аватары
        ));
    }
}