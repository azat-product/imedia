<?php

# Класс пользователей

class Users_ extends UsersBase
{

    public function authPage($sTemplate, $sTitle = '', array $aData = array())
    {
        tpl::includeJS('users.auth', false, 4);
        if(empty($aData['back'])){
            $aData['back'] = Request::referer(static::urlBase());
        }
        return $this->showShortPage($sTitle, $this->viewPHP($aData, $sTemplate));
    }

    /**
     * Авторизация
     */
    public function login()
    {
        switch ($this->input->getpost('step', TYPE_STR))
        {
            case 'resend-activation': # Повторная отправка письма "активации"
            {
                $aResponse = array();
                do {
                    $aData = $this->input->postgetm(array(
                        'email' => array(TYPE_NOTAGS, 'len' => 100), # E-mail
                        'pass'  => TYPE_NOTRIM, # Пароль (в открытом виде)
                    ));
                    extract($aData);

                    if (!$this->security->validateReferer()) {
                        $this->errors->reloadPage(); break;
                    }
                    # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                    if (Site::i()->preventSpam('users-login-resend-activation', 5, false)) {
                        $this->errors->set(_t('', 'Повторите попытку через несколько секунд'));
                        break;
                    }
                    # уже авторизован
                    if (User::id()) {
                        $this->errors->reloadPage();
                        break;
                    }
                    # проверяем email
                    if (!$this->input->isEmail($email)) {
                        $this->errors->set(_t('users', 'E-mail адрес указан некорректно'), 'email');
                        break;
                    }
                    $userData = $this->model->userDataByFilter(array('email'=>$email),
                        array('user_id', 'email', 'name', 'password', 'password_salt', 'activated', 'activate_key', 'phone_number'));
                    if (empty($userData) || $userData['activated']) {
                        $this->errors->reloadPage();
                        break;
                    }
                    # телефон
                    if (static::registerPhone() && !empty($userData['phone_number'])) {
                        $this->errors->reloadPage();
                        break;
                    }
                    # проверяем пароль
                    if ($userData['password'] != $this->security->getUserPasswordMD5($pass, $userData['password_salt'])) {
                        $this->errors->set(_t('users', 'E-mail или пароль указаны некорректно'), 'email');
                        break;
                    }
                    # генерируем ссылку активации
                    $activationData = $this->updateActivationKey($userData['user_id'], $userData['activate_key']);
                    if ( ! $activationData) {
                        $this->errors->reloadPage();
                        break;
                    }

                    # отправляем письмо для активации аккаунта
                    $mailData = array(
                        'id'            => $userData['user_id'],
                        'user_id'       => $userData['user_id'],
                        'name'          => $userData['name'],
                        'password'      => $pass,
                        'email'         => $email,
                        'activate_link' => $activationData['link']
                    );
                    bff::sendMailTemplate($mailData, 'users_register', $email);

                    # сохраняем данные для повторной отправки письма
                    $this->security->sessionStart();
                    $this->security->setSESSION('users-register-data', $mailData);

                    $aResponse['redirect'] = static::url('register', array('step'=>'emailed', 'resend'=>1));
                } while(false);
                $this->ajaxResponseForm($aResponse);
            }
            break;
        }

        if (Request::isPOST())
        {
            $aData = $this->input->postm(array(
                'email'    => array(TYPE_NOTAGS, 'len' => 100), # E-mail
                'pass'     => TYPE_NOTRIM, # Пароль
                'social'   => TYPE_BOOL,   # Авторизация через соц. сеть
                'remember' => TYPE_BOOL,   # Запомнить меня
                'back'     => TYPE_NOTAGS, # Ссылка возврата
            ));
            extract($aData);

            $aResponse = array('success' => false, 'status' => 0);
            do {
                # уже авторизован
                if (User::id()) {
                    $aResponse['success'] = true;
                    break;
                }

                # проверяем корректность email
                if (!$this->input->isEmail($email)) {
                    $this->errors->set(_t('users', 'E-mail адрес указан некорректно'), 'email');
                    break;
                }

                # при авторизации проверяем блокировку по IP
                if ($mBlocked = $this->checkBan(true)) {
                    $this->errors->set(_t('users', 'Доступ заблокирован по причине: [reason]', array('reason' => $mBlocked)));
                    break;
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('users-login', rand(3,6), false)) {
                    $this->errors->set(_t('', 'Повторите попытку через несколько секунд'));
                    break;
                }

                $mResult = $this->userAuth($email, 'email', $pass, false, true);
                if ($mResult === true) {
                    $nUserID = User::id();

                    # привязываем соц. аккаунт
                    if ($social) {
                        $this->social()->authFinish($nUserID);
                    }

                    # пересохраняем избранные ОБ из куков в базу
                    BBS::i()->saveFavoritesToDB($nUserID);

                    $aResponse['status'] = 2; # успешная авторизация
                    $aResponse['success'] = true;
                    if (static::loginRemember() && $remember) {
                        $days = config::sysAdmin('users.login.remember.days', 30, TYPE_UINT);
                        $this->security->setRememberMe($this->security->getUserLogin(), $this->security->getUserPasswordMD5($pass), $days);
                    }
                } elseif ($mResult === 1) {
                    $aResponse['status'] = 1; # необходимо активировать аккаунт
                    $userData = config::get('__users_preactivate_data');
                    $userData = $this->model->userData($userData['id'], array('user_id as id','email','name','activate_key','phone_number'));
                    if (static::registerPhone() && !empty($userData['phone_number'])) {
                        if (empty($userData['activate_key'])) {
                            # Обновляем ключ активации
                            $activationData = $this->updateActivationKey($userData['id']);
                            if (!$activationData) {
                                $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                                break;
                            } else {
                                $userData['activate_key'] = $activationData['key'];
                            }
                        }

                        # Отправляем SMS с кодом активации для подтверждения номера телефона
                        $this->sms(false)->sendActivationCode($userData['phone_number'], $userData['activate_key']);
                        $this->security->sessionStart();
                        $this->security->setSESSION('users-register-data', array(
                            'id'            => $userData['id'],
                            'name'          => $userData['name'],
                            'password'      => $pass,
                            'phone'         => $userData['phone_number'],
                            'email'         => $email,
                        ));

                        $this->errors->set(_t('users', 'Данный аккаунт неактивирован. <a [link_activate]>Активировать</a>',
                            array('link_activate'=>'href="'.static::url('register', array('step' => 'phone')).'"')));
                    } else {
                        $this->errors->set(_t('users', 'Данный аккаунт неактивирован, перейдите по ссылке отправленной вам в письме.<br /><a [link_resend]>Получить письмо повторно</a>',
                            array('link_resend'=>'href="javascript:void(0);" class="ajax j-resend-activation"')));
                    }
                    break;
                }

            } while (false);

            if ($aResponse['success']) {
                if (empty($back)
                    || strpos($back, '/user/register') !== false
                    || strpos($back, '/user/login') !== false
                    || ! $this->security->validateReferer($back)
                ) {
                    $back = bff::urlBase();
                }
                $aResponse['redirect'] = $back;
            }

            $this->ajaxResponseForm($aResponse);
        } else {
            if (User::id()) {
                $this->redirectToCabinet();
            }
        }

        # SEO: Авторизация
        $aData = array();
        $this->urlCorrection(static::url('login'));
        $this->setMeta('login', array(), $aData);

        $data = array(
            'providers' => $this->social()->getProvidersEnabled(),
            'back'      => $this->input->get('ref', TYPE_STR)
        );
        return $this->authPage('auth.login', (!empty($aData['titleh1']) ? $aData['titleh1'] : _t('users', 'Войдите на сайт с помощью электронной почты или через социальную сеть')), $data);
    }

    /**
     * Авторизация через соц. сети
     */
    public function loginSocial()
    {
        $this->social()->auth($this->input->get('provider', TYPE_NOTAGS));
    }

    /**
     * Авторизация на фронтенде из админки
     */
    public function login_admin()
    {
        $userID = $this->input->get('uid', TYPE_UINT);
        do {
            if (!$this->security->validateReferer() || !$userID || $this->model->userIsAdministrator($userID)) break;

            $userData = $this->model->userData($userID, array('last_login','email','password'));
            if (empty($userData)) break;

            $hash = $this->input->get('hash', TYPE_STR);
            if (empty($hash)) break;
            if ($hash != $this->adminAuthURL($userID, $userData['last_login'], $userData['email'], true)) break;

            if ($this->userAuth($userID, 'user_id', $userData['password']) !== true) break;

            $this->redirect(bff::urlBase());
        } while (false);

        $this->errors->error404();
    }

    /**
     * Атоматическая авторизация пользователя по GET параметру "alogin"
     * @return int ID пользователя или 0
     */
    public function loginAuto()
    {
        $userID = User::id();
        if ($userID) return $userID;

        $hashFull = $this->input->get('alogin', TYPE_STR);
        if (empty($hashFull)) return 0;

        list($userID, $hash) = explode('.', $hashFull);

        do{
            $userData = $this->model->userData($userID, array('user_id', 'user_id_ex', 'last_login', 'password'));
            if (empty($userData) ||
                $this->model->userIsAdministrator($userID) ||
                $hashFull !== static::loginAutoHash($userData)) {
                break;
            }
            if ($this->userAuth($userID, 'user_id', $userData['password']) === true) {
                $this->security->userCounter(null);
                return $userID;
            }
        } while(false);

        $this->redirect(static::url('login', array('ref' => Request::url(true))));

        return 0;
    }

    /**
     * Регистрация
     */
    public function register()
    {
        $this->setMeta('register');

        switch ($this->input->getpost('step', TYPE_STR))
        {
            case 'phone': # Подтверждение номера телефона
            {
                if (User::id()) {
                    $this->redirectToCabinet();
                }
                $registerData = $this->security->getSESSION('users-register-data');
                if (empty($registerData['id']) || empty($registerData['phone'])) {
                    $this->errors->error404();
                }
                $userID = $registerData['id'];
                $userPhone = $registerData['phone'];
                $userData = $this->model->userData($userID, array('password','activated','activate_key','blocked','blocked_reason'));
                if (empty($userData) || $userData['blocked']) {
                    $this->errors->error404();
                }
                if ($userData['activated']) {
                    $this->userAuth($userID, 'user_id', $userData['password']);
                    $this->redirectToCabinet();
                }

                if (Request::isPOST())
                {
                    $act = $this->input->postget('act');
                    $response = array();
                    if (!$this->security->validateReferer() || !static::registerPhone()) {
                        $this->errors->reloadPage(); $act = '';
                    }
                    switch ($act)
                    {
                        # Проверка кода подтверждения
                        case 'code-validate':
                        {
                            $code = $this->input->postget('code', TYPE_NOTAGS);
                            if (mb_strtolower($code) !== $userData['activate_key']) {
                                $this->errors->set(_t('users', 'Код подтверждения указан некорректно'), 'phone');
                                break;
                            }
                            # Активируем аккаунт
                            $res = $this->model->userSave($userID, array('phone_number_verified'=>1, 'activated' => 1, 'activate_key' => ''));
                            if ($res) {
                                $this->triggerOnUserActivated($userID, array(
                                    'context' => 'user-register-sms',
                                ));
                                # Авторизуем
                                $this->userAuth($userID, 'user_id', $userData['password']);
                                # Отправляем письмо об успешной регистрации
                                bff::sendMailTemplate($registerData, 'users_register_phone', $registerData['email']);
                                $this->security->setSESSION('users-register-data', null);
                                $response['redirect'] = static::url('register', array('step' => 'finished'));
                            } else {
                                bff::log('users: Ошибка активации аккаунта пользователя по коду подтверждения [user-id="'.$userID.'"]');
                                $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                                break;
                            }
                        } break;
                        # Повторная отправка кода подтверждения
                        case 'code-resend':
                        {
                            $activationData = $this->getActivationInfo();

                            $res = $this->sms()->sendActivationCode($userPhone, $activationData['key']);
                            if ($res) {
                                $activationData = $this->updateActivationKey($userID, $activationData['key']);
                                if (!$activationData) {
                                    $this->errors->reloadPage();
                                    break;
                                }
                            }
                        } break;
                        # Смена номера телефона
                        case 'phone-change':
                        {
                            $phone = $this->input->postget('phone', TYPE_NOTAGS, array('len'=>30));
                            if (!$this->input->isPhoneNumber($phone)) {
                                $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                                break;
                            }
                            if ($phone === $userPhone) {
                                break;
                            }
                            if ($this->model->userPhoneExists($phone, $userID)) {
                                $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован. <a [link_forgot]>Забыли пароль?</a>',
                                        array('link_forgot' => 'href="' . static::url('forgot') . '"')
                                    ), 'phone'
                                );
                                break;
                            }
                            $res = $this->model->userSave($userID, array(
                                'phone_number' => $phone,
                                'phone_number_verified' => 0,
                            ));
                            if (!$res) {
                                bff::log('users: Ошибка обновления номера телефона [user-id="'.$userID.'"]');
                                $this->errors->reloadPage();
                            } else {
                                $registerData['phone'] = $phone;
                                $response['phone'] = '+'.$phone;
                                $this->sms()->sendActivationCode($phone, $userData['activate_key']);
                                $this->security->setSESSION('users-register-data', $registerData);
                            }
                        } break;
                    }

                    $this->ajaxResponseForm($response);
                }

                //
                return $this->authPage('auth.register.phone', _t('users', 'Подтверждение номера мобильного телефона'), $registerData);
            } break;
            case 'emailed': # Уведомление о письме "активации"
            {
                if (User::id()) {
                    $this->redirectToCabinet();
                }
                $aData = $this->security->getSESSION('users-register-data');
                if (!empty($aData['id'])) {
                    $aUser = $this->model->userData($aData['id'], array('activated', 'blocked','phone_number'));
                    if (empty($aUser) || $aUser['activated'] || $aUser['blocked']) {
                        $aData = false;
                    }
                }
                if (static::registerPhone() && !empty($aUser['phone_number'])) {
                    $this->errors->error404();
                }
                if (Request::isPOST()) {
                    if (!$this->security->validateReferer()) {
                        $this->errors->reloadPage();
                    } else {
                        if (!User::id() && !empty($aData)) {
                            # Повторная отправка письма об успешной регистрации
                            bff::sendMailTemplate($aData, 'users_register', $aData['email']);
                            $this->security->setSESSION('users-register-data', null);
                        }
                    }
                    $this->ajaxResponseForm();
                }

                $bResend = $this->input->get('resend', TYPE_BOOL);
                $sTitle = ( $bResend ? _t('users', 'Письмо отправлено') : _t('users', 'Регистрация завершена') );
                return $this->authPage('auth.register.emailed', $sTitle, array('retry_allowed' => !empty($aData)));
            }
            break;
            case 'social': # Регистрация через аккаунт в соц. сети
            {
                if (User::id()) {
                    if (Request::isPOST()) {
                        $this->errors->reloadPage();
                        $this->ajaxResponseForm();
                    }
                    $this->redirectToCabinet();
                }

                $aSocialData = $this->social()->authData();

                if (Request::isPOST()) {
                    $aResponse = array('exists' => false);
                    $p = $this->input->postm(array(
                            'email'     => array(TYPE_NOTAGS, 'len' => 100), # E-mail
                            'agreement' => TYPE_BOOL, # Пользовательское соглашение
                        )
                    );
                    extract($p);
                    do {
                        if (!$this->security->validateReferer() || empty($aSocialData)) {
                            $this->errors->reloadPage();
                            break;
                        }
                        if (!$this->input->isEmail($email)) {
                            $this->errors->set(_t('users', 'E-mail адрес указан некорректно'), 'email');
                            break;
                        }
                        if (Users::isEmailTemporary($email)){
                            $this->errors->set(_t('', 'Указанный вами email адрес находится в списке запрещенных, используйте например @gmail.com'), 'email');
                            break;
                        }
                        if ($mBanned = $this->checkBan(true, $email)) {
                            $this->errors->set(_t('users', 'Доступ заблокирован по причине: [reason]', array('reason' => $mBanned)));
                            break;
                        }
                        if ($this->model->userEmailExists($email)) {
                            $aResponse['exists'] = true;
                            break;
                        }
                        if (!$agreement) {
                            $this->errors->set(_t('users', 'Пожалуйста подтвердите, что Вы согласны с пользовательским соглашением'), 'agreement');
                        }
                        if (!$this->errors->no('users.register.social.submit',array('data'=>&$aSocialData,'email'=>$email))) {
                            break;
                        }

                        # Создаем аккаунт пользователя + генерируем пароль
                        $aUserData = $this->userRegister(array(
                                'name'  => $aSocialData['name'], # ФИО из соц. сети
                                'email' => $email, # E-mail
                            )
                        );
                        if (empty($aUserData)) {
                            $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                            break;
                        }
                        $nUserID = $aUserData['user_id'];

                        # Загружаем аватар из соц. сети
                        if (!empty($aSocialData['avatar'])) {
                            $this->avatar($nUserID)->uploadSocial($aSocialData['provider_id'], $aSocialData['avatar'], true);
                        }

                        # Закрепляем соц. аккаунт за пользователем
                        $this->social()->authFinish($nUserID);

                        # Активируем аккаунт пользователя без подтверждения email адреса
                        if (!config::sysAdmin('users.register.social.email.activation', true, TYPE_BOOL))
                        {
                            $res = $this->model->userSave($nUserID, array(
                                'activated' => 1
                            ));
                            if (!$res) {
                                $this->errors->reloadPage(); break;
                            }

                            $res = $this->userAuth($nUserID, 'user_id', $aUserData['password'], false);
                            if ($res!==true) {
                                $this->errors->reloadPage(); break;
                            }

                            # Отправляем письмо об успешной регистрации
                            $aMailData = array(
                                'name'     => $aSocialData['name'],
                                'password' => $aUserData['password'],
                                'email'    => $email,
                                'user_id'  => $nUserID,
                            );
                            bff::sendMailTemplate($aMailData, 'users_register_auto', $email);

                            $aResponse['success'] = true;
                            $aResponse['redirect'] = static::url('register', array('step' => 'finished'));
                            break;
                        }

                        # Отправляем письмо для активации аккаунта
                        $aMailData = array(
                            'id'            => $nUserID,
                            'user_id'       => $nUserID,
                            'name'          => $aSocialData['name'],
                            'password'      => $aUserData['password'],
                            'email'         => $email,
                            'activate_link' => $aUserData['activate_link']
                        );
                        bff::sendMailTemplate($aMailData, 'users_register', $email);

                        # Сохраняем данные для повторной отправки письма
                        $this->security->sessionStart();
                        $this->security->setSESSION('users-register-data', $aMailData);

                        $aResponse['success'] = true;
                        $aResponse['redirect'] = static::url('register', array('step' => 'emailed')); # url результирующей страницы

                    } while (false);

                    $this->ajaxResponseForm($aResponse);
                }

                # Данные о процессе регистрации через соц.сеть некорректны, причины:
                # 1) неудалось сохранить в сессии
                # 2) повторная попытка, вслед за успешной (случайный переход по ссылке)
                if (empty($aSocialData)) {
                    $this->redirect(static::url('register'));
                }

                # Аватар по-умолчанию
                if (empty($aSocialData['avatar'])) {
                    $aSocialData['avatar'] = UsersAvatar::url(0, '', UsersAvatar::szNormal);
                }

                return $this->authPage('auth.register.social', _t('users', 'Для завершения регистрации введите Вашу электронную почту'), $aSocialData);
            }
            break;
            case 'finished': # Страница успешной регистрации
            {
                return $this->authPage('auth.message', _t('users', 'Вы успешно зарегистрировались!'), array(
                        'message' => _t('users', 'Теперь вы можете <a [link_home]>перейти на главную страницу</a> или <a [link_profile]>в настройки своего профиля</a>.',
                            array(
                                'link_home'    => 'href="' . bff::urlBase() . '"',
                                'link_profile' => 'href="' . static::url('my.settings') . '"'
                            )
                        )
                    )
                );
            }
            break;
        }

        $bPhone = static::registerPhone(); # задействовать: номер телефона
        $bCaptcha = config::sysAdmin('users.register.captcha', false, TYPE_BOOL); # задействовать: капчу
        $bPasswordConfirm = config::sysAdmin('users.register.passconfirm', true, TYPE_BOOL); # задействовать: подтверждение пароля

        if (Request::isPOST()) {
            $aResponse = array('captcha' => false);

            if (User::id()) {
                $this->ajaxResponseForm($aResponse);
            }

            $aData = $this->input->postm(array(
                    'phone'     => array(TYPE_NOTAGS, 'len' => 30), # Номер телефона
                    'email'     => array(TYPE_NOTAGS, 'len' => 100), # E-mail
                    'pass'      => TYPE_NOTRIM, # Пароль
                    'pass2'     => TYPE_NOTRIM, # Подтверждение пароля
                    'back'      => TYPE_NOTAGS, # Ссылка возврата
                    'captcha'   => TYPE_STR, # Капча
                    'agreement' => TYPE_BOOL, # Пользовательское соглашение
                )
            );
            extract($aData);

            $aResponse['back'] = $back;

            do {
                if (!$this->security->validateReferer()) {
                    $this->errors->reloadPage();
                    break;
                }

                if ($bPhone && ! $this->input->isPhoneNumber($phone)) {
                    $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                    break;
                }

                if (!$this->input->isEmail($email)) {
                    $this->errors->set(_t('users', 'E-mail адрес указан некорректно'), 'email');
                    break;
                }

                if (Users::isEmailTemporary($email)){
                    $this->errors->set(_t('', 'Указанный вами email адрес находится в списке запрещенных, используйте например @gmail.com'), 'email');
                    break;
                }

                if ($mBanned = $this->checkBan(true, $email)) {
                    $this->errors->set(_t('users', 'Доступ заблокирован по причине: [reason]', array('reason' => $mBanned)));
                    break;
                }

                if ($bPhone && $this->model->userPhoneExists($phone)) {
                    $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован. <a [link_forgot]>Забыли пароль?</a>',
                            array('link_forgot' => 'href="' . static::url('forgot') . '"')
                        ), 'phone'
                    );
                    break;
                }

                if ($this->model->userEmailExists($email)) {
                    $this->errors->set(_t('users', 'Пользователь с таким e-mail адресом уже зарегистрирован. <a [link_forgot]>Забыли пароль?</a>',
                            array('link_forgot' => 'href="' . static::url('forgot') . '"')
                        ), 'email'
                    );
                    break;
                }

                if (empty($pass)) {
                    $this->errors->set(_t('users', 'Укажите пароль'), 'pass');
                } elseif (mb_strlen($pass) < $this->passwordMinLength) {
                    $this->errors->set(_t('users', 'Пароль не должен быть короче [min] символов', array('min' => $this->passwordMinLength)), 'pass');
                } else {
                    if ($bPasswordConfirm && $pass != $pass2) {
                        $this->errors->set(_t('users', 'Подтверждение пароля указано неверно'), 'pass2');
                    }
                }

                if ($bCaptcha) {
                    if (Site::captchaCustom('users-auth-register')) {
                        bff::hook('captcha.custom.check');
                    } else {
                        if (empty($captcha) || !CCaptchaProtection::isCorrect($captcha)) {
                            $this->errors->set(_t('users', 'Результат с картинки указан некорректно'), 'captcha');
                            $aResponse['captcha'] = true;
                            CCaptchaProtection::reset();
                        }
                    }
                }

                if (!$agreement) {
                    $this->errors->set(_t('users', 'Пожалуйста подтвердите, что Вы согласны с пользовательским соглашением'), 'agreement');
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if ($this->errors->no()) {
                    Site::i()->preventSpam('users-register', 30);
                }

                if (!$this->errors->no('users.register.submit',array('data'=>&$aData))) {
                    break;
                }

                # Создаем аккаунт пользователя
                $aUserData = array(
                    'email'    => $email,
                    'password' => $pass,
                );
                if ($bPhone) {
                    $aUserData['phone_number'] = $phone;
                }
                $aUserData = $this->userRegister($aUserData);
                if (empty($aUserData)) {
                    $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                    break;
                }

                $aResponse['success'] = true;
                $aResponse['redirect'] = static::url('register', array('step' => ($bPhone?'phone':'emailed'))); # url результирующей страницы

                # Отправляем письмо для активации аккаунта
                $aMailData = array(
                    'id'            => $aUserData['user_id'],
                    'user_id'       => $aUserData['user_id'],
                    'name'          => '',
                    'password'      => $pass,
                    'phone'         => $phone,
                    'email'         => $email,
                    'activate_link' => $aUserData['activate_link']
                );
                if ($bPhone) {
                    # Отправляем SMS с кодом активации для подтверждения номера телефона
                    # Письмо отправим после успешного подтверждения
                    $this->sms(false)->sendActivationCode($phone, $aUserData['activate_key']);
                } else {
                    bff::sendMailTemplate($aMailData, 'users_register', $email);
                }

                # Сохраняем данные для повторной отправки письма
                $this->security->sessionStart();
                $this->security->setSESSION('users-register-data', $aMailData);

            } while (false);

            $this->ajaxResponseForm($aResponse);
        } else {
            if (User::id()) {
                $this->redirectToCabinet();
            }
        }

        # seo
        $this->urlCorrection(static::url('register'));

        return $this->authPage('auth.register', _t('users', 'Зарегистрируйтесь на сайте с помощью электронной почты или через социальную сеть'), array(
                'phone_on'        => $bPhone,
                'captcha_on'      => $bCaptcha,
                'pass_confirm_on' => $bPasswordConfirm,
                'providers'       => $this->social()->getProvidersEnabled()
            )
        );
    }

    /**
     * Активация пользователя
     */
    public function activate()
    {
        if (User::id()) {
            $this->redirectToCabinet();
        }

        # ключ активации
        $sKey = $this->input->get('key', TYPE_NOTAGS);

        # ключ переписки
        $bMessageRedirect = false;
        $sMessageKey = $this->input->get('msg', TYPE_NOTAGS);
        if (!empty($sMessageKey)) {
            list($nAuthorID, $nInterlocutorID) = explode('-', (strpos($sMessageKey, '-') !== false ? $sMessageKey : '0-0-0'), 3);
            $nAuthorID = intval($nAuthorID);
            $nInterlocutorID = intval($nInterlocutorID);
            $bMessageRedirect = ($nAuthorID > 0 && $nInterlocutorID > 0);
        }

        $bAutoRegistration = ($bMessageRedirect || $this->input->get('ar', TYPE_BOOL));

        $aUserData = $this->model->userDataByFilter(array(
                'activated'    => 0,
                'blocked'      => 0,
                array('activate_expire > :expire', ':expire' => $this->db->now()),
                'activate_key' => $sKey,
            ), array('user_id', 'email', 'password', 'password_salt', 'name', 'activated')
        );

        # Не нашли пользователя по ключу:
        # 1) Срок ключа истек / ключ некорректный
        # 2) Пользователь активирован / заблокирован
        if (empty($aUserData)) {
            $this->seo()->robotsIndex(false);
            # При переходе по ссылке "прочитать сообщение..."
            if ($bMessageRedirect) {
                return $this->authPage('auth.message', _t('users', 'Просмотр переписки'), array(
                        'message' => _t('users', 'Для просмотра переписки необходимо <a [link_auth]>авторизоваться</a>.', array(
                                'link_auth' => ' href="' . static::url('login') . '"'
                            )
                        )
                    )
                );
            }

            return $this->authPage('auth.message', _t('users', 'Активация аккаунта'), array(
                    'message' => _t('users', 'Срок действия ключа активации истек.')
                )
            );
        }

        $nUserID = $aUserData['user_id'];

        # Активируем
        $aActivateData = array(
            'activated'    => 1,
            'activate_key' => '',
        );
        if ($bAutoRegistration) {
            $sPassword = func::generator(12); # генерируем новый пароль
            $aActivateData['password'] = $aUserData['password'] = $this->security->getUserPasswordMD5($sPassword, $aUserData['password_salt']);
        }
        $bActivated = $this->model->userSave($nUserID, $aActivateData);
        if ($bActivated) {
            # Триггер активации аккаунта
            $this->triggerOnUserActivated($nUserID, array(
                'context' => 'user-activate',
            ));

            # Отправляем письмо об успешной автоматической регистрации
            if ($bAutoRegistration) {
                bff::sendMailTemplate(array(
                        'name'     => $aUserData['name'],
                        'email'    => $aUserData['email'],
                        'user_id'  => $nUserID,
                        'password' => $sPassword
                    ),
                    'users_register_auto', $aUserData['email']
                );
            }
        }

        # Авторизуем
        $bAuthorized = $this->userAuth($nUserID, 'user_id', $aUserData['password']);
        if ($bAuthorized === true) {
            # Пересохраняем избранные ОБ из куков в базу
            BBS::i()->saveFavoritesToDB($nUserID);

            # Редирект на переписку
            if ($bMessageRedirect) {
                $aInterlocutorData = $this->model->userData($nInterlocutorID, array('user_id', 'login'));
                if (!empty($aInterlocutorData)) {
                    $this->redirect(InternalMail::url('my.messages', array('i' => $aInterlocutorData['login'])));
                }
            }
        }

        # Редирект на страницу успешного завершения регистрации
        $this->redirect(static::url('register', array('step' => 'finished')));
    }

    /**
     * Восстановление пароля пользователя
     */
    public function forgot()
    {
        # Уже авторизован
        if (User::id()) {
            if (Request::isAJAX()) {
                $this->errors->impossible();
                $this->ajaxResponseForm();
            }
            $this->redirectToCabinet();
        }

        $sKey = $this->input->getpost('key', TYPE_NOTAGS, array('len' => 100));
        $bSocial = $this->input->getpost('social', TYPE_BOOL);
        if (!empty($sKey)) {
            # Шаг2: Смена пароля
            if (Request::isAJAX()) {
                do {
                    if (!$this->security->validateReferer()) {
                        $this->errors->reloadPage();
                        break;
                    }
                    # Ищем по "ключу восстановления"
                    $aData = $this->model->userDataByFilter(array(
                            'blocked'      => 0, # незаблокированные аккаунты
                            'activate_key' => $sKey,
                            array('activate_expire > :expire', ':expire' => $this->db->now()),
                        ), array('user_id', 'email', 'password', 'password_salt', 'activated')
                    );

                    # Не нашли, возможные причины:
                    # 1) Истек срок действия ссылки восстановления / неверная ссылка восстановления
                    # 2) Аккаунт заблокирован
                    # 3) Запрещаем смену пароля администраторам
                    if (empty($aData) || $this->model->userIsAdministrator($aData['user_id'])) {
                        $this->errors->set(_t('users', 'Срок действия ссылки восстановления пароля истек или ссылка некорректна, <a href="[link_fogot]">повторите попытку</a>.',
                                array('link_fogot' => static::url('forgot'))
                            )
                        );
                        break;
                    }

                    # Проверяем новый пароль
                    $password = $this->input->post('pass', TYPE_NOTRIM);
                    if (mb_strlen($password) < $this->passwordMinLength) {
                        $this->errors->set(_t('users', 'Пароль не должен быть короче [min] символов', array('min' => $this->passwordMinLength)), 'pass');
                        break;
                    }

                    $nUserID = $aData['user_id'];

                    # Cохраняем новый пароль + активируем
                    $this->model->userSave($nUserID, array(
                        'password'     => $this->security->getUserPasswordMD5($password, $aData['password_salt']),
                        'activated'    => 1,  # активируем, если аккаунт еще НЕ активирован
                        'activate_key' => '', # сбрасываем ключ
                    ));

                    # Закрепляем соц. аккаунт за профилем
                    if ($bSocial) {
                        $this->social()->authFinish($nUserID);
                    }

                } while (false);

                $this->ajaxResponseForm();
            }

            return $this->authPage('auth.forgot.finish', _t('users', 'Введите новый пароль'), array(
                    'key'    => $sKey,
                    'social' => $bSocial,
                )
            );
        } else {
            # Шаг1: Инициация восстановления пароля по E-mail адресу
            if (Request::isAJAX()) {
                $email = $this->input->post('email', TYPE_NOTAGS);
                do {
                    if (!$this->security->validateReferer()) {
                        $this->errors->reloadPage();
                        break;
                    }
                    # Проверяем E-mail
                    if (!$this->input->isEmail($email)) {
                        $this->errors->set(_t('users', 'E-mail адрес указан некорректно'), 'email');
                        break;
                    }
                    # Получаем данные пользователя
                    # - восстановление пароля для неактивированных аккаунтов допустимо
                    $aData = $this->model->userDataByFilter(array('email' => $email, 'blocked' => 0),
                        array('user_id', 'name', 'activated', 'activate_expire')
                    );
                    if (empty($aData) || $this->model->userIsAdministrator($aData['user_id'])) {
                        $this->errors->set(_t('users', 'Указанный e-mail в базе не найден'), 'email');
                        break;
                    }

                    /**
                     * Генерируем "ключ восстановления", помечаем период его действия.
                     * В случае если аккаунт неактивирован, период действия ключа восстановления пароля будет равен
                     * периоду действия ссылки активации аккаунта, поскольку задействуется
                     * одно и тоже поле "activate_expire"
                     */
                    $sKey = func::generator(20);
                    $bSaved = $this->model->userSave($aData['user_id'], array(
                            'activate_key'    => $sKey,
                            'activate_expire' => (!$aData['activated'] ? $aData['activate_expire'] :
                                    date('Y-m-d H:i:s', strtotime('+4 hours'))),
                        )
                    );

                    if (!$bSaved) {
                        $this->errors->reloadPage();
                    } else {
                        # Отправляем письмо с инcтрукцией о смене пароля
                        bff::sendMailTemplate(array(
                                'link'  => static::url('forgot', array('key' => $sKey, 'social' => $bSocial)),
                                'email' => $email,
                                'name'  => $aData['name'],
                                'user_id' => $aData['user_id'],
                            ), 'users_forgot_start', $email
                        );
                    }
                } while (false);

                $this->ajaxResponseForm();
            }

            # seo
            $this->urlCorrection(static::url('forgot'));
            $this->setMeta('forgot');

            return $this->authPage('auth.forgot.start', _t('users', 'Введите электронную почту, которую вы указывали при регистрации'), array(
                    'social' => $bSocial,
                )
            );
        }
    }

    /**
     * Выход
     */
    public function logout()
    {
        $sRedirect = bff::urlBase();

        if (User::id()) {
            $nUserID  = User::id();
            $sReferer = Request::referer();

            # оставляем пользователя на текущей странице,
            # за исключением следующих:
            $aWrongReferers = array(
                '/user/login',
                '/user/register',
                '/cabinet/',
                '/item/edit',
                '/item/success',
                '/item/activate',
            );

            if (!empty($sReferer)) {
                foreach ($aWrongReferers as $v) {
                    if (strpos($sReferer, $v) !== false) {
                        $sReferer = false;
                        break;
                    }
                }
                if (!empty($sReferer)) {
                    $sRedirect = $sReferer;
                }
            }

            if ($this->security->validateReferer()) {
                $this->security->sessionDestroy(-1, true);

                bff::hook('users.user.logout', $nUserID, $sRedirect);
            }
        }

        $this->redirect($sRedirect);
    }

    /**
     * Профиль пользователя
     */
    public function profile()
    {
        $login = $this->input->get('login', TYPE_NOTAGS);

        # Данные пользователя
        $user = $this->model->userDataByFilter(array('login' => $login), array(
                'user_id',
                'user_id_ex',
                'shop_id',
                'name',
                'login',
                'sex',
                'last_login',
                'last_activity',
                'activated',
                'blocked',
                'blocked_reason',
                'avatar',
                'created',
                'region_id',
                'reg1_country',
                'phone_number',
                'phone_number_verified',
                'phones',
                'contacts',
            )
        );

        if (empty($user)) {
            $this->errors->error404();
        }
        if ($user['blocked']) {
            $this->seo()->robotsIndex(false);
            return $this->showInlineMessage(_t('users', 'Аккаунт пользователя был заблокирован по причине:<br /><b>[reason]</b>',
                    array('reason' => $user['blocked_reason'])
                )
            );
        }

        # Подготовка данных
        $userID = $user['user_id'];
        if (empty($user['name'])) {
            $user['name'] = $user['login'];
        }
        $user['avatar'] = UsersAvatar::url($userID, $user['avatar'], UsersAvatar::szNormal, $user['sex']);
        if (!empty($user['region_id'])) {
            $user['region_title'] = Geo::regionTitle($user['region_id']);
            # разворачиваем данные о регионе: region_id => reg1_country, reg2_region, reg3_city
            $aRegions = Geo::model()->regionParents($user['region_id']);
            $user = array_merge($user, $aRegions['db']);
            $user['country_title'] = Geo::regionTitle($user['reg1_country']);
        }
        $user['phones'] = (!empty($user['phones']) ? func::unserialize($user['phones']) : array());
        if (static::registerPhoneContacts() && $user['phone_number'] && $user['phone_number_verified']) {
            array_unshift($user['phones'], array('v'=>$user['phone_number'],'m'=>Users::phoneMask($user['phone_number'])));
        }
        $user['has_contacts'] = ($user['phones'] || !empty($user['contacts']));
        $user['profile_link'] = static::urlProfile($login);
        $user['profile_link_dynamic'] = static::urlProfile($login, '', array(), true);
        View::setPageData([
            'users_profile_id'   => $userID,
            'users_profile_data' => &$user,
        ]);

        # Разделы профиля
        $tab = trim($this->input->getpost('tab', TYPE_NOTAGS), ' /');
        $tabs = array(
            'items' => array(
                't'   => _t('users', 'Объявления пользователя'),
                'm'   => 'BBS',
                'ev'  => 'user_items',
                'url' => static::urlProfile($user['login']),
                'a'   => false
            ),
        );

        # Расширяем
        $tabs = bff::filter('users.profile.tabs', $tabs, array(
            'tab' => &$tab, 'user' => &$user,
        ));
        # Сортируем
        func::sortByPriority($tabs);

        if (!isset($tabs[$tab])) {
            $tab = 'items';
        }
        $tabs[$tab]['a'] = true;

        $data = array(
            'content'  => call_user_func((is_array($tabs[$tab]['ev']) ? $tabs[$tab]['ev'] : array(bff::module($tabs[$tab]['m']), $tabs[$tab]['ev'])), $userID, $user, ['tabs'=>&$tabs]),
            'tabs'     => &$tabs,
            'user'     => &$user,
            'is_owner' => User::isCurrent($userID),
        );

        $data['avarage_author_rating'] = BBS::model()->getAvarageAuthorRating($userID, false);
        $data['avarage_author_categories_rating'] = BBS::model()->getAuthorCategoriesAvarageRating($userID, false);

        return $this->viewPHP($data, 'profile');
    }

    /**
     * Кабинет пользователя (layout)
     */
    public function my()
    {
        $aData = array('shop_open' => false);
        $tab = $this->input->get('tab', TYPE_NOTAGS); $tab = rtrim($tab, '/');
        $header = $this->my_header_menu();
        $counters = User::counter(array());
        $balance = User::balance();
        $userID = User::id();
        $shopID = User::shopID();
        $publisher = BBS::publisher();
        $tabs = array();
        View::setPageData([
            'cabinet_user_id' => $userID,
            'cabinet_shop_id' => $shopID,
        ]);

        # Магазин
        if (isset($header['menu']['shop'])) {
            $tabs['shop'] = array(
                't'   => _t('users', 'Магазин'),
                'm'   => 'Shops',
                'ev'  => 'my_shop',
                'url' => Shops::url('my.shop'),
            );
            if ($tab == 'shop/open') {
                $this->redirect($tabs['shop']['url']);
            }
            if ($tab == 'shop/abonement') {
                $tabs['shop/abonement'] = array(
                    't'   => _t('users', 'Магазин'),
                    'm'   => 'Shops',
                    'ev'  => 'my_abonement',
                    'url' => Shops::url('my.abonement'),
                );
            }
        }

        # Объявления
        if (isset($header['menu']['items'])) {
            $tabs['items'] = array(
                't'   => ($publisher == BBS::PUBLISHER_USER || !$shopID ?
                        _t('users', 'Объявления') :
                        _t('users', 'Частные объявления')
                    ),
                'm'   => 'BBS',
                'url' => BBS::url('my.items')
            );
            if ($tab == 'shop' && !isset($tabs['shop'])) {
                $this->redirect($tabs['items']['url']);
            }
        } else {
            if ($tab == 'items') {
                $this->redirect($tabs['shop']['url']);
            }
        }
        
        # Импорт
        if(BBS::importAllowed()){
            $tabs['import'] = array(
                't'   => _t('users', 'Импорт'),
                'm'   => 'BBS',
                'ev'  => 'my_import',
                'url' => BBS::url('my.import'),
            );
        }

        # Избранные объявления
        $tabs['favs'] = array(
            't'   => _t('users', 'Избранные'),
            'm'   => 'BBS',
            'url' => BBS::url('my.favs'),
        );
        # Сообщения
        $tabs['messages'] = array(
            't'       => _t('users', 'Сообщения'),
            'm'       => 'InternalMail',
            'url'     => InternalMail::url('my.messages'),
            'counter' => (!empty($counters['cnt_internalmail_new']) ? $counters['cnt_internalmail_new'] : ''),
        );
        $tabs['messages/chat'] = array('t' => false, 'm' => 'InternalMail', 'ev' => 'my_chat');

        # Счет
        if (bff::servicesEnabled()) {
            $tabs['bill'] = array(
                't'       => _t('users', 'Счёт'),
                'm'       => 'Bills',
                'url'     => Bills::url('my.history'),
                'counter' => (!empty($balance) ? $balance . ' ' . Site::currencyDefault() : ''),
            );
        }
        # Услуга платного расширения лимитов
        if (BBS::limitsPayedEnabled() && $userID) {
            $cnt = BBS::model()->limitsPayedUserByFilter(array('user_id' => $userID, 'active' => 1,));
            if ($cnt) {
                $tabs['limits'] = array(
                    't' => _t('bbs', 'Платные пакеты'),
                    'm' => 'BBS',
                    'ev' => 'my_limits_payed',
                    'url' => BBS::url('my.limits.payed'),
                );
            }
        }

        # Настройки
        $tabs['settings'] = array(
            't'   => _t('users', 'Настройки'),
            'm'   => 'Users',
            'url' => static::url('my.settings'),
        );
        # Открыть магазин
        if (!$shopID && bff::shopsEnabled(true)) {
            $aData['shop_open'] = array('url' => Shops::url('my.open'), 'active' => ($tab == 'shop/open'));
            $tabs['shop/open'] = array(
                't'   => false,
                'm'   => 'Shops',
                'ev'  => 'my_open',
                'url' => $aData['shop_open']['url']
            );
        }
        # Расширяем
        $tabs = bff::filter('users.cabinet.tabs', $tabs, array(
            'tab'=>&$tab, 'header' => &$header, 'counters' => $counters, 'balance' => $balance,
            'userID' => $userID, 'shopID' => $shopID, 'publisher' => $publisher,
        ));
        # Сортируем
        func::sortByPriority($tabs);

        if (!isset($tabs[$tab])) {
            if (Request::isAJAX()) {
                $this->errors->impossible();
                $this->ajaxResponseForm();
            } else {
                $this->errors->error404();
            }
        }
        if (!$userID) {
            if (Request::isAJAX() && $tab != 'favs') {
                $this->errors->reloadPage();
                $this->ajaxResponseForm();
            }
        }

        if (isset($tabs[$tab]['callback'])) {
            $callable = $tabs[$tab]['callback'];
        } else {
            $callable = (!empty($tabs[$tab]['m']) ? array(bff::module($tabs[$tab]['m']), 'my_' . $tab) : array());
            if (isset($tabs[$tab]['ev'])) {
                if (is_array($tabs[$tab]['ev'])) {
                    $callable = $tabs[$tab]['ev'];
                } else {
                    $callable[1] = $tabs[$tab]['ev'];
                }
            }
        }
        $aData['content'] = call_user_func($callable);

        if ($tab == 'messages/chat') {
            $tab = 'messages';
        }
        $tabs[$tab]['active'] = true;

        $aData += array(
            'tabs' => &$tabs,
            'tab'  => $tab,
            'user' => User::data(array('name', 'shop_id')),
        );

        $this->seo()->robotsIndex(false);
        bff::setMeta(_t('users', 'Кабинет пользователя'));

        return $this->viewPHP($aData, 'my.layout');
    }

    /**
     * Кабинет: Настройки профиля
     */
    public function my_settings()
    {
        $nUserID = User::id();
        $nShopID = User::shopID();
        $nPublisher = BBS::publisher();
        if (!$nUserID) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }

        $this->security->setTokenPrefix('my-settings');
        # доступность настроек:
        # true - доступна, false - скрыта
        $on_contacts = config::sysAdmin('users.settings.contacts', true, TYPE_BOOL); # контактные данные
        $on_email = config::sysAdmin('users.settings.email.change', true, TYPE_BOOL); # смена email-адреса
        $on_phone = static::registerPhone(); # смена номера телефона
        $on_destroy = config::sysAdmin('users.settings.destroy', 'none', TYPE_STR); # удаление аккаунта

        # скрываем настройки контактов пользователя при включенном обязательном магазине (не в статусе заявки)
        if ($nShopID && ($nPublisher == BBS::PUBLISHER_SHOP || $nPublisher == BBS::PUBLISHER_USER_TO_SHOP)) {
            if (Shops::premoderation()) {
                $aShopData = Shops::model()->shopData($nShopID, array('status'));
                if (!empty($aShopData['status']) && $aShopData['status'] != Shops::STATUS_REQUEST) {
                    $on_contacts = false;
                }
            } else {
                $on_contacts = false;
            }
        }

        if (Request::isPOST()) {
            $sAction = $this->input->getpost('act', TYPE_NOTAGS);
            if (!$this->security->validateToken() && $sAction != 'avatar-upload') {
                $this->errors->reloadPage();
                $this->ajaxResponseForm();
            }

            $aResponse = array();
            switch ($sAction) {
                case 'shop': # магазин
                {
                    if (!User::shopID() || !bff::shopsEnabled()) {
                        $this->errors->reloadPage();
                        break;
                    }

                    Shops::i()->my_settings();
                }
                break;
                case 'abonement': # тарифы
                {
                    if (!User::shopID() || !bff::shopsEnabled() || !Shops::abonementEnabled()) {
                        $this->errors->reloadPage();
                        break;
                    }

                    Shops::i()->my_abonement();
                }
                break;
                case 'contacts': # контактные данные
                {
                    if (!$on_contacts) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $aData = $this->input->postm(array(
                            'name'      => array(TYPE_NOTAGS, 'len' => 50, 'len.sys' => 'users.contacts.name.limit'), # ФИО
                            'region_id' => TYPE_UINT, # город или 0
                            'addr_addr' => array(TYPE_NOTAGS, 'len' => 400, 'len.sys' => 'users.contacts.addr.limit'), # адрес
                            'addr_lat'  => TYPE_NUM, # адрес, координата LAT
                            'addr_lon'  => TYPE_NUM, # адрес, координата LON
                            'phones'    => TYPE_ARRAY_NOTAGS, # телефоны
                            'contacts'  => TYPE_ARRAY_NOTAGS, # контакты
                        )
                    );

                    $this->cleanUserData($aData);

                    $this->model->userSave($nUserID, $aData);

                    $this->security->updateUserInfo($aData);

                    $aResponse['name'] = $aData['name'];
                }
                break;
                case 'avatar-upload': # аватар: загрузка
                {
                    if (!$this->security->validateToken()) {
                        $this->errors->reloadPage();
                        $mResult = false;
                    } else {
                        $mResult = $this->avatar($nUserID)->uploadQQ(true, true);
                    }

                    $aResponse = array(
                        'success' => ($mResult !== false && $this->errors->no()),
                        'errors'  => $this->errors->get(),
                    );
                    if ($mResult !== false) {
                        $this->security->updateUserInfo(array('avatar' => $mResult['filename']));
                        $nSex = User::data('sex');
                        $aResponse = array_merge($aResponse, $mResult);
                        foreach (array(UsersAvatar::szNormal, UsersAvatar::szSmall) as $size) {
                            $aResponse[$size] = UsersAvatar::url($nUserID, $mResult['filename'], $size, $nSex);
                        }
                    }

                    $this->ajaxResponse($aResponse, true);
                }
                break;
                case 'avatar-delete': # аватар: удаление
                {
                    $bDeleted = $this->avatar($nUserID)->delete(true);
                    if ($bDeleted) {
                        $nSex = User::data('sex');
                        $aResponse[UsersAvatar::szNormal] = UsersAvatar::url(0, false, UsersAvatar::szNormal, $nSex);
                        $aResponse[UsersAvatar::szSmall] = UsersAvatar::url(0, false, UsersAvatar::szSmall, $nSex);
                        $this->security->updateUserInfo(array('avatar' => ''));
                    }
                }
                break;
                case 'social-unlink': # соц. сети: отвязывание
                {
                    $oSocial = $this->social();
                    $providerKey = $this->input->post('provider', TYPE_STR);
                    $providerID = $oSocial->getProviderID($providerKey);
                    if ($providerID) {
                        $res = $oSocial->unlinkSocialAccountFromUser($providerID, $nUserID);
                        if (!$res) {
                            $this->errors->reloadPage();
                        }
                    }
                }
                break;
                case 'enotify': # email уведомления
                {
                    $aUserEnotify = $this->input->post('enotify', TYPE_ARRAY_UINT);
                    $res = $this->model->userSave($nUserID, array('enotify' => array_sum($aUserEnotify)));
                    if (empty($res)) {
                        $this->errors->reloadPage();
                    }
                }
                break;
                case 'pass': # смена пароля
                {
                    $this->input->postm(array(
                            'pass0' => TYPE_NOTRIM, # текущий пароль
                            'pass1' => TYPE_NOTRIM, # новый пароль
                        ), $p
                    );
                    extract($p, EXTR_REFS);

                    if (!User::isCurrentPassword($pass0)) {
                        $this->errors->set(_t('users', 'Текущий пароль указан некорректно'), 'pass0');
                        break;
                    }

                    if (empty($pass1)) {
                        $this->errors->set(_t('users', 'Укажите новый пароль'), 'pass1');
                    } elseif (mb_strlen($pass1) < $this->passwordMinLength) {
                        $this->errors->set(_t('users', 'Новый пароль не должен быть короче [symbols] символов',
                                array('symbols' => $this->passwordMinLength)
                            ), 'pass1'
                        );
                    } elseif ($pass0 == $pass1) {
                        $this->errors->set(_t('users', 'Новый пароль не должен совпадать с текущим'), 'pass1');
                    }

                    # запрещаем редактирование пароля администраторам
                    if ($this->model->userIsAdministrator($nUserID)) {
                        $this->errors->reloadPage(); break;
                    }

                    if (!$this->errors->no()) {
                        break;
                    }

                    $sNewPasswordHash = $this->security->getUserPasswordMD5($pass1, User::data('password_salt'));
                    $res = $this->model->userSave($nUserID, array('password' => $sNewPasswordHash));
                    if (!empty($res)) {
                        $this->security->updateUserInfo(array('password' => $sNewPasswordHash));
                    } else {
                        $this->errors->reloadPage();
                    }
                }
                break;
                case 'phone': # смена номера телефона
                {
                    if (!$on_phone) {
                        $this->errors->reloadPage();
                        break;
                    }
                    $this->input->postm(array(
                        'phone' => array(TYPE_NOTAGS, 'len' => 30, 'len.sys' => 'users.contacts.phone.limit'), # новый номер телефона
                        'code'  => TYPE_NOTAGS, # код активации из sms
                        'step'  => TYPE_NOTAGS, # этап
                    ), $p); extract($p, EXTR_REFS);

                    if (!$this->input->isPhoneNumber($phone)) {
                        $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                        break;
                    }

                    if ($this->model->userPhoneExists($phone, $nUserID)) {
                        $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован'), 'phone');
                        break;
                    }

                    if ($step == 'code-send') {
                        $activationData = $this->getActivationInfo();
                        $res = $this->sms()->sendActivationCode($phone, $activationData['key']);
                        if ($res) {
                            $activationData['key'] = md5($phone.$activationData['key']);
                            $activationData = $this->updateActivationKey($nUserID, $activationData['key']);
                            if ( ! $activationData) {
                                $this->errors->reloadPage();
                                break;
                            }
                        }
                    } else if ($step == 'finish') {
                        $aUserData = $this->model->userData($nUserID, array('activate_key'));
                        if (empty($aUserData['activate_key'])) {
                            $this->errors->reloadPage(); break;
                        }
                        if (mb_strtolower($aUserData['activate_key']) !== mb_strtolower(md5($phone.$code))) {
                            $this->errors->set(_t('users', 'Код подтверждения указан некорректно'), 'code');
                            break;
                        }
                        $res = $this->model->userSave($nUserID, array(
                            'phone_number' => $phone,
                            'phone_number_verified' => 1,
                            'activate_key' => '',
                        ));
                        if (!empty($res)) {
                            $aResponse['phone'] = '+'.$phone;
                        } else {
                            $this->errors->reloadPage();
                        }
                    }

                }
                break;
                case 'email': # смена email
                {
                    if (!$on_email) {
                        $this->errors->reloadPage();
                        break;
                    }
                    $this->input->postm(array(
                            'email' => array(TYPE_NOTAGS, 'len' => 100), # новый email
                            'pass'  => TYPE_NOTRIM, # текущий пароль
                        ), $p
                    );
                    extract($p, EXTR_REFS);

                    if ( ! User::isCurrentPassword($pass)) {
                        $this->errors->set(_t('users', 'Текущий пароль указан некорректно'), 'pass');
                        break;
                    }
                    if ( ! $this->input->isEmail($email)) {
                        $this->errors->set(_t('users', 'E-mail адрес указан некорректно'), 'email');
                        break;
                    }
                    if (Users::isEmailTemporary($email)){
                        $this->errors->set(_t('', 'Указанный вами email адрес находится в списке запрещенных, используйте например @gmail.com'), 'email');
                        break;
                    }
                    if ($this->model->userEmailExists($email)) {
                        $this->errors->set(_t('users', 'Пользователь с таким e-mail адресом уже зарегистрирован'), 'email');
                        break;
                    }

                    $user = $this->model->userData($nUserID, array('extra', 'name'));
                    $info = $this->getActivationInfo(array(), func::generator(32));
                    $info['email'] = $email;
                    $info['link'] = static::url('email_change', array('key' => $info['key'], 'step'=>$nUserID));
                    $user['extra']['email_change'] = $info;

                    $res = $this->model->userSave($nUserID, array('extra' => serialize($user['extra'])));
                    if (!empty($res)) {
                        # отправляем письмо для активации e-mail адреса
                        $mailData = array(
                            'id'            => $nUserID,
                            'user_id'       => $nUserID,
                            'name'          => $user['name'],
                            'email'         => $email,
                            'activate_link' => $info['link'],
                        );
                        bff::sendMailTemplate($mailData, 'users_email_change', $email);
                        $aResponse['msg'] = _t('users', 'На указанный e-mail отправленно письмо. Следуйте инструкциям в письме.');
                    } else {
                        $this->errors->reloadPage();
                    }
                }
                break;
                case 'destroy': # удаление аккаунта
                {
                    if ($on_destroy == 'none') {
                        $this->errors->reloadPage();
                        break;
                    }
                    $pass = $this->input->post('pass', TYPE_NOTRIM);
                    if ( ! User::isCurrentPassword($pass)) {
                        $this->errors->set(_t('users', 'Текущий пароль указан некорректно'), 'pass');
                        break;
                    }
                    $user = $this->model->userData($nUserID, array('admin', 'login', 'email', 'admin_comment'));
                    if ( ! empty($user['admin'])) {
                        $this->errors->impossible();
                        return false;
                    }

                    switch($on_destroy) {
                        case 'delete': # полное удаление
                            $saved = $this->model->userSave($nUserID, array(
                                'deleted' => static::DESTROY_BY_OWNER,
                                'blocked' => 1,
                                'blocked_reason' => _t('users', 'Учетная запись будет удалена в течение суток'),
                            ));
                            if ($saved) {
                                bff::cronManager()->executeOnce('users', 'cronDeleteUsers');
                            }
                            break;
                        case 'block': # блокировка

                            # Триггер блокировки/разблокировки аккаунта
                            bff::i()->callModules('onUserBlocked', array($nUserID, true));

                            $comments = $user['admin_comment'];
                            if ( ! empty($comments)) $comments .= "\n";
                            $comments .= _t('users', 'Пользователь удалил свой аккаунт и был помечен как заблокированный, исходный email -  [email], логин - [login]', array(
                                'email' => $user['email'],
                                'login' => $user['login'],
                            ));

                            $save = array(
                                'blocked' => 1,
                                'blocked_reason' => _t('users', 'Учетная запись удалена'),
                                'email' => 'deleted'.func::generator(15).'@'.SITEHOST,
                                'login' => 'd'.mt_rand(123456789, 987654321),
                                'admin_comment' => $comments,
                            );
                            $this->model->userSave($nUserID, $save);
                            break;
                    }

                    if ($this->errors->no()) {
                        $this->security->sessionDestroy(-1, true);
                        $aResponse['redirect'] = Site::url('index');
                        $aResponse['message_success'] = _t('users', 'Ваша учетная запись будет удалена в течение суток');
                    }
                }
                break;
                default:
                {
                    $this->errors->impossible();
                }
                break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->model->userData($nUserID, array(
                'user_id as id',
                'email',
                'phone_number',
                'phone_number_verified',
                'name',
                'enotify',
                'phones',
                'contacts',
                'avatar',
                'sex',
                'addr_addr',
                'addr_lat',
                'addr_lon',
                'region_id',
                'admin',
            ), true
        );
        if (empty($aData)) {
            # ошибка получения данных о пользователе
            bff::log('Неудалось получить данные о пользователе #' . $nUserID . ' [users::my_settings]');
            $this->security->sessionDestroy();
        }

        $aData['avatar_normal'] = UsersAvatar::url($nUserID, $aData['avatar'], UsersAvatar::szNormal, $aData['sex']);
        $aData['avatar_maxsize'] = $this->avatar($nUserID)->getMaxSize();

        # координаты по-умолчанию
        Geo::mapDefaultCoordsCorrect($aData['addr_lat'], $aData['addr_lon']);

        # данные о привязанных соц. аккаунтах
        $oSocial = $this->social();
        $aSocialProviders = $oSocial->getProvidersEnabled();
        $aSocialUser = $oSocial->getUserSocialAccountsData($nUserID);
        foreach ($aSocialUser as $k => $v) {
            if (isset($aSocialProviders[$k]) && strpos($v['profile_data'], 'a:') === 0) {
                $aSocialProviders[$k]['user'] = func::unserialize($v['profile_data']);
            }
        }
        $aData['social'] = $aSocialProviders;

        # настройки уведомлений
        $aData['enotify'] = $this->getEnotifyTypes($aData['enotify']);

        # активный подраздел настроек
        $tab = $this->input->getpost('t', TYPE_NOTAGS);
        if (empty($tab)) {
            if (!$on_contacts) {
                $tab = 'shop';
            } else {
                if (!$nShopID || $nPublisher == BBS::PUBLISHER_USER) {
                    $tab = 'contacts';
                }
            }
        }
        $aData['tab'] = & $tab;

        if ($aData['admin']) {
            $on_destroy = 'none';
        }

        $aData['on'] = array(
            'contacts' => $on_contacts,
            'phone'    => $on_phone,
            'email'    => $on_email,
            'destroy'  => $on_destroy != 'none',
        );

        $aData['shop_opened'] = ($nShopID && ! BBS::publisher(BBS::PUBLISHER_USER));
        $aData['shop_id'] = ($aData['shop_opened'] ? $nShopID : 0);

        return $this->viewPHP($aData, 'my.settings');
    }

    /**
     * Меню пользователя (шапка, кабинет)
     */
    public function my_header_menu()
    {
        static $data;
        if (isset($data)) {
            return $data;
        }

        $data = array();

        # данные о пользователе + счетчики
        if (User::id()) {
            $data['user'] = User::data(array('name', 'shop_id')) + User::counter(array());
        } else {
            $data['user'] = array('name' => _t('users', 'Гость'), 'shop_id' => 0, 'cnt_items_fav' => 0, 'cnt_internalmail_new' => 0);
        }
        # меню пользователя:
        $data['menu'] = array();
        $menu = & $data['menu'];

        # > магазин
        $shopID = User::shopID();
        $shopsEnabled = bff::shopsEnabled();
        $publisher = BBS::publisher();
        if ($shopsEnabled && $shopID) {
            $menu['shop'] = array(
                'i'   => 'fa fa-shopping-cart',
                't'   => _t('header', 'магазин'),
                'url' => Shops::url('my.shop')
            );
        }
        # > объявления
        $menu['items'] = array(
            't'   => (
                ($publisher == BBS::PUBLISHER_USER || !$data['user']['shop_id']) ?
                    _t('users', 'объявления') :
                    _t('users', 'частные объявления')
                ),
            'i'   => 'fa fa-list',
            'url' => BBS::url('my.items')
        );
        # скрываем раздел кабинета "объявления"
        if ($shopsEnabled && $shopID) {
            # при публикации только от "магазинов"
            if ($publisher == BBS::PUBLISHER_SHOP) {
                unset($menu['items']);
            } else {
                if ($publisher == BBS::PUBLISHER_USER_TO_SHOP) {
                    # после одобрения заявки магазина
                    if (Shops::model()->shopStatus($shopID) !== Shops::STATUS_REQUEST) {
                        unset($menu['items']);
                    }
                }
            }
        }

        # > избранные
        $menu['favs'] = array('i' => 'fa fa-star', 't' => _t('users', 'избранные'), 'url' => BBS::url('my.favs'));

        # > сообщения
        $menu['messages'] = array(
            'i'   => 'fa fa-comment',
            't'   => _t('users', 'сообщения'),
            'url' => InternalMail::url('my.messages')
        );

        $menu[] = 'D'; // разделитель

        # > счет
        if (bff::servicesEnabled()) {
            $menu['bill'] = array('i' => 'fa fa-retweet', 't' => _t('users', 'счет'), 'url' => Bills::url('my.history'));
        }

        # > настройки
        $menu['settings'] = array(
            'i'   => 'fa fa-pencil',
            't'   => _t('users', 'настройки'),
            'url' => static::url('my.settings')
        );

        $menu[] = 'D'; // разделитель

        # > выход
        $menu['logout'] = array('i' => 'fa fa-power-off', 't' => _t('users', 'выход'), 'url' => static::url('logout'));

        $menu = bff::filter('users.header.user.menu', $menu, $data['user']);
        func::sortByPriority($menu);

        return $data;
    }

    /**
     * Смена e-mail адреса, инициированная им в кабинете
     */
    public function email_change()
    {
        $this->seo()->robotsIndex(false);
        $title = _t('users', 'Подтверждение e-mail адреса');
        bff::setMeta($title);

        $data = $this->input->getm(array(
            'step' => TYPE_UINT, # ID пользователя
            'key'  => TYPE_NOTAGS, # Ключ
        ));
        $userID = $data['step'];
        $user = $this->model->userData($userID, array('extra'));
        $change = (isset($user['extra']['email_change']) ? $user['extra']['email_change'] : array());
        if (empty($change['key']) || ! $this->security->compareString($change['key'], $data['key']) ||
            strtotime($change['expire']) < time()) {
            return $this->authPage('auth.message', $title, array(
                'message' => _t('users', 'Срок действия ссылки истек. Перейдите в настройки и повторите попытку.')
            ));
        }
        if ($this->model->userEmailExists($change['email'])) {
            return $this->authPage('auth.message', $title, array(
                'message' => _t('users', 'Пользователь с таким e-mail адресом уже зарегистрирован')
            ));
        }

        unset($user['extra']['email_change']);
        $ok = $this->model->userSave($userID, array('email' => $change['email'], 'extra' => serialize($user['extra'])));
        if ($ok) {
            if (User::isCurrent($userID)) {
                $this->security->updateUserInfo(array('email' => $change['email']));
            }
            return $this->authPage('auth.message', $title, array(
                'message' => _t('users', 'E-mail был успешно изменен')
            ));
        }

        return $this->authPage('auth.message', $title, array(
            'message' => _t('users', 'Произошла ошибка. Зайдите в настройки и повторите попытку.')
        ));
    }

    /**
     * Смена языка пользователем
     * @param string $lng
     */
    public function localeChange($lng = LNG)
    {
        $userID = User::id();
        if( ! $userID) return;
        $update = array('lang' => $lng);
        $this->model->userSave($userID, $update);
        $this->security->updateUserInfo($update);
    }

    /**
     * Отписатся от рассылки
     * @return string HTML
     */
    public function unsubscribe()
    {
        $hash = $this->input->get('h', TYPE_STR);
        $hash = static::userHashValidate($hash);
        $hashValid = !empty($hash['user_id']);
        $userID = intval($hash['user_id']);
        $userData = $this->model->userData($userID, array('user_id', 'enotify'));
        if (empty($userData)) {
            $hashValid = false;
        } else {
            $userData['enotify'] = intval($userData['enotify']);
        }

        if (Request::isPOST())
        {
            $response = array();
            do {
                if (!$hashValid || !$this->security->validateReferer()) {
                    $this->errors->reloadPage(); break;
                }
                # Подписываем на рассылку
                if (!($userData['enotify'] & static::ENOTIFY_NEWS)) {
                    $userData['enotify'] += static::ENOTIFY_NEWS;
                    $this->model->userSave($userID, array('enotify'=>$userData['enotify']));
                }
                $response['title'] = _t('users', 'Спасибо!');
                $response['message']  = _t('users', 'Вы успешно подписались на нашу рассылку.');
                $response['message'] .= '<br />';
                $response['message'] .= _t('users', 'Обещаем не спамить и писать только по делу!');
            } while (false);
            $this->ajaxResponseForm($response);
        }

        if ($hashValid && ($userData['enotify'] & static::ENOTIFY_NEWS) && !Request::isRefresh()) {
            # Отписываем от рассылки
            $userData['enotify'] -= static::ENOTIFY_NEWS;
            $this->model->userSave($userID, array('enotify'=>$userData['enotify']));
        }

        $this->seo()->robotsIndex(false);
        $aData = array();
        # Текущий шаг
        $aData['step'] = 'subscribe';
        if (!$hashValid) {
            $aData['step'] = 'error';
        } else if ($userData['enotify'] & static::ENOTIFY_NEWS) {
            $aData['step'] = 'success';
        }

        return $this->showShortPage(_t('users', 'Отписаться от рассылки'), $this->viewPHP($aData, 'unsubscribe'));
    }

    # non-actions

    protected function redirectToCabinet()
    {
        $this->redirect(static::url('my.settings'));
    }

    /**
     * Форма отправки сообщения
     * @param string $formID ID формы
     * @return string HTML
     */
    public function writeForm($formID)
    {
        $aData = array('form_id' => $formID);
        $aData['captcha'] = static::writeFormCaptcha() && ! User::id();

        return $this->viewPHP($aData, 'write.form');
    }

    /**
     * Обработчик формы отправки сообщения пользователю / магазину
     * @param integer $authorID ID отправителя
     * @param integer $receiverID ID получателя или 0 (владелец объявления)
     * @param integer $itemID ID объявления или 0
     * @param boolean $itemRequired ID объявления обязательно ($itemID != 0)
     * @param integer|boolean $shopID ID магазина / 0 / -1 (ID магазина объявления)
     */
    public function writeFormSubmit($authorID, $receiverID, $itemID, $itemRequired, $shopID)
    {
        $aResponse = array();

        do {
            if (!$this->security->validateToken(true, false)) {
                $this->errors->reloadPage();
                break;
            }
            if (!$itemID && $itemRequired) {
                $this->errors->reloadPage();
                break;
            }

            if ($itemID) {
                $itemData = BBS::model()->itemData($itemID, array(
                        'id',
                        'user_id',
                        'shop_id',
                        'link',
                        'title',
                        'status'
                    )
                );
                if (empty($itemData) || $itemData['status'] != BBS::STATUS_PUBLICATED) {
                    $this->errors->reloadPage();
                    break;
                }
            }

            if (!$authorID) {
                $email = $this->input->postget('email', TYPE_NOTAGS, array('len' => 150));
                if (!$this->input->isEmail($email)) {
                    $this->errors->set(_t('', 'E-mail адрес указан некорректно'), 'email');
                    break;
                }
            }

            $message = $this->input->post('message', TYPE_TEXT, array('len'=>1000));
            if (mb_strlen($message) < 10) {
                $this->errors->set(_t('users', 'Сообщение слишком короткое'), 'message');
                break;
            }

            if (!$authorID) {
                if (static::writeFormCaptcha()) {
                    if (Site::captchaCustom('users-write-form')) {
                        bff::hook('captcha.custom.check');
                    } else {
                        $aResponse['captcha'] = false;
                        if (!CCaptchaProtection::isCorrect($this->input->post('captcha', TYPE_STR), 'math', 'c2wf')) {
                            $aResponse['captcha'] = true;
                            $this->errors->set(_t('', 'Результат с картинки указан некорректно'), 'captcha');
                            break;
                        }
                    }
                }

                # Функция доступна только авторизованным пользователям
                if (static::writeFormLogined()) {
                    $this->errors->set(_t('', 'Авторизуйтесь для возможности отправить сообщение'));
                    break;
                }

                $userData = $this->model->userDataByFilter(array('email' => $email), array(
                        'user_id',
                        'blocked',
                        'blocked_reason'
                    )
                );
                if (empty($userData)) {
                    # создаем новый аккаунт (неактивированный)
                    $userData = $this->userRegister(array('email' => $email));
                    if (!empty($userData['user_id'])) {
                        $authorID = $userData['user_id'];
                    } else {
                        # ошибка регистрации
                        $this->errors->reloadPage();
                        break;
                    }
                } else {
                    if ($userData['blocked']) {
                        $this->errors->set(_t('users', 'Данный аккаунт заблокирован по причине: [reason]',
                                array('reason' => $userData['blocked_reason'])
                            )
                        );
                        break;
                    }
                    $authorID = $userData['user_id'];
                }
            }

            if ($itemID) {
                $receiverID = $itemData['user_id'];
                if ($shopID === -1) {
                    $shopID = $itemData['shop_id'];
                }
            }

            # проверяем получателя
            $receiver = $this->model->userData($receiverID, array('activated', 'blocked'));
            if (empty($receiver) || $receiver['blocked'] || User::isCurrent($receiverID)) {
                $this->errors->reloadPage();
                break;
            }

            if ($authorID && $receiverID && ($authorID !== $receiverID)) {
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('users-write-form', 15)) {
                    break;
                }
                
                # отправляем сообщение владельцу ОБ
                $messageID = InternalMail::model()->sendMessage($authorID, $receiverID, $shopID, $message,
                    InternalMail::i()->attachUpload(),
                    $itemID
                );
                # сбрасываем капчу
                if (!User::id() && isset($aResponse['captcha'])) {
                    CCaptchaProtection::reset('math','c2wf');
                }
            }
        } while (false);

        $this->iframeResponseForm($aResponse);
    }

    public function ajax()
    {
        $response = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            case 'user-contacts': # просмотр контактов пользователя
            {
                $ex = $this->input->postget('ex', TYPE_STR);
                if (empty($ex)) {
                    $this->errors->reloadPage();
                    break;
                }
                list($ex, $userID) = explode('-', $ex);

                $user = $this->model->userData($userID, array(
                        'user_id',
                        'user_id_ex',
                        'activated',
                        'blocked',
                        'phone_number',
                        'phone_number_verified',
                        'phones',
                        'contacts',
                    )
                );

                if (empty($user) || $user['user_id_ex'] != $ex || !$user['activated'] || $user['blocked'] ||
                    !$this->security->validateToken(true, false)
                ) {
                    $this->errors->reloadPage();
                    break;
                }

                if (static::registerPhoneContacts() && $user['phone_number'] && $user['phone_number_verified']) {
                    if (empty($user['phones'])) $user['phones'] = array();
                    array_unshift($user['phones'], array('v'=>$user['phone_number']));
                }

                $response['phones'] = Users::phonesView($user['phones']);

                if (!empty($user['contacts'])) {
                    foreach (Users::contactsFields($user['contacts']) as $contact) {
                        $response['contacts'][$contact['key']] = (isset($contact['view'])
                            ? tpl::renderMacro($contact['value'], $contact['view'],'value')
                            : HTML::obfuscate($contact['value']));
                    }
                }
            }
            break;
        }

        $this->ajaxResponseForm($response);
    }

    public function cron()
    {
        if (!bff::cron()) {
            return;
        }
        $this->model->usersCronDeleteNotActivated();
    }

    public function cronDeleteUsers()
    {
        if (!bff::cron()) {
            return;
        }
        $this->model->usersCronDelete();
    }

    /**
     * Расписание запуска крон задач
     * @return array
     */
    public function cronSettings()
    {

        return array(
            'cron' => array('period' => '10 0 * * *'),
            'cron' => array('cronDeleteUsers' => '13 1 * * *'),
        );
    }

}