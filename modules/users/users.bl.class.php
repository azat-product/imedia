<?php

abstract class UsersBase_ extends UsersModule
{
    /** @var UsersModel */
    public $model = null;
    /** @var bool задействовать поле "пол" */
    public $profileSex = false;

    # Флаги email-уведомлений:
    const ENOTIFY_NEWS = 1; # новости сервиса
    const ENOTIFY_INTERNALMAIL = 2; # уведомления о новых сообщениях
    const ENOTIFY_BBS_COMMENTS = 4; # уведомления о комментариях в объявлениях

    public function init()
    {
        parent::init();

        bff::autoloadEx(array(
                'UsersSMS'   => array('app', 'modules/users/users.sms.php'),
            )
        );

        # кол-во доступных номеров телефон (в профиле)
        $this->profilePhonesLimit = config::sysAdmin('users.profile.phones', 5, TYPE_UINT);
    }

    /**
     * Настройки полей контактных данных
     * @param array|boolean $data данные требующие проверки на соответствие разрешенным полям
     * @return mixed
     */
    public static function contactsFields($data = false)
    {
        $list = bff::filter('users.contacts.fields', array(
            'skype' => array(
                'title' => _te('', 'Skype'),
                'icon' => 'fa fa-skype',
                'regexp' => '/[^\.\s\[\]\:\-\_a-zA-Z0-9]/i',
                'maxlength' => 32,
                'view' => '<a href="skype:{value}?call">{value}</a>',
                'enabled' => true,
            ),
            'icq' => array(
                'title' => _te('', 'ICQ'),
                'icon' => 'fa fa-comment',
                'regexp' => '/[^\.\-\s\_0-9]/',
                'maxlength' => 20,
                'enabled' => true,
            ),
            'whatsapp' => array(
                'title' => _te('', 'WhatsApp'),
                'icon' => 'fa fa-whatsapp',
                'maxlength' => 30,
                'enabled' => false,
            ),
            'viber' => array(
                'title' => _te('', 'Viber'),
                'icon' => 'fa fa-vimeo',
                'maxlength' => 30,
                'enabled' => false,
            ),
            'telegram' => array(
                'title' => _te('', 'Telegram'),
                'icon' => 'fa fa-telegram',
                'maxlength' => 30,
                'enabled' => false,
            ),
        ));
        func::sortByPriority($list);
        foreach ($list as $k=>$v) {
            if (isset($v['enabled']) && empty($v['enabled'])) {
                unset($list[$k]); continue;
            }
            if (in_array($k, array('phones','name','has','contacts'))) {
                unset($list[$k]); continue;
            }
            if (!isset($v['maxlength'])) {
                $v['maxlength'] = 1000;
            }
            $list[$k]['key'] = $k;
        }
        if ($data !== false) {
            if (is_array($data)) {
                foreach ($data as $k => &$v) {
                    if (!isset($list[$k])) {
                        unset($data[$k]); continue;
                    }
                    $list[$k]['value'] = $v;
                    $data[$k] = $list[$k];
                }
                unset($v);
            } else if ($data === true) {
                return array_keys($list);
            }
            return $data;
        }
        return $list;
    }

    /**
     * Clean contacts data
     * @param string $contacts
     * @return array cleaned contacts
     */
    public static function contactsToArray($contacts)
    {
        if (is_string($contacts) && !empty($contacts)) {
            $contacts = json_decode($contacts, true);
        }
        if (!is_array($contacts)) {
            $contacts = array();
        }
        # only allowed
        $allowed = static::contactsFields();
        foreach ($contacts as $k=>&$v) {
            if (!isset($allowed[$k]) || # only allowed
                in_array($k,['phones','name','has','contacts']) || # forbidden key
                empty($v) # not empty
            ) {
                unset($contacts[$k]);
            }
        } unset($v);
        return $contacts;
    }

    /**
     * Clean contacts data
     * @param array $aContacts
     * @return array cleaned contacts
     */
    public function contactsCleanData(array $contacts)
    {
        $result = array();
        foreach (static::contactsFields($contacts) as $contact) {
            $value = $contact['value'];
            if (isset($contact['regexp'])) {
                $value = preg_replace($contact['regexp'], '', $value);
            }
            $value = mb_strcut($value, 0, $contact['maxlength']);
            if (!empty($value)) {
                $result[$contact['key']] = $value;
            }
        }
        return $result;
    }

    public function sendmailTemplates()
    {
        $templates = array(
            'users_register'      => array(
                'title'       => _t('users','Пользователи: уведомление о регистрации'),
                'description' => _t('users','Уведомление, отправляемое <u>пользователю</u> после регистрации, с указаниями об активации аккаунта'),
                'vars'        => array(
                    '{email}'         => _t('','Email'),
                    '{password}'      => _t('users','Пароль'),
                    '{activate_link}' => _t('users','Ссылка активации аккаунта'),
                )
            ,
                'impl'        => true,
                'priority'    => 1,
                'enotify'     => 0, # всегда
                'group'       => 'users',
            ),
            'users_register_phone' => array(
                'title'       => _t('users','Пользователи: уведомление о регистрации (с вводом номера телефона)'),
                'description' => _t('users','Шаблон письма, отправляемого <u>пользователю</u> после успешной регистрации с подтверждением номера телефона'),
                'vars'        => array(
                    '{email}'         => _t('','Email'),
                    '{password}'      => _t('users','Пароль'),
                    '{phone}'         => _t('users','Номер телефона'),
                ),
                'impl'        => true,
                'priority'    => 1.5,
                'enotify'     => 0, # всегда
                'group'       => 'users',
            ),
            'users_register_auto' => array(
                'title'       => _t('users','Пользователи: уведомление об успешной автоматической регистрации'),
                'description' => _t('users','Уведомление, отправляемое <u>пользователю</u> в случае автоматической регистрации.<br /> Активация объявления / переход по ссылке "продолжить переписку"'),
                'vars'        => array(
                    '{name}'     => _t('users','Имя'),
                    '{email}'    => _t('','Email'),
                    '{password}' => _t('users','Пароль'),
                )
            ,
                'impl'        => true,
                'priority'    => 2,
                'enotify'     => 0, # всегда
                'group'       => 'users',
            ),
            'users_forgot_start'  => array(
                'title'       => _t('users','Пользователи: восстановление пароля'),
                'description' => _t('users','Уведомление, отправляемое <u>пользователю</u> в случае запроса на восстановление пароля'),
                'vars'        => array(
                    '{name}'  => _t('users','Имя'),
                    '{email}' => _t('users','Email пользователя'),
                    '{link}'  => _t('users','Ссылка восстановления'),
                )
            ,
                'impl'        => true,
                'priority'    => 3,
                'enotify'     => 0, # всегда
                'group'       => 'users',
            ),
            'users_blocked'       => array(
                'title'       => _t('users','Пользователи: уведомление о блокировке аккаунта'),
                'description' => _t('users','Уведомление, отправляемое <u>пользователю</u> в случае блокировки аккаунта'),
                'vars'        => array(
                    '{name}'           => _t('users','Имя'),
                    '{email}'          => _t('','Email'),
                    '{blocked_reason}' => _t('users','Причина блокировки'),
                )
            ,
                'impl'        => true,
                'priority'    => 4,
                'enotify'     => 0, # всегда
                'group'       => 'users',
            ),
            'users_unblocked'     => array(
                'title'       => _t('users','Пользователи: уведомление о разблокировке аккаунта'),
                'description' => _t('users','Уведомление, отправляемое <u>пользователю</u> в случае разблокировки аккаунта'),
                'vars'        => array(
                    '{name}'  => _t('users','Имя'),
                    '{email}' => _t('','Email'),
                )
            ,
                'impl'        => true,
                'priority'    => 5,
                'enotify'     => 0, # всегда
                'group'       => 'users',
            ),
            'users_email_change'      => array(
                'title'       => _t('users','Пользователи: изменение e-mail адреса'),
                'description' => _t('users','Уведомление, отправляемое <u>пользователю</u> при изменении e-mail адреса'),
                'vars'        => array(
                    '{name}'          => _t('users','Имя'),
                    '{email}'         => _t('','Email'),
                    '{activate_link}' => _t('users','Ссылка активации'),
                )
            ,
                'impl'        => true,
                'priority'    => 6,
                'enotify'     => 0, # всегда
                'group'       => 'users',
            ),

        );

        if (!static::registerPhone()) {
            unset($templates['users_register_phone']);
        }

        Sendmail::i()->addTemplateGroup('users', _t('users', 'Пользователи'), 3);

        return $templates;
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key = '', array $opts = array(), $dynamic = false)
    {
        $url = $base = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # Авторизация
            case 'login':
                $url .= '/user/login' . static::urlQuery($opts);
                break;
            # Выход
            case 'logout':
                $url .= '/user/logout' . static::urlQuery($opts);
                break;
            # Регистрация
            case 'register':
                $url .= '/user/register' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Авторизация
            case 'login.social':
                $url .= '/user/loginsocial/' . (!empty($opts['provider']) ? $opts['provider'] : '') . static::urlQuery($opts, array('provider'));
                break;
            # Профиль пользователя
            case 'user.profile':
                $url .= '/users/' . $opts['login'] . '/' . (!empty($opts['tab']) ? $opts['tab'] . '/' : '') . static::urlQuery($opts, array('login','tab'));
                break;
            # Восстановление пароля
            case 'forgot':
                $url .= '/user/forgot' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Настройки профиля
            case 'my.settings':
                $url .= '/cabinet/settings' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Ссылка активации акканута
            case 'activate':
                $url .= '/user/activate' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Ссылка подтверждения email-адреса при смене инициированной из кабинета
            case 'email.change':
                $url .= '/user/email_change' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Пользовательское соглашение
            case 'agreement':
                $url .= '/'.config::sys('users.agreement.page', 'agreement.html', TYPE_STR) . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
            # Отписка от рассылки
            case 'unsubscribe':
                $url .= '/user/unsubscribe' . (!empty($opts) ? static::urlQuery($opts) : '');
                break;
        }
        return bff::filter('users.url', $url, array('key'=>$key, 'opts'=>$opts, 'dynamic'=>$dynamic, 'base'=>$base));
    }

    /**
     * Страница просмотра профиля пользователя
     * @param string $login логин пользователя
     * @param string $tab ключ подраздела
     * @param array $opts доп.параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function urlProfile($login, $tab = '', array $opts = array(), $dynamic = false)
    {
        if ($tab == 'items') {
            $tab = '';
        }

        return static::url('user.profile', array('login' => $login, 'tab' => $tab) + $opts, $dynamic);
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        return array(
            'pages' => array(
                'login'    => array(
                    't'      => _t('users', 'Авторизация'),
                    'list'   => false,
                    'i'      => true,
                    'macros' => array(),
                    'fields'  => array(
                        'titleh1' => array(
                            't'    => _t('', 'Заголовок H1'),
                            'type' => 'text',
                        ),
                    ),
                ),
                'register' => array(
                    't'      => _t('users', 'Регистрация'),
                    'list'   => false,
                    'i'      => true,
                    'macros' => array()
                ),
                'forgot'   => array(
                    't'      => _t('users', 'Забыли пароль'),
                    'list'   => false,
                    'i'      => true,
                    'macros' => array()
                ),
            ),
        );
    }

    /**
     * Валидация данных пользователя
     * @param array $aData @ref данные
     * @param array|boolean $mKeys список ключей требующих валидации данных или TRUE - все
     * @param array $aExtraSettings дополнительные параметры валидации
     */
    public function cleanUserData(array &$aData, $mKeys = true, array $aExtraSettings = array())
    {
        if (!is_array($mKeys)) {
            $mKeys = array_keys($aData);
        }

        foreach ($mKeys as $key) {
            if (!isset($aData[$key])) {
                continue;
            }
            if (\bff::hooksAdded('users.clean.data.'.$key)) {
                $aData[$key] = \bff::filter('users.clean.data.'.$key, $aData[$key], array('data'=>&$aData, 'extraSettings'=>$aExtraSettings));
                continue;
            }
            switch ($key) {
                case 'name': # имя
                    # допустимые символы:
                    # латиница, кирилица, тире, пробелы
                    $aData[$key] = preg_replace('/[^\.\s\[\]\-\_\p{L}0-9\w\’\']+/iu', '', $aData[$key]);
                    $aData[$key] = trim(mb_substr($aData[$key], 0, (isset($aExtraSettings['name_length']) ? $aExtraSettings['name_length'] : 50)), '- ');
                    break;
                case 'birthdate': # дата рождения
                    if (!$this->profileBirthdate) {
                        break;
                    }
                    if (empty($aData[$key]) || !checkdate($aData[$key]['month'], $aData[$key]['day'], $aData[$key]['year'])) {
                        $this->errors->set(_t('users', 'Дата рождения указана некорректно'));
                    } else {
                        $aData[$key] = "{$aData[$key]['year']}-{$aData[$key]['month']}-{$aData[$key]['day']}";
                    }
                    break;
                case 'site': # сайт
                    if (mb_strlen($aData[$key]) > 3) {
                        $aData[$key] = mb_substr(strip_tags($aData[$key]), 0, 255);
                        if (stripos($aData[$key], 'http') !== 0) {
                            $aData[$key] = 'http://' . $aData[$key];
                        }
                    } else {
                        $aData[$key] = '';
                    }
                    break;
                case 'about': # о себе
                    $aData[$key] = mb_substr($aData[$key], 0, 2500);
                    break;
                case 'phones': # телефоны
                    # в случае если телефоны в serialized виде => пропускаем
                    if (is_string($aData[$key]) && mb_stripos($aData[$key], 'a:') === 0) {
                        break;
                    }
                    $aPhones = static::validatePhones($aData[$key], (isset($aExtraSettings['phones_limit']) ? $aExtraSettings['phones_limit'] : $this->profilePhonesLimit));
                    $aData[$key] = serialize($aPhones);
                    # сохраняем первый телефон в отдельное поле
                    if (!empty($aPhones)) {
                        $aPhoneFirst = reset($aPhones);
                        $aData['phone'] = $aPhoneFirst['v'];
                    } else {
                        $aData['phone'] = '';
                    }
                    break;
                case 'skype': # skype
                    $aData[$key] = preg_replace('/[^\.\s\[\]\:\-\_a-zA-Z0-9]/', '', $aData[$key]);
                    $aData[$key] = trim(mb_substr($aData[$key], 0, 32), ' -');
                    break;
                case 'icq': # icq
                    $aData[$key] = preg_replace('/[^\.\-\s\_0-9]/', '', $aData[$key]);
                    $aData[$key] = trim(mb_substr($aData[$key], 0, 20), ' .-');
                    break;
                case 'contacts':
                    foreach ($aData[$key] as $k => $v) {
                        if (empty($v)) {
                            unset($aData[$key][$k]);
                        }
                    }
                    $aData[$key] = json_encode($this->contactsCleanData($aData[$key]));
                    break;
                case 'region_id':
                    if ($aData[$key] > 0) {
                        # проверяем корректность указанного города
                        if (!Geo::isCity($aData[$key])) {
                            $aData[$key] = 0;
                            $aData['reg1_country'] = 0;
                            $aData['reg2_region'] = 0;
                            $aData['reg3_city'] = 0;
                        } else {
                            # разворачиваем данные о регионе: region_id => reg1_country, reg2_region, reg3_city
                            $aRegions = Geo::model()->regionParents($aData['region_id']);
                            $aData = array_merge($aData, $aRegions['db']);
                        }
                    } else {
                        $aData['reg1_country'] = 0;
                        $aData['reg2_region'] = 0;
                        $aData['reg3_city'] = 0;
                    }
                    break;
            }
        }
    }

    /**
     * Иницилизация компонента работы с соц. аккаунтами
     * @return UsersSocial
     */
    public function social()
    {
        static $i;
        if (!isset($i)) {
            $i = new UsersSocial();
        }

        return $i;
    }

    /**
     * SMS шлюз
     * @param boolean $userErrors фиксировать ошибки для пользователей
     * @return UsersSMS
     */
    public function sms($userErrors = true)
    {
        static $i;
        if (!isset($i)) {
            $i = new UsersSMS();
        }
        $i->userErrorsEnabled($userErrors);

        return $i;
    }

    /**
     * Запрашивать номер телефона пользователя при регистрации
     * @return bool
     */
    public static function registerPhone()
    {
        return config::sysAdmin('users.register.phone', false, TYPE_BOOL);
    }

    /**
     * Отображать номер телефона указанный при регистрации в контактах профиля
     * @return bool
     */
    public static function registerPhoneContacts()
    {
        return config::sysAdmin('users.register.phone.contacts', false, TYPE_BOOL) && static::registerPhone();
    }

    /**
     * Выводить поле "Запомнить меня"
     * @return bool
     */
    public static function loginRemember()
    {
        return config::sysAdmin('users.login.remember', true, TYPE_BOOL);
    }

    /**
     * Поле ввода номера телефона
     * @param array $fieldAttr аттрибуты поля
     * @param array $options доп. параметры:
     *  'country' => ID страны по-умолчанию
     * @return string HTML
     */
    public function registerPhoneInput(array $fieldAttr = array(), array $options = array())
    {
        $fieldAttr = array_merge(array('name'=>'phone_number'), $fieldAttr);
        $countryList = Geo::i()->countriesList();
        $countrySelected = (!empty($options['country']) ? $options['country'] : Geo::i()->defaultCountry());
        if (!$countrySelected) {
            $filter = Geo::filter();
            if (!empty($filter['country'])) {
                $countrySelected = $filter['country'];
            }
        }
        if (!isset($countryList[$countrySelected])) {
            $countrySelected = key($countryList);
        }

        if (empty($fieldAttr['value'])) {
            $fieldAttr['value'] = '+'.intval($countryList[$countrySelected]['phone_code']);
        }

        $aData = array(
            'attr' => &$fieldAttr,
            'options' => &$options,
            'countryList' => &$countryList,
            'countrySelected' => &$countryList[$countrySelected],
            'countrySelectedID' => $countrySelected,
            'itemForm' => !empty($options['item-form']),
        );
        return $this->viewPHP($aData, 'phone.input');
    }

    /**
     * Регистрация пользователя
     * @param array $aData данные
     * @param bool $bAuth авторизовать в случае успешной регистрации
     * @return array|bool
     *  false - ошибка регистрации
     *  array - данные о вновь созданном пользователе (user_id, password, activate_link)
     */
    public function userRegister(array $aData, $bAuth = false)
    {
        # генерируем логин на основе email-адреса
        if (isset($aData['email'])) {
            $login = mb_substr($aData['email'], 0, mb_strpos($aData['email'], '@'));
            $login = preg_replace('/[^a-z0-9\_]/ui', '', $login);
            $login = mb_strtolower(trim($login, '_ '));
            if (mb_strlen($login) >= $this->loginMinLength) {
                if (mb_strlen($login) > $this->loginMaxLength) {
                    $login = mb_substr($login, 0, $this->loginMaxLength);
                }
                $aData['login'] = $this->model->userLoginGenerate($login, true);
            } else {
                $aData['login'] = $this->model->userLoginGenerate();
            }
        }

        # генерируем пароль или используем переданный
        $sPassword = (isset($aData['password']) ? $aData['password'] : func::generator(10));

        # подготавливаем данные
        $this->cleanUserData($aData, ['name', 'birthdate', 'site', 'about', 'phones', 'region_id']);
        $aData['password_salt'] = $this->security->generatePasswordSalt();
        $aData['password'] = $this->security->getUserPasswordMD5($sPassword, $aData['password_salt']);

        # данные необходимые для активации аккаунта
        $aActivation = $this->getActivationInfo();
        $aData['activated'] = 0;
        $aData['activate_key'] = $aActivation['key'];
        $aData['activate_expire'] = $aActivation['expire'];

        # по-умолчанию подписываем на все типы email-уведомлений
        $aData['enotify'] = $this->getEnotifyTypes(0, true);

        # создаем аккаунт
        $aData['user_id_ex'] = func::generator(6);
        $nUserID = $this->model->userCreate($aData, self::GROUPID_MEMBER);
        if (!$nUserID) {
            return false;
        }

        # подарок при регистрации
        $gift = config::sysAdmin('users.register.money.gift', 0, TYPE_UINT);
        if ($gift > 0) {
            Bills::i()->updateUserBalance($nUserID, $gift, true);
            Bills::i()->createBill_InGift($nUserID, $gift, $gift, _t('users', 'Подарок за регистрацию'));
        }

        if ($bAuth) {
            # авторизуем
            $this->userAuth($nUserID, 'user_id', $aData['password']);
        }

        bff::hook('users.user.register', $nUserID, $aData, array('pass'=>$sPassword, 'activation'=>$aActivation));

        return array(
            'user_id'       => $nUserID,
            'password'      => $sPassword,
            'activate_key'  => $aActivation['key'],
            'activate_link' => $aActivation['link'],
        );
    }

    /**
     * Формируем ключ активации
     * @param array $opts дополнительные параметры ссылки активации
     * @param string $key ключ активации (если был сгенерирован ранее)
     * @return array (
     *  'key'=>ключ активации,
     *  'link'=>ссылка для активации,
     *  'expire'=>дата истечения срока действия ключа
     *  )
     */
    public function getActivationInfo(array $opts = array(), $key = '')
    {
        $aData = array();
        if (empty($key)) {
            $shortCode = (static::registerPhone() && $this->input->getpost('step', TYPE_STR) !== 'social');
            if ($shortCode) {
                # В случае регистрации через телефон генерируем короткий ключ активации
                # Кроме ситуации с регистрацией через соц. сеть
                $key = mb_strtolower(func::generator(5, false));
            } else {
                $key = md5(substr(md5(uniqid(mt_rand() . SITEHOST . config::sys('users.activation.salt','^*RD%S&()%$#',TYPE_STR), true)), 0, 10) . BFF_NOW);
            }
        }
        $aData['key'] = $opts['key'] = $key;
        $aData['link'] = static::url('activate', $opts);
        $aData['expire'] = date('Y-m-d H:i:s', '+'.strtotime(config::sys('users.activation.expire','7 days',TYPE_STR)));

        return $aData;
    }

    /**
     * ОБновляем ключ активации пользователя
     * @param integer $userID ID пользователя
     * @param string $currentKey ключ активации (если был сгенерирован ранее)
     * @return array|false
     */
    public function updateActivationKey($userID, $currentKey = '')
    {
        if (empty($userID) || $userID <0) return false;

        $activationData = $this->getActivationInfo(array(), $currentKey);
        $res = $this->model->userSave($userID, array(
            'activate_key'    => $activationData['key'],
            'activate_expire' => $activationData['expire'],
        ));
        if (!$res) {
            bff::log(_t('users', 'Ошибка сохранения данных пользователя #').$userID.' [users::updateActivationKey]');
            return false;
        }

        return $activationData;
    }

    /**
     * Получаем доступные варианты email-уведомлений
     * @param int $nSettings текущие активные настройки пользователя (битовое поле)
     * @param bool $bAllCheckedSettings получить битовое поле всех активированных настроек
     * @return array|int|number
     */
    public function getEnotifyTypes($nSettings = 0, $bAllCheckedSettings = false)
    {
        $aTypes = array(
            self::ENOTIFY_NEWS         => array(
                'title' => _t('users', 'Получать рассылку о новостях [site_title]', array('site_title' => Site::title('users.enotify.news'))),
                'a'     => 0
            ),
            self::ENOTIFY_INTERNALMAIL => array(
                'title' => _t('users', 'Получать уведомления о новых сообщениях'),
                'a'     => 0
            ),
        );
        if (BBS::commentsEnabled()) {
            $aTypes[self::ENOTIFY_BBS_COMMENTS] = array(
                'title' => _t('users', 'Получать уведомления о новых комментариях на объявления'),
                'a'     => 0
            );
        }
        $aTypes = bff::filter('users.enotify.types', $aTypes, $nSettings, $bAllCheckedSettings);
        foreach ($aTypes as $k=>&$v) {
            if (!is_array($v)) {
                $v = array('title'=>$v);
            }
        } unset($v);
        func::sortByPriority($aTypes, 'priority', 2);

        if ($bAllCheckedSettings) {
            return (!empty($aTypes) ? array_sum(array_keys($aTypes)) : 0);
        }

        if (!empty($nSettings)) {
            foreach ($aTypes as $k => $v) {
                if ($nSettings & $k) {
                    $aTypes[$k]['a'] = 1;
                }
            }
        }

        return $aTypes;
    }

    /**
     * Формирование контакта пользователя в виде изображения
     * @param string|array $text текст контакта
     * @param boolean|array $imageTag обворачивать в тег <img />
     * @return string base64
     */
    public static function contactAsImage($text, $imageTag = false)
    {
        if (is_array($text) && sizeof($text) == 1) {
            $text = reset($text);
        }

        # Указываем шрифт
        $font = PATH_CORE . 'fonts' . DS . 'ubuntu-b.ttf';
        $fontSize = 11;
        $fontAngle = 0;

        # Определяем необходимые размера изображения
        $textDimm = false;
        if (function_exists('imagettfbbox')) {
            if (is_array($text)) {
                $textMulti = join("\n", $text);
                $textDimm = imagettfbbox($fontSize, $fontAngle, $font, $textMulti);
            } else {
                $textDimm = imagettfbbox($fontSize, $fontAngle, $font, $text);
            }
        }
        if ($textDimm === false) {
            return (is_array($text) ? join('<br />', $text) : $text);
        }
        $width = ($textDimm[4] - $textDimm[6]) + 2;
        $height = ($textDimm[1] - $textDimm[7]) + 2;

        # Создаем холст
        $image = imagecreatetruecolor($width, $height);

        # Формируем прозрачный фон
        imagealphablending($image, false);
        $transparentColor = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparentColor);
        imagesavealpha($image, true);

        # Пишем текст
        $textColor = imagecolorallocate($image, 0x33, 0x33, 0x33); # цвет текста
        if (is_array($text)) {
            $i = 0;
            foreach ($text as $v) {
                $y = ($i++ * $fontSize) + (5 * $i) + 8;
                imagettftext($image, $fontSize, $fontAngle, 0, $y, $textColor, $font, $v);
            }
        } else {
            imagettftext($image, $fontSize, $fontAngle, 0, $height - 2, $textColor, $font, $text);
        }

        # Формируем base64 версию изображения
        ob_start();
        imagepng($image);
        imagedestroy($image);
        $data = ob_get_clean();
        $data = 'data:image/png;base64,' . base64_encode($data);

        if (!empty($imageTag)) {
            if (!is_array($imageTag)) {
                $imageTag = array();
            }
            $imageTag['src'] = $data;
            if (!isset($imageTag['alt'])) {
                $imageTag['alt'] = '';
            }
            return '<img '.HTML::attributes($imageTag).' />';
        }

        return $data;
    }

    /**
     * Отображение номеров телефонов
     * @param array $phones номера телефонов в формате [[v=>'123','m'=>XXX], ...]
     * @return string HTML
     */
    public static function phonesView($phones, $wrap = true)
    {
        if (empty($phones) || !is_array($phones)) {
            return '';
        }
        foreach ($phones as $k=>&$v) {
            if (is_array($v)) {
                if (isset($v['v'])) {
                    $v = $v['v'];
                } else {
                    unset($phones[$k]);
                }
            }
        } unset($v);
        if (!bff::deviceDetector(bff::DEVICE_PHONE)) {
            $phones = static::contactAsImage($phones, true);
        } else {
            $view = array();
            foreach ($phones as $v) {
                $phone = HTML::obfuscate($v);
                $view[] = '<a '.HTML::attributes(array('href'=>'tel:'.$phone)).'>'.$phone.'</a>';
            }
            $phones = join(', ', $view);
        }
        return ($wrap ? '<span>'.$phones.'</span>' : $phones);
    }

    /**
     * Формирование маски номера телефона (скрытого вида)
     * @param string $phoneNumber номера телефона
     * @return string
     */
    public static function phoneMask($phoneNumber)
    {
        if (bff::hooksAdded('users.phone.mask.filter')) {
            return bff::filter('users.phone.mask.filter', $phoneNumber);
        } else {
            return mb_substr(trim(strval($phoneNumber), ' -+'), 0, 2) . bff::filter('users.phone.mask', 'x xxx xxxx');
        }
    }

    /**
     * Валидация номеров телефонов
     * @param array $aPhones номера телефонов
     * @param int $nLimit лимит
     * @return array
     */
    public static function validatePhones(array $aPhones = array(), $nLimit = 0)
    {
        $aResult = array();
        foreach ($aPhones as $v) {
            $v = preg_replace('/[^\s\+\-0-9]/', '', $v);
            $v = preg_replace('/\s+/', ' ', $v);
            $v = trim($v, '- ');
            if (strlen($v) > 4) {
                $v = mb_substr($v, 0, 20);
                $v = trim($v, '- ');
                $v = (strpos($v, '+') === 0 ? '+' : '') . str_replace('+', '', $v);
                $phone = array('v' => $v);
                $phone['m'] = static::phoneMask($v);
                $aResult[] = $phone;
            }
        }
        if ($nLimit > 0 && sizeof($aResult) > $nLimit) {
            $aResult = array_slice($aResult, 0, $nLimit);
        }

        return $aResult;
    }

    /**
     * Формирование ключа для авторизации от имени пользователя (из админ. панели)
     * @param integer $userID ID пользователя
     * @param string $userLastLogin дата последней авторизации
     * @param string $userEmail E-mail пользователя
     * @param boolean $onlyHash вернуть только hash
     * @return string
     */
    function adminAuthURL($userID, $userLastLogin, $userEmail, $onlyHash = false)
    {
        $hash = $this->security->getRememberMePasswordMD5($userID.md5($userLastLogin).config::sys('site.title').$userEmail);
        if ($onlyHash) {
            return $hash;
        }

        return static::urlBase().'/user/login_admin?hash='.$hash.'&uid='.$userID;
    }

    /**
     * Генерация ключа для автоматической авторизации
     * @param array $data @ref данные пользователя user_id, user_id_ex, last_login
     * @return string
     */
    public static function loginAutoHash(&$data)
    {
        if (empty($data['user_id']) ||
            empty($data['user_id_ex']) ||
            empty($data['last_login'])) {
            return '';
        }
        return $data['user_id'].'.'.mb_strtolower(hash('sha256', $data['user_id'].$data['user_id_ex'].md5($data['last_login']).$data['last_login']));
    }

    /**
     * Инициируем событие активации пользователя
     * @param integer $userID ID пользователя
     * @param array $options доп. параметры активации
     */
    public function triggerOnUserActivated($userID, array $options = array())
    {
        bff::hook('users.user.activated', $userID, $options);
        bff::i()->callModules('onUserActivated', array($userID, $options));
    }

    /**
     * Проверка на временный e-mail
     * @param string $email
     * @return bool
     */
    public static function isEmailTemporary($email)
    {
        if (config::sysAdmin('users.email.temporary.check', false, TYPE_BOOL)) {
            return bff::input()->isEmailTemporary($email);
        }
        return false;
    }

    /**
     * Включена ли капча для формы "связаться с автором"
     * @return bool
     */
    public static function writeFormCaptcha()
    {
        return config::sysAdmin('users.write.form.captcha', false, TYPE_BOOL);
    }

    /**
     * Формирование {user-hash} для рассылки
     * @param integer $nUserID ID пользователя
     * @param integer $nMassendID ID рассылки
     * @return string
     */
    public static function userHashGenerate($nUserID, $nMassendID)
    {
        return md5($nUserID .'!'. $nMassendID .'X2/$T'. mb_substr(md5($nMassendID.$nUserID), 4, 9) . $nMassendID .
            substr(md5('3eGH!Cm6X'.$nMassendID.'C3*TB'.$nUserID.'IG3[B'.$nMassendID.'v@-1w'.$nUserID.'m5q,E'),2,5) ) .
        '.'.$nUserID .
        '.'.$nMassendID;
    }

    /**
     * Разбор {user-hash} рассылки
     * @param string $hash hash
     * @return boolean|array [user_id, massend_id]
     */
    public static function userHashValidate($hash)
    {
        if( empty($hash) || strpos($hash, '.') === false ) {
            return false;
        }
        $data = explode('.', $hash, 3);
        if( empty($data) || empty($data[0]) || empty($data[1]) || ! isset($data[2]) ) {
            return false;
        }
        $userID = intval($data[1]);
        $massendID = intval($data[2]);
        $userData = Users::model()->userData($userID, array('user_id'));
        if( empty($userData) || empty($userData['user_id']) ) {
            return false;
        }
        if( $hash !== static::userHashGenerate($userID, $massendID) ) {
            return false;
        }
        return array(
            'user_id' => $userID,
            'massend_id' => $massendID,
        );
    }



}