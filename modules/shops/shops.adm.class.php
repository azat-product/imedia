<?php

/**
 * Права доступа группы:
 *  - shops: Магазины
 *      - shops-listing: Просмотр списка магазинов
 *      - shops-requests: Управление списком запросов на открытие и закрепление
 *      - shops-edit: Управление магазинами (добавление/редактирование/удаление)
 *      - shops-moderate: Модерация магазинов (блокирование/одобрение)
 *      - claims-listing: Просмотр списка жалоб
 *      - claims-edit: Управление жалобами (модерация/удаление)
 *      - categories: Управление категориями (список, добавление, редактирование, удаление)
 *      - svc: Управление услугами
 *      - settings: Дополнительные настройки
 */

class Shops_ extends ShopsBase
{
    public function listing()
    {
        if (!$this->haveAccessTo('shops-listing')) {
            return $this->showAccessDenied();
        }
        $aData = array('f' => array());

        $sAction = $this->input->get('act', TYPE_STR);
        if ($sAction) {
            $aResponse = array();
            switch ($sAction) {
                case 'default':
                {
                }
                break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $limit = config::sysAdmin('shops.admin.shops.list.limit', 15, TYPE_UINT);
        $f = array();
        $this->input->postgetm(array(
                'page'   => TYPE_UINT,
                'status' => TYPE_UINT,
                'q'      => TYPE_NOTAGS,
                'u'      => TYPE_NOTAGS,
                'cat'    => TYPE_UINT,
                'owner'  => TYPE_UINT,
            ), $f
        );

        $aFilter = array();

        switch ($f['status']) {
            case 0: # Активные
                $aFilter['status'] = self::STATUS_ACTIVE;
                if (static::premoderation()) {
                    $aFilter['moderated'] = array(1, 2);
                }
                break;
            case 1: # Неактивные
                $aFilter['status'] = self::STATUS_NOT_ACTIVE;
                break;
            case 2: # На модерации
                $aFilter[':status'] = 'S.status!=' . self::STATUS_REQUEST;
                $aFilter['moderated'] = array(0, 2);
                $limit = config::sysAdmin('shops.admin.shops.list.moderate.limit', $limit, TYPE_UINT);
                break;
            case 3: # Заблокированные
                $aFilter['status'] = self::STATUS_BLOCKED;
                break;
            case 4: # Все
                $aFilter[':status'] = 'S.status!=' . self::STATUS_REQUEST;
                break;
        }

        # Тип владельца
        switch ($f['owner']) {
            case 0: # Все
                break;
            case 1: # С владельцем
                $aFilter[':owner'] = 'S.user_id > 0';
                break;
            case 2: # Без владельца
                $aFilter[':owner'] = 'S.user_id = 0';
                break;
        }

        if (!empty($f['q'])) {
            $aFilter['q'] = $f['q'];
        }
        if (!empty($f['u'])) {
            $aFilter['u'] = $f['u'];
        }
        if ($f['cat'] > 0) {
            $aFilter['cat'] = $f['cat'];
        }

        $aData['orders'] = array('created' => 'desc');
        $f += $this->prepareOrder($orderBy, $orderDirection, 'created' . tpl::ORDER_SEPARATOR . 'desc', $aData['orders']);
        $f['order'] = $orderBy . tpl::ORDER_SEPARATOR . $orderDirection;
        $aData['f'] = $f;

        $nTotal = $this->model->shopsListing($aFilter, true);
        $oPgn = new Pagination($nTotal, $limit, '#', 'jShops.page(' . Pagination::PAGE_ID . '); return false;');

        $aData['list'] = $this->model->shopsListing($aFilter, false, "$orderBy $orderDirection", $oPgn->getLimitOffset());
        $aData['list'] = $this->viewPHP($aData, 'admin.shops.listing.ajax');

        $aData['pgn'] = $oPgn->view();

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }
        if (static::categoriesEnabled()) {
            $aData['cats'] = $this->model->catsOptions('adm-shops-listing', $f['cat'], _t('shops', 'Все категории'));
        } else {
            $aData['cats'] = BBS::model()->catsOptions('adm-shops-listing', $f['cat'], _t('shops', 'Все категории'));
        }

        return $this->viewPHP($aData, 'admin.shops.listing');
    }

    public function requests_open()
    {
        if (!$this->haveAccessTo('shops-requests')) {
            return $this->showAccessDenied();
        }
        $aData = array('f' => array());

        $f = array();
        $this->input->postgetm(array(
                'page' => TYPE_UINT,
            ), $f
        );

        $aFilter = array(
            'status' => static::STATUS_REQUEST
        );

        $sAction = $this->input->getpost('act', TYPE_STR);
        if (!empty($sAction)) {
            $aResponse = array();
            switch ($sAction) {
                case 'delete':
                {

                    $nShopID = $this->input->post('id', TYPE_UINT);
                    if (!$nShopID) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    if (BBS::publisher(BBS::PUBLISHER_SHOP)) {
                        $itemTotal = $this->model->shopItemsCounter($nShopID);
                        if ($itemTotal > 0) {
                            $this->errors->set(_t('shops', 'Невозможно удалить магазин с объявлениями ([total])', array('total' => $itemTotal)));
                            break;
                        }
                    }
                    $res = $this->shopDelete($nShopID);
                    if (!$res) {
                        $this->errors->impossible();
                    }
                }
                break;
            }
            $this->ajaxResponseForm($aResponse);
        }

        $aData['orders'] = array('created' => 'desc');
        $f += $this->prepareOrder($orderBy, $orderDirection, 'created' . tpl::ORDER_SEPARATOR . 'desc', $aData['orders']);
        $f['order'] = $orderBy . tpl::ORDER_SEPARATOR . $orderDirection;
        $aData['f'] = $f;

        $nTotal = $this->model->shopsListing($aFilter, true);
        $oPgn = new Pagination($nTotal, 15, '#', 'jShops.page(' . Pagination::PAGE_ID . '); return false;');

        $aData['list'] = $this->model->shopsListing($aFilter, false, "$orderBy $orderDirection", $oPgn->getLimitOffset());
        $aData['list'] = $this->viewPHP($aData, 'admin.shops.requests.open.ajax');

        $aData['pgn'] = $oPgn->view();

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        return $this->viewPHP($aData, 'admin.shops.requests.open');
    }

    public function requests()
    {
        if (!$this->haveAccessTo('shops-requests')) {
            return $this->showAccessDenied();
        }

        $sAct = $this->input->postget('act', TYPE_STR);
        if (!empty($sAct) || Request::isPOST()) {
            $aResponse = array();
            switch ($sAct) {
                case 'edit':
                {
                    $nRequestID = $this->input->postget('id', TYPE_UINT);
                    if (!$nRequestID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $aData = $this->model->requestData($nRequestID, true);
                    if (empty($aData)) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    if (empty($aData['viewed'])) {
                        $this->model->requestSave($nRequestID, array('viewed' => 1));
                        $this->updateRequestsCounter(-1, true);
                    }
                    if ($aData['user_id']) {
                        $aData['user'] = Users::model()->userData($aData['user_id'], array(
                                'user_id',
                                'email',
                                'blocked'
                            )
                        );
                    }
                    if ($aData['shop_id']) {
                        $aData['shop'] = $this->model->shopData($aData['shop_id'], array('link', 'title'));
                    }

                    $aResponse['form'] = $this->viewPHP($aData, 'admin.requests.form');
                }
                break;
                case 'delete':
                {
                    $nRequestID = $this->input->postget('id', TYPE_UINT);
                    if (!$nRequestID) {
                        $this->errors->impossible();
                        break;
                    }

                    $aData = $this->model->requestData($nRequestID, true);
                    if (empty($aData)) {
                        $this->errors->impossible();
                        break;
                    }


                    $res = $this->model->requestDelete($nRequestID);
                    if (!$res) {
                        $this->errors->impossible();
                        break;
                    } else {
                        if (empty($aData['viewed'])) {
                            $this->updateRequestsCounter(-1, true);
                        }
                    }
                }
                break;
                default:
                    $aResponse = false;
            }

            if ($aResponse !== false && Request::isAJAX()) {
                $this->ajaxResponseForm($aResponse);
            }
        }

        $f = array();
        $this->input->postgetm(array(
                'page' => TYPE_UINT,
            ), $f
        );

        # формируем фильтр списка
        $sql = array();
        $mPerpage = 15;
        $aData['pgn'] = '';

        $nCount = $this->model->requestsListing($sql, true);
        $oPgn = new Pagination($nCount, $mPerpage, '#', 'jShopsRequestsList.page('.Pagination::PAGE_ID.'); return false;');
        $aData['pgn'] = $oPgn->view(array('arrows'=>false));
        $aData['list'] = $this->model->requestsListing($sql, false, $oPgn->getLimitOffset());
        $aData['list'] = $this->viewPHP($aData, 'admin.requests.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        $aData['f'] = $f;
        $aData['id'] = $this->input->get('id', TYPE_UINT);
        $aData['act'] = $sAct;

        return $this->viewPHP($aData, 'admin.requests.listing');
    }

    /**
     * Обрабатываем параметры запроса на закрепление
     * @param integer $nRequestID ID заявки или 0
     * @param boolean $bSubmit выполняем сохранение/редактирование
     * @return array параметры
     */
    protected function validateRequestData($nRequestID, $bSubmit)
    {
        $aData = array();
        $this->input->postm(array(
                'name'        => TYPE_STR, # Имя
                'phone'       => TYPE_STR, # Телефон
                'email'       => TYPE_STR, # E-mail
                'description' => TYPE_STR, # Описание
            ), $aData
        );

        if ($bSubmit) {
            //
        }

        return $aData;
    }

    public function add()
    {
        if (!$this->haveAccessTo('shops-edit')) {
            return $this->showAccessDenied();
        }

        $this->validateShopData(0, $aData);

        if (Request::isPOST()) {
            $aResponse = array();

            $nUserID = $this->input->post('user_id', TYPE_UINT);
            $aUserData = array();
            if (!$nUserID) {
                if (!static::categoriesEnabled()) {
                    $this->errors->set(_t('shops', 'Укажите владельца'), 'user');
                }
            } else {
                $aUserData = Users::model()->userData($nUserID, array('shop_id'));
                if (empty($aUserData)) {
                    $this->errors->set(_t('shops', 'Указанный пользователь не найден'), 'user');
                } else {
                    if (!empty($aUserData['shop_id'])) {
                        $this->errors->set(_t('shops', 'Указанный пользователь уже закреплен за другим магазином'), 'user');
                    }
                }
            }

            do {
                # создаем магазин
                $aData['user_id'] = $nUserID;
                $aData['status'] = self::STATUS_ACTIVE;
                $aData['moderated'] = 1;
                if (!$this->errors->no('shops.admin.shop.submit',array('id'=>0,'data'=>&$aData,'user'=>$aUserData))) {
                    break;
                }
                $nShopID = $this->model->shopSave(0, $aData);
                if (!$nShopID) {
                    $this->errors->set(_t('shops', 'Ошибка создания магазина'));
                    break;
                }

                if ($nUserID > 0) {
                    # помечаем связь магазина с пользователем
                    $this->onUserShopCreated($nUserID, $nShopID);

                }
                # загружаем логотип
                $mLogo = $this->shopLogo($nShopID)->onSubmit(true, 'shop_logo', 'shop_logo_del');
                if ($mLogo !== false) {
                    $this->model->shopSave($nShopID, array('logo' => $mLogo));
                }
            } while (false);

            $this->iframeResponseForm($aResponse);
        }

        return $this->form(0, $aData);
    }

    public function edit()
    {
        if (!$this->haveAccessTo('shops-edit')) {
            return $this->showAccessDenied();
        }

        $nShopID = $this->input->getpost('id', TYPE_UINT);
        if (!$nShopID) {
            return $this->showImpossible();
        }

        if (Request::isPOST()) {
            $sAction = $this->input->getpost('act', TYPE_STR);
            switch ($sAction) {
                case 'info': # сохранение данных вкладки "Настройки"
                {

                    $aResponse = array('reload' => false);
                    $this->validateShopData($nShopID, $aData);

                    # закрепление за пользователем
                    $nUserID = $this->input->post('user_id', TYPE_UINT);
                    $aUserData = array();
                    do {
                        if (!$nUserID) {
                            break;
                        }
                        $aDataPrev = $this->model->shopData($nShopID, array('user_id'));
                        if (!empty($aDataPrev['user_id'])) {
                            $this->errors->set(_t('shops', 'Данный магазин уже закреплен за пользователем'), 'user');
                            break;
                        }
                        $aUserData = Users::model()->userData($nUserID, array('shop_id'));
                        if (empty($aUserData)) {
                            $this->errors->set(_t('shops', 'Указанный пользователь не найден'), 'user');
                            break;
                        }
                        if (!empty($aUserData['shop_id'])) {
                            $this->errors->set(_t('shops', 'Указанный пользователь уже закреплен за другим магазином'), 'user');
                            break;
                        }
                        $aData['user_id'] = $nUserID;
                    } while (false);

                    if ($this->errors->no('shops.admin.shop.submit',array('id'=>$nShopID,'data'=>&$aData,'user'=>$aUserData))) {
                        # обновляем логотип (если необходимо)
                        $mLogo = $this->shopLogo($nShopID)->onSubmit(false, 'shop_logo', 'shop_logo_del');
                        if ($mLogo !== false) {
                            $aData['logo'] = $mLogo;
                            $aResponse['reload'] = true;
                        }

                        # сохраняем настройки магазина
                        $res = $this->model->shopSave($nShopID, $aData);

                        if (!empty($res) && !empty($aData['user_id'])) {
                            $this->onUserShopCreated($aData['user_id'], $nShopID);
                        }
                    }

                    $this->iframeResponseForm($aResponse);
                }
                break;
                case 'claims': # инициализация вкладки "Жалобы"
                {
                    $aData['id'] = $nShopID;
                    $aData['edit_allowed'] = $this->haveAccessTo('claims-edit');
                    $aData['claims'] = $this->model->claimsListing(array('shop_id' => $nShopID));
                    foreach ($aData['claims'] as &$v) {
                        $v['message'] = $this->getItemClaimText($v['reason'], $v['message']);
                    }
                    unset($v);

                    $aResponse['html'] = $this->viewPHP($aData, 'admin.shops.claims.list');
                    $this->ajaxResponseForm($aResponse);
                }
                break;
            }
        }

        $aData = $this->model->shopData($nShopID, '*', true);
        if (empty($aData)) {
            return $this->showImpossible();
        }

        return $this->form($nShopID, $aData);
    }

    protected function form($nShopID, &$aData)
    {
        $aData['tabs'] = array('info' => _t('shops', 'Настройки'));
        if ($nShopID) {
            $aData['tabs']['claims'] = _t('shops', 'Жалобы') . ($aData['claims_cnt'] ? ' (' . $aData['claims_cnt'] . ')' : '');
            if (bff::servicesEnabled()) {
                $aData['tabs']['svc'] = _t('shops', 'Услуги');
            }
        }
        $aData['tabs'] = bff::filter('shops.admin.shops.form.tabs', $aData['tabs'], array('id'=>$nShopID,'data'=>&$aData));
        $aData['tab'] = $this->input->get('tab', TYPE_STR);
        if (!isset($aData['tabs'][$aData['tab']])) {
            $aData['tab'] = key($aData['tabs']);
        }

        $nUserID = (bff::$event == 'add' ? $this->input->get('user', TYPE_UINT) : $aData['user_id']);
        $aData['user'] = ($nUserID ? Users::model()->userData($nUserID, array(
                'user_id',
                'email',
                'blocked'
            )
        ) : array());

        $aData['id'] = $nShopID;
        $aData['tab_info'] = $this->formInfo($nShopID, $aData);

        if ($nShopID) {
            tpl::includeJS('comments', true); # подключаем js+css для вкладки "Жалобы"
        }
        if (static::abonementEnabled() && !empty($aData['svc_abonement_id'])) {
            $aData['publicated'] = BBS::model()->itemsCount(array('shop_id' => $nShopID, 'is_publicated' => 1, 'status' => BBS::STATUS_PUBLICATED));
            $aData['abonement'] = $this->model->abonementData($aData['svc_abonement_id']);
        }

        return $this->viewPHP($aData, 'admin.shops.form');
    }

    public function formInfo($nShopID = 0, &$aData = array())
    {
        if ($nShopID) {
            if (empty($aData)) {
                $aData = $this->model->shopData($nShopID, '*', true);
            } else {
                $aData['id'] = $nShopID;
            }
        } else {
            $this->validateShopData(0, $aData);
            $aData['id'] = 0;
            $aData['logo'] = '';
            if (isset($aData['region_id'])) {
                $aData['region_title'] = Geo::regionTitle($aData['region_id']);
            }
        }

        if (!empty($aData['logo'])) {
            $aData['logo_list'] = ShopsLogo::url($nShopID, $aData['logo'], ShopsLogo::szList);
            $aData['logo_view'] = ShopsLogo::url($nShopID, $aData['logo'], ShopsLogo::szView);
        }

        if ($aData['cats_on'] = static::categoriesEnabled()) {
            $aData['cats'] = $this->model->catsOptions('adm-shop-form', 0, _t('shops', 'Выберите категорию'));
            $aData['cats_in'] = $this->model->shopCategoriesIn($nShopID, ShopsCategoryIcon::SMALL);
        }
        if (static::abonementEnabled() && ! empty($aData['svc_abonement_id'])) {
            $aData['publicated'] = BBS::model()->itemsCount(array('shop_id' => $nShopID, 'is_publicated' => 1, 'status' => BBS::STATUS_PUBLICATED));
            $aData['abonement'] = $this->model->abonementData($aData['svc_abonement_id']);
        }

        $aData['import_access'] = config::sysAdmin('bbs.import.access', config::get('bbs_items_import', BBS::IMPORT_ACCESS_ADMIN, TYPE_UINT), TYPE_UINT);

        $aData['social_types'] = $this->socialLinksTypes(true);

        return $this->viewPHP($aData, 'admin.shops.form.info');
    }

    public function claims()
    {
        if (!$this->haveAccessTo('claims-listing')) {
            return $this->showAccessDenied();
        }

        if (Request::isAJAX()) {
            switch ($this->input->get('act', TYPE_STR)) {
                case 'delete': # удаляем жалобу
                {
                    if (!$this->haveAccessTo('claims-edit')) {
                        $this->ajaxResponse(Errors::ACCESSDENIED);
                    }

                    $nClaimID = $this->input->post('claim_id', TYPE_UINT);
                    if ($nClaimID) {
                        $aData = $this->model->claimData($nClaimID, array('id', 'viewed'));
                        if (empty($aData)) {
                            $this->ajaxResponse(Errors::IMPOSSIBLE);
                        }

                        $aResponse = array('counter_update' => false);
                        $res = $this->model->claimDelete($nClaimID);
                        if ($res && !$aData['viewed']) {
                            $this->claimsCounterUpdate(-1);
                            $aResponse['counter_update'] = true;
                        }
                        $aResponse['res'] = $res;
                        $this->ajaxResponse($aResponse);
                    }
                }
                break;
                case 'viewed': # отмечаем жалобу как прочитанную
                {
                    if (!$this->haveAccessTo('claims-edit')) {
                        $this->ajaxResponse(Errors::ACCESSDENIED);
                    }

                    $nClaimID = $this->input->post('claim_id', TYPE_UINT);
                    if ($nClaimID) {
                        $res = $this->model->claimSave($nClaimID, array('viewed' => 1));
                        if ($res) {
                            $this->claimsCounterUpdate(-1);
                        }
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
            }
            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        $aData = $this->input->getm(array(
                'shop'    => TYPE_UINT, # ID магазина
                'page'    => TYPE_UINT,
                'perpage' => TYPE_UINT,
                'status'  => TYPE_UINT,
            )
        );

        $aFilter = array();
        if ($aData['shop']) {
            $aFilter['shop_id'] = $aData['shop'];
        }
        switch ($aData['status']) {
            case 1: # Все
                break;
            default: # Просмотренные
                $aFilter['viewed'] = 0;
                break;
        }

        $nCount = $this->model->claimsListing($aFilter, true);

        $aPerpage = $this->preparePerpage($aData['perpage'], array(20, 40, 60));

        $sFilter = http_build_query($aData);
        unset($aData['page']);
        $oPgn = new Pagination($nCount, $aData['perpage'], $this->adminLink("claims&$sFilter&page=" . Pagination::PAGE_ID));
        $aData['pgn'] = $oPgn->view();

        $aData['claims'] = ($nCount > 0 ?
            $this->model->claimsListing($aFilter, false, $oPgn->getLimitOffset()) :
            array());
        foreach ($aData['claims'] as &$v) {
            $v['message'] = $this->getItemClaimText($v['reason'], $v['message']);
        }
        unset($v);

        $aData['perpage'] = $aPerpage;

        return $this->viewPHP($aData, 'admin.shops.claims');
    }

    public function ajax()
    {
        $bAccessShopsModerate = $this->haveAccessTo('shops-moderate');
        $aResponse = array();

        $sAct = $this->input->getpost('act', TYPE_STR);
        switch ($sAct) {
            case 'shop-info-popup':
            {
                /**
                 * Краткая информация о магазине (popup)
                 * @param integer 'id' ID магазина
                 */
                if (!$this->haveAccessTo('shops-listing')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nShopID = $this->input->get('id', TYPE_UINT);
                if (!$nShopID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->shopData($nShopID, array(
                        'id',
                        'user_id',
                        'created',
                        'claims_cnt',
                        'status',
                        'status_prev',
                        'status_changed',
                        'blocked_reason',
                        'moderated',
                        'link',
                        'title',
                        'svc_abonement_id',
                        'svc_abonement_expire',
                        'svc_abonement_termless',
                    )
                );
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData['items'] = $this->model->shopItemsCounter($nShopID);
                $aData['user'] = Users::model()->userData($aData['user_id'], array('email', 'name', 'blocked'));

                if (static::abonementEnabled() && $aData['svc_abonement_id']) {
                    $aData['publicated'] = BBS::model()->itemsCount(array('user_id' => $aData['user_id'], 'shop_id' => $nShopID, 'is_publicated' => 1, 'status' => BBS::STATUS_PUBLICATED));
                    $aData['abonement'] = $this->model->abonementData($aData['svc_abonement_id']);
                }

                echo $this->viewPHP($aData, 'admin.shops.info.popup');
                bff::shutdown();
            }
            break;
            case 'shop-status-block':
            {
                /**
                 * Блокировка магазина (если уже заблокирован => изменение причины блокировки)
                 * @param string 'blocked_reason' причина блокировки
                 * @param integer 'id' ID магазина
                 */
                if (!$bAccessShopsModerate) {
                    $this->errors->accessDenied();
                    break;
                }

                $bUnblock = $this->input->post('unblock', TYPE_UINT);
                $sBlockedReason = $this->input->postget('blocked_reason', TYPE_NOTAGS, array('len' => 1000));
                $nShopID = $this->input->post('id', TYPE_UINT);
                if (!$nShopID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->shopData($nShopID, array('status', 'status_prev', 'user_id'));
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $bBlocked = ($aData['status'] == self::STATUS_BLOCKED);

                $aUpdate = array(
                    'moderated'      => 1,
                    'blocked_reason' => $sBlockedReason,
                );

                if ($aData['user_id']) {
                    $aUserData = Users::model()->userData($aData['user_id'], array('user_id', 'blocked'));
                    if (!empty($aUserData['blocked'])) {
                        $this->errors->set(_t('shops', 'Для блокировки/разблокировки магазина, разблокируйте аккаунт владельца'));
                        break;
                    }
                }

                $bBlockedResult = $bBlocked;
                if (!$bBlocked) {
                    # блокируем
                    $aUpdate['status_prev'] = $aData['status'];
                    $aUpdate['status'] = self::STATUS_BLOCKED;
                    $bBlockedResult = true;
                } else {
                    if ($bUnblock) {
                        # разблокируем
                        switch ($aData['status_prev']) {
                            case self::STATUS_ACTIVE:
                            case self::STATUS_NOT_ACTIVE:
                                $aUpdate['status'] = $aData['status_prev'];
                                break;
                            case self::STATUS_BLOCKED:
                            {
                                $aUpdate['status'] = self::STATUS_NOT_ACTIVE;
                            }
                                break;
                            case self::STATUS_REQUEST:
                            {
                                $aUpdate['status'] = self::STATUS_ACTIVE;
                            }
                                break;
                        }
                        $aUpdate['status_prev'] = self::STATUS_BLOCKED;
                        $aResponse['reload'] = true;
                        $bBlockedResult = false;
                    }
                }

                $res = $this->model->shopSave($nShopID, $aUpdate);
                if ($res) {
                    if ($aData['user_id']) {
                        BBS::i()->onShopBlocked($nShopID, $bBlockedResult, $aData['user_id']);
                    }
                    if ($aData['status'] == self::STATUS_REQUEST) {
                        $this->updateRequestsCounter(-1);
                    }
                }

                $aResponse['blocked'] = $bBlockedResult;
                $aResponse['reason'] = $sBlockedReason;

            }
            break;
            case 'shop-status-approve':
            {
                if (!$bAccessShopsModerate) {
                    $this->errors->accessDenied();
                    break;
                }

                $nShopID = $this->input->post('id', TYPE_UINT);
                if (!$nShopID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->shopData($nShopID, array('status', 'user_id', 'title', 'link'));
                if (empty($aData)) {
                    $this->errors->impossible();
                    break;
                }

                $aUpdate = array(
                    'moderated' => 1
                );

                if ($aData['status'] == self::STATUS_BLOCKED) {
                    /**
                     * В случае если "Одобряем" заблокированный Магазин
                     * => значит после блокировки он был отредактирован пользователем
                     */
                    $aUpdate['status_prev'] = $aData['status'];
                    $aUpdate['status'] = self::STATUS_ACTIVE;
                } else {
                    if ($aData['status'] == self::STATUS_REQUEST) {
                        /**
                         * Одобряем заявку на открытие
                         */
                        $aUpdate['status_prev'] = $aData['status'];
                        $aUpdate['status'] = self::STATUS_ACTIVE;
                    }
                }

                $res = $this->model->shopSave($nShopID, $aUpdate);
                if (empty($res)) {
                    $this->errors->impossible();
                } else {
                    if ($aData['status'] == self::STATUS_REQUEST) {
                        $this->updateRequestsCounter(-1);
                        # Привязываем объявления пользователя к магазину
                        if (BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP)) {
                            BBS::model()->itemsLinkShop($aData['user_id'], $nShopID);
                        }
                        # Отправляем уведомление об открытии (активации) магазина
                        $aUserData = Users::model()->userDataEnotify($aData['user_id']);
                        if ($aUserData) {
                            bff::sendMailTemplate(
                                array(
                                    'name'       => $aUserData['name'],
                                    'email'      => $aUserData['email'],
                                    'user_id'    => $aData['user_id'],
                                    'shop_id'    => $nShopID,
                                    'shop_link'  => $aData['link'].'?alogin='.Users::loginAutoHash($aUserData),
                                    'shop_title' => $aData['title']
                                ),
                                'shops_open_success', $aUserData['email'], false, '', '', $aUserData['lang']
                            );
                        }
                    }
                    $this->updateModerationCounter();
                }

            }
            break;
            case 'shop-status-activate':
            case 'shop-status-deactivate':
            {
                /**
                 * Активация / деактивация магазина
                 * @param integer 'id' ID магазина
                 */
                if (!$bAccessShopsModerate) {
                    $this->errors->accessDenied();
                    break;
                }

                $nShopID = $this->input->post('id', TYPE_UINT);
                if (!$nShopID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->shopData($nShopID, array('id', 'status', 'moderated', 'user_id'));
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                if ($sAct == 'shop-status-activate') {
                    if (in_array($aData['status'], array(self::STATUS_BLOCKED, self::STATUS_REQUEST))) {
                        $this->errors->set($aData['moderated'] == 0
                            ? _t('shops', 'Невозможно выполнить активацию, поскольку магазин ожидает проверки')
                            : _t('shops', 'Невозможно выполнить активацию, поскольку магазин заблокирован'));
                        break;
                    }
                    $res = $this->model->shopSave($nShopID, array(
                            'status_prev = status',
                            'status'    => self::STATUS_ACTIVE,
                            'moderated' => 1,
                        )
                    );
                    if ($res && $aData['user_id'] && $aData['status'] == self::STATUS_NOT_ACTIVE) {
                        # публикуем объявления магазина
                        $publicateFilter = array(
                            'user_id'       => $aData['user_id'],
                            'shop_id'       => $nShopID,
                            'is_publicated' => 0,
                            'status'        => BBS::STATUS_PUBLICATED_OUT,
                            'status_prev'   => BBS::STATUS_PUBLICATED,
                        );
                        if (BBS::premoderation()) {
                            $publicateFilter[] = 'moderated > 0';
                        }
                        BBS::model()->itemsPublicate($publicateFilter);
                    }
                } else {
                    if ($aData['status'] != self::STATUS_ACTIVE) {
                        $this->errors->set(_t('shops', 'Возможность деактивации доступна только для активированных магазинов'));
                        break;
                    }
                    $res = $this->model->shopSave($nShopID, array(
                            'status_prev = status',
                            'status'    => self::STATUS_NOT_ACTIVE,
                            'moderated' => 1,
                        )
                    );
                    if ($res && $aData['user_id']) {
                        # снимаем с публикации объявления магазина
                        BBS::model()->itemsUnpublicate(array(
                                'shop_id' => $nShopID,
                                'user_id' => $aData['user_id'],
                                'is_publicated' => 1,
                                'status'  => BBS::STATUS_PUBLICATED,
                            )
                        );
                    }
                }
                if (empty($res)) {
                    $this->errors->impossible();
                }
            }
            break;
            case 'shop-delete':
            {
                if (!$this->haveAccessTo('shops-edit')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nShopID = $this->input->post('id', TYPE_UINT);
                if (!$nShopID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model()->shopData($nShopID, array('user_id'));
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }
                if (BBS::publisher(BBS::PUBLISHER_SHOP)) {
                    $itemTotal = $this->model->shopItemsCounter($nShopID);
                    if ($itemTotal > 0) {
                        $this->errors->set(_t('shops', 'Невозможно удалить магазин с объявлениями ([total])', array('total' => $itemTotal)));
                        break;
                    }
                }
                $res = $this->shopDelete($nShopID);
                if ($res) {
                    Users::i()->userSessionUpdate($aData['user_id'], array('shop_id' => 0), false);
                } else {
                    $this->errors->impossible();
                }
            }
            break;
            case 'user-autocomplete': # autocomplete
            {
                if (!$this->haveAccessTo('shops-listing')) {
                    $this->errors->accessDenied();
                    break;
                }
                $sQ = $this->input->post('q', TYPE_NOTAGS);
                $sQ = $this->input->cleanSearchString($sQ, 100);
                # получаем список подходящих по email пользователей, исключая:
                # - неактивированных пользователей
                $aUsers = Users::model()->usersList(array(
                        'activated' => 1,
                        ':email'    => array('email LIKE (:email)', ':email' => $sQ . '%'),
                        ':shop'     => 'shop_id = 0',
                    ), array('user_id', 'email'), false, $this->db->prepareLimit(0, 12), 'email'
                );
                $this->autocompleteResponse($aUsers, 'user_id', 'email');
            }
            break;
            default:
            {
                bff::hook('shops.admin.ajax.default.action', $sAct, $this);
                $this->errors->impossible();
            }
        }

        $this->ajaxResponseForm($aResponse);
    }

    //-------------------------------------------------------------------------------------------------------------------------------
    // категории

    public function categories_listing()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $aData = array();
        $sAct = $this->input->get('act', TYPE_STR);
        if (!empty($sAct)) {
            switch ($sAct) {
                case 'subs-list':
                {
                    $nCategoryID = $this->input->postget('category', TYPE_UINT);
                    if (!$nCategoryID) {
                        $this->ajaxResponse(Errors::UNKNOWNRECORD);
                    }

                    $aData['cats'] = $this->model->catsListing(array('pid' => $nCategoryID));
                    $aData['deep'] = static::CATS_MAXDEEP;

                    $this->ajaxResponse(array(
                            'list' => $this->viewPHP($aData, 'admin.categories.listing.ajax'),
                            'cnt'  => sizeof($aData['cats'])
                        )
                    );
                }
                break;
                case 'toggle':
                {

                    $nCategoryID = $this->input->get('rec', TYPE_UINT);
                    if ($this->model->catToggle($nCategoryID, 'enabled')) {
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'rotate':
                {

                    if ($this->model->catsRotate()) {
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'delete':
                {

                    $nCategoryID = $this->input->post('rec', TYPE_UINT);
                    if ($this->model->catDelete($nCategoryID)) {
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'dev-treevalidate':
                {
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    return $this->model->treeCategories->validate(true);
                }
                break;
                case 'dev-delete-all':
                {
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    if ($this->model->catDeleteAll()) {
                        $this->adminRedirect(Errors::SUCCESS, 'categories_listing');
                    }
                }
                break;
            }

            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        $aFilter = array();
        $sCatState = $this->input->cookie(bff::cookiePrefix() . 'shops_cats_state');
        $aCatExpandedID = (!empty($sCatState) ? explode('.', $sCatState) : array());
        $aCatExpandedID = array_map('intval', $aCatExpandedID);
        $aCatExpandedID[] = 1;
        $aFilter['pid'] = $aCatExpandedID;

        $aData['cats'] = $this->model->catsListing($aFilter);
        $aData['deep'] = static::CATS_MAXDEEP;
        $aData['cats'] = $this->viewPHP($aData, 'admin.categories.listing.ajax');

        return $this->viewPHP($aData, 'admin.categories.listing');
    }

    public function categories_add()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $aData = $this->validateCategoryData(0);

        if (Request::isPOST()) {

            if ($this->errors->no('shops.admin.category.submit',array('id'=>0,'data'=>&$aData))) {
                $nCategoryID = $this->model->catSave(0, $aData);
                if ($nCategoryID) {
                    # ...
                }
                $this->adminRedirect(Errors::SUCCESS, 'categories_listing');
            }
            $aData = $_POST;
        }

        $aData['id'] = 0;
        $aData['pid_options'] = $this->model->catsOptions('adm-category-form-add', $aData['pid']);

        return $this->viewPHP($aData, 'admin.categories.form');
    }

    public function categories_edit()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $nCategoryID = $this->input->getpost('id', TYPE_UINT);
        if (!$nCategoryID) {
            $this->adminRedirect(Errors::UNKNOWNRECORD, 'categories_listing');
        }

        $aData = $this->model->catData($nCategoryID, '*', true);
        if (!$aData) {
            $this->adminRedirect(Errors::UNKNOWNRECORD, 'categories_listing');
        }

        if (Request::isPOST()) {

            $aDataSave = $this->validateCategoryData($nCategoryID);

            if ($this->errors->no('shops.admin.category.submit',array('id'=>$nCategoryID,'data'=>&$aData))) {
                $res = $this->model->catSave($nCategoryID, $aDataSave);
                if (!empty($res) && $aData['keyword_edit'] != $aDataSave['keyword_edit'] && $aData['node'] > 1) {
                    # если keyword был изменен и есть вложенные подкатегории:
                    # > перестраиваем полный путь подкатегорий (и items::link)
                    $this->model->catSubcatsRebuildKeyword($nCategoryID, $aData['keyword_edit']);
                }

                if ($this->model->catIsMain($nCategoryID, $aDataSave['pid'])) {
                    $aUpdate = array();
                    $oIcon = static::categoryIcon($nCategoryID);
                    foreach ($oIcon->getVariants() as $iconField => $v) {
                        $oIcon->setVariant($iconField);
                        $aIconData = $oIcon->uploadFILES($iconField, true, false);
                        if (!empty($aIconData)) {
                            $aUpdate[$iconField] = $aIconData['filename'];
                        } else {
                            if ($this->input->post($iconField . '_del', TYPE_BOOL)) {
                                if ($oIcon->delete(false)) {
                                    $aUpdate[$iconField] = '';
                                }
                            }
                        }
                    }

                    if (!empty($aUpdate)) {
                        $this->model->catSave($nCategoryID, $aUpdate);
                    }
                }

                $this->adminRedirect(Errors::SUCCESS, 'categories_listing');
            }
            $aData = $_POST;
        }

        $aData['pid_options'] = $this->model->catParentsData($nCategoryID, array(
                'id',
                'title'
            ), false, $aData['pid'] != self::CATS_ROOTID
        );

        return $this->viewPHP($aData, 'admin.categories.form');
    }

    /**
     * Обработка данных категории
     * @param integer $nCategoryID ID категории
     * @return array $aData данные
     */
    protected function validateCategoryData($nCategoryID = 0)
    {
        $aData['pid'] = $this->input->postget('pid', TYPE_UINT);
        $aParams = array(
            'keyword_edit' => TYPE_NOTAGS,
            'mtemplate'    => TYPE_BOOL, # Использовать общий шаблон SEO
        );
        $this->input->postm($aParams, $aData);
        $this->input->postm_lang($this->model->langCategories, $aData);

        if (Request::isPOST()) {
            do {
                # основная категория обязательна
                if (!$aData['pid']) {
                    $this->errors->set(_t('shops', 'Укажите основную категорию'));
                    break;
                } else {
                    $parent = $this->model->catData($aData['pid'], array('id'));
                    if (empty($parent)) {
                        $this->errors->set(_t('shops', 'Основная категория указана некорректно'));
                        break;
                    }
                }
                # название обязательно
                if (isset($aData['title'][LNG]) && empty($aData['title'][LNG])) {
                    $this->errors->set(_t('shops', 'Укажите название'));
                    break;
                }
                foreach ($aData['title'] as $k => $v) {
                    $aData['title'][$k] = str_replace(array("'", '"'), '', $v);
                }

                # keyword
                $sKeyword = $aData['keyword_edit'];
                if (empty($sKeyword) && !empty($aData['title'][LNG])) {
                    $sKeyword = mb_strtolower(func::translit($aData['title'][LNG]));
                }
                $sKeyword = preg_replace('/[^\p{L}\w0-9_\-]/iu', '', mb_strtolower($sKeyword));
                if (empty($sKeyword)) {
                    $this->errors->set(_t('shops', 'Keyword указан некорректно'));
                    break;
                }
                # проверяем уникальность keyword'a в пределах основной категории
                $res = $this->model->catDataByFilter(array(
                        'pid'          => $aData['pid'],
                        'keyword_edit' => $sKeyword,
                        array('C.id!=:id', ':id' => $nCategoryID)
                    ), array('id')
                );
                if (!empty($res)) {
                    $this->errors->set(_t('shops', 'Указанный keyword уже используется, укажите другой'));
                    break;
                }
                $aData['keyword_edit'] = $sKeyword;

                # строим полный путь "parent-keyword / ... / keyword"
                $aKeywordsPath = array();
                if ($aData['pid'] > self::CATS_ROOTID) {
                    $aParentCatData = $this->model->catData($aData['pid'], array('keyword'));
                    if (empty($aParentCatData)) {
                        $this->errors->set(_t('shops', 'Основная категория указана некорректно'));
                        break;
                    } else {
                        $aKeywordsPath = explode('/', $aParentCatData['keyword']);
                    }
                }
                $aKeywordsPath[] = $sKeyword;
                $aKeywordsPath = join('/', $aKeywordsPath);
                $aData['keyword'] = $aKeywordsPath;

            } while (false);
        } else {
            if (!$nCategoryID) {
                $aData['mtemplate'] = 1;
            }
        }

        return $aData;
    }

    //-------------------------------------------------------------------------------------------------------------------------------
    // настройки

    public function settings()
    {
        if (!$this->haveAccessTo('settings')) {
            return $this->showAccessDenied();
        }

        $aData = array();
        return $this->viewPHP($aData, 'admin.settings');
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }

    # ------------------------------------------------------------------------------------------------------------------------------
    # Услуги

    public function svc_services()
    {
        if (!$this->haveAccessTo('svc')) {
            return $this->showAccessDenied();
        }

        $svc = Svc::model();

        if (Request::isPOST()) {
            $aResponse = array();

            switch ($this->input->getpost('act')) {
                case 'update':
                {

                    $nSvcID = $this->input->post('id', TYPE_UINT);
                    if (!$nSvcID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $aData = $svc->svcData($nSvcID, array('id', 'type', 'keyword'));
                    if (empty($aData) || $aData['type'] != Svc::TYPE_SERVICE) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $this->svcValidateData($nSvcID, Svc::TYPE_SERVICE, $aDataSave);

                    if ($this->errors->no('shops.admin.svc-service.submit',array('id'=>$nSvcID,'data'=>&$aDataSave,'before'=>$aData))) {
                        # загружаем иконки
                        $oIcon = static::svcIcon($nSvcID);
                        $oIcon->setAssignErrors(false);
                        foreach ($oIcon->getVariants() as $iconField => $v) {
                            $oIcon->setVariant($iconField);
                            $aIconData = $oIcon->uploadFILES($iconField, true, false);
                            if (!empty($aIconData)) {
                                $aDataSave[$iconField] = $aIconData['filename'];
                            } else {
                                if ($this->input->post($iconField . '_del', TYPE_BOOL)) {
                                    if ($oIcon->delete(false)) {
                                        $aDataSave[$iconField] = '';
                                    }
                                }
                            }
                        }

                        # сохраняем
                        $svc->svcSave($nSvcID, $aDataSave);
                    }

                }
                break;
                case 'reorder': # сортировка услуг
                {

                    $aSvc = $this->input->post('svc', TYPE_ARRAY_UINT);
                    $svc->svcReorder($aSvc, Svc::TYPE_SERVICE);
                }
                break;
                default:
                {
                    $this->errors->impossible();
                }
                break;
            }

            $this->iframeResponseForm($aResponse);
        }

        $aData = array(
            'svc' => $svc->svcListing(Svc::TYPE_SERVICE, $this->module_name),
        );
        unset($aData['svc']['abonement']);

        # Подготавливаем данные о региональной стоимости услуг для редактирования
        $aData['price_ex'] = $this->model->svcPriceExEdit();
        return $this->viewPHP($aData, 'admin.svc.services');
    }

    /**
     * Проверка данных услуги / пакета услуг
     * @param integer $nSvcID ID услуги / пакета услуг
     * @param integer $nType тип Svc::TYPE_
     * @param array $aData @ref проверенные данные
     */
    public function svcValidateData($nSvcID, $nType, &$aData)
    {
        $aParams = array(
            'price' => TYPE_PRICE,
        );

        if ($nType == Svc::TYPE_SERVICE) {
            $aSettings = array(
                'period' => TYPE_UINT, # период действия услуги
                'color'  => TYPE_NOTAGS, # цвет
                'on'     => TYPE_BOOL, # включена
            );

            $aData = $this->input->postm($aParams);
            $aData['settings'] = $this->input->postm($aSettings);
            $this->input->postm_lang($this->model->langSvcServices, $aData['settings']);
            $aData['title'] = $aData['settings']['title_view'][LNG];

            if ($aData['settings']['period'] < 1) {
                $aData['settings']['period'] = 1;
            }

            if (Request::isPOST()) {
                $priceEx = $this->input->post('price_ex', array(
                        TYPE_ARRAY_ARRAY,
                        'price'   => TYPE_PRICE,
                        'regions' => TYPE_ARRAY_UINT,
                    )
                );
                $this->model->svcPriceExSave($nSvcID, $priceEx);
            }
        }
    }

    /**
     * Услуга Абонемент, настройка тарифов
     * @return string HTML
     */
    public function svc_abonement()
    {
        if ( ! $this->haveAccessTo('abonements'))
            return $this->showAccessDenied();

        $sAct = $this->input->postget('act',TYPE_STR);
        if ( ! empty($sAct) || Request::isPOST())
        {
            $aResponse = array();
            switch ($sAct)
            {
                case 'add':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $aData = $this->validateAbonementData(0, $bSubmit);
                    if ($bSubmit)
                    {

                        if (!$this->errors->no('shops.admin.svc-abonement.submit',array('id'=>0,'data'=>&$aData))) {
                            break;
                        }

                        $nAbonementID = $this->model->abonementSave(0, $aData);
                        if (!$nAbonementID) {
                            $this->errors->set(_t('shops', 'Ошибка создания тарифа'));
                            break;
                        }

                        if ($this->errors->no()) {
                            # загружаем иконки
                            $oIcon = static::abonementIcon($nAbonementID);
                            $oIcon->setAssignErrors(false);
                            foreach ($oIcon->getVariants() as $iconField => $v) {
                                $oIcon->setVariant($iconField);
                                $aIconData = $oIcon->uploadFILES($iconField, true, false);
                                if (!empty($aIconData)) {
                                    $aData[$iconField] = $aIconData['filename'];
                                } else {
                                    if ($this->input->post($iconField . '_del', TYPE_BOOL)) {
                                        if ($oIcon->delete(false)) {
                                            $aData[$iconField] = '';
                                        }
                                    }
                                }
                            }

                            # сохраняем
                            $this->model->abonementSave($nAbonementID, $aData);
                        }

                        if ($nAbonementID > 0) {
                            $this->adminRedirect(Errors::SUCCESS, 'svc_abonement');
                        }
                    }

                    $aData['id'] = 0;
                    $aData['svc'] = Svc::model()->svcListing(0, 'bbs');
                    unset($aData['svc']['limit']);
                    $aResponse['form'] = $this->viewPHP($aData, 'admin.abonements.form');
                } break;
                case 'edit':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $nAbonementID = $this->input->postget('id', TYPE_UINT);
                    if ( ! $nAbonementID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    if ($bSubmit)
                    {

                        $aData = $this->validateAbonementData($nAbonementID, $bSubmit);
                        if ($this->errors->no('shops.admin.svc-abonement.submit',array('id'=>$nAbonementID,'data'=>&$aData))) {
                            # загружаем иконки
                            $oIcon = static::abonementIcon($nAbonementID);
                            $oIcon->setAssignErrors(false);
                            foreach ($oIcon->getVariants() as $iconField => $v) {
                                $oIcon->setVariant($iconField);
                                $aIconData = $oIcon->uploadFILES($iconField, true, false);
                                if (!empty($aIconData)) {
                                    $aData[$iconField] = $aIconData['filename'];
                                } else {
                                    if ($this->input->post($iconField . '_del', TYPE_BOOL)) {
                                        if ($oIcon->delete(false)) {
                                            $aData[$iconField] = '';
                                        }
                                    }
                                }
                            }

                            # сохраняем
                            $this->model->abonementSave($nAbonementID, $aData);
                            $cronManager = bff::cronManager();
                            if ($cronManager->isEnabled()) {
                                $cronManager->executeOnce('shops', 'svc_abonement_edit', array('id' => $nAbonementID), $nAbonementID);
                            }
                            $this->adminRedirect(Errors::SUCCESS, ($this->input->post('return', TYPE_BOOL) ? 'svc_abonement' : 'svc_abonement&act=edit&id='.$nAbonementID) );
                        }

                        $aData['id'] = $nAbonementID;
                    } else {
                        $aData = $this->model->abonementData($nAbonementID, true);
                        if (empty($aData)) { $this->errors->unknownRecord(); break; }
                    }
                    $aData['svc'] = Svc::model()->svcListing(0, 'bbs');
                    unset($aData['svc']['limit']);
                    $aData['cronEnabled'] = bff::cronManager()->isEnabled();
                    $aResponse['form'] = $this->viewPHP($aData, 'admin.abonements.form');
                } break;
                case 'toggle':
                {
                    $nAbonementID = $this->input->postget('id', TYPE_UINT);
                    if (!$nAbonementID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $sToggleType = $this->input->get('type', TYPE_STR);
                    $this->model->abonementToggle($nAbonementID, $sToggleType);
                } break;
                case 'rotate':
                {

                    if ($this->model->abonementRotate()) {
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'delete':
                {
                    $nAbonementID = $this->input->postget('id', TYPE_UINT);
                    $nAbonNewID = $this->input->postget('newID', TYPE_UINT);
                    if (!$nAbonementID) {
                        $this->errors->impossible();
                        break;
                    }

                    $res = $this->model->abonementDelete($nAbonementID, $nAbonNewID);
                    if ( ! $res) { $this->errors->impossible(); break; }
                    else {
                        # если тариф используется, просим заменить его
                        if(isset($res['used'])){
                            $aResponse['select'] = $this->model()->abonementsListing(array('enabled' => 1));
                            $this->errors->set(
                                _t('shops', 'Данный тариф использует [cnt]. Выберите тариф для замены из списка и повторите попытку.',
                                    array('cnt' => tpl::declension($res['used'], _t('shops', 'магазин;магазина;магазинов')))),
                                'false', 'used'
                            );
                            break;
                        }
                    }
                } break;
                case 'abonement-set-default':
                {
                    $nAbonementID = $this->input->postget('id', TYPE_UINT);
                    if(!$nAbonementID){
                        $this->errors->impossible();
                        break;
                    }

                    $aResponse['success'] = $this->model()->abonementSetDefault($nAbonementID);
                } break;
                case 'activate':
                {
                    $nAbonementID = $this->input->postget('id', TYPE_UINT);
                    if ( ! $nAbonementID) {
                        $this->errors->impossible();
                        break;
                    }

                    $cronManager = bff::cronManager();
                    if ( ! $cronManager->isEnabled()) {
                        $this->errors->set(_t('shops', 'CronManager не запущен'));
                        break;
                    }

                    $aResponse['success'] = $cronManager->executeOnce('shops', 'svc_abonement_activate_all', array('id' => $nAbonementID));

                } break;
                default: $aResponse = false;
            }

            if ($aResponse !== false && Request::isAJAX()) $this->ajaxResponseForm($aResponse);
        }

        $f = array();
        $this->input->postgetm(array(
            'page'   => TYPE_UINT,
        ), $f);

        # формируем фильтр списка тарифов
        $sql = array();
        $sqlOrder = 'num';
        $mPerpage = 15;
        $aData['pgn'] = '';

        if ($mPerpage!==false) {
            $nCount = $this->model->abonementsListing($sql, true);
            $oPgn = new Pagination($nCount, $mPerpage, '#', 'jShopsAbonementsList.page(' . Pagination::PAGE_ID . '); return false;');
            $aData['list'] = $this->model->abonementsListing($sql, false, $oPgn->getLimitOffset(), $sqlOrder);
        } else {
            $aData['list'] = $this->model->abonementsListing($sql, false, '', $sqlOrder);
        }

        $aData['list'] = $this->viewPHP($aData, 'admin.abonements.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                'list'=>$aData['list'],
                'pgn'=>$aData['pgn'],
            ));
        }

        $aData['f'] = $f;
        $aData['id'] = $this->input->get('id', TYPE_UINT);
        $aData['act'] = $sAct;
        tpl::includeJS(array('tablednd'), true);

        return $this->viewPHP($aData, 'admin.abonements.listing');
    }

    /**
     * Обрабатываем параметры запроса
     * @param integer $nAbonementID ID тарифа или 0
     * @param boolean $bSubmit выполняем сохранение/редактирование
     * @return array параметры
     */
    protected function validateAbonementData($nAbonementID, $bSubmit)
    {
        $this->input->postm(array(
            'is_default'        => TYPE_BOOL, # по-умолчанию
            'enabled'           => TYPE_BOOL, # включена
            'one_time'          => TYPE_BOOL, # единоразовый
            'price_free'        => TYPE_BOOL, # бесплатно
            'price_free_period' => TYPE_UINT, # кол-во месяцев бесплатно
            'items'             => TYPE_UINT, # кол-во объявлений
            'import'            => TYPE_BOOL, # импорт
            'svc_mark'          => TYPE_BOOL, # услуга выделения
            'svc_fix'           => TYPE_BOOL, # услуга закрепления
            'price'             => TYPE_ARRAY_ARRAY, # цены
            'discount'          => TYPE_ARRAY_UINT, # скидки
        ), $aData);

        $this->input->postm_lang($this->model->langAbonements, $aData);

        if ($bSubmit) {
            if (empty($aData['title'][LNG])) {
                $this->errors->set(_t('shops', 'Укажите название тарифа'), 'title');
            } else {
                if (mb_strlen($aData['title'][LNG]) < 2) {
                    $this->errors->set(_t('shops', 'Название тарифа слишком короткое'), 'title');
                }
            }
        }

        # приводим цены к виду месяц => цена
        $aMass = array();
        foreach ($aData['price'] as $v) {
            $this->input->clean_array($v, array(
                'm' => TYPE_UINT,
                'p' => TYPE_PRICE,
            ));
            if ($v['m'] > 0 && !isset($aMass[$v['m']])) {
                $aMass[$v['m']] = $v['p'];
            }
        }
        $aData['price'] = $aMass;
        return $aData;
    }

}