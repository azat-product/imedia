<?php

/**
 * Права доступа группы:
 *  - bbs: Объявления
 *      - items-listing: Просмотр списка объявлений
 *      - items-edit: Управление объявлениями (добавление/редактирование/удаление)
 *      - items-moderate: Модерация объявлений (блокирование/одобрение/продление/активация)
 *      - items-comments: Управление комментариями
 *      - items-press: Управление печатью в прессу
 *      - items-import: Импорт объявлений
 *      - items-export: Управление печатью в прессу
 *      - claims-listing: Просмотр списка жалоб
 *      - claims-edit: Управление жалобами (модерация/удаление)
 *      - categories: Управление категориями
 *      - types: Управление типами категорий
 *      - svc: Управление услугами
 *      - settings: Дополнительные настройки
 */
class BBS_ extends BBSBase
{

    public function init()
    {
        parent::init();
    }

    # -------------------------------------------------------------------------------------------------------------------------------
    # объявления

    public function listing()
    {
        if (!$this->haveAccessTo('items-listing')) {
            return $this->showAccessDenied();
        }
        $aData = array('f' => array(), 'shops_on' => bff::shopsEnabled());

        $sAction = $this->input->get('act', TYPE_STR);
        if ($sAction) {
            $aResponse = array();
            switch ($sAction) {
                case 'delete':
                {
                    if (!$this->haveAccessTo('items-edit')) {
                        $this->errors->accessDenied();
                        break;
                    }

                    $nItemID = $this->input->postget('id', TYPE_UINT);
                    if (!$nItemID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $aDataEmail = $this->model->itemData2Email($nItemID);

                    $res = $this->model->itemsDelete(array($nItemID), true);
                    # объявление было удалено
                    if ($res) {
                        if ($aDataEmail !== false) {
                            bff::sendMailTemplate(array(
                                    'name'       => $aDataEmail['name'],
                                    'email'      => $aDataEmail['email'],
                                    'user_id'    => $aDataEmail['user_id'],
                                    'item_id'    => $aDataEmail['item_id'],
                                    'item_link'  => $aDataEmail['item_link'],
                                    'item_title' => $aDataEmail['item_title'],
                                ), 'bbs_item_deleted',
                                $aDataEmail['email'],
                                false, '', '', $aDataEmail['lang']
                            );
                        }
                    } else {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'dev-items-links-rebuild':
                {
                    if (!FORDEV) {
                        $this->showAccessDenied();
                        break;
                    }

                    $data = array();
                    $cronManager = bff::cronManager();
                    if ($cronManager->isEnabled()) {
                        $cronManager->executeOnce('bbs', 'itemsLinksRebuild');
                        $data['cronManager'] = 1;
                    } else {
                        $this->errors->set(_t('bbs', 'Не запущен cron-manager'));
                    }
                    return $this->viewPHP($data, 'admin.info');
                }
                break;
                case 'dev-items-publicate-all-unpublicated':
                {
                    if (!FORDEV) {
                        $this->showAccessDenied();
                    }

                    $res = $this->model->itemsPublicateAllUnpublicated();
                    if ($res > 0) {
                        $this->moderationCounterUpdate();
                    }

                    $this->adminRedirect(Errors::SUCCESS);
                }
                break;
                case 'dev-items-cats-rebuild':
                {
                    if (!FORDEV) {
                        $this->showAccessDenied();
                        break;
                    }
                    $data = array();
                    $cronManager = bff::cronManager();
                    if ($cronManager->isEnabled()) {
                        $cronManager->executeOnce('bbs', 'itemsCatsRebuild');
                        $data['cronManager'] = 1;
                    }else{
                        $this->errors->set(_t('bbs', 'Не запущен cron-manager'));
                    }
                    return $this->viewPHP($data, 'admin.info');
                }
                break;
                case 'dev-items-default-currency':
                {
                    if (!FORDEV) {
                        $this->showAccessDenied();
                        break;
                    }
                    $this->model->itemsDefaultCurrency();
                    $this->adminRedirect(Errors::SUCCESS);
                }
                break;
                case 'moderation-counter':
                {
                    $aResponse['mod_counter'] = $this->moderationCounterUpdate(false);
                } break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $f = $this->input->postgetm(array(
            'page'          => TYPE_UINT,
            'cat'           => TYPE_UINT,
            'region'        => TYPE_UINT,
            'status'        => TYPE_UINT,
            'title'         => array(TYPE_NOTAGS, 'len' => 150), # ID / Заголовок объявления / Телефон (объявления или пользователя)
            'uid'           => array(TYPE_NOTAGS, 'len' => 150), # ID / E-mail пользователя 
            'shopid'        => TYPE_UINT, # ID магазина
            'moderate_list' => TYPE_UINT, # доп. фильтр объявлений "на модерации"
        ));

        # формируем фильтр списка объявлений
        # - исключаем формирующиеся(недооформленные) объявления из списка
        $limit = config::sysAdmin('bbs.admin.items.list.limit', 20, TYPE_UINT);
        $sql = array();
        $orderBy = 'id DESC';

        switch ($f['status']) {
            case 0:
            { # Опубликованные
                $sql['is_publicated'] = 1;
                $sql['status'] = self::STATUS_PUBLICATED;
            }
            break;
            case 2:
            { # Снятые с публикации
                $sql['is_publicated'] = 0;
                $sql['status'] = self::STATUS_PUBLICATED_OUT;
            }
            break;
            case 3:
            { # На модерации
                $sql['is_moderating'] = 1;
                $limit = config::sysAdmin('bbs.admin.items.list.moderate.limit', $limit, TYPE_UINT);
                if ($f['moderate_list'] == 1) { # отредактированные
                    $sql['moderated'] = array('>', 1);
                } elseif ($f['moderate_list'] == 2) { # импортированные
                    $sql['import'] = array('>', 0);
                }
            }
            break;
            case 4:
            { # Неактивированные
                $sql['is_publicated'] = 0;
                $sql['status'] = self::STATUS_NOTACTIVATED;
            }
            break;
            case 5:
            { # Заблокированные
                $sql['is_publicated'] = 0;
                $sql['status'] = self::STATUS_BLOCKED;
            }
            break;
            case 6:
            { # Удаленные
                $sql['is_publicated'] = 0;
                $sql['status'] = self::STATUS_DELETED;
            }
            break;
            case 7:
            { # Все
                $orderBy = 'id DESC';
            }
            break;
            default: {
                bff::hook('bbs.admin.item.list.status.filter', $f['status'], array(
                    'filter' => &$f, 'sql' => &$sql, 'orderBy' => &$orderBy,
                ));
            } break;
        }

        if ($f['cat'] > 0) {
            $sql[':cat-filter'] = $f['cat'];
        }

        $nUserID = 0;
        if ( ! empty($f['uid'])) {
            $userFilter = array();
            if ($this->input->isEmail($f['uid'])) {
                $userFilter['email'] = $f['uid'];
            } else if (is_numeric($f['uid'])) {
                $userFilter['user_id'] = intval($f['uid']);
            }
            if (!empty($userFilter)) {
                $aUserData = Users::model()->userDataByFilter($userFilter, array('user_id','shop_id'));
                if ( ! empty($aUserData['user_id'])) {
                    $nUserID = intval($aUserData['user_id']);
                    $sql['user_id'] = $nUserID;
                    if ($aUserData['shop_id'] > 0) {
                        $sql['shop_id'] = array(0, intval($aUserData['shop_id']));
                    } else {
                        $sql['shop_id'] = 0;
                    }
                }
            }
        }

        if ($f['shopid'] > 0 && $aData['shops_on']) {
            if ( ! $nUserID) {
                $shopData = Shops::model()->shopData($f['shopid'], array('user_id'));
                if ( ! empty($shopData['user_id'])) {
                    $sql['user_id'] = intval($shopData['user_id']);
                }
            }
            $sql['shop_id'] = $f['shopid'];
        } else {
            $f['shopid'] = '';
        }

        if ( ! empty($f['title'])) {
            $query = $f['title'];
            if (is_numeric($query)) {
                if (mb_strlen($query) >= 8) {
                    # ищем еще и номер телефона
                    if ($this->getItemContactsFromProfile()) {
                        if (empty($f['uid'])) {
                            $filter = array(
                                array('(phone LIKE :phone OR phones LIKE :phone)', ':phone' => '%'.$query.'%')
                            );
                            $users = Users::model()->usersList($filter, array('user_id'), false, $this->db->prepareLimit(0,50));
                            if (!empty($users)) {
                                $sql['user_id'] = array_keys($users);
                            }
                        }
                    } else {
                        $sql[':query'] = $query;
                    }
                } else {
                    $sql['id'] = intval($query);
                }
            } else {
                $sql[':query'] = $query;
            }
        }

        if ($f['region']) {
            $sql[':region-filter'] = $f['region'];
        }

        $nCount = $this->model->itemsListing($sql, true);
        $aData['f'] = $f;

        $oPgn = new Pagination($nCount, $limit, '#', 'jItems.page(' . Pagination::PAGE_ID . '); return false;');
        $aData['pgn'] = $oPgn->view();
        $aData['list'] = $this->model->itemsListing($sql, false, array(
            'limit'   => $oPgn->getLimit(),
            'offset'  => $oPgn->getOffset(),
            'orderBy' => $orderBy,
        ));
        $aData['list'] = $this->viewPHP($aData, 'admin.items.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                'list'   => $aData['list'],
                'pgn'    => $aData['pgn'],
                'filter' => $f,
                'mod_counter' => $this->moderationCounterUpdate(false),
            ));
        }

        $aData['cats_select'] = $this->model->catsOptions('adm-items-listing', $f['cat'], _t('bbs', 'Все разделы'));

        return $this->viewPHP($aData, 'admin.items.listing');
    }


    public function listing_press()
    {
        if (!$this->haveAccessTo('items-press')) {
            return $this->showAccessDenied();
        }

        $sAction = $this->input->getpost('act', TYPE_STR);
        if (!empty($sAction)) {
            $aResponse = array();
            switch ($sAction) {
                case 'press':
                {
                    # тип: 1 - отмеченные, 2 - все
                    $nType = $this->input->post('type', TYPE_UINT);

                    $sDate = $this->input->post('date', TYPE_STR);
                    $nDate = (!empty($sDate) ? strtotime($sDate) : false);
                    if (empty($nDate) || $nDate === -1) {
                        $this->errors->set(_t('bbs', 'Дата публикации указана некорректно'));
                        break;
                    }


                    $aFilter = array();
                    if ($nType == 1) { # отмеченные
                        $aItemsID = $this->input->post('i', TYPE_ARRAY_UINT);
                        if (empty($aItemsID)) {
                            $this->errors->set(_t('bbs', 'Необходимо отметить объявления для печати'));
                            break;
                        }
                        $aFilter['id'] = $aItemsID;
                    } else { # все
                        $aFilter['svc_press_status'] = self::PRESS_STATUS_PAYED;
                        $aFilter['status'] = array(self::STATUS_PUBLICATED, self::STATUS_PUBLICATED_OUT);
                    }
                    $aResponse['updated'] = $this->model->itemsUpdateByFilter(array(
                        'svc_press_date'   => date('Y.m.d', $nDate),
                        'svc_press_status' => self::PRESS_STATUS_PUBLICATED,
                    ), $aFilter, array(
                        'context' => 'admin-press-'.$sAction,
                    ));
                    if ( ! $aResponse['updated']) {
                        $this->errors->set(_t('bbs', 'Нет объявлений доступных для печати'));
                        break;
                    }

                    $this->pressCounterUpdate(-intval($aResponse['updated']));

                    $aResponse['updated'] = tpl::declension($aResponse['updated'], _t('bbs', 'объявление;объявления;объявлений'));
                }
                break;
                case 'export':
                case 'export-check':
                {
                    # тип: 1 - отмеченные, 2 - все
                    $nType = $this->input->postget('type', TYPE_UINT);
                    $aFilter = array();
                    if ($nType == 1) { # отмеченные
                        $aItemsID = $this->input->postget('i', TYPE_ARRAY_UINT);
                        if (empty($aItemsID)) {
                            $this->errors->set(_t('bbs', 'Необходимо отметить объявления для печати'));
                            break;
                        }
                        $aFilter['id'] = $aItemsID;
                    } else { # все
                        $aFilter['svc_press_status'] = self::PRESS_STATUS_PAYED;
                        $aFilter['status'] = array(self::STATUS_PUBLICATED, self::STATUS_PUBLICATED_OUT);
                    }
                    $itemsCount = $this->model->itemsCount($aFilter, array('context'=>'admin-press-'.$sAction));
                    if ( ! $itemsCount) {
                        $this->errors->set(_t('bbs', 'Нет объявлений доступных для печати'));
                        break;
                    }
                    if ($sAction == 'export') {
                        $import = $this->itemsImport();

                        $filename = 'press';

                        header('Content-Disposition: attachment; filename=' . $filename . '.xml');
                        header("Content-Type: application/force-download");
                        header('Pragma: private');
                        header('Cache-control: private, must-revalidate');

                        echo $import->exportPrintXML($aFilter, $this->locale->getCurrentLanguage());
                        bff::shutdown();
                    }
                } break;
            }
            $this->ajaxResponseForm($aResponse);
        }

        $aData = array('f' => array());
        $aData['orders'] = array('svc_press_date' => 'desc');

        $f = $this->input->postgetm(array(
            'page'    => TYPE_UINT,
            'status'  => TYPE_UINT,
            'pressed' => TYPE_STR,
        ));

        # формируем фильтр списка объявлений
        $sql = array();

        switch ($f['status']) {
            case static::PRESS_STATUS_PUBLICATED: # Опубликованные в прессе
            {
                $sql['svc_press_status'] = static::PRESS_STATUS_PUBLICATED;
                $nPressed = strtotime($f['pressed']);
                if (!empty($nPressed) && $nPressed !== -1) {
                    $sql['svc_press_date'] = date('Y-m-d', $nPressed);
                }
            }
            break;
            case static::PRESS_STATUS_PUBLICATED_EARLIER: # Предыдущие публикации
            {
                $sql['svc_press_status'] = 0;
                $nPressed = strtotime($f['pressed']);
                if (!empty($nPressed) && $nPressed !== -1) {
                    $sql['svc_press_date_last'] = date('Y-m-d', $nPressed);
                } else {
                    $sql['svc_press_date_last'] = array('!=','0000-00-00');
                }
            }
            break;
            case static::PRESS_STATUS_PAYED: # Ожидают публикации в прессе
            default:
            {
                $sql['svc_press_status'] = static::PRESS_STATUS_PAYED;
                $f['status'] = static::PRESS_STATUS_PAYED;
            }
            break;
        }
        $nCount = $this->model->itemsListing($sql, true);
        $f += $this->prepareOrder($orderBy, $orderDirection, 'svc_press_date' . tpl::ORDER_SEPARATOR . 'desc', $aData['orders']);
        $f['order'] = $orderBy . tpl::ORDER_SEPARATOR . $orderDirection;
        $aData['f'] = $f;
        $oPgn = new Pagination($nCount, 15, '#', 'jItemsPress.page(' . Pagination::PAGE_ID . '); return false;');
        $aData['pgn'] = $oPgn->view();
        $aData['list'] = $this->model->itemsListing($sql, false, array(
            'limit'   => $oPgn->getLimit(),
            'offset'  => $oPgn->getOffset(),
            'orderBy' => "$orderBy $orderDirection",
        ));
        $aData['list'] = $this->viewPHP($aData, 'admin.items.press.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        $aData['tabs'] = array(
            static::PRESS_STATUS_PAYED      => array('t' => _t('bbs', 'Ожидают публикации')),
            static::PRESS_STATUS_PUBLICATED => array('t' => _t('bbs', 'Опубликованные')),
            static::PRESS_STATUS_PUBLICATED_EARLIER  => array('t' => _t('bbs', 'Предыдущие публикации')),
        );

        return $this->viewPHP($aData, 'admin.items.press');
    }


    public function add()
    {
        if (!$this->haveAccessTo('items-edit')) {
            return $this->showAccessDenied();
        }

        $this->validateItemData($aData, 0);

        if (Request::isPOST()) { # ajax
            $aResponse = array('id' => 0);
            $nUserID = $this->input->post('user_id', TYPE_UINT);
            if (!$nUserID) {
                $this->errors->set(_t('bbs', 'E-mail адрес пользователя указан некорректно'), 'email');
            } else {
                $aUserData = Users::model()->userData($nUserID, array('name', 'phones', 'contacts', 'shop_id'));
                if (empty($aUserData)) {
                    $this->errors->set(_t('bbs', 'E-mail адрес пользователя указан некорректно'), 'email');
                } else {
                    foreach ($aUserData as $k => $v) {
                        $aData[$k] = $aUserData[$k];
                    }
                    # Контакты (masked версия)
                    $aContacts = array(
                        'phones' => array(),
                    );
                    foreach ($aUserData['phones'] as $v) $aContacts['phones'][] = $v['m'];
                    $aData['contacts'] = json_encode($aUserData['contacts']);
                }
                $aData['shop_id'] = $this->publisherCheck($aUserData['shop_id'], 'shop');
                if ($aData['shop_id'] && !Shops::model()->shopActive($aData['shop_id'])) {
                    $this->errors->set(_t('bbs', 'Размещение объявления доступно только от активированного магазина'));
                }
            }

            if ($this->errors->no('bbs.admin.item.submit',array('id'=>0,'data'=>&$aData))) {
                # публикуем
                $aData['user_id'] = $nUserID;
                $aData['publicated'] = $this->db->now();
                $aData['publicated_order'] = $this->db->now();
                $aData['publicated_to'] = $this->getItemPublicationPeriod(isset($aData['publicated_period']) ? $aData['publicated_period'] : 0);
                $aData['status'] = self::STATUS_PUBLICATED;
                $aData['moderated'] = 1;

                $nItemID = $this->model->itemSave(0, $aData, 'd');
                if ($nItemID > 0) {
                    $aResponse['id'] = $nItemID;
                    $this->itemImages($nItemID)->saveTmp('img');
                }
            }
            $this->ajaxResponseForm($aResponse);
        }

        $aData['id'] = 0;
        $aData['images'] = array();
        $aData['imgcnt'] = 0;
        $aData['img'] = $this->itemImages(0);
        # выбор категории
        $aData['cats'] = $this->model->catsOptionsByLevel(array(), array('empty' => _t('', 'Выбрать')));
        $aData['cat'] = array();
        # город и метро
        $aData['city_data'] = array();
        $aData['city_metro'] = Geo::cityMetro();
        if (Geo::coveringType(Geo::COVERING_CITY)) {
    		$aData['city_id'] = Geo::coveringRegion();
    		$aData['city_data'] = Geo::regionData($aData['city_id']);
            $aData['city_metro'] = Geo::cityMetro($aData['city_id'], 0, true);
		}

        return $this->viewPHP($aData, 'admin.form');
    }

    public function edit()
    {
        if (!$this->haveAccessTo('items-edit')) {
            return $this->showAccessDenied();
        }

        $nItemID = $this->input->getpost('id', TYPE_UINT);
        if (!$nItemID) {
            $this->showImpossible(true);
        }

        if (Request::isPOST()) { # ajax
            $aResponse = array();
            $sAction = $this->input->get('act', TYPE_STR);
            switch ($sAction) {
                case 'info': # сохранение данных вкладки "Описание"
                {
                    $aItemData = $this->model->itemData($nItemID, array(
                        'user_id','shop_id','cat_id','city_id',
                        'video','video_embed',
                        'status','moderated','publicated_order',
                        'imgcnt','price','title','descr',
                    ), true);
                    $this->validateItemData($aData, $nItemID, $aItemData);

                    if (static::publisher(static::PUBLISHER_USER_OR_SHOP)) {
                        $aData['shop_id'] = $this->publisherCheck($aItemData['user_shop_id'], 'shop');
                        if ($aData['shop_id'] && !Shops::model()->shopActive($aData['shop_id'])) {
                            $this->errors->set(_t('bbs', 'Размещение объявления доступно только от активированного магазина'));
                        }
                    }
                    if ($this->errors->no('bbs.admin.item.submit',array('id'=>$nItemID,'data'=>&$aData,'before'=>$aItemData))) {
                        if ($aItemData['moderated'] == 1) {
                            $aData['moderated'] = 1; # Если сохраняем промодерированное объявления, то обновим промодерированные данные
                        }
                        $this->model->itemSave($nItemID, $aData, 'd');
                    }

                    $this->ajaxResponseForm($aResponse);
                }
                break;
                case 'comments-init': # инициализация вкладки "Комментарии"
                {
                    $aData['id'] = $nItemID;
                    $aData['edit_allowed'] = $this->haveAccessTo('items-comments');
                    $aData['comments'] = $this->itemComments()->admListing($nItemID);
                    $aResponse['html'] = $this->viewPHP($aData, 'admin.form.comments');
                }
                break;
                case 'claims-init': # инициализация вкладки "Жалобы"
                {
                    $aData['id'] = $nItemID;
                    $aData['edit_allowed'] = $this->haveAccessTo('claims-edit');
                    $aData['claims'] = $this->model->claimsListing(array('item_id' => $nItemID));
                    foreach ($aData['claims'] as &$v) {
                        $v['message'] = $this->getItemClaimText($v['reason'], $v['message']);
                    }
                    unset($v);

                    $aResponse['html'] = $this->viewPHP($aData, 'admin.items.claims');
                }
                break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->adminEditPrepare($nItemID);

        if ( ! $aData) {
            $this->showImpossible(true);
        }



        $this->itemComments()->admListingIncludes(); # подключаем js+css для вкладки "Комментарии" / "Жалобы"
        return $this->viewPHP($aData, 'admin.form');
    }

    public function img()
    {
        if (!$this->haveAccessTo('items-edit')) {
            return $this->showAccessDenied();
        }

        $nItemID = $this->input->getpost('item_id', TYPE_UINT);
        $oImages = $this->itemImages($nItemID);
        $aResponse = array();
        $sAction = $this->input->getpost('act');

        switch ($sAction) {
            case 'upload': # загрузка изображений
            {

                $mResult = $oImages->uploadQQ();
                $aResponse = array('success' => ($mResult !== false && $this->errors->no()));

                if ($mResult !== false) {
                    $aResponse = array_merge($aResponse, $mResult);
                    $aResponse = array_merge($aResponse, $oImages->getURL($mResult, array(
                                BBSItemImages::szSmall,
                                BBSItemImages::szMedium,
                                BBSItemImages::szView
                            ), empty($nItemID)
                        )
                    );
                    $aResponse['rotate'] = $oImages->rotateAvailable($mResult, ! $nItemID);
                }
                $aResponse['errors'] = $this->errors->get();
                $this->ajaxResponse($aResponse, true, false, true);
            }
            break;
            case 'rotate': # поворот изображения
            {
                $nImageID = $this->input->post('image_id', TYPE_UINT);
                $sFilename = $this->input->post('filename', TYPE_STR);
                if (!$nImageID && empty($sFilename)) {
                    $this->errors->impossible();
                    break;
                }
                if ($nItemID && $nImageID) {
                    # повернем изображение по ID
                    $result = $oImages->rotate($nImageID, -90);
                } else {
                    # повернем временное изображений
                    $result = $oImages->rotateTmp($sFilename, -90);
                }
                if ($result !== false) {
                    $aResponse = array_merge($aResponse, $result);
                    $aResponse = array_merge($aResponse, $oImages->getURL($result, array(
                        BBSItemImages::szSmall,
                        BBSItemImages::szMedium,
                        BBSItemImages::szView
                    ), empty($nItemID)
                    )
                    );
                }
            }
            break;
            case 'saveorder': # сохранение порядка изображений
            {
                $img = $this->input->post('img', TYPE_ARRAY);
                if (!$oImages->saveOrder($img, false, true)) {
                    $this->errors->impossible();
                }
            }
            break;
            case 'delete': # удаление изображений
            {
                $nImageID = $this->input->post('image_id', TYPE_UINT);
                $sFilename = $this->input->post('filename', TYPE_STR);
                if (!$nImageID && empty($sFilename)) {
                    $this->errors->impossible();
                    break;
                }
                if ($nImageID) {
                    $bSuccess = $oImages->deleteImage($nImageID);
                    if ($bSuccess) {
                        # фото удалено, отправляем email-уведомление
                        $aDataEmail = $this->model->itemData2Email($nItemID);

                        $loginAuto = Users::loginAutoHash($aDataEmail);
                        $aDataEmail['item_link'] .= '?alogin='.$loginAuto;

                        if ($aDataEmail !== false && ! User::isCurrent(intval($aDataEmail['user_id']))) {
                            bff::sendMailTemplate(array(
                                    'name'       => $aDataEmail['name'],
                                    'email'      => $aDataEmail['email'],
                                    'user_id'    => $aDataEmail['user_id'],
                                    'item_id'    => $aDataEmail['item_id'],
                                    'item_link'  => $aDataEmail['item_link'],
                                    'item_title' => $aDataEmail['item_title'],
                                ), 'bbs_item_photo_deleted', $aDataEmail['email'],
                                false, '', '', $aDataEmail['lang']
                            );
                        }
                    }
                } else {
                    $oImages->deleteTmpFile($sFilename);
                }
            }
            break;
            case 'delete-all': # удаление всех изображений
            {
                if ($nItemID) {
                    $oImages->deleteAllImages(true);
                } else {
                    $sFilename = $this->input->post('filenames', TYPE_ARRAY_STR);
                    $oImages->deleteTmpFile($sFilename);
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

    public function comments_ajax()
    {
        if (!$this->haveAccessTo('items-comments')) {
            return $this->showAccessDenied();
        }

        $this->itemComments()->admAjax();
    }

    public function comments_mod()
    {
        if (!$this->haveAccessTo('items-comments')) {
            return $this->showAccessDenied();
        }

        return $this->itemComments()->admListingModerate(15, true);
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
                'item'    => TYPE_UINT,
                'page'    => TYPE_UINT,
                'perpage' => TYPE_UINT,
                'status'  => TYPE_UINT,
            )
        );

        $aFilter = array();
        if ($aData['item']) {
            $aFilter['item_id'] = $aData['item'];
        }
        switch ($aData['status']) {
            case 1:
            {
                /* все */
            }
            break;
            default:
            {
                $aFilter['viewed'] = 0;
            }
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

        return $this->viewPHP($aData, 'admin.items.claims.listing');
    }

    public function ajax()
    {
        $aResponse = array();
        $action = $this->input->get('act', TYPE_STR);
        $statusBlock = function($itemID) use (&$aResponse) {
            $data = $this->model->itemData($itemID);
            $data['user'] = array('blocked'=>$data['user_blocked']);
            $data['is_refresh'] = true;
            $data['is_popup'] = $this->input->post('popup', TYPE_BOOL);
            $aResponse['html'] = $this->viewPHP($data, 'admin.form.status');
        };
        switch ($action) {
            case 'item-info':
            {
                /**
                 * Краткая информация об ОБ (popup)
                 * @param integer 'id' ID объявления
                 */
                if (!$this->haveAccessTo('items-listing')) {
                    $this->errors->accessDenied();
                    break;
                }
                $nItemID = $this->input->get('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->itemData($nItemID, array(
                        'id',
                        'user_id',
                        'shop_id',
                        'cat_id',
                        'created',
                        'title',
                        'descr',
                        'link',
                        'status',
                        'status_prev',
                        'status_changed',
                        'claims_cnt',
                        'blocked_id',
                        'blocked_num',
                        'blocked_reason',
                        'moderated',
                        'publicated',
                        'publicated_to',
                        'publicated_order',
                        'price',
                        'price_curr',
                        'price_ex',
                        'imgcnt',
                    )
                );
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData['user'] = Users::model()->userData($aData['user_id'], array(
                        'email',
                        'name',
                        'blocked',
                        'shop_id'
                    )
                );
                if ($aData['shop_id'] && $aData['shop_id'] == $aData['user']['shop_id'] && bff::shopsEnabled()) {
                    $aData['shop'] = Shops::model()->shopData($aData['shop_id'], array('id', 'link', 'title'));
                }
                $aData['cats_path'] = $this->model->catParentsData($aData['cat_id'], array('id', 'title', 'price'));
                $aData['img'] = $this->itemImages($nItemID);
                $aData['images'] = $aData['img']->getData($aData['imgcnt']);
                echo $this->viewPHP($aData, 'admin.items.info');
                exit;
            }
            break;
            case 'item-form-cat':
            {
                /**
                 * Форма ОБ, дополнительные поля в зависимости от категории
                 * @param integer 'cat_id' ID категории
                 */
                if (!$this->haveAccessTo('items-edit')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nCategoryID = $this->input->post('cat_id', TYPE_UINT);
                $aResponse['id'] = $nCategoryID;

                do {
                    $aData = $this->itemFormByCategory($nCategoryID);
                    if (empty($aData)) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $aResponse = array_merge($aData, $aResponse);
                } while (false);
            }
            break;
            case 'item-block':
            {
                /**
                 * Блокировка объявления (если уже заблокирован => изменение причины блокировки)
                 * @param string 'blocked_reason' причина блокировки
                 * @param integer 'id' ID объявления
                 */
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $blockedID = $this->input->postget('blocked_id', TYPE_UINT);
                $sBlockedReason = $this->input->postget('blocked_reason', TYPE_STR);
                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->itemData($nItemID, array('status', 'status_prev', 'user_id', 'blocked_reason'));
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                if ($aData['status'] == self::STATUS_DELETED) {
                    $this->errors->impossible();
                    break;
                }

                $bBlocked = ($aData['status'] == self::STATUS_BLOCKED);

                $aUpdate = array(
                    'moderated'      => 1,
                    'blocked_id'     => $blockedID,
                    'blocked_reason' => $sBlockedReason,
                );

                if (!$bBlocked) {
                    $aUpdate[] = 'blocked_num = blocked_num + 1';
                    $aUpdate[] = 'status_prev = status';
                    $aUpdate['status'] = self::STATUS_BLOCKED;
                } else {
                    if ($aData['blocked_reason'] != $sBlockedReason) {
                        $bBlocked = false;
                    }
                }
                if ($blockedID != static::BLOCK_OTHER &&
                    $blockedID != static::BLOCK_FOREVER) {
                    $reasons = static::blockedReasons();
                    if (isset($reasons[ $blockedID ])) {
                        $aUpdate['blocked_reason'] = $sBlockedReason = $reasons[ $blockedID ];
                    }
                }

                $res = $this->model->itemSave($nItemID, $aUpdate);
                if ($res && !$bBlocked) {
                    $bBlocked = true;

                    # отправляем email-уведомление о блокировке ОБ
                    do {
                        if ($aData['status'] == self::STATUS_NOTACTIVATED ||
                            $aData['status'] == self::STATUS_DELETED) {
                            break;
                        }
                        if (!$aData['user_id']) {
                            break;
                        }

                        $aDataEmail = $this->model->itemData2Email($nItemID);
                        if (empty($aDataEmail)) {
                            break;
                        }
                        $loginAuto = Users::loginAutoHash($aDataEmail);
                        $aDataEmail['item_link'] .= '?alogin='.$loginAuto;

                        bff::sendMailTemplate(array(
                                'name'           => $aDataEmail['name'],
                                'email'          => $aDataEmail['email'],
                                'user_id'        => $aDataEmail['user_id'],
                                'item_id'        => $aDataEmail['item_id'],
                                'item_link'      => $aDataEmail['item_link'],
                                'item_title'     => $aDataEmail['item_title'],
                                'blocked_reason' => $sBlockedReason
                            ), 'bbs_item_blocked', $aDataEmail['email'],
                            false, '', '', $aDataEmail['lang']
                        );
                    } while (false);
                }

                # обновляем счетчик "на модерации"
                $this->moderationCounterUpdate();

                $aResponse['blocked'] = $bBlocked;
                $aResponse['reason'] = $sBlockedReason;
            }
            break;
            case 'item-activate':
            {
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItemData = $this->model->itemData($nItemID, array('status', 'user_id', 'publicated_period'));
                if (empty($aItemData) || $aItemData['status'] != self::STATUS_NOTACTIVATED) {
                    $this->errors->impossible();
                    break;
                }
                $aUserData = Users::model()->userData($aItemData['user_id'], array('activated', 'blocked'));
                if (empty($aUserData)) {
                    $this->errors->impossible();
                    break;
                }
                if (!$aUserData['activated']) {
                    $this->errors->set(_t('bbs', 'Невозможно активировать объявление для неактивированного пользователя'));
                    break;
                }
                if ($aUserData['blocked']) {
                    $this->errors->set(_t('bbs', 'Невозможно активировать объявление для заблокированного пользователя'));
                    break;
                }

                $res = $this->model->itemSave($nItemID, array(
                        'activate_key'     => '', # чистим ключ активации
                        'publicated'       => $this->db->now(),
                        'publicated_order' => $this->db->now(),
                        'publicated_to'    => $this->getItemPublicationPeriod($aItemData['publicated_period']),
                        'status_prev'      => self::STATUS_NOTACTIVATED,
                        'status'           => self::STATUS_PUBLICATED,
                        'moderated'        => 1,
                    )
                );
                if (empty($res)) {
                    $this->errors->impossible();
                } else {
                    # обновляем счетчик "на модерации"
                    $this->moderationCounterUpdate();
                    $statusBlock($nItemID);
                }
            }
            break;
            case 'item-approve':
            {
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItemData = $this->model->itemData($nItemID, array('status', 'publicated', 'publicated_to', 'user_id', 'cat_id', 'shop_id'));
                if (empty($aItemData) || in_array($aItemData['status'], array(self::STATUS_NOTACTIVATED, self::STATUS_DELETED))) {
                    $this->errors->impossible();
                    break;
                }

                $aUpdate = array(
                    'moderated' => 1
                );

                if ($aItemData['status'] == self::STATUS_BLOCKED) {
                    /**
                     * В случае если "Одобряем" заблокированное ОБ
                     * => значит оно после блокировки было отредактировано пользователем
                     * => следовательно если его период публикации еще не истек => "Публикуем",
                     *    в противном случае переводим в статус "Период публикации завершился"
                     */
                    $newStatus = self::STATUS_PUBLICATED_OUT;
                    $now = time();
                    $from = strtotime($aItemData['publicated']);
                    $to = strtotime($aItemData['publicated_to']);
                    if (!empty($from) && !empty($to) && $now >= $from && $now < $to) {
                        $newStatus = self::STATUS_PUBLICATED;
                    }
                    $aUpdate[] = 'status_prev = status';
                    $aUpdate['status'] = $newStatus;
                }

                # Проверка лимитов: текущий тариф (абонемент)
                if ($aItemData['shop_id'] && bff::shopsEnabled()) {
                    if ($aItemData['status'] == static::STATUS_PUBLICATED && ! isset($aUpdate['status'])) {
                        if (Shops::i()->abonementLimitExceed($aItemData['shop_id'])) {
                            $aUpdate['status'] = self::STATUS_PUBLICATED_OUT;
                        }
                    }
                }
                # Проверка лимитов: платные лимиты
                if (static::limitsPayedEnabled() && $aItemData['status'] == static::STATUS_PUBLICATED && ! isset($aUpdate['status'])) {
                    $limit = $this->model->limitsPayedCategoriesForUser(array(
                        'user_id' => $aItemData['user_id'],
                        'shop_id' => $aItemData['shop_id'],
                        'cat_id'  => $aItemData['cat_id'],
                    ));
                    if ( ! empty($limit)) {
                        $limit = reset($limit);
                        if ($limit['cnt'] >= $limit['limit']) {
                            $aUpdate['status'] = self::STATUS_PUBLICATED_OUT;
                        }
                    }
                }

                $res = $this->model->itemSave($nItemID, $aUpdate);
                if (empty($res)) {
                    $this->errors->impossible();
                } else {
                    # обновляем счетчик "на модерации"
                    $this->moderationCounterUpdate();
                    $statusBlock($nItemID);
                }
            }
            break;
            case 'items-approve':
            {
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }


                $ids = $this->input->post('i', TYPE_ARRAY_UINT);

                $items = $this->model->itemsDataByFilter(array('id' => $ids), array(
                        'id',
                        'status',
                        'publicated',
                        'publicated_to',
                        'user_id',
                    ), array('context'=>'admin-moderate-items-approve')
                );
                if (!$items || empty($items)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $save = array(self::STATUS_PUBLICATED=>array(), self::STATUS_PUBLICATED_OUT=>array());
                $blocked = array(self::STATUS_PUBLICATED=>array(), self::STATUS_PUBLICATED_OUT=>array());
                foreach ($items as $id => &$item) {
                    if ($item['status'] == self::STATUS_PUBLICATED ||
                        $item['status'] == self::STATUS_PUBLICATED_OUT) {
                        $save[$item['status']][] = $id;
                    } elseif ($item['status'] == self::STATUS_BLOCKED) {
                        /**
                         * В случае если "Одобряем" заблокированное ОБ
                         * => значит оно после блокировки было отредактировано пользователем
                         * => следовательно если его период публикации еще не истек => "Публикуем",
                         *    в противном случае переводим в статус "Период публикации завершился"
                         */
                        $now = time();
                        $from = strtotime($item['publicated']);
                        $to = strtotime($item['publicated_to']);
                        if (!empty($from) && !empty($to) && $now >= $from && $now < $to) {
                            $blocked[self::STATUS_PUBLICATED][] = $id;
                        } else {
                            $blocked[self::STATUS_PUBLICATED_OUT][] = $id;
                        }
                    }
                } unset($item);

                $updatedTotal = 0;
                $savePublicated = $save[self::STATUS_PUBLICATED];
                if (!empty($savePublicated)) {
                    $updateData = array(
                        'moderated' => 1,
                        'is_moderating' => 0,
                        'status' => self::STATUS_PUBLICATED,
                    );
                    $updatedTotal += $this->model->itemsUpdateByFilter($updateData, array('id'=>$savePublicated), array('context'=>'items-approve'));
                    # Платные лимиты:
                    if (static::limitsPayedEnabled()) {
                        $users = array();
                        foreach ($savePublicated as $v) {
                            $users[] = $items[$v]['user_id'];
                        }
                        $users = array_unique($users);
                        foreach ($users as $u) {
                            $this->model->limitsPayedUserUnpublicate($u);
                        }
                    }
                }
                $savePublicatedOut = $save[self::STATUS_PUBLICATED_OUT];
                if (!empty($savePublicatedOut)) {
                    $updateData = array(
                        'moderated' => 1,
                        'is_moderating' => 0,
                        'status' => self::STATUS_PUBLICATED_OUT,
                    );
                    $updatedTotal += $this->model->itemsUpdateByFilter($updateData, array('id'=>$savePublicatedOut), array('context'=>'items-approve'));
                }

                if (!empty($blocked)) {
                    $updateData = array(
                        'moderated' => 1,
                        'is_moderating' => 0,
                        'status_prev = status',
                    );
                    foreach ($blocked as $newStatus => $items) {
                        if (empty($items)) continue;
                        $updateData['status'] = $newStatus;
                        $updateData['is_publicated'] = ($newStatus == self::STATUS_PUBLICATED ? 1 : 0);
                        $updatedTotal += $this->model->itemsUpdateByFilter($updateData, array('id'=>$items), array('context'=>'items-approve'));
                    }
                }

                # обновляем счетчик "на модерации"
                $this->moderationCounterUpdate();

                $aResponse = array(
                    'updated' => $updatedTotal,
                    'success' => true,
                );
            }
            break;
            case 'item-refresh':
            {
                /**
                 * Продление публикации ОБ
                 * @param integer 'id' ID объявления
                 */
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if ( ! $nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItem = $this->model->itemData($nItemID, array(
                    'id',
                    'status',
                    'moderated',
                    'publicated',
                    'publicated_to',
                ));
                if (empty($aItem)) {
                    $this->errors->unknownRecord();
                    break;
                }
                # поднять вверх списка
                $bTopUp = $this->input->post('topup', TYPE_BOOL);

                switch ($aItem['status']) {
                    case self::STATUS_NOTACTIVATED:
                    {
                        $this->errors->set(_t('bbs', 'Невозможно продлить публикацию неактивированного объявления'));
                    }
                    break;
                    case self::STATUS_BLOCKED:
                    {
                        $this->errors->set($aItem['moderated'] == 0
                            ? _t('bbs', 'Невозможно продлить публикацию, поскольку объявление ожидает проверки')
                            : _t('bbs', 'Невозможно продлить публикацию, поскольку объявление отклонено')
                        );
                    }
                    break;
                    case self::STATUS_PUBLICATED:
                    {
                        # продлеваем от даты завершения срока публикации
                        $aUpdate = array(
                            'publicated_to' => $this->getItemRefreshPeriod($aItem['publicated_to']),
                        );
                        # поднимаем вверх списка
                        if ($bTopUp) {
                            $aUpdate['publicated_order'] = $this->db->now();
                        }
                        $this->model->itemSave($nItemID, $aUpdate);
                    }
                    break;
                    case self::STATUS_PUBLICATED_OUT:
                    {
                        # продлеваем от текущего момента + публикуем
                        $aUpdate = array(
                            'publicated_to' => $this->getItemRefreshPeriod(),
                            'status_prev = status',
                            'status'        => self::STATUS_PUBLICATED,
                            'moderated'     => 1,
                        );
                        # поднимаем вверх списка
                        if ($bTopUp) {
                            $aUpdate['publicated'] = $this->db->now();
                            $aUpdate['publicated_order'] = $this->db->now();
                        }

                        $res = $this->model->itemSave($nItemID, $aUpdate);
                        if (empty($res)) {
                            $this->errors->impossible();
                        } else {
                            # обновляем счетчик "на модерации"
                            $this->moderationCounterUpdate();
                        }
                    }
                    break;
                    default:
                    {
                        $this->errors->set(_t('bbs', 'Текущий статус объявления указан некорректно'));
                    }
                    break;
                }

                if ($this->errors->no()) {
                    $statusBlock($nItemID);
                }
            }
            break;
            case 'item-unpublicate':
            {
                /**
                 * Снимаем ОБ с публикации
                 * @param integer 'id' ID объявления
                 */
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItem = $this->model->itemData($nItemID, array(
                        'id',
                        'status',
                        'moderated',
                        'publicated',
                        'publicated_to'
                    )
                );
                if (empty($aItem) || $aItem['status'] != self::STATUS_PUBLICATED) {
                    $this->errors->impossible();
                    break;
                }

                $aUpdate = array(
                    'status_prev = status',
                    'status'        => self::STATUS_PUBLICATED_OUT,
                    'moderated'     => 1,
                    'publicated_to' => $this->db->now(),
                    # оставляем все текущие услуги активированными
                );

                $res = $this->model->itemSave($nItemID, $aUpdate);
                if (empty($res)) {
                    $this->errors->impossible();
                } else {
                    # обновляем счетчик "на модерации"
                    $this->moderationCounterUpdate();
                    $statusBlock($nItemID);
                }
            }
            break;
            case 'item-user':
            {
                if (!$this->haveAccessTo('items-listing')) {
                    $this->errors->accessDenied();
                    $this->ajaxResponse($aResponse);
                }
                $sEmail = $this->input->post('q', TYPE_NOTAGS);
                $sEmail = $this->input->cleanSearchString($sEmail);
                $aFilter = array(
                    'blocked'   => 0,
                    'activated' => 1,
                );

                if (is_numeric($sEmail)) {
                    $aFilter['user_id'] = $sEmail;
                } else {
                    $aFilter[':email'] = array(
                        (Users::model()->userEmailCrypted() ? 'BFF_DECRYPT(email)' : 'email') . ' LIKE :email',
                        ':email' => $sEmail . '%'
                    );
                }

                if (static::publisher(static::PUBLISHER_SHOP)) {
                    $aFilter[':shop'] = 'shop_id > 0';
                }
                $aUsers = Users::model()->usersList($aFilter, array('user_id', 'email', 'shop_id'));
                $aResponse = array();
                foreach ($aUsers as $v) {
                    $aResponse[] = array($v['user_id'], $v['email'], $v['shop_id']);
                }

                $this->ajaxResponse($aResponse);
            }
            break;
            case 'import-info':
            {
                /**
                 * Подробная информация об импорте ОБ (popup)
                 * @param integer 'id' ID импорта
                 */
                if (!$this->haveAccessTo('items-listing')) {
                    $this->errors->accessDenied();
                    break;
                }
                $nImportID = $this->input->get('id', TYPE_UINT);
                if (!$nImportID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->importData($nImportID);
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }
                $aData['user'] = Users::model()->userData($aData['user_id'], array('email','blocked'));
                $settings = func::unserialize($aData['settings']);
                if ( ! $settings) {
                    $this->errors->set(_t('bbs', 'Ошибка чтения настроек импорта'));
                    break;
                }

                if ( ! empty($settings['catId'])) {
                    $aParents = $this->model->catParentsData($settings['catId']);
                    if ( ! $aParents) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $catTitles = array();
                    foreach ($aParents as $v) $catTitles[] = $v['title'];
                    $settings['cat_title'] = join(' / ', $catTitles);
                }

                $settings['user'] = Users::model()->userData($settings['userId'], array('user_id', 'email', 'name', 'blocked', 'shop_id'));
                if ($settings['shop'] > 0 && bff::shopsEnabled()) {
                    $settings['shop'] = Shops::model()->shopData($settings['shop'], array('id', 'link', 'title'));
                }
                $aData['settings'] = &$settings;

                $statusList = $this->itemsImport()->getStatusList();
                $aData['status_title'] = ( isset($statusList[$aData['status']]) ? $statusList[$aData['status']] : '?' );

                echo $this->viewPHP($aData, 'admin.items.import.info');
                exit;
            }
            break;
            case 'item-auto-title':
            {
                if (!$this->haveAccessTo('items-edit')) {
                    $this->errors->accessDenied();
                    break;
                }

                $catID = $this->input->post('cat_id', TYPE_UINT);
                $field = 'tpl_title_view';
                if (static::translate()) {
                    $catData = array();
                    $cat = $this->catNearestParent($catID, array($field), $catData);
                    $catData = $this->model->catDataLang($cat, array($field));
                    foreach ($catData as $v) {
                        $aResponse['title'][$v['lang']] = $this->dpFillTpl($catID, $v[$field], $_POST, $v['lang']);
                    }
                } else {
                    $catData = array();
                    $this->catNearestParent($catID, array($field), $catData);
                    $aResponse['title'] = $this->dpFillTpl($catID, $catData[$field], $_POST);
                }
            }
            break;
            case 'category-options':
            {
                if (!$this->haveAccessTo('categories')) {
                    $this->errors->accessDenied();
                    break;
                }
                $sType = $this->input->post('type', TYPE_NOTAGS);
                $nSelectedID = $this->input->post('selected', TYPE_UINT);
                $aResponse['options'] = $this->model->catsOptions($sType, $nSelectedID);
            }
            break;
            default:
            {
                bff::hook('bbs.admin.ajax.default.action', $action, $this);
                $this->errors->impossible();
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    # -------------------------------------------------------------------------------------------------------------------------------
    # категории

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
                        $this->ajaxResponseForm(array('reload' => true));
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
                    if (FORDEV) {
                        if ($this->model->catDeleteDev($nCategoryID)) {
                            $this->ajaxResponse(Errors::SUCCESS);
                        }
                    } elseif ($this->model->catDelete($nCategoryID)) {
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'dev-landing-pages-auto':
                {

                    $this->model->catsLandingPagesAuto();

                    $this->adminRedirect(Errors::SUCCESS, 'categories_listing');
                }
                break;
                case 'dev-export':
                {
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    $sType = $this->input->getpost('type', TYPE_STR);
                    switch ($sType) {
                        case 'txt':
                        default:
                        {
                            $aData = $this->model->catsExport('txt');
                            header('Content-disposition: attachment; filename=categories_export.txt');
                            header('Content-type: text/plain');
                            foreach ($aData as &$v) {
                                echo str_repeat("\t", $v['numlevel'] - 1) . ($v['subs'] ? '-' : '+') . ' ' . $v['id'] . ' '. $v['title'] . "\n";
                            }
                            unset($v);
                        }
                        break;
                    }
                    exit;
                }
                break;
                case 'dev-treevalidate':
                {
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    set_time_limit(0);
                    ignore_user_abort(true);

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
        $sCatState = $this->input->cookie(bff::cookiePrefix() . 'bbs_cats_state');
        $aCatExpandedID = (!empty($sCatState) ? explode('.', $sCatState) : array());
        $aCatExpandedID = array_map('intval', $aCatExpandedID);
        $aCatExpandedID[] = self::CATS_ROOTID;
        $aFilter['pid'] = $aCatExpandedID;

        $aData['cats'] = $this->model->catsListing($aFilter);
        $aData['deep'] = static::CATS_MAXDEEP;
        $aData['cats'] = $this->viewPHP($aData, 'admin.categories.listing.ajax');

        return $this->viewPHP($aData, 'admin.categories.listing');
    }

    public function categories_packetActions()
    {
        if (!FORDEV) {
            return $this->showAccessDenied();
        }

        $aData = array();
        if (Request::isAJAX())
        {
            $updated = 0;

            do {

                $actions = $this->input->post('actions', TYPE_ARRAY_BOOL);
                $actions = $this->input->clean_array($actions, array(
                    'currency_default' => TYPE_BOOL,
                    'photos_max'       => TYPE_BOOL,
                    'list_type'        => TYPE_BOOL,
                    'landingpages_auto'=> TYPE_BOOL,
                ));
                if ( ! array_sum($actions)) {
                    $this->errors->set(_t('bbs', 'Отметьте как минимум одну из доступных настроек'));
                    break;
                }
                $catsFields = array('id');

                # валюта по-умолчанию
                if ($actions['currency_default']) {
                    $currencyID = $this->input->post('currency_default', TYPE_UINT);
                    if ( ! $currencyID) {
                        $this->errors->set(_t('bbs', 'Валюта по-умолчанию указана некорректно'));
                        break;
                    }
                    $catsFields[] = 'price_sett';
                }

                # максимально доступное кол-во фотографий
                if ($actions['photos_max']) {
                    $photosMax = $this->input->post('photos_max', TYPE_UINT);
                    if ($photosMax < static::itemsImagesLimit(false)) {
                        $photosMax = static::itemsImagesLimit(false);
                    }
                    if ($photosMax > static::itemsImagesLimit()) {
                        $photosMax = static::itemsImagesLimit();
                    }
                    $catsFields[] = 'photos';
                }

                # вид списка по-умолчанию
                if ($actions['list_type']) {
                    $nListType = $this->input->post('list_type', TYPE_UINT);
                    if ( ! array_key_exists($nListType, static::itemsSearchListTypes()) ) {
                        $actions['list_type'] = false;
                    } else {
                        $catsFields[] = 'list_type';
                        $catsFields[] = 'addr';
                    }
                }

                # избавление от /search/
                if ($actions['landingpages_auto']) {
                    $updated = $this->model->catsLandingPagesAuto();
                    if (array_sum($actions) == 1) {
                        break;
                    }
                }

                bff::hook('bbs.admin.category.packetActions.step1',array('actions'=>&$actions,'catsFields'=>&$catsFields));

                $data = $this->model->catsDataByFilter(array(), $catsFields);
                if (empty($data)) {
                    $this->errors->set(_t('bbs', 'Неудалось найти категории'));
                    break;
                }
                foreach ($data as &$v)
                {
                    if ($actions['currency_default']) {
                        if (!isset($v['price_sett']) || !isset($v['price_sett']['curr'])) {
                            continue;
                        }
                        $v['price_sett']['curr'] = $currencyID;
                    }
                    if ($actions['photos_max']) {
                        $v['photos'] = $photosMax;
                    }
                    if ($actions['list_type']) {
                        if ($nListType != static::LIST_TYPE_MAP ||
                            ($nListType == static::LIST_TYPE_MAP && $v['addr'])) {
                            $v['list_type'] = $nListType;
                        }
                    }
                    bff::hook('bbs.admin.category.packetActions.step2',array('id'=>$v['id'],'data'=>$v,'actions'=>&$actions));
                    $res = $this->model->catSave($v['id'], $v);
                    if ( ! empty($res)) $updated++;
                } unset($v);
            } while(false);

            $this->ajaxResponseForm(array('updated'=>$updated));
        }

        return $this->viewPHP($aData, 'admin.categories.packetActions');
    }

    public function categories_add()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $aData = $this->validateCategoryData(0);

        if (Request::isPOST()) {
            $aResponse = array('reload' => false, 'back' => false);

            if ($this->errors->no('bbs.admin.category.submit',array('id'=>0,'data'=>&$aData))) {
                $nCategoryID = $this->model->catSave(0, $aData);
                if ($nCategoryID) {
                    # ...
                }
                $aResponse['back'] = true;
            }
            $this->iframeResponseForm($aResponse);
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

        $bAllowEditParent = true;
        $nCategoryID = $this->input->getpost('id', TYPE_UINT);
        if (!$nCategoryID) {
            $this->adminRedirect(Errors::UNKNOWNRECORD, 'categories_listing');
        }

        $aData = $this->model->catData($nCategoryID, '*', true);
        $aData['structure_modified'] = $this->model->catsStructureChanged();

        if (Request::isPOST()) {
            $aResponse = array('reload' => false, 'back' => false);

            if (!$aData) {
                $this->errors->unknownRecord();
                $this->iframeResponseForm($aResponse);
            }
            $bCopySettingsToSubs = ($this->input->post('copy_to_subs', TYPE_BOOL) && FORDEV);
            $aDataSave = $this->validateCategoryData($nCategoryID);

            if ($this->errors->no('bbs.admin.category.submit',array('id'=>$nCategoryID,'data'=>&$aDataSave,'before'=>$aData))) {
                # смена parent-категории
                if ($bAllowEditParent && !$bCopySettingsToSubs && $aDataSave['pid'] != $aData['pid'] && $this->input->post('structure_modified', TYPE_STR) === $aData['structure_modified']) {
                    if ($this->model->catChangeParent($nCategoryID, $aDataSave['pid']) !== false) {
                        $aResponse['structure_modified'] = $this->db->now();
                    }
                    # очищаем состояние списка категорий из-за смены порядка вложенности
                    Request::deleteCOOKIE(bff::cookiePrefix() . 'bbs_cats_state', $this->security->getAdminPath());
                }

                # отвязываем объявления, добавленные в виртуальную категорию
                if (!empty($aData['virtual_ptr']) && empty($aDataSave['virtual_ptr'])) {
                    $this->model->catVirtualDropItemsLink($nCategoryID);
                }
                # изменение связи виртуальной категории
                if ($aData['virtual_ptr'] != $aDataSave['virtual_ptr']) {
                    $this->model->itemsCountersCalculateVirtual();
                }

                $res = $this->model->catSave($nCategoryID, $aDataSave);
                if (!empty($res)) {
                    # если keyword был изменен и есть вложенные подкатегории:
                    # > перестраиваем полный путь подкатегорий (и items::link)
                    if ($aData['keyword_edit'] != $aDataSave['keyword_edit'] && $aData['node'] > 1) {
                        $this->model->catSubcatsRebuildKeyword($nCategoryID, $aData['keyword_edit']);
                    }
                    # сбрасываем кеш дин. свойств категории
                    $this->dpSettingsChanged($nCategoryID, 0, 'cat-edit');
                    # хук успешного сохранения
                    bff::hook('bbs.admin.category.submit.success',array('id'=>$nCategoryID,'data'=>&$aDataSave,'before'=>$aData));
                }

                if ($this->model->catIsMain($nCategoryID, $aDataSave['pid'])) {
                    $aUpdate = array();
                    $oIcon = static::categoryIcon($nCategoryID);
                    foreach ($oIcon->getVariants() as $iconField => $v) {
                        $oIcon->setVariant($iconField);
                        $aIconData = $oIcon->uploadFILES($iconField, true, false);
                        if (!empty($aIconData)) {
                            $aUpdate[$iconField] = $aIconData['filename'];
                            $aResponse['reload'] = true;
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
                # копируем настройки во все подкатегории
                if ($bCopySettingsToSubs) {
                    $bCopySettingsToSubsParams = $this->input->post('copy_to_subs_data', TYPE_ARRAY);
                    $this->model->catDataCopyToSubs($nCategoryID, array_unique($bCopySettingsToSubsParams));
                }

                if ($this->input->post('back', TYPE_BOOL)) {
                    $aResponse['back'] = true;
                } else {
                    $aResponse['landing_id'] = $aDataSave['landing_id'];
                    $aResponse['landing_url'] = $aDataSave['landing_url'];
                }
            }
            $this->iframeResponseForm($aResponse);
        } else {
            if (!$aData) {
                $this->adminRedirect(Errors::UNKNOWNRECORD, 'categories_listing');
            }
            $this->validateCategoryPriceSettings($aData['price_sett']);
        }

        $aData['pid_editable'] = $bAllowEditParent;
        if ($bAllowEditParent) {
            $aData['pid_options'] = $this->model->catsOptions('adm-category-form-edit', $aData['pid'], false, array(
                    'id'       => $nCategoryID,
                    'numleft'  => $aData['numleft'],
                    'numright' => $aData['numright'],
                )
            );
        } else {
            $aData['pid_options'] = $this->model->catParentsData($nCategoryID, array('id', 'title'), false, false);
        }
        
        $aData['dp'] = $this->dp()->getByOwner($nCategoryID, true, true, false);

        $aData['tpl_parent'] = array();
        $this->catNearestParent($nCategoryID, array('tpl_title_list', 'tpl_title_view'), $aData['tpl_parent'], false);
        $this->catNearestParent($nCategoryID, array('tpl_descr_list'), $aData['tpl_parent'], false);

        return $this->viewPHP($aData, 'admin.categories.form');
    }

    # -------------------------------------------------------------------------------------------------------------------------------
    # типы категории

    public function types_listing($nCategoryID)
    {
        if (!$this->haveAccessTo('types')) {
            return '';
        }
        $aData['cat_id'] = $nCategoryID;
        $aData['cats'] = $this->model->catParentsData($nCategoryID, array('id', 'title'));
        $aData['types'] = $this->model->cattypesListing(array($this->db->prepareIN('T.cat_id', array_keys($aData['cats']))));
        $aData['list'] = $this->viewPHP($aData, 'admin.types.listing.ajax');
        if (Request::isAJAX()) {
            return $aData['list'];
        }

        return $this->viewPHP($aData, 'admin.types.listing');
    }

    public function types()
    {
        $aResponse = array();
        do {
            if (!$this->haveAccessTo('types')) {
                $this->errors->accessDenied();
                break;
            }

            $nCategoryID = $this->input->getpost('cat_id', TYPE_UINT);
            if (!$nCategoryID) {
                $this->errors->impossible();
                break;
            }

            switch ($this->input->postget('act', TYPE_STR)) {
                case 'toggle':
                {

                    $nTypeID = $this->input->get('type_id', TYPE_UINT);
                    if (!$this->model->cattypeToggle($nTypeID, 'enabled')) {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'rotate':
                {

                    if (!$this->model->cattypesRotate($nCategoryID)) {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'form':
                {
                    $nTypeID = $this->input->get('type_id', TYPE_UINT);
                    if ($nTypeID) {
                        $aData = $this->model->cattypeData($nTypeID, '*', true);
                    } else {
                        $this->validateCategoryTypeData($aData, $nCategoryID, 0);
                        $aData['id'] = 0;
                    }

                    $aData['form'] = $this->viewPHP($aData, 'admin.types.form');
                    $aResponse = $aData;
                }
                break;
                case 'delete':
                {

                    $nTypeID = $this->input->get('type_id', TYPE_UINT);
                    if (!$this->model->cattypeDelete($nTypeID)) {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'add':
                {

                    $this->validateCategoryTypeData($aData, $nCategoryID, 0);
                    if ($this->errors->no('bbs.admin.category-type.submit',array('id'=>0,'data'=>&$aData,'cat'=>$nCategoryID))) {
                        $this->model->cattypeSave(0, $nCategoryID, $aData);
                        $aResponse['list'] = $this->types_listing($nCategoryID);
                    }
                }
                break;
                case 'edit':
                {

                    $nTypeID = $this->input->post('type_id', TYPE_UINT);
                    if (!$nTypeID) {
                        $this->errors->impossible();
                        break;
                    }
                    $this->validateCategoryTypeData($aData, $nCategoryID, $nTypeID);
                    if ($this->errors->no('bbs.admin.category-type.submit',array('id'=>$nTypeID,'data'=>&$aData,'cat'=>$nCategoryID))) {
                        $this->model->cattypeSave($nTypeID, $nCategoryID, $aData);
                        $aResponse['list'] = $this->types_listing($nCategoryID);
                    }
                }
                break;
                default:
                    $this->errors->impossible();
            }
        } while (false);

        $this->ajaxResponseForm($aResponse);
    }

    public function settings()
    {
        if (!$this->haveAccessTo('settings')) {
            return $this->showAccessDenied();
        }

        $sCurrentTab = $this->input->postget('tab');
        if (empty($sCurrentTab)) {
            $sCurrentTab = 'general';
        }

        $aLang = array(
            'form_add'  => TYPE_STR,
            'form_edit' => TYPE_STR,
        );

        $aCats = $this->model->catsListing(array(
            'numlevel' => 1,
            'enabled'  => 1,
            'pid'      => 1,
        ));
        $aCatsLimit = array();
        foreach ($aCats as $v) {
            $aCatsLimit[ 's'.$v['id'] ] = $v['title'];
        }

        $wordformsManager = $this->itemsSearchSphinx()->wordformsManager($this->adminLink('settings'));

        if (Request::isPOST() && $this->input->post('save', TYPE_BOOL)) {

            $aData = $this->input->postm(array(
                    'item_publication_period'            => TYPE_UINT,
                    'item_publication_periods'           => TYPE_ARRAY,
                    'item_refresh_period'                => TYPE_UINT,
                    'item_share_code'                    => TYPE_STR,
                    'item_unpublicated_soon'             => TYPE_ARRAY_UINT,
                    'items_limits_user'                  => TYPE_UINT,
                    'items_limits_user_common'           => TYPE_UINT,
                    'items_limits_user_category'         => TYPE_ARRAY_UINT,
                    'items_limits_user_category_default' => TYPE_UINT,
                    'items_limits_shop'                  => TYPE_UINT,
                    'items_limits_shop_common'           => TYPE_UINT,
                    'items_limits_shop_category'         => TYPE_ARRAY_UINT,
                    'items_limits_shop_category_default' => TYPE_UINT,
                    'items_spam_duplicates'              => TYPE_BOOL,
                    'items_spam_duplicates_images'       => TYPE_BOOL,
                    'items_spam_minuswords'              => TYPE_ARRAY_STR,
                )
            );

            # срок публикации
            if (static::formPublicationPeriod()) {
                $publication_periods = $this->publicationPeriodVariants();
                $def = 0;
                if (isset($aData['item_publication_periods']['def'])) {
                    $def = intval($aData['item_publication_periods']['def']);
                }
                foreach ($publication_periods as &$v) {
                    unset($v['t']);
                    $v['a'] = isset($aData['item_publication_periods'][ $v['days'] ]['a']);
                    $v['def'] = $v['a'] && $def == $v['days'];
                } unset($v);
                $aData['item_publication_periods'] = serialize($publication_periods);
            } else {
                if (!$aData['item_publication_period']) {
                    $aData['item_publication_period'] = 30;
                } else {
                    if ($aData['item_publication_period'] > 1000) {
                        $aData['item_publication_period'] = 1000;
                    }
                }
                $aData['item_publication_periods'] = serialize(array());
            }

            # срок продления
            if (!$aData['item_refresh_period']) {
                $aData['item_refresh_period'] = 30;
            } else {
                if ($aData['item_refresh_period'] > 1000) {
                    $aData['item_refresh_period'] = 1000;
                }
            }

            # водяной знак
            $this->itemImages()->watermarkSave('images_watermark',
                $this->input->post('images_watermark_delete', TYPE_BOOL),
                $this->input->post('images_watermark_pos_x', TYPE_NOTAGS),
                $this->input->post('images_watermark_pos_y', TYPE_NOTAGS)
            );

            # оповещение о завершении публикации
            $aData['item_unpublicated_soon'] = serialize($aData['item_unpublicated_soon']);

            # Лимитирование объявлений
            foreach ($aData['items_limits_user_category'] as $k => $v) {
                if ( ! array_key_exists('s'.$k, $aCatsLimit)) {
                    unset($aData['items_limits_user_category'][$k]);
                }
            }
            $aData['items_limits_user_category'] = serialize($aData['items_limits_user_category']);

            foreach ($aData['items_limits_shop_category'] as $k => $v) {
                if ( ! array_key_exists('s'.$k, $aCatsLimit)) {
                    unset($aData['items_limits_shop_category'][$k]);
                }
            }
            $aData['items_limits_shop_category'] = serialize($aData['items_limits_shop_category']);

            $aData['items_spam_minuswords'] = \bff\utils\TextParser::minuswordsPrepare('to_array', $aData['items_spam_minuswords']);
            $aData['items_spam_minuswords'] = serialize($aData['items_spam_minuswords']);

            bff::hook('bbs.admin.settings.submit', array('data'=>&$aData,'lang'=>&$aLang));

            $this->input->postm_lang($aLang, $aData);
            $this->db->langFieldsModify($aData, $aLang, $aData);

            $this->configSave($aData);
            
            $this->adminRedirect(Errors::SUCCESS, 'settings&tab=' . $sCurrentTab);
        }

        $aData = $this->configLoad();
        foreach ($this->locale->getLanguages() as $lng) {
            foreach ($aLang as $k => $v) {
                if (!isset($aData[$k . '_' . $lng])) {
                    $aData[$k . '_' . $lng] = '';
                }
            }
        }

        $aData['tab'] = $sCurrentTab;
        $aData['tabs'] = bff::filter('bbs.admin.settings.tabs', array(
            'general'   => array('t' => _t('bbs', 'Общие настройки')),
            'limits'    => array('t' => _t('bbs', 'Лимиты')),
            'spam'      => array('t' => _t('bbs', 'Спам фильтр')),
            'images'    => array('t' => _t('bbs', 'Изображения')),
            'share'     => array('t' => _t('bbs', 'Поделиться')),
            'wordforms' => array('t' => _t('bbs', 'Поисковые фразы'), 'nofooter' => 1),
        ), array('tab'=>&$aData['tab']));
        # При включенных платных лимитах => настройки бесплатных скрываем
        if (static::limitsPayedEnabled()) {
            unset($aData['tabs']['limits']);
        }

        $aData['images_watermark'] = $this->itemImages()->watermarkSettings();
        $aData['images_watermark']['exists'] = (!empty($aData['images_watermark']['file']['path']) &&
            file_exists($aData['images_watermark']['file']['path']));

        $aData['item_unpublicated_soon_days'] = $this->getUnpublicatedDays();
        $aData['item_unpublicated_soon'] = (!empty($aData['item_unpublicated_soon']) ? func::unserialize($aData['item_unpublicated_soon']) : array());

        if (!isset($aData['items_limits_user'])) {
            $aData['items_limits_user'] = static::LIMITS_NONE;
        }
        if (!isset($aData['items_limits_shop'])) {
            $aData['items_limits_shop'] = static::LIMITS_NONE;
        }
        if (static::formPublicationPeriod()) {
            if (isset($aData['item_publication_periods'])) {
                $publication_periods = func::unserialize($aData['item_publication_periods']);
                $aData['item_publication_periods'] = $this->publicationPeriodVariants();
                foreach ($aData['item_publication_periods'] as & $v) {
                    $v['a'] = ! empty($publication_periods[ $v['days'] ]['a']);
                    $v['def'] = ! empty($publication_periods[ $v['days'] ]['def']);
                } unset($v);
            } else {
                $aData['item_publication_periods'] = $this->publicationPeriodVariants();
            }
        }

        $aData['aCatsLimit'] = $aCatsLimit;

        $aCats = func::array_transparent($aCats, 'id', true);
        $aData['items_limits_user_category'] = (isset($aData['items_limits_user_category']) ? func::unserialize($aData['items_limits_user_category']) : array());
        if ( ! empty($aData['items_limits_user_category'])) {
            uksort($aData['items_limits_user_category'], function ($a, $b) use ($aCats) {
                return $aCats[$a]['numleft'] > $aCats[$b]['numleft'];
            });
        }
        $aData['items_limits_shop_category'] = (isset($aData['items_limits_shop_category']) ? func::unserialize($aData['items_limits_shop_category']) : array());
        if ( ! empty($aData['items_limits_shop_category'])) {
            uksort($aData['items_limits_shop_category'], function ($a, $b) use ($aCats) {
                return $aCats[$a]['numleft'] > $aCats[$b]['numleft'];
            });
        }
        $aData['items_spam_minuswords'] = isset($aData['items_spam_minuswords']) ? \bff\utils\TextParser::minuswordsPrepare('to_string', func::unserialize($aData['items_spam_minuswords'])) : array();
        $aData['wordformsManager'] = $wordformsManager;
        return $this->viewPHP($aData, 'admin.settings');
    }

    public function settingsSystem(array &$options = array())
    {
        $aData = array('options'=>&$options);
        return $this->viewPHP($aData, 'admin.settings.sys');
    }

    public function import()
    {
        $access = array(
            'import' => $this->haveAccessTo('items-import'),
            'export' => $this->haveAccessTo('items-export'),
            'yandex' => $this->haveAccessTo('items-export-yandex'),
        );
        if (!$access['import'] && !$access['export']) {
            return $this->showAccessDenied();
        }

        $aSettings = array(
            'catId'     => $this->input->get('catId', TYPE_UINT),
            'state'     => $this->input->get('state', TYPE_UINT),
            'langKey'   => $this->input->get('langKey', TYPE_NOTAGS),
            'type'      => $this->input->post('type', TYPE_BOOL),
            'extension' => $this->input->get('extension', TYPE_NOTAGS),
        );

        $import = $this->itemsImport();
        switch ($this->input->get('act')) {
            case 'export':
                $countOnly = $this->input->get('count', TYPE_BOOL);
                $import->export($aSettings, $countOnly);
                break;
            case 'import-template':
                $import->importTemplate($aSettings);
                break;
            case 'import-cancel':
                $importID = $this->input->get('id', TYPE_UINT);
                $import->importCancel(array('id'=>$importID));
                $this->ajaxResponseForm();
                break;
            case 'yandex-save':
                $response = $this->exportYandexSave();
                $this->ajaxResponseForm($response);
                break;
            case 'yandex-check':
                $response = array('');
                if(BBSYandexMarket::i()->isGenerated($tme)){
                    $response['date'] = tpl::date_format2($tme, true);
                }
                $this->ajaxResponseForm($response);
                break;
            case 'yml-download':
                BBSYandexMarket::i()->download(false);
                break;
            default:
                bff::hook('bbs.admin.import.submit');
                if (Request::isPOST() && !empty($_FILES))
                {
                    $aResponse = array();
                    $aSettings = array(
                        # категория
                        'catId'  => $this->input->post('cat_id', TYPE_UINT),
                        # пользователь (владелец импортируемых объявлений)
                        'userId' => $this->input->post('user_id', TYPE_UINT),
                        # закреплять за магазином
                        'shop'   => $this->input->post('shop', TYPE_UINT),
                        # итоговый статус объявлений
                        'state'  => $this->input->post('state', TYPE_UINT),
                        'type'   => $this->input->post('type', TYPE_BOOL),
                        # срок публикации
                        'publicate_period' => $this->input->post('publicate_period', TYPE_UINT),
                        # несколько пользователей
                        'multi_users'       => $this->input->post('multi_users', TYPE_UINT),
                        # создавать фейковых
                        'multi_users_fake'  => $this->input->post('multi_users_fake', TYPE_UINT),
                    );
                    if (empty($aSettings['state'])) {
                        $this->errors->set(_t('bbs.import', 'Необходимо выбрать статус объявлений'));
                    }

                    if (empty($aSettings['multi_users']) && empty($aSettings['userId'])) {
                        $this->errors->set(_t('bbs.import', 'Укажите пользователя'));
                    }

                    if ($aSettings['type'] == BBSItemsImport::TYPE_URL) {
                        $settUrl = $this->input->postm(array(
                            'url'     => TYPE_NOTAGS,
                            'period'  => TYPE_UINT,
                        ));

                        if (filter_var($settUrl['url'], FILTER_VALIDATE_URL) === false) {
                            $this->errors->set(_t('bbs.import', 'Некорректный URL'), 'url');
                        }
                        $periods = BBSItemsImport::importPeriodOptions();
                        if ( ! array_key_exists($settUrl['period'], $periods)) {
                            $this->errors->reloadPage();
                        }
                        $aSettings = array_merge($aSettings, $settUrl);
                        if ($this->errors->no('bbs.admin.import.url.submit',array('data'=>&$aSettings))) {
                            $aResponse['id'] = $import->importUrlStart($aSettings);
                        }
                    } else {
                        if ($this->errors->no('bbs.admin.import.file.submit',array('data'=>&$aSettings))) {
                            $aResponse['id'] = $import->importStart('file', $aSettings);
                        }
                    }
                    $this->iframeResponseForm($aResponse);
                }

                $f = array();
                $this->input->postgetm(array(
                        'page' => TYPE_UINT,
                    ), $f
                );

                $aData['f'] = $f;

                $tabCurrent = 'import';
                $tab = $this->input->get('tab', TYPE_NOTAGS);
                if (!empty($tab) && !empty($access[$tab])) {
                    $tabCurrent = $tab;
                }

                $tab_list = $this->input->postget('tab_list', TYPE_NOTAGS);
                if (empty($tab_list)) $tab_list = 'admin';
                $is_admin = ($tab_list == 'admin');

                $sqlFilter = array();
                $sqlFields = array();
                if (Request::isAJAX()) {
                    $uid = $this->input->post('uid', TYPE_UINT);
                    $uemail = $this->input->post('uemail', TYPE_NOTAGS);
                    if (!empty($uid)) {
                        $sqlFilter['user_id'] = $uid;
                    } elseif (is_numeric($uemail)) {
                        $sqlFilter['user_id'] = $uemail;
                    } elseif (strpos($uemail, 'parent:') !== false) {
                        $parent = explode(':', $uemail);
                        $parent = intval(trim($parent[1]));
                        if ($parent) {
                            $sqlFilter['parent_id'] = $parent;
                        }
                    }
                }

                $sqlFilter['is_admin'] = ($is_admin ? 1 : 0);
                $sqlFilter['periodic'] = BBSItemsImport::TYPE_FILE;

                $nCount = $this->model->importListing($sqlFields, $sqlFilter, false, false, true);
                $oPgn = new Pagination($nCount, 15, '#', 'jBbsImportsList.page('.Pagination::PAGE_ID.'); return false;');
                $aData['pgn'] = $oPgn->view(array('arrows'=>false));
                $aData['list'] = $this->model->importListing($sqlFields, $sqlFilter, $oPgn->getLimitOffset(), 'created DESC');
                if (!empty($aData['list'])) {
                    foreach ($aData['list'] as &$v) {
                        $v['comment_text'] = '';
                        $comment = func::unserialize($v['status_comment']);
                        if ($comment) {
                            if ($v['status'] == BBSItemsImport::STATUS_FINISHED) {
                                $details = array();
                                if ($v['items_ignored'] > 0) {
                                    $details[] = _t('bbs.import', 'пропущено: [count]', array('count' => '<strong>'.$v['items_ignored'].'</strong>'));
                                }
                                if (!empty($comment['success'])) {
                                    $details[] = _t('bbs.import', 'добавлено: [count]', array('count' => '<strong>'.$comment['success'].'</strong>'));
                                }
                                if (!empty($comment['updated'])) {
                                    $details[] = _t('bbs.import', 'обновлено: [count]', array('count' => '<strong>'.$comment['updated'].'</strong>'));
                                }
                                if (!empty($details)) {
                                    $v['comment_text'] = implode(', ', $details);
                                }
                            } elseif (isset($comment['message'])) {
                                $v['comment_text'] = $comment['message'];
                            }
                        }
                        $file = func::unserialize($v['filename']);
                        if ( ! empty($file['filename'])) {
                            $v['filename'] = $import->getImportPath(true, $file['filename']);
                        }
                    } unset($v);
                }

                $aData['list'] = $this->viewPHP($aData, 'admin.items.imports.ajax');

                if (Request::isAJAX()) {
                    $this->ajaxResponse(array(
                            'list'   => $aData['list'],
                            'pgn'    => $aData['pgn'],
                            'filter' => $f,
                        )
                    );
                }

                $aData['tab_form'] = $tabCurrent;
                $aData['tab_list'] = $tab_list;
                $aData['tabs'] = array();
                if ($access['import']) {
                    $aData['tabs']['import'] = array('t' => _t('bbs', 'Импорт'));
                }
                if ($access['export']) {
                    $aData['tabs']['export'] = array('t' => _t('bbs', 'Экспорт'));
                }
                if ($access['yandex']) {
                    $aData['tabs']['yandex'] = array('t' => _t('bbs', 'Яндекс.Маркет'));

                    $cats = $this->model->catsOptionsByLevel(array(), array('empty' => _t('', 'Выбрать')));
                    if (!empty($cats)) {
                        $cats = reset($cats);
                        $aData['catsOptions'] = $cats['cats'];
                    }

                    $aData['yandex'] = func::unserialize(config::get('bbs_export_yandex_market'));
                    if( ! empty($aData['yandex']['cats'])){
                        foreach($aData['yandex']['cats'] as & $v){
                            $parents = $this->model->catParentsData($v);
                            $titles = array();
                            foreach ($parents as $vv) $titles[] = $vv['title'];
                            $v = array('id' => $v, 'title' => join(' / ', $titles));
                        }unset($v);
                    }
                    $aData['currencies'] = Site::model()->currencyData(false);
                    $aData['curDef'] = mb_strtoupper(Site::currencyDefault('keyword'));
                    $allowed = BBSYandexMarket::currenciesAllowed();
                    foreach ($aData['currencies'] as $k => & $v) {
                        $v['keyword'] = mb_strtoupper($v['keyword']);
                        if ( ! in_array($v['keyword'], $allowed)) {
                            unset($aData['currencies'][$k]);
                        }
                    } unset($v);
                }

                $aData['tabs'] = bff::filter('bbs.admin.import.tabs', $aData['tabs'], array('access'=>$access,'data'=>&$aData));

                $aData['cats'] = $this->model->catsOptionsByLevel(array(), array('empty' => _t('', 'Выбрать')));

                $aData['periodic'] = '';
                if (static::importUrlEnabled()) {
                    $aData['periodic'] = $this->import_periodic();
                }

                return $this->viewPHP($aData, 'admin.items.import');
                break;
        }
    }

    protected function exportYandexSave()
    {
        $data = $this->input->postm(array(
            'enabled'           => TYPE_BOOL,
            'all'               => TYPE_BOOL,
            'cats'              => TYPE_ARRAY_UINT,
            'name'              => TYPE_STR,
            'company'           => TYPE_STR,
            'download'          => TYPE_BOOL,
            'auth'              => TYPE_BOOL,
            'login'             => TYPE_STR,
            'pass'              => TYPE_STR,
            'currencies'        => TYPE_ARRAY_STR,
            'currency_default'  => TYPE_STR,
        ));

        if(empty($data['cats']) && empty($data['all'])){
            $this->errors->set(_t('', 'Выберите категорию'));
            return array();
        }

        if (empty($data['currencies'])) {
            $this->errors->set(_t('', 'Выберите валюты'));
            return array();
        }
        if ( ! in_array($data['currency_default'], $data['currencies'])) {
            $this->errors->set(_t('', 'Укажите основную валюту'));
            return array();
        }

        $data['login'] = BBSYandexMarket::encrypt($data['login']);
        $data['pass'] = BBSYandexMarket::encrypt($data['pass']);

        config::save('bbs_export_yandex_market', serialize($data));

        $response = array();
        if( ! empty($data['enabled'])){
            $generated = BBSYandexMarket::i()->isGenerated();
            if( $this->input->post('force', TYPE_BOOL) || ! $generated){
                $cronManager = bff::cronManager();
                if ($cronManager->isEnabled()) {
                    $cronManager->executeOnce('bbs', 'itemsCronYandexMarket');
                    $response['generate'] = 1;
                    BBSYandexMarket::i()->unlink();
                }
            }
        }
        return $response;
    }
    
    public function import_periodic()
    {
        if ( ! $this->haveAccessTo('items-import')) {
            return $this->showAccessDenied();
        }

        $data = array('list' => '', 'pgn' => '');
        $f = array();
            $this->input->postgetm(array(
                'p_page' => TYPE_UINT,
            ), $f
        );

        $data['f'] = $f;

        switch ($this->input->get('act'))
        {
            case 'import-delete':

                $importID = $this->input->get('id', TYPE_UINT);
                $aResponse['success'] = $this->model()->importDelete($importID);
                $this->ajaxResponseForm($aResponse);
                break;
        }


        $tab_list = $this->input->postget('p_tab_list', TYPE_NOTAGS);
        if (empty($tab_list)) $tab_list = 'admin';
        $is_admin = ($tab_list == 'admin');
        $data['tab_list'] = $tab_list;
        $sqlFilter = array();
        $sqlFields = array();

        $sqlFilter['is_admin'] = ($is_admin ? 1 : 0);
        $sqlFilter['periodic'] = BBSItemsImport::TYPE_URL;
        
        $nCount = $this->model->importListing($sqlFields, $sqlFilter, false, false, true);
        $oPgn = new Pagination($nCount, 15, '#', 'jBbsImportsPeriodicList.page('.Pagination::PAGE_ID.'); return false;');
        $oPgn->pageVar = 'p_page';
        $data['pgn'] = $oPgn->view(array('arrows'=>false));
        $data['list'] = $this->model->importListing($sqlFields, $sqlFilter, $oPgn->getLimitOffset(), 'created DESC');
        if (!empty($data['list'])) {
            foreach ($data['list'] as &$v) {
                $v['comment'] = '';
                $comment = func::unserialize($v['status_comment']);
                if (isset($comment['date']) && isset($comment['message'])) {
                    $v['comment'] = (is_array($comment['message']) ? join(';', $comment['message']) : $comment['message']);
                }
            } unset($v);
        }

        $data['list'] = $this->viewPHP($data, 'admin.items.import.periodic.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list'   => $data['list'],
                    'pgn'    => $data['pgn'],
                    'filter' => $f,
                )
            );
        }
        
        return $this->viewPHP($data, 'admin.items.import.periodic');
    }

    /**
     * Настройки услуги платного расширения лимитов
     * @return string HTML
     */
    public function limitsPayed()
    {
        if ( ! $this->haveAccessTo('items-limits-payed')) {
            return $this->showAccessDenied();
        }
        $data = array();
        return $this->viewPHP($data, 'admin.limits');
    }

    /**
     * Обаботка данных стоимости и регионов услуги платного расширения лимитов
     * @param array $exists обновить существуюшие данные
     * @param bool $allowRegions данные без регионов
     * @return array
     */
    protected function validateLimitsPayedSettings($exists = array(), $allowRegions = true)
    {
        $params = array(
            'checked' => TYPE_ARRAY_BOOL,
            'price'   => TYPE_ARRAY_PRICE,
        );
        if ($allowRegions) {
            $params['regions'] = TYPE_ARRAY_UINT;
        }

        $data = $this->input->postm($params);
        $result = array();
        do{
            # необходимо выбрать хотя-бы одно не нулевое значение
            $settings = array();
            $min = 0; $max = 0; $cnt = 0;
            $limits = static::limitsPayedNumbers();
            foreach ($data['price'] as $k => $v) {
                if ( ! isset($limits[$k])) continue;
                $settings[$k] = array('id' => $k, 'items' => $limits[$k]['items'], 'price' => $v, 'checked' => isset($data['checked'][$k]));
                if ( ! $v) continue;
                if ( ! isset($data['checked'][$k])) continue;
                $cnt++;
                if ( ! $min) $min = $v;
                if ( ! $max) $max = $v;
                if ($min > $v) $min = $v;
                if ($max < $v) $max = $v;
            }

            if ( ! $cnt) {
                $this->errors->set(_t('bbs', 'Укажите стоимость'));
                break;
            }

            $result['settings'] = $settings;
            $title = array();
            if (isset($exists['title'])) {
                $title = $exists['title'];
            }

            $title['min'] = $min;
            $title['max'] = $max;
            $title['regs'] = array();

            # подготовим регионы
            $regions = array();
            if ( ! empty($data['regions'])) {
                foreach ($data['regions'] as $v) {
                    $region = geo::regionData($v);
                    if (empty($region)) continue;
                    switch ($region['numlevel']) {
                        case Geo::lvlCountry:
                            $regions[] = array(
                                'reg1_country'  => $region['id'],
                                'reg2_region'   => 0,
                                'reg3_city'     => 0,
                            );
                            break;
                        case Geo::lvlRegion:
                            $regions[] = array(
                                'reg1_country'  => $region['country'],
                                'reg2_region'   => $region['id'],
                                'reg3_city'     => 0,
                            );
                            break;
                        case Geo::lvlCity:
                            $regions[] = array(
                                'reg1_country'  => $region['country'],
                                'reg2_region'   => $region['pid'],
                                'reg3_city'     => $region['id'],
                            );
                            break;
                    }
                    # сохраним названия регионов, для вывода в списке в админ. панели
                    $title['regs'][ $region['id'] ] = array('lvl' => $region['numlevel'], 't' => $region['title'], 'c' => $region['country']);
                }
            }

            $result['title'] = $title;
            $result['regions'] = $regions;

        } while(false);

        return $result;
    }

    /**
     * Обработка данных категории
     * @param integer $nCategoryID ID категории
     * @return array $aData данные
     */
    protected function validateCategoryData($nCategoryID = 0)
    {
        $bSubmit = $this->isPOST();
        $aData['pid'] = $this->input->postget('pid', TYPE_UINT);
        $aParams = array(
            'price'          => TYPE_BOOL,
            'price_sett'     => TYPE_ARRAY,
            'addr'           => TYPE_BOOL,
            'addr_metro'     => TYPE_BOOL,
            'photos'         => TYPE_UINT,
            'seek'           => TYPE_BOOL,
            'list_type'      => TYPE_UINT,
            'regions_delivery' => TYPE_BOOL,
            'owner_business' => TYPE_BOOL,
            'owner_search'   => (Request::isPOST() ? TYPE_ARRAY_UINT : TYPE_UINT),
            'keyword_edit'   => TYPE_NOTAGS,
            'mtemplate'      => TYPE_BOOL, # Использовать общий шаблон SEO
            'is_virtual'     => TYPE_BOOL,
            'tpl_title_enabled' => TYPE_BOOL,
            'virtual_ptr'    => TYPE_UINT, # Указатель на реальную категорию
        );
        $this->input->postm($aParams, $aData);
        $this->input->postm_lang($this->model->langCategories, $aData);

        if (!$aData['is_virtual']) {
             $aData['virtual_ptr'] = null;
        } else {
            if ($aData['virtual_ptr'] === $aData['pid']) {
                $this->errors->set(
                    _t('bbs', 'Виртуальная категория не может ссылаться на свою родительскую категорию')
                );
            }
        }
        unset($aData['is_virtual']);

        $this->validateCategoryPriceSettings($aData['price_sett']);

        if ($bSubmit) {
            do {
                # основная категория обязательна
                if (!$aData['pid']) {
                    $this->errors->set(_t('bbs', 'Укажите основную категорию'));
                    break;
                } else {
                    $parent = $this->model->catData($aData['pid'], array('seek','addr','addr_metro','regions_delivery'));
                    if (empty($parent)) {
                        $this->errors->set(_t('bbs', 'Основная категория указана некорректно'));
                        break;
                    } else {
                        # наследуем настройки из основной категории:
                        foreach (array(
                            'seek', # тип размещения "ищу"
                            'addr', # адрес
                            //'addr_metro', # метро
                            'regions_delivery', # доставка в регионы
                        ) as $k) {
                            if (!$aData[$k] && $parent[$k]) {
                                $aData[$k] = $parent[$k];
                            }
                        }
                    }
                }
                # название обязательно
                if (isset($aData['title'][LNG]) && empty($aData['title'][LNG])) {
                    $this->errors->set(_t('bbs', 'Укажите название'));
                    break;
                }
                foreach ($aData['title'] as $k => $v) {
                    $aData['title'][$k] = str_replace(array('"'), '', $v);
                }

                # лимит фотографий
                if ($aData['photos'] > static::itemsImagesLimit()) {
                    $aData['photos'] = static::itemsImagesLimit();
                } else {
                    if ($aData['photos'] < static::itemsImagesLimit(false)) {
                        $aData['photos'] = static::itemsImagesLimit(false);
                    }
                }

                # keyword
                $sKeyword = $aData['keyword_edit'];
                if (empty($sKeyword) && !empty($aData['title'][LNG])) {
                    $sKeyword = mb_strtolower(func::translit($aData['title'][LNG]));
                }
                $sKeyword = preg_replace('/[^\p{L}\w0-9_\-]/iu', '', mb_strtolower($sKeyword));
                if (empty($sKeyword)) {
                    $this->errors->set(_t('bbs', 'Keyword указан некорректно'));
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
                    $this->errors->set(_t('bbs', 'Указанный keyword уже используется, укажите другой'));
                    break;
                }
                $aData['keyword_edit'] = $sKeyword;

                # строим полный путь "parent-keyword / ... / keyword"
                $aKeywordsPath = array();
                if ($aData['pid'] > self::CATS_ROOTID) {
                    $aParentCatData = $this->model->catData($aData['pid'], array('keyword'));
                    if (empty($aParentCatData)) {
                        $this->errors->set(_t('bbs', 'Основная категория указана некорректно'));
                        break;
                    } else {
                        $aKeywordsPath = explode('/', $aParentCatData['keyword']);
                    }
                }
                $aKeywordsPath[] = $sKeyword;
                $aKeywordsPath = join('/', $aKeywordsPath);
                $aData['keyword'] = $aKeywordsPath;

                # посадочный URL
                $landingData = $this->seo()->joinedLandingpage($this, 'search-category',
                    static::url('items.search', array('keyword'=>$aData['keyword'], 'region'=>false), true),
                    array('joined-id'=>$nCategoryID, 'joined-module'=>'bbs-cats'));
                $aData['landing_id'] = $landingData['id'];
                $aData['landing_url'] = $landingData['url'];

                $aData['owner_search'] = array_sum($aData['owner_search']);

                # тип списка по-умолчанию
                if ( ! array_key_exists($aData['list_type'], static::itemsSearchListTypes())) {
                    $aData['list_type'] = 0;
                } else if ($aData['list_type'] == static::LIST_TYPE_MAP && ! $aData['addr']) {
                    $aData['list_type'] = 0;
                }

            } while (false);
        } else {
            if (!$nCategoryID) {
                $aData['mtemplate'] = 1;
            }
        }

        bff::hook('bbs.admin.category.form.validate', array('id'=>$nCategoryID,'data'=>&$aData,'submit'=>$bSubmit));

        return $aData;
    }

    /**
     * Обработка данных категории: настройки цены
     * @param integer $nCategoryID ID категории
     * @return array $aData данные
     */
    protected function validateCategoryPriceSettings(&$aSettings)
    {
        $this->input->clean_array($aSettings, array(
                'title'     => TYPE_ARRAY_STR,
                'curr'      => TYPE_UINT,
                'ranges'    => TYPE_ARRAY,
                'ex'        => (Request::isPOST() ? TYPE_ARRAY_UINT : TYPE_UINT),
                'mod_title' => TYPE_ARRAY_STR,
            )
        );

        if (Request::isPOST()) {
            $ranges = & $aSettings['ranges'];
            if (!empty($ranges) && is_array($ranges)) {
                foreach ($ranges as $k => &$v) {
                    $v['from'] = floatval(trim(strip_tags($v['from'])));
                    $v['to'] = floatval(trim(strip_tags($v['to'])));

                    if (empty($v['from']) && empty($v['to'])) {
                        unset($ranges[$k]);
                        continue;
                    }
                }
            } else {
                $ranges = array();
            }
            $aSettings['ex'] = array_sum($aSettings['ex']);
        }
    }

    /**
     * Обработка данных типа категории
     * @param array $aData @ref данные
     * @param integer $nCategoryID ID категории
     * @param integer $nTypeID ID типа
     */
    protected function validateCategoryTypeData(&$aData, $nCategoryID, $nTypeID)
    {
        $this->input->postm_lang($this->model->langCategoriesTypes, $aData);

        if (Request::isPOST()) {
            if ($this->errors->no()) {
                # ...
            }
        }
    }

    # ------------------------------------------------------------------------------------------------------------------------------
    # Услуги / Пакеты услуг

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

                    if ($this->errors->no('bbs.admin.svc-service.submit',array('id'=>$nSvcID,'data'=>&$aDataSave,'before'=>$aData))) {
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
            'svc'  => $svc->svcListing(Svc::TYPE_SERVICE, $this->module_name, array(
                        (!static::PRESS_ON ? 'press' : '')
                    )
                ),
            'cats' => $this->model->catsOptions('adm-svc-prices-ex', 0, _t('bbs', 'Выберите категорию')),
        );
        unset($aData['svc']['limit']);

        # Подготавливаем данные о региональной стоимости услуг для редактирования
        $aData['price_ex'] = $this->model->svcPriceExEdit();

        return $this->viewPHP($aData, 'admin.svc.services');
    }

    /**
     * Пакеты услуг
     */
    public function svc_packs()
    {
        if (!$this->haveAccessTo('svc')) {
            return $this->showAccessDenied();
        }

        $svc = Svc::model();

        if (Request::isPOST()) {
            $aResponse = array();

            switch ($this->input->getpost('act')) {
                case 'update': # сохранение
                {
                    $nSvcID = $this->input->post('id', TYPE_UINT);
                    if (!$nSvcID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $aData = $svc->svcData($nSvcID, array('id', 'type'));
                    if (empty($aData) || $aData['type'] != Svc::TYPE_SERVICEPACK) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $this->svcValidateData($nSvcID, Svc::TYPE_SERVICEPACK, $aDataSave);

                    if ($this->errors->no('bbs.admin.svc-pack.submit',array('id'=>$nSvcID,'data'=>&$aDataSave,'before'=>$aData))) {
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

                        # сохраняем информацию о пакете
                        $svc->svcSave($nSvcID, $aDataSave);
                    }
                }
                break;
                case 'reorder': # сортировка пакетов
                {

                    $aSvc = $this->input->post('svc', TYPE_ARRAY_UINT);
                    $svc->svcReorder($aSvc, Svc::TYPE_SERVICEPACK);
                }
                break;
                case 'del': # удаление пакета услуг
                {

                    $nSvcID = $this->input->post('id', TYPE_UINT);
                    if (!$nSvcID) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $aData = $svc->svcData($nSvcID, array('id', 'type'));
                    if (empty($aData) || $aData['type'] != Svc::TYPE_SERVICEPACK) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $aResponse['redirect'] = $this->adminLink(bff::$event);
                    $bSuccess = $svc->svcDelete($nSvcID);
                    if (empty($bSuccess)) {
                        $this->errors->impossible();
                    }
                    $this->ajaxResponseForm($aResponse);
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
            'packs' => $svc->svcListing(Svc::TYPE_SERVICEPACK, $this->module_name),
            'svc'   => $svc->svcListing(Svc::TYPE_SERVICE, $this->module_name),
            'curr'  => Site::currencyDefault(false),
            'cats' => $this->model->catsOptions('adm-svc-prices-ex', 0, _t('bbs', 'Выберите категорию')),
        );

        # Подготавливаем данные о региональной стоимости услуг для редактирования
        $aData['price_ex'] = $this->model->svcPriceExEdit();


        return $this->viewPHP($aData, 'admin.svc.packs');
    }

    /**
     * Добавление Пакета услуг
     */
    public function svc_packs_create()
    {
        if (!$this->haveAccessTo('svc')) {
            return $this->showAccessDenied();
        }

        $aData = $this->input->postm(array(
                'title'   => TYPE_NOTAGS,
                'keyword' => TYPE_NOTAGS,
            )
        );

        $svc = Svc::model();

        if (Request::isPOST()) {

            if (empty($aData['title'])) {
                $this->errors->set(_t('bbs', 'Название указано некорректно'));
            }

            if (empty($aData['keyword'])) {
                $this->errors->set(_t('bbs', 'Keyword указан некорректно'));
            } else {
                if ($svc->svcKeywordExists($aData['keyword'], $this->module_name)) {
                    $this->errors->set(_t('bbs', 'Указанный keyword уже используется'));
                }
            }

            if ($this->errors->no()) {
                $aData['type'] = Svc::TYPE_SERVICEPACK;

                $this->svcValidateData(0, $aData['type'], $aDataSave);
                $aData = array_merge($aData, $aDataSave);
                $aData['module'] = $this->module_name;
                $aData['module_title'] = 'Объявления';
                if ($this->errors->no('bbs.admin.svc-pack.submit',array('id'=>0,'data'=>&$aData))) {
                    $nSvcID = $svc->svcSave(0, $aData);
                    $bSuccess = !empty($nSvcID);
                    $this->adminRedirect(($bSuccess ? Errors::SUCCESS : Errors::IMPOSSIBLE), 'svc_packs');
                }
            }
        }

        return $this->viewPHP($aData, 'admin.svc.packs.create');
    }

    /**
     * Проверка данных услуги / пакета услуг
     * @param integer $nSvcID ID услуги / пакета услуг
     * @param integer $nType тип Svc::TYPE_
     * @param array $aData @ref проверенные данные
     */
    protected function svcValidateData($nSvcID, $nType, &$aData)
    {
        $aParams = array(
            'price' => TYPE_PRICE,
        );

        if ($nType == Svc::TYPE_SERVICE) {
            $aSettings = array(
                'period'   => TYPE_UINT, # период действия услуги
                'color'    => TYPE_NOTAGS, # цвет
                'add_form' => TYPE_BOOL, # в форме добавления
                'on'       => TYPE_BOOL, # включена
            );
            switch ($nSvcID){
                case self::SERVICE_FIX:
                {
                    $aSettings['period_type']  =  TYPE_UINT; # тип услуги
                }
                break;
                case self::SERVICE_UP:
                {
                    $aSettings['auto_enabled']  =  TYPE_BOOL; # настройка автоподнятия
                    $aSettings['free_period']  =  TYPE_UINT; # настройка автоподнятия
                }
                break;
            }

            $aData = $this->input->postm($aParams);
            $aData['settings'] = $this->input->postm($aSettings);
            $this->input->postm_lang($this->model->langSvcServices, $aData['settings']);
            $aData['title'] = $aData['settings']['title_view'][LNG];

            if ($aData['settings']['period'] < 1) {
                $aData['settings']['period'] = 1;
            }
        } else {
            if ($nType == Svc::TYPE_SERVICEPACK) {
                $aData = $this->input->postm($aParams);
                $aSettings = array(
                    'color'    => TYPE_NOTAGS, # цвет
                    'add_form' => TYPE_BOOL, # в форме добавления
                    'on'       => TYPE_BOOL, # включен
                );
                $aSettings = $this->input->postm($aSettings);

                # услуги, входящие в пакет
                $aSvc = $this->input->post('svc', array(
                        TYPE_ARRAY_ARRAY,
                        'id'  => TYPE_UINT,
                        'cnt' => TYPE_UINT,
                    )
                );
                foreach ($aSvc as $k => $v) {
                    if (!$v['id'] ||
                        # исключаем услуги, у которых неуказано кол-во (кроме SERVICE_PRESS)
                        ($v['id'] != self::SERVICE_PRESS && !$v['cnt'])
                    ) {
                        unset($aSvc[$k]);
                    }
                }
                $aSettings['svc'] = $aSvc;

                # текстовые поля
                $this->input->postm_lang($this->model->langSvcPacks, $aSettings);

                if (!$nSvcID) {
                    $sTitle = $this->input->post('title', TYPE_STR);
                    foreach ($this->locale->getLanguages() as $lng) {
                        $aSettings['title_view'][$lng] = $sTitle;
                    }
                    $aData['title'] = $sTitle;
                } else {
                    $aData['title'] = $aSettings['title_view'][LNG];
                }
                $aData['settings'] = $aSettings;
            }
        }
        if (Request::isPOST()) {
            $priceEx = $this->input->post('price_ex', array(
                    TYPE_ARRAY_ARRAY,
                    'price'   => TYPE_PRICE,
                    'cats'    => TYPE_ARRAY_UINT,
                    'regions' => TYPE_ARRAY_UINT,
                )
            );
            $this->model->svcPriceExSave($nSvcID, $priceEx);
        }
    }

    /**
     * Таб лимиты в форме редактирования пользователя
     * @param integer $userID ID пользователя
     * @param integer $shopID ID магазина
     * @return string HTML
     */
    public function limitsPayedUser($userID, $shopID = 0)
    {
        if ( ! $userID) return '';
        $shops = array(0);
        if ($shopID) {
            if ( ! Shops::abonementEnabled()) {
                $shops[] = $shopID;
            }
        }
        $data = array('tabs' => array());
        $term = config::get('bbs_limits_payed_days', 0, TYPE_UINT);
        $data['term'] = $term;

        foreach ($shops as $s) {
            $shop = $s ? 1 : 0;
            # список купленных поинтов
            $limits = $this->model->limitsPayedUserByFilter(array(
                'user_id' => $userID,
                'shop'    => $shop,
            ), array('id', 'cat_id', 'items', 'expire', 'active'), false, '', 'cat, active DESC, id');
            $points = array();
            foreach ($limits as $v) {
                if ( ! isset($points[ $v['cat_id'] ])) {
                    $point = array(
                        'cat_id' => $v['cat_id'],
                        'limits' => array(),
                        'free'   => $this->model->limitsPayedFreeForCategory($v['cat_id'], $shop), # количество бесплатных объявлений
                        'cnt'    => 0,
                        'title'  => $this->limitsPayedCatTitle($v['cat_id']),
                        'paid'   => 0,
                    );
                    $points[ $v['cat_id'] ] = $point;
                }
                $points[ $v['cat_id'] ]['limits'][] = $v;
                $points[ $v['cat_id'] ]['paid'] += $v['items'];
            }
            # количество объявлений для поинтов у пользователя
            $items = $this->model->limitsPayedCategoriesForUser(array(
                'user_id' => $userID,
                'shop_id' => $s,
            ));
            foreach ($items as $v) {
                if (isset($points[ $v['point'] ])) {
                    $points[ $v['point'] ]['cnt'] = $v['cnt'];
                }
            }
            foreach ($points as & $v) {
                $cnt = $v['cnt'] - $v['free'];
                if ($cnt < 0) {
                    $cnt = 0;
                }
                foreach ($v['limits'] as & $vv) {
                    $vv['cnt'] = 0;
                    if ( ! $vv['active']) continue;
                    $vv['cnt'] = $cnt > $vv['items'] ? $vv['items'] : $cnt;
                    $cnt -= $vv['items'];
                    if ($cnt < 0) {
                        $cnt = 0;
                    }
                } unset($vv);
            } unset($v);
            $data['points'] = $points;
            $data['key'] = $shop ? 'shops' : 'bbs';
            $data['tpl'] = 'payed.user.tab';
            $data['tabs'][$shop] = array(
                'id' => $shop,
                'key' => $data['key'],
                't' => $shop ? _t('shops', 'Магазин') : _t('bbs', 'Объявления'),
                'content' => $this->viewPHP($data, 'admin.limits.tpl'));
        }
        if ($shopID && Shops::abonementEnabled()) {
            $data['tabs'][1] = array(
                'id' => 1,
                'key' => 'shops',
                't' => _t('shops', 'Магазин'),
                'content' => '<div class="alert alert-info">'._t('bbs', 'Настройки лимитирования объявлений выполняются в тарифах услуги "Абонемент"').'</div>',
            );
        }
        $data['tpl'] = 'payed.user';
        return $this->viewPHP($data, 'admin.limits.tpl');
    }





}