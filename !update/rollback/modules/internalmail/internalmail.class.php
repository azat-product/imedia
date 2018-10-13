<?php

class InternalMail_ extends InternalMailBase
{
    /**
     * Список контактов
     */
    public function my_messages()
    {
        $userID = User::id();
        if (!$userID) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }

        # Доступна ли фильтрация сообщений (для частого лица / магазина)
        $shopID = (bff::shopsEnabled() ? User::shopID() : 0);
        $shopSplit = ($shopID && BBS::publisher(array(BBS::PUBLISHER_USER_OR_SHOP, BBS::PUBLISHER_USER_TO_SHOP)));

        $action = $this->input->post('act', TYPE_STR);
        if (Request::isPOST() && !empty($action)) {
            $response = array();
            switch ($action) {
                case 'move2folder':
                {
                    $interlocutorID = $this->input->post('user', TYPE_UINT);
                    $shopID = $this->input->post('shop', TYPE_UINT);
                    $folderID = $this->input->post('folder', TYPE_UINT);

                    if (!static::foldersEnabled() || !$interlocutorID || !$folderID
                        || !$this->security->validateToken()
                    ) {
                        $this->errors->reloadPage();
                        break;
                    }
                    if (!array_key_exists($folderID, $this->getFolders())) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $nInterlocutorData = Users::model()->userData($interlocutorID, array(
                            'user_id',
                            'shop_id',
                            'admin'
                        )
                    );
                    if (empty($nInterlocutorData) || ($folderID == self::FOLDER_IGNORE && $nInterlocutorData['admin'])) {
                        $response['added'] = 0;
                    } else {
                        $response['added'] = $this->model->interlocutorToFolder($userID, $interlocutorID, $shopID, $folderID);
                    }
                }
                break;
            }
            $this->ajaxResponseForm($response);
        }

        $aData = array(
            'list'       => array(),
            'pgn'        => '',
            'pgn_pp'     => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>15)), 'c' => 15),
                25 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>25)), 'c' => 25),
                50 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>50)), 'c' => 50),
            ),
            'folders'    => array(
                self::FOLDER_ALL => array('title' => _t('internalmail', 'Все сообщения')),
            ),
            'my_shop_id' => $shopID,
            'shop_split' => $shopSplit,
        );
        if ($shopSplit) {
            $aData['folders'] += array(
                self::FOLDER_SH_SHOP => array('title' => _t('internalmail', 'Для магазина')),
                self::FOLDER_SH_USER => array('title' => _t('internalmail', 'Для частного лица')),
            );
        }
        if (static::foldersEnabled()) {
            $aData['folders'] += $this->getFolders();
        }

        # формируем данные для мобайл-навигации
        $i = 0;
        foreach ($aData['folders'] as $k => &$v) {
            $v['i'] = $i++;
            $v['id'] = $k;
        }
        unset($v);
        $foldersCopy = array_values($aData['folders']);
        foreach ($aData['folders'] as &$v) {
            $v['left'] = (isset($foldersCopy[$v['i'] - 1]) ? $foldersCopy[$v['i'] - 1]['id'] : false);
            $v['right'] = (isset($foldersCopy[$v['i'] + 1]) ? $foldersCopy[$v['i'] + 1]['id'] : false);
            unset($v['i'], $v['id']);
        }
        unset($v, $foldersCopy);

        $f = $this->input->postgetm(array(
                'f'    => TYPE_UINT, # папка (self::FOLDER_)
                'qq'   => TYPE_NOTAGS, # строка поиска
                'page' => TYPE_UINT, # страница
                'pp'   => TYPE_INT, # кол-во на страницу
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        if (!array_key_exists($f_f, $aData['folders'])) {
            $f_f = self::FOLDER_ALL;
        }
        if (!static::foldersEnabled()) {
            $f_f = -1;
        } # выключаем проверку папки
        if (!isset($aData['pgn_pp'][$f_pp])) {
            $f_pp = 15;
        }

        if (!empty($f_qq)) {
            $f_qq = $this->input->cleanSearchString($f_qq, 50);
        }
        $folderID = $f_f;

        $total = $aData['total'] = $this->model->getContactsListingFront($userID, $shopID, $folderID, $f_qq, true);
        if ($total > 0) {
            $pgn = new Pagination($total, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);
            $aData['list'] = $this->model->getContactsListingFront($userID, $shopID, $folderID, $f_qq, false, $pgn->getLimitOffset());
            if (!empty($aData['list'])) {
                $conversation_url_shop = static::url('my.chat', array('shop'=>''));
                $conversation_url_user = static::url('my.chat', array('user'=>''));
                foreach ($aData['list'] as &$v)
                {
                    $v['shop_id_my'] = ($v['shop_id'] && $v['shop_id'] == $shopID);
                    if ($v['shop_id'] && ! $v['shop_id_my']) {
                        $v['c_url']  = $conversation_url_shop . $v['shop']['keyword'].'-'.$v['shop']['id'];
                        $v['c_name'] = $v['shop']['title'];
                        $v['c_logo'] = $v['shop']['logo'];
                    } else {
                        $v['c_url']  = $conversation_url_user . $v['login'].($v['shop_id_my'] ? '&shop=1' : '');
                        $v['c_name'] = ( ! empty($v['name']) ? $v['name'] : $v['login'] );
                        $v['c_logo'] = UsersAvatar::url($v['user_id'], $v['avatar'], UsersAvatar::szNormal, $v['sex']);
                    }
                }
                unset($v);
            }
            $aData['pgn'] = $pgn->view(array(), tpl::PGN_COMPACT);
        }

        # формируем список контактов
        $aData['list'] = $this->viewPHP($aData, 'my.contacts.list');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'pgn'   => $aData['pgn'],
                    'list'  => $aData['list'],
                    'total' => $aData['total'],
                )
            );
        }

        if ($f_f === -1) {
            $f_f = self::FOLDER_ALL;
        }
        $aData['f'] = & $f;

        return $this->viewPHP($aData, 'my.contacts');
    }

    /**
     * Переписка с пользователем / магазином
     */
    public function my_chat()
    {
        # данные о собеседнике
        $userLogin = $this->input->getpost('user', TYPE_NOTAGS);
        if (!empty($userLogin)) {
            $userData = Users::model()->userDataByFilter(array('login' => $userLogin),
                array(
                    'user_id',
                    'avatar',
                    'sex',
                    'name',
                    'login',
                    'email',
                    'blocked',
                    'blocked_reason',
                    'activated',
                    'activate_key',
                    'im_noreply'
                )
            );
            if (!Request::isPOST()) {
                if (empty($userData)) {
                    return $this->showForbidden(
                        _t('', 'Ошибка'),
                        _t('internalmail', 'Указанный собеседник не найден либо был заблокирован')
                    );
                }
                if ($userData['blocked']) {
                    $userData['blocked_message'] = _t('users', 'Пользователь был заблокирован по причине: [reason]', array(
                        'reason' => $userData['blocked_reason']
                    ));
                }
                $userData['avatar'] = UsersAvatar::url($userData['user_id'], $userData['avatar'], UsersAvatar::szSmall, $userData['sex']);
                $userData['url_profile'] = Users::urlProfile($userData['login']);
                $userData['url_title'] = (!empty($userData['name']) ? $userData['name'] : $userData['login']);
            }
            $chatKey = $userLogin;
            $interlocutorData = & $userData;
            $interlocutorData['is_shop'] = false;
            $shopID = 0;
            if ($this->input->getpost('shop', TYPE_BOOL)) {
                $shopID = User::shopID();
            }
        } else {
            if (!bff::shopsEnabled()) {
                $this->errors->error404();
            }
            $shopKey = $this->input->getpost('shop', TYPE_NOTAGS);
            if (preg_match('/(.*)\-([\d]+)$/Ui', $shopKey, $matches) && empty($matches[2])) {
                $this->errors->error404();
            }
            $shopID = intval($matches[2]);
            $shopData = Shops::model()->shopData($shopID, array(
                    'id',
                    'user_id',
                    'title',
                    'link',
                    'logo',
                    'status',
                    'blocked_reason'
                )
            );
            if (!Request::isPOST()) {
                if (empty($shopData)) {
                    return $this->showForbidden(
                        _t('', 'Ошибка'),
                        _t('internalmail', 'Указанный собеседник не найден либо был заблокирован')
                    );
                }
                if ($shopData['status'] == Shops::STATUS_BLOCKED) {
                    $shopData['blocked_message'] = _t('internalmail', 'Магазин был заблокирован по причине: [reason]', array(
                        'reason' => $shopData['blocked_reason']
                    ));
                } else {
                    if ($shopData['status'] != Shops::STATUS_ACTIVE) {
                        return $this->showForbidden(
                            _t('', 'Ошибка'),
                            _t('internalmail', 'Переписка с магазином временно недоступна')
                        );
                    }
                }
                $shopData['avatar'] = Shops::i()->shopLogo()->url($shopID, $shopData['logo'], ShopsLogo::szMini, false, true);
                $shopData['blocked'] = ($shopData['status'] == Shops::STATUS_BLOCKED);
                $shopData['url_profile'] = Shops::urlDynamic($shopData['link']);
                $shopData['url_title'] = $shopData['title'];
                $shopData['shop_key'] = $shopKey;
            }
            $chatKey = $shopKey;
            $shopData['im_noreply'] = false;
            $interlocutorData = & $shopData;
            $interlocutorData['is_shop'] = true;
        }

        $userID = User::id();
        $this->security->setTokenPrefix('my-chat-' . $chatKey);

        $action = $this->input->getpost('act', TYPE_STR);
        if (Request::isPOST() && !empty($action)) {
            $response = array();
            switch ($action) {
                case 'send': # отправка сообщения
                {
                    if (!$this->security->validateToken()) {
                        $this->errors->reloadPage();
                        break;
                    }


                    $message = $this->input->post('message', TYPE_STR);
                    $message = $this->model->cleanMessage($message, 4000, false);
                    if (mb_strlen($message) < 5) {
                        $this->errors->set(_t('internalmail', 'Текст сообщения слишком короткий'), 'message');
                        break;
                    }

                    $interlocutorID = $interlocutorData['user_id'];
                    if ($interlocutorID == $userID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    if ($interlocutorData['im_noreply'] ||
                        $this->model->isUserInFolder($userID, $interlocutorID, $shopID, self::FOLDER_IGNORE)
                    ) {
                        $this->errors->set(_t('internalmail', 'Пользователь запретил отправлять ему сообщения'));
                        break;
                    }

                    $this->attach()->setAssignErrors(true);
                    $attachment = $this->attachUpload();
                    if (!$this->errors->no()) {
                        break;
                    }

                    $res = $this->model->sendMessage($userID, $interlocutorID, $shopID, $message, $attachment);
                    if (empty($res)) {
                        if ($this->errors->no()){
                            $this->errors->reloadPage();
                        }
                        break;
                    } else {
                        if (!$interlocutorData['is_shop'] && !$interlocutorData['activated']) {
                            # для неактивированного аккаунта отправляем спец. письмо
                            $activateData = Users::i()->getActivationInfo(array(
                                    'msg' => join('-', array($interlocutorID, $userID, rand(12345, 54321)))
                                ), $interlocutorData['activate_key']
                            );
                            if ( ! isset($interlocutorData['lang'])) {
                                $user = Users::model()->userDataEnotify($interlocutorData['user_id']);
                                $interlocutorData['lang'] = $user['lang'];
                            }
                            bff::sendMailTemplate(array(
                                    'author'        => User::data('login'),
                                    'link_activate' => $activateData['link'],
                                    'message'       => tpl::truncate(strip_tags($message), 250),
                                    'user_id'       => $interlocutorID,
                                ), 'internalmail_new_message_newuser', $interlocutorData['email'], false, '', '', $interlocutorData['lang']
                            );

                            # продлеваем период действия ссылки активации
                            Users::model()->userSave($interlocutorID, array(
                                    'activate_expire' => $activateData['expire']
                                )
                            );
                        }
                    }

                }
                break;
            }
            $this->iframeResponseForm($response);
        }

        $interlocutorID = $interlocutorData['user_id'];
        $aData['i'] =& $interlocutorData;

        # считаем кол-во сообщений в переписке
        $total = $aData['messagesTotal'] = $this->model->getConversationMessages($userID, $interlocutorID, $shopID, true);

        if ($total > 0) {
            # формируем постраничность
            $pgn = new Pagination($total, 100, '?' . Pagination::PAGE_PARAM);
            $aData['pgn'] = $pgn->view(array(), tpl::PGN_COMPACT);
            # получаем сообщения переписки
            $aData['messages'] = $this->model->getConversationMessages($userID, $interlocutorID, $shopID, false, $pgn->getLimitOffset());
        } else {
            $aData['pgn'] = '';
            $aData['messages'] = array();
        }

        # прикрепляем данные об объявлениях
        if (!empty($aData['messages'])) {
            $aItems = array();
            foreach ($aData['messages'] as $v) {
                if ($v['item_id']) {
                    $aItems[] = $v['item_id'];
                }
            }
            if (!empty($aItems)) {
                $aItems = BBS::model()->itemsListChat($aItems);
            }
            $aData['items'] = & $aItems;
        }

        # формируем список сообщений
        foreach ($aData['messages'] as &$v) {
            $v['created_date'] = strtotime($v['created_date']);
        }
        unset($v);

        $aData['attach'] = $this->attach();
        $aData['list'] = $this->viewPHP($aData, 'my.chat.list');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'pgn'  => $aData['pgn'],
                    'list' => $aData['list'],
                )
            );
        }

        # отмечаем как прочитанные все новые сообщения, в которых пользователь является получателем
        $aData['page'] = $this->input->get('page', TYPE_UINT);
        if (!$aData['page']) {
            $aNewItems = array();
            $nNewViewed = 0;
            if (!empty($aData['messages'])) {
                foreach ($aData['messages'] as $v) {
                    if ($v['new']) {
                        $nNewViewed++;
                        if ($v['item_id'] > 0) {
                            if (!isset($aNewItems[$v['item_id']])) {
                                $aNewItems[$v['item_id']] = 0;
                            }
                            $aNewItems[$v['item_id']]++;
                        }
                    }
                }
            }
            if ($nNewViewed > 0) {
                $this->model->setMessagesReaded($userID, $interlocutorID, $shopID, true);
            }
            if (!empty($aNewItems)) {
                BBS::model()->itemsListChatSetReaded($aNewItems);
            }
        }

        $aData['shop_id'] = $shopID;
        $aData['is_shop'] = $interlocutorData['is_shop'];
        $aData['attach'] = $this->attach();
        $aData['url_back'] = HTML::escape(static::url('my.messages'));

        $interlocutorData['ignoring'] = ($interlocutorData['im_noreply'] || (static::foldersEnabled() &&
                $this->model->isUserInFolder($userID, $interlocutorID, $shopID, self::FOLDER_IGNORE)));

        return $this->viewPHP($aData, 'my.chat');
    }

}