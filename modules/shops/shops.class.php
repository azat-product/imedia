<?php

class Shops_ extends ShopsBase
{
    public function init()
    {
        parent::init();

        if (bff::$class == $this->module_name && Request::isGET()) {
            bff::setActiveMenu('//shops');
        }
    }

    /**
     * Поиск и результаты поиска
     * @return mixed
     */
    public function search()
    {
        $pageSize = config::sysAdmin('shops.search.pagesize', 8, TYPE_UINT);
        $f = $this->searchFormData();
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

        # seo данные
        $seoKey = '';
        $seoNoIndex = false;
        $seoData = array(
            'page'   => &$f['page'],
            'region' => Geo::regionData(($f_region ? $f_region : Geo::defaultCountry())),
        );
        if (Geo::coveringType(Geo::COVERING_COUNTRIES)) {
            $regionData = Geo::regionData($f_region);
            if (!$f_region) $seoData['region'] = '';
            $seoData['city'] = (Geo::isCity($regionData) ? Geo::regionData($f_region) : '');
            $seoData['country'] = (!empty($regionData) ? ($regionData['numlevel'] == Geo::lvlCountry ? $regionData : Geo::regionData($regionData['country'])) : '');
        }

        # Данные о категории:
        $catID = 0;
        $catData = array();
        $catFields = array('id', 'numlevel', 'enabled');
        $catsEnabled = static::categoriesEnabled();
        $catModel = ($catsEnabled ? $this->model : BBS::model());
        $catFilter = array();
        if ( ! $catsEnabled) {
            $catFilter[':ignoreVirtual'] = 'virtual_ptr IS NULL';
        }
        if (!Request::isAJAX()) {
            $catKey = $this->input->get('cat', TYPE_STR);
            $catKey = trim($catKey, ' /\\');
            if (!empty($catKey)) {
                $catFilter['keyword'] = $catKey;
                $catData = $catModel->catDataByFilter($catFilter, array_merge($catFields, array(
                            'pid',
                            'subs',
                            'keyword',
                            'numleft',
                            'numright',
                            'enabled',
                            'title',
                            'mtitle',
                            'mkeywords',
                            'mdescription',
                            'mtemplate',
                            'seotext',
                            'titleh1'
                        )
                    )
                );
                if (empty($catData) || !$catData['enabled']) {
                    $this->errors->error404();
                }
                bff::filterData('shops-search-category', $catData);
                $catID = $f_c = $catData['id'];
                $catData['crumbs'] = $this->categoryCrumbs($catID, __FUNCTION__);

                # seo: Поиск в категории
                $seoKey = 'search-category';
                $metaCategories = array();
                foreach ($catData['crumbs'] as $k => &$v) {
                    if ($k) {
                        $metaCategories[] = $v['title'];
                    }
                }
                unset($v);
                $seoData['category'] = $catData['title'];
                $seoData['categories'] = join(', ', $metaCategories);
                $seoData['categories.reverse'] = join(', ', array_reverse($metaCategories, true));
                $seoData['category+parent'] = join(', ', array_reverse(array_splice($metaCategories,(sizeof($metaCategories) > 2 ? sizeof($metaCategories) - 2 : 0))));
                if (!$catsEnabled) {
                    $catData['mtemplate'] = 1;
                }
            } else {
                # seo: Поиск (все категории)
                $seoKey = 'search';
            }
        } else {
            $catID = $f_c;
            $catFilter['id'] = $catID;
            $catData = $catModel->catDataByFilter($catFilter, $catFields);
            if (empty($catData) || !$catData['enabled']) {
                $catID = 0;
            }
        }
        if (!$catID) {
            $f_c = $f_ct = 0;
            $catKey = '';
            $catData = array('id' => 0);
            if (!Request::isAJAX()) {
                $catData['crumbs'] = $this->categoryCrumbs(0, __FUNCTION__);
            }
        }

        # Формируем запрос поиска:
        $sqlTablePrefix = 'S.';
        $sql = array(
            'status' => self::STATUS_ACTIVE,
        );
        if (static::premoderation()) {
            $sql[':mod'] = $sqlTablePrefix . 'moderated > 0';
        }
        if ($f_region) {
            $aRegion = Geo::regionData($f_region);
            switch ($aRegion['numlevel']) {
                case Geo::lvlCountry:  $sql['reg1_country'] = $f_region; break;
                case Geo::lvlRegion:   $sql['reg2_region']  = $f_region; break;
                case Geo::lvlCity:     $sql['reg3_city']    = $f_region; break;
            }
        }
        $seoResetCounter = sizeof($sql); # всю фильтрацию ниже скрываем от индексации
        $sphinxSearch = false;        
        if (strlen($f_q) > 1) {
            if (ShopsSearchSphinx::enabled()){
                $sphinxSearch = $f_q;
            } else {
                $sql[] = array(
                    '('.$sqlTablePrefix.'title LIKE (:query) OR '.$sqlTablePrefix.'descr LIKE (:query))',
                    ':query' => "%$f_q%"
                );
            }
            Banners::i()->viewQuery($f_q);
        }
        if ($f_lt == self::LIST_TYPE_MAP) {
            # на карту выводим только с корректно указанными координатами
            $sql[':addr'] = $sqlTablePrefix . 'addr_lat!=0';
            $seoResetCounter++;
        }

        # Выполняем поиск магазинов:
        $aData = array('items' => array(), 'pgn' => '');

        if ($sphinxSearch) {
            $sphinx = $this->shopsSearchSphinx();
            $nTotal = $sphinx->searchShops($sphinxSearch, $sql, $f_c, true);
        } else {
            $nTotal = $this->model->shopsList($sql, $f_c, true);
        }
        
        if ($nTotal > 0) {
            $aPgnLinkQuery = $f;
            if ($f['c']) {
                unset($aPgnLinkQuery['c']);
            }
            if ($f['region']) {
                unset($aPgnLinkQuery['region']);
            }
            if ($f['lt'] == self::LIST_TYPE_LIST) {
                unset($aPgnLinkQuery['lt']);
            }
            $oPgn = new Pagination($nTotal, $pageSize, array(
                'link'  => static::url('search', array('keyword' => $catKey)),
                'query' => $aPgnLinkQuery
            ));
            if ($sphinxSearch) {
                $limit = $oPgn->getLimit();
                $res = $sphinx->searchShops($sphinxSearch, $sql, $f_c, false, $limit, $oPgn->getOffset());
                $aData['items'] = $this->model->shopsList(array('id' => $res), false, false,  'LIMIT '.$limit, 'FIELD('.$sqlTablePrefix.'id,'.join(',', $res).')'); # MySQL only
            } else {
                $aData['items'] = $this->model->shopsList($sql, $f_c, false, $oPgn->getLimitOffset());
            }
            if (!empty($aData['items'])) {
                foreach ($aData['items'] as &$v) {
                    $v['logo'] = ShopsLogo::url($v['id'], $v['logo'], ShopsLogo::szList);
                    $v['link'] = static::urlDynamic($v['link']);
                    $v['phones'] = (!empty($v['phones']) ? func::unserialize($v['phones']) : array());
                    $v['social'] = (!empty($v['social']) ? func::unserialize($v['social']) : array());
                    $v['has_contacts'] = ($v['phones'] || $v['social']);
                    $v['ex'] = $v['id_ex'] . '-' . $v['id'];
                    unset($v['id_ex']);
                    if ($f_lt == self::LIST_TYPE_MAP) {
                        unset($v['region_id'], $v['region_title'], $v['addr_addr'],
                        $v['has_contacts'], $v['phones'], $v['site'], $v['social']);
                    }
                }
                unset($v);
            }
            $aData['pgn'] = $oPgn->view();
            $f['page'] = $oPgn->getCurrentPage();
        }

        $nNumStart = ($f_page <= 1 ? 1 : (($f_page - 1) * $pageSize) + 1);
        if (Request::isAJAX()) { # ajax ответ
            $this->ajaxResponseForm(array(
                    'list'  => $this->searchList(bff::device(), $f_lt, $aData['items'], $nNumStart, array('filter'=>&$f)),
                    'items' => &$aData['items'],
                    'pgn'   => $aData['pgn'],
                    'total' => $nTotal,
                )
            );
        }

        # seo
        $this->urlCorrection(static::url('search', array('keyword' => $catKey)));
        $this->seo()->robotsIndex(!(sizeof($sql) - $seoResetCounter) && !$seoNoIndex);
        $this->seo()->canonicalUrl(static::url('search', array('keyword' => $catKey), true),
            array('page' => $f['page']),
            ($nTotal > 0 ? array('page-current' => $f['page'], 'page-last' => $oPgn->getPageLast()) : array())
        );
        # подготавливаем хлебные крошки для подстановки макросов
        if ($catID > 0) {
            foreach ($catData['crumbs'] as &$v) {
                if ( ! $v['id']) continue;
                $seoData['category'] = $v['title'];
                $this->setMeta($seoKey, $seoData, $v, array(
                    'breadcrumb' => array('ignore' => array((!$f_region ? 'region' : ''))),
                ));
            } unset($v);
        }
        $this->setMeta($seoKey, $seoData, $catData, array(
            'titleh1' => array('ignore' => array((!$f_region ? 'region' : ''),)),
        ));

        $aData['total'] = $nTotal;
        $aData['num_start'] = $nNumStart;
        $aData['cat'] =& $catData;
        $aData['f'] =& $f;
        $aData['show_open_link'] = (User::id() && !User::shopID() && bff::shopsEnabled());

        # Типы списка:
        $listTypes = array(
            static::LIST_TYPE_LIST => array('t'=>_t('shops','Списком'), 'i'=>'fa fa-th-list','a'=>0),
            static::LIST_TYPE_MAP  => array('t'=>_t('shops','На карте'),'i'=>'fa fa-map-marker','a'=>0),
        );
        if( ! isset($listTypes[$f['lt']]) ) $f['lt'] = key($listTypes);
        $listTypes[$f['lt']]['a'] = true;
        $aData['listTypes'] = &$listTypes;
        $aData['isMap'] = ($f['lt'] == static::LIST_TYPE_MAP);

        return $this->viewPHP($aData, 'search');
    }

    public function searchForm()
    {
        $aData['f'] = $this->searchFormData();

        # фильтр: категория (определяется в Shops::search)
        $catData = bff::filterData('shops-search-category');
        $catID = ( ! empty($catData['id']) ? $catData['id'] : 0 );
        $aData['catACTIVE'] = ($catID > 0);
        $aData['catACTIVE_STEP'] = ($aData['catACTIVE'] ? ($catData['subs'] || $catData['numlevel']>1 ? 2 : 1) : 1);
        $aData['catData'] = &$catData;
        $aData['catID'] = &$catID;

        return $this->viewPHP($aData, 'search.form');
    }

    public function searchFormData(&$dataUpdate = false)
    {
        static $data;
        if (isset($data)) {
            if ($dataUpdate !== false) {
                $data = $dataUpdate;
            }

            return $data;
        }

        $aParams = array(
            'c'    => TYPE_UINT, # id категорий
            'q'    => TYPE_NOTAGS, # поисковая строка
            'qm'   => TYPE_NOTAGS, # поисковая строка
            'lt'   => TYPE_UINT, # тип списка (self::LIST_TYPE_)
            'cnt'  => TYPE_BOOL, # только кол-во
            'page' => TYPE_UINT, # страница
        );

        $data = $this->input->postgetm($aParams);

        # поисковая строка
        $device = bff::device();
        $data['q'] = $this->input->cleanSearchString(
            (in_array($device, array(bff::DEVICE_DESKTOP, bff::DEVICE_TABLET)) ? $data['q'] : $data['qm']), 80
        );
        # страница
        if (!$data['page']) {
            $data['page'] = 1;
        }
        # регион
        $data['region'] = Geo::filter('id'); # user

        return $data;
    }

    /**
     * Формирование результатов поиска (список магазинов)
     * @param string $deviceID тип устройства
     * @param integer $nListType тип списка (self::LIST_TYPE_)
     * @param array $aShops @ref данные о найденных магазинах
     * @param integer $nNumStart изначальный порядковый номер
     * @param array $aExtra доп. данные
     * @return mixed
     */
    public function searchList($deviceID, $nListType, array &$aShops, $nNumStart = 1, array $aExtra = array())
    {
        static $prepared = false;
        if (!$prepared) {
            $prepared = true;
            foreach ($aShops as &$v) {
                $v['num'] = $nNumStart++; # порядковый номер (для карты)
            }
            unset($v);
        }

        if (empty($aShops)) {
            return $this->showInlineMessage(array(
                    '<br />',
                    _t('bbs', 'Магазинов по вашему запросу не найдено')
                )
            );
        }

        $aTemplates = array(
            self::LIST_TYPE_LIST => 'search.list.list',
            self::LIST_TYPE_MAP  => 'search.list.map',
        );
        $aData = $aExtra;
        $aData['items'] = &$aShops;
        $aData['device'] = $deviceID;
        return $this->viewPHP($aData, $aTemplates[$nListType]);
    }

    /**
     * Cтраница магазина
     */
    public function view()
    {
        $shopID = $this->input->get('id', TYPE_UINT);
        if (!$shopID) {
            $this->errors->error404();
        }

        $shop = $this->model->shopDataSidebar($shopID);
        if (empty($shop) || $shop['status'] == static::STATUS_REQUEST) {
            $this->errors->error404();
        }
        if ($shop['status'] == static::STATUS_NOT_ACTIVE) {
            return $this->showInlineMessage(_t('shops', 'Магазин был временно деактивирован модератором'));
        }
        if ($shop['status'] == static::STATUS_BLOCKED) {
            return $this->showInlineMessage(_t('shops', 'Магазин был заблокирован модератором по причине:<br /><b>[reason]</b>',
                    array('reason' => $shop['blocked_reason'])
                )
            );
        }

        if ($userID = $shop['user_id']) {
            $user = Users::model()->userData($userID, array('login', 'activated', 'blocked', 'blocked_reason'));
            if (empty($user) || !$user['activated']) {
                return $this->showInlineMessage(_t('shops', 'Ошибка просмотра страницы магазина. Обратитесь к администратору'));
            }
            if ($user['blocked']) {
                return $this->showInlineMessage(_t('users', 'Аккаунт владельца магазина был заблокирован по причине:<br /><b>[reason]</b>',
                        array('reason' => $user['blocked_reason'])
                    )
                );
            }
            $shop['user'] = & $user;
        }

        # Подготовка данных
        $shopID = $shop['id'];
        $shop['has_contacts'] = ($shop['phones'] || !empty($shop['social']));
        $shop['addr_map'] = (!empty($shop['addr_addr']) && (floatval($shop['addr_lat']) || floatval($shop['addr_lon'])));
        $shop['descr'] = nl2br($shop['descr']);
        $shop['descr_visible_limit'] = 100;

        # Разделы
        $tab = trim($this->input->getpost('tab', TYPE_NOTAGS), ' /');
        $tabs = array(
            'items'   => array(
                't'   => _t('shops', 'Объявления магазина'),
                'm'   => 'BBS',
                'ev'  => 'shop_items',
                'url' => static::urlShop($shop['link']),
                'a'   => false
            ),
            'contact' => array(
                't'   => _t('shops', 'Написать сообщение'),
                'm'   => 'Shops',
                'ev'  => 'shop_contact',
                'url' => static::urlContact($shop['link']),
                'a'   => false
            ),
        );
        if (User::shopID() == $shopID || !$shop['user_id']) {
            unset($tabs['contact']);
        }
        # Расширяем
        $tabs = bff::filter('shops.view.tabs', $tabs, array(
            'tab' => &$tab, 'shop' => &$shop,
        ));
        # Сортируем
        func::sortByPriority($tabs);

        if (!isset($tabs[$tab])) {
            $tab = 'items';
        }
        $tabs[$tab]['a'] = true;

        $data = array(
            'shop'        => &$shop,
            'social'      => static::socialLinksTypes(),
            'url_promote' => static::url('shop.promote', array('id' => $shopID, 'from' => 'view')),
            'has_owner'   => !empty($shop['user_id']),
            'is_owner'    => User::isCurrent($shop['user_id']),
        );
        $data['url_promote_visible'] = bff::servicesEnabled() && ($data['is_owner'] || bff::shopsEnabled(true));

        if ($data['has_owner']) {
            $data += array(
                'content' => call_user_func((is_array($tabs[$tab]['ev']) ? $tabs[$tab]['ev'] : array(bff::module($tabs[$tab]['m']), $tabs[$tab]['ev'])), $shopID, $shop),
                'tab'     => $tab,
                'tabs'    => &$tabs,
            );
        } else {
            # SEO: Страница магазина (без владельца)
            $this->urlCorrection($shop['link']);
            $this->seo()->canonicalUrl($shop['link_dynamic']);
            $this->setMeta('shop-view', array(
                    'title'       => $shop['title'],
                    'description' => tpl::truncate($shop['descr'], 150),
                    'region'      => ($shop['region_id'] ? $shop['city'] : ''),
                    'country'     => (!empty($shop['country']) ? $shop['country'] : ''),
                    'page'        => 1,
                ), $shop
            );
            $this->seo()->setSocialMetaOG($shop['share_title'], $shop['share_description'], $shop['logo'], $shop['link'], $shop['share_sitename']);
            $data['content'] = '';
        }
        if ($tab == 'items') {
            $data['share_code'] = config::get('shops_shop_share_code');
        }

        $data['breadcrumbs'] = array(
            array('title'=>_t('shops','Магазины'),'link'=>static::url('search', array('region'=>$shop['region'], 'city'=>$shop['city'])),'active'=>false),
            array('title'=>$shop['title'],'active'=>true),
        );

        $data['request_form_visible'] = (!$data['has_owner'] && static::categoriesEnabled() && !User::shopID() && bff::shopsEnabled(true));

        # Баннеры
        Banners::i()->viewQuery($data['shop']['title']);

        # Last Modified
        if (!BFF_DEBUG) {
            Request::lastModified($data['shop']['modified']);
        }

        return $this->viewPHP($data, 'view');
    }

    /**
     * Форма связи с магазином
     * @param integer $shopID ID магазина
     * @param array $shopData данные магазина
     */
    public function shop_contact($shopID, $shopData)
    {
        if (User::isCurrent($shopData['user_id'])) {
            $this->redirect($shopData['link']);
        }

        # SEO:
        $this->urlCorrection(static::urlContact($shopData['link']));
        $this->seo()->robotsIndex(false);
        bff::setMeta(_t('shops', 'Отправить сообщение магазину [shop]', array('shop' => $shopData['title'])));

        if (Request::isPOST()) {
            Users::i()->writeFormSubmit(User::id(), $shopData['user_id'], 0, false, $shopID);
        }

        return $this->viewPHP($shopData, 'view.contact');
    }

    /**
     * Продвижение магазина
     * @param getpost ::uint 'id' - ID магазина
     */
    public function promote()
    {
        $aData = array();
        bff::setMeta(_t('shops', 'Продвижение магазина'));
        $sFrom = $this->input->postget('from', TYPE_NOTAGS);
        $nUserID = User::id();
        $nSvcID = $this->input->postget('svc', TYPE_UINT);
        $nShopID = $this->input->getpost('id', TYPE_UINT);
        $aShop = $this->model->shopData($nShopID, array(
                'id',
                'user_id',
                'status',
                'blocked_reason',
                'region_id',
                'title',
                'link',
                'svc',
                'svc_fixed_to',
                'svc_marked_to',
                'svc_abonement_id',
                'svc_abonement_expire',
                'svc_abonement_one_time',
            )
        );
        if (!empty($_GET['success'])) {
            if ($aShop['status'] == self::STATUS_REQUEST) {
                $this->redirect(static::url('my.shop', array('success' => 1)));
            }
            $sMessage = _t('shops', 'Вы успешно активировали услугу для магазина');
            if ($nSvcID === static::SERVICE_ABONEMENT) {
                $sMessage = _t('shops', 'Вы успешно активировали тариф для магазина');
            }
            return $this->showInlineMessage(array(
                    $sMessage,
                    '<br />',
                    (!empty($aShop) ? _t('shops', '<a [link]>[title]</a>', array(
                            'link'  => 'href="' . $aShop['link'] . '"',
                            'title' => $aShop['title'],
                        )
                    ) : '')
                )
            );
        }
        $aPaySystems = Bills::getPaySystems(true, true);

        $aSvc = $this->model->svcData();
        $aSvcPrices = $this->model->svcPricesEx(array_keys($aSvc), $aShop['region_id']);
        foreach ($aSvcPrices as $k => $v) {
            if (!empty($v)) {
                $aSvc[$k]['price'] = $v;
            }
        }

        $nUserBalance = $this->security->getUserBalance();

        if (Request::isPOST()) {
            $ps = $this->input->getpost('ps', TYPE_STR);
            if (!$ps || !array_key_exists($ps, $aPaySystems)) {
                $ps = key($aPaySystems);
            }
            $nPaySystem = $aPaySystems[$ps]['id'];
            $sPaySystemWay = $aPaySystems[$ps]['way'];

            $aResponse = array();
            do {
                if (!bff::servicesEnabled()) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$nShopID || empty($aShop) || $aShop['status'] != self::STATUS_ACTIVE) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$nSvcID || !isset($aSvc[$nSvcID])) {
                    $this->errors->set(_t('shops', 'Выберите услугу'));
                    break;
                }
                $aSvcSettings = array();
                $nSvcPrice = $aSvc[$nSvcID]['price'];

                if (static::abonementEnabled() && $nSvcID == static::SERVICE_ABONEMENT) {
                    if ($this->abonementOpen($aResponse, $nShopID, $aSvcSettings)) {
                        break;
                    } else {
                        $nSvcPrice = $aSvcSettings['price'];
                        unset($aResponse['redirect'], $aSvcSettings['price']);
                    }
                }

                # конвертируем сумму в валюту для оплаты по курсу
                $pay = Bills::getPayAmount($nSvcPrice, $ps);

                if ($ps == 'balance' && $nUserBalance >= $nSvcPrice) {
                    # активируем услугу (списываем со счета пользователя)
                    $aResponse['redirect'] = static::url('shop.promote', array(
                            'id'      => $nShopID,
                            'success' => 1,
                            'from'    => $sFrom
                        )
                    );
                    $aResponse['activated'] = $this->svc()->activate($this->module_name, $nSvcID, false, $nShopID, $nUserID, $nSvcPrice, $pay['amount'], $aSvcSettings);
                } else {
                    # создаем счет для оплаты
                    $nBillID = $this->bills()->createBill_InPay($nUserID, $nUserBalance,
                        $nSvcPrice,
                        $pay['amount'],
                        $pay['currency'],
                        Bills::STATUS_WAITING,
                        $nPaySystem, $sPaySystemWay,
                        _t('bills', 'Пополнение счета через [system]', array('system' => $this->bills()->getPaySystemTitle($nPaySystem))),
                        $nSvcID, true, # помечаем необходимость активации услуги сразу после оплаты
                        $nShopID, $aSvcSettings
                    );
                    if (!$nBillID) {
                        $this->errors->set(_t('bills', 'Ошибка создания счета'));
                        break;
                    }
                    $aResponse['pay'] = true;
                    # формируем форму оплаты для системы оплаты
                    $aResponse['form'] = $this->bills()->buildPayRequestForm($nPaySystem, $sPaySystemWay, $nBillID, $pay['amount']);
                }
            } while (false);
            $this->ajaxResponseForm($aResponse);
        }

        if (!$nShopID || empty($aShop)) {
            return $this->showInlineMessage(_t('shops', 'Магазин был удален, либо ссылка указана некорректно'));
        }
        # проверяем статус ОБ
        if ($aShop['status'] == self::STATUS_BLOCKED) {
            return $this->showInlineMessage(_t('shops', 'Магазин был заблокирован модератором, причина: [reason]', array(
                        'reason' => $aShop['blocked_reason']
                    )
                )
            );
        } else {
            if ($aShop['status'] != self::STATUS_ACTIVE) {
                return $this->showInlineMessage(_t('shops', 'Возможность продвижения магазина будет доступна после его проверки модератором.'));
            }
        }
        $aData['shop'] =& $aShop;

        # способы оплаты
        $aData['curr'] = Site::currencyDefault();
        $aData['ps'] =& $aPaySystems;
        reset($aPaySystems);
        $aData['ps_active_key'] = key($aPaySystems);
        foreach ($aPaySystems as $k => &$v) {
            $v['active'] = ($k == $aData['ps_active_key']);
        }
        unset($v);

        # список услуг
        foreach ($aSvc as &$v) {
            $v['active'] = ($v['id'] == $nSvcID);
            $aSvcPrices[$v['id']] = $v['price'];
        }
        unset($v);
        $aData['svc'] =& $aSvc;
        $aData['svc_id'] = $nSvcID;
        $aData['svc_prices'] = $aSvcPrices;
        $aData['user_id'] = $aShop['user_id'];
        $aData['user_balance'] =& $nUserBalance;
        $aData['from'] = $sFrom;

        $this->seo()->robotsIndex(false);

        tpl::includeJS('shops.promote', false, 3);

        # Абонемент: тарифы
        if (static::abonementEnabled()) {
            if ($nSvcID == static::SERVICE_ABONEMENT) {
                $aData['abonements'] = $this->abonementForm($aData);
            } else if ($aData['shop']['svc_abonement_id']) {
                $aAbonData = $this->model->abonementData($aData['shop']['svc_abonement_id']);
                if (!empty($aAbonData['svc_fix'])) {
                    $aData['svc'][static::SERVICE_FIX]['disabled'] = true;
                }
                if (!empty($aAbonData['svc_mark'])) {
                    $aData['svc'][static::SERVICE_MARK]['disabled'] = true;
                }
            }
        }

        return $this->viewPHP($aData, 'promote');
    }

    /**
     * Подача заявки на закрепление магазина за пользователем
     * @param getpost ::uint 'id' - ID магазина
     */
    public function request()
    {
        $shopID = $this->input->postget('id', TYPE_UINT);

        $aResponse = array();
        do {
            if (!Request::isPOST() || User::shopID()) {
                $this->errors->reloadPage();
                break;
            }
            $shopData = $this->model->shopData($shopID, array('id', 'status', 'user_id'));
            if (!$shopID || empty($shopData) || $shopData['user_id'] > 0 ||
                $shopData['status'] != self::STATUS_ACTIVE
            ) {
                $this->errors->reloadPage();
                break;
            }

            if (User::id()) {
                $data = User::data(array('name', 'email', 'phone'));
            } else {
                $data = $this->input->postm(array(
                        'name'  => array(TYPE_TEXT, 'len' => 50),
                        'phone' => array(TYPE_TEXT, 'len' => 50),
                        'email' => array(TYPE_TEXT, 'len' => 100),
                    )
                );
                $data['name'] = preg_replace('/[^a-zа-яёїієґ\-\s0-9]+/iu', '', $data['name']);
                if (empty($data['name'])) {
                    $this->errors->set(_t('shops', 'Укажите ваше имя'), 'name');
                }
                if (empty($data['phone'])) {
                    $this->errors->set(_t('shops', 'Укажите ваш номер телефона'), 'phone');
                }
                if (!$this->input->isEmail($data['email'])) {
                    $this->errors->set(_t('', 'E-mail адрес указан некорректно'), 'email');
                }
            }

            $data['description'] = $this->input->post('description', TYPE_TEXT, array('len' => 3000));
            if (mb_strlen($data['description']) < 10) {
                $this->errors->set(_t('shops', 'Расскажите как вы связаны с данным магазином немного подробнее'), 'description');
            }

            if ($this->errors->no('shops.request.submit',array('data'=>&$data,'shops'=>$shopData))) {
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-request-join', (User::id() ? 5 : 15))) {
                    break;
                }

                $data['shop_id'] = $shopID;

                $res = $this->model->requestSave(0, $data);
                if (!$res) {
                    $this->errors->reloadPage();
                } else {
                    $this->updateRequestsCounter(1, true);
                }
            }

        } while (false);

        $this->ajaxResponseForm($aResponse);
    }

    /**
     * Кабинет пользователя: Магазин
     * Список объявлений, добавленных от "магазина"
     */
    public function my_shop()
    {
        $nShopID = User::shopID();
        if (!$nShopID) {
            # магазин не создан => отправляем на форму заявки на открытие
            $this->redirect(static::url('my.open'));
        }
        $aData = $this->model->shopData($nShopID, array(
                'id',
                'title',
                'link',
                'status',
                'moderated',
                'blocked_reason'
            )
        );
        # ошибка получения данных о магазине
        if (empty($aData)) {
            bff::log('Неудалось получить данные о магазине #' . $nShopID . ' [shops::my_shop]');

            return $this->showInlineMessage(array(
                    _t('shops', 'Ошибка формирования списка объявлений.'),
                    '<br />',
                    _t('shops', 'Для выяснения причины обратитесь к администратору.'),
                )
            );
        }
        # магазин заблокирован
        if ($aData['status'] == static::STATUS_BLOCKED) {
            return $this->formStatus('edit.blocked', $aData);
        }
        # магазин деактивирован
        if ($aData['status'] == static::STATUS_NOT_ACTIVE) {
            return $this->formStatus('edit.notactive', $aData);
        } # результат открытия магазина (ожидание проверки модератора)
        else {
            if (!empty($_GET['success']) || $aData['status'] == static::STATUS_REQUEST) {
                $this->security->setTokenPrefix('');

                return $this->formStatus('add.success', $aData);
            }
        }

        return BBS::i()->my_items($nShopID);
    }

    /**
     * Кабинет пользователя: Настройки магазина
     */
    public function my_settings()
    {
        $nShopID = User::shopID();

        if (Request::isPOST()) {
            $aResponse = array();
            do {
                # проверка токена + реферера
                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                $this->validateShopData($nShopID, $aData);


                if (!$this->errors->no('shops.settings.submit',array('id'=>$nShopID,'data'=>&$aData))) {
                    break;
                }

                # отправляем на модерацию: смена название, описания, либо если заблокирован
                $aDataPrev = $this->model->shopData($nShopID, array('title', 'descr', 'status'));
                foreach (array('title','descr') as $k) {
                    if ($aDataPrev[$k] != $aData[$k]) {
                        $aData['moderated'] = 2;
                    }
                }
                if ($aDataPrev['status'] == static::STATUS_BLOCKED) {
                    $aData['moderated'] = 2;
                }

                # сохраняем настройки магазина
                $this->model->shopSave($nShopID, $aData);
                $aResponse['refill'] = array(
                    'title' => $aData['title'],
                    'descr' => $aData['descr']
                );
                $this->updateModerationCounter();

            } while (false);

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->model->shopData($nShopID, '*', true);
        # ошибка получения данных о магазине
        if (empty($aData)) {
            bff::log('Неудалось получить данные о магазине #' . $nShopID . ' [shops::my_settings]');

            return $this->showInlineMessage(array(
                    _t('shops', 'Ошибка редактирования настроек магазина.'),
                    '<br />',
                    _t('shops', 'Для выяснения причины обратитесь к администратору.'),
                )
            );
        }
        # магазин деактивирован
        if ($aData['status'] == static::STATUS_NOT_ACTIVE) {
            return $this->formStatus('edit.notactive', $aData);
        } # результат открытия магазина (ожидание проверки модератора)
        else {
            if ($aData['status'] == static::STATUS_REQUEST) {
                $this->security->setTokenPrefix('');

                return $this->formStatus('edit.moderating', $aData);
            }
        }

        return $this->form($nShopID, $aData);
    }

    /**
     * Кабинет пользователя: Настройки тарифов
     */
    public function my_abonement()
    {
        $nShopID = User::shopID();

        if ( ! static::abonementEnabled()) {
            bff::log('Услуга "Абонемент" выключена [shops::my_abonement]');

            return $this->showInlineMessage(array(
                    _t('shops', 'Ошибка редактирования настроек тарифов магазина.'),
                    '<br />',
                    _t('shops', 'Для выяснения причины обратитесь к администратору.'),
                )
            );
        }

        if (Request::isPOST()) {
            $aResponse = array();
            do {
                $this->security->setTokenPrefix('my-settings');

                # проверка токена + реферера
                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }


                $act = $this->input->postget('act', TYPE_STR);
                switch ($act) {
                    case 'subscribe':
                        $prolong = $this->input->postget('auto', TYPE_UINT);
                        $this->model->shopSave($nShopID, array('svc_abonement_auto' => $prolong));
                        break;
                    default:
                        $this->abonementOpen($aResponse, $nShopID);
                        break;
                }
            } while (false);

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->model->shopData($nShopID, '*', true);
        # ошибка получения данных о магазине
        if (empty($aData)) {
            bff::log('Не удалось получить данные о магазине #' . $nShopID . ' [shops::my_settings]');

            return $this->showInlineMessage(array(
                    _t('shops', 'Ошибка редактирования настроек магазина.'),
                    '<br />',
                    _t('shops', 'Для выяснения причины обратитесь к администратору.'),
                )
            );
        }
        # магазин деактивирован
        if ($aData['status'] == static::STATUS_NOT_ACTIVE) {
            return $this->formStatus('edit.notactive', $aData);
        } # результат открытия магазина (ожидание проверки модератора)
        else {
            if ($aData['status'] == static::STATUS_REQUEST) {
                $this->security->setTokenPrefix('');

                return $this->formStatus('edit.moderating', $aData);
            }
        }

        return $this->abonementForm($aData);
    }

    /**
     * Активация услуги "Абонемент"
     * @param array $response @ref ajax ответ
     * @param integer $nShopID ID магазина
     * @param array $svcSettings @ref массив с данными для активации
     * @return bool true - удалось активировать абонемент, false - нет
     */
    protected function abonementOpen(& $response, $nShopID, & $svcSettings = array())
    {
        # ID тарифа
        $nAbonID = $this->input->postget('abonement_id', TYPE_INT);
        if ( ! $nAbonID) {
            $this->errors->unknownRecord();
            return false;
        }

        $nUserBalance = $this->security->getUserBalance();
        $nAbonPeriod = $this->input->postget('abonement_period', TYPE_INT);
        $nSvcID = static::SERVICE_ABONEMENT;
        $aSvc = $this->model->abonementData($nAbonID);
        $aSvc['module'] = $this->module_name;
        if (!$aSvc['price_free']) {
            $nSvcPrice = $aSvc['price'][$nAbonPeriod];
        } else {
            if ( ! $aSvc['price_free_period']) {
                $shop = $this->model->shopData($nShopID, array('svc_abonement_id'));
                if ($shop['svc_abonement_id'] == $nAbonID) {
                    $this->errors->set(_t('shops', 'У Вас уже активен данный тариф'));
                    return false;
                }
            }
            $nSvcPrice = 0;
        }
        $svcSettings['abonement_id'] = $nAbonID;
        $svcSettings['abonement_period'] = $nAbonPeriod;
        if ($nUserBalance >= $nSvcPrice) {
            # активируем услугу (списываем со счета пользователя)
            $activated = $this->svc()->activate($this->module_name, $nSvcID, $aSvc, $nShopID, User::id(), $nSvcPrice, $nSvcPrice, $svcSettings);
            if ($activated) {
                $response['redirect'] = static::url('shop.promote', array(
                    'id'      => $nShopID,
                    'success' => 1,
                    'svc'     => $nSvcID,
                ));
            }
            return true;
        } else {
            $svcSettings['price'] = $nSvcPrice;
            $response['redirect'] = static::url('shop.promote', array(
                'id'         => $nShopID,
                'svc'        => $nSvcID,
                'abonID'     => $nAbonID,
                'abonPeriod' => $nAbonPeriod,
            ));
        }
        return false;
    }

    /**
     * Кабинет пользователя: Открытие магазина
     */
    public function my_open()
    {
        $nUserID = User::id();
        if (!$nUserID) {
            return $this->showInlineMessage(_t('shops', 'Открытие магазина доступно только для авторизованных пользователей'), array('auth' => true));
        }

        $this->security->setTokenPrefix('my-settings');

        $this->validateShopData(0, $aData);

        if (Request::isPOST()) {
            $aResponse = array();
            do {
                # проверка токена + реферера
                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                if (User::shopID()) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$this->errors->no('shops.open.submit',array('data'=>&$aData))) {
                    break;
                }

                $aData['user_id'] = $nUserID;
                $aData['moderated'] = 0; # помечаем на модерацию
                if (static::premoderation()) {
                    $aData['status'] = self::STATUS_REQUEST;
                } else {
                    $aData['status'] = self::STATUS_ACTIVE;
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-open', 60)) {
                    break;
                }

                # создаем магазин
                $nShopID = $this->model->shopSave(0, $aData);
                if (!$nShopID) {
                    $this->errors->set(_t('shops', 'Ошибка открытия магазина, обратитесь в службу поддержки.'));
                    break;
                } else {
                    # связываем пользователя с магазином
                    $this->onUserShopCreated($nUserID, $nShopID);
                    # сохраняем логотип
                    $sLogoFilename = $this->input->postget('logo', TYPE_STR);
                    if (!empty($sLogoFilename)) {
                        $this->shopLogo($nShopID)->untemp($sLogoFilename, true);
                    }
                    if (static::premoderation()) {
                        $this->updateRequestsCounter(1);
                    } else {
                        $this->updateModerationCounter(1);
                    }
                    # Абонемент
                    if (static::abonementEnabled()) {
                        $this->abonementOpen($aResponse, $nShopID);
                    }
                }

                $aResponse['id'] = $nShopID;
                $aResponse['refill'] = array(
                    'title' => $aData['title'],
                    'descr' => $aData['descr']
                );
                if(empty($aResponse['redirect'])){
                    $aResponse['redirect'] = static::url('my.shop', array('success' => 1));
                }
            } while (false);

            $this->ajaxResponseForm($aResponse);
        }

        # Данные о пользователе
        $aUserData = User::data(array(
                'email',
                'phones',
                'region_id',
                'addr_addr',
                'addr_lat',
                'addr_lon',
                'shop_id'
            ), true
        );
        if ($aUserData['shop_id']) {
            $this->redirect(static::url('my.shop'));
        }
        # Корректируем регион пользователя
        if ($aUserData['region_id']) {
            $regionData = Geo::regionData($aUserData['region_id']);
            if ($regionData && !Geo::coveringRegionCorrect($regionData)) {
                $aUserData['region_id'] = 0;
                if (Geo::coveringType(Geo::COVERING_CITY)) {
                    $aUserData['region_id'] = Geo::coveringRegion();
                }
            }
            unset($regionData);
        }
        $aData = array_merge($aData, $aUserData);
        $aData['id'] = 0;
        $aData['user_id'] = $nUserID;
        $aData['abonements'] = $this->abonementForm($aData);
        $aData['logo'] = '';
        $aData['open_text'] = config::get('shops_form_add_' . LNG, '');
        bff::setMeta(_t('shops', 'Открытие магазина'));

        return $this->form(0, $aData);
    }

    /**
     * Формирование формы открытия/редактирования настроек магазина
     * @param integer $nShopID ID магазина
     * @param array $aData @ref настроки магазина
     * @return string HTML
     */
    protected function form($nShopID, array &$aData = array())
    {
        $aData['id'] = $nShopID;

        # логотип
        $oLogo = $this->shopLogo($nShopID);
        $aData['logo_preview'] = ShopsLogo::url($nShopID, (!empty($aData['logo']) ? $aData['logo'] : false),
            ShopsLogo::szList, false, true
        );
        $aData['logo_maxsize'] = $oLogo->getMaxSize(false);
        $aData['logo_maxsize_format'] = $oLogo->getMaxSize(true);

        # категории
        if (($aData['cats_on'] = static::categoriesEnabled())) {
            $aData['cats'] = $this->model->shopCategoriesIn($nShopID, ShopsCategoryIcon::SMALL);
            foreach ($aData['cats'] as &$v) {
                if ($v['pid'] > static::CATS_ROOTID) {
                    $v['icon'] = $v['picon'];
                    $v['title'] = $v['ptitle'] . ' &raquo; ' . $v['title'];
                    unset($v['picon'], $v['ptitle']);
                }
            }
            unset($v);
            $aData['cats_main'] = $this->catsList('form', 'init');
        } else {
            $aData['cats'] = array();
            $aData['cats_main'] = '';
        }

        # координаты по-умолчанию
        Geo::mapDefaultCoordsCorrect($aData['addr_lat'], $aData['addr_lon']);

        $aData['is_edit'] = ($nShopID > 0);
        $aData['is_open'] = ! $aData['is_edit'];
        $aData['url_submit'] = ($aData['is_edit'] ? Users::url('my.settings', array('act'=>'shop')) : static::url('my.open') );
        $aData['titlesLang'] = static::titlesLang();
        $aData['languages'] = $this->locale->getLanguages(false);
        return $this->viewPHP($aData, 'my.form');
    }

    /**
     * Отображение статуса магазина
     * @param string $sFormStatus статус
     * @param array $aData @ref данные магазина
     * @return string
     */
    protected function formStatus($sFormStatus, array &$aData = array())
    {
        $aData['form_status'] = $sFormStatus;

        return $this->viewPHP($aData, 'my.form.status');
    }

    /**
     * Форма добавления/изменения услуги Абонемент
     * @param array $aData настроки магазина
     * @return string
     */
    protected function abonementForm(array $aData)
    {
        if ( ! static::abonementEnabled()) {
            return '';
        }

        $aData['abonements'] = $this->model->abonementsList();
        if (empty($aData['abonements'])) {
            return _t('shops', 'Список тарифов пуст');
        }

        # Устанавливаем тариф по-умолчанию исходя из настроек при открытии магазина
        if ( ! empty($aData['abonements'])) {
            foreach ($aData['abonements'] as $v) {
                $aData['prices'][$v['id']] = $v['price'];
                if ($v['is_default']) {
                    $aDefault = $v;
                }
            }
        }

        # Переопределяем тариф по-умолчанию если указан ID тарифа
        $abonID = $this->input->postget('abonID', TYPE_INT);
        if ($abonID) {
            $aDefault = $aData['abonements'][$abonID];
        }

        # Если у пользователя установлен тарифный план - формируем информацию о нем
        if ( ! empty($aData['id']) && ! empty($aData['svc_abonement_id']))
        {
            $aData['user_abonement'] = $this->model->abonementData($aData['svc_abonement_id']);
            if ( ! empty($aData['user_abonement']['price'])) {
                foreach ($aData['user_abonement']['price'] as $k => &$vv) {
                    if ($k == 0) {
                        $vv = array(
                            'pr' => $vv,
                            'ex' => _t('shops', 'неограниченного периода'),
                            'm'  => ''
                        );
                        continue;
                    }
                    $now = new DateTime($aData['svc_abonement_expire']);
                    $vv = array(
                        'pr' => $vv,
                        'ex' => $now->modify('+' . $k . ' month')->format('d.m.Y'),
                        'm'  => tpl::declension($k, _t('', 'месяц;месяца;месяцев'))
                    );
                } unset($vv);
            } else{
                $aData['user_abonement']['price'] =  array(
                    'pr' => 0,
                    'ex' => _t('shops', 'неограниченного периода'),
                    'm'  => ''
                );
            }
            $aData['user_abonement']['publicated'] = BBS::model()->itemsList(array(
                'user_id' => $aData['user_id'],
                'shop_id' => $aData['id'],
                'is_publicated' => 1,
                'status' => BBS::STATUS_PUBLICATED,
            ), true, array('context'=>'shops-abonement-form'));
            $aDefault = $aData['user_abonement'];
        }

        # Если тариф по-умолчанию не установлен берём первый из списка
        if (empty($aDefault)) {
            $aDefault = reset($aData['abonements']);
        }

        # Устанавливаем значения тарифа по-умолчанию
        $aData['default_price'] = $aDefault['price'];
        $aData['default_name'] = $aDefault['title'];
        $aData['is_default'] = $aDefault['id'];
        if ( ! empty($aData['id']) && empty($aData['svc_abonement_id'])) {
            $aData['is_default'] = 0;
        }
        # Формируем данные об использованных единоразовых тарифах
        if (isset($aData['svc_abonement_one_time'])) {
            $aData['svc_abonement_one_time'] = func::unserialize($aData['svc_abonement_one_time']);
        } else {
            $aData['svc_abonement_one_time'] = array();
        }

        # Если услуг несколько
        if (isset($aData['svc_prices'])) {
            $aData['svc_prices'] += $aData['prices'];
        }

        # Режим + форма
        $aData['edit'] = !empty($aData['user_abonement']);
        $aData['form'] = ($aData['edit'] || ( ! $aData['edit'] && ! empty($aData['id'])));
        if ($aData['edit']) {
            $aData['title'] = _t('shops', 'Смена тарифного плана');
        } else {
            $aData['title'] = _t('shops', 'Тарифный план');
        }
        $aData['curr'] = Site::currencyDefault();

        return $this->viewPHP($aData, 'svc.abonement.form');
    }

    /**
     * Загрузка / удаление логотипа магазина
     */
    public function logo()
    {
        $this->security->setTokenPrefix('my-settings');
        $nShopID = User::shopID();

        switch ($this->input->getpost('act')) {
            case 'upload': # загрузка
            {

                $bTmp = !$nShopID;
                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    $mResult = false;
                } else {
                    $mResult = $this->shopLogo($nShopID)->uploadQQ(true, !$bTmp);
                }

                $aResponse = array('success' => ($mResult !== false && $this->errors->no()));
                if ($mResult !== false) {
                    $aResponse = array_merge($aResponse, $mResult);
                    $aResponse['preview'] = ShopsLogo::url($nShopID, $mResult['filename'], ShopsLogo::szList, $bTmp);
                    if ($bTmp) {
                        $this->shopLogo($nShopID)->deleteTmp($this->input->postget('tmp', TYPE_STR));
                    } else {
                        # отправляем на пост-модерацию: смена логотипа
                        $this->model->shopSave($nShopID, array('moderated' => 2));
                    }
                }
                $aResponse['errors'] = $this->errors->get(true);

                $this->ajaxResponse($aResponse, 1);
            }
            break;
            case 'delete': # удаление
            {
                $aResponse = array();
                $oLogo = $this->shopLogo($nShopID);



                if ($this->security->validateToken(true, false)) {
                    if ($nShopID) {
                        $oLogo->delete(true);
                    } else {
                        $oLogo->deleteTmp($this->input->post('fn', TYPE_NOTAGS));
                    }
                    if ($this->errors->no()) {
                        $aResponse['preview'] = $oLogo->urlDefault(ShopsLogo::szList);
                    }
                }

                $this->ajaxResponseForm($aResponse);
            }
            break;
        }
    }

    public function ajax()
    {
        $response = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            case 'shop-contacts': # Просмотр контактов магазина в блоке справа
            {
                $ex = $this->input->post('ex', TYPE_STR);
                if (empty($ex)) {
                    $this->errors->reloadPage();
                    break;
                }
                list($ex, $shopID) = explode('-', $ex);

                $shop = $this->model->shopData($shopID, array(
                        'id',
                        'id_ex',
                        'status',
                        'phones',
                        'contacts',
                        'social'
                    )
                );

                if (empty($shop) || $shop['id_ex'] != $ex || $shop['status'] != static::STATUS_ACTIVE ||
                    !$this->security->validateToken(true, false)
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-contacts', 1)) {
                    break;
                }

                $response['phones'] = Users::phonesView($shop['phones']);
                if (!empty($shop['contacts'])) {
                    foreach (Users::contactsFields($shop['contacts']) as $contact) {
                        $response['contacts'][$contact['key']] = (isset($contact['view'])
                            ? tpl::renderMacro($contact['value'], $contact['view'], 'value')
                            : HTML::obfuscate($contact['value']));
                    }
                }

                $page = $this->input->postget('page', TYPE_STR);
                if ($page == 'list') {
                    $response['listType'] = $this->input->postget('lt', TYPE_UINT);
                    $response['social'] = & $shop['social'];
                    $response['socialTypes'] = static::socialLinksTypes();
                    $response = array(
                        'html' => $this->viewPHP($response, 'search.list.contacts')
                    );
                }
            }
            break;
            case 'shop-contacts-list': # Просмотр контактов магазина в списке
            {
                $ex = $this->input->post('ex', TYPE_STR);
                if (empty($ex)) {
                    $this->errors->reloadPage();
                    break;
                }
                list($ex, $shopID) = explode('-', $ex);

                $data = $this->model->shopData($shopID, array(
                        'id',
                        'id_ex',
                        'status',
                        'items',
                        'title',
                        'link',
                        'logo',
                        'phones',
                        'contacts',
                        'social',
                        'region_id',
                        'addr_addr'
                    )
                );

                if (empty($data) || $data['id_ex'] != $ex || $data['status'] != static::STATUS_ACTIVE ||
                    !$this->security->validateToken(true, false)
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-contacts-list', 1)) {
                    break;
                }

                $data['region_title'] = (isset($data['region_id']) ? Geo::regionTitle($data['region_id']) : '');

                $data['phones'] = Users::phonesView($data['phones']);

                if (!empty($data['contacts'])) {
                    foreach (Users::contactsFields($data['contacts']) as $contact) {
                        $response['contacts'][$contact['key']] = (isset($contact['view'])
                            ? tpl::renderMacro($contact['value'], $contact['view'], 'value')
                            : HTML::obfuscate($contact['value']));
                    }
                }
                $data['has_contacts'] = ($data['phones'] || $data['social'] || !empty($data['contacts']));

                $data['logo'] = ShopsLogo::url($shopID, $data['logo'], ShopsLogo::szList);
                $data['device'] = $this->input->postget('device', TYPE_STR);
                $data['listType'] = $this->input->postget('lt', TYPE_UINT);
                $data['socialTypes'] = static::socialLinksTypes();
                $response['html'] = $this->viewPHP($data, 'search.list.contacts');
            }
            break;
            case 'shop-claim': # Пожаловаться
            {
                $nShopID = $this->input->postget('id', TYPE_UINT);
                if (!$nShopID || !$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                $nReason = $this->input->post('reason', TYPE_ARRAY_UINT);
                $nReason = array_sum($nReason);
                $sMessage = $this->input->post('comment', TYPE_TEXT, array('len'=>1000));

                if (!$nReason) {
                    $this->errors->set(_t('shops', 'Укажите причину'));
                    break;
                } else {
                    if ($nReason & self::CLAIM_OTHER) {
                        if (mb_strlen($sMessage) < 10) {
                            $this->errors->set(_t('shops', 'Опишите причину подробнее'), 'comment');
                            break;
                        }
                    }
                }

                if (!User::id()) {
                    if (Site::captchaCustom('shops-view')) {
                        bff::hook('captcha.custom.check');
                        if ( ! $this->errors->no()) break;
                    } else {
                        $response['captcha'] = false;
                        if (!CCaptchaProtection::isCorrect($this->input->post('captcha', TYPE_NOTAGS))) {
                            $response['captcha'] = true;
                            $this->errors->set(_t('', 'Результат с картинки указан некорректно'), 'captcha');
                            break;
                        }
                    }
                } else {
                    # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                    if (Site::i()->preventSpam('shops-claim')) {
                        break;
                    }
                }

                $nClaimID = $this->model->claimSave(0, array(
                        'reason'  => $nReason,
                        'message' => $sMessage,
                        'shop_id' => $nShopID,
                    )
                );

                if ($nClaimID > 0) {
                    $this->claimsCounterUpdate(1);
                    $this->model->shopSave($nShopID, array(
                            'claims_cnt = claims_cnt + 1'
                        )
                    );
                    if (!User::id()) {
                        CCaptchaProtection::reset();
                    }
                }
            }
            break;
            case 'shop-sendfriend': # Поделиться с другом
            {
                $nShopID = $this->input->postget('id', TYPE_UINT);
                if (!$nShopID || !$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                $sEmail = $this->input->post('email', TYPE_NOTAGS, array('len' => 150));
                if (!$this->input->isEmail($sEmail, false)) {
                    $this->errors->set(_t('', 'E-mail адрес указан некорректно'), 'email');
                    break;
                }

                $aData = $this->model->shopData($nShopID, array('title', 'link'));
                if (empty($aData)) {
                    $this->errors->reloadPage();
                    break;
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-sendfriend')) {
                    $aResponse['later'] = true;
                    break;
                }

                bff::sendMailTemplate(array(
                        'shop_title' => $aData['title'],
                        'shop_link'  => $aData['link'],
                    ), 'shops_shop_sendfriend', $sEmail
                );
            }
            break;
        }

        $this->ajaxResponseForm($response);
    }

    /**
     * Список выбора категорий
     * @param string $type тип списка
     * @param string $device тип устройства bff::DEVICE_ или 'init'
     * @param int $parentID ID parent-категории
     */
    public function catsList($type = '', $device = '', $parentID = 0)
    {
        if (Request::isAJAX()) {
            $type = $this->input->getpost('act', TYPE_STR);
            $device = $this->input->post('device', TYPE_STR);
            $parentID = $this->input->post('parent', TYPE_UINT);
        }

        list($model, $ICON, $ICON_SMALL, $ICON_BIG, $ROOT_ID) = (static::categoriesEnabled() ?
            array(
                $this->model,
                static::categoryIcon(0),
                ShopsCategoryIcon::SMALL,
                ShopsCategoryIcon::BIG,
                self::CATS_ROOTID
            ) :
            array(
                BBS::model(),
                BBS::categoryIcon(0),
                BBSCategoryIcon::SMALL,
                BBSCategoryIcon::BIG,
                BBS::CATS_ROOTID
            ));

        switch ($type) {
            case 'search': # поиск: фильтр категории
            {
                $urlListing = static::url('search');
                $cut2levels = true;

                if ($device == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $selectedID = 0;
                    if ($parentID > $ROOT_ID) {
                        $parentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'icon_' . $ICON_BIG . ' as icon',
                            'shops',
                            'subs'
                        );
                        $aParent = $model->catData($parentID, $parentData);
                        if (!empty($aParent)) {
                            if ($cut2levels && $aParent['numlevel'] == 2) {
                                $aParent['subs'] = 0;
                            }
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $model->catData($aParent['pid'], $parentData);
                                if (!empty($aParent)) {
                                    $selectedID = $parentID;
                                    $parentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aData = $model->catsList($type, $device, $parentID, $ICON_BIG, true);
                    if (!empty($aData)) {
                        foreach ($aData as &$v) {
                            $v['l'] = $urlListing . $v['k'] . '/';
                            $v['i'] = $ICON->url($v['id'], $v['i'], $ICON_BIG);
                            $v['active'] = ($v['id'] == $selectedID);
                        }
                        unset($v);
                    }
                    if ($parentID > $ROOT_ID) {
                        if (!empty($aParent)) {
                            $aParent['link'] = $urlListing . $aParent['keyword'] . '/';
                            $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем настройки основной категории
                                $aParentsID = $model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array(
                                'cats'       => $aData,
                                'parent'     => $aParent,
                                'step'       => 2,
                                'cut2levels' => $cut2levels
                            );
                            $aData = $this->viewPHP($aData, 'search.cats.desktop');
                            if (Request::isAJAX()) {
                                $this->ajaxResponseForm(array('html' => $aData));
                            } else {
                                return $aData;
                            }
                        } else {
                            $this->errors->impossible();
                            $this->ajaxResponseForm(array('html' => ''));
                        }
                    } else {
                        $aData = array('cats' => $aData, 'total' => config::get('shops_total_active', 0, TYPE_UINT), 'step' => 1);

                        return $this->viewPHP($aData, 'search.cats.desktop');
                    }
                } else {
                    if ($device == bff::DEVICE_PHONE) {
                        $selectedID = 0;
                        if ($parentID > $ROOT_ID) {
                            $parentData = array(
                                'id',
                                'pid',
                                'numlevel',
                                'numleft',
                                'numright',
                                'title',
                                'keyword',
                                'icon_' . $ICON_SMALL . ' as icon',
                                'subs'
                            );
                            $aParent = $model->catData($parentID, $parentData);
                            if (!empty($aParent)) {
                                if ($cut2levels && $aParent['numlevel'] == 2) {
                                    $aParent['subs'] = 0;
                                }
                                if (!$aParent['subs']) {
                                    # в данной категории нет подкатегорий
                                    # формируем список подкатегорий ее parent-категории
                                    $aParent = $model->catData($aParent['pid'], $parentData);
                                    if (!empty($aParent)) {
                                        $selectedID = $parentID;
                                        $parentID = $aParent['id'];
                                    }
                                }
                            }
                        }
                        $aData = $model->catsList($type, $device, $parentID, $ICON_SMALL, true);
                        if (!empty($aData)) {
                            foreach ($aData as $k => $v) {
                                $aData[$k]['l'] = $urlListing . $v['k'] . '/';
                                $aData[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_SMALL);
                                $aData[$k]['active'] = ($v['id'] == $selectedID);
                            }
                        }
                        if ($parentID > $ROOT_ID) {
                            if (!empty($aParent)) {
                                $aParent['link'] = $urlListing . $aParent['keyword'] . '/';
                                $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                                if ($aParent['main']) {
                                    $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                                } else {
                                    # глубже второго уровня, получаем иконку основной категории
                                    $aParentsID = $model->catParentsID($aParent, false);
                                    if (!empty($aParentsID[1])) {
                                        $aParentMain = $model->catData($aParentsID[1], array(
                                                'id',
                                                'icon_' . $ICON_SMALL . ' as icon'
                                            )
                                        );
                                        $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                    }
                                }
                                $aData = array(
                                    'cats'       => $aData,
                                    'parent'     => $aParent,
                                    'step'       => 2,
                                    'cut2levels' => $cut2levels
                                );
                                $aData = $this->viewPHP($aData, 'search.cats.phone');
                                if (Request::isAJAX()) {
                                    $this->ajaxResponseForm(array('html' => $aData));
                                } else {
                                    return $aData;
                                }
                            } else {
                                $this->errors->impossible();
                                $this->ajaxResponseForm(array('html' => ''));
                            }
                        } else {
                            $aData = array('cats' => $aData, 'step' => 1);

                            return $this->viewPHP($aData, 'search.cats.phone');
                        }
                    }
                }
            }
            break;
            case 'form': # форма магазина: выбор категории
            {
                $ICON = static::categoryIcon(0);
                if ($device == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $selectedID = 0;
                    if ($parentID > $ROOT_ID) {
                        $parentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'icon_' . $ICON_BIG . ' as icon',
                            'subs'
                        );
                        $aParent = $this->model->catData($parentID, $parentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $parentData);
                                if (!empty($aParent)) {
                                    $selectedID = $parentID;
                                    $parentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($type, $device, $parentID, $ICON_BIG);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_BIG);
                            $aCats[$k]['active'] = ($v['id'] == $selectedID);
                        }
                    }
                    if ($parentID > $ROOT_ID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2);
                            $aData = $this->viewPHP($aData, 'my.form.cats.desktop');
                            if (Request::isAJAX()) {
                                $this->ajaxResponseForm(array(
                                        'html' => $aData,
                                        'cats' => $aCats,
                                        'pid'  => $aParent['pid']
                                    )
                                );
                            } else {
                                return $aData;
                            }
                        } else {
                            $this->errors->impossible();
                            $this->ajaxResponseForm(array('html' => ''));
                        }
                    } else {
                        $aData = array('cats' => $aCats, 'step' => 1);

                        return $this->viewPHP($aData, 'my.form.cats.desktop');
                    }
                } else if ($device == bff::DEVICE_PHONE) {
                    $selectedID = 0;
                    if ($parentID > $ROOT_ID) {
                        $parentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'icon_' . $ICON_SMALL . ' as icon',
                            'subs'
                        );
                        $aParent = $this->model->catData($parentID, $parentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $parentData);
                                if (!empty($aParent)) {
                                    $selectedID = $parentID;
                                    $parentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($type, $device, $parentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_SMALL);
                            $aCats[$k]['active'] = ($v['id'] == $selectedID);
                        }
                    }
                    if ($parentID > $ROOT_ID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_SMALL . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2);
                            $aData = $this->viewPHP($aData, 'my.form.cats.phone');
                            if (Request::isAJAX()) {
                                $this->ajaxResponseForm(array(
                                        'html' => $aData,
                                        'cats' => $aCats,
                                        'pid'  => $aParent['pid']
                                    )
                                );
                            } else {
                                return $aData;
                            }
                        } else {
                            $this->errors->impossible();
                            $this->ajaxResponseForm(array('html' => ''));
                        }
                    } else {
                        $aData = array('cats' => $aCats, 'step' => 1);

                        return $this->viewPHP($aData, 'my.form.cats.phone');
                    }
                } else if ($device == 'init') {
                    /**
                     * Формирование данных об основных категориях
                     * для jShopsForm.init({catsMain:DATA});
                     */
                    $aCats = $this->model->catsList('form', bff::DEVICE_PHONE, $parentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_SMALL);
                        }
                    }

                    return $aCats;
                }
            }
            break;
        }
    }

    /**
     * Пересчет счетчиков магазинов
     * - периодичность = актуальность счетчиков магазинов, рекомендуемая: каждые 15 минут
     */
    public function shopsCronCounters()
    {
        if (!bff::cron()) return;

        $this->model->shopsCronCounters();
    }

    /**
     * Расписание запуска крон задач
     * @return array
     */
    public function cronSettings()
    {

        return array(
            'shopsCronCounters' => array('period' => '*/15 * * * *'),
        );
    }

    /**
     * Автоматическое закрепление тарифного плана за магазинами, у которых тариф не был назначен ранее
     * Вызывается из крон-менеджера, задание инициируется в форме редактирования тарифа.
     * @param array $params array('id' => ID тарифного плана)
     */
    public function svc_abonement_activate_all($params)
    {
        if (empty($params['id'])) return;

        $abonement = $this->model->abonementData($params['id']);
        if (empty($abonement)) return;

        $svcData = Svc::model()->svcData(static::SERVICE_ABONEMENT);
        $svcSettings = array(
            'abonement_id' => $abonement['id'],
            'email_not_send' => true,
        );
        if ($abonement['price_free']) {
            $svcSettings['abonement_period'] = $abonement['price_free_period'];
        } else {
            if (empty($abonement['price'])) return;
            $svcSettings['abonement_period'] = reset(array_keys($abonement['price']));
        }
        $this->model->abonementActivateAll(function($data) use ($svcData, $svcSettings){
            $this->svcActivateService($data['id'], static::SERVICE_ABONEMENT, $svcData, $data, false, $svcSettings);
        });
    }

    /**
     * Проверка соответствия параметров услуги абонемент у магазинов, в зависимости от настроек тарифного плана.
     * Вызывается из крон-менеджера, задание инициируется при редактировании тарифа в админ. панели
     * @param array $params array('id' => ID тарифного плана)
     */
    public function svc_abonement_edit($params)
    {
        if (empty($params['id'])) return;

        $abonement = $this->model->abonementData($params['id']);
        if (empty($abonement)) return;

        $termless = false; # флаг бессрочного пакета
        $expire = false; # окончание действия срочного пакета (при переходе с бессрочного на срочный считается с момента сохранения тарифа админом)
        if ($abonement['price_free']) {
            if ($abonement['price_free_period'] == 0) {
                $termless = true;
            } else {
                $expire = date('Y-m-d H:i:s', strtotime('+' . $abonement['price_free_period'] . ' month'));
            }
        } else {
            # для платных пакетов берем минимальный срок
            $expire = array_keys($abonement['price']);
            $expire = reset($expire);
            $expire = date('Y-m-d H:i:s', strtotime('+' . $expire . ' month'));
        }

        $this->model->abonementUpdateAll(array('svc_abonement_id' => $abonement['id']), function($data) use ($expire, $termless, & $abonement){
            $abonementID = $abonement['id'];
            $update = array();
            # изменение бессрочности
            if ($termless && ! $data['svc_abonement_termless']) {
                # срок действия => бессрочный
                $update['svc_abonement_termless'] = 1;
            }
            if ($data['svc_abonement_termless'] && ! $termless) {
                # бессрочный => срок действия
                $update['svc_abonement_termless'] = 0;
                if ($expire) {
                    $update['svc_abonement_expire'] = $expire;
                    $data['svc_abonement_expire'] = $expire;
                }
            }
            # изменение единоразовости
            $oneTime = func::unserialize($data['svc_abonement_one_time']);
            if ($abonement['one_time']) {
                # тариф единоразовый
                if ( ! in_array($abonementID, $oneTime)) {
                    # сохраним факт в базе
                    $oneTime[] = $abonementID;
                    $update['svc_abonement_one_time'] = serialize($oneTime);
                }
            } else {
                # тариф не единоразовый
                if (in_array($abonementID, $oneTime)) {
                    # удалим факт из базы
                    $k = array_search($abonementID, $oneTime);
                    unset($oneTime[$k]);
                    $update['svc_abonement_one_time'] = serialize($oneTime);
                }
            }
            # изменение платных услуг
            if ($abonement['svc_mark']) {
                # тариф включает услугу выделения
                if ($data['svc'] & static::SERVICE_MARK) {
                    # магазин выделен, проверим срок действия на соответствие с настройкой бессрочности
                    if ( ! $termless && $data['svc_marked_to'] == static::SVC_TERMLESS_DATE) {
                        $update['svc_marked_to'] = $data['svc_abonement_expire'];
                    }
                } else {
                    # применим услугу для магазина
                    $update['svc'] = $data['svc'];
                    $update['svc'] |= static::SERVICE_MARK;
                    $update['svc_marked_to'] = $termless ? static::SVC_TERMLESS_DATE : $data['svc_abonement_expire'];
                }
            }
            if ($abonement['svc_fix']) {
                # тариф включает услугу закрепления
                if ($data['svc'] & static::SERVICE_FIX) {
                    # магазин закреплен, проверим срок действия на соответствие с настройкой бессрочности
                    if ( ! $termless && $data['svc_fixed_to'] == static::SVC_TERMLESS_DATE) {
                        $update['svc_fixed_to'] = $data['svc_abonement_expire'];
                    }
                } else {
                    # применим услугу для магазина
                    if ( ! isset($update['svc'])) {
                        $update['svc'] = $data['svc'];
                    }
                    $update['svc'] |= static::SERVICE_FIX;
                    $update['svc_fixed_to'] = $termless ? static::SVC_TERMLESS_DATE : $data['svc_abonement_expire'];
                }
            }

            if ( ! empty($update)) {
                $this->model->shopSave($data['id'], $update);
            }
        });
    }

}