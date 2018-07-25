<?php

/**
 * Класс UsersSocialBase
 * Вспомогательный класс авторизации/регистрации пользователей через соц.сети
 * В качестве инструмента реализующего общение через протоколы OAuth2 и т.п. используется HybridAuth
 */
class UsersSocialBase extends Component
{
    # ID доступных провайдеров
    const PROVIDER_VKONTAKTE     = 1;
    const PROVIDER_FACEBOOK      = 2;
    const PROVIDER_ODNOKLASSNIKI = 4;
    const PROVIDER_MAILRU        = 8;
    const PROVIDER_GOOGLE        = 16;
    const PROVIDER_YANDEX        = 32;
    const PROVIDER_OPENID        = 64;
    const PROVIDER_YAHOO         = 128;
    const PROVIDER_AOL           = 256;
    const PROVIDER_TWITTER       = 512;
    const PROVIDER_LIVE          = 1024;
    const PROVIDER_MYSPACE       = 2048;
    const PROVIDER_LINKEDIN      = 4096;
    const PROVIDER_FOURSQUARE    = 8192;

    public function __construct()
    {
        $this->init();
    }

    /**
     * Получаем ID провайдера по ключу
     * @param string $sProviderKey ключ провайдера
     * @return integer 0 если ключ некорректный
     */
    public function getProviderID($sProviderKey)
    {
        $nID = constant('self::PROVIDER_' . mb_strtoupper($sProviderKey));

        return (is_null($nID) ? 0 : $nID);
    }

    /**
     * Получаем список доступных провайдеров
     * @return array
     */
    public function getProvidersEnabled()
    {
        $aResult = array();
        $aConfig = config::file('social');
        if (!empty($aConfig['providers']) && is_array($aConfig['providers'])) {
            foreach ($aConfig['providers'] as $k => $v) {
                if (!empty($v['enabled']) && sizeof($v) > 1) {
                    if (isset($v['keys'])) {
                        unset($v['keys']);
                    }
                    $key = mb_strtolower($k);
                    $v['id'] = $this->getProviderID($key);
                    $v['key'] = $key;
                    $aResult[$v['id']] = $v;
                }
            }
        }

        return $aResult;
    }

    /**
     * Авторизация через HybridAuth
     * @param string $sProviderKey ключ провайдера
     * @param string $sSocialLoginURL URL авторизации через соц. сеть
     * @return integer
     */
    public function auth($sProviderKey, $sSocialLoginURL = '')
    {
        $this->security->sessionStart();

        $sProviderKey = mb_strtolower($sProviderKey);

        $hybridConfig = config::file('social');
        $hybridConfig['base_url'] = ( ! empty($sSocialLoginURL) ? $sSocialLoginURL : Users::url('login.social') );
        $hybridPath = PATH_CORE . 'external'.DS.'hybridauth'.DS;
        if (!class_exists('Hybrid_Auth')) {
            require_once modification($hybridPath . 'Hybrid'.DS.'Auth.php');
        }

        try {
            if (empty($sProviderKey)) {
                $sDone = $this->input->get('hauth_done');
                $sError = $this->input->get('error');
                if (!empty($sDone) && !empty($sError)) {
                    $this->redirectFromPopup();
                }
                # Обработка запросов по протоколу (OAuth, OpenID, ...)
                if (!class_exists('Hybrid_Endpoint')) {
                    require_once modification($hybridPath . 'Hybrid'.DS.'Endpoint.php');
                }
                Hybrid_Endpoint::process();
            }

            $auth = new Hybrid_Auth($hybridConfig);

            $sRedirect = $this->input->get('ret', TYPE_STR);
            if (!empty($sRedirect) && stripos($sRedirect, SITEHOST) !== false) {
                $this->security->setSESSION('users-login-social-redirect', $sRedirect);
            }

            # Вернули $adapter
            #  или
            # Показали окно авторизации(запрос приложения) соц.сети (exit)
            $adapter = $auth->authenticate($sProviderKey);

            # Уcпешная авторизация
            $profile = $adapter->getUserProfile();
            $nProviderID = $this->getProviderID($sProviderKey);
            $nProfileID = $profile->identifier;
            $nUserID = User::id();

            # Ищем соц.аккаунт
            $aData = $this->getSocialAccountData($nProfileID, $nProviderID);
            if (empty($aData)) # нет соц.аккаунта
            {
                # Создаем соц.аккаунт
                $nSocialID = $this->createSocialAccount(0, $nProfileID, $nProviderID, $this->serializeHybridProfile($profile));
                if ($nSocialID) {
                    if ($nUserID) {
                        # пользователь авторизован => привязываем соц.аккаунт к пользователю
                        $this->linkSocialAccountToUser($nSocialID, $nUserID);
                        $this->redirectFromPopup();
                    }
                    # сохраняем в сессии пометку о процессе авторизации через соц.сеть
                    $this->authStatus($nSocialID);

                    # переходим к шагу №2
                    $this->redirectFromPopup(Users::url('register', array('step' => 'social')));
                } else {
                    # Неудалось создать соц.аккаунт
                    $this->errors->set(_t('users', 'Произошла ошибка авторизации, попробуйте обновить страницу и авторизоваться повторно'));
                }
            } else {
                $nSocialID = $aData['id'];
                if (!$aData['user_id']) # соц.аккаунт не привязан к пользователю сайта
                {
                    if ($nUserID) {
                        # пользователь авторизован => привяжем соц.аккаунт к пользователю
                        $this->linkSocialAccountToUser($nSocialID, $nUserID);
                        $this->redirectFromPopup();
                    }

                    # сохраняем в сессии пометку о процессе авторизации через соц.сеть
                    $this->authStatus($nSocialID);

                    # переходим к шагу №2
                    $this->redirectFromPopup(Users::url('register', array('step' => 'social')));
                }
                else # привязан
                {
                    if ($nUserID) {
                        # одновременно один профиль в одной соц.сети не может быть привязан
                        # к двум разным аккаунтам пользователей(TABLE_USERS), поэтому проверку на привязку
                        # текущего пользователя к данному соц.аккаунту ($nSocialID) не выполняем:
                        # - пользователь авторизован, обновляем страницу
                        $this->redirectFromPopup();
                    }

                    # Авторизуем пользователя привязанного к данному соц.аккаунту
                    $mResult = Users::i()->userAuth($aData['user_id'], 'user_id', $aData['user_password'], true, true);
                    if ($mResult === true) {
                        $this->redirectFromPopup();
                    } elseif ($mResult === 1) {
                        # неактивирован
                        # ... выводим сообщение о необходимости Активации аккаунта
                    } elseif (is_array($mResult)) {
                        # заблокирован
                        # ... выводим сообщение о Блокировке аккаунта и причину
                    }
                }
            }
        } catch (\Exception $e) {
            $nErrorCode = $e->getCode();
            if ($nErrorCode == 5) { # пользователь нажал "Отмена"
                $this->redirectFromPopup();
            }
            $aErrors = array(
                # Unspecified error.
                0 => 'Неизвестная ошибка.',
                # Unknown or disabled provider.
                1 => 'Ошибка конфигурации Hybriauth.',
                # Provider not properly configured.
                2 => 'Провайдер настроен некорректно.',
                # Unknown or disabled provider.
                3 => 'Неизвестный или неподдерживаемый провайдер.',
                # Missing provider application credentials.
                4 => 'Доступы проложения провайдера настроены некорректно.',
                # Authentification failed. The user has canceled the authentication or the provider refused the connection.
                5 => 'Ошибка аутентификации. Пользователь "отменил" либо провайдер отказал в подключении',
                # User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.
                6 => 'Запрос к данным профиля пользователя завершился ошибкой. Скорее всего пользователь не подключился к провайдеру и необходима повторная аутентификация',
                # User not connected to the provider.
                7 => 'Пользователь не подключился к провайдеру.',
            );
            if (isset($aErrors[$nErrorCode])) {
                $error = $aErrors[$nErrorCode];
                if (in_array($nErrorCode, array(6, 7))) {
                    if (!empty($adapter)) {
                        $adapter->logout();
                    }
                }
            }
            bff::log('HybridAuth: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Помечаем / получаем процесс авторизации в сессии
     * @param integer|null $mSocialID ID соц.аккаунта или NULL(получаем текущий)
     * @return mixed
     */
    public function authStatus($mSocialID = null)
    {
        $sKey = 'users-login-social-status';
        if (is_null($mSocialID)) {
            return $this->security->getSESSION($sKey);
        } else {
            $this->security->setSESSION($sKey, $mSocialID);
        }
    }

    /**
     * Получаем данные о процессе авторизации
     * @return array|boolean
     */
    public function authData()
    {
        $nSocialID = $this->authStatus();
        do {
            if (empty($nSocialID)) {
                break;
            }

            $aData = $this->getSocialAccountData($nSocialID, false);
            if (empty($aData)) {
                break;
            }

            $aProfileData = (!empty($aData['profile_data']) ? unserialize($aData['profile_data']) : array());
            unset($aData['profile_data']);

            # Формируем ФИО
            $aName = array();
            if (!empty($aProfileData['firstName'])) {
                $aName[] = $aProfileData['firstName'];
            }
            if (!empty($aProfileData['lastName'])) {
                $aName[] = $aProfileData['lastName'];
            }
            $aData['name'] = join(' ', $aName);

            # Аватар
            $aData['avatar'] = (!empty($aProfileData['photoURL']) ? $aProfileData['photoURL'] : '');

            # E-mail
            if (!empty($aProfileData['email']) && $this->input->isEmail($aProfileData['email'], false)) {
                $aData['email'] = $aProfileData['email'];
            }

            return $aData;
        } while (false);

        return false;
    }

    /**
     * Завершаем авторизацию / регистрацию через соц.сеть
     * Привязываем соц.аккаунт к пользователю
     * @param integer $nUserID ID пользователя
     * @return bool
     */
    public function authFinish($nUserID)
    {
        $nSocialID = $this->authStatus();
        if ($nUserID && !empty($nSocialID) && $nSocialID > 0) {
            $bSuccess = $this->linkSocialAccountToUser($nSocialID, $nUserID);
            if ($bSuccess) {
                $this->authStatus(0);
            }

            return $bSuccess;
        }

        return false;
    }

    /**
     * Выполняем редирект из popup-окна авторизации через соц.сеть
     * @param string|boolean $mURL URL или FALSE
     */
    public function redirectFromPopup($mURL = false)
    {
        if ($mURL === false) {
            $sRedirect = $this->security->getSESSION('users-login-social-redirect');
            if (!empty($sRedirect)) {
                $mURL = $sRedirect;
            } else {
                $mURL = bff::urlBase();
            }
        }
        $mURL = addslashes($mURL);
        echo '<!DOCTYPE html>
            <html>
              <head>
                <script type="text/javascript">
                if (window.opener) {
                    window.close();
                    ' . (!empty($mURL) ? ' window.opener.location = \'' . $mURL . '\'; ' : '') . '
                } else {
                    window.location = \'' . $mURL . '\';
                }
                </script>
              </head>
              <body></body>
            </html>';
        exit;
    }

    /**
     * Получаем данные о соц.аккаунте
     * @param integer $nSocialID ID соц.аккаунта(TABLE_USERS_SOCIAL) или ID профиля в соц.сети (если $mProviderID!==false)
     * @param integer|bool $mProviderID integer - ID провайдера (self::PROVIDER_) или FALSE
     * @return mixed
     */
    protected function getSocialAccountData($nSocialID, $mProviderID = false)
    {
        if ($mProviderID !== false) {
            return $this->db->one_array('SELECT S.*, U.password as user_password
                FROM ' . TABLE_USERS_SOCIAL . ' S
                    LEFT JOIN ' . TABLE_USERS . ' U ON S.user_id = U.user_id
                WHERE S.provider_id = :providerID AND S.profile_id = :profileID',
                array(
                    ':providerID' => $mProviderID,
                    ':profileID'  => (!empty($nSocialID) ? strval($nSocialID) : '0'),
                )
            );
        } else {
            return $this->db->one_array('SELECT S.*
                FROM ' . TABLE_USERS_SOCIAL . ' S
            WHERE S.id = :socialID',
                array(
                    ':socialID' => $nSocialID,
                )
            );
        }
    }

    /**
     * Прикрепляем соц.аккаунт к пользователю
     * @param integer $nSocialID ID соц.аккаунта(TABLE_USERS_SOCIAL)
     * @param integer $nUserID ID пользователя
     * @return boolean
     */
    protected function linkSocialAccountToUser($nSocialID, $nUserID)
    {
        if (empty($nSocialID) || empty($nUserID)) {
            return false;
        }
        $res = $this->db->update(TABLE_USERS_SOCIAL,
            array('user_id' => $nUserID),
            array('id' => $nSocialID, 'user_id' => 0)
        );

        return !empty($res);
    }

    /**
     * Открепляем соц.аккаунт от пользователя
     * @param integer $nProviderID ID провайдера
     * @param integer $nUserID ID пользователя
     * @return boolean
     */
    public function unlinkSocialAccountFromUser($nProviderID, $nUserID)
    {
        if (empty($nProviderID) || empty($nUserID)) {
            return false;
        }
        $res = $this->db->update(TABLE_USERS_SOCIAL,
            array('user_id' => 0),
            array('provider_id' => $nProviderID, 'user_id' => $nUserID)
        );

        return !empty($res);
    }

    /**
     * Создаем соц.аккаунт
     * @param integer $nUserID ID пользователя(TABLE_USERS), к которому привязываем данный соц.аккаунт или 0
     * @param integer $nProfileID ID профиля в соц.сети
     * @param integer $nProviderID ID провайдера (self::PROVIDER_)
     * @param array|string $aProfileData данные профиля полученные из соц.сети
     * @return integer ID соц.акканута (TABLE_USERS_SOCIAL)
     */
    protected function createSocialAccount($nUserID, $nProfileID, $nProviderID, $aProfileData = array())
    {
        return $this->db->insert(TABLE_USERS_SOCIAL, array(
                'user_id'      => $nUserID,
                'provider_id'  => $nProviderID,
                'profile_id'   => strval($nProfileID),
                'profile_data' => (is_array($aProfileData) ? serialize($aProfileData) : $aProfileData),
            ), 'id'
        );
    }

    /**
     * Получаем данные о соц. аккаунтах пользователя
     * @param integer $nUserID ID пользователя
     * @return array
     */
    public function getUserSocialAccountsData($nUserID)
    {
        if (!empty($nUserID)) {
            $aData = $this->db->select_key('SELECT * FROM ' . TABLE_USERS_SOCIAL . ' WHERE user_id = :id',
                'provider_id', array(':id' => $nUserID)
            );
        }
        if (empty($aData)) {
            $aData = array();
        }

        return $aData;
    }

    /**
     * Выполняем сериализацию данных профиля соц.сети
     * @param Hybrid_User_Profile $profile
     * @return string
     */
    protected function serializeHybridProfile(Hybrid_User_Profile $profile)
    {
        if (!empty($profile)) {
            $vars = array();
            $varTemplate = get_class_vars(get_class($profile));
            foreach ($varTemplate as $name => $defaultVal) {
                $vars[$name] = $profile->$name;
            }

            return serialize($vars);
        } else {
            return serialize(array());
        }
    }

}