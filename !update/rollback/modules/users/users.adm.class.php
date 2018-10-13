<?php

/**
 * Права доступа группы:
 *  - users: Пользователи
 *      - profile: Ваш профиль
 *      - members-listing: Список пользователей
 *      - admins-listing: Список администраторов
 *      - users-edit: Управление пользователями
 *      - ban: Блокировка пользователей
 *      - groups-listing: Список групп пользователей
 *      - groups-edit: Управление группами пользователей
 */
class Users_ extends UsersBase
{
    function main()
    {
        $this->login();
    }

    public function profile()
    {
        if (!$this->haveAccessTo('profile')) {
            return $this->showAccessDenied();
        }

        $bChangeLogin = false;

        $nUserID = User::id();
        if (!$nUserID) {
            $this->adminRedirect(Errors::IMPOSSIBLE, 'login');
        }

        if (Request::isPOST()) {

            $sLogin = $this->input->post('login', TYPE_STR);
            $bChangePassword = $this->input->post('changepass', TYPE_BOOL);
            $bChangeLogin = ($bChangeLogin && !empty($sLogin));

            $aData = $this->model->userData($nUserID, array('password', 'password_salt'));

            $sql = array();
            if ($this->errors->no() && $bChangePassword) {
                $this->input->postm(array(
                        'password0' => TYPE_NOTRIM, # текущий пароль
                        'password1' => TYPE_NOTRIM, # новый пароль
                        'password2' => TYPE_NOTRIM, # подтверждение нового пароля
                    ), $p
                );
                extract($p);

                do {
                    if (!$password0) {
                        $this->errors->set(_t('users', 'Укажите текущий пароль'), 'password0');
                        break;
                    }

                    if ($aData['password'] != $this->security->getUserPasswordMD5($password0, $aData['password_salt'])) {
                        $this->errors->set(_t('users', 'Текущий пароль указан некорректно'), 'password0');
                        break;
                    }

                    if (empty($password1) || strlen($password1) < $this->passwordMinLength) {
                        $this->errors->set(_t('users', 'Новый пароль слишком короткий'), 'password1');
                        break;
                    }
                    if ($password1 !== $password2) {
                        $this->errors->set(_t('users', 'Ошибка подтверждения пароля'), 'password2');
                        break;
                    }
                    if ($this->errors->no()) {
                        $sql['password'] = $this->security->getUserPasswordMD5($password1, $aData['password_salt']);
                    }
                } while (false);
            }

            if ($this->errors->no() && $bChangeLogin) {
                do {
                    if (empty($sLogin)) {
                        $this->errors->set(_t('users', 'Укажите логин'), 'login');
                        break;
                    }
                    if (!$this->isLoginCorrect($sLogin)) {
                        $this->errors->set(_t('users', 'Пожалуйста для поля <strong>логин</strong> используйте символы A-Z,a-z,0-9,_'), 'login');
                        break;
                    }

                    if ($this->model->userLoginExists($sLogin, $nUserID, array('list', 'view'))) {
                        $this->errors->set(_t('users', 'Указанный логин уже существует, укажите другой'), 'login');
                        break;
                    }

                    $sql['login'] = $sLogin;
                } while (false);
            }

            if ($this->errors->no('users.admin.profile.submit',array('id'=>$nUserID,'data'=>&$sql)) && !empty($sql)) {
                $this->model->userSave($nUserID, $sql);
                $this->security->expire();
                $this->adminRedirect(Errors::SUCCESS, 'profile');
            }
        }

        $aUserGroups = $this->model->userGroups($nUserID);
        if (empty($aUserGroups)) {
            $aUserGroups = array();
        }

        $aData = User::data(array('name', 'login', 'avatar', 'email'));
        $aData['user_id'] = $nUserID;
        $aData['tuid'] = $this->makeTUID($nUserID);
        $aData['groups'] = $aUserGroups;
        $aData['changelogin'] = $bChangeLogin;

        return $this->viewPHP($aData, 'admin.profile');
    }

    function logout()
    {
        bff::hook('users.user.logout', User::id());
        $this->security->sessionDestroy($this->adminLink('login'));
    }

    function login()
    {
        if ($this->security->haveAccessToAdminPanel()) {
            $this->adminRedirect(null, 'profile');
        }

        $sLogin = '';
        if (Request::isPOST()) {
            $sLogin = $this->input->post('login', TYPE_STR);
            if (!$sLogin) {
                $this->errors->set(_t('users', 'Укажите логин'));
            }

            $sPassword = $this->input->post('password', TYPE_STR);
            if (!$sPassword) {
                $this->errors->set(_t('users', 'Укажите пароль'));
            }

            if ($this->errors->no('users.admin.login.step1', array('id'=>0,'login'=>$sLogin,'password'=>$sPassword))) {
                $this->isLoginOrEmail($sLogin, $isEmail);
                $aData = $this->model->userSessionData(array(($isEmail ? 'email' : 'login') => $sLogin));
                if (!empty($aData)) {
                    if ($aData['password'] != $this->security->getUserPasswordMD5($sPassword, $aData['password_salt'])) {
                        $aData = false;
                    }
                }

                if (!$aData) {
                    $this->errors->set(_t('users', 'Логин или пароль были указаны некорректно'));
                    $nUserID = 0;
                } else {
                    $nUserID = $aData['id'];

                    # аккаунт заблокирован
                    if ($aData['blocked'] == 1) {
                        $this->errors->set(sprintf(_t('users', 'Акканут заблокирован: %s'), $aData['blocked_reason']));
                    } # проверка IP в бан-листе
                    else {
                        if ($mBlocked = $this->checkBan(true)) {
                            $this->errors->set(_t('users', 'Доступ заблокирован по причине:<br />[reason]', array('reason' => $mBlocked)));
                        } # проверка доступа в админ-панель
                        else {
                            if (!$this->security->haveAccessToAdminPanel($nUserID)) {
                                $this->errors->accessDenied();
                            }
                        }
                    }
                }

                if ($this->errors->no('users.admin.login.step2', array('id'=>$nUserID,'data'=>&$aData))) {
                    $aUserGroups = $this->model->userGroups($nUserID, true);

                    # стартуем сессию администратора
                    $this->security->sessionStart();

                    # обновляем статистику
                    $this->model->userSave($nUserID, false, array(
                            'last_activity' => $this->db->now(),
                            'last_login2'   => $aData['last_login'],
                            'last_login'    => $this->db->now(),
                            'last_login_ip' => Request::remoteAddress(true),
                            'session_id'    => session_id(),
                        )
                    );

                    $this->security->setUserInfo($nUserID, $aUserGroups, $aData);

                    $this->redirect($this->adminLink(null));
                }
            }
        }

        $data = array('login' => $sLogin, 'errors' => $this->errors->get(true));
        echo View::template('login', $data);
        bff::shutdown();
    }

    //-------------------------------------------------------------------
    // users

    public function listing() # список пользователей
    {
        if (!$this->haveAccessTo('members-listing')) {
            return $this->showAccessDenied();
        }

        $f = $this->input->getm(array(
                'status' => TYPE_INT, # статус
                'q'      => TYPE_NOTAGS, #  поиск по ID/login/email
                'region' => TYPE_UINT, # страна / регион / город
                'r_from' => TYPE_NOTAGS, # регистрация: от
                'r_to'   => TYPE_NOTAGS, # регистрация: до
                'a_from' => TYPE_NOTAGS, # авторизация: от
                'a_to'   => TYPE_NOTAGS, # авторизация: до
                'page'   => TYPE_UINT, # страница
            )
        );

        $sqlFilter = array(':notSuperAdmin' => 'user_id != 1', 'admin' => 0);
        if ($f['status'] > 0) {
            switch ($f['status']) {
                case 1:
                    $sqlFilter['activated'] = 1;
                    break;
                case 2:
                    $sqlFilter['activated'] = 0;
                    break;
                case 3:
                    $sqlFilter['blocked'] = 1;
                    break;
                case 4:
                    $sqlFilter[':subscribed'] = 'enotify & ' . static::ENOTIFY_NEWS;
                    $sqlFilter['blocked'] = 0;
                    break;
            }
        }

        if ($f['q'] != '') {
            $sqlFilter[':q'] = array(
                '( user_id = :q_user_id OR login LIKE :q_login OR email LIKE :q_email OR phone_number LIKE :q_email )',
                ':q_user_id' => intval($f['q']),
                ':q_login'   => '%' . $f['q'] . '%',
                ':q_email'   => '%' . $f['q'] . '%',
            );
        }
        if ($f['region'] > 0) {
            $aRegions = Geo::regionData($f['region']);
            switch ($aRegions['numlevel']) {
                case Geo::lvlCountry: $sqlFilter['reg1_country'] = $f['region']; break;
                case Geo::lvlRegion:  $sqlFilter['reg2_region']  = $f['region']; break;
                case Geo::lvlCity:    $sqlFilter['reg3_city']    = $f['region']; break;
            }
        }

        # период регистрации
        if (!empty($f['r_from'])) {
            $r_from = strtotime($f['r_from']);
            if (!empty($r_from)) {
                $sqlFilter[':regFrom'] = array('created >= :regFrom', ':regFrom' => date('Y-m-d', $r_from));
            }
        }
        if (!empty($f['r_to'])) {
            $r_to = strtotime($f['r_to']);
            if (!empty($r_to)) {
                $sqlFilter[':regTo'] = array('created <= :regTo', ':regTo' => date('Y-m-d', $r_to));
            }
        }

        # период последней авторизации
        if (!empty($f['a_from'])) {
            $a_from = strtotime($f['a_from']);
            if (!empty($a_from)) {
                $sqlFilter[':authFrom'] = array('last_login >= :authFrom', ':authFrom' => date('Y-m-d', $a_from));
            }
        }
        if (!empty($f['a_to'])) {
            $a_to = strtotime($f['a_to']);
            if (!empty($a_to)) {
                $sqlFilter[':authTo'] = array('last_login <= :authTo', ':authTo' => date('Y-m-d', $a_to));
            }
        }



        $aOrdersAllowed = array('user_id' => 'asc', 'email' => 'asc', 'last_login' => 'desc');
        $aData = $this->prepareOrder($orderBy, $orderDirection, 'user_id' . tpl::ORDER_SEPARATOR . 'asc', $aOrdersAllowed);

        if (!$f['page']) {
            $f['page'] = 1;
        }
        $aData['filter'] = '&' . http_build_query($f);

        $nTotal = $this->model->usersList($sqlFilter, array(), true);

        $oPgn = new Pagination($nTotal, 15, $this->adminLink("listing{$aData['filter']}&order=$orderBy-$orderDirection&page=" . Pagination::PAGE_ID));
        $aData['pgn'] = $oPgn->view();


        $aData['users'] = $this->model->usersList($sqlFilter, array(
                'user_id',
                'admin',
                'name',
                'login',
                'email',
                'shop_id',
                'last_login',
                'blocked',
                'activated'
            ), false, $oPgn->getLimitOffset(), "$orderBy $orderDirection"
        );

        # данные о магазинах пользователей
        if ($aData['shops_on'] = bff::shopsEnabled()) {
            $aUsersShops = Shops::model()->shopsDataToUsersListing((!empty($aData['users']) ? array_keys($aData['users']) : array()));
            foreach ($aData['users'] as $k => &$v) {
                if (isset($aUsersShops[$k])) {
                    $v['shop'] = $aUsersShops[$k];
                }
            }
            unset($v);
        }

        foreach ($aData['users'] as &$v) {
            $v['tuid'] = $this->makeTUID($v['user_id']);
        }
        unset($v);

        $aData['f'] = $f;

        return $this->viewPHP($aData, 'admin.listing');
    }

    function listing_moderators() # список модераторов
    {
        if (!$this->haveAccessTo('admins-listing')) {
            return $this->showAccessDenied();
        }

        $aOrdersAllowed = array('user_id' => 'desc', 'login' => 'asc', 'email' => 'asc');
        $aData = $this->prepareOrder($orderBy, $orderDirection, 'login' . tpl::ORDER_SEPARATOR . 'asc', $aOrdersAllowed);

        $sqlFilter = array('admin' => 1);

        $nTotal = $this->model->usersList($sqlFilter, array(), true);
        $oPgn = new Pagination($nTotal, 20, $this->adminLink(__FUNCTION__ . "&order=$orderBy-$orderDirection&page=" . Pagination::PAGE_ID));
        $aData['pgn'] = $oPgn->view();

        $aData['users'] = $this->model->usersList($sqlFilter,
            array('user_id', 'login', 'email', 'password', 'blocked', 'deleted', 'activated'),
            false, $oPgn->getLimitOffset(), "$orderBy $orderDirection"
        );

        # получаем все группы с доступом в админ. панель
        $aUsersGroups = $this->model->usersGroups(true, 'user_id');

        foreach ($aData['users'] as $k => $v) {
            $aData['users'][$k]['tuid'] = $this->makeTUID($v['user_id']);
            $aData['users'][$k]['groups'] = array();
            if (isset($aUsersGroups[$v['user_id']])) {
                $aData['users'][$k]['groups'] = $aUsersGroups[$v['user_id']];
            }
        }

        $aData['page'] = $oPgn->getCurrentPage();

        return $this->viewPHP($aData, 'admin.listing.moderators');
    }

    function user_add()
    {
        if (!$this->haveAccessTo('users-edit')) {
            return $this->showAccessDenied();
        }

        $this->validateUserData($aData, 0);

        if (Request::isPOST()) {

            $aResponse = array();
            $aGroupID = $aData['group_id'];
            do {
                $aData['member'] = (in_array(self::GROUPID_MEMBER, $aGroupID) ? 1 : 0);
                $aData['password_salt'] = $this->security->generatePasswordSalt();
                $aData['password'] = $this->security->getUserPasswordMD5($aData['password'], $aData['password_salt']);
                $aData['activated'] = 1;
                $aData['user_id_ex'] = func::generator(6);

                unset($aData['group_id']);
                if (empty($aGroupID)) {
                    $aGroupID = array(self::GROUPID_MEMBER);
                }

                if (!$this->errors->no('users.admin.user.submit', array('id'=>0,'data'=>&$aData,'groups'=>&$aGroupID))) {
                    break;
                }

                $nUserID = $this->model->userCreate($aData, $aGroupID);
                if (!$nUserID) {
                    $this->errors->impossible();
                    break;
                }

                $sqlUpdate = array();

                # загружаем аватар
                $aAvatar = $this->avatar($nUserID)->uploadFILES('avatar', false, false);
                if ($aAvatar !== false && !empty($aAvatar['filename'])) {
                    $sqlUpdate['avatar'] = $aAvatar['filename'];
                }

                # обновляем, является ли пользователь администратором
                $bIsAdmin = 0;
                if (count($aGroupID) == 1 && current($aGroupID) == self::GROUPID_MEMBER) {
                    $bIsAdmin = 0;
                } else {
                    if (in_array(self::GROUPID_SUPERADMIN, $aGroupID) ||
                        in_array(self::GROUPID_MODERATOR, $aGroupID)
                    ) {
                        $bIsAdmin = 1;
                    } else {
                        $aUserGroups = $this->model->groups();
                        foreach ($aUserGroups as $v) {
                            if ($v['adminpanel'] == 1) {
                                $bIsAdmin = 1;
                                break;
                            }
                        }
                    }
                }

                if ($bIsAdmin) {
                    $sqlUpdate['admin'] = $bIsAdmin;
                }

                if (!empty($sqlUpdate)) {
                    $this->model->userSave($nUserID, $sqlUpdate);
                }

                $aResponse['redirect'] = ($bIsAdmin ? 'listing_moderators' : 'listing');
            } while (false);

            $this->iframeResponseForm($aResponse);
        }

        $aData = array_merge($aData, array('password' => '', 'password2' => '', 'user_id' => '', 'avatar' => ''));
        $aActiveGroupsID = array(self::GROUPID_MEMBER);
        $this->prepareGroupsOptions($aData, array(USERS_GROUPS_SUPERADMIN), $aActiveGroupsID);

        $aData['phones'] = array(); # телефоны
        $aData['user_id'] = 0;
        $aData['shops_on'] = bff::shopsEnabled();
        $aData['region_title'] = '';

        return $this->viewPHP($aData, 'admin.user.form');
    }

    function user_edit()
    {
        if (!$this->haveAccessTo('users-edit')) {
            return $this->showAccessDenied();
        }

        if (!($nUserID = $this->input->get('rec', TYPE_UINT))) {
            $this->adminRedirect(Errors::IMPOSSIBLE, 'listing');
        }

        $sTUID = $this->input->get('tuid');
        if (!$this->checkTUID($sTUID, $nUserID)) {
            return $this->showAccessDenied();
        }

        $aData = array('admin' => 0);

        # анализируем группы, в которые входит пользователь
        $bUserSuperadmin = 0;
        $aUserGroups = $this->model->userGroups($nUserID);
        foreach ($aUserGroups as $v) {
            if ($v['group_id'] == self::GROUPID_SUPERADMIN) {
                $bUserSuperadmin = 1;
            }
            if ($v['adminpanel'] == 1) {
                $aData['admin'] = 1;
            }
        }

        if (Request::isPOST()) {

            $aResponse = array('reload' => false, 'back' => false);
            $this->validateUserData($aData, $nUserID);

            if (!$aData['admin']) { # удаляем настройки предназначенные для админов
                unset($aData['im_noreply']);
            }

            $aGroupID = $aData['group_id'];

            do {

                # основный данные
                unset($aData['group_id']);
                $aData['member'] = in_array(self::GROUPID_MEMBER, $aGroupID) ? 1 : 0;
                if (!$this->errors->no('users.admin.user.submit', array('id'=>$nUserID,'data'=>&$aData,'groups'=>&$aGroupID))) {
                    break;
                }
                $this->model->userSave($nUserID, $aData);

                $sqlUpdate = array();

                # аватар
                $aAvatar = $this->avatar($nUserID)->uploadFILES('avatar', true, false);
                if ($aAvatar !== false && !empty($aAvatar['filename'])) {
                    $sqlUpdate['avatar'] = $aAvatar['filename'];
                    $aResponse['reload'] = true;
                } else {
                    if ($this->input->postget('avatar_del', TYPE_BOOL)) {
                        if ($this->avatar($nUserID)->delete(false)) {
                            $sqlUpdate['avatar'] = '';
                            $aResponse['reload'] = true;
                        }
                    }
                }

                # связь с группами
                if ($bUserSuperadmin && !in_array(self::GROUPID_SUPERADMIN, $aGroupID)) {
                    $aGroupID = array_merge($aGroupID, array(self::GROUPID_SUPERADMIN));
                }
                $this->model->userToGroups($nUserID, $aGroupID);

                # обновляем, является ли юзер администратором
                $bIsAdmin = 0;
                if ($this->errors->no()) {
                    if ($bUserSuperadmin || in_array(self::GROUPID_MODERATOR, $aGroupID)) {
                        $bIsAdmin = 1;
                    } elseif (count($aGroupID) == 1 && current($aGroupID) == self::GROUPID_MEMBER) {
                        $bIsAdmin = 0;
                    } else {
                        $aUserGroups = $this->model->userGroups($nUserID);
                        foreach ($aUserGroups as $v) {
                            if ($v['adminpanel'] == 1) {
                                $bIsAdmin = 1;
                                break;
                            }
                        }
                    }

                    if ($aData['admin'] != $bIsAdmin) {
                        $sqlUpdate['admin'] = $bIsAdmin;
                        if (!$bIsAdmin) {
                            $sqlUpdate['im_noreply'] = 0;
                        }
                    }
                }

                # сохраняем настройки магазина (если необходимо)
                if (bff::shopsEnabled() && $aData['shop_id']) {
                    # обновляем настройки магазина
                    $nShopID = $aData['shop_id'];
                    Shops::i()->validateShopData($nShopID, $aShopData);
                    if ($this->errors->no('users.admin.shop.submit',array('id'=>$nShopID,'data'=>&$aShopData,'user'=>$aData))) {
                        $mShopLogo = Shops::i()->shopLogo($nShopID)->onSubmit(false, 'shop_logo', 'shop_logo_del');
                        if ($mShopLogo !== false) {
                            $aShopData['logo'] = $mShopLogo;
                            $aResponse['reload'] = true;
                        }
                        Shops::model()->shopSave($nShopID, $aShopData);
                    }
                }

                if (!empty($sqlUpdate)) {
                    $this->model->userSave($nUserID, $sqlUpdate);
                }

                # если пользователь редактирует собственные настройки
                if ($this->security->isCurrentUser($nUserID)) {
                    $this->security->expire();
                }

                if ($this->input->post('back', TYPE_BOOL)) {
                    $aResponse['back'] = true;
                }

            } while (false);

            $this->iframeResponseForm($aResponse);
        }

        $aUserInfo = $this->model->userData($nUserID, '*', true);
        $aData = HTML::escape(array_merge($aUserInfo, $aData), 'html', array('name', 'site'));

        $aActiveGroupsID = array();
        for ($j = 0; $j < count($aUserGroups); $j++) {
            $aActiveGroupsID[] = $aUserGroups[$j]['group_id'];
        }
        $this->prepareGroupsOptions($aData, ($bUserSuperadmin ? null : USERS_GROUPS_SUPERADMIN), $aActiveGroupsID);

        # настройки магазина
        $aData['shops_on'] = bff::shopsEnabled();
        if ($aData['shops_on']) {
            $aData['shop_form'] = Shops::i()->formInfo($aData['shop_id']);
        }

        # услуга платного расширения лимитов
        if (BBS::limitsPayedEnabled()) {
            $aData['limits'] = BBS::i()->limitsPayedUser($nUserID, $aData['shop_id']);
        }

        $aData['superadmin'] = $bUserSuperadmin;
        $aData['tuid'] = $sTUID;
        $aData['social'] = $this->social()->getUserSocialAccountsData($nUserID, true);

        $aData['admin_auth_url'] = $this->adminAuthURL($aUserInfo['user_id'], $aUserInfo['last_login'], $aUserInfo['email']);

        return $this->viewPHP($aData, 'admin.user.form');
    }

    function user_action()
    {
        if (!$this->haveAccessTo('users-edit')) {
            return $this->showAccessDenied();
        }

        if (!($nUserID = $this->input->getpost('rec', TYPE_UINT))) {
            $this->adminRedirect(Errors::IMPOSSIBLE);
        }

        $sTUID = $this->input->getpost('tuid');
        if (!$this->checkTUID($sTUID, $nUserID)) {
            return $this->showAccessDenied();
        }

        if ($this->model->userIsSuperAdmin($nUserID)) {
            $this->adminRedirect(Errors::ACCESSDENIED);
        }

        switch ($this->input->get('type')) {
            case 'delete': # удаление пользователя [нереализовано]
            {
                $this->adminRedirect(Errors::IMPOSSIBLE);

                # аватар
                $this->avatar($nUserID)->delete(false);
                # данные
                $this->model->deleteUser($nUserID);
            }
            break;
            case 'logout': # завершение сессии
            {

                $this->userSessionDestroy($nUserID, false); # frontend
                if (!$this->security->isCurrentUser($nUserID)) {
                    $this->userSessionDestroy($nUserID, true); # admin panel
                }

                $this->adminRedirect(Errors::SUCCESS, "user_edit&rec=$nUserID&tuid=$sTUID");

            }
            break;
        }

        $this->adminRedirect(Errors::SUCCESS);
    }

    public function ajax()
    {
        $nUserID = $this->input->getpost('id', TYPE_UINT);
        if (!$nUserID) {
            $this->ajaxResponse(Errors::UNKNOWNRECORD);
        }

        $action = $this->input->getpost('act', TYPE_STR);
        switch ($action) {
            case 'user-info': # popup
            {
                if (!$this->haveAccessTo('members-listing')) {
                    echo $this->showAccessDenied();
                    exit;
                }

                $aData = $this->model->userData($nUserID, '*');
                $aData['shops_on'] = bff::shopsEnabled();
                if ($aData['shops_on'] && $aData['shop_id'] > 0) {
                    $aData['shop'] = Shops::model()->shopData($aData['shop_id'], array('title', 'link'));
                }

                $aData['region_title'] = Geo::regionTitle($aData['region_id']);
                $aData['tuid'] = $this->makeTUID($nUserID);
                $aData['im_form'] = bff::moduleExists('internalmail');
                $aData['social'] = $this->social()->getUserSocialAccountsData($nUserID, true);

                echo $this->viewPHP($aData, 'admin.user.info');
                exit;
            }
            break;
            case 'user-block': # блокировка / разблокировка пользователя
            {
                if (!$this->haveAccessTo('users-edit')) {
                    $this->ajaxResponse(Errors::ACCESSDENIED);
                }
                if ($this->security->isCurrentUser($nUserID)) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $aData = $this->model->userData($nUserID, array('blocked', 'activated', 'email', 'name', 'session_id', 'lang'));
                if (empty($aData)) {
                    $this->ajaxResponse(Errors::UNKNOWNRECORD);
                }

                $sBlockedReason = $this->input->postget('blocked_reason', TYPE_STR, array('len' => 1000));
                $bBlocked = $this->input->postget('blocked', TYPE_BOOL);

                $aUpdate = array();
                if (!$bBlocked) {
                    $aUpdate['blocked'] = 0;
                } else {
                    $aUpdate['blocked'] = 1;
                    $aUpdate['blocked_reason'] = $sBlockedReason;
                }

                $bSaved = $this->model->userSave($nUserID, $aUpdate);
                if ($bSaved) {
                    if ($aUpdate['blocked'] != $aData['blocked']) {
                        # Триггер блокировки/разблокировки аккаунта
                        bff::i()->callModules('onUserBlocked', array($nUserID, $aUpdate['blocked']));

                        if ($aUpdate['blocked'] === 1) {
                            # аккаунт заблокирован
                            bff::sendMailTemplate(
                                array(
                                    'name'           => $aData['name'],
                                    'email'          => $aData['email'],
                                    'user_id'        => $nUserID,
                                    'blocked_reason' => $sBlockedReason,
                                ),
                                'users_blocked', $aData['email'], false, '', '', $aData['lang']
                            );

                            # убиваем сессию пользователя (если авторизован)
                            $this->security->impersonalizeSession($aData['session_id'], null, true, false);
                        } else {
                            # аккаунт разблокирован
                            bff::sendMailTemplate(
                                array(
                                    'name'    => $aData['name'],
                                    'email'   => $aData['email'],
                                    'user_id' => $nUserID,
                                ),
                                'users_unblocked', $aData['email'], false, '', '', $aData['lang']
                            );
                        }
                    }
                }

                $this->ajaxResponse(array('reason' => nl2br($sBlockedReason), 'blocked' => $aUpdate['blocked']));
            }
            break;
            case 'user-activate': # активация пользователя
            {
                do {
                    if (!$this->haveAccessTo('users-edit')) {
                        $this->errors->accessDenied();
                        break;
                    }

                    $aData = $this->model->userData($nUserID, array('user_id', 'activated', 'blocked'));
                    if (empty($aData)) {
                        $this->errors->impossible();
                        break;
                    } else {
                        if ($aData['activated'] == 1) {
                            $this->errors->set(_t('users', 'Данный аккаунт уже активирован'));
                            break;
                        } else {
                            if ($aData['blocked'] == 1) {
                                $this->errors->set(_t('users', 'Невозможно активировать заблокированный аккаунт'));
                                break;
                            }
                        }
                    }

                    $bSuccess = $this->model->userSave($nUserID, array(
                            'phone_number_verified' => 1,
                            'activate_key' => '',
                            'activated'    => 1
                        )
                    );

                    if (!empty($bSuccess)) {
                        # триггер активации аккаунта
                        $this->triggerOnUserActivated($nUserID, array(
                            'context' => 'user-activate-admin',
                            'currentUserID' => User::id(),
                        ));
                    }
                } while (false);

                $this->ajaxResponseForm();
            }
            break;
            case 'user-admin-comment': # комментарий о пользователе
            {
                $response = array('comment'=>'');
                do {
                    if (!$this->haveAccessTo('users-edit')) {
                        $this->errors->accessDenied();
                        break;
                    }

                    $aData = $this->model->userData($nUserID, array('user_id', 'admin_comment'));
                    if (empty($aData)) {
                        $this->errors->impossible();
                        break;
                    } else {
                        $response['comment'] = $this->input->post('admin_comment', TYPE_TEXT, ['len'=>5000]);
                        $this->model->userSave($nUserID, array(
                            'admin_comment' => $response['comment'],
                        ));
                    }
                } while (false);
                $response['comment'] = nl2br($response['comment']);
                $this->ajaxResponseForm($response);
            }
            break;
            case 'delete-unactivated': # пакетное удаление неактивированных пользователей
            {
                $aResponse = array();
                do{

                    if (!$this->haveAccessTo('users-edit')) {
                        $this->errors->accessDenied();
                        break;
                    }


                    $aData = $this->input->postm(array(
                        'mode' => TYPE_STR,
                        'i'    => TYPE_ARRAY_INT,
                    ));

                    if (!in_array($aData['mode'], array('checked', 'all'))) {
                        $this->errors->reloadPage();
                        break;
                    }

                    if ($aData['mode'] == 'checked' && empty($aData['i'])) {
                        $this->errors->set(_t('users', 'Выберите пользователей'));
                        break;
                    }

                    $aDeleted = $this->model->deleteUnactivated(($aData['mode'] == 'all'), $aData['i']);

                    $aResponse['msg'] = _t('users', 'Удалено: [cnt].', array('cnt' => tpl::declension(count($aDeleted), 'пользователь;пользователя;пользователей')));
                    $aResponse['deleted'] = $aDeleted;

                } while(false);
                $this->ajaxResponseForm($aResponse);
            }
            break;
            default:
            {
                bff::hook('users.admin.ajax.default.action', $action, $this);
            }
        }

        $this->ajaxResponse(Errors::IMPOSSIBLE);
    }

    ### non-actions

    /**
     * Валидация пользователя
     * @param array @ref $aData данные
     * @param integer $nUserID ID пользователя
     */
    protected function validateUserData(&$aData, $nUserID = 0)
    {
        $aParams = array(
            'name'      => array(TYPE_NOTAGS, 'len' => 50, 'len.sys' => 'users.form.name.limit'),
            'surname'   => TYPE_NOTAGS,
            'email'     => TYPE_NOTAGS,
            'login'     => TYPE_NOTAGS,
            'region_id' => TYPE_UINT,
            'addr_addr' => array(TYPE_NOTAGS, 'len' => 400, 'len.sys' => 'users.form.addr.limit'),
            'shop_id'   => TYPE_UINT, # ID магазина
            'sex'       => TYPE_UINT,
            'contacts'  => TYPE_ARRAY_NOTAGS,
            'phones'    => TYPE_ARRAY_NOTAGS,
            'site'      => TYPE_NOTAGS,
            'about'     => TYPE_NOTAGS,
            'group_id'  => TYPE_ARRAY_UINT,
            'enotify'   => TYPE_ARRAY_UINT,
        );

        if ($nUserID) {
            $aParams['changepass'] = TYPE_BOOL;
            $aParams['password'] = TYPE_NOTRIM;
            $aParams['im_noreply'] = TYPE_BOOL;
        } else {
            $aParams['password'] = TYPE_NOTRIM;
            $aParams['password2'] = TYPE_NOTRIM;
        }

        $usePhone = static::registerPhone();
        if ($usePhone) {
            $aParams['phone_number'] = TYPE_NOTAGS;
        }

        if ($this->profileBirthdate) {
            $aParams['birthdate'] = TYPE_ARRAY_UINT;
        }

        $this->input->postm($aParams, $aData);

        if (!$nUserID) {
            $aData['admin'] = 0;
        }

        if (Request::isPOST()) {
            # номер телефона
            if ($usePhone && (!empty($aData['phone_number']) || !$nUserID)) {
                if (!$this->input->isPhoneNumber($aData['phone_number'])) {
                    $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone_number');
                } elseif ($this->model->userPhoneExists($aData['phone_number'], $nUserID)) {
                    $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован'), 'phone_number');
                }
            }

            # email (для авторизации)
            if (!$this->input->isEmail($aData['email'])) {
                $this->errors->set(_t('users', 'Email указан некорректно'), 'email');
            } elseif ($this->model->userEmailExists($aData['email'], $nUserID)) {
                $this->errors->set(_t('users', 'Указанный email уже существует'), 'email');
            }

            # login
            if (!$this->isLoginCorrect($aData['login'])) {
                $this->errors->set(_t('users', 'Укажите корректный логин'), 'login');
            } elseif ($this->model->userLoginExists($aData['login'], $nUserID)) {
                $this->errors->set(_t('users', 'Указанный логин уже существует'), 'login');
            }

            if ($nUserID) {
                if ($aData['changepass']) {
                    if (strlen($aData['password']) < $this->passwordMinLength) {
                        $this->errors->set(_t('users', 'Новый пароль слишком короткий'), 'password');
                    } else {
                        $aDataCurrent = $this->model->userData($nUserID, array('password_salt'));
                        $aData['password'] = $this->security->getUserPasswordMD5($aData['password'], $aDataCurrent['password_salt']);
                    }
                } else {
                    unset($aData['password']);
                }
                unset($aData['changepass']);
            } else {
                if (strlen($aData['password']) < $this->passwordMinLength) {
                    $this->errors->set(_t('users', 'Пароль слишком короткий'), 'password');
                } elseif ($aData['password'] != $aData['password2']) {
                    $this->errors->set(_t('users', 'Ошибка подтверждения пароля'), 'password');
                }
                unset($aData['password2']);
            }

            # пол
            if (!in_array($aData['sex'], array(self::SEX_MALE, self::SEX_FEMALE))) {
                $aData['sex'] = self::SEX_MALE;
            }

            # уведомления
            $aData['enotify'] = array_sum($aData['enotify']);

            if ($aData['region_id']) {
                # разворачиваем данные о регионе: region_id => reg1_country, reg2_region, reg3_city
                $aRegions = Geo::model()->regionParents($aData['region_id']);
                $aData = array_merge($aData, $aRegions['db']);
            } else {
                $aData['reg1_country'] = 0;
                $aData['reg2_region'] = 0;
                $aData['reg3_city'] = 0;
            }

            $this->cleanUserData($aData);
        }
    }

    protected function prepareGroupsOptions(&$aData, $mExceptGroupKeyword, $aActiveGroupsID = array())
    {
        $exists_options = '';
        $active_options = '';
        $aGroups = $this->model->groups($mExceptGroupKeyword, false);
        for ($i = 0; $i < count($aGroups); $i++) {
            if (in_array($aGroups[$i]['group_id'], $aActiveGroupsID)) {
                $active_options .= '<option value="' . $aGroups[$i]['group_id'] . '" style="color:' . $aGroups[$i]['color'] . ';">' . $aGroups[$i]['title'] . '</option>';
            } else {
                $exists_options .= '<option value="' . $aGroups[$i]['group_id'] . '" style="color:' . $aGroups[$i]['color'] . ';">' . $aGroups[$i]['title'] . '</option>';
            }
        }
        $aData['exists_options'] = $exists_options;
        $aData['active_options'] = $active_options;
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }

}