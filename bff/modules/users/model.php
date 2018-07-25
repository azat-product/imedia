<?php

/**
 * Используемые таблицы:
 * TABLE_USERS - таблица пользователей
 * TABLE_USERS_STAT - таблица динамических данных пользователей
 * TABLE_USERS_GROUPS - группы пользователей
 * TABLE_USER_IN_GROUPS - вхождение пользователей в группы
 * TABLE_USERS_GROUPS_PERMISSIONS - права групп пользователей
 * TABLE_MODULE_METHODS - модули-методы, доступные для настройки прав доступа группы
 */

class UsersModelBase extends Model
{
    /** @var array список шифруемых полей в таблице TABLE_USERS */
    public $cryptUsers = array();
    /** @var array список полей пользовательских счетчиков (TABLE_USERS_STAT) */
    public $userStatCounters = array();
    /**
     * @var array список полей данных о пользователе запрашиваемых для сохранения в сессии
     * обязательные: id, member, admin, login, email, password, password_salt, name, avatar, sex
     */
    protected $userSessionDataKeys = array(
        'user_id as id',
        'member',
        'login',
        'password',
        'password_salt',
        'email',
        'name',
        'phone',
        'avatar',
        'sex',
        'activated',
        'blocked',
        'blocked_reason',
        'admin',
        'last_login',
        'region_id'
    );

    /**
     * Формируем список пользователей
     * @param array $aFilter фильтр списка
     * @param array $aDataKeys список требуемых полей
     * @param bool $bCount только подсчет кол-ва
     * @param array $aBind подстановочные данные
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function usersList(array $aFilter, array $aDataKeys = array(), $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        if (!empty($this->cryptUsers)) {
            foreach ($aFilter as $k => $v) {
                if (in_array($k, $this->cryptUsers)) {
                    unset($aFilter[$k]);
                    $aFilter[':' . $k] = array('BFF_DECRYPT(' . $k . ') = :' . $k, ':' . $k => $v);
                }
            }
        }
        $aFilter = $this->prepareFilter($aFilter);

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(user_id) FROM ' . TABLE_USERS . '
                INNER JOIN ' . TABLE_USERS_STAT . ' USING (user_id)
                ' . $aFilter['where'], $aFilter['bind']
            );
        }

        if (empty($aDataKeys)) {
            $aDataKeys = array('*');
            $sAssocKey = 'user_id';
            if (!empty($this->cryptUsers)) {
                foreach ($this->cryptUsers as $k => $v) {
                    $aDataKeys[] = 'BFF_DECRYPT(' . $k . ') as ' . $k;
                }
            }
        } else {
            if (!empty($this->cryptUsers)) {
                foreach ($aDataKeys as $k => $v) {
                    if (in_array($v, $this->cryptUsers)) {
                        $aDataKeys[$k] = 'BFF_DECRYPT(' . $v . ') as ' . $v;
                    }
                }
            }
            $sAssocKey = (in_array('user_id', $aDataKeys) ? 'user_id' : false);
        }

        return $this->db->select_key('SELECT ' . join(',', $aDataKeys) . '
                           FROM ' . TABLE_USERS . '
                                INNER JOIN ' . TABLE_USERS_STAT . ' USING (user_id)
                           ' . $aFilter['where'] .
            ' GROUP BY user_id ' .
            (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
            . $sqlLimit, $sAssocKey, $aFilter['bind']
        );
    }

    /**
     * Получаем данные о пользователе по фильтру
     * @param array|int $mFilter фильтр
     * @param mixed $aDataKeys ключи необходимых данных
     * @param array $aBind
     * @return array|mixed
     */
    public function userDataByFilter($mFilter, $aDataKeys = '*', $aBind = array())
    {
        if (!is_array($mFilter)) {
            $mFilter = array('user_id' => intval($mFilter));
        }
        if (!empty($this->cryptUsers)) {
            foreach ($mFilter as $k => $v) {
                if (in_array($k, $this->cryptUsers)) {
                    unset($mFilter[$k]);
                    $mFilter[':' . $k] = array('BFF_DECRYPT(' . $k . ') = :' . $k, ':' . $k => $v);
                }
            }
        }
        $aFilter = $this->prepareFilter($mFilter);

        if (empty($aDataKeys)) {
            return array();
        } else {
            if ($aDataKeys == '*') {
                $aDataKeys = array('*');
                if (!empty($this->cryptUsers)) {
                    foreach ($this->cryptUsers as $k => $v) {
                        $aDataKeys[] = 'BFF_DECRYPT(' . $k . ') as ' . $k;
                    }
                }
            } else {
                if (!is_array($aDataKeys)) {
                    $aDataKeys = array($aDataKeys);
                }
                if (!empty($this->cryptUsers)) {
                    foreach ($aDataKeys as $k => $v) {
                        if (is_string($k)) {
                            if (in_array($k, $this->cryptUsers)) {
                                $aDataKeys[$k] = "BFF_DECRYPT($k) as $v";
                            }
                        } else {
                            if (in_array($v, $this->cryptUsers)) {
                                $aDataKeys[$k] = "BFF_DECRYPT($v) as $v";
                            }
                        }
                    }
                }
            }
        }

        if (!empty($aBind)) {
            $aFilter['bind'] = array_merge($aBind, $aFilter['bind']);
        }

        $aData = $this->db->one_array('SELECT ' . join(',', $aDataKeys) . '
                           FROM ' . TABLE_USERS . '
                                INNER JOIN ' . TABLE_USERS_STAT . ' USING (user_id)
                           ' . $aFilter['where'] . '
                           LIMIT 1', $aFilter['bind']
        );

        if (isset($aData['contacts']) && method_exists(bff::module('users'), 'contactsToArray')) {
            $aData['contacts'] = Users::contactsToArray($aData['contacts']);
        }
        return $aData;
    }

    /**
     * Получаем данные о пользователе для сохранения в сессии
     * @param array|int $mFilter фильтр
     * @param bool $bGetGroups получать информацию о группах, в которых состоит пользователь
     * @return mixed
     */
    public function userSessionData($mFilter, $bGetGroups = false)
    {
        $aData = $this->userDataByFilter($mFilter, $this->userSessionDataKeys);
        if (!empty($aData) && $bGetGroups) {
            $aDataGroups = $this->userGroups($aData['id'], true);
            if (empty($aDataGroups) && $aData['member'] == 1) {
                $aDataGroups = array(USERS_GROUPS_MEMBER);
            }
            $aData['groups'] = $aDataGroups;
        }

        return $aData;
    }

    /**
     * Получаем password-hash пользователя по логину|email|ID пользователя
     * @param mixed $mLogin login|email|user_id пользователя
     * @param string $sField поле для первого параметра - login, email, user_id
     * @return mixed
     */
    public function userPassword($mLogin, $sField = 'login')
    {
        $aData = $this->userDataByFilter(array($sField => $mLogin), array('password'));

        return (isset($aData['password']) ? $aData['password'] : '');
    }

    /**
     * Получаем текущий баланс пользователя
     * @param int|bool $nUserID ID пользователя или FALSE - id текущего пользователя
     * @param bool $bResetCache сбрасываем кеш
     * @return float
     */
    public function userBalance($nUserID = false, $bResetCache = false)
    {
        # кешируем для текущего пользователя
        if ($nUserID === false || $this->security->isCurrentUser($nUserID)) {
            static $cache;
            if (!isset($cache) || $bResetCache) {
                $nUserID = $this->security->getUserID();
                if (!$nUserID) {
                    return ($cache = 0);
                }
                $aData = $this->userDataByFilter($nUserID, array('balance'));
                $cache = (float)(isset($aData['balance']) ? $aData['balance'] : 0);
            }

            return $cache;
        } else {
            $aData = $this->userDataByFilter($nUserID, array('balance'));

            return (float)(isset($aData['balance']) ? $aData['balance'] : 0);
        }
    }

    /**
     * Сохраняем данные пользователя
     * @param int $nUserID ID пользователя
     * @param array|bool $aData данные
     * @param array $aDataStat динамические данные
     * @param array $aBind доп. параметры запросы для bind'a
     * @return bool
     */
    public function userSave($nUserID, $aData, $aDataStat = array(), array $aBind = array())
    {
        $res = true;
        $aCond = array('user_id' => $nUserID);
        if (!empty($aData)) {
            $res = $this->db->update(TABLE_USERS, $aData, $aCond, $aBind, $this->cryptUsers);
            $res = !empty($res);
        }
        if (!empty($aDataStat)) {
            $this->db->update(TABLE_USERS_STAT, $aDataStat, $aCond, $aBind, $this->cryptUsers);
        }
        \bff::hook('users.user.save', $nUserID, array('data'=>&$aData, 'dataStat'=>&$aDataStat));

        return $res;
    }

    /**
     * Создаем пользователя
     * @param array $aUserData данные пользователя
     * @param array|integer|null $aGroupsID ID групп, в которые входит пользователь
     * @return int ID пользователя
     */
    public function userCreate($aUserData, $aGroupsID = null)
    {
        if (empty($aUserData['login'])) {
            # сгенерируем уникальный логин
            $aUserData['login'] = $this->userLoginGenerate();
        }

        $aUserData['created'] = $this->db->now();
        $aUserData['created_ip'] = Request::remoteAddress(true);
        $nUserID = $this->db->insert(TABLE_USERS, $aUserData, 'user_id', array(), $this->cryptUsers);

        if ($nUserID > 0) {
            if (!empty($aGroupsID)) {
                $this->userToGroups($nUserID, $aGroupsID, false);
            }
            $this->db->insert(TABLE_USERS_STAT, array('user_id' => $nUserID), false, array(), $this->cryptUsers);
        } else {
            $nUserID = 0;
        }
        if ($nUserID > 0) {
            \bff::hook('users.user.create', $nUserID, array('data'=>&$aUserData,'groups'=>$aGroupsID));
        }

        return $nUserID;
    }

    /**
     * Проверяем наличие пользователя по логину
     * @param string $sLogin логин
     * @param int $nExceptUserID ID пользователя, которого следует исключить из проверки
     * @param array $aReservedLogins список зарезервированных логинов
     * @return bool
     */
    public function userLoginExists($sLogin, $nExceptUserID = 0, array $aReservedLogins = array()) // isLoginExists
    {
        if (!empty($aReservedLogins)) {
            if (in_array($sLogin, $aReservedLogins)) {
                return true;
            }
        }

        $aFilter = array('login' => $sLogin);
        $aBind = array();
        if (!empty($nExceptUserID)) {
            $aFilter[':userIdExcept'] = 'user_id != :userId ';
            $aBind[':userId'] = $nExceptUserID;
        }

        $res = $this->userDataByFilter($aFilter, 'user_id', $aBind);

        return (!empty($res));
    }

    /**
     * Генерируем уникальный логин (с проверкой на наличие)
     * @param string $prefix префикс, по-умолчанию "u"
     * @param boolean $increment
     * @return string
     */
    public function userLoginGenerate($prefix = 'u', $increment = false)
    {
        if ($increment) {
            $done = false;
            $i = 1;
            do {
                $login = $prefix . ($i > 1 ? $i : '');
                $i++;
                $res = $this->db->one_data('SELECT user_id FROM ' . TABLE_USERS . ' WHERE login = :login', array(':login' => $login));
                if (empty($res)) {
                    $done = true;
                } else {
                    # не нашли за 5 итераций, генерируем упрощенным вариантом
                    if ($i >= 5) {
                        $login = $this->userLoginGenerate($prefix, false);
                        $done = true;
                    }
                }
            } while (!$done);
        } else {
            $done = false;
            do {
                $login = $prefix . mt_rand(123456789, 987654321);
                $res = $this->db->one_data('SELECT user_id FROM ' . TABLE_USERS . ' WHERE login = :login', array(':login' => $login));
                if (empty($res)) {
                    $done = true;
                }
            } while (!$done);
        }

        return $login;
    }

    /**
     * Проверяем наличие пользователя по email-адресу
     * @param string $sEmail email адрес
     * @param int $nExceptUserID ID пользователя, которого следует исключить из проверки
     * @return bool
     */
    public function userEmailExists($sEmail, $nExceptUserID = 0) // isEmailExists
    {
        $res = $this->userDataByEmail($sEmail, 'user_id', $nExceptUserID);

        return (!empty($res));
    }

    /**
     * Данные пользователя по email-адресу
     * @param string $sEmail email адрес
     * @param string|array $aDataKeys ключи необходимых данных
     * @param int $nExceptUserID ID пользователя, которого следует исключить из поиска
     * @return array|mixed данные пользователя или false
     */
    public function userDataByEmail($sEmail, $aDataKeys = '*', $nExceptUserID = 0)
    {
        $aBind = array();
        $aFilter = array();
        if ($this->userEmailCrypted()) {
            $aFilter[':emailCheck'] = array('BFF_DECRYPT(email) = :email', ':email' => $sEmail);
        } else {
            $aFilter['email'] = $sEmail;
        }
        if (!empty($nExceptUserID)) {
            $aFilter[':userIdExcept'] = 'user_id != :userId';
            $aBind[':userId'] = $nExceptUserID;
        }

        return $this->userDataByFilter($aFilter, $aDataKeys, $aBind);
    }

    /**
     * Используется ли шифрование поля email в таблице TABLE_USERS
     * @return bool
     */
    public function userEmailCrypted()
    {
        return (!empty($this->cryptUsers) && in_array('email', $this->cryptUsers));
    }

    /**
     * Проверяем наличие пользователя по номеру телефона
     * @param string $sPhoneNumber номер телефона
     * @param integer $nExceptUserID ID пользователя, которого следует исключить из проверки
     * @param string $sFieldName имя поля телефона в БД
     * @return bool
     */
    public function userPhoneExists($sPhoneNumber, $nExceptUserID = 0, $sFieldName = 'phone_number')
    {
        $aFilter = array();
        if (!empty($this->cryptUsers) && in_array($sFieldName, $this->cryptUsers)) {
            $aFilter[':phone'] = array('BFF_DECRYPT('.$sFieldName.') = :phone', ':phone' => $sPhoneNumber);
        } else {
            $aFilter[$sFieldName] = $sPhoneNumber;
        }
        $aBind = array();
        if (!empty($nExceptUserID)) {
            $aFilter[':userIdExcept'] = 'user_id != :userId ';
            $aBind[':userId'] = $nExceptUserID;
        }

        $res = $this->userDataByFilter($aFilter, 'user_id', $aBind);

        return (!empty($res));
    }

    // ---------------------------------------------------------------------------
    // группы пользователей

    /**
     * Включаем пользователя в группу, по keyword'у группы
     * @param int $nUserID ID пользователя
     * @param string $sGroupKeyword keyword группы
     * @param bool $bOutgoinFromGroups открепить пользователя от закрепленных за ним групп
     * @return bool
     */
    public function userToGroup($nUserID, $sGroupKeyword, $bOutgoinFromGroups = true) // assignUser2Group
    {
        # получаем ID группы по $sGroupKeyword
        $nGroupID = (int)$this->db->one_data('SELECT group_id FROM ' . TABLE_USERS_GROUPS . '
                                         WHERE keyword = :keyword LIMIT 1',
            array(':keyword' => $sGroupKeyword)
        );
        if (!$nGroupID) {
            return false;
        }

        return $this->userToGroups($nUserID, $nGroupID, $bOutgoinFromGroups);
    }

    /**
     * Включаем пользователя в группы
     * @param int $nUserID ID пользователя
     * @param array|int $aGroupID ID группы (нескольких групп)
     * @param bool $bOutgoinFromGroups исключить пользователя из текущих групп перед включением в новые
     * @return bool
     */
    public function userToGroups($nUserID, $aGroupID, $bOutgoinFromGroups = true) // assignUser2Groups
    {
        if (!is_array($aGroupID)) {
            $aGroupID = array($aGroupID);
        }

        # открепляем пользователя от закрепленных за ним групп
        if ($bOutgoinFromGroups) {
            $this->userFromGroups($nUserID, null);
        } else {
            # исключаем группы в которых пользователь уже состоит
            $aGroupsCurrent = $this->userGroups($nUserID);
            if (!empty($aGroupsCurrent)) {
                foreach ($aGroupsCurrent as $v) {
                    $exists = array_search($v['group_id'], $aGroupID);
                    if ($exists !== false) {
                        unset($aGroupID[$exists]);
                    }
                }
            }
        }

        $aInsert = array();
        $sNOW = $this->db->now();
        foreach ($aGroupID as $nGroupID) {
            $nGroupID = intval($nGroupID);
            if ($nGroupID <= 0) {
                continue;
            }
            $aInsert[] = array(
                'user_id'  => $nUserID,
                'group_id' => $nGroupID,
                'created'  => $sNOW,
            );
        }

        if (!empty($aInsert)) {
            return $this->db->multiInsert(TABLE_USER_IN_GROUPS, $aInsert);
        }

        return false;
    }

    /**
     * Исключаем пользователя из групп
     * @param int $nUserID ID пользователя
     * @param array|int|null $aGroupID ID групп или null(исключаем из всех групп)
     * @param boolean $bKeywords true - указаны keyword'ы групп
     * @return mixed
     */
    public function userFromGroups($nUserID, $aGroupID = null, $bKeywords = false) // outgoinUserFromGroups
    {
        if (!empty($aGroupID)) {
            if (!is_array($aGroupID)) {
                $aGroupID = array($aGroupID);
            }

            # исключаем пользователя из указанных групп
            if ($bKeywords) {
                $aGroupID = $this->db->select_one_column('SELECT group_id
                        FROM ' . TABLE_USERS_GROUPS . '
                        WHERE '.$this->db->prepareIN('keyword', $aGroupID, false, false, false)
                );
                if (empty($aGroupID)) {
                    return 0;
                }
            }
            return $this->db->delete(TABLE_USER_IN_GROUPS, array('user_id' => $nUserID, 'group_id' => $aGroupID));
        } else {
            # исключаем пользователя из всех групп
            return $this->db->delete(TABLE_USER_IN_GROUPS, array('user_id' => $nUserID));
        }
    }

    /**
     * Исключаем нескольких пользователей из групп
     * @param array|int $mUserID ID пользователей
     * @param array|null $aGroupID
     * @param boolean $bKeywords true - указаны keyword'ы групп
     * @return mixed
     */
    public function usersFromGroups($mUserID, $aGroupID = null, $bKeywords = false) // outgoinUsersFromGroups
    {
        if (is_array($mUserID)) {
            if (isset($aGroupID)) {
                if (!is_array($aGroupID)) {
                    $aGroupID = array($aGroupID);
                }

                # исключаем указанных пользователей из указанных групп
                if ($bKeywords) {
                    $aGroupID = $this->db->select_one_column('SELECT group_id
                            FROM ' . TABLE_USERS_GROUPS . '
                            WHERE '.$this->db->prepareIN('keyword', $aGroupID, false, false, false)
                    );
                    if (empty($aGroupID)) {
                        return 0;
                    }
                }

                return $this->db->delete(TABLE_USER_IN_GROUPS, array('user_id' => $mUserID, 'group_id' => $aGroupID));
            } else {
                # исключаем указанных пользователей из всех групп в которые они входят
                return $this->db->delete(TABLE_USER_IN_GROUPS, array('user_id' => $mUserID));
            }
        } else {
            # исключаем одного пользователя из групп
            return $this->userFromGroups($mUserID, $aGroupID);
        }
    }

    /**
     * Является ли пользователь супер-администратором
     * @param int $nUserID ID пользователя
     * @return bool
     */
    public function userIsSuperAdmin($nUserID) // isSuperAdmin
    {
        return ((bool)$this->db->one_data('SELECT UIG.group_id
                   FROM ' . TABLE_USER_IN_GROUPS . ' UIG
                   WHERE UIG.user_id  = :user_id AND UIG.group_id = :group_id
                   LIMIT 1', array(':user_id' => $nUserID, ':group_id' => Users::GROUPID_SUPERADMIN)
        ));
    }

    /**
     * Является ли пользователь администратором (входит ли хотя-бы в одну группу с разрешенным доступом в админ. панель)
     * @param int $nUserID ID пользователя
     * @return bool
     */
    public function userIsAdministrator($nUserID) // isAdministrator
    {
        return ((bool)$this->db->one_data('SELECT UIG.group_id
                   FROM ' . TABLE_USER_IN_GROUPS . ' UIG, ' . TABLE_USERS_GROUPS . ' G
                   WHERE UIG.user_id  = :user_id AND UIG.group_id = G.group_id
                     AND G.adminpanel = 1
                   LIMIT 1', array(':user_id' => $nUserID)
        ));
    }

    /**
     * Получаем список групп пользователей
     * @param array|string|null $mExceptGroupKeyword исключить группы с указанными keyword
     * @param bool $bWithoutAdminpanelAccess только группы без доступа в админ-панель
     * @return mixed
     */
    public function groups($mExceptGroupKeyword = null, $bWithoutAdminpanelAccess = false) // getGroups
    {
        $aFilter = array();
        if (!empty($mExceptGroupKeyword)) {
            if (!is_array($mExceptGroupKeyword)) {
                $mExceptGroupKeyword = array($mExceptGroupKeyword);
            }

            $aFilter[] = $this->db->prepareIN('G.keyword', $mExceptGroupKeyword, true, false, false);
        }

        if ($bWithoutAdminpanelAccess) {
            $aFilter[] = 'G.adminpanel = 0';
        }

        return $this->db->select('SELECT G.*
            FROM ' . TABLE_USERS_GROUPS . ' G
            ' . (!empty($aFilter) ? ' WHERE ' . join(' AND ', $aFilter) : '') . '
            ORDER BY G.title'
        );
    }

    /**
     * Получаем список групп пользователя
     * @param int $nUserID ID пользователя
     * @param bool $bOnlyKeywords только keyword'ы групп
     * @return array
     */
    public function userGroups($nUserID, $bOnlyKeywords = false) // getUserGroups
    {
        $aBind = array(':id' => $nUserID);
        $sQuery = 'SELECT ' . ($bOnlyKeywords ? ' G.keyword ' : ' G.* ') . '
                  FROM ' . TABLE_USERS_GROUPS . ' G,
                       ' . TABLE_USER_IN_GROUPS . ' UIG
                  WHERE UIG.user_id = :id AND UIG.group_id = G.group_id
                  ORDER BY G.group_id ASC';

        return ($bOnlyKeywords ?
            $this->db->select_one_column($sQuery, $aBind) :
            $this->db->select($sQuery, $aBind));
    }

    /**
     * Получаем группы, в которых есть пользователи
     * @param bool $bWithAdminpanelAccess с доступом в админ-панель
     * @param string $mTransparentKey ключ, по которому выполняем группировку результата
     * @return array|mixed
     */
    public function usersGroups($bWithAdminpanelAccess = false, $mTransparentKey = 'user_id') // getUsersGroups
    {
        $aData = $this->db->select('SELECT G.*, U.user_id
            FROM ' . TABLE_USERS . ' U, ' . TABLE_USER_IN_GROUPS . ' UIG, ' . TABLE_USERS_GROUPS . ' G
            WHERE ' . ($bWithAdminpanelAccess ? 'G.adminpanel=1 AND ' : '') . '
                UIG.group_id = G.group_id AND U.user_id = UIG.user_id
            ORDER BY G.group_id');

        return (!empty($mTransparentKey) ?
            func::array_transparent($aData, $mTransparentKey, false) :
            $aData);
    }

    /**
     * Проверяем наличие группы по ключу
     * @param string $sGroupKeyword ключ группы
     * @param int $nExceptGroupID ID группы, которую следует исключить из проверки
     * @return bool
     */
    public function groupKeywordExists($sGroupKeyword, $nExceptGroupID = 0) // isGroupKeywordExists
    {
        if (empty($sGroupKeyword)) {
            return true;
        }

        $aBind = array(':keyword' => $sGroupKeyword);
        if (!empty($nExceptGroupID)) {
            $aBind[':gid'] = $nExceptGroupID;
        }

        return ((integer)$this->db->one_data('SELECT group_id FROM ' . TABLE_USERS_GROUPS . '
                   WHERE keyword = :keyword' . (!empty($nExceptGroupID) ? ' AND group_id != :gid' : '') . '
                   LIMIT 1', $aBind
            ) > 0);
    }

    /**
     * Создаем/обновляем группу
     * @param int $nGroupID ID группы или 0
     * @param array $aData данные
     * @return bool|int
     */
    public function groupSave($nGroupID, array $aData) // saveGroup
    {
        if ($nGroupID) {
            return $this->db->update(TABLE_USERS_GROUPS, $aData, array('group_id' => $nGroupID));
        } else {
            return $this->db->insert(TABLE_USERS_GROUPS, $aData);
        }
    }

    /**
     * Получаем данные о группе
     * @param int $nGroupID ID группы
     * @return array|mixed
     */
    public function groupData($nGroupID) // getGroup
    {
        return $this->db->one_array('SELECT * FROM ' . TABLE_USERS_GROUPS . '
                    WHERE group_id = :gid LIMIT 1', array(':gid' => $nGroupID)
        );
    }

    /**
     * Удаляем группу (исключаем пользователей состоящих в ней)
     * @param int $nGroupID ID группы
     * @return bool
     */
    public function groupDelete($nGroupID) // deleteGroup
    {
        $res = $this->db->delete(TABLE_USERS_GROUPS, array('group_id' => $nGroupID));
        if (!empty($res)) {
            $this->db->delete(TABLE_USERS_GROUPS_PERMISSIONS, array('unit_type' => 'group', 'unit_id' => $nGroupID));
            $this->db->delete(TABLE_USER_IN_GROUPS, array('group_id' => $nGroupID));

            return true;
        }

        return false;
    }

    /**
     * Получаем права доступа группы
     * @param int $nGroupID ID группы
     * @return mixed
     */
    public function groupPermissions($nGroupID) // getGroupPermissions
    {
        $sUnitType = 'group';
        $sItemType = 'module';

        $aData = $this->db->select('SELECT M.*, (P.item_id IS NOT NULL) as permissed
                    FROM ' . TABLE_MODULE_METHODS . ' M
                    LEFT JOIN ' . TABLE_USERS_GROUPS_PERMISSIONS . ' P
                        ON P.unit_type = :utype
                       AND P.unit_id = :uid
                       AND P.item_type = :itype
                       AND P.item_id = M.id
                    WHERE M.module = M.method
                    ORDER BY M.number, M.id
                 ', array(':utype' => $sUnitType, ':uid' => $nGroupID, ':itype' => $sItemType)
        );

        $aSubData = $this->db->select('SELECT M.*, (P.item_id IS NOT NULL) as permissed
                    FROM ' . TABLE_MODULE_METHODS . ' M
                    LEFT JOIN ' . TABLE_USERS_GROUPS_PERMISSIONS . ' P
                            ON P.unit_type = :utype
                               AND P.unit_id = :uid
                               AND P.item_type = :itype
                               AND P.item_id = M.id
                    WHERE M.module != M.method
                    ORDER BY M.number, M.id
                 ', array(':utype' => $sUnitType, ':uid' => $nGroupID, ':itype' => $sItemType)
        );
        $aSubData = func::array_transparent($aSubData, 'module');

        for ($i = 0; $i < count($aData); $i++) {
            $aData[$i]['subitems'] = array();
            if (isset($aSubData[$aData[$i]['module']])) {
                $aData[$i]['subitems'] = $aSubData[$aData[$i]['module']];
            }
        }

        return $aData;
    }

    /**
     * Сохраняем права доступа группы
     * @param int $nGroupID ID группы
     * @param array $aPermissionsID права доступа
     */
    public function groupPermissionsSave($nGroupID, $aPermissionsID) // setGroupPermissions
    {
        if (empty($nGroupID)) {
            return;
        }

        $this->groupPermissionsClear($nGroupID);

        $aInsert = array();
        $sUnitType = 'group';
        $sItemType = 'module';
        $sNow = $this->db->now();
        $j = count($aPermissionsID);
        for ($i = 0; $i < $j; $i++) {
            $id = (int)$aPermissionsID[$i];
            if ($id == 0) {
                continue;
            }

            $aInsert[] = array(
                'unit_type' => $sUnitType,
                'unit_id'   => $nGroupID,
                'item_type' => $sItemType,
                'item_id'   => $id,
                'created'   => $sNow,
            );
        }
        if (!empty($aInsert)) {
            $this->db->multiInsert(TABLE_USERS_GROUPS_PERMISSIONS, $aInsert);
        }
    }

    /**
     * Удаляем права доступа группы
     * @param int|array $mGroupID ID группы/нескольких групп
     * @return void
     */
    public function groupPermissionsClear($aGroupID) // clearGroupPermissions
    {
        if (empty($aGroupID)) {
            return;
        }

        if (!is_array($aGroupID)) {
            $aGroupID = array($aGroupID);
        }

        $this->db->delete(TABLE_USERS_GROUPS_PERMISSIONS, array(
                'unit_id'   => $aGroupID,
                'unit_type' => 'group',
            )
        );
    }

    /**
     * Создание бан-правила
     * @param string $sMode Режим бана: user, ip, email
     * @param array $ban Бан-items
     * @param integer $banPeriod Период бана или 0 - постоянный
     * @param string $banPeriodDate Период бана (дата, до которой банить)
     * @param integer $nExclude Исключение
     * @param string $sDescription Описание бана
     * @param string $sReason Причина бана (показываемая пользователю)
     */
    public function banCreate($sMode, $ban, $banPeriod, $banPeriodDate, $nExclude = 0, $sDescription = '', $sReason = '')
    {
        # Удаляем просроченные баны
        $this->db->delete(TABLE_USERS_BANLIST, array('finished<' . time(), 'finished <> 0'));

        $ban = (!is_array($ban)) ? array_unique(explode(PHP_EOL, $ban)) : $ban;

        $nCurrentTime = time();

        # Переводим $banEnd в unixtime. 0 - постоянный бан.
        if ($banPeriod) {
            if ($banPeriod != -1 || !$banPeriodDate) {
                $banEnd = max($nCurrentTime, $nCurrentTime + ($banPeriod) * 60);
            } else {
                $banPeriodDate = explode('-', $banPeriodDate);
                if (sizeof($banPeriodDate) == 3 && ((int)$banPeriodDate[2] < 9999) &&
                    (strlen($banPeriodDate[2]) == 4) && (strlen($banPeriodDate[1]) == 2) && (strlen($banPeriodDate[0]) == 2)
                ) {
                    $banEnd = max($nCurrentTime, gmmktime(0, 0, 0, (int)$banPeriodDate[1], (int)$banPeriodDate[0], (int)$banPeriodDate[2]));
                } else {
                    $this->errors->set(_t('users', 'Дата должна быть в формате <kbd>ДД-ММ-ГГГГ</kbd>.'));
                }
            }
        } else {
            $banEnd = 0;
        }

        $aBanlistResult = array();

        switch ($sMode) {
            case 'user':
            {
                $type = 'uid';

                # Не поддеживается бан пользователей по групповому символу(*,?)

                $aUserlogins = array();

                foreach ($ban as $userlogin) {
                    $userlogin = trim($userlogin);
                    if ($userlogin != '') {
                        if ($userlogin == $this->security->getUserLogin()) {
                            $this->errors->set(_t('users', 'Вы не можете закрыть доступ самому себе.'));
                        }

                        $aUserlogins[] = $userlogin;
                    }
                }

                // Make sure we have been given someone to ban
                if (!sizeof($aUserlogins)) {
                    $this->errors->set(_t('users', 'Имя пользователя не определено.'));
                }

                $aResult = $this->db->select_one_column('SELECT id FROM ' . TABLE_USERS . '
                                WHERE ' . $this->db->prepareIN('login', $aUserlogins, false, false) . '
                                 AND id <> :userId', array(':userId' => $this->security->getUserID())
                );

                if (!empty($aResult)) {
                    foreach ($aResult as $uid) {
                        $aBanlistResult[] = (integer)$uid;
                    }
                } else {
                    $this->errors->set(_t('users', 'Запрашиваемых пользователей не существует.'));
                }
                unset($aResult);
            }
            break;
            case 'ip':
            {
                $type = 'ip';

                foreach ($ban as $banItem) {
                    if (preg_match('#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})[ ]*\-[ ]*([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#', trim($banItem), $ip_range_explode)) {
                        // This is an IP range
                        $ip_1_counter = $ip_range_explode[1];
                        $ip_1_end = $ip_range_explode[5];

                        while ($ip_1_counter <= $ip_1_end) {
                            $ip_2_counter = ($ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[2] : 0;
                            $ip_2_end = ($ip_1_counter < $ip_1_end) ? 254 : $ip_range_explode[6];

                            if ($ip_2_counter == 0 && $ip_2_end == 254) {
                                $ip_2_counter = 256;
                                $ip_2_fragment = 256;

                                $aBanlistResult[] = "$ip_1_counter.*";
                            }

                            while ($ip_2_counter <= $ip_2_end) {
                                $ip_3_counter = ($ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[3] : 0;
                                $ip_3_end = ($ip_2_counter < $ip_2_end || $ip_1_counter < $ip_1_end) ? 254 : $ip_range_explode[7];

                                if ($ip_3_counter == 0 && $ip_3_end == 254) {
                                    $ip_3_counter = 256;
                                    $ip_3_fragment = 256;

                                    $aBanlistResult[] = "$ip_1_counter.$ip_2_counter.*";
                                }

                                while ($ip_3_counter <= $ip_3_end) {
                                    $ip_4_counter = ($ip_3_counter == $ip_range_explode[3] && $ip_2_counter == $ip_range_explode[2] && $ip_1_counter == $ip_range_explode[1]) ? $ip_range_explode[4] : 0;
                                    $ip_4_end = ($ip_3_counter < $ip_3_end || $ip_2_counter < $ip_2_end) ? 254 : $ip_range_explode[8];

                                    if ($ip_4_counter == 0 && $ip_4_end == 254) {
                                        $ip_4_counter = 256;
                                        $ip_4_fragment = 256;

                                        $aBanlistResult[] = "$ip_1_counter.$ip_2_counter.$ip_3_counter.*";
                                    }

                                    while ($ip_4_counter <= $ip_4_end) {
                                        $aBanlistResult[] = "$ip_1_counter.$ip_2_counter.$ip_3_counter.$ip_4_counter";
                                        $ip_4_counter++;
                                    }
                                    $ip_3_counter++;
                                }
                                $ip_2_counter++;
                            }
                            $ip_1_counter++;
                        }
                    } else {
                        if (preg_match('#^([0-9]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})\.([0-9\*]{1,3})$#', trim($banItem)) || preg_match('#^[a-f0-9:]+\*?$#i', trim($banItem))) {
                            # Нормальный IP адрес
                            $aBanlistResult[] = trim($banItem);
                        } else {
                            if (preg_match('#^\*$#', trim($banItem))) {
                                # Баним все IP адреса
                                $aBanlistResult[] = '*';
                            } else {
                                if (preg_match('#^([\w\-_]\.?){2,}$#is', trim($banItem))) {
                                    # Имя хоста
                                    $ip_ary = gethostbynamel(trim($banItem));

                                    if (!empty($ip_ary)) {
                                        foreach ($ip_ary as $ip) {
                                            if ($ip) {
                                                if (mb_strlen($ip) > 40) {
                                                    continue;
                                                }

                                                $aBanlistResult[] = $ip;
                                            }
                                        }
                                    }
                                    $this->errors->set(_t('users', 'Не удалось определить IP-адрес указанного хоста'));
                                } else {
                                    $this->errors->set(_t('users', 'Не определён IP-адрес или имя хоста'));
                                }
                            }
                        }
                    }
                }
            }
            break;
            case 'email':
            {
                $type = 'email';

                foreach ($ban as $banItem) {
                    $banItem = trim($banItem);
                    if (preg_match('#^.*?@*|(([a-z0-9\-]+\.)+([a-z]{2,3}))$#i', $banItem)) {
                        if (strlen($banItem) > 100) {
                            continue;
                        }

                        $aBanlistResult[] = $banItem;
                    }
                }

                if (sizeof($ban) == 0) {
                    $this->errors->set(_t('users', 'Не найдено правильных адресов электронной почты.'));
                }

            }
            break;
            default:
            {
                $this->errors->set(_t('users', 'Не указан режим.'));
            }
            break;
        }

        # Fetch currently set bans of the specified type and exclude state. Prevent duplicate bans.
        $aResult = $this->db->select_one_column("SELECT $type
            FROM " . TABLE_USERS_BANLIST . '
            WHERE ' . ($type == 'uid' ? 'uid <> 0' : "$type <> ''") . '
                AND exclude = ' . (integer)$nExclude
        );

        if (!empty($aResult)) {
            $aBanlistResultTemp = array();
            foreach ($aResult as $item) {
                $aBanlistResultTemp[] = $item;
            }

            $aBanlistResult = array_unique(array_diff($aBanlistResult, $aBanlistResultTemp));
            unset($aBanlistResultTemp);
        }
        unset($aResult);

        # Есть что банить
        if (sizeof($aBanlistResult)) {
            $nExclude = (integer)$nExclude;
            $banEnd = (integer)$banEnd;
            $sDescription = (string)$sDescription;
            $sReason = (string)$sReason;

            $aEntries = array();
            foreach ($aBanlistResult as $banItem) {
                $aEntries[] = array(
                    $type         => $banItem,
                    'started'     => $nCurrentTime,
                    'finished'    => $banEnd,
                    'exclude'     => $nExclude,
                    'description' => $sDescription,
                    'reason'      => $sReason
                );
            }

            $this->db->multiInsert(TABLE_USERS_BANLIST, $aEntries);

            # Если мы баним, тогда необходимо разлогинить пользователей подпадающих под бан
            if (!$nExclude && false) {
                $sQueryWhere = '';
                switch ($sMode) {
                    case 'user':
                    {
                        $sQueryWhere = 'WHERE ' . $this->db->prepareIN('session_user_id', $aBanlistResult, false, false);
                    }
                    break;
                    case 'ip':
                    {
                        $sQueryWhere = 'WHERE ' . $this->db->prepareIN('session_ip', $aBanlistResult, false, false);
                    }
                    break;
                    case 'email':
                    {
                        $aQueryBanlist = array();
                        foreach ($aBanlistResult as $banEntry) {
                            $aQueryBanlist[] = (string)str_replace('*', '%', $banEntry);
                        }

                        $aUsersIn = $this->db->select_one_column('SELECT id
                            FROM ' . TABLE_USERS . '
                            WHERE ' . $this->db->prepareIN('email', $aQueryBanlist, false, false)
                        );

                        $sQueryWhere = 'WHERE ' . $this->db->prepareIN('session_user_id', $aUsersIn, false, false);

                        unset($aUsersIn);
                    }
                    break;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Удаляем правило бана
     * @param integer|array $mBanID ID правила(правил)
     */
    public function banDelete($mBanID)
    {
        # удаляем просроченные баны
        $this->db->delete(TABLE_USERS_BANLIST, array('finished<' . time(), 'finished <> 0'));

        if (!is_array($mBanID)) {
            $mBanID = array($mBanID);
        }

        $aUnbanID = array_map('intval', $mBanID);
        if (sizeof($aUnbanID)) {
            $this->db->delete(TABLE_USERS_BANLIST, array('id' => $aUnbanID));
        }
    }

}