<?php

class Contacts_ extends ContactsBase
{
    /**
     * Страница "Связаться с администрацией"
     */
    public function write()
    {
        # Использовать капчу, в случае если пользователь неавторизован
        $captcha_on = config::sysAdmin('contacts.captcha', true, TYPE_BOOL);

        $userID = User::id();
        $aData = array(
            'user'       => ($userID ? User::data(array('name', 'email')) : array('name' => '', 'email' => '')),
            'captcha_on' => $captcha_on,
        );

        if (Request::isAJAX()) {
            $response = array('captcha' => false);
            $p = $this->input->postm(array(
                    'name'    => array(TYPE_NOTAGS, 'len' => 70, 'len.sys' => 'contacts.name.limit'), # имя
                    'email'   => array(TYPE_NOTAGS, 'len' => 70, 'len.sys' => 'contacts.email.limit'), # e-mail
                    'ctype'   => TYPE_UINT, # тип контакта
                    'message' => array(TYPE_TEXT, 'len' => 3000, 'len.sys' => 'contacts.message.limit'), # сообщение
                    'captcha' => TYPE_STR, # капча
                )
            );

            if (!$userID) {
                if (empty($p['name'])) {
                    $this->errors->set(_t('contacts', 'Укажите ваше имя'), 'email');
                }
                if (!$this->input->isEmail($p['email'])) {
                    $this->errors->set(_t('contacts', 'E-mail адрес указан некорректно'), 'email');
                }
            }
            $this->users()->cleanUserData($p, array('name'), array('name_length'=>mb_strlen($p['name'])));
            if ($userID) {
                if (empty($p['name'])) {
                    $p['name'] = $aData['user']['name'];
                }
                if (!$this->input->isEmail($p['email'])) {
                    $p['email'] = $aData['user']['email'];
                }
            }

            $p['message'] = bff::filter('contacts.message.validate', $p['message']);
            if (mb_strlen($p['message']) < config::sys('contacts.message.min', 10, TYPE_UINT)) {
                $this->errors->set(_t('contacts', 'Текст сообщения слишком короткий'), 'message');
            }

            if (!$userID && $captcha_on) {
                # проверяем капчу
                if (Site::captchaCustom('contacts-write')) {
                    bff::hook('captcha.custom.check');
                } else {
                    if (!CCaptchaProtection::isCorrect($p['captcha'])) {
                        $this->errors->set(_t('', 'Результат с картинки указан некорректно'), 'captcha');
                        $response['captcha'] = true;
                    }
                }
            } else {
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if ($this->errors->no()) {
                    Site::i()->preventSpam('contacts-form');
                }
            }

            if ($this->errors->no('contacts.form.submit',array('data'=>&$p))) {
                CCaptchaProtection::reset();
                unset($p['captcha']);

                # корректируем тип контакта
                $contactTypes = $this->getContactTypes();
                if (!array_key_exists($p['ctype'], $contactTypes)) {
                    $p['ctype'] = key($contactTypes);
                }

                $p['useragent'] = Request::userAgent();
                $nContactID = $this->model->contactSave(0, $p);
                if ($nContactID) {
                    $this->updateCounter($p['ctype'], 1);

                    if (config::sysAdmin('contacts.from.sender', false, TYPE_BOOL)) {
                        # Отправка уведомления от адреса + имени отправителя
                        $from = $p['email'];
                        $fromName = $p['name'];
                    } else {
                        $from = '';
                        $fromName = '';
                    }
                    bff::sendMailTemplate(array(
                            'name'    => $p['name'],
                            'email'   => $p['email'],
                            'message' => nl2br($p['message']),
                        ),
                        'contacts_admin', config::sys('mail.admin'), false, $from, $fromName
                    );
                }
            }

            $this->ajaxResponseForm($response);
        }

        # SEO: Форма контактов
        $this->urlCorrection(static::url('form'));
        $this->seo()->canonicalUrl(static::url('form', array(), true));
        $this->seo()->setPageMeta('site', 'contacts-form', array(), $aData);

        $aData['types'] = $this->getContactTypes(true);

        return $this->viewPHP($aData, 'write');
    }

}