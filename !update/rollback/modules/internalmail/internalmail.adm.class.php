<?php

/**
 * Права доступа группы:
 *  - internalmail: Сообщения
 *      - my: Личная переписка (список контактов, отправка сообщения, просмотр переписки)
 *      - spy: Просмотр переписки пользователей
 */
class InternalMail_ extends InternalMailBase
{
    public function init()
    {
        parent::init();
        tpl::includeCSS('im');
    }

    /**
     * Список личных сообщений
     */
    public function listing()
    {
        if (!($this->haveAccessTo('my') || $this->haveAccessTo('users-edit', 'users'))) {
            return $this->showAccessDenied();
        }

        $nUserID = User::id();
        $nFolderID = $this->input->getpost('f', TYPE_UINT);
        if (!$nFolderID) {
            $nFolderID = self::FOLDER_ALL;
        }
        if (!static::foldersEnabled()) {
            $nFolderID = -1;
        }

        $sListingUrl = bff::$event . '&f=' . $nFolderID;
        $aData = array('f' => $nFolderID);
        if (Request::isPOST()) {
            switch ($this->input->post('act', TYPE_STR)) {
                case 'send': # отправка сообщения
                {

                    $nRecipientID = $this->input->post('recipient', TYPE_UINT);
                    if (!$nRecipientID) {
                        $this->errors->set(_t('internalmail', 'Укажите получателя сообщения'), 'recipient');
                    }

                    $sMessage = $this->model->cleanMessage($this->input->post('message', TYPE_STR));
                    if (!$sMessage) {
                        $this->errors->set(_t('internalmail', 'Не указан текст сообщения'), 'message');
                    }

                    if ($this->errors->no()) {
                        $res = $this->model->sendMessage($nUserID, $nRecipientID, 0, $sMessage, $this->attachUpload());
                        $this->adminRedirect(($res ? Errors::SUCCESS : Errors::IMPOSSIBLE), $sListingUrl);
                    }
                }
                break;
            }
        }

        $nTotal = $this->model->getContactsListingAdm($nUserID, 0, $nFolderID, true);
        $oPgn = new Pagination($nTotal, 8, $this->adminLink($sListingUrl . '&page=' . Pagination::PAGE_ID));
        $aData['contacts'] = $this->model->getContactsListingAdm($nUserID, 0, $nFolderID, false, $oPgn->getLimitOffset());

        if (!empty($aData['contacts'])) {
            foreach ($aData['contacts'] as $k => $v) {
                $aData['contacts'][$k]['avatar'] = UsersAvatar::url($v['user_id'], $v['avatar'], UsersAvatar::szNormal);
            }
        }

        $aData['pgn'] = $oPgn->view();
        if (static::foldersEnabled()) {
            $aData['folders'] = $this->getFolders();
        }

        return $this->viewPHP($aData, 'admin.listing');
    }

    /**
     * Просмотр личной переписки
     */
    public function conv()
    {
        if (!$this->haveAccessTo('my')) {
            return $this->showAccessDenied();
        }

        $sListingUrl = 'listing&f=' . $this->input->get('f', TYPE_UINT);

        $nUserID = User::id();
        $nInterlocutorID = $this->input->getpost('i', TYPE_UINT);
        $nShopID = $this->input->getpost('shop', TYPE_UINT);
        if (!$nInterlocutorID) {
            $this->adminRedirect(Errors::IMPOSSIBLE, $sListingUrl);
        }

        $aData = array('admin' => $this->security->isAdmin());
        if (Request::isPOST() && !Request::isAJAX()) {

            $aData['message'] = $this->model->cleanMessage($this->input->post('message', TYPE_STR));
            if (!$aData['message']) {
                $this->errors->set(_t('internalmail', 'Не указан текст сообщения'));
            }

            if ($this->errors->no()) {
                $res = $this->model->sendMessage($nUserID, $nInterlocutorID, $nShopID, $aData['message'], $this->attachUpload());
                $this->adminRedirect(($res ? Errors::SUCCESS : Errors::IMPOSSIBLE), $sListingUrl);
            }
        }

        $aData['name'] = User::data('name');
        $aData['shop_id'] = $nShopID;
        $aData['i'] = Users::model()->userData($nInterlocutorID, array(
                'user_id as id',
                'name',
                'email',
                'login',
                'avatar',
                'im_noreply',
                'blocked',
                'admin',
                'activated'
            )
        );
        if (empty($aData['i'])) {
            $this->adminRedirect(Errors::IMPOSSIBLE, $sListingUrl);
        }

        $nTotal = $this->model->getConversationMessages($nUserID, $nInterlocutorID, $nShopID, true);
        $oPgn = new Pagination($nTotal, 10, '#');
        $aData['list'] = $this->model->getConversationMessages($nUserID, $nInterlocutorID, $nShopID, false, $oPgn->getLimitOffset());
        $aData['list'] = $this->viewPHP($aData, 'admin.conv.ajax');
        $aData['pgn'] = $oPgn->view(array(
                'pages.attr'  => array('class' => 'j-page', 'data-page' => Pagination::PAGE_ID),
                'arrows.attr' => array('class' => 'j-page', 'data-page' => Pagination::PAGE_ID),
            )
        );

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn']
                )
            );
        }

        $aData['i']['avatar'] = UsersAvatar::url($nInterlocutorID, $aData['i']['avatar'], UsersAvatar::szNormal);

        # отмечаем как прочитанные все новые сообщения, в которых пользователь является получателем
        $this->model->setMessagesReaded($nUserID, $nInterlocutorID, $nShopID, true);

        $aData['ignored'] = (!$aData['admin'] && static::foldersEnabled() &&
            $this->model->isUserInFolder($nUserID, $nInterlocutorID, $nShopID, self::FOLDER_IGNORE));
        $aData['total'] = $nTotal;
        $aData['list_url'] = $this->adminLink($sListingUrl);

        return $this->viewPHP($aData, 'admin.conv');
    }

    /**
     * Лента сообщений пользователей
     */
    public function spy_lenta()
    {
        if (!$this->haveAccessTo('spy')) {
            return $this->showAccessDenied();
        }

        # фильтр: пользователь
        $nUserID = $this->input->getpost('u', TYPE_UINT);
        if ($nUserID > 0) {
            $aUserData = Users::model()->userData($nUserID, array('user_id as id', 'email'));
        }
        if (empty($aUserData)) {
            $nUserID = 0;
            $aUserData = array('id' => 0, 'email' => '');
        }
        $aData['user'] = $aUserData;

        $nTotal = $this->model->getMessagesSpyLenta($nUserID, true);
        $oPgn = new Pagination($nTotal, 15, '#');
        $aData['list'] = $this->model->getMessagesSpyLenta($nUserID, false, $oPgn->getLimitOffset());
        foreach ($aData['list'] as &$v) {
            $v['from_avatar'] = UsersAvatar::url($v['from_id'], $v['from_avatar'], UsersAvatar::szSmall, $v['from_sex']);
            $v['to_avatar'] = UsersAvatar::url($v['to_id'], $v['to_avatar'], UsersAvatar::szSmall, $v['to_sex']);
        }
        unset($v);

        $aData['list'] = $this->viewPHP($aData, 'admin.spy.lenta.ajax');
        $aData['pgn'] = $oPgn->view(array(
                'pages.attr'  => array('class' => 'j-page', 'data-page' => Pagination::PAGE_ID),
                'arrows.attr' => array('class' => 'j-page', 'data-page' => Pagination::PAGE_ID),
            )
        );

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn']
                )
            );
        }

        return $this->viewPHP($aData, 'admin.spy.lenta');
    }

    /**
     * Список сообщений пользователя
     */
    public function spy_listing()
    {
        if (!$this->haveAccessTo('spy')) {
            return $this->showAccessDenied();
        }

        $nFolderID = -1;
        $nUserID = $this->input->get('u', TYPE_UINT);
        $nShopID = $this->input->get('shop', TYPE_UINT);
        if ($nUserID > 0) {
            $aUserData = Users::model()->userData($nUserID, array('user_id as id', 'email'));
        }
        if (empty($aUserData)) {
            $nUserID = 0;
            $aUserData = array('id' => 0, 'email' => '');
        }

        $aData = array();
        $nTotal = $this->model->getContactsListingAdm($nUserID, $nShopID, $nFolderID, true);
        $oPgn = new Pagination($nTotal, 10, $this->adminLink('spy_listing&page=' . Pagination::PAGE_ID . '&u=' . $nUserID));
        $aData['contacts'] = $this->model->getContactsListingAdm($nUserID, $nShopID, $nFolderID, false, $oPgn->getLimitOffset());

        if (!empty($aData['contacts'])) {
            foreach ($aData['contacts'] as $k => $v) {
                $aData['contacts'][$k]['avatar'] = UsersAvatar::url($v['user_id'], $v['avatar'], UsersAvatar::szNormal);
            }
        }

        $aData['pgn'] = $oPgn->view();
        $aData['page'] = $oPgn->getCurrentPage();
        $aData['folders'] = $this->getFolders();
        $aData['user'] = $aUserData;

        return $this->viewPHP($aData, 'admin.spy.listing');
    }

    /**
     * Просмотр переписки пользователя
     */
    public function spy_conv()
    {
        if (!$this->haveAccessTo('spy')) {
            return $this->showAccessDenied();
        }

        $nUserID = $this->input->get('u', TYPE_UINT);
        $nShopID = $this->input->get('shop', TYPE_UINT);
        $sListingUrl = 'spy_listing&u=' . $nUserID . '&shop=' . $nShopID;

        $aData = array();
        $aData['u'] = Users::model()->userData($nUserID, array(
                'user_id as id',
                'name',
                'email',
                'login',
                'avatar',
                'activated'
            )
        );
        if (empty($aData['u'])) {
            $this->adminRedirect(Errors::IMPOSSIBLE, $sListingUrl);
        }

        $nInterlocutorID = $this->input->get('i', TYPE_UINT);
        if (!$nInterlocutorID) {
            $this->adminRedirect(Errors::IMPOSSIBLE, $sListingUrl);
        }

        $aData['i'] = Users::model()->userData($nInterlocutorID, array(
                'user_id as id',
                'name',
                'email',
                'login',
                'avatar',
                'im_noreply',
                'blocked',
                'admin',
                'activated'
            )
        );
        if (empty($aData['i'])) {
            $this->adminRedirect(Errors::IMPOSSIBLE, $sListingUrl);
        }

        $nTotal = $this->model->getConversationMessages($nUserID, $nInterlocutorID, $nShopID, true);
        $oPgn = new Pagination($nTotal, 10, '#');
        $aData['list'] = $this->model->getConversationMessages($nUserID, $nInterlocutorID, $nShopID, false, $oPgn->getLimitOffset());
        $aData['list'] = $this->viewPHP($aData, 'admin.spy.conv.ajax');
        $aData['pgn'] = $oPgn->view(array(
                'pages.attr'  => array('class' => 'j-page', 'data-page' => Pagination::PAGE_ID),
                'arrows.attr' => array('class' => 'j-page', 'data-page' => Pagination::PAGE_ID),
            )
        );

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn']
                )
            );
        }

        $aData['u']['avatar'] = UsersAvatar::url($nUserID, $aData['u']['avatar'], UsersAvatar::szNormal);
        $aData['i']['avatar'] = UsersAvatar::url($nInterlocutorID, $aData['i']['avatar'], UsersAvatar::szNormal);

        $aData['total'] = $nTotal;
        $aData['list_url'] = $this->adminLink($sListingUrl);

        return $this->viewPHP($aData, 'admin.spy.conv');
    }

    public function ajax()
    {
        if (!$this->haveAccessTo('my')) {
            $this->ajaxResponse(Errors::ACCESSDENIED);
        }

        $nUserID = User::id();
        $aResponse = array();
        $action = $this->input->postget('act', TYPE_STR);
        switch ($action) {
            case 'recipients': # autocomplete
            {
                $sQ = $this->input->post('q', TYPE_STR);
                $sQ = $this->input->cleanSearchString($sQ, 100);
                $aInterlocutors = $this->model->suggestInterlocutors($sQ, $nUserID, 10);
                $this->autocompleteResponse($aInterlocutors, 'id', 'email', 'ac');
            }
            break;
            case 'move2folder':
            {

                $nInterlocutorID = $this->input->postget('iid', TYPE_UINT);
                $nShopID = $this->input->postget('shop', TYPE_UINT);
                $nFolderID = $this->input->postget('fid', TYPE_UINT);
                if (!static::foldersEnabled() || !$nInterlocutorID || !$nFolderID) {
                    $this->errors->impossible();
                    break;
                }

                $aResponse['added'] = $this->model->interlocutorToFolder($nUserID, $nInterlocutorID, $nShopID, $nFolderID);
            }
            break;
            case 'toggle-blocked': # блокировка / снятие блокировки
            {
                $nMessageID = $this->input->postget('id', TYPE_UINT);
                if (!$nMessageID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $this->model->messageToggle($nMessageID, 'blocked');
            }
            break;
            case 'block-all': # блокировка всех сообщений пользователя
            {
                $nUserID2 = $this->input->post('u', TYPE_UINT);
                if (!$nUserID2) {
                    $this->errors->reloadPage();
                    break;
                }

                $aResponse['total'] = $this->model->blockUserMessages($nUserID2);
                $aResponse['message'] = tpl::declension($aResponse['total'], _t('internalmail', 'сообщение;сообщения;сообщений'));
                $aResponse['message'] = _t('internalmail', 'Заблокировано: [messages]', array('messages'=>$aResponse['message']));
            }
            break;
            default:
            {
                bff::hook('internalmail.admin.ajax.default.action', $action, $this);
                $this->errors->impossible();
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }

}