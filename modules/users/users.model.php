<?php

class UsersModel_ extends UsersModelBase
{
    public function init()
    {
        parent::init();

        # список полей данных о пользователе запрашиваемых для сохранения в сессии
        $this->userSessionDataKeys = bff::filter('users.model.user.session.data.keys', array(
            'user_id as id',
            'member',
            'login',
            'shop_id',
            'password',
            'password_salt',
            'email',
            'phone_number',
            'phone_number_verified',
            'name',
            'surname',
            'phone',
            'avatar',
            'sex',
            'birthdate',
            'activated',
            'blocked',
            'blocked_reason',
            'admin',
            'last_login',
            'region_id',
            'social_id',
            'lang',
        ));
        # список полей счетчиков
        $this->userStatCounters = bff::filter('users.model.user.stat.counters', array('internalmail_new', 'items', 'items_fav',));
    }

    /**
     * Получаем данные о пользователе по ID или фильтру
     * @param array|int $filter фильтр или ID пользователя
     * @param mixed $aDataKeys ключи необходимых данных
     * @param bool $bEdit true - выполнить подготовку данных для редактирования
     * @return array|mixed
     */
    public function userData($filter, $aDataKeys = array(), $bEdit = false)
    {
        if ( ! is_array($filter)) {
            $filter = array('user_id' => intval($filter));
        }
        $aData = $this->userDataByFilter($filter, $aDataKeys);
        if ($bEdit) {
            if (isset($aData['region_id'])) {
                $aData['region_title'] = Geo::regionTitle($aData['region_id']);
            }
        }
        if (isset($aData['phones']) || (is_array($aData) && array_key_exists('phones', $aData))) {
            $aData['phones'] = (!empty($aData['phones']) ? func::unserialize($aData['phones']) : array());
        }
        if (isset($aData['extra'])) {
            $aData['extra'] = func::unserialize($aData['extra']);
        }

        return $aData;
    }

    /**
     * Данные о пользователе для правого блока
     * @param integer $nUserID ID пользователя
     * @param array $aFieldsEx ключи доп. полей
     * @return array
     */
    public function userDataSidebar($nUserID, $aFieldsEx = array())
    {
        $aFields = bff::filter('users.model.user.data.sidebar', array('user_id as id', 'name', 'login', 'last_login', 'last_activity', 'shop_id', 'created', 'avatar', 'sex',
                         'phones', 'phone_number', 'phone_number_verified', 'contacts'));
        if (!empty($aFieldsEx)) {
            $aFields = array_merge($aFields, $aFieldsEx);
        }
        $aData = $this->userData($nUserID, $aFields);
        if (!empty($aData)) {
            $aData['link'] = Users::urlProfile($aData['login']);
            $aData['avatar'] = UsersAvatar::url($nUserID, $aData['avatar'], UsersAvatar::szNormal, $aData['sex']);
            if (Users::registerPhoneContacts() && $aData['phone_number'] && $aData['phone_number_verified']) {
                if (!is_array($aData['phones'])) { $aData['phones'] = array(); }
                array_unshift($aData['phones'], array('v'=>$aData['phone_number'],'m'=>Users::phoneMask($aData['phone_number'])));
            }
        }

        return $aData;
    }

    /**
     * Получаем данные о пользователе - для отправки ему email-уведомления.
     * Проверяем возможно ли отправить письмо пользователю и если нет возвращаем FALSE.
     * @param integer $nUserID ID пользователя
     * @param integer $nEnotifyID ID настройки email-уведомления, 0 - не выполнять проверку настроек уведомлений
     * @param array $aDataKeys требуемые данные
     * @return array|boolean данные или FALSE
     */
    public function userDataEnotify($nUserID, $nEnotifyID = 0, array $aDataKeys = array())
    {
        $aDataKeys = array_merge($aDataKeys, bff::filter('users.model.user.data.enotify', array(
                'user_id',
                'name',
                'email',
                'login',
                'blocked',
                'enotify',
                'activated',
                'user_id_ex',
                'last_login',
                'lang',
                'fake',
            ))
        );
        $aData = $this->userData($nUserID, $aDataKeys, false);

        do {
            # не нашли такого пользователя
            if (empty($aData)) {
                break;
            }
            # заблокирован / неактивирован / фейковый
            if ($aData['blocked'] || !$aData['activated'] || $aData['fake']) {
                break;
            }
            # запретил email-уведомления
            if ($nEnotifyID > 0) {
                if (empty($aData['enotify']) || !($aData['enotify'] & $nEnotifyID)) {
                    break;
                }
            }
            # email адрес некорректный
            if (!$this->input->isEmail($aData['email'])) {
                break;
            }
            if (empty($aData['lang'])) {
                $aData['lang'] = LNG;
            }

            return $aData;
        } while (false);

        return false;
    }

    /**
     * Удаляем неактивированные аккаунты с просроченным периодом активации
     * Период действия ссылки активации аккаунта пользователя - 7 дней (Users::getActivationInfo)
     * Период действия ссылки активации объявления - 1 день (BBS::getActivationInfo)
     */
    public function usersCronDeleteNotActivated()
    {
        //$this->deleteUnactivated(true);
        return;
    }

    /**
     * Удаление неактивированных пользователей
     * @param boolean $bAll удалить всех неактивных
     * @param array $aUsersID ID удаляемых пользователей
     * @return integer кол-во помеченных на удаление пользователей
     */
    public function deleteUnactivated($bAll, array $aUsersID = array())
    {
        # помечаем для удаления
        $aFilter = array(
            'activated' => 0,
            'admin'     => 0,
        );

        if (!$bAll) {
            if (empty($aUsersID)) {
                return 0;
            }
            $aFilter['user_id'] = $aUsersID;
        }

        $total = $this->db->update(TABLE_USERS, array(
            'deleted' => Users::DESTROY_BY_ADMIN,
            'blocked' => 1,
            'blocked_reason' => _t('users', 'Учетная запись будет удалена в течение суток'),
        ), $aFilter);
        if ($total > 0) {
            bff::cronManager()->executeOnce('users', 'cronDeleteUsers');
        }

        return $total;
    }

    /**
     * Удаление пользователей помеченных для удаления
     */
    public function usersCronDelete()
    {
        $avatar = Users::i()->avatar(0);

        $this->db->select_iterator('SELECT user_id, admin, avatar, deleted FROM '.TABLE_USERS.' WHERE deleted > 0', array(), function($row) use(& $avatar){
            if ($row['user_id'] == 1 || ($row['admin'] && $this->userIsSuperAdmin($row['user_id']))) {
                $this->userSave($row['user_id'], array(
                    'deleted' => 0,
                    'blocked' => 0,
                    'blocked_reason' => '',
                ));
                return;
            }

            if ($row['avatar']) {
                $avatar->setRecordID($row['user_id']);
                $avatar->delete(false, $row['avatar']);
            }

            $options = array(
                'initiator' => $row['deleted'] == Users::DESTROY_BY_OWNER ? 'user' : 'admin',
            );
            bff::log('Deleted user '.$row['user_id'].' by '.$options['initiator'], Logger::INFO);

            bff::hook('users.user.deleted', $row['user_id'], $options);
            bff::i()->callModules('onUserDeleted', array($row['user_id'], $options));

            $this->db->delete(TABLE_USERS, array('user_id' => $row['user_id']));
            $this->db->delete(TABLE_USERS_STAT, array('user_id' => $row['user_id']));
            $this->db->delete(TABLE_USER_IN_GROUPS, array('user_id' => $row['user_id']));
            $this->db->delete(TABLE_USERS_SOCIAL, array('user_id' => $row['user_id']));
        });
    }

}