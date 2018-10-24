<?php

use bff\db\NestedSetsTree;

class BBSModel_ extends Model
{
    /** @var BBS */
    protected $controller;

    /** @var NestedSetsTree для категорий */
    public $treeCategories;
    public $langCategories = array(
        'title'                 => TYPE_NOTAGS, // название
        'mtitle'                => TYPE_NOTAGS, // meta-title
        'mkeywords'             => TYPE_NOTAGS, // meta-keywords
        'mdescription'          => TYPE_NOTAGS, // meta-description
        'seotext'               => TYPE_STR, // seotext
        'titleh1'               => TYPE_STR, // H1
        'breadcrumb'            => TYPE_STR, // хлебная крошка
        'type_offer_form'       => TYPE_STR, // тип "предложение" в форме
        'type_offer_search'     => TYPE_STR, // тип "предложение" при поиске
        'type_seek_form'        => TYPE_STR, // тип "ищу" в форме
        'type_seek_search'      => TYPE_STR, // тип "ищу" при поиске
        'owner_private_form'    => TYPE_STR, // тип "представителя" в форме
        'owner_private_search'  => TYPE_STR, // тип "представителя" при поиске
        'owner_business_form'   => TYPE_STR, // тип "представителя" в форме
        'owner_business_search' => TYPE_STR, // тип "представителя" при поиске
        'subs_filter_title'     => TYPE_STR, // заголовок для подкатегорий в фильтре
        'tpl_title_list'        => TYPE_STR, // шаблон для заголовка объявления (список)
        'tpl_title_view'        => TYPE_STR, // шаблон для заголовка объявления (просмотр)
        'tpl_descr_list'        => TYPE_STR, // шаблон для описания объявления (список)
    );

    public $langCategoriesTypes = array(
        'title' => TYPE_STR, // название
    );

    public $langSvcServices = array(
        'title_view'       => TYPE_STR, // название
        'description'      => TYPE_STR, // описание (краткое)
        'description_full' => TYPE_STR, // описание (подробное)
    );

    public $langSvcPacks = array(
        'title_view'       => TYPE_NOTAGS, // название
        'description'      => TYPE_STR, // описание (краткое)
        'description_full' => TYPE_STR, // описание (подробное)
    );

    public $langItem = array(
        'title'      => array(TYPE_NOTAGS), // название
        'descr'      => array(TYPE_TEXT),   // описание (краткое)
        'title_list' => array(TYPE_NOTAGS),
        'descr_list' => array(TYPE_TEXT),
    );

    /** @var array список шифруемых полей в таблице TABLE_BBS_ITEMS */
    protected $cryptItems = array();

    const ITEMS_ENOTIFY_UNPUBLICATESOON = 1;
    const ITEMS_ENOTIFY_UP_FREE_ENABLE  = 2;

    public function init()
    {
        parent::init();

        $this->langItem['title']['len'] = config::sys('bbs.form.title.limit', 100, TYPE_UINT);
        $this->langItem['descr']['len'] = config::sys('bbs.form.descr.limit', 4000, TYPE_UINT);

        # подключаем nestedSets для категорий
        $this->treeCategories = new NestedSetsTree(TABLE_BBS_CATEGORIES);
        $this->treeCategories->init();
    }

    # --------------------------------------------------------------------
    # Объявления

    /**
     * Список объявлений (admin)
     * @param array $filter фильтр списка объявлений
     * @param bool $countOnly только подсчет кол-ва объявлений
     * @param array $opts доп. параметры: orderBy, limit, offset
     * @return mixed
     */
    public function itemsListing(array $filter = array(), $countOnly = false, array $opts = array())
    {
        func::array_defaults($opts, array(
            'context' => 'admin-search',
            'count'   => $countOnly,
            'fields'  => array(),
            'joinTables' => array(),
        ));

        $itemsID = $this->itemsSearch($filter, $opts);
        if ($countOnly) {
            return $itemsID;
        }
        if (empty($itemsID)) {
            return array();
        }

        $fields = $opts['fields']; $joinTables = $opts['joinTables'];
        $this->db->tag('bbs-items-listing-data', array('fields'=>&$fields,'join'=>&$joinTables));
        return $this->db->select('SELECT I.id, I.link, I.title, I.created,
                I.svc_press_status, I.svc_press_date, I.svc_press_date_last,
                I.imgcnt, I.comments_cnt, I.status, I.moderated, I.import,
                I.user_ip, I.user_id, C.title as cat_title, I.cat_id1
                '.( ! empty($fields) ? ','.join(',', $fields) : '').'
           FROM ' . TABLE_BBS_ITEMS . ' I '.join(' ', $joinTables).'
             LEFT JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' C ON C.id = I.cat_id1 AND C.lang = :lang
           WHERE '.$this->db->prepareIN('I.id', $itemsID).'
           ORDER BY FIELD(I.id,'.join(',', $itemsID).')', array(
            ':lang' => $this->locale->getCurrentLanguage()
        ));
    }

    /**
     * Список объявлений по фильтру (frontend)
     * @param array $filter фильтр списка объявлений
     * @param bool $countOnly только подсчет кол-ва объявлений
     * @param integer|array $opts доп. параметры:
     * @param array $fields список дополнительных полей
     * @return mixed
     */
    public function itemsList(array $filter = array(), $countOnly = false, array $opts = array(), $orderByRating = false)
    {
        func::array_defaults($opts, array(
            'context' => 'bbs-items-list',
            'count' => $countOnly,
            'fields' => array(),
            'joinTables' => array(),
            'listCurrency' => 0, # ID валюты в списке
            'user' => User::id(), # ID текущего пользователя (для пометки избранных)
            'lang' => $this->locale->getCurrentLanguage(),
            'favs' => true, # помечать избранные
            'districts' => true, # получать данные о районах города
            'ttl' => 60, # кешировать запрос (сек)
        ));

        # Поиск объявлений
        $itemsID = $this->itemsSearch($filter, $opts);
        if ($countOnly) {
            return $itemsID;
        }
        if (empty($itemsID)) {
            return array();
        }

        $orderBy = $orderByRating ? "avarage_rating_value DESC" : 'FIELD(I.id,'.join(',', $itemsID).')';

        $lang = $opts['lang'];
        $districtsEnabled = $opts['districts'] && Geo::districtsEnabled();
        $fields = $opts['fields']; $joinTables = $opts['joinTables'];
        $this->db->tag('bbs-items-list-data', array('fields'=>&$fields,'join'=>&$joinTables,'opts'=>$opts));
        $aData = $this->db->select('SELECT
                 I.id, I.title, I.title_list, I.descr_list,
                 I.link, I.img_s, I.img_m, I.imgcnt as imgs,
                 I.addr_lat as lat, I.addr_lon as lon, I.addr_addr,
                 I.price, I.price_curr, I.price_ex,'.($districtsEnabled ? 'I.district_id,' : '').'
                 ((I.svc & ' . BBS::SERVICE_MARK . ') > 0) as svc_marked, I.svc_fixed,
                 ((I.svc & ' . BBS::SERVICE_QUICK . ') > 0) as svc_quick,
                 ((I.svc & ' . BBS::SERVICE_UP . ') > 0) as svc_up,
                 I.publicated, I.publicated_order as publicated_last,  I.modified,
                 C.price_sett, C.price as price_on, CL.title as cat_title,
                 SUM(IR.value)/COUNT(IR.value) as avarage_rating_value, 
                 R.title_'.$lang.' AS city_title, I.regions_delivery, I.lang '.
                 ( ! empty($fields) ? ','.join(',', $fields) : '').'
          FROM ' . TABLE_BBS_ITEMS . ' I '.join(' ', $joinTables).'
            INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
            INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
            LEFT JOIN ' . TABLE_REGIONS . ' R ON I.city_id = R.id
            LEFT JOIN ' . TABLE_BBS_ITEMS_RATINGS . ' IR ON I.id = IR.item_id 
          WHERE I.id IN ('.join(',', $itemsID).')
          GROUP BY I.id 
          ORDER BY '.$orderBy.'
          ', array(':lang'=>$lang), $opts['ttl']
        );

        if ($districtsEnabled) {
            $aDistricts = array();
            foreach ($aData as $v) {
                if ($v['district_id']) {
                    $aDistricts[] = $v['district_id'];
                }
            }
            if ( ! empty($aDistricts)) {
                $aDistricts = array_unique($aDistricts);
                $aDistricts = $this->db->tag('bbs-items-list-districts')->select_key('SELECT id, title_' . $lang . ' as t
                                  FROM ' . TABLE_REGIONS_DISTRICTS . '
                                  WHERE ' . $this->db->prepareIN('id', $aDistricts));
            }
        }

        # проверим наличие перевода
        $translate = $this->db->tag('bbs-items-list-translate')->select_key('
            SELECT id, title, title_list, descr_list
            FROM ' . TABLE_BBS_ITEMS_LANG . '
            WHERE lang = :lng AND ' . $this->db->prepareIN('id', $itemsID), 'id', array(':lng' => $lang)
        );

        if ($opts['favs']) {
            $aFavoritesID = $this->controller->getFavorites($opts['user'], false, $itemsID);
        }
        foreach ($aData as &$v) {
            # выполнялось ли поднятие
            $v['publicated_up'] = false;
            if ($v['publicated'] !== $v['publicated_last']) {
                $publicated = strtotime($v['publicated']);
                $publicated_last = strtotime($v['publicated_last']);
                if ($publicated_last > $publicated &&
                    ($publicated_last - $publicated) >= 86400 /* 1 day */) {
                    $v['publicated_up'] = true;
                }
            }
            # помечаем избранные
            $v['fav'] = ($opts['favs'] && in_array($v['id'], $aFavoritesID));
            # форматируем цену
            if ($v['price_on'] = (!empty($v['price_on']))) {
                $v['price'] = tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex'], $opts['listCurrency']);
                if (($v['price_mod'] = ($v['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $v['price_sett'] = func::unserialize($v['price_sett']);
                    $v['price_mod'] = (!empty($v['price_sett']['mod_title'][$lang]) ? $v['price_sett']['mod_title'][$lang] : _t('bbs', 'Торг возможен'));
                } else {
                    $v['price_mod'] = '';
                }
            } else {
                $v['price_mod'] = '';
            }
            unset($v['price_curr'], $v['price_ex'], $v['price_sett']);
            # формируем ссылку
            $v['link'] = BBS::urlDynamic($v['link'], array(), $lang);
            # район города
            if ($districtsEnabled && $v['district_id'] && ! empty($aDistricts[ $v['district_id'] ])) {
                $v['district_title'] = $aDistricts[ $v['district_id'] ]['t'];
            }
            # доставка в регионы
            if (!empty($v['regions_delivery'])) {
                $v['city_title'] = _t('bbs', 'доставка из г.[city]', array('city'=>$v['city_title']));
            }
            # перевод
            if ($v['lang'] != $lang && ! empty($translate[ $v['id'] ])) {
                foreach ($translate[ $v['id'] ] as $k => $vv){
                    if ($k == 'id') continue;
                    if (empty($vv)) continue;
                    $v[$k] = $vv;
                }
            }
            # автозаголовок
            if ( ! empty($v['title_list'])) {
                $v['title'] = $v['title_list'];
            }
        }
        unset($v);

        bff::hook('bbs.model.items.list.data', ['data'=>&$aData,'filter'=>&$filter,'opts'=>&$opts]);

        return $aData;
    }
    
    /**
     * Список объявлений по фильтру для экспорта
     * @param array $aFilter фильтр списка объявлений
     * @param bool $countOnly только подсчёт кол-ва
     * @param array $opts доп. параметры: limit, orderBy, lang, fields, joinTables
     * @return mixed
     */
    public function itemsListExport(array $aFilter, $countOnly = false, array $opts = array())
    {
        func::array_defaults($opts, array(
            'context' => 'items-list-export',
            'count'   => $countOnly,
            'lang'    => $this->locale->getCurrentLanguage(),
            'fields'  => array(),
            'joinTables' => array(),
            'limit'   => 0,
        ));

        $itemsID = $this->itemsSearch($aFilter, $opts);
        if ($countOnly) {
            return $itemsID;
        }
        if (empty($itemsID)) {
            return array();
        }

        $lang = $opts['lang'];
        $this->db->tag('bbs-items-list-export-data', array('fields'=>&$opts['fields'],'join'=>&$opts['joinTables'],'opts'=>$opts));
        $aData = $this->db->select_key('SELECT I.id, I.title_edit as title, I.descr, I.user_id, I.shop_id, I.cat_id, I.city_id,
                R.title_' . $lang . ' as city_title, I.metro_id, RM.title_' . $lang . ' as metro_title,
                U.email, I.addr_addr, I.addr_lat, I.addr_lon, I.district_id, I.cat_type, I.lang,
                I.price, I.price_curr, I.price_ex, I.phones, I.contacts, I.name, U.phone as u_phone,
                U.phones as u_phones, U.contacts as u_contacts, I.video, I.regions_delivery,
                IL.title AS title_trans, IL.descr AS descr_trans
                '.( ! empty($opts['fields']) ? ','.join(',', $opts['fields']) : '').'
          FROM ' . TABLE_BBS_ITEMS . ' I '.(!empty($opts['joinTables']) ? join(' ', $opts['joinTables']) : '').'
            INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
            INNER JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id
            INNER JOIN ' . TABLE_REGIONS . ' R ON I.city_id = R.id
            LEFT JOIN ' . TABLE_REGIONS_METRO . ' RM ON I.metro_id = RM.id
            LEFT JOIN ' . TABLE_BBS_ITEMS_LANG.' IL ON I.id = IL.id AND IL.lang = :lang
          WHERE I.id IN ('.join(',', $itemsID).')
          ORDER BY FIELD(I.id,'.join(',', $itemsID).')', 'id', array(
            ':lang' => $lang,
        ));

        if (empty($aData)) {
            return array();
        }
        foreach ($aData as &$v) {
            # translate:
            if ($v['lang'] != $lang) {
                if (!empty($v['title_trans'])) {
                    $v['title'] = $v['title_trans'];
                }
                if (!empty($v['descr_trans'])) {
                    $v['descr'] = $v['descr_trans'];
                }
            }
            # contacts:
            $v['contacts'] = Users::contactsToArray($v['contacts']);
            $v['u_contacts'] = Users::contactsToArray($v['u_contacts']);
        } unset($v);

        return $aData;
    }

    /**
     * Данные об объявлениях для экспорта на печать
     * @param array $aFilter фильтр объявлений для экспорта
     * @param string $lang ключ языка
     * @return array ['items' - данные об объявлениях]
     */
    public function itemsListExportPrint(array $aFilter, $lang = LNG)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');
        $aData = array();

        $this->db->tag('bbs-items-list-export-print-data', array('filter'=>&$aFilter));
        $aData['items'] = $this->db->select_key('
            SELECT I.*,
                   R.title_'.$lang.' AS city, RM.title_'.$lang.' AS metro,
                   U.email, U.phones as u_phones, U.contacts as u_contacts
            FROM ' . TABLE_BBS_ITEMS . ' I
                LEFT JOIN ' . TABLE_REGIONS_METRO . ' RM ON I.metro_id = RM.id,
                '.TABLE_USERS.' U,
                '.TABLE_REGIONS.' R
            ' . $aFilter['where'].' AND I.user_id = U.user_id AND I.reg3_city = R.id
            ORDER BY I.id', 'id', $aFilter['bind']
        );

        $aCats = array(); # ID категорий
        $aRegs = array(); # ID регионов
        foreach ($aData['items'] as &$v) {
            foreach (array('cat_id', 'cat_id1', 'cat_id2', 'cat_id3', 'cat_id4') as $c) {
                if (!$v[$c]) continue;
                if (!in_array($v[$c], $aCats)){
                    $aCats[] = $v[$c];
                }
            }
            foreach (array('reg1_country', 'reg2_region') as $c) {
                if (!$v[$c]) continue;
                if (!in_array($v[$c], $aRegs)) {
                    $aRegs[] = $v[$c];
                }
            }
            $v['contacts'] = Users::contactsToArray($v['contacts']);
            $v['u_contacts'] = Users::contactsToArray($v['u_contacts']);
        } unset($v);

        # Названия категорий (полный путь)
        if (!empty($aCats)) {
            $aFl = array(
                'lang' => $lang,
                'id' => $aCats,
            );
            $aFl = $this->prepareFilter($aFl, 'L');
            $aCats = $this->db->tag('bbs-items-list-export-print-categories', array('filter'=>&$aFl))->select_key('
                SELECT L.id, L.title
                FROM ' . TABLE_BBS_CATEGORIES_LANG . ' L
                ' . $aFl['where'], 'id', $aFl['bind']);

            foreach ($aData['items'] as &$v) {
                $v['category'] = $aCats[ $v['cat_id'] ]['title'];
                $sPath = '';
                foreach (array('cat_id1', 'cat_id2', 'cat_id3', 'cat_id4') as $c) {
                    if (!$v[$c]) continue;
                    if ($sPath) $sPath .= ' / ';
                    $sPath .= $aCats[ $v[$c] ]['title'];
                }
                $v['category_path'] = $sPath;
            } unset($v);
        }

        # Названия регионов
        if (!empty($aRegs)) {
            $aFl = array(
                'id' => $aRegs,
            );
            $aFl = $this->prepareFilter($aFl, 'R');
            $aRegs = $this->db->tag('bbs-items-list-export-print-regions', array('filter'=>&$aFl))->select_key('
                SELECT R.id, R.title_'.$lang.' AS title
                FROM ' . TABLE_REGIONS . ' R
                ' . $aFl['where'], 'id', $aFl['bind']);
            foreach ($aData['items'] as &$v) {
                $v['country'] = $aRegs[ $v['reg1_country'] ]['title'];
                $v['region'] = $aRegs[ $v['reg2_region'] ]['title'];
            } unset($v);
        }

        return $aData;
    }

    /**
     * Список "моих" объявлений по фильтру (frontend)
     * @param array $filter фильтр списка объявлений
     * @param bool $countOnly только подсчет кол-ва объявлений
     * @param array $opts доп. параметры: orderBy, limit, offset, listCurrency
     * @return mixed
     */
    public function itemsListMy(array $filter = array(), $countOnly = false, array $opts = array())
    {
        func::array_defaults($opts, array(
            'context' => 'my-items',
            'count'   => $countOnly,
            'fields'  => array(),
            'listCurrency' => 0,
            'index'   => 'users',
        ));

        if (isset($filter['onlyIDs'])) {
            unset($filter['onlyIDs']);
            $this->db->tag('bbs-items-list-my-onlyid', array('filter'=>&$filter));
            return $this->itemsSearch($filter, $opts);
        }

        $itemsID = $this->itemsSearch($filter, $opts);
        if ($countOnly) {
            return $itemsID;
        }
        if (empty($itemsID)) {
            return array();
        }

        $aFields = $opts['fields'];
        $aData = $this->db->tag('bbs-items-list-my-data', array('fields'=>&$aFields))->select('
            SELECT I.id, I.title, I.title_list, I.link, I.img_s, I.imgcnt as imgs,
                 I.status, I.moderated, I.publicated, I.publicated_to,
                 I.views_item_total, I.views_contacts_total,
                 I.messages_total, I.messages_new, I.publicated_order, I.svc_up_free, I.svc_upauto_on,
                 I.price, I.price_curr, I.price_ex, I.svc_fixed, I.svc, 
                 ((I.svc & ' . BBS::SERVICE_MARK . ') > 0) as svc_marked,
                 ((I.svc & ' . BBS::SERVICE_QUICK . ') > 0) as svc_quick,
                 I.svc_marked_to, I.svc_fixed_to, I.svc_premium_to, I.svc_quick_to, 
                 SUM(IR.value)/COUNT(IR.value) as avarage_rating_value,
                 C.price_sett, C.price as price_on, CL.title as cat_title
                 '.( ! empty($aFields) ? ','.join(',', $aFields) : '').'
            FROM ' . TABLE_BBS_ITEMS . ' I
                INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
                LEFT JOIN ' . TABLE_BBS_ITEMS_RATINGS . ' IR ON I.id = IR.item_id 
            WHERE '.$this->db->prepareIN('I.id', $itemsID).'
            GROUP BY I.id 
            ORDER BY FIELD(I.id,'.join(',', $itemsID).')',
            array(':lang'=>$this->locale->getCurrentLanguage())
        );
        if (empty($aData)) {
            return array();
        }

        foreach ($aData as &$v) {
            # форматируем цену
            if ($v['price_on'] = (!empty($v['price_on']))) {
                $v['price'] = tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex'], $opts['listCurrency']);
                if (($v['price_mod'] = ($v['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $v['price_sett'] = func::unserialize($v['price_sett']);
                    $v['price_mod'] = (!empty($v['price_sett']['mod_title'][LNG]) ? $v['price_sett']['mod_title'][LNG] : _t('bbs', 'Торг возможен'));
                } else {
                    $v['price_mod'] = '';
                }
            } else {
                $v['price_mod'] = '';
            }
            unset($v['price_curr'], $v['price_ex'], $v['price_sett']);
            # автозаголовок
            if ( ! empty($v['title_list'])) {
                $v['title'] = $v['title_list'];
            }
            # формируем ссылку
            $v['link'] = BBS::urlDynamic($v['link']);
        }
        unset($v);

        bff::hook('bbs.model.items.list.my.data', ['data'=>&$aData,'filter'=>&$filter,'opts'=>&$opts]);

        return $aData;
    }

    /**
     * Список объявлений для переписки во внутренней почте (frontend)
     * @param array $aItemID ID объявлений
     * @param integer $nListCurrencyID ID текущей валюты, формируемого списка ОБ или 0
     * @return mixed
     */
    public function itemsListChat(array $aItemID = array(), $nListCurrencyID = 0)
    {
        $aFilter = $this->prepareFilter(array('id' => $aItemID), 'I', array(':lang' => LNG));
        $aFields = array();

        $aData = $this->db->tag('bbs-items-list-chat-data', array('fields'=>&$aFields,'filter'=>&$aFilter))->select_key('SELECT I.id, I.title, I.link, I.img_s, I.imgcnt as imgs,
                                         I.status, I.price, I.price_curr, I.price_ex,
                                         C.price_sett, C.price as price_on, CL.title as cat_title
                                         '.( ! empty($aFields) ? ','.join(',', $aFields) : '').'
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
                                  ' . $aFilter['where'] . '
                                  ' . (!empty($sqlOrder) ? ' ORDER BY I.' . $sqlOrder : ''),
            'id', $aFilter['bind']
        );
        if (empty($aData)) {
            return array();
        }

        foreach ($aData as &$v) {
            # форматируем цену
            if ($v['price_on'] = (!empty($v['price_on']))) {
                $v['price'] = tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex'], $nListCurrencyID);
                if (($v['price_mod'] = ($v['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $v['price_sett'] = func::unserialize($v['price_sett']);
                    $v['price_mod'] = (!empty($v['price_sett']['mod_title'][LNG]) ? $v['price_sett']['mod_title'][LNG] : _t('bbs', 'Торг возможен'));
                } else {
                    $v['price_mod'] = '';
                }
            } else {
                $v['price_mod'] = '';
            }
            unset($v['price_curr'], $v['price_ex'], $v['price_sett']);
            # формируем ссылку
            $v['link'] = BBS::urlDynamic($v['link']);
        }
        unset($v);

        return $aData;
    }

    /**
     * Помечаем все новые сообщения переписки связанные с объявлениями как "прочитанные"
     * @param array $aItemsID (ID объявления => кол-во прочитанных, ...)
     */
    public function itemsListChatSetReaded(array $aItemsID = array())
    {
        if (empty($aItemsID)) {
            return;
        }
        $aUpdateData = array();
        foreach ($aItemsID as $k => $i) {
            $aUpdateData[] = "WHEN $k THEN (messages_new - $i)";
        }
        if (!empty($aUpdateData)) {
            $this->itemsUpdateByFilter(array(
                'messages_new = CASE id ' . join(' ', $aUpdateData) . ' ELSE messages_new END',
            ), array(
                'id' => array_keys($aItemsID),
            ), array('context'=>__FUNCTION__));
        }
    }

    /**
     * Список категорий, в которые входят объявления
     * @param array $aFilter фильтр списка объявлений
     * @param integer $nNumlevel уровень категорий
     * @return array
     */
    public function itemsListCategories(array $aFilter, $nNumlevel = 1)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');
        $aFilter['bind'][':lang'] = LNG;
        if (empty($nNumlevel) || $nNumlevel < 1 || $nNumlevel > BBS::CATS_MAXDEEP) {
            $nNumlevel = 1;
        }

        $sCatField = 'cat_id' . $nNumlevel;
        $aData = $this->db->tag('bbs-items-list-categories-data', array('filter'=>&$aFilter))->select_key('SELECT C.id, C.pid, CL.title, COUNT(I.id) as items
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.' . $sCatField . '
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.' . $sCatField . ' AND CL.lang = :lang
                                  ' . $aFilter['where'] . '
                                  GROUP BY I.' . $sCatField . '
                                  ORDER BY C.numleft', 'id',
            $aFilter['bind']
        );

        return (is_array($aData) ? $aData : array());
    }

    /**
     * Список основных категорий, в которые входят объявления
     * @param array $filter фильтр списка объявлений
     * @return array
     */
    public function itemsListCategoriesMain(array $filter)
    {
        # список основных категорий + кол-во объявлений в них
        $fields = array('CAST(SUBSTRING(SUBSTRING_INDEX(I.cat_path, \'-\', 2), 2) AS UNSIGNED) as cat_id, COUNT(*) as items');
        $list1 = $this->itemsDataByFilter($filter, $fields, array('prefix'=>'I','groupKey'=>'cat_id','groupBy'=>'1'));
        if (empty($list1)) {
            return array();
        }

        $list2 = $this->db->select_key('SELECT C.id, C.pid, CL.title
          FROM ' . TABLE_BBS_CATEGORIES . ' C,
               ' . TABLE_BBS_CATEGORIES_LANG . ' CL
          WHERE '.$this->db->prepareIN('C.id', array_keys($list1)).'
            AND CL.id = C.id AND CL.lang = :lang
          ORDER BY C.numleft', 'id', array(':lang' => $this->locale->getCurrentLanguage()));
        if (empty($list2)) {
            return array();
        }
        foreach ($list1 as $k=>$v) {
            if (isset($list2[$k])) {
                $list2[$k]['items'] = $v['items'];
            }
        }
        return $list2;
    }

    /**
     * Быстрый поиск объявлений (frontend)
     * @param array $aFilter фильтр списка
     * @param integer $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function itemsQuickSearch(array $aFilter, $sqlLimit = '', $sqlOrder = '')
    {
        $itemsID = $this->itemsSearch($aFilter, array(
            'limit'   => $sqlLimit,
            'orderBy' => $sqlOrder,
            'context' => 'search-quick',
        ));
        if (empty($itemsID)) {
            return array();
        }

        $aFields = array();
        $aData = $this->db->tag('bbs-items-quick-search-data', array('fields'=>&$aFields))->select_key('SELECT
            I.id, I.title, I.link, I.imgcnt, I.price, I.price_curr, I.price_ex, C.price as price_on
            '.(!empty($aFields) ? ','.join(',', $aFields) : '').'
           FROM ' . TABLE_BBS_ITEMS . ' I
             INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
           WHERE '.$this->db->prepareIN('I.id', $itemsID).'
           ORDER BY FIELD(I.id, '.join(',', $itemsID).')
        ');
        if (!empty($aData)) {
            # формируем ссылки на изображения ОБ
            $oImages = $this->controller->itemImages();
            $aImages = $oImages->getItemsImagesData(array_keys($aData));
            foreach ($aData as $id => &$v) {
                $v['img'] = array();
                if ($v['imgcnt'] > 0 && !empty($aImages[$id])) {
                    $aItemImages = $aImages[$id];
                    $oImages->setRecordID($id);
                    foreach ($aItemImages as $img) {
                        $v['img'][] = $oImages->getURL($img, BBSItemImages::szSmall);
                    }
                }
                # формируем ссылку
                $v['link'] = BBS::urlDynamic($v['link']);
            }
            unset($v);
        }

        return $aData;
    }

    /**
     * Сохранение/обновление данных об объявлении
     * @param integer $nItemID ID объявления
     * @param array $aData данные
     * @param mixed $sDynpropsDataKey ключ данных дин. свойств или FALSE
     * @return bool|int
     */
    public function itemSave($nItemID, $aData, $sDynpropsDataKey = false)
    {
        if (empty($aData)) {
            return false;
        }
        # проверка необходимости пересчета счетчиков объявлений
        $checkCounters = $this->itemSaveCountersCheck($nItemID, $aData);

        if (!empty($sDynpropsDataKey) && !empty($aData['cat_id'])) {
            $aDataDP = $this->controller->dpSave($aData['cat_id'], $sDynpropsDataKey);
            $aData = array_merge($aData, $aDataDP);
        }
        if (array_key_exists('phones', $aData)) {
            if (empty($aData['phones']) || !is_array($aData['phones'])) {
                $aData['phones'] = array();
            }
            $aData['phones'] = serialize($aData['phones']);
        }
        if (isset($aData['status']) || isset($aData['status_prev'])) {
            $aData['status_changed'] = $this->db->now();
        }
        $translate = false;
        $translates = false;
        if (BBS::translate()) {
            if (isset($aData['title']) || isset($aData['descr']) || isset($aData['lang'])) {
                $translate = true;
            }
        }
        if (isset($aData['translates'])) {
            $translates = $aData['translates'];
            unset($aData['translates']);
        }
        foreach ($this->langItem as $k => $v){
            if (isset($aData[$k]) && is_array($aData[$k])){
                unset($aData[$k]);
            }
        }

        $aData['modified'] = $this->db->now();
        if ($nItemID) {
            if ($translate || ! empty($translates)) {
                $old = $this->itemData($nItemID, array('title', 'descr', 'lang'));
            }
            # обновляем данные об объявлении
            $result = $this->db->update(TABLE_BBS_ITEMS, $aData, array('id' => $nItemID), array(), $this->cryptItems);
            if ($result) {
                \bff::hook('bbs.item.save', $nItemID, array('data'=>&$aData));
                if ($translate || ! empty($translates)) {
                    $this->itemSaveTranslate($nItemID, $aData, $translates, $old);
                }
                if (isset($aData['moderated']) && $aData['moderated'] == 1) {
                    $this->itemSaveModerated($nItemID);
                }
            }
        } else {
            # создаем объявление
            $aData['user_ip'] = Request::remoteAddress(); # IP адрес текущего пользователя
            $aData['created'] = $this->db->now();
            if ( ! isset($aData['lang'])) {
                $aData['lang'] = LNG;
            }
            $nItemID = $this->db->insert(TABLE_BBS_ITEMS, $aData, 'id', array(), $this->cryptItems);
            if ($nItemID) {
                if ( ! empty($aData['user_id']) && $aData['status'] != BBS::STATUS_NOTACTIVATED) {
                    # накручиваем счетчик кол-ва объявлений пользователя (+1)
                    $this->security->userCounter('items', 1, $aData['user_id'], true);
                }
                if (isset($aData['link'])) {
                    # дополняем ссылку ID объявления
                    $aData['link'] = str_replace('{item-id}', $nItemID, $aData['link'], $replaceCount);
                    if (!$replaceCount && mb_stripos($aData['link'], '.html') === false) {
                        $aData['link'] .= $nItemID . '.html';
                    }
                    $this->db->update(TABLE_BBS_ITEMS, array(
                            'link' => $aData['link']
                        ), array('id' => $nItemID)
                    );
                }
                if ($translate || ! empty($translates)) {
                    $this->itemSaveTranslate($nItemID, $aData, $translates);
                }
                if (isset($aData['moderated']) && $aData['moderated'] == 1) {
                    $this->itemSaveModerated($nItemID);
                }
                \bff::hook('bbs.item.create', $nItemID, array('data'=>&$aData));
            }
            $result = $nItemID;
        }
        if ($result) {
            if ($checkCounters !== false) {
                $this->itemSaveCountersUpdate($nItemID, $checkCounters);
            }
            # перестраиваем поля для индексов
            $this->itemsIndexesUpdate(array($nItemID));
        }
        return $result;
    }

    /**
     * Сохранение промодерированных данных (для определения изменений при следующей модерации)
     * @param array|integer $itemsID ID объявления или массив с ID объявлений
     */
    protected function itemSaveModerated($itemsID)
    {
        $fields = array('title', 'descr', 'price', 'addr_addr', 'contacts', 'name', 'phones');
        $prefix = $this->controller->dp()->getSettings('datafield_prefix');
        $last = $this->controller->dp()->getSettings('datafield_text_last');
        for ($i = 1; $i <= $last; $i++) {
            $fields[] = $prefix.$i;
        }

        if (is_numeric($itemsID)) {
            $itemsID = array($itemsID);
        }

        $this->db->tag('bbs-item-save-moderated-data', array('fields'=>&$fields))->select_iterator('SELECT id, '.join(',', $fields).' FROM '.TABLE_BBS_ITEMS.' WHERE '.$this->db->prepareIN('id', $itemsID), array(),
        function($row) use($fields) {
            $id = $row['id']; unset($row['id']);
            $row['phones'] = func::unserialize($row['phones']);
            $row['contacts'] = Users::contactsToArray($row['contacts']);
            $this->db->update(TABLE_BBS_ITEMS, array('moderated_data' => serialize($row)), array('id' => $id));
        });
    }

    /**
     * Проверка необходимости изменения счетчиков количества объявлений
     * @param integer|array $itemID ID объявления или массив ID объявлений
     * @param array $data изменяемые данные
     * @return mixed false - не надо пересчитывать иначе - данные объявления до сохранения
     */
    protected function itemSaveCountersCheck($itemID, $data)
    {
        $fields = array('cat_id', 'status', 'moderated', 'regions_delivery', 'reg1_country', 'reg2_region', 'reg3_city');
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $fields[] = 'cat_id'.$i;
        }
        $check = false;
        foreach ($fields as $v) {
            if (isset($data[$v])) {
                $check = true;
                break;
            }
        }
        if ( ! $check) return false;
        if (is_array($itemID)) {
            return $this->db->select_key('
                SELECT id, '.join(', ', $fields).' FROM '.TABLE_BBS_ITEMS.' 
                WHERE '.$this->db->prepareIN('id', $itemID), 'id');
        } else {
            if ($itemID) {
                return $this->itemData($itemID, $fields);
            } else {
                return array();
            }
        }
    }

    /**
     * Изменение счетчиков количества объявлений
     * @param integer $itemID ID объявления
     * @param array $old данные объявления до изменения
     */
    protected function itemSaveCountersUpdate($itemID, $old)
    {
        $fields = array('cat_id', 'status', 'moderated', 'regions_delivery', 'reg1_country', 'reg2_region', 'reg3_city');
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $fields[] = 'cat_id'.$i;
        }
        $new = $this->itemData($itemID, $fields);

        $isOldPublicated = ! empty($old) && $old['status'] == BBS::STATUS_PUBLICATED && (BBS::premoderation() ? $old['moderated'] > 0 : true);
        $isNewPublicated = $new['status'] == BBS::STATUS_PUBLICATED && (BBS::premoderation() ? $new['moderated'] > 0 : true);
        if ( ! $isOldPublicated && ! $isNewPublicated) return;
        if ( ! $isOldPublicated && $isNewPublicated) {
            # Публикация объявления
            $this->itemsCountersUpdate($new, 1);
        } else if ($isOldPublicated && ! $isNewPublicated) {
            # Снятие с публикации
            $this->itemsCountersUpdate($old, -1);
        } else {
            # Изменение категории, региона, доставки в регионы
            unset($fields['status'], $fields['moderated']);
            foreach ($fields as $v) {
                if ($old[$v] != $new[$v]) {
                    $this->itemsCountersUpdate($old, -1);
                    $this->itemsCountersUpdate($new, 1);
                    return;
                }
            }
        }
    }

    /**
     * Изменение счетчиков количества объявлений на основе данных для нескольких объявлений
     * @param array $itemsID массив ID объявлений
     * @param array $old массив объявлений до изменения
     */
    protected function itemsSaveCountersUpdate($itemsID, $old)
    {
        $fields = array('cat_id', 'status', 'moderated', 'regions_delivery', 'reg1_country', 'reg2_region', 'reg3_city');
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $fields[] = 'cat_id'.$i;
        }
        $new = $this->db->select_key('
                SELECT id, '.join(', ', $fields).' FROM '.TABLE_BBS_ITEMS.' 
                WHERE '.$this->db->prepareIN('id', $itemsID), 'id');

        unset($fields['status'], $fields['moderated']);
        $counters = array();
        foreach ($new as $k => $n) {
            if ( ! isset($old[$k])) continue;
            $isOldPublicated = $old[$k]['status'] == BBS::STATUS_PUBLICATED && ( BBS::premoderation() ? $old[$k]['moderated'] > 0 : true);
            $isNewPublicated = $n['status'] == BBS::STATUS_PUBLICATED && ( BBS::premoderation() ? $n['moderated'] > 0 : true);
            if ( ! $isOldPublicated && !$isNewPublicated) continue;
            if ( ! $isOldPublicated && $isNewPublicated) {
                $this->itemsCountersCalculateGroup($n, 1, $counters);
            } else if ($isOldPublicated && ! $isNewPublicated) {
                $this->itemsCountersCalculateGroup($old[$k], -1, $counters);
            } else {
                foreach ($fields as $v) {
                    if ($old[$k][$v] != $n[$v]) {
                        $this->itemsCountersCalculateGroup($old[$k], -1, $counters);
                        $this->itemsCountersCalculateGroup($n, 1, $counters);
                        continue;
                    }
                }
            }
        }
        if ( ! empty($counters)) {
            $update = array();
            $where = array();
            $delete = array();
            foreach ($counters as $c => $v) {
                foreach ($v as $r => $vv) {
                    foreach ($vv as $d => $cnt) {
                        if ($cnt == 0) continue;
                        $update[] = ' WHEN cat_id = '.$c.' AND region_id = '.$r.' AND delivery = '.$d.' THEN items '.($cnt > 0 ? ' + '.$cnt : ' - '.abs($cnt));
                        $where[] = '(cat_id = '.$c.' AND region_id = '.$r.' AND delivery = '.$d.')';
                        if ($cnt < 0) {
                            $delete[] = '(cat_id = '.$c.' AND region_id = '.$r.' AND delivery = '.$d.')';
                        }
                    }
                }
            }

            if ( ! empty($update)) {
                $data = $this->db->select('SELECT cat_id, region_id, delivery FROM '.TABLE_BBS_ITEMS_COUNTERS.' WHERE ( '.join(' OR ', $where).' ) ');
                $exist = array();
                foreach ($data as $v) {
                    $exist[ $v['cat_id'] ][ $v['region_id'] ][ $v['delivery'] ] = 1;
                }
                $this->db->exec('
                    UPDATE '.TABLE_BBS_ITEMS_COUNTERS.' 
                    SET items = CASE '.join(' ', $update).' ELSE items END 
                    WHERE '.join(' OR ', $where));

                $insert = array();
                foreach ($counters as $c => $v) {
                    foreach ($v as $r => $vv) {
                        foreach ($vv as $d => $cnt) {
                            if ($cnt <= 0) continue;
                            if ( ! isset($exist[ $c ][ $r ][ $d ])) {
                                $insert[] = '('.$c.', '.$r.', '.$d.', '.$cnt.')';
                            }
                        }
                    }
                }
                if ( ! empty($insert)) {
                    $this->db->exec('
                        INSERT INTO '.TABLE_BBS_ITEMS_COUNTERS.' (cat_id, region_id, delivery, items) 
                        VALUES '.join(',', $insert).'
                        ON DUPLICATE KEY UPDATE items = items + VALUES(items);');
                }
            }
            if ( ! empty($delete)) {
                $data = $this->db->select('
                    SELECT cat_id, region_id, delivery FROM '.TABLE_BBS_ITEMS_COUNTERS.' FORCE INDEX(id)
                    WHERE ( '.join(' OR ', $delete).' ) AND items <= 0   
                ');
                if ( ! empty($data)) {
                    $delete = array();
                    foreach ($data as $v) {
                        $delete[] = '(cat_id = '.$v['cat_id'].' AND region_id = '.$v['region_id'].' AND delivery = '.$v['delivery'].')';
                    }
                    $this->db->exec('DELETE FROM '.TABLE_BBS_ITEMS_COUNTERS.' WHERE '.join(' OR ', $delete));
                }
            }
        }
    }

    /**
     * Перевод и сохранение мультиязычных данных
     * @param integer $itemID ID объявления
     * @param array $data данные для перевода
     * @param array $translates данные для остальных языков если указанны, то не переводим
     * @param array $old старые данные
     */
    protected function itemSaveTranslate($itemID, $data, $translates = array(), $old = array())
    {
        if (empty($itemID)) {
            return;
        }
        $isTranslate = BBS::translate(); # переводить используя сторонний сервис перевода
        if ( ! $isTranslate && empty($translates)) {
            return;
        }

        # поля генерируемые по шаблонам автозаполнения - не переводим.
        $noTranslate = array('title_list', 'descr_list');

        $lang = isset($data['lang']) ? $data['lang'] : (isset($old['lang']) ? $old['lang'] : false);
        if ($lang === false) {
            return;
        }

        $exist = $this->db->select_key('SELECT id, lang
            FROM ' . TABLE_BBS_ITEMS_LANG . '
            WHERE id = :id', 'lang', array(':id' => $itemID)
        );
        $translatesFields = array();

        $search = array();
        $fields = array_keys($this->langItem);
        foreach ($fields as $f) {
            if (in_array($f, $noTranslate)) continue;
            $search[$f . '_translates'] = isset($data[$f]) ? $data[$f] : '';
        }

        # сохраняем указанные локали
        if (!empty($translates)) {
            $f = reset($fields);
            if (empty($translates[$f])) {
                $translates = array();
            }
        }
        if (!empty($translates)) {
            $languages = $this->locale->getLanguages();
            $k = array_search($lang, $languages);
            unset($languages[$k]);
            foreach ($languages as $l) {
                $d = array();
                foreach ($translates as $f => $v) {
                    if (!isset($v[$l])) continue;
                    $d[$f] = $v[$l];
                    if ( ! in_array($f, $noTranslate)) {
                        $search[$f.'_translates'] .= ' '.$v[$l].' ';
                    }
                    if ( ! in_array($f, $translatesFields)) {
                        $translatesFields[] = $f;
                    }
                }
                if (!empty($d)) {
                    if (isset($exist[$l])) {
                        $this->db->update(TABLE_BBS_ITEMS_LANG, $d, array('id' => $itemID, 'lang' => $l));
                    } else {
                        $d['id'] = $itemID;
                        $d['lang'] = $l;
                        $this->db->insert(TABLE_BBS_ITEMS_LANG, $d);
                    }
                }
            }
            if ($isTranslate) {
                $exist = $this->db->select_key('SELECT id, lang
                    FROM '.TABLE_BBS_ITEMS_LANG.'
                    WHERE id = :id', 'lang', array(':id' => $itemID)
                    );
            }
        }

        # определяем какие поля необходимо переводить сравнив со старыми значениями
        if ($isTranslate) {
            $translate = array();
            foreach ($fields as $f) {
                if (in_array($f, $noTranslate)) {
                    continue;
                }
                if (in_array($f, $translatesFields)) {
                    continue;
                }
                if (!isset($data[$f])) {
                    continue;
                }
                if (empty($exist) || empty($old[$f]) || $data[$f] != $old[$f]) {
                    $translate[$f] = $data[$f];
                }
            }

            if (!empty($translate)) {
                $languages = $this->locale->getLanguages();
                $k = array_search($lang, $languages);
                unset($languages[$k]);
                if (!empty($languages)) {
                    # есть что переводить => переводим
                    $translated = BBSTranslate::i()->translate($translate, $lang, $languages);
                    if (!empty($translated)) {
                        foreach ($translated as $lng => $v) {
                            if (isset($exist[$lng])) {
                                $this->db->update(TABLE_BBS_ITEMS_LANG, $v, array('id' => $itemID, 'lang' => $lng));
                            } else {
                                $v['id'] = $itemID;
                                $v['lang'] = $lng;
                                $this->db->insert(TABLE_BBS_ITEMS_LANG, $v);
                            }
                            foreach ($fields as $f) {
                                if (!isset($v[$f])) {
                                    continue;
                                }
                                $search[$f.'_translates'] .= ' '.$v[$f].' ';
                            }
                        }
                    }
                }
            }
        }

        # сохраняем для sphinx-поиска
        if (!empty($search)) {
            $this->db->update(TABLE_BBS_ITEMS, $search, array('id' => $itemID));
        }
    }

    /**
     * Расчет изменения счетчиков при групповом обновлении
     * @param array $item данные объявления
     * @param integer $cnt на сколько изменить количество
     * @param @ref array $result результат
     */
    protected function itemsCountersCalculateGroup($item, $cnt, & $result)
    {
        if (empty($cnt)) return;
        $cats = array();
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $cats[] = 'cat_id'.$i;
        }
        $regs = array('reg1_country', 'reg2_region', 'reg3_city');

        /* [cat_id] [region_id] [delivery] */
        if ( ! isset($result[ 0 ][ 0 ][ 0 ])) {
            $result[ 0 ][ 0 ][ 0 ] = $cnt;
        } else {
            $result[ 0 ][ 0 ][ 0 ] += $cnt;
        }
        foreach ($cats as $c){
            if (empty($item[$c])) continue;
            if ( ! isset($result[ $item[$c] ][0][0])) {
                $result[ $item[$c] ][0][0] = $cnt;
            } else {
                $result[ $item[$c] ][0][0] += $cnt;
            }
        }
        if ($item['regions_delivery']) {
            if ($item['reg1_country']) {
                if ( ! isset($result[0][ $item['reg1_country'] ][0])) {
                    $result[0][ $item['reg1_country'] ][0] = $cnt;
                } else {
                    $result[0][ $item['reg1_country'] ][0] += $cnt;
                }
                foreach ($cats as $c) {
                    if (empty($item[$c])) continue;
                    if ( ! isset($result[ $item[$c] ][ $item['reg1_country'] ][1])) {
                        $result[ $item[$c] ][ $item['reg1_country'] ][1] = $cnt;
                    } else {
                        $result[ $item[$c] ][ $item['reg1_country'] ][1] += $cnt;
                    }
                }
            }
        } else {
            foreach ($regs as $r) {
                if (empty($item[$r])) continue;
                if ( ! isset($result[0][ $item[$r] ][0])) {
                    $result[0][ $item[$r] ][0] = $cnt;
                } else {
                    $result[0][ $item[$r] ][0] += $cnt;
                }
                foreach ($cats as $c) {
                    if (empty($item[$c])) continue;
                    if ( ! isset($result[ $item[$c] ][ $item[$r] ][0])) {
                        $result[ $item[$c] ][ $item[$r] ][0] = $cnt;
                    } else {
                        $result[ $item[$c] ][ $item[$r] ][0] += $cnt;
                    }
                }
            }
        }
    }

    /**
     * Данные об объявлении
     * @param integer $nItemID ID объявления
     * @param array $aFields
     * @param bool $bEdit
     * @return mixed
     */
    public function itemData($nItemID, array $aFields = array(), $bEdit = false)
    {
        return $this->itemDataByFilter(array('id' => $nItemID), $aFields, $bEdit);
    }

    /**
     * Данные об объявлении
     * @param array $aFilter
     * @param array $aFields
     * @param bool $bEdit
     * @return mixed
     */
    public function itemDataByFilter($aFilter, array $aFields = array(), $bEdit = false)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');

        if (empty($aFields)) {
            $aFields = array('*') + (!empty($this->cryptItems) ? $this->cryptItems : array());
        }

        $aParams = array();
        if (!is_array($aFields)) {
            $aFields = array($aFields);
        }
        foreach ($aFields as $v) {
            if (in_array($v, $this->cryptItems)) {
                $aParams[] = "BFF_DECRYPT(I.$v) as $v";
            } else {
                $aParams[] = 'I.' . $v;
            }
        }
        $aParams[] = 'U.email, U.phone_number, U.phone_number_verified, U.blocked as user_blocked, U.shop_id as user_shop_id';

        if ($bEdit) {
            # берем title для редактирования
            $aParams[] = 'I.title_edit as title';
        }

        $aData = $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_ITEMS . ' I
                            LEFT JOIN ' . TABLE_USERS . ' U ON U.user_id = I.user_id
                       ' . $aFilter['where'] . '
                       LIMIT 1', $aFilter['bind']
        );

        if (isset($aData['phones'])) {
            $aData['phones'] = (!empty($aData['phones']) ? func::unserialize($aData['phones']) : array());
        }
        if (isset($aData['link'])) {
            $aData['link'] = BBS::urlDynamic($aData['link']);
        }
        if (isset($aData['contacts'])) {
            $aData['contacts'] = Users::contactsToArray($aData['contacts']);
        }

        return $aData;
    }

    /**
     * Получение данных объявления для отправки email-уведомления
     * @param integer $nItemID ID объявления
     * @return array|bool
     */
    public function itemData2Email($nItemID)
    {
        $aFields = array();
        $aData = $this->db->tag('bbs-item-data-to-email', array('fields'=>&$aFields))->one_array('SELECT I.id as item_id, I.status,
                    I.link as item_link, I.title as item_title,
                    I.user_id, U.name, U.email, U.blocked as user_blocked, U.lang, U.user_id_ex, U.fake, S.last_login
                    '.(!empty($aFields) ? ','.join(',', $aFields) : '').'
                    FROM ' . TABLE_BBS_ITEMS . ' I,
                         ' . TABLE_USERS . ' U,
                         ' . TABLE_USERS_STAT . ' S
                    WHERE I.id = :id
                      AND I.user_id = U.user_id AND I.user_id = S.user_id', array(':id' => $nItemID)
        );

        do {
            if (empty($aData)) {
                break;
            }
            # ОБ удалялось пользователем
            if ((int)$aData['status'] === BBS::STATUS_DELETED) {
                break;
            }
            # ОБ неактивировано
            if ((int)$aData['status'] === BBS::STATUS_NOTACTIVATED) {
                break;
            }
            # Проверяем владельца:
            # - незарегистрированный
            if (empty($aData['user_id'])) {
                break;
            }
            # - фейковый
            if (!empty($aData['fake'])) {
                break;
            }
            # - заблокирован
            if (!empty($aData['user_blocked'])) {
                break;
            }

            # Формируем ссылку:
            $aData['item_link'] = BBS::urlDynamic($aData['item_link']);

            return $aData;
        } while (false);

        return false;
    }

    /**
     * Получение данных ОБ для страницы просмотра ОБ
     * @param integer $nItemID ID объявления
     * @return array
     */
    public function itemDataView($nItemID)
    {
        if (empty($nItemID) || $nItemID < 0) {
            return array();
        }

        $aFields = array();
        $data = $this->db->tag('bbs-item-data-view', array('fields'=>&$aFields))->one_array('SELECT I.*,
                            ((I.svc & ' . BBS::SERVICE_QUICK . ') > 0) as svc_quick,
                            CL.title as cat_title, C.addr as cat_addr,
                            C.price as price_on, C.price_sett,
                            U.phone_number, U.phone_number_verified
                            '.(!empty($aFields) ? ','.join(',', $aFields) : '').'
                       FROM ' . TABLE_BBS_ITEMS . ' I,
                            ' . TABLE_USERS . ' U,
                            ' . TABLE_BBS_CATEGORIES . ' C
                            LEFT JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON ' . $this->db->langAnd(false, 'C', 'CL') . '
                       WHERE I.id = :id
                         AND I.cat_id = C.id
                         AND I.user_id = U.user_id
                       LIMIT 1', array(':id' => $nItemID)
        );

        if (!empty($data)) {
            # форматируем цену
            if ($data['price_on']) {
                if (isset($data['price_sett'])) {
                    $priceSett =& $data['price_sett'];
                    $priceSett = (!empty($priceSett) ? func::unserialize($priceSett) : array());
                    if ($priceSett === false) {
                        $priceSett = array();
                    }
                }
                $data['price_value'] = $data['price'];
                $data['price'] = tpl::itemPrice($data['price'], $data['price_curr'], $data['price_ex']);
                if (($data['price_mod'] = ($data['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $data['price_mod'] = (!empty($priceSett['mod_title'][LNG]) ? $priceSett['mod_title'][LNG] : _t('bbs', 'Торг возможен'));
                } else {
                    $data['price_mod'] = '';
                }
                unset($data['price_sett'], $data['price_search']);
            }

            # формируем данные о городе и метро
            # формируем данные о регионе ОБ
            $data['country'] = Geo::regionData($data['reg1_country']);
            $data['country_title'] = (!empty($data['country']['title']) ? $data['country']['title'] : '');
            $data['region'] = Geo::regionData($data['reg2_region']);
            $data['region_title'] = (!empty($data['region']['title']) ? $data['region']['title'] : '');
            $data['city'] = Geo::regionData($data['reg3_city']);
            $data['city_title'] = (!empty($data['city']['title']) ? $data['city']['title'] : '');
            if ($data['metro_id']) {
                $data['metro_data'] = Geo::model()->metroData($data['metro_id'], false);
            }
            # район
            if (Geo::districtsEnabled() && $data['district_id']) {
                $data['district_data'] = Geo::model()->districtData($data['district_id']);
                if (empty($data['district_data'])) {
                    $aData['district_id'] = 0;
                } else {
                    $data['district_data']['title'] = $data['district_data']['title_'.LNG];
                }
            }

            # телефоны
            $data['phones'] = (!empty($data['phones']) ? func::unserialize($data['phones']) : array());
            # + телефон регистрации
            if (Users::registerPhoneContacts() && $data['phone_number'] && $data['phone_number_verified']) {
                array_unshift($data['phones'], array(
                    'v' => $data['phone_number'],
                    'm' => Users::phoneMask($data['phone_number']),
                ));
            }

            # контакты
            $data['contacts'] = array(
                'contacts' => Users::contactsToArray($data['contacts']),
                'phones'   => array(),
            );
            if (!empty($data['phones'])) {
                foreach ($data['phones'] as $v) {
                    $data['contacts']['phones'][] = $v['m'];
                }
            }
            $data['contacts']['has'] = (!empty($data['contacts']['contacts']) || !empty($data['phones']));

            # дин. свойства
            if ($this->controller->dp()->cacheKey) {
                $sql = $this->controller->dpPrepareSelectFieldsQuery('', $data['cat_id']);
                if ( ! empty($sql)) {
                    $dp = $this->db->tag('bbs-item-data-view-dp', array('fields'=>&$sql))->one_array('SELECT '.$sql.' FROM '.TABLE_BBS_ITEMS.' WHERE id = :id', array(':id'=>$nItemID));
                    if ( ! empty($dp) && is_array($dp)) {
                        foreach ($dp as $k=>$v) {
                            if ( ! isset($data[$k])) {
                                $data[$k] = $v;
                            }
                        }
                    }
                }
            }

            # проверим наличие перевода
            if ($data['lang'] != LNG) {
                $translate = $this->itemDataTranslate($nItemID, LNG);
                $fields = array_keys($this->langItem);
                foreach ($fields as $f) {
                    if ( ! empty($translate[$f])) {
                        $data[$f] = $translate[$f];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Получение данных о переводе для объявления
     * @param integer $itemID ID объявления
     * @param string|bool $lng язык (или для всех языков)
     * @return mixed
     */
    public function itemDataTranslate($itemID, $lng = false)
    {
        if ($lng) {
            return $this->db->tag('bbs-item-data-translate-lang')->one_array('
                SELECT id, lang, title, descr 
                FROM '.TABLE_BBS_ITEMS_LANG.' 
                WHERE id = :id AND lang = :lng', array(':id' => $itemID, ':lng' => $lng));
        } else {
            return $this->db->tag('bbs-item-data-translate-langs')->select_key('
                SELECT id, lang, title, descr 
                FROM '.TABLE_BBS_ITEMS_LANG.' 
                WHERE id = :id', 'lang', array(':id' =>$itemID));
        }
    }

    /**
     * Получение данных о нескольких объявлениях по фильтру
     * @param array $filter параметры фильтра
     * @param array $fields требуемые поля
     * @param array $opts:
     *   string 'context' контекст вызова функции
     *   string 'prefix' префикс таблицы
     *   boolean 'oneColumn' один столбец из таблицы
     *   string 'groupKey' поле для группировки данных в массиве, по-умолчанию: 'id'
     *   string|array 'groupBy' условие запроса GROUP BY
     *   string|array 'orderBy' условие запроса ORDER BY
     *   integer|string|array 'limit' лимит выборки, например: 15
     *   callable 'iterator' функция-итератор
     * @return mixed
     */
    public function itemsDataByFilter(array $filter, array $fields = array(), array $opts = array())
    {
        if (empty($fields)) {
            $fields = array('*');
        }
        if ( ! is_array($fields)) {
            $fields = array($fields);
        }

        # default options:
        func::array_defaults($opts, array(
            'context'  => '?',
            'prefix'   => '',
            'oneColumn'=> false,
            'groupKey' => 'id',
            'groupBy'  => false,
            'orderBy'  => false,
            'limit'    => false,
            'iterator' => false,
            'ttl'      => 0,
        ));

        $this->db->tag('bbs-items-data-by-filter', array(
            'filter'  => &$filter,
            'fields'  => &$fields,
            'options' => &$opts,
        ));

        $select = $this->db->select_prepare(TABLE_BBS_ITEMS, $fields, $filter, $opts);

        if ( ! empty($opts['iterator'])) {
            $this->db->select_iterator($select['query'], $select['bind'], $opts['iterator']);
        } else if ($opts['oneColumn']) {
            return $this->db->select_one_column($select['query'], $select['bind'], $opts['ttl']);
        } else if (!empty($opts['groupKey'])) {
            return $this->db->select_key($select['query'], $opts['groupKey'], $select['bind'], $opts['ttl']);
        } else {
            return $this->db->select($select['query'], $select['bind'], $opts['ttl']);
        }
    }


    /**
     * Поиск ID объявлений по фильтру
     * @param array $filter параметры фильтра
     * @param array $opts:
     *   string 'context' контекст вызова функции
     *   boolean 'count' только подсчет кол-ва
     *   string|array 'groupBy' условие запроса GROUP BY
     *   string|array 'orderBy' условие запроса ORDER BY
     *   integer 'limit' лимит выборки, например: 15
     *   integer 'offset' пропуск результатов выборки
     * @return array|integer список ID найденных объявлений или кол-во найденных исходя из фильтра
     */
    public function itemsSearch(array $filter, array $opts = array())
    {
        func::array_defaults($opts, array(
            'returnQuery' => false,
            'context'  => '?',
            'count'    => false,
            'groupBy'  => false,
            'orderBy'  => false,
            'limit'    => 0,
            'offset'   => 0,
            'ttl'      => 0,
        ));

        $this->db->tag('bbs-items-id-by-filter', array(
            'filter'  => &$filter,
            'options' => &$opts,
        ));

        # Sphinx:
        if (isset($filter[':query']) && BBSItemsSearchSphinx::enabled()) {
            $sphinx = $this->controller->itemsSearchSphinx();
            $data = $sphinx->searchItems($filter[':query'], $filter, $opts['count'], $opts['limit'], $opts['offset']);
            if ($data !== false) return $data;
        }

        # MySQL:
        if (isset($filter[':query'])) {
            if (BBS::translate()) {
                $filter[':query'] = array(
                    '(title LIKE (:query) OR title_translates LIKE (:query) OR 
                      descr LIKE (:query) OR descr_translates LIKE (:query) OR
                      phones LIKE (:query))',
                    ':query' => '%' . $filter[':query'] . '%',
                );
            } else {
                $filter[':query'] = array(
                    '(title LIKE (:query) OR descr LIKE (:query) OR phones LIKE (:query))',
                    ':query' => '%' . $filter[':query'] . '%',
                );
            }
        }
        # фильтр по категории
        if (isset($filter[':cat-filter'])) {
            $catFilter = $this->catPathFilter($filter[':cat-filter']);
            if ($catFilter !== false) {
                $filter[':cat-filter'] = $catFilter;
            } else {
                unset($filter[':cat-filter']);
            }
        }
        # фильтр по региону
        if (isset($filter[':region-filter'])) {
            $regionID = $filter[':region-filter'];
            $regionFilter = array();
            if ($regionID > 0 && ($regionData = Geo::regionData($regionID))) {
                $searchDelivery = config::sysAdmin('bbs.search.delivery', true, TYPE_BOOL);
                if ($regionData['numlevel'] == Geo::lvlCountry) {
                    $regionFilter[] = array($regionData['id']);
                } else if ($regionData['numlevel'] == Geo::lvlRegion) {
                    $regionFilter[] = array($regionData['country'], $regionID);
                    if ($searchDelivery) {
                        $regionFilter[] = array($regionData['country'], 'ANY');
                    }
                } else if ($regionData['numlevel'] == Geo::lvlCity) {
                    $regionFilter[] = array($regionData['country'], $regionData['pid'], $regionID);
                    if ($searchDelivery) {
                        $regionFilter[] = array($regionData['country'], 'ANY');
                    }
                }
            }
            if ( ! empty($regionFilter)) {
                $regionFilterQuery = array();
                $regionFilterBind = array();
                $i = 1;
                foreach ($regionFilter as $v) {
                    $regionFilterQuery[] = 'reg_path LIKE :regionQuery'.$i;
                    $regionFilterBind[':regionQuery'.$i] = '-' . join('-', $v) . '-%';
                    $i++;
                }
                $filter[':region-filter'] = array('('.join(' OR ', $regionFilterQuery).')') + $regionFilterBind;
            }
            if (!isset($filter[':cat-filter'])) {
                $filter[':cat-filter'] = $this->catPathFilter(0, true);
            }
        }
        if (isset($filter['is_publicated'], $filter['status'])) {
            # Оптимизация под индексы
            $filter = $this->itemsSearchOptimize($filter);
        }
        # Оптимизация дин. свойств
        if (isset($filter[':dp'])) {
            if (is_string($filter[':dp'])) {
                $filter[':dp'] = 'id IN (SELECT id FROM '.TABLE_BBS_ITEMS.' FORCE INDEX (search_dp) WHERE '.$filter[':dp'].' )';
            } else if (is_array($filter[':dp'])) {
                foreach($filter[':dp'] as $k => $v) {
                    if ($k == 0 && is_string($v)) {
                        $filter[':dp'][0] = 'id IN (SELECT id FROM '.TABLE_BBS_ITEMS.' FORCE INDEX (search_dp) WHERE '.$filter[':dp'][0].' )';
                        break;
                    }
                }
            }
        }
        if ($opts['offset'] > 0) {
            $opts['limit'] = array($opts['offset'], $opts['limit']);
        }
        if ($opts['count']) {
            unset($opts['limit'], $opts['offset']);
            return $this->db->select_rows_count(TABLE_BBS_ITEMS, $filter, $opts);
        }
        $select = $this->db->select_prepare(TABLE_BBS_ITEMS, array('id'), $filter, $opts);
        if ($opts['returnQuery']) {
            return $select;
        }
        return $this->db->select_one_column($select['query'], $select['bind'], $opts['ttl']);
    }

    /**
     * Оптимизируем запрос поиска с учетом индексов
     * @param array $filter
     * @param array $opts
     * @return array
     */
    protected function itemsSearchOptimize(array $filter, array $opts = array())
    {
        # нулевые значения для индекса
        # search: is_publicated, status, cat_path, reg_path, cat_type, imgcnt, owner_type, price_search, addr_lat, district_id, metro_id
        $any = array(
            'is_publicated'  => array('>=',0),
            'status'         => array('>=',0),
            ':cat-filter'    => $this->catPathFilter(0, true),
            ':region-filter' => array('reg_path LIKE :regionQueryAny', ':regionQueryAny' => '-%'),
            'cat_type'       => array('>=',\BBS::TYPE_OFFER),
            'imgcnt'         => array('>=',0),
            'owner_type'     => array('>=',0),
            ':price'         => array('price_search >= :priceAny', ':priceAny' => 0),
            'addr_lat'       => array('<',91),
            'district_id'    => array('>=',0),
            'metro_id'       => array('>=',0),
        );

        # отбросим хвостовые нулевые фильтры
        $any = array_reverse($any);
        foreach ($any as $k => $v) {
            if (isset($filter[$k])) break;
            unset($any[$k]);
        }
        $any = array_reverse($any);
        $result = array();
        foreach ($any as $k => $v) {
            $result[ $k ] = (isset($filter[$k]) ? $filter[$k] : $v);
            unset($filter[$k]);
        }
        if ( ! empty($filter)) {
            foreach ($filter as $k => $v) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    /**
     * Обновляем данные об объявлениях
     * @param array $aItemsID ID объявлений
     * @param array $aData данные
     * @return integer кол-во обновленных объявлений
     */
    public function itemsSave(array $aItemsID, array $aData)
    {
        if (empty($aItemsID) || empty($aData)) {
            return 0;
        }
        # проверка необходимости пересчета счетчиков объявлений
        $checkCounters = $this->itemSaveCountersCheck($aItemsID, $aData);

        $result = $this->db->update(TABLE_BBS_ITEMS, $aData, array('id' => $aItemsID));
        if ($checkCounters !== false) {
            $this->itemsSaveCountersUpdate($aItemsID, $checkCounters);
        }
        if (isset($aData['moderated']) && $aData['moderated'] == 1) {
            $this->itemSaveModerated($aItemsID);
        }
        # перестраиваем поля для индексов
        $this->itemsIndexesUpdate($aItemsID);

        return $result;
    }

    /**
     * Обновление данных объявлений по фильтру
     * @param array $update обновляемые данные
     * @param array $filter параметры фильтра
     * @param array $opts:
     *   string 'context' контекст вызова функции
     *   array 'bind' доп. параметры подставляемые в запрос
     *   string|array 'orderBy' условие запроса ORDER BY
     *   integer|string|array 'limit' лимит выборки, например: 15
     *   array 'cryptKeys' шифруемые столбцы
     * @return integer кол-во обновленных объявлений
     */
    public function itemsUpdateByFilter(array $update, array $filter, array $opts = array())
    {
        # default options:
        func::array_defaults($opts, array(
            'context'   => '?',
            'tag'       => '',
            'bind'      => array(),
            'orderBy'   => false,
            'limit'     => false,
            'iterator'  => false,
            'cryptKeys' => array(),
        ));

        # tag
        if (!empty($opts['tag'])) {
            $this->db->tag($opts['tag']);
        }

        $updated = (int)$this->db->update(TABLE_BBS_ITEMS, $update, $filter, $opts['bind'], $opts['cryptKeys'], $opts);
        if ($updated > 0) {
            if (isset($filter['id']) && (
                isset($update['status']) ||
                isset($update['deleted'])
            )) {
                $this->itemsIndexesUpdate($filter['id'], 'is_publicated');
            }
        }
        return $updated;
    }

    /**
     * Актуализация индексируемых полей статуса объявлений
     * @param array $itemsID ID объявлений
     * @param string|boolean $indexName название индекса для пересборки или FALSE - все индексы
     */
    public function itemsIndexesUpdate(array $itemsID = array(), $indexName = false)
    {
        $filter = array();
        if (!empty($itemsID)) {
            $filter['id'] = $itemsID;
        }
        $opts = array('context'=>__FUNCTION__);

        # is_publicated:
        if ($indexName === 'is_publicated' || $indexName === false) {
            $this->itemsUpdateByFilter(array(
                'is_publicated' => 0,
            ), $filter + array(
                'is_publicated' => 1,
            ), $opts);
            $filterCopy = $filter;
            $filterCopy['status'] = BBS::STATUS_PUBLICATED;
            $filterCopy['deleted'] = 0;
            if (BBS::premoderation()) {
                $filterCopy['moderated'] = 1;
            }
            $this->itemsUpdateByFilter(array(
                'is_publicated' => 1,
            ), $filterCopy, $opts);
        }

        # is_moderating:
        if ($indexName === 'is_moderating' || $indexName === false) {
            $this->itemsUpdateByFilter(array(
                'is_moderating' => 0,
            ), $filter + array(
                'is_moderating' => 1,
            ), $opts);
            $filterCopy = $filter;
            $filterCopy[] = 'moderated != 1';
            $filterCopy[] = 'status NOT IN (:statusNotActivated, :statusDeleted)';
            $filterCopy['deleted'] = 0;
            $this->itemsUpdateByFilter(array(
                'is_moderating' => 1,
            ), $filterCopy, array(
                'context' => $opts['context'],
                'bind' => array(
                    ':statusNotActivated' => BBS::STATUS_NOTACTIVATED,
                    ':statusDeleted'      => BBS::STATUS_DELETED,
                ),
            ));
        }

        # cat_path:
        if ($indexName === 'cat_path') {
            $fieldPrefix = 'cat_id';
            $fieldsList = array();
            for ($i = 1; $i<= BBS::CATS_MAXDEEP; $i++) {
                $fieldsList[] = $fieldPrefix.$i;
            }
            $this->itemsUpdateByFilter(array(
                'cat_path = CONCAT(:sep, CONCAT_WS(:sep, ' . join(', ', $fieldsList) . '), :sep)',
            ), $filter + array(
                'cat_id > 0',
            ), array(
                'context' => $opts['context'],
                'bind' => array(':sep'=>'-'),
            ));
        }

        # reg_path:
        if ($indexName === 'reg_path') {
            $this->itemsUpdateByFilter(array(
                'reg_path = CONCAT(:sep, CONCAT_WS(:sep, reg1_country, reg2_region, reg3_city), :sep)',
            ), $filter + array(
                'regions_delivery = 0',
            ), array(
                'context' => $opts['context'],
                'bind' => array(':sep'=>'-'),
            ));
            $this->itemsUpdateByFilter(array(
                'reg_path = CONCAT(:sep, reg1_country, :sep, :any, :sep)',
            ), $filter + array(
                'regions_delivery = 1',
            ), array(
                'context' => $opts['context'],
                'bind' => array(':sep'=>'-', ':any'=>'ANY'),
            ));
        }
    }

    /**
     * Отвязываем объявления от магазина (при его удалении)
     * @param integer $nShopID ID магазина
     * @return integer кол-во затронутых объявлений
     */
    public function itemsUnlinkShop($nShopID)
    {
        if (empty($nShopID) || $nShopID <= 0) {
            return 0;
        }

        return $this->db->update(TABLE_BBS_ITEMS, array('shop_id' => 0), array('shop_id' => $nShopID));
    }

    /**
     * Привязываем объявления пользователя к магазину
     * @param integer $nShopID ID магазина
     * @return integer кол-во затронутых объявлений
     */
    public function itemsLinkShop($nUserID, $nShopID)
    {
        if (empty($nUserID) || $nUserID < 0 || empty($nShopID) || $nShopID < 0) {
            return 0;
        }

        return $this->db->update(TABLE_BBS_ITEMS, array('shop_id' => $nShopID), array('user_id' => $nUserID));
    }

    /**
     * Получаем общее кол-во объявлений, ожидающих модерации
     * @return integer
     */
    public function itemsModeratingCounter()
    {
        $filter = array('is_moderating'=>1);
        $this->db->tag('bbs-items-moderating-count', array('filter'=>&$filter));
        return $this->itemsCount($filter);
    }

    /**
     * Получаем общее кол-во опубликованных объявлений
     * @param array $aFilter доп. фильтр
     * @return integer
     */
    public function itemsPublicatedCounter(array $filter = array())
    {
        if ( ! isset($filter['cat_id'])) {
            $filter['cat_id'] = 0;
        }
        if (isset($filter['reg1_country'])) {
            $filter['region_id'] = $filter['reg1_country'];
            unset($filter['reg1_country']);
            $data = $this->itemsCountByFilter($filter, array('cat_id', 'items'), false);
            $sum = 0;
            foreach ($data as $v) {
                $sum += $v['items'];
            }
            return $sum;
        }
        if (isset($filter['reg2_region']) || isset($filter['reg3_city'])) {
            $filter['region_id'] = isset($filter['reg3_city']) ? $filter['reg3_city'] : $filter['reg2_region'];
            $filter['delivery'] = 0;
            unset($filter['reg2_region'], $filter['reg3_city']);
            $sum = (int)$this->itemsCountByFilter($filter);
            $reg = Geo::regionData($filter['region_id']);
            if ( ! empty($reg['country'])) {
                $filter['region_id'] = $reg['country'];
                $filter['delivery'] = 1;
                $sum += (int)$this->itemsCountByFilter($filter);
            }
            return $sum;
        }
        if ( ! isset($filter['region_id'])) {
            $filter['region_id'] = 0;
            $filter['delivery'] = 0;
        } else {
            return (int)$this->itemsCountByFilter($filter);
        }
        return (int)$this->itemsCountByFilter($filter);
    }

    /**
     * Публикация нескольких объявлений по фильтру
     * @param array $aFilter фильтр требуемых объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsPublicate(array $aFilter)
    {
        if (empty($aFilter)) {
            return 0;
        }

        $result = 0;
        $now = $this->db->now();
        $publicatedPeriod = $this->controller->getItemPublicationPeriod();
        $update = array(
            'status_prev = status',
            'is_publicated'    => 1,
            'status'           => BBS::STATUS_PUBLICATED,
            'publicated'       => $now,
            'publicated_to'    => $publicatedPeriod, # от текущей даты
        );
        $timeout = config::sysAdmin('bbs.publicate.topup.timeout', 7, TYPE_UINT);
        if ($timeout > 0) {
            # Публикуем + поднимаем
            $updateUp = $update; $updateUp['publicated_order'] = $now;
            $filterUp = $aFilter; $filterUp[] = 'DATEDIFF(:now, publicated_order) >= :days';
            $result += $this->itemsUpdateByFilter(
                $updateUp,
                $filterUp, array(
                'bind' => array(
                    ':now' => $now,
                    ':days' => $timeout,
                ),
                'context' => 'items-publicate-topup',
            ));
        }
        # Публикуем оставшиеся
        $result += $this->itemsUpdateByFilter($update, $aFilter, array(
            'context' => 'items-publicate',
        ));

        return $result;
    }

    /**
     * Публикация всех на текущий момент снятых с публикации объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsPublicateAllUnpublicated()
    {
        $updated = (int)$this->db->update(TABLE_BBS_ITEMS, array(
                'status_prev = status',
                'status'           => BBS::STATUS_PUBLICATED,
                'status_changed'   => $this->db->now(),
                'publicated_to'    => $this->controller->getItemPublicationPeriod(),
                'publicated_order' => $this->db->now(),
            ),
            array(
                'is_publicated' => 0,
                'status' => BBS::STATUS_PUBLICATED_OUT,
            )
        );
        if ($updated) {
            $this->itemsIndexesUpdate();
        }
    }

    /**
     * Продление нескольких объявлений по фильтру
     * @param array $aFilter фильтр требуемых объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsRefresh(array $aFilter)
    {
        if (empty($aFilter)) {
            return 0;
        }

        $updated = 0;
        $this->itemsDataByFilter($aFilter, array('id', 'publicated_to'), array('iterator' => function($item) use (&$updated) {
            $res = $this->itemSave($item['id'], array(
                # от даты завершения публикации объявления
                'publicated_to' => $this->controller->getItemRefreshPeriod($item['publicated_to']),
            ));
            if ( ! empty($res)) $updated++;
        }, 'context'=>'items-refresh'));
        return $updated;
    }

    /**
     * Бесплатное поднятие нескольких объявлений по фильтру
     * @param array $aFilter фильтр требуемых объявлений
     * @param array $opts доп. параметры
     * @return integer кол-во затронутых объявлений
     */
    public function itemsUpFree(array $aFilter, array $opts = array())
    {
        if (empty($aFilter)) {
            return 0;
        }
        if (!isset($opts['context'])) {
            $opts['context'] = 'items-upfree';
        }

        $now = $this->db->now();
        return $this->itemsUpdateByFilter(array(
            'svc_up_free'      => $now,
            'publicated_order' => $now,
            'svc_up_date'      => $now,
        ), $aFilter, $opts);
    }

    /**
     * Снятие нескольких объявлений с публикации по фильтру
     * @param array $aFilter фильтр требуемых объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsUnpublicate(array $aFilter)
    {
        if (empty($aFilter)) {
            return 0;
        }

        return $this->itemsUpdateByFilter(array(
            'status_prev = status',
            'status'        => BBS::STATUS_PUBLICATED_OUT,
            'publicated_to' => $this->db->now(),
            'is_publicated' => 0,
        ), $aFilter, array(
            'context' => 'items-unpublicate',
        ));
    }

    /**
     * Удаление нескольких объявлений одного пользователя
     * @param array $aItemsID ID удаляемых объявлений
     * @param bool $bUserCounterUpdate выполнять актуализацию счетчика объявлений пользователя
     * @return int кол-во удаленных объявлений
     */
    public function itemsDelete(array $aItemsID, $bUserCounterUpdate)
    {
        if (empty($aItemsID)) {
            return 0;
        }
        $cats = array();
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $cats[] = 'I.cat_id'.$i;
        }

        $aFields = array();
        $aData = $this->db->tag('bbs-items-delete-data', array('fields'=>&$aFields))->select('SELECT I.id, I.user_id, I.status, I.cat_id, '.join(', ', $cats).', I.cat_type,
                                           I.imgcnt, I.svc_press_status, I.claims_cnt, I.messages_total, I.moderated,
                                           I.reg1_country, I.reg2_region, I.reg3_city, I.regions_delivery, I.created,
                                           U.activated as user_activated
                                           '.(!empty($aFields) ? ','.join(',', $aFields) : '').'
                                    FROM ' . TABLE_BBS_ITEMS . ' I
                                        LEFT JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id
                                    WHERE ' . $this->db->prepareIN('I.id', $aItemsID)
        );
        if (empty($aData)) {
            return 0;
        }

        $nUserCounterDecrement = 0;
        $bClaimsCounterUpdate = false;
        $bModerationCounterUpdate = false;
        $bPressCounterUpdate = false;
        $bMessagesUnlink = false;

        $aItemsID = array();
        $oImages = $this->controller->itemImages();
        $bHookDelete = bff::hooksAdded('bbs.item.delete');
        foreach ($aData as &$v) {
            # удаляем изображения
            if ($v['imgcnt'] > 0) {
                $oImages->setRecordID($v['id']);
                $oImages->deleteAllImages(false);
            }
            if ($bUserCounterUpdate && $v['status'] != BBS::STATUS_NOTACTIVATED) {
                $nUserCounterDecrement++;
            }
            if ($v['messages_total'] > 0) {
                $bMessagesUnlink = true;
            }
            if ($v['claims_cnt'] > 0) {
                $bClaimsCounterUpdate = true;
            }
            if ($v['svc_press_status'] > 0) {
                $bPressCounterUpdate = true;
            }
            if ($v['moderated'] != 1) {
                $bModerationCounterUpdate = true;
            }
            $aItemsID[] = $v['id'];
            if ($v['status'] == BBS::STATUS_PUBLICATED && ( BBS::premoderation() ? $v['moderated'] > 0 : true)) {
                $this->itemsCountersUpdate($v, -1);
            }
            if ($bHookDelete) {
                bff::hook('bbs.item.delete', $v);
            }
        }
        unset($v);

        $this->db->delete(TABLE_BBS_ITEMS_FAV, array('item_id' => $aItemsID));
        $this->db->delete(TABLE_BBS_ITEMS_VIEWS, array('item_id' => $aItemsID));
        $this->db->delete(TABLE_BBS_ITEMS_CLAIMS, array('item_id' => $aItemsID));
        $this->db->delete(TABLE_BBS_ITEMS_LANG, array('id' => $aItemsID));

        if (BBS::commentsEnabled()) {
            # удаляем комментарии
            $this->db->delete(TABLE_BBS_ITEMS_COMMENTS, array('item_id' => $aItemsID));
            # пересчитываем кол-во непромодерированных комментариев
            $this->controller->itemComments()->updateUnmoderatedAllCounter(null);
        }

        $res = $this->db->delete(TABLE_BBS_ITEMS, array('id' => $aItemsID));

        if (!empty($res)) {
            # удаляем связь сообщений внутренней почты с удаляемыми объявлениями
            if ($bMessagesUnlink) {
                InternalMail::model()->unlinkMessagesItemsID($aItemsID);
            }

            # актуализируем счетчик необработанных жалоб
            if ($bClaimsCounterUpdate) {
                $this->controller->claimsCounterUpdate(null);
            }

            # актуализируем счетчик ожидающих печати
            if ($bPressCounterUpdate) {
                $this->controller->pressCounterUpdate(null);
            }

            # актуализируем счетчик объявлений пользователя
            if ($bUserCounterUpdate && $nUserCounterDecrement > 0) {
                $aItemData = reset($aData);
                $this->security->userCounter('items', -$nUserCounterDecrement, $aItemData['user_id'], true);
            }

            # актуализируем счетчик "на модерации"
            if ($bModerationCounterUpdate) {
                $this->controller->moderationCounterUpdate(null);
            }
        }

        return intval($res);
    }

    /**
     * Полное удаление всех объявлений пользователя
     * @param integer $userID ID пользователя
     * @param array $opts доп. параметры:
     *  'markDeleted' - только пометить как удаленные
     * @return integer кол-во затронутых объявлений
     */
    public function itemsDeleteByUser($userID, array $opts = array())
    {
        $total = 0;
        if ($userID <= 0) {
            return $total;
        }

        if ( ! empty($opts['markDeleted'])) {
            $total = $this->itemsUpdateByFilter(array(
                'publicated_to' => $this->db->now(), # помечаем дату снятия с публикации
                'status_prev = status',
                'status_changed'=> $this->db->now(),
                'status'        => BBS::STATUS_DELETED,
                'deleted'       => 1,
                'is_publicated' => 0,
                'is_moderating' => 0,
            ), array(
                'user_id' => $userID,
            ), array(
                'context' => 'user-items-delete',
            ));
        } else {
            $data = array();
            $this->db->tag('bbs-items-delete-by-user')->select_iterator('SELECT id
                    FROM ' . TABLE_BBS_ITEMS . '
                    WHERE user_id = :user_id', array(':user_id' => $userID),
                function($row) use(& $data, &$total){
                $data[] = $row['id'];
                if (count($data) > 100) {
                    $total += $this->itemsDelete($data, true);
                    $data = array();
                }
            });
            if ( ! empty($data)) {
                $total += $this->itemsDelete($data, true);
            }
        }

        return $total;
    }

    /**
     * Получаем ID избранных ОБ пользователя
     * @param integer $nUserID ID пользователя
     * @param array $aOnlyID фильтр по ID объявлений
     * @return array|mixed
     */
    public function itemsFavData($nUserID, array $aOnlyID = array())
    {
        if (empty($nUserID)) {
            return array();
        }

        return $this->db->tag('bbs-items-fav-data')->select_one_column('SELECT item_id
                FROM ' . TABLE_BBS_ITEMS_FAV . '
                WHERE user_id = :userID
                    '.(!empty($aOnlyID) ? ' AND '.$this->db->prepareIN('item_id', $aOnlyID) : '').'
                GROUP BY item_id',
            array(':userID' => $nUserID)
        );
    }

    /**
     * Сохранение избранных ОБ пользователя
     * @param integer $nUserID ID пользователя
     * @param array $aItemID ID объявлений
     * @return integer|mixed кол-во сохраненных ОБ или FALSE
     */
    public function itemsFavSave($nUserID, array $aItemID)
    {
        if (empty($aItemID) || !$nUserID) {
            return 0;
        }

        $aData = array();
        foreach ($aItemID as $id) {
            $aData[] = array(
                'item_id' => $id,
                'user_id' => $nUserID,
            );
        }

        return $this->db->multiInsert(TABLE_BBS_ITEMS_FAV, $aData);
    }

    /**
     * Удаление избранных ОБ пользователя
     * @param integer $nUserID ID пользователя
     * @param integer|boolean|array $mItemID ID объявления(-ний) или FALSE (всех избранных объявлений)
     * @return mixed
     */
    public function itemsFavDelete($nUserID, $mItemID = false)
    {
        $aCond = array('user_id' => $nUserID);
        if ($mItemID !== false) {
            $aCond['item_id'] = $mItemID;
        }

        return $this->db->delete(TABLE_BBS_ITEMS_FAV, $aCond);
    }

    /**
     * Накручиваем счетчик просмотров
     * @param integer $nItemID ID объявления
     * @param string $sViewType тип просмотра: 'item'=>просмотр ОБ, 'contacts'=>просмотр контактов ОБ
     * @param integer $nViewsToday текущий счетчик просмотров ОБ за сегодня или 0
     * @return boolean
     */
    public function itemViewsIncrement($nItemID, $sViewType, $nViewsToday = 0)
    {
        if (empty($nItemID) || !in_array($sViewType, array('item', 'contacts'))) {
            return false;
        }

        $sDate = date('Y-m-d');
        $sField = $sViewType . '_views';

        # TABLE_BBS_ITEMS_VIEWS:
        # 1. пытаемся вначале обновить статистику
        # поскольку запись о статистике за сегодня уже может быть создана
        $res = $this->db->update(TABLE_BBS_ITEMS_VIEWS,
            array($sField . ' = ' . $sField . ' + 1'),
            array('item_id' => $nItemID, 'period' => $sDate)
        );

        # обновить не получилось
        if (empty($res)) {
            # 2. начинаем подсчет статистики за сегодня
            if ( ! empty($nViewsToday)) {
                $this->db->update(TABLE_BBS_ITEMS, array(
                    'views_today' => 0,
                ), array('id' => $nItemID));
            }
            $res = $this->db->insert(TABLE_BBS_ITEMS_VIEWS, array(
                    'item_id' => $nItemID,
                    $sField   => 1,
                    'period'  => $sDate,
                ), false
            );
        }

        # TABLE_BBS_ITEMS:
        # 3. накручиваем счетчик просмотров ОБ/Контактов за сегодня (+ общий)
        if (!empty($res)) {
            $this->db->update(TABLE_BBS_ITEMS, array(
                    'views_total = views_total + 1',
                    'views_today = views_today + 1',
                    'views_' . $sViewType . '_total = views_' . $sViewType . '_total + 1',
                ), array('id' => $nItemID)
            );
        }

        return !empty($res);
    }

    /**
     * Получаем данные о статистике просмотров ОБ
     * @param integer $nItemID ID объявления
     * @return array
     */
    public function itemViewsData($nItemID)
    {
        $aResult = array('data' => array(), 'from' => '', 'to' => '', 'total' => 0, 'today' => 0);

        do {
            if (empty($nItemID)) {
                break;
            }

            $aData = $this->db->select('SELECT SUM(item_views) as item, SUM(contacts_views) as contacts, period
                        FROM ' . TABLE_BBS_ITEMS_VIEWS . '
                        WHERE item_id = :id
                        GROUP BY period
                        ORDER BY period ASC', array(':id' => $nItemID)
            );
            if (empty($aData)) {
                break;
            }
            foreach ($aData as $k => $v) {
                $aData[$k]['total'] = $v['item'] + $v['contacts'];
                $aData[$k]['date'] = $v['period'];
                unset($aData[$k]['period']);
            }

            $aItemData = $this->itemData($nItemID, array('views_total', 'views_today'));
            if (empty($aItemData)) {
                break;
            }
            $aResult['total'] = $aItemData['views_total'];
            $aResult['today'] = $aItemData['views_today'];

            $view = current($aData);
            $aResult['from'] = $view['date']; # от
            $nFrom = strtotime($view['date']);

            $view = end($aData);
            $aResult['to'] = $view['date']; # до
            $nTo = strtotime($view['date']);

            reset($aData);

            # дополняем днями, за которые статистика отсутствует
            $nDay = 86400;
            $nTotalDays = (($nTo - $nFrom) / $nDay) + 1;
            if ($nTotalDays > sizeof($aData)) {
                $aDataFull = array();
                foreach ($aData as $v) {
                    $aDataFull[$v['date']] = $v;
                }
                $aDataResult = array();
                for ($i = $nFrom; $i <= $nTo; $i += $nDay) {
                    $sDate = date('Y-m-d', $i);
                    if (isset($aDataFull[$sDate])) {
                        $aDataResult[$sDate] = $aDataFull[$sDate];
                    } else {
                        $aDataResult[$sDate] = array('item' => 0, 'contacts' => 0, 'total' => 0, 'date' => $sDate);
                    }
                }
                unset($aDataFull);
                $aData = array_values($aDataResult);
            }

            $aResult['data'] = $aData;

        } while (false);

        return $aResult;
    }

    /**
     * Актуализация статуса объявлений (cron)
     * Рекомендуемый период: раз в 10 минут
     */
    public function itemsCronStatus()
    {

            # Удаляем неактивированные объявления по прошествии суток
            $data = array();
            $this->db->select_iterator('SELECT id
                FROM ' . TABLE_BBS_ITEMS . '
                WHERE status = :status
                  AND activate_expire <= :now',
                array(
                    ':status' => BBS::STATUS_NOTACTIVATED,
                    ':now'    => $this->db->now()
                ),
            function($row) use(& $data){
                $data[] = $row['id'];
                if (count($data) > 100) {
                    $this->itemsDelete($data, false);
                    # email уведомления не отправляем, поскольку email адреса не подтверджались
                    $data = array();
                }
            });

            if (!empty($data)) {
                $this->itemsDelete($data, false);
                # email уведомления не отправляем, поскольку email адреса не подтверджались
            }

            # Снимаем с публикации просроченные объявления
            $this->itemsUpdateByFilter(array(
                'status'         => BBS::STATUS_PUBLICATED_OUT,
                'status_prev'    => BBS::STATUS_PUBLICATED,
                'status_changed' => $this->db->now(),
                'is_publicated'  => 0,
            ), array(
                'is_publicated'  => 1,
                'status'         => BBS::STATUS_PUBLICATED,
                'publicated_to <= :now',
            ), array(
                'context' => __FUNCTION__,
                'bind' => array(':now'=>$this->db->now()),
            ));

            # проверим превышение лимита, услуга платного расширения лимитов
            $this->limitsPayedCron();


        # Выполняем пересчет счетчиков ОБ (items):
        # Типы категорий
        if (BBS::CATS_TYPES_EX) {
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES_TYPES . ' SET items = 0');
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES_TYPES . ' T,
                     (SELECT I.cat_type as id, COUNT(I.id) as items
                        FROM ' . TABLE_BBS_ITEMS . ' I
                            LEFT JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id
                        WHERE I.status = ' . BBS::STATUS_PUBLICATED . ' AND (U.user_id IS NULL OR U.blocked = 0)
                          AND I.cat_type != 0
                     GROUP BY I.cat_type) as X
                SET T.items = X.items
                WHERE T.id = X.id
            '
            );
        }
        # Актуализируем счетчик "на модерации"
        $this->controller->moderationCounterUpdate();
    }

    /**
     * Пересчет счетчиков количества объявлений в виртуальных категориях
     */
    public function itemsCountersCalculateVirtual()
    {
        $this->db->exec('DELETE IC
          FROM '.TABLE_BBS_ITEMS_COUNTERS.' AS IC, '.TABLE_BBS_CATEGORIES.' AS C
          WHERE C.id = IC.cat_id
            AND C.virtual_ptr IS NOT NULL');

        $this->db->exec('INSERT INTO ' . TABLE_BBS_ITEMS_COUNTERS . ' (cat_id, region_id, delivery, items) 
	      SELECT C.id, IC.region_id, IC.delivery, IC.items 
	      FROM '.TABLE_BBS_ITEMS_COUNTERS.' IC 
	      INNER JOIN '.TABLE_BBS_CATEGORIES.' C ON C.virtual_ptr = IC.cat_id 
	        ON DUPLICATE KEY UPDATE items = IC.items'
        );
    }

    /**
     * Полный пересчет счетчиков количества объявлений в категориях по регионам
     */
    public function itemsCountersCalculate()
    {
        $cats = array();
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $cats[] = 'cat_id'.$i;
        }
        $regs = array('reg1_country', 'reg2_region', 'reg3_city');

        $filter = array(
            'status' => BBS::STATUS_PUBLICATED,
        );
        if (BBS::premoderation()) {
            $filter[] = 'moderated > 0';
        }
        $filter = $this->prepareFilter($filter);

        $first = true;
        $insert = function(& $values) use(& $first){
            if(empty($values)) return;
            if($first){
                $this->db->exec('DELETE FROM '.TABLE_BBS_ITEMS_COUNTERS);
                $first = false;
            }
            $this->db->exec('
                INSERT INTO '.TABLE_BBS_ITEMS_COUNTERS.' (cat_id, region_id, delivery, items) 
                VALUES '.join(',', $values).'
                ON DUPLICATE KEY UPDATE items = items + VALUES(items);');
            $values = array();
        };

        $values = array();
        $total = 0;
        $this->db->select_iterator('
            SELECT '.join(',',$cats).', '.join(',', $regs).', regions_delivery, COUNT(*) AS cnt
            FROM '.TABLE_BBS_ITEMS.
            $filter['where'].'
            GROUP BY cat_id, reg3_city, regions_delivery', $filter['bind'],
            function($row) use($cats, $regs, & $values, & $total, & $insert){
                $total += $row['cnt'];
                foreach($cats as $c){
                    if(empty($row[$c])) continue;
                    $values[] = '('.$row[$c].', 0, 0, '.$row['cnt'].')';
                }
                if ($row['regions_delivery']) {
                    if ($row['reg1_country']) {
                        $values[] = '(0, '.$row['reg1_country'].', 0, '.$row['cnt'].')';
                        foreach ($cats as $c) {
                            if (empty($row[$c])) continue;
                            $values[] = '('.$row[$c].', '.$row['reg1_country'].', 1, '.$row['cnt'].')';
                        }
                    }
                } else {
                    foreach($regs as $r){
                        if (empty($row[$r])) continue;
                        $values[] = '(0, '.$row[$r].', 0, '.$row['cnt'].')';
                        foreach ($cats as $c) {
                            if (empty($row[$c])) continue;
                            $values[] = '('.$row[$c].','.$row[$r].', 0, '.$row['cnt'].')';
                        }
                    }
                }

                if (count($values) > 500) {
                    $insert($values);
                }
        });

        $values[] = '(0, 0, 0, '.$total.')';
        $insert($values);
    }

    /**
     * Обновление счетчика количества объявлений на основе данных о редактируемом объявлении
     * @param array $item данные объявления
     * @param integer $cnt на сколько изменить количество: +N, -N
     */
    protected function itemsCountersUpdate($item, $cnt)
    {
        if (empty($cnt)) return;

        $cats = array();
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $cats[] = 'cat_id'.$i;
        }
        $regs = array('reg1_country', 'reg2_region', 'reg3_city');

        if ($cnt > 0) {
            # +N
            $values = array();

            foreach ($cats as $c) {
                if (empty($item[$c])) continue;
                $values[] = '('.$item[$c].', 0, 0, '.$cnt.')';
            }
            if ($item['regions_delivery']) {
                if ($item['reg1_country']) {
                    $values[] = '(0, '.$item['reg1_country'].', 0, '.$cnt.')';
                    foreach ($cats as $c) {
                        if (empty($item[$c])) continue;
                        $values[] = '('.$item[$c].', '.$item['reg1_country'].', 1, '.$cnt.')';
                    }
                }
            } else {
                foreach ($regs as $r) {
                    if (empty($item[$r])) continue;
                    $values[] = '(0, '.$item[$r].', 0, '.$cnt.')';
                    foreach ($cats as $c) {
                        if (empty($item[$c])) continue;
                        $values[] = '('.$item[$c].','.$item[$r].', 0, '.$cnt.')';
                    }
                }
            }
            $values[] = '(0, 0, 0, '.$cnt.')';
            $this->db->exec('
                INSERT INTO '.TABLE_BBS_ITEMS_COUNTERS.' (cat_id, region_id, delivery, items) 
                VALUES '.join(',', $values).'
                ON DUPLICATE KEY UPDATE items = items + VALUES(items);');
        } else {
            # -N
            $cnt = abs($cnt);
            $where = array();
            foreach ($cats as $c) {
                if (empty($item[$c])) continue;
                $where[] = '(cat_id = '.$item[$c].' AND region_id = 0 AND delivery = 0)';
            }
            if ($item['regions_delivery']) {
                if ($item['reg1_country']) {
                    $where[] = '(cat_id = 0 AND region_id = '.$item['reg1_country'].' AND delivery = 0 )';
                    foreach ($cats as $c) {
                        if (empty($item[$c])) continue;
                        $where[] = '(cat_id = '.$item[$c].' AND region_id = '.$item['reg1_country'].' AND delivery = 1 )';
                    }
                }
            } else {
                foreach ($regs as $r) {
                    if (empty($item[$r])) continue;
                    $where[] = '(cat_id = 0 AND region_id = '.$item[$r].' AND delivery = 0)';
                    foreach ($cats as $c) {
                        if (empty($item[$c])) continue;
                        $where[] = '(cat_id = '.$item[$c].' AND region_id = '.$item[$r].' AND delivery = 0)';
                    }
                }
            }

            $data = $this->db->select('SELECT cat_id, region_id, delivery, items FROM '.TABLE_BBS_ITEMS_COUNTERS.' WHERE '.join(' OR ', $where));
            if (empty($data)) return;
            $where[] = '(cat_id = 0 AND region_id = 0 AND delivery = 0)';
            $update = array('WHEN cat_id = 0 AND region_id = 0 AND delivery = 0 THEN items - '.$cnt);
            $delete = array();
            foreach ($data as $v) {
                if ($v['items'] > $cnt) {
                    $update[] = ' WHEN cat_id = '.$v['cat_id'].' AND region_id = '.$v['region_id'].' AND delivery = '.$v['delivery'].' THEN items - '.$cnt;
                } else {
                    $delete[] = '(cat_id = '.$v['cat_id'].' AND region_id = '.$v['region_id'].' AND delivery = '.$v['delivery'].')';
                }
            }
            if (!empty($delete)) {
                $this->db->exec('DELETE FROM '.TABLE_BBS_ITEMS_COUNTERS.' WHERE '.join(' OR ', $delete));
            }
            $this->db->exec('
                UPDATE '.TABLE_BBS_ITEMS_COUNTERS.' 
                SET items = CASE '.join(' ', $update).' ELSE items END 
                WHERE '.join(' OR ', $where));
        }
    }

    /**
     * Счетчики количества объявлений по фильтру
     * @param array $filter [cat_id, region_id, delivery]
     * @param bool|array $fields false - только количество
     * @param bool $oneArray
     * @return mixed
     */
    public function itemsCountByFilter(array $filter, $fields = false,  $oneArray = true, $ttl = 0)
    {
        $filter = $this->prepareFilter($filter);
        if (empty($fields)) {
            return (int)$this->db->one_data('SELECT items FROM ' . TABLE_BBS_ITEMS_COUNTERS . $filter['where'].' LIMIT 1', $filter['bind'], $ttl);
        } else {
            if ($oneArray) {
                return $this->db->one_array('SELECT ' . join(',', $fields) . ' FROM ' . TABLE_BBS_ITEMS_COUNTERS . $filter['where'].' LIMIT 1', $filter['bind'], $ttl);
            } else {
                return $this->db->select('SELECT ' . join(',', $fields) . ' FROM ' . TABLE_BBS_ITEMS_COUNTERS . $filter['where'], $filter['bind'], $ttl);
            }
        }
    }

    /**
     * Полное удаление удаленных пользователем объявлений через X дней после окончания публикации
     */
    public function itemsCronDelete()
    {

        $nDays = config::sysAdmin('bbs.delete.timeout', 0, TYPE_UINT);
        if (!$nDays) return;

        $data = array();
        $this->db->tag('bbs-items-cron-delete')->select_iterator('SELECT id
                FROM ' . TABLE_BBS_ITEMS . '
                WHERE is_publicated = 0 AND status = :status AND publicated_to < :date',
            array(
                ':status' => BBS::STATUS_DELETED,
                ':date'   => date('Y-m-d H:i:s', strtotime('- '.$nDays.' days')),
            ),
            function($row) use(& $data){
                $data[] = $row['id'];
                if (count($data) > 100) {
                    $this->itemsDelete($data, false);
                    $data = array();
                }
            });

        if (!empty($data)) {
            $this->itemsDelete($data, false);
        }
    }

    /**
     * Помечаем снятые с публикации объявления как удаленные для неактивных аккаунтов пользователей.
     * @param integer $days кол дней
     */
    public function itemsCronDeleteInactiveUsers($days)
    {
        if ( ! $days) return;

        $date = strtotime('-'.$days.' days');
        $now  = $this->db->now();
        $ids  = array();
        $this->db->select_iterator('SELECT I.id
            FROM '.TABLE_BBS_ITEMS.' I, '.TABLE_USERS_STAT.' S
            WHERE I.user_id = S.user_id 
              AND I.is_publicated = 0
              AND I.status = :status
              AND S.last_login < :date',
            array(
                ':status' => BBS::STATUS_PUBLICATED_OUT,
                ':date'   => date('Y-m-d', $date),
            ),
        function($row) use (& $ids, $now) {
            $ids[] = $row['id'];
            if (count($ids) > 100) {
                $this->db->update(TABLE_BBS_ITEMS, array(
                    'status' => BBS::STATUS_DELETED,
                    'status_changed' => $now,
                    'is_publicated' => 0,
                    'is_moderating' => 0,
                ), array('id' => $ids));
                $ids = array();
            }
        });

        if ( ! empty($ids)) {
            $this->db->update(TABLE_BBS_ITEMS, array(
                'status' => BBS::STATUS_DELETED,
                'status_changed' => $now,
                'is_publicated' => 0,
                'is_moderating' => 0,
            ), array('id' => $ids));
        }
    }

    /**
     * Актуализация статистики объявлений (cron)
     * Рекомендуемый период: раз в сутки (в 00:00)
     */
    public function itemsCronViews()
    {
        # Обнуляем статистику просмотров за сегодня
        $this->itemsUpdateByFilter(array('views_today'=>0), array(), array('context'=>__FUNCTION__));

        # Удаляем историю просмотров старше X месяцев
        $this->db->exec('DELETE FROM ' . TABLE_BBS_ITEMS_VIEWS . '
            WHERE period < DATE_SUB(:now, INTERVAL 1 MONTH)', array(':now' => $this->db->now())
        );
    }

    /**
     * Данные об объявлениях для "Уведомления о завершении публикации объявлений" (cron)
     * @param array $days список дней за сколько необходимо отправить уведомление
     * @param integer $limit ограничение на выборку
     * @param string|boolean $date дата в формате Y-m-d
     * @return array
     */
    public function itemsCronUnpublicateSoon(array $days, $limit = 100, $date = false)
    {
        if (empty($days)) return array();
        if (empty($date)) return array();
        if (empty($limit) || $limit < 0) {
            $limit = 100;
        }

        $aFilter = array(
            'I.is_publicated = 1',
            'I.status = '.BBS::STATUS_PUBLICATED,
            'DATEDIFF(I.publicated_to,STR_TO_DATE(:date, :format)) IN ('.join(',', $days).')',
            'E.item_id IS NULL',
            'U.user_id = I.user_id',
            'US.user_id = I.user_id',
            'U.blocked = 0',
            'U.activated = 1',
            'U.enotify & '.Users::ENOTIFY_NEWS,
        );

        $aFilter = $this->prepareFilter($aFilter, '', array(
            ':date'   => $date,
            ':type'   => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
            ':format' => '%Y-%m-%d',
        ));

        $data = $this->db->select_key('SELECT I.id as item_id, I.title as item_title, I.link as item_link, U.email, U.name,
                        I.user_id, U.user_id_ex, US.last_login, U.lang,
                        DATEDIFF(I.publicated_to,STR_TO_DATE(:date, :format)) as days,
                        COUNT(I.id) AS cnt, GROUP_CONCAT(I.id) AS items
                    FROM ' . TABLE_BBS_ITEMS . ' as I
                        LEFT JOIN '.TABLE_BBS_ITEMS_ENOTIFY.' E ON E.item_id = I.id AND sended = :date AND message_type = :type,
                         '. TABLE_USERS .' as U,
                         '. TABLE_USERS_STAT .' as US
                    '. $aFilter['where'] .'
                    GROUP BY I.user_id
                    '. $this->db->prepareLimit(0, $limit), 'user_id', $aFilter['bind']);

        if (empty($data)) $data = array();
        return $data;
    }

    /**
     * Уведомления о завершении срока публикации:
     * Работа со списком объявлений отправленных уведомлений о завершении срока публикации
     * @param integer|array $itemsID ID объявления / нескольких объявлений
     * @param string $date дата в формаре "Y-m-d"
     * @return bool
     */
    public function itemsCronUnpublicateSended($itemsID, $date)
    {
        if ( ! is_array($itemsID)) {
            $itemsID = array($itemsID);
        }
        $data = $this->db->select_rows_column(TABLE_BBS_ITEMS_ENOTIFY, 'item_id', array(
            'message_type' => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
            'item_id'      => $itemsID,
            'sended'       => $date,
        ));
        if (empty($data)) {
            $data = false;
        }

        $insert = array();
        foreach ($itemsID as $v) {
            if ($data && in_array($v, $data)) {
                continue;
            }
            $insert[] = array(
                'message_type' => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
                'item_id'      => $v,
                'sended'       => $date,
            );
        }
        if ( ! empty($insert)) {
            $this->db->multiInsert(TABLE_BBS_ITEMS_ENOTIFY, $insert);
            return false;
        }

        return true;
    }

    /**
     * Очистка списка объявлений для которых выполнялась отправка уведомлений
     * о завершении срока публикации за указанную дату.
     * @param string $date дата в формате "Y-m-d"
     * @return mixed
     */
    public function itemsCronUnpublicateClearLast($date)
    {
        if (empty($date)) return false;
        return $this->db->delete(TABLE_BBS_ITEMS_ENOTIFY, array(
            'sended < :date',
            'message_type' => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
        ), array(
            ':date' => $date,
        ));
    }

    /**
     * Получаем список объявлений исходя из даты завершения публикации
     * @param integer $userID ID пользователя
     * @param string $day дата завершения публикации
     * @return array
     */
    public function itemsUserUnpublicateDay($userID, $day)
    {
        return $this->itemsDataByFilter(array(
            'user_id' => $userID,
            'shop_id' => array('>=', 0),
            'is_publicated' => 1,
            'status' => BBS::STATUS_PUBLICATED,
            ':from' => array('publicated_to >= :from', ':from' => date('Y-m-d 00:00:00', $day)),
            ':to' => array('publicated_to <= :to', ':to' => date('Y-m-d 23:59:59', $day)),
        ), array('id','shop_id'), array(
            'groupKey' => false,
        ));
    }

    /**
     * Автоматическое поднятие ОБ, исходя из настроек услуги
     * @param callable $callback функция для обработки строк
     */
    public function itemsCronUpAutoData(callable $callback)
    {
        $this->itemsDataByFilter(array(
            'is_publicated' => 1,
            'status'        => BBS::STATUS_PUBLICATED,
            'svc_upauto_on' => 1,
            'svc_upauto_next' => array('<', $this->db->now()),
        ), array(
            'id','user_id','cat_id','cat_id1','city_id','svc_upauto_sett','svc_up_date','svc_up_free',
        ), array(
            'iterator' => $callback,
            'context' => 'items-cron-up-auto-data',
        ));
    }

    /**
     * Данные об объявлениях для "Уведомления о возможности бесплатного поднятия объявлений" (cron)
     * @param integer $days кол-во дней через которое поднятие становится вновь доступным
     * @param integer $limit ограничение на выборку
     * @return array
     */
    public function itemsCronUpFreeEnable($days, $limit = 100)
    {
        if (empty($limit) || $limit < 0) {
            $limit = 100;
        }

        $aFilter = array(
            'I.is_publicated = 1',
            'I.status = '.BBS::STATUS_PUBLICATED,
            'I.svc_up_free = :date',
            'E.item_id IS NULL',
            'U.user_id = I.user_id',
            'US.user_id = I.user_id',
            'U.blocked = 0',
            'U.activated = 1',
            'U.enotify & '.Users::ENOTIFY_NEWS,
        );

        $aFilter = $this->prepareFilter($aFilter, '', array(
            ':type' => self::ITEMS_ENOTIFY_UP_FREE_ENABLE,
            ':now'  => date('Y-m-d'),
            ':date' => date('Y-m-d', strtotime('-'.$days.' days')),
        ));

        $data = $this->db->select_key('SELECT I.id as item_id, I.title as item_title, I.link as item_link, U.email, U.name,
                        I.user_id, U.user_id_ex, US.last_login, U.lang,
                        COUNT(I.id) AS cnt, GROUP_CONCAT(I.id) AS items
                    FROM ' . TABLE_BBS_ITEMS . ' AS I
                        LEFT JOIN '.TABLE_BBS_ITEMS_ENOTIFY.' E ON E.item_id = I.id AND E.sended = :now AND E.message_type = :type,
                         '. TABLE_USERS .' AS U,
                         '. TABLE_USERS_STAT .' AS US
                    '. $aFilter['where'] .'
                    GROUP BY I.user_id
                    '. $this->db->prepareLimit(0, $limit), 'user_id', $aFilter['bind']);

        if (empty($data)) $data = array();
        return $data;
    }

    /**
     * Уведомления о возможности бесплатного поднятия объявлений:
     * Работа со списком объявлений отправленных уведомлений о возможности бесплатного поднятия
     * @param integer|array $itemsID ID объявления / нескольких объявлений
     * @param string $date дата в формаре "Y-m-d"
     * @return bool
     */
    public function itemsCronUpFreeEnableSended($itemsID, $date)
    {
        if ( ! is_array($itemsID)) {
            $itemsID = array($itemsID);
        }
        $data = $this->db->select_rows_column(TABLE_BBS_ITEMS_ENOTIFY, 'item_id', array(
            'message_type' => self::ITEMS_ENOTIFY_UP_FREE_ENABLE,
            'item_id'      => $itemsID,
            'sended'       => $date,
        ));
        if (empty($data)) {
            $data = false;
        }

        $insert = array();
        foreach ($itemsID as $v) {
            if ($data && in_array($v, $data)) {
                continue;
            }
            $insert[] = array(
                'message_type' => self::ITEMS_ENOTIFY_UP_FREE_ENABLE,
                'item_id'      => $v,
                'sended'       => $date,
            );
        }
        if ( ! empty($insert)) {
            $this->db->multiInsert(TABLE_BBS_ITEMS_ENOTIFY, $insert);
            return false;
        }

        return true;
    }

    /**
     * Очистка списка объявлений для которых выполнялась отправка уведомлений
     * о возможности бесплатного поднятия за указанную дату.
     * @param string $date дата в формате "Y-m-d"
     * @return mixed
     */
    public function itemsCronUpFreeEnableClear($date)
    {
        if (empty($date)) return false;
        return $this->db->delete(TABLE_BBS_ITEMS_ENOTIFY, array(
            'sended < :date',
            'message_type' => self::ITEMS_ENOTIFY_UP_FREE_ENABLE,
        ), array(
            ':date' => $date,
        ));
    }

    /**
     * Данные для пересохранения объявлений
     * @param callable $callable
     */
    public function itemsCronResave(callable $callable)
    {
        $this->db->select_iterator('SELECT id, moderated, city_id, cat_id, video, video_embed
            FROM '.TABLE_BBS_ITEMS.' WHERE is_publicated = 0 AND status = '.BBS::STATUS_DELETED,
            array(), $callable);
    }

    /**
     * Бесплатное поднятие:
     * Получаем список ID объявлений пользователя, подходящих по дате, отправленной в рассылке
     * @param integer $userID ID пользователя
     * @param integer $days кол-во дней
     * @return mixed
     */
    public function itemsUserUpFreeEnable($userID, $days)
    {
        return $this->db->select_one_column('
            SELECT I.id
            FROM ' . TABLE_BBS_ITEMS . ' I
            WHERE I.user_id = :user
              AND I.shop_id >= 0
              AND I.is_publicated = 1
              AND I.status = :publicated AND I.svc_up_free <= :date
            ', array(
            ':user' => $userID,
            ':publicated' => BBS::STATUS_PUBLICATED,
            ':date' => date('Y-m-d', strtotime('-'.$days.' days')),
        ));
    }

    /**
     * Получение объявлений для формирования файла Sitemap.xml (cron)
     * @param array $aFilter фильтр
     * @param string $sPriority приоритетность url
     * @return callable callback-генератор строк вида array [['l'=>'url страницы','m'=>'дата последних изменений'],...]
     */
    public function itemsSitemapXmlData(array $aFilter = array(), $sPriority = '')
    {
        $aFilter['is_publicated'] = 1;
        $aFilter['status'] = BBS::STATUS_PUBLICATED;

        return function($count = false, callable $callback = null) use ($aFilter, $sPriority) {
            if ($count) {
                return $this->itemsCount($aFilter);
            } else {
                $aFilter = $this->prepareFilter($aFilter, '', array(
                    ':format' => '%Y-%m-%d',
                ));
                $this->db->tag('bbs-items-sitemap-xml-data', array('filter'=>&$aFilter))->select_iterator('
                    SELECT link as l, DATE_FORMAT(modified, :format) as m
                    FROM ' . TABLE_BBS_ITEMS . '
                    '. $aFilter['where'] .'
                    ORDER BY publicated_order DESC',
                $aFilter['bind'],
                function(&$item) use (&$callback, $sPriority) {
                    $item['l'] = BBS::urlDynamic($item['l']);
                    if ( ! empty($sPriority)) {
                        $item['p'] = $sPriority;
                    }
                    $callback($item);
                });
            }
            return false;
        };
    }

    /**
     * Получение текущей позиции опубликованного объявления в категории
     * @param integer $nItemID ID объявления
     * @param integer $nCategoryID ID основной категории
     * @param integer $nLimit ограничение поиска в списке
     * @return integer текущая позиция объявления
     */
    public function itemPositionInCategory($nItemID, $nCategoryID, $nLimit = 15)
    {
        if (empty($nLimit) || $nLimit < 0) {
            $nLimit = 30;
        }

        $nPosition = 0;
        do {
            # получаем список первых объявлений в категории
            $itemsID = $this->itemsSearch(array(
                'is_publicated' => 1,
                'status'        => BBS::STATUS_PUBLICATED,
                ':cat-filter'   => $nCategoryID,
            ), array(
                'orderBy' => 'publicated_order DESC',
                'limit'   => $nLimit,
                'context' => 'item-category-position',
            ));
            if (empty($itemsID)) {
                break; # нет среди первых
            }
            # ищем $nItemID среди найденных
            $i = 1;
            foreach ($itemsID as $id) {
                if ($id == $nItemID) {
                    $nPosition = $i;
                    break;
                }
                $i++;
            }
        } while (false);

        return $nPosition;
    }

    /**
     * Конвертирование цен объявлений в валюту по-умолчанию
     */
    public function itemsDefaultCurrency()
    {
        $defaultID = Site::currencyDefault('id');

        $this->itemsUpdateByFilter(array(
            'price = price_search',
            'price_curr' => $defaultID,
        ), array(
            'price_curr != :default',
        ), array(
            'context'=>__FUNCTION__,
            'bind'=>array(':default'=>$defaultID)
        ));
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL объявлений
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function itemsGeoUrlTypeChanged($prevType, $nextType)
    {
        if ($prevType == $nextType) {
            return;
        }

        $aData = $this->db->select('SELECT
                RR.keyword as region, RR.id as region_id,
                RC.keyword as city, RC.id as city_id
            FROM ' . TABLE_BBS_ITEMS . ' I
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON I.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON I.reg3_city = RC.id
            WHERE I.reg3_city > 0 AND I.reg2_region > 0
            GROUP BY I.reg3_city
            ORDER BY I.reg3_city
        '
        );

        $coveringType = Geo::coveringType();

        if ($prevType == Geo::URL_SUBDOMAIN) {
            foreach ($aData as &$v) {
                switch ($nextType) {
                    case Geo::URL_SUBDIR:
                    {
                        $to = '//{sitehost}/' . $v['city'] . '/';
                    }
                    break;
                    case Geo::URL_NONE:
                    {
                        if ($coveringType == Geo::COVERING_CITY) {
                            continue 2;
                        }
                        $to = '//{sitehost}/';
                    }
                    break;
                }
                switch ($coveringType) {
                    case Geo::COVERING_COUNTRIES:
                    case Geo::COVERING_COUNTRY:
                    case Geo::COVERING_REGION:
                    case Geo::COVERING_CITIES:
                    {
                        $from = '//' . $v['city'] . '.{sitehost}/';
                    }
                    break;
                    case Geo::COVERING_CITY:
                    {
                        $from = '//{sitehost}/';
                    }
                    break;
                }
                $this->db->update(TABLE_BBS_ITEMS,
                    array('link = REPLACE(link, :from, :to)'),
                    array('reg3_city = :city AND reg2_region = :region'),
                    array(
                        ':from'   => $from,
                        ':to'     => $to,
                        ':city'   => $v['city_id'],
                        ':region' => $v['region_id'],
                    )
                );
            }
            unset($v);
        } else {
            if ($prevType == Geo::URL_SUBDIR) {
                foreach ($aData as &$v) {
                    switch ($nextType) {
                        case Geo::URL_SUBDOMAIN:
                        {
                            switch ($coveringType) {
                            case Geo::COVERING_COUNTRIES:
                            case Geo::COVERING_COUNTRY:
                            case Geo::COVERING_REGION:
                            case Geo::COVERING_CITIES:
                            {
                                $to = '//' . $v['city'] . '.{sitehost}/';
                            }
                            break;
                            case Geo::COVERING_CITY:
                            {
                                $to = '//{sitehost}/';
                            }
                            break;
                            }
                        }
                        break;
                        case Geo::URL_NONE:
                        {
                            if ($coveringType == Geo::COVERING_CITY) {
                                continue 2;
                            }
                            $to = '//{sitehost}/';
                        }
                        break;
                    }
                    switch ($coveringType) {
                    case Geo::COVERING_COUNTRIES:
                    case Geo::COVERING_COUNTRY:
                    case Geo::COVERING_REGION:
                    case Geo::COVERING_CITIES:
                    {
                        $from = '//{sitehost}/' . $v['city'] . '/';
                    }
                    break;
                    case Geo::COVERING_CITY:
                    {
                        $from = '//{sitehost}/';
                    }
                    break;
                    }
                    $this->db->update(TABLE_BBS_ITEMS,
                        array('link = REPLACE(link, :from, :to)'),
                        array('reg3_city = :city AND reg2_region = :region'),
                        array(
                            ':from'   => $from,
                            ':to'     => $to,
                            ':city'   => $v['city_id'],
                            ':region' => $v['region_id'],
                        )
                    );
                }
                unset($v);
            } else {
                if ($prevType == Geo::URL_NONE && $coveringType != Geo::COVERING_CITY) {
                    foreach ($aData as &$v) {
                        switch ($nextType) {
                            case Geo::URL_SUBDOMAIN:
                            {
                                $to = '//' . $v['city'] . '.{sitehost}/';
                            }
                            break;
                            case Geo::URL_SUBDIR:
                            {
                                $to = '//{sitehost}/' . $v['city'] . '/';
                            }
                            break;
                        }
                        $this->db->update(TABLE_BBS_ITEMS,
                            array('link = REPLACE(link, :from, :to)'),
                            array('reg3_city = :city AND reg2_region = :region'),
                            array(
                                ':from'   => '//{sitehost}/',
                                ':to'     => $to,
                                ':city'   => $v['city_id'],
                                ':region' => $v['region_id'],
                            )
                        );
                    }
                    unset($v);
                }
            }
        }
    }

    /**
     * Перестраиваем URL всех объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsLinksRebuild()
    {
        $total = 0;

        $this->db->select_iterator('SELECT I.id, I.keyword, C.keyword as cat_keyword, C.landing_url,
                RR.keyword as region, RR.id as region_id,
                RC.keyword as city, RC.id as city_id
            FROM ' . TABLE_BBS_ITEMS . ' I
                 INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON I.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON I.reg3_city = RC.id
            ORDER BY I.id
        ', array(), function($v) use (&$total) {
            $link = BBS::url('items.search', array(
                    'keyword' => $v['cat_keyword'],
                    'landing_url' => $v['landing_url'],
                    'region'  => $v['region'],
                    'city'    => $v['city'],
                    'item'    => array('id'=>$v['id'], 'keyword'=>$v['keyword'], 'event'=>'links-rebuild'),
                ), true
            );

            $res = $this->db->update(TABLE_BBS_ITEMS, array('link' => $link), array('id' => $v['id']));
            if (!empty($res)) {
                $total++;
            }
        });

        return $total;
    }

    /**
     * Подсчет кол-ва объявлений по фильтру
     * @param array $filter
     * @param array $options
     * @return integer
     */
    public function itemsCount(array $filter = array(), array $options = array())
    {
        return $this->db->select_rows_count(TABLE_BBS_ITEMS, $filter, $options);
    }

    /**
     * Проверка структуры категорий. Объявления могут быть в категории, не содержащей подкатегорий.
     * Если в категории есть и подкатегории и объявления, то объявленея будут перенесены в первую дочернюю категорию не содержащую подкатегорий.
     * @return int
     */
    public function itemsCatsRebuild()
    {
        $items = $this->db->select_key('
            SELECT cat_id, count(*) AS cnt 
            FROM '.TABLE_BBS_ITEMS.' 
            GROUP BY cat_id', 'cat_id');

        $cats = $this->db->select_key('SELECT id, pid, numlevel, 0 AS cnt FROM '.TABLE_BBS_CATEGORIES, 'id');
        foreach($cats as $v){
            $pid = $v['pid'];
            if(empty($pid)) continue;
            if( ! isset($cats[$pid])){
                $this->errors->set('Incorrect cat pid '.$v['id']);
                return 0;
            }
            $cats[$pid]['cnt']++;
        };

        $children = function($cat) use(& $cats){
            $result = array();
            foreach($cats as $v){
                if($v['pid'] == $cat){
                    $result[ $v['id'] ] = $v;
                }
            }
            return $result;
        };

        $parents = function($cat) use(& $cats){
            $result = array();
            for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
                $result['cat_id'.$i] = 0;
            }
            do {
                $c = $cats[$cat];
                $result['cat_id' . $c['numlevel']] = $c['id'];
                $cat = $c['pid'];
            }while(isset($cats[$cat]));
            unset($result['cat_id0']);
            return $result;
        };

        foreach($items as $v){
            $cat = $v['cat_id'];
            if( ! isset($cats[ $cat ])){
                $this->errors->set('Incorrect cat id '.$v['cat_id']);
                return 0;
            }
            if(empty($cats[ $cat ]['cnt'])) continue;

            $c = $cats[ $cat ];
            do {
                $ch = $children($c['id']);
                if(empty($ch)) {
                    $this->errors->set('Empty children ' . $cat);
                    return 0;
                }

                foreach($ch as $c) {
                    if(empty($c['cnt'])) {
                        break;
                    }
                }
            }while( ! empty($c['cnt']));

            $p = $parents($c['id']);
            $p['cat_id'] = $c['id'];
            $this->db->update(TABLE_BBS_ITEMS, $p, array('cat_id' => $cat));
        }
        return 0;
    }

    /**
     * Счетчики объявлений по регионам
     * @param array $filter
     * @return array|mixed
     */
    public function regionsItemsCounters(array $filter, $ttl = 0)
    {
        if(empty($filter)) return array();

        $filter['delivery'] = 0;
        $filter[] = 'R.id = C.region_id';
        $filter = $this->prepareFilter($filter);

        $data = $this->db->select('
            SELECT R.id, R.keyword, R.title_'.LNG.' AS title, R.declension, C.items
            FROM '.TABLE_REGIONS.' R, 
                 '.TABLE_BBS_ITEMS_COUNTERS.' C
            '.$filter['where'].'
            ORDER BY R.num', $filter['bind'], $ttl);
        foreach($data as & $v){
            $v['declension'] = func::unserialize($v['declension']);
            if( ! empty($v['declension'][LNG])){
                $v['title'] = $v['declension'][LNG];
            }
        }
        return $data;
    }

    /**
     * Счетчики объявлений по категориям
     * @param array $filter фильтр [region_id]
     * @param integer $deliveryCountry включить с доставкой по стране
     * @return array данные о категориях с количеством объявлений в них
     */
    public function catsItemsCounters(array $filter, $deliveryCountry)
    {
        if (empty($filter)) return array();

        $region = 0;
        if (isset($filter['region_id'])) {
            $region = $filter['region_id'];
            unset($filter['region_id']);
        }

        $filter['enabled'] = 1;
        $filter[] = $this->db->langAnd(false, 'C', 'CL');
        $filter = $this->prepareFilter($filter, '', array(':region' => $region));

        $data = $this->db->select('
            SELECT C.id, C.pid, C.keyword, C.landing_url, CL.title, N.items
            FROM '.TABLE_BBS_CATEGORIES.' C 
                 LEFT JOIN '.TABLE_BBS_ITEMS_COUNTERS.' N ON C.id = N.cat_id AND N.delivery = 0 AND N.region_id = :region
                 , '.TABLE_BBS_CATEGORIES_LANG.' CL
            '.$filter['where'].'
            ORDER BY C.numleft', $filter['bind']);

        # добавим счетчики объявлений с доставкой по всей стране
        if ( ! empty($data) && $deliveryCountry) {
            $cats = array();
            foreach ($data as $v) {
                $cats[] = $v['id'];
            }
            $counters = $this->itemsCountByFilter(array('cat_id' => $cats, 'region_id' => $deliveryCountry, 'delivery' => 1),
                array('cat_id', 'items'), false);
            if ( ! empty($counters)){
                $counters = func::array_transparent($counters, 'cat_id', true);
                foreach ($data as &$v) {
                    if ( ! isset($counters[$v['id']])) continue;
                    $v['items'] += $counters[$v['id']]['items'];
                }
                unset($v);
            }
        }
        foreach ($data as $k => $v) {
            if ( ! empty($v['items'])) continue;
            unset($data[$k]);
        }
        return $data;
    }

    /**
     * Получаем счетчики объявлений в указанный категориях
     * @param array $catsID ID категорий
     * @param mixed $geo фильтр региона [id, country]
     * @param array $opts доп. параметры
     * @param integer $ttl кешировать (секунды)
     * @return array счетчики объявлений сгруппированные по категории [ID категории=>Кол-во объявлений, ...]
     */
    public function catsItemsCountersByID(array $catsID, $geo, array $opts = array(), $ttl = 60)
    {
        $counters = array();

        # посчитаем количество объявлений в категориях
        $temp = $this->itemsCountByFilter(array(
            'cat_id' => $catsID,
            'region_id' => $geo['id'],
            'delivery' => 0,
        ), array('cat_id', 'items'), false, $ttl);
        foreach ($temp as $v) {
            $counters[ $v['cat_id'] ] = $v['items'];
        }
        # доставки из регионов
        if ( ! empty($geo['country'])) {
            $temp = $this->itemsCountByFilter(array(
                'cat_id' => $catsID,
                'region_id' => $geo['country'],
                'delivery' => 1,
            ), array('cat_id', 'items'), false, $ttl);
            foreach ($temp as $v) {
                if (isset($counters[ $v['cat_id'] ])) {
                    $counters[ $v['cat_id'] ] += $v['items'];
                } else {
                    $counters[ $v['cat_id'] ] = $v['items'];
                }
            }
        }

        return $counters;
    }

    # ----------------------------------------------------------------
    # Импорт объявлений

    /**
     * Получение списка импорта (admin)
     * @param array $aFields выбираемые поля
     * @param array $aFilter фильтр списка
     * @param mixed $nLimit ограничение выборки, false - без ограничения
     * @param string $sqlOrder
     * @param bool $bCount только подсчёт кол-ва
     * @return mixed
     */
    public function importListing(array $aFields = array(), array $aFilter = array(), $nLimit = false, $sqlOrder = '', $bCount = false) //adm
    {
        if (empty($aFields)) {
            $aFields[] = 'I.*';
            $aFields[] = 'C.title as cat_title';
        }
        
        $aFilter = $this->prepareFilter($aFilter,'I');

        if ($bCount) {
            return (int)$this->db->one_data('SELECT COUNT(I.id)'
                        . 'FROM ' . TABLE_BBS_ITEMS_IMPORT . ' I ' . $aFilter['where']
                        , $aFilter['bind']);
        }

        if ($nLimit) {
            if (is_integer($nLimit)) $nLimit = $this->db->prepareLimit(0, $nLimit);
        } else $nLimit = '';

        return $this->db->select('SELECT ' . join(',', $aFields) . '
                            FROM ' . TABLE_BBS_ITEMS_IMPORT . ' I
                                LEFT JOIN ' . TABLE_BBS_CATEGORIES . ' C ON I.cat_id = C.id '
                            . $aFilter['where']
                            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
                            . $nLimit, $aFilter['bind']);
    }

    /**
     * Сохранение данных об импорте объявлений
     * @param integer $nImportID ID импорта или 0
     * @param array $aData
     */
    public function importSave($nImportID, array $aData)
    {
        if ($nImportID) {
            return $this->db->update(TABLE_BBS_ITEMS_IMPORT, $aData, array('id' => $nImportID));
        } else {
            return $this->db->insert(TABLE_BBS_ITEMS_IMPORT, $aData, 'id');
        }
    }

    /**
     * Удаление данных об импорте ОБ
     * @param integer $nImportID ID импорта
     * @return bool
     */
    public function importDelete($nImportID)
    {
        if ($nImportID) {
            return $this->db->delete(TABLE_BBS_ITEMS_IMPORT,  array('id' => $nImportID));
        }
        return false;
    }

    /**
     * Обновление данных об импорте объявлений по фильтру
     * @param array $aFilter фильтр
     * @param array $aData данные
     */
    public function importUpdateByFilter(array $aFilter = array(), array $aData = array())
    {
        if(empty($aFilter)) return;
        if(empty($aData)) return;
        $aFilter = $this->prepareFilter($aFilter);
        $aFilter['where'] = substr($aFilter['where'], 6);
        
        return $this->db->update(TABLE_BBS_ITEMS_IMPORT, $aData, $aFilter['where'], $aFilter['bind']);
    }

    /**
     * Получение данных об импорте объявлений по ID
     * @param integer $nImportID ID импорта или 0
     * @return mixed
     */
    public function importData($nImportID)
    {
        if (empty($nImportID)) return false;
        
        return $this->db->one_array('SELECT *
            FROM '.TABLE_BBS_ITEMS_IMPORT.'
            WHERE id = :id', array(':id'=>$nImportID));
    }

    # ----------------------------------------------------------------
    # Категории объявлений

    /**
     * Данные для формирования списка категорий
     * @param string $type тип списка категорий
     * @param string $device тип устройства
     * @param integer $parentID ID parent-категории
     * @param string $iconVariant размер иконки
     * @param boolean $ignoreVirtual игнорировать виртуальные категории
     * @return mixed
     */
    public function catsList($type, $device, $parentID, $iconVariant, $ignoreVirtual = false)
    {
        $filter = array(
            'C.pid != 0',
            'C.enabled = 1',
        );
        if ($ignoreVirtual) {
            $filter[] = 'C.virtual_ptr IS NULL';
        }
        $bind = array();
        $geo = Geo::filter();
        $bind[':region'] = ! empty($geo['id']) ? $geo['id'] : 0;
        switch ($type) {
        case 'index':
        {
            if ($device == bff::DEVICE_DESKTOP) {
                $filter[] = 'C.numlevel < 3';
            } else {
                if ($device == bff::DEVICE_PHONE) {
                    if ($parentID > 0) {
                        $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                    } else {
                        $filter[] = 'C.numlevel = 1';
                    }
                }
            }
        }
            break;
        case 'form':
        case 'search':
        {
            if ($device == bff::DEVICE_DESKTOP) {
                if ($parentID > 0) {
                    $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                } else {
                    $filter[] = 'C.numlevel = 1';
                }
            } else if ($device == bff::DEVICE_PHONE) {
                if ($parentID > 0) {
                    $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                } else {
                    $filter[] = 'C.numlevel = 1';
                }
            }
        }
            break;
        }

        $filter[] = $this->db->langAnd(false, 'C', 'CL');
        $filter = $this->prepareFilter($filter, '', $bind);

        $data = $this->db->select('
            SELECT C.id, C.pid, C.icon_' . $iconVariant . ' as i, CL.title as t, C.keyword as k, C.landing_url as lpu,
                 IFNULL(IC.items, 0) AS items, (C.numright-C.numleft)>1 as subs, C.numlevel as lvl
            FROM ' . TABLE_BBS_CATEGORIES . ' C 
                    LEFT JOIN '.TABLE_BBS_ITEMS_COUNTERS.' IC ON C.id = IC.cat_id AND IC.region_id = :region AND IC.delivery = 0
                    INNER JOIN '.TABLE_BBS_CATEGORIES.' CP ON C.pid = CP.id AND CP.enabled = 1
                    , ' . TABLE_BBS_CATEGORIES_LANG . ' CL
            ' . $filter['where'] . '
            ORDER BY C.numleft ASC', $filter['bind'], 60
        );
        # Строим счетчики объявлений в категориях
        $countryID = (isset($geo['country']) ? ($geo['country'] > 0 ? $geo['country'] : $geo['id']) : 0);
        if ($countryID > 0 && ! empty($data)) {
            $cats = array();
            foreach ($data as $v) {
                $cats[] = $v['id'];
            }
            $counters = $this->itemsCountByFilter(array('cat_id' => $cats, 'region_id' => $countryID, 'delivery' => 1),
                array('cat_id', 'items'), false);
            if ( ! empty($counters)) {
                $counters = func::array_transparent($counters, 'cat_id', true);
                foreach ($data as &$v) {
                    if ( ! isset($counters[ $v['id'] ])) continue;
                    $v['items'] += $counters[ $v['id'] ]['items'];
                } unset($v);
            }
        }
        return $data;
    }

    public function catsListSitemap($iconVariant)
    {
        return $this->db->select('SELECT C.id, C.pid, C.icon_' . $iconVariant . ' as icon, CL.title, C.keyword, C.landing_url, IFNULL(IC.items, 0) AS items
                            FROM ' . TABLE_BBS_CATEGORIES . ' C
                                LEFT JOIN '.TABLE_BBS_ITEMS_COUNTERS.' IC ON C.id = IC.cat_id AND IC.region_id = 0 AND IC.delivery = 0
                                INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                            WHERE C.enabled = 1 AND C.pid != 0 AND C.numlevel <= 2
                              AND ' . $this->db->langAnd(false, 'C', 'CL') . '
                            ORDER BY C.numleft ASC', NULL, 60
        );
    }

    public function catsListing(array $aFilter) //adm
    {
        $aBind = array();
        $aFilter = $this->prepareFilter($aFilter, 'C', $aBind);
        return $this->db->tag('bbs-cats-listing-data', array('filter'=>&$aFilter))->select('SELECT C.id, C.pid, C.enabled, C.addr, C.price, C.numlevel,
                                IF(C.numright-C.numleft>1,1,0) as node, C.title, IFNULL(IC.items, 0) AS items, C.numleft,
                                C.virtual_ptr, VC.title as virtual_name
                            FROM ' . TABLE_BBS_CATEGORIES . ' C 
                                LEFT JOIN ' . TABLE_BBS_ITEMS_COUNTERS . ' IC ON C.id = IC.cat_id AND IC.region_id = 0
                                LEFT JOIN ' . TABLE_BBS_CATEGORIES . ' VC ON C.virtual_ptr = VC.id
                            ' . $aFilter['where'] . '
                            ORDER BY C.numleft ASC', $aFilter['bind']
        );
    }

    /**
     * Отвязываем объявления от виртуальной категории
     * @param integer $nCategoryID ID виртуальной категории
     * @return bool
     */
    public function catVirtualDropItemsLink($nCategoryID)
    {
        return $this->db->update(TABLE_BBS_ITEMS, ['cat_id_virtual' => null], ['cat_id_virtual' => $nCategoryID]);
    }

    /**
     * Получаем ID/Keyword реальной категории
     * @param integer|string $id ID/Keyword виртуальной категории (или реальной)
     * @param boolean $searchByKeyword выполнять поиск по полю 'keyword'
     * @return string
     */
    public function catToReal($id, $searchByKeyword = false)
    {
        $field = ($searchByKeyword ? 'keyword' : 'id');
        $realID = $this->db->one_data('SELECT C.'.$field.' 
                FROM ' . TABLE_BBS_CATEGORIES . ' VC
                    INNER JOIN  ' . TABLE_BBS_CATEGORIES . ' C ON C.id = VC.virtual_ptr
                WHERE VC.'.$field.' = :'.$field, [':'.$field => $id]
        );
        return ( ! empty($realID) ? $realID : $id);
    }

    /**
     * Фильтр поиска по категории по полю cat_path
     * @param integer|array $categoryID ID категории или нескольких категорий (вложенность от верхнего уровня)
     * @param boolean $force строить фильтр и в случае если категория не указана
     * @return array|bool
     */
    public function catPathFilter($categoryID, $force = false)
    {
        if (is_array($categoryID)) {
            if (!empty($categoryID)) {
                return array('cat_path LIKE :catQuery', ':catQuery' => '-' . join('-', $categoryID) . '-%');
            }
        } else if ($categoryID > 0) {
            $data = $this->catData($categoryID, array('id','pid','numlevel','numleft','numright'));
            if (!empty($data)) {
                if ($data['numlevel'] == 1) {
                    return array('cat_path LIKE :catQuery', ':catQuery' => '-' . $data['id'] . '-%');
                } else if ($data['numlevel'] == 2) {
                    return array('cat_path LIKE :catQuery', ':catQuery' => '-' . $data['pid'] . '-' . $data['id'] . '-%');
                } else if ($data['numlevel'] > 2) {
                    $categoryParents = $this->catParentsID($data, true);
                    return array('cat_path LIKE :catQuery', ':catQuery' => '-' . join('-', $categoryParents) . '-%');
                }
            }
        }
        if ($force) {
            return array('cat_path LIKE :catQueryAny', ':catQueryAny' => '-%');
        }
        return false;
    }

    public function catData($nCategoryID, $aFields = array(), $bEdit = false)
    {
        if (empty($nCategoryID)) return array();

        return $this->catDataByFilter(array('id' => $nCategoryID), $aFields, $bEdit);
    }

    public function catDataLang($categoryID, $fields = array(), $lang = array())
    {
        if ( ! $categoryID) return array();
        if (empty($fields)) return array();
        if (empty($lang)) {
            $lang = $this->locale->getLanguages();
        }
        if ( ! in_array('lang', $fields)) {
            $fields[] = 'lang';
        }
        return $this->db->select_key('SELECT '.join(',', $fields).' FROM '.TABLE_BBS_CATEGORIES_LANG.' 
            WHERE id = :id AND '.$this->db->prepareIN('lang', $lang, false, false, false), 'lang', array(':id' => $categoryID));
    }

    public function catDataByFilter($aFilter, $aFields = array(), $bEdit = false)
    {
        if(isset($aFilter['lang'])) {
            $lang = $aFilter['lang'];
            unset($aFilter['lang']);
        } else $lang = LNG;

        $aParams = array();
        $bind = array();
        if (empty($aFields) || $bEdit) $aFields = '*';
        if ($aFields == '*') {
            $aParams = array($aFields);
        } else {
            if (!is_array($aFields)) {
                $aFields = array($aFields);
            }
            foreach ($aFields as $v) {
                if (isset($this->langCategories[$v])) {
                    $v = 'CL.' . $v;
                } elseif ($v == 'subs') {
                    $v = '((C.numright-C.numleft)>1) as subs';
                } else {
                    $v = 'C.' . $v;
                }
                $aParams[] = $v;
            }
        }
        $counters = false;
        if($k = array_search('C.items', $aParams)){
            $counters = true;
            $geo = Geo::filter();
            $bind[':region'] = ! empty($geo['id']) ? $geo['id'] : 0;
            $aParams[$k] = 'IFNULL(IC.items, 0) AS items';
        }

        $aFilter[':lng'] = $this->db->langAnd(false, 'C', 'CL', $lang);
        $aFilter = $this->prepareFilter($aFilter, 'C', $bind);
        $aFilter['where'] = str_replace('C.lang = :lang','CL.lang = :lang AND C.id = CL.id',$aFilter['where']);
        
        $aData = $this->db->one_array('
            SELECT ' . join(',', $aParams) . '
            FROM ' . TABLE_BBS_CATEGORIES . ' C '.
                   ($counters ? ' LEFT JOIN '.TABLE_BBS_ITEMS_COUNTERS.' IC ON C.id = IC.cat_id AND IC.region_id = :region AND IC.delivery = 0' : '').'
                , ' . TABLE_BBS_CATEGORIES_LANG . ' CL
            ' . $aFilter['where'] . '
            LIMIT 1', $aFilter['bind'], ($bEdit ? 0 : 60)
        );
        if($counters && ! empty($geo['id']) && ! empty($aData['id'])){
            $aData['items'] += $this->itemsCountByFilter(array(
                'cat_id' => $aData['id'],
                'region_id' =>  $geo['country'] ? $geo['country'] : $geo['id'],
                'delivery' => 1));
        }
        if (isset($aData['price_sett'])) {
            $priceSett =& $aData['price_sett'];
            $priceSett = (!empty($priceSett) ? func::unserialize($priceSett) : array());
            if ($priceSett === false) $priceSett = array();
            if (!isset($priceSett['ranges'])) $priceSett['ranges'] = array();
            if (!isset($priceSett['ex'])) $priceSett['ex'] = BBS::PRICE_EX_PRICE;
        }

        if ($bEdit) {
            $aData['node'] = ($aData['numright'] - $aData['numleft']);
            if (!Request::isPOST()) {
                $this->db->langSelect($aData['id'], $aData, $this->langCategories, TABLE_BBS_CATEGORIES_LANG);
            }
        }

        return $aData;
    }

    /**
     * Получение списка категорий по фильтру
     * @param array $aFilter список фильтров
     * @param array $aFields список полей которые нужно получить
     * @return array|boolean
     */
    public function catsDataByFilter(array $aFilter, $aFields = array(), $ttl = 0)
    {
        if(isset($aFilter['lang'])) {
            $lang = $aFilter['lang'];
            unset($aFilter['lang']);
        } else $lang = LNG;

        $aParams = array();
        if (empty($aFields)) $aFields = '*';
        if ($aFields == '*') {
            $aParams = array($aFields);
        } else {
            if (!is_array($aFields)) {
                $aFields = array($aFields);
            }
            foreach ($aFields as $v) {
                if (isset($this->langCategories[$v])) {
                    $v = 'CL.' . $v;
                } elseif ($v == 'subs') {
                    $v = '((C.numright-C.numleft)>1) as subs';
                } else {
                    $v = 'C.' . $v;
                }
                $aParams[] = $v;
            }
        }

        $aFilter[':lng'] = $this->db->langAnd(false, 'C', 'CL', $lang);
        $aFilter = $this->prepareFilter($aFilter, 'C');
        
        $aData = $this->db->select('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_CATEGORIES . ' C,
                            ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                       ' . $aFilter['where'] . ' ORDER BY C.numleft', $aFilter['bind'], $ttl
        );
        if ($aData)
        {
            foreach($aData as &$v)
            {
                if (isset($v['price_sett'])) {
                    $priceSett =& $v['price_sett'];
                    $priceSett = (!empty($priceSett) ? unserialize($priceSett) : array());
                    if ($priceSett === false) $priceSett = array();
                    if (!isset($priceSett['ranges'])) $priceSett['ranges'] = array();
                    if (!isset($priceSett['ex'])) $priceSett['ex'] = BBS::PRICE_EX_PRICE;
                }
            } unset($v);
        } else {
            $aData = array();
        }

        return $aData;
    }
    
    /**
     * Получаем данные о child-категориях всех уровней вложенности
     * @param int $numleft левая граница
     * @param int $numright правая граница
     * @param string $langKey язык записей
     * @param array $aFields требуемые поля child-категорий
     * @return array|mixed
     */
    public function catChildsTree($numleft, $numright, $langKey = LNG, $aFields = array())
    {
        if (empty($aFields)) $aFields[] = 'id';
        foreach ($aFields as $k => $v) {
            if ($v == 'id' || array_key_exists($v, $this->langCategories)) $aFields[$k] = 'CL.' . $v;
        }
        
        return $this->db->select_key('SELECT ' . join(',', $aFields) . '
                            FROM ' . TABLE_BBS_CATEGORIES . ' C
                                LEFT JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL USING (id)
                            WHERE C.numleft > :left AND C.numright < :right AND CL.lang = :lang
                            ORDER BY C.numleft ASC', 'id',
                            array(':left'=>$numleft,':right'=>$numright,':lang'=>$langKey)
        );
    }

    /**
     * Копирование настроек категории в подкатегории
     * @param integer $nCategoryID ID категории
     * @param array $aSelected отмеченные свойства
     * @return boolean
     */
    public function catDataCopyToSubs($nCategoryID, array $aSelected = array())
    {
        # настройки
        $aParams = bff::filter('bbs.admin.category.form.copy2subs.data', array(
            'seek' => array(
                'data' => array('seek'),
                'lang' => array(
                    'type_offer_form',
                    'type_offer_search',
                    'type_seek_form',
                    'type_seek_search',
                ),
            ),
            'price' => array(
                'data' => array('price','price_sett'),
            ),
            'photos' => array(
                'data' => array('photos'),
            ),
            'owner' => array(
                'data' => array('owner_business','owner_search'),
                'lang' => array(
                    'owner_private_form',
                    'owner_private_search',
                    'owner_business_form',
                    'owner_business_search',
                ),
            ),
            'addr' => array(
                'data' => array('addr'),
            ),
            'addr_metro' => array(
                'data' => array('addr_metro'),
            ),
            'regions_delivery' => array(
                'data' => array('regions_delivery'),
            ),
            'list_type' => array(
                'data' => array('list_type'),
            ),
        ));

        $aDataFields = array();
        $aLangFields = array();
        foreach ($aSelected as $key) {
            if (isset($aParams[$key])) {
                foreach ($aParams[$key]['data'] as $v) {
                    $aDataFields[] = $v;
                }
                if (isset($aParams[$key]['lang'])) {
                    foreach ($aParams[$key]['lang'] as $v) {
                        $aLangFields[] = $v;
                    }
                }
            }
        }

        # шаг 1:
        $aData = $this->db->one_array('SELECT numleft, numright, numlevel
                '.(!empty($aDataFields) ? ', '.join(',', $aDataFields) : '').'
                FROM ' . TABLE_BBS_CATEGORIES . '
                WHERE id = :id', array(':id' => $nCategoryID)
        );
        if (empty($aData)) {
            return false;
        }
        # нет подкатегорий
        if (($aData['numright'] - $aData['numleft']) == 1) {
            return true;
        }
        # получаем ID подкатегорий
        $aSubsID = $this->db->select_one_column('SELECT id FROM ' . TABLE_BBS_CATEGORIES . '
            WHERE numleft > :left AND numright < :right AND numlevel = :lvl', array(
                ':left'  => $aData['numleft'],
                ':right' => $aData['numright'],
                ':lvl'   => $aData['numlevel'] + 1,
            )
        );
        unset($aData['numleft'], $aData['numright'], $aData['numlevel']);
        if (!empty($aData)) {
            $this->db->update(TABLE_BBS_CATEGORIES, $aData, array('id' => $aSubsID));
        }

        # шаг2:
        $aLangs = $this->locale->getLanguages();
        if (!empty($aLangFields)) {
            foreach ($aLangs as $lang) {
                $aData2 = $this->db->one_array('SELECT ' . join(', ', $aLangFields) . '
                    FROM ' . TABLE_BBS_CATEGORIES_LANG . '
                    WHERE id = :id AND lang = :lang',
                    array(':id' => $nCategoryID, ':lang' => $lang)
                );

                $this->db->update(TABLE_BBS_CATEGORIES_LANG, $aData2,
                    array('id' => $aSubsID, 'lang' => $lang)
                );
            }
        }
    }

    /**
     * Сохранение данных категории
     * @param integer $nCategoryID ID категории
     * @param array $aData данные
     * @return bool|int
     */
    public function catSave($nCategoryID, $aData)
    {
        if ($nCategoryID) {
            # запрет именения parent'a
            if (isset($aData['pid'])) unset($aData['pid']);
            $aData['modified'] = $this->db->now();
            if (isset($aData['price_sett'])) $aData['price_sett'] = serialize($aData['price_sett']);
            $this->db->langUpdate($nCategoryID, $aData, $this->langCategories, TABLE_BBS_CATEGORIES_LANG);
            $aDataNonLang = array_diff_key($aData, $this->langCategories);
            if (isset($aData['title'][LNG])) $aDataNonLang['title'] = $aData['title'][LNG];

            return $this->db->update(TABLE_BBS_CATEGORIES, $aDataNonLang, array('id' => $nCategoryID));
        } else {
            $nCategoryID = $this->treeCategories->insertNode($aData['pid']);
            if (!$nCategoryID) return 0;
            unset($aData['pid']);
            $aData['created'] = $this->db->now();
            $this->catSave($nCategoryID, $aData);
            $this->catsStructureChanged(true);

            return $nCategoryID;
        }
    }

    public function saveItemRatingByUser($itemId, $userId, $value)
    {
        $this->db->exec('
            INSERT INTO '.TABLE_BBS_ITEMS_RATINGS.' (item_id, user_id, value)
            VALUES (:item_id, :user_id, :value)
            ON DUPLICATE KEY UPDATE value = :value
        ', ['item_id' => $itemId, ':user_id' => $userId, ':value' => $value]);
    }

    public function getAvarageItemRating($itemId)
    {
        $avarageRating = $this->db->select_one_column('
            SELECT SUM(value)/COUNT(value) 
            FROM '.TABLE_BBS_ITEMS_RATINGS.'
            WHERE item_id = :item_id
            GROUP BY item_id
        ', ['item_id' => $itemId]);

        return number_format((float) array_shift($avarageRating), 2);
    }

    public function getCurrentUserItemRating($nItemID, $nUserID)
    {
        $userItemRating = $this->db->select_one_column('
            SELECT value 
            FROM '.TABLE_BBS_ITEMS_RATINGS.'
            WHERE 
                item_id = :item_id AND
                user_id = :user_id
        ', ['item_id' => $nItemID, 'user_id' => $nUserID]);

        return (int) array_shift($userItemRating);
    }

    public function getAvarageAuthorRating($nUserId, $isShop)
    {
        $where = [
            'I.user_id = :user_id',
            $isShop ? 'I.shop_id > 0' : 'I.shop_id = 0'
        ];

        $avarageRating = $this->db->select_one_column('
            SELECT SUM(IR.value)/COUNT(IR.value) 
            FROM 
                '.TABLE_BBS_ITEMS_RATINGS.' IR
                JOIN '.TABLE_BBS_ITEMS.' I ON I.id = IR.item_id
            WHERE 
                '.join(' AND ' ,$where).'
            GROUP BY I.user_id
        ', ['user_id' => $nUserId]);

        return number_format((float) array_shift($avarageRating), 2);
    }

    public function getAuthorCategoriesAvarageRating($nUserId, $isShop)
    {
        $where = [
            'I.user_id = :user_id',
            $isShop ? 'I.shop_id > 0' : 'I.shop_id = 0',
        ];

        $ratings = $this->db->select("SELECT 
                           I.cat_id1, 
                           C.title,
                           SUM(IR.value)/COUNT(IR.value) as value
                        FROM ".TABLE_BBS_ITEMS." I 
                            JOIN ".TABLE_BBS_ITEMS_RATINGS." IR  ON I.id = IR.item_id
                            JOIN  ".TABLE_BBS_CATEGORIES." C ON I.`cat_id1` = C.id       
                        WHERE 
                            ".join(' AND ' ,$where)."
                        GROUP BY C.id",
            ['user_id' => $nUserId]
        );

        return $ratings;
    }

    /**
     * Смена parent-категории
     * @param integer $nCategoryID ID перемещаемой категории
     * @param integer $nNewParentID ID новой parent-категории
     * @return boolean
     */
    public function catChangeParent($nCategoryID, $nNewParentID)
    {
        # выполняем смену parent-категории + проверку на максимальный уровень вложенности
        $success = $this->treeCategories->changeParent($nCategoryID, $nNewParentID, BBS::CATS_MAXDEEP);
        if ($success !== true) {
            if ($success === -1) {
                $this->errors->set('Неудалось изменить основную категорию, максимальный уровень вложенности - '.BBS::CATS_MAXDEEP);
            }
            return false;
        }
        # помечаем дату последнего измнения структуры категорий
        $this->catsStructureChanged(true);
        # обновляем связи объявлений с категориями
        $where = array();
        $cats = array();
        for ($i = BBS::CATS_MAXDEEP; $i>0; $i--) {
            $where[] = 'I.cat_id'.$i.' = :cat';
            $cats['cat_id'.$i] = 0;
        }
        $prepareUpdate = function($item) use ($cats) {
            static $cache;
            $catID = $item['cat_id'];
            if (!isset($cache[$catID])) {
                $update = $cats;
                $update['cat_id'.$item['numlevel']] = $catID;
                if ($item['numlevel'] == 2) {
                    $update['cat_id1'] = $item['pid'];
                } else if ($item['numlevel'] > 2) {
                    $data = $this->db->select('SELECT id, numlevel
                        FROM ' . TABLE_BBS_CATEGORIES . '
                        WHERE numleft <= :left AND numright > :right
                            AND numlevel > 0
                        ORDER BY numleft', array(
                            ':left'  => $item['numleft'],
                            ':right' => $item['numright'],
                    ));
                    if (!empty($data)) {
                        foreach ($data as $v) {
                            $update['cat_id'.$v['numlevel']] = $v['id'];
                        }
                    }
                }
                $cache[$catID] = $update;
            }
            return $cache[$catID];
        };
        $this->db->select_iterator('SELECT I.id, I.cat_id,
                C.pid, C.numlevel, C.numleft, C.numright
            FROM '.TABLE_BBS_ITEMS.' I,
                 '.TABLE_BBS_CATEGORIES.' C
            WHERE ('.join(' OR ', $where).')
              AND I.cat_id = C.id
            ORDER BY I.id
        ', array(':cat'=>$nCategoryID), function($item) use ($prepareUpdate){
            $this->db->update(TABLE_BBS_ITEMS, $prepareUpdate($item), ['id'=>$item['id']]);
            $this->itemsIndexesUpdate([$item['id']], 'cat_path');
        });
        return true;
    }

    /**
     * Удаление категории (с проверкой наличия подкатегорий/объявлений)
     * @param integer $nCategoryID ID категории
     * @return bool|int
     */
    public function catDelete($nCategoryID)
    {
        if (!$nCategoryID) return false;

        # проверяем наличие подкатегорий
        $aData = $this->catData($nCategoryID, '*', true);
        if ($aData['node'] > 1) {
            $this->errors->set('Невозможно удалить категорию с подкатегориями');

            return false;
        }

        # проверяем наличие связанных с категорией ОБ
        $nItems = $this->itemsCount(array('cat_id'=>$nCategoryID));
        if (!empty($nItems)) {
            $this->errors->set('Невозможно удалить категорию с объявлениями');

            return false;
        }

        # удаляем
        $aDeleteID = $this->treeCategories->deleteNode($nCategoryID);
        if (empty($aDeleteID)) {
            $this->errors->set('Ошибка удаления категории');

            return false;
        }

        # помечаем дату последнего измнения структуры категорий
        $this->catsStructureChanged(true);

        # удаляем посадочную страницу
        if (!empty($aData['landing_id'])) {
            SEO::model()->landingpageDelete($aData['landing_id']);
        }

        # удаляем иконки
        $oIcon = BBS::categoryIcon($nCategoryID);
        foreach ($oIcon->getVariants() as $k => $v) {
            $oIcon->setVariant($k);
            $oIcon->delete(false, $aData[$k]);
        }

        return true;
    }

    public function catDeleteDev($categoryID)
    {
        # проверяем наличие связанных с категорией ОБ
        $cats = ' cat_id = :id ';
        for ($i = 1; $i <= BBS::CATS_MAXDEEP; $i++) {
            $cats .= ' OR cat_id'.$i.' = :id ';
        }
        $nItems = $this->itemsCount(array(
            ':cats' => array($cats, ':id'=>$categoryID),
        ));
        if (!empty($nItems)) {
            $this->errors->set('Невозможно удалить категорию с объявлениями');
            return false;
        }

        $data = $this->catData($categoryID);
        # удаляем иконку категории
        $oIcon = BBS::categoryIcon($categoryID);
        foreach ($oIcon->getVariants() as $k => $v) {
            $oIcon->setVariant($k);
            $oIcon->delete(false, $data[$k]);
        }
        $fields = array_keys($oIcon->getVariants());
        $fields[] = 'id';
        $fields[] = 'landing_id';

        # удаляем иконки вложенных категорий
        $children = $this->catChildsTree($data['numleft'], $data['numright'], LNG, $fields);
        if ( ! empty($children)) {
            foreach ($children as $v) {
                $oIcon->setRecordID($v['id']);
                foreach ($oIcon->getVariants() as $k => $vv) {
                    $oIcon->setVariant($k);
                    $oIcon->delete(false, $v[$k]);
                }
                # удаляем посадочные страницы
                if (!empty($v['landing_id'])) {
                    SEO::model()->landingpageDelete($v['landing_id']);
                }
            }
        }

        # удаляем
        $aDeleteID = $this->treeCategories->deleteNode($categoryID);
        if (empty($aDeleteID)) {
            $this->errors->set('Ошибка удаления категории');
            return false;
        } else {
            # удаляем посадочную страницу
            if (!empty($data['landing_id'])) {
                SEO::model()->landingpageDelete($data['landing_id']);
            }
            # помечаем дату последнего измнения структуры категорий
            $this->catsStructureChanged(true);
        }

        return true;
    }

    public function catDeleteAll()
    {
        # чистим таблицу категорий (+ зависимости по внешним ключам)
        $this->db->exec('DELETE FROM ' . TABLE_BBS_CATEGORIES . ' WHERE id > 0');
        $this->db->exec('ALTER TABLE ' . TABLE_BBS_CATEGORIES . ' AUTO_INCREMENT = 2');
        # чистим связанные посадочные страницы
        SEO::i();
        $this->db->exec('DELETE FROM ' . TABLE_LANDING_PAGES . ' WHERE joined > 0
            AND joined_module = :module', array(':module'=>'bbs-cats'));

        # создаем корневую директорию
        $nRootID = BBS::CATS_ROOTID;
        $sRootTitle = 'Корневой раздел';
        $aData = array(
            'id'       => $nRootID,
            'pid'      => 0,
            'numleft'  => 1,
            'numright' => 2,
            'numlevel' => 0,
            'title'    => $sRootTitle,
            'keyword'  => 'root',
            'enabled'  => 1,
            'created'  => $this->db->now(),
            'modified' => $this->db->now(),
        );
        $res = $this->db->insert(TABLE_BBS_CATEGORIES, $aData);
        if (!empty($res)) {
            $aDataLang = array('title' => array());
            foreach ($this->locale->getLanguages() as $lng) {
                $aDataLang['title'][$lng] = $sRootTitle;
            }
            $this->db->langInsert($nRootID, $aDataLang, $this->langCategories, TABLE_BBS_CATEGORIES_LANG);
        }

        return !empty($res);
    }

    public function catToggle($nCategoryID, $sField)
    {
        if (!$nCategoryID) return false;

        switch ($sField) {
        case 'addr_map':
        {
            return $this->toggleInt(TABLE_BBS_CATEGORIES, $nCategoryID, 'addr', 'id');
        }
            break;
        case 'enabled':
        {
            $res = $this->toggleInt(TABLE_BBS_CATEGORIES, $nCategoryID, 'enabled', 'id');
            if ($res) {
                $aCategoryData = $this->catData($nCategoryID, array('numleft', 'numright', 'enabled', 'landing_id'));
                if (!empty($aCategoryData)) {
                    $this->db->update(TABLE_BBS_CATEGORIES, array(
                            'enabled' => $aCategoryData['enabled'],
                        ), array(
                            'numleft > :left AND numright < :right'
                        ), array(
                            ':left'  => $aCategoryData['numleft'],
                            ':right' => $aCategoryData['numright'],
                        )
                    );
                    if (!empty($aCategoryData['landing_id'])) {
                        SEO::model()->landingpageToggle($aCategoryData['landing_id'], 'enabled');
                    }
                }
            }

            return $res;
        }
            break;
        }

        return false;
    }

    public function catsRotate()
    {
        $res = $this->treeCategories->rotateTablednd();
        # помечаем дату последнего измнения структуры категорий
        if ($res !== false) {
            $this->catsStructureChanged(true);
        }
        return $res;
    }

    public function catsExport($type, $lang = LNG)
    {
        $aData = array();
        if (empty($type) || $type == 'txt') {
            $aData = $this->db->select('SELECT C.id, C.numlevel, ((C.numright-C.numleft)>1) as subs, CL.title
                FROM ' . TABLE_BBS_CATEGORIES . ' C,
                     ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                WHERE C.id != :rootID AND C.id = CL.id AND CL.lang = :lang
                ORDER BY C.numleft
            ', array(':rootID' => BBS::CATS_ROOTID, ':lang' => $lang)
            );
        }
        if (empty($aData)) {
            $aData = array();
        }

        return $aData;
    }

    /**
     * Получаем данные о parent-категориях
     * @param int|array $mCategoryData ID категории или данные о ней [id,numleft,numright]
     * @param array $aFields требуемые поля parent-категорий
     * @param bool $bIncludingSelf включая категорию $mCategoryData
     * @param bool $bExludeRoot исключая данные о корневом элементе
     * @param string $lang язык
     * @return array|mixed
     */
    public function catParentsData($mCategoryData, array $aFields = array(
        'id',
        'title',
        'keyword'
    ), $bIncludingSelf = true, $bExludeRoot = true, $lang = LNG
    ) {
        if (empty($aFields)) $aFields[] = 'id';
        foreach ($aFields as $k => $v) {
            if ($v == 'id' || array_key_exists($v, $this->langCategories)) $aFields[$k] = 'CL.' . $v;
            if ($v == 'subs') { $aFields[$k] = '((C.numright-C.numleft)>1) as subs'; }
        }

        if (is_array($mCategoryData))
        {
            if (empty($mCategoryData)) return array();
            foreach (array('id','numleft','numright') as $k) {
                if (!isset($mCategoryData[$k])) return array();
            }
            $aParentsData = $this->db->select('SELECT ' . join(',', $aFields) . '
                FROM ' . TABLE_BBS_CATEGORIES . ' C,
                     ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                WHERE ((C.numleft <= ' . $mCategoryData['numleft'] . ' AND C.numright > ' . $mCategoryData['numright'] . ')' . ($bIncludingSelf ? ' OR C.id = ' . $mCategoryData['id'] : '') . ')
                    '.( $bExludeRoot ? ' AND C.id != ' . BBS::CATS_ROOTID : '' ).'
                ' . $this->db->langAnd(true, 'C', 'CL', $lang) . '
                ORDER BY C.numleft
            '
            );
        } else {
            if ($mCategoryData <= 0) return array();
            $aParentsID = $this->treeCategories->getNodeParentsID($mCategoryData, ($bExludeRoot ? ' AND id != ' . BBS::CATS_ROOTID : ''), $bIncludingSelf);
            if (empty($aParentsID)) return array();

            $aParentsData = $this->db->select('SELECT ' . join(',', $aFields) . '
                FROM ' . TABLE_BBS_CATEGORIES . ' C,
                     ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                WHERE C.id IN(' . join(',', $aParentsID) . ')
                ' . $this->db->langAnd(true, 'C', 'CL', $lang) . '
                ORDER BY C.numleft
            '
            );
        }

        return func::array_transparent($aParentsData, 'id', true);
    }

    /**
     * Получаем данные о parent-категориях
     * @param array|integer $mCategoryData ID категории или данные о текущей категории: id, pid, numlevel, numleft, numright, ...
     * @param bool $bIncludingSelf включать текущую в итоговых список
     * @param bool $bExludeRoot исключить корневую категорию
     * @return array array(lvl=>id, ...)
     */
    public function catParentsID($mCategoryData, $bIncludingSelf = true, $bExludeRoot = true)
    {
        if (!is_array($mCategoryData)) {
            $mCategoryData = $this->catDataByFilter(array('id' => $mCategoryData), array(
                    'id',
                    'pid',
                    'numlevel',
                    'numleft',
                    'numright'
                )
            );
            if (empty($mCategoryData)) return array();
        }

        $aParentsID = array();
        if (!$bExludeRoot) {
            $aParentsID[0] = 1;
        }
        if ($mCategoryData['numlevel'] == 1) {
            if ($bIncludingSelf)
                $aParentsID[1] = $mCategoryData['id'];
        } else if ($mCategoryData['numlevel'] == 2) {
            $aParentsID[1] = $mCategoryData['pid'];
            if ($bIncludingSelf)
                $aParentsID[2] = $mCategoryData['id'];
        } else {
            $aData = $this->db->select('SELECT id, numlevel FROM ' . TABLE_BBS_CATEGORIES . '
                                    WHERE numleft <= ' . $mCategoryData['numleft'] . ' AND numright > ' . $mCategoryData['numright'] .
                ($bExludeRoot ? ' AND numlevel > 0' : '') . '
                                    ORDER BY numleft'
            );
            $aParentsID = array();
            if (!empty($aData)) {
                foreach ($aData as $v) {
                    $aParentsID[$v['numlevel']] = $v['id'];
                }
            }
            if ($bIncludingSelf) {
                $aParentsID[] = $mCategoryData['id'];
            }
        }

        return $aParentsID;
    }

    /**
     * Формирование списка подкатегорий
     * @param integer $nCategoryID ID категории
     * @param mixed $mOptions формировать select-options (@see HTML::selectOptions) или FALSE
     * @return array|string
     */
    public function catSubcatsData($nCategoryID, $mOptions = false)
    {
        $aData = $this->db->select('SELECT C.id, CL.title
                    FROM ' . TABLE_BBS_CATEGORIES . ' C,
                         ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                    WHERE C.pid = :pid ' . $this->db->langAnd(true, 'C', 'CL') . '
                    ORDER BY C.numleft', array(':pid' => $nCategoryID)
        );
        if (empty($mOptions)) {
            return $aData;
        } else {
            return HTML::selectOptions($aData, $mOptions['sel'], $mOptions['empty'], 'id', 'title');
        }
    }
    
    /**
     * Обработка редактирования keyword'a в категории с подменой его в путях подкатегорий
     * @param integer $nCategoryID ID категории
     * @param string $sKeywordPrev предыдущий keyword
     * @return boolean
     */
    public function catSubcatsRebuildKeyword($nCategoryID, $sKeywordPrev)
    {
        $aCatData = $this->catData($nCategoryID, array('pid', 'keyword', 'numleft', 'numright', 'numlevel'));
        if (empty($aCatData)) return false;
        if ($aCatData['pid'] == BBS::CATS_ROOTID) {
            $sFrom = $sKeywordPrev . '/';
        } else {
            $aParentCatData = $this->catData($aCatData['pid'], array('keyword'));
            if (empty($aParentCatData)) return false;
            $sFrom = $aParentCatData['keyword'] . '/' . $sKeywordPrev . '/';
        }

        # перестраиваем полный путь подкатегорий
        $nCatsUpdated = $this->db->update(TABLE_BBS_CATEGORIES,
            array('keyword = REPLACE(keyword, :from, :to)'),
            'numleft > :left AND numright < :right',
            array(
                ':from'  => $sFrom,
                ':to'    => $aCatData['keyword'] . '/',
                ':left'  => $aCatData['numleft'],
                ':right' => $aCatData['numright']
            )
        );
        if (!empty($nCatsUpdated)) {
            # перестраиваем ссылки в объявлениях
            $sPrefix = '/search/';
            $this->db->update(TABLE_BBS_ITEMS,
                array('link = REPLACE(link, :from, :to)'),
                'cat_id' . $aCatData['numlevel'] . ' = :cat',
                array(
                    ':from' => $sPrefix . $sFrom,
                    ':to'   => $sPrefix . $aCatData['keyword'] . '/',
                    ':cat'  => $nCategoryID,
                )
            );

            return true;
        }

        return false;
    }

    /**
     * Автоматическое создание посадочных страниц без /search/ для категорий
     * @param $refresh boolean обновить полностью
     * @return integer кол-во затронутых категорий
     */
    public function catsLandingPagesAuto($refresh = false)
    {
        $updated = 0;
        if ($refresh) {
            SEO::i();
            $this->db->exec('DELETE FROM ' . TABLE_LANDING_PAGES . ' WHERE joined > 0
                AND joined_module = :module', array(':module'=>'bbs-cats'));
            $this->db->exec('DELETE FROM ' . TABLE_REDIRECTS . ' WHERE joined > 0
                AND joined_module = :module', array(':module'=>'bbs-cats'));
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES . ' SET landing_id = 0, landing_url = :empty
                WHERE landing_id > 0', array(':empty'=>''));
        }
        $filter = array(':root' => 'numlevel > 0');
        if (!$refresh) $filter['landing_id'] = 0;
        $filter = $this->prepareFilter($filter);
        $this->db->select_iterator('
            SELECT id, keyword FROM '.TABLE_BBS_CATEGORIES.$filter['where'],
            (!empty($filter['bind']) ? $filter['bind'] : array()),
            function ($cat) use (&$updated) {
                $_POST['landing_url'] = '/'.$cat['keyword'].'/';
                $originalURL = BBS::url('items.search', array('keyword'=>$cat['keyword'], 'region'=>false), true);
                $originalURL = str_replace('//{sitehost}', '', $originalURL);
                # Посадочная страница
                $landingData = SEO::i()->joinedLandingpage($this->controller, 'search-category', $originalURL, array(
                    'joined-id' => $cat['id'],
                    'joined-module' => 'bbs-cats',
                ));
                if (!empty($landingData['id'])) {
                    $this->catSave($cat['id'], array(
                        'landing_id' => $landingData['id'],
                        'landing_url' => $landingData['url'],
                    ));
                    # Редирект
                    SEO::model()->redirectSave(0, array(
                        'from_uri' => $originalURL,
                        'to_uri' => $landingData['url'],
                        'status' => 301,
                        'is_relative' => 1,
                        'add_extra' => 1,
                        'add_query' => 1,
                        'enabled' => 1,
                        'joined' => $cat['id'],
                        'joined_module' => 'bbs-cats',
                    ));
                    $updated++;
                }
            }
        );
        return $updated;
    }

    /**
     * Является ли категория основной
     * @param integer $nCategoryID ID категории
     * @param integer $nParentID ID parent-категории (для избежания запроса к БД) или false
     * @return boolean true - основная, false - подкатегория
     */
    public function catIsMain($nCategoryID, $nParentID = false)
    {
        if (!empty($nParentID)) {
            return ($nParentID == BBS::CATS_ROOTID);
        } else {
            $nNumlevel = $this->treeCategories->getNodeNumlevel($nCategoryID);

            return ($nNumlevel == 1);
        }
    }

    /**
     * Формирование выпадающего списка категорий
     * @param string $sType тип требуемого списка
     * @param int $nSelectedID ID выбранной категории
     * @param string|bool $mEmptyOpt параметры значения по-умолчанию
     * @param array $aExtra доп. настройки
     * @return string select::options
     */
    public function catsOptions($sType, $nSelectedID = 0, $mEmptyOpt = false, array $aExtra = array())
    {
        $sqlWhere = array($this->db->langAnd(false, 'C', 'CL'));
        $aSelectFields = ['C.id', 'C.pid', 'CL.title', 'C.numlevel', 'C.numleft',
            'C.numright', '0 as disabled'];
        $bCountItems = false;
        switch ($sType) {
            case 'adm-items-listing':
            {
                $sqlWhere[] = '(C.numlevel = 1' . ($nSelectedID > 0 ? ' OR C.id = ' . $nSelectedID : '') . ')';
            }
            break;
            case 'adm-shops-listing':
            {
                $sqlWhere[] = '(C.numlevel = 1 ' . ($nSelectedID > 0 ? ' OR C.id = ' . $nSelectedID : '') . ')';
            }
            break;
            case 'adm-category-form-add':
            {
                $sqlWhere[] = 'C.numlevel < ' . BBS::CATS_MAXDEEP;
                $bCountItems = true;
            }
            break;
            case 'adm-category-form-edit':
            {
                $sqlWhere[] = '( ! (C.numleft > ' . $aExtra['numleft'] . ' AND C.numright < ' . $aExtra['numright'] . ')
                                    AND C.id != ' . $aExtra['id'] . ')';
            }
            break;
            case 'adm-svc-prices-ex':
            {
                $sqlWhere[] = 'C.numlevel IN(1,2)';
            }
            break;
            case 'adm-category-add-virtual':
            {
                $sqlWhere[] = 'C.numlevel <= ' . BBS::CATS_MAXDEEP;
                $sqlWhere[] = 'C.numlevel > ' . 0;
                $sqlWhere[] = 'C.virtual_ptr IS NULL';
                $aSelectFields[] = '((C.numright-C.numleft)>1) as subs';
            }
            break;
        }

        $aData = $this->catsOptionsData($aSelectFields, $sqlWhere, $bCountItems);

        if (empty($aData)) $aData = array();

        if ($sType == 'adm-category-form-add') {
            foreach ($aData as &$v) {
                $v['disabled'] = ($v['numlevel'] > 0 && $v['items'] > 0);
            }
            unset($v);
        }

        if ($sType == 'adm-category-add-virtual') {
            foreach ($aData as &$v) {
                $v['disabled'] = $v['subs'] > 0;
            }
            unset($v);
        }

        $aRender = [];
        $aRender['cats'] = $aData;
        $aRender['mEmptyOpt'] = $mEmptyOpt;
        $aRender['nSelectedID'] = $nSelectedID;
        return $this->controller->viewPHP($aRender, 'admin.categories.options');
    }

    /**
     * Categories data for render as select options
     * @param array $aFields select fields
     * @param array $aFilter filter
     * @param bool $bCountItems select non-cached items count
     * @return mixed
     */
    public function catsOptionsData(array $aFields, array $aFilter, $bCountItems = false)
    {
        return $this->db->select('SELECT ' . join(',', $aFields) . '
                        ' . ($bCountItems ? ', SUM(I.items) as items ' : ', C.items') . '
                   FROM ' . TABLE_BBS_CATEGORIES_LANG . ' CL,
                        ' . TABLE_BBS_CATEGORIES . ' C
                        ' . ($bCountItems ? ' LEFT JOIN ' . TABLE_BBS_ITEMS_COUNTERS . ' I ON C.id = I.cat_id AND I.region_id = 0 AND (C.numright-C.numleft) = 1' : '') . '
                   WHERE ' . join(' AND ', $aFilter) . '
                   GROUP BY C.id
                   ORDER BY C.numleft'
        );
    }

    /**
     * Формирование списков категорий (при добавлении/редактировании объявления в админ панели, при поиске в категории)
     * @param integer $nCategoryID ID категории [lvl=>selectedCatID, ...]
     * @param mixed $mOptions формировать select-options или возвращать массивы данных о категориях
     * @param boolean $bPrepareURLKeywords подготовить keyword'ы для построения ссылок
     * @return array [lvl=>[a=>id выбранной,cats=>список категорий(массив или options)],...]
     */
    public function catsOptionsByLevel($aCategoriesID, $mOptions = false, $bPrepareURLKeywords = false)
    {
        $aData = array();
        if (empty($aCategoriesID)) $aCategoriesID = array(1 => 0);

        # формируем список уровней для которых необходимо получить категории
        $aLevels = array();
        $bFill = true;
        $parentID = BBS::CATS_ROOTID;
        foreach ($aCategoriesID as $lvl => $catID) {
            if ($catID || $bFill) {
                $aLevels[$lvl] = $parentID;
                if (!$catID) break;
                $parentID = $catID;
            } else {
                break;
            }
        }

        if (empty($aLevels)) return $aData;

        $sQuery = 'SELECT C.id, CL.title as t, CL.breadcrumb as cr, C.keyword as k, C.numlevel as lvl
                    FROM ' . TABLE_BBS_CATEGORIES . ' C,
                         ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                    WHERE C.numlevel IN (' . join(',', array_keys($aLevels)) . ')
                      AND C.pid IN(' . join(',', $aLevels) . ')
                      ' . $this->db->langAnd(true, 'C', 'CL') . '
                    ORDER BY C.numleft';
        $aData = $this->db->select($sQuery);
        if (empty($aData)) return array();

        $aLevels = array();
        foreach ($aData as $v) {
            $aLevels[$v['lvl']][$v['id']] = $v;
        }
        unset($aData);

        if (!empty($mOptions)) {
            foreach ($aCategoriesID as $lvl => $nSelectedID) {
                if (isset($aLevels[$lvl])) {
                    $aCategoriesID[$lvl] = array(
                        'a'    => $nSelectedID,
                        'cats' => HTML::selectOptions($aLevels[$lvl], $nSelectedID, $mOptions['empty'], 'id', 't'),
                    );
                } else {
                    $aCategoriesID[$lvl] = array(
                        'a'    => $nSelectedID,
                        'cats' => false,
                    );
                }
            }
        } elseif ($bPrepareURLKeywords) {
            foreach ($aCategoriesID as $lvl => $nSelectedID) {
                $aCategoriesID[$lvl] = array(
                    'a'    => $nSelectedID,
                    'cats' => (isset($aLevels[$lvl]) ? $aLevels[$lvl] : false),
                );
            }
        }

        return $aCategoriesID;
    }

    /**
     * Формируем запрос по фильтру "цена"
     * @param array $aPriceFilter настройки фильтра "цена": 'r'=>диапазоны, 'f'=>от, 't'=>до
     * @param array $aCategoryData @ref данные категории
     * @param string $sTablePrefix префикс таблицы, формат: 'T.'
     * @return string
     */
    public function preparePriceQuery($aPriceFilter, array &$aCategoryData, $sTablePrefix = '')
    {
        if (empty($aPriceFilter) || empty($aCategoryData['price_sett'])) return '';

        $nPriceCurrID = Site::currencyData($aCategoryData['price_sett']['curr'], 'id');
        $sPriceField = $sTablePrefix . 'price_search';

        $sql = array();
        # от - до
        if (!empty($aPriceFilter['f']) || !empty($aPriceFilter['t'])) {
            $nPriceFromTo = (!empty($aPriceFilter['c']) ? $aPriceFilter['c'] : $nPriceCurrID);
            $from = Site::currencyPriceConvertToDefault($aPriceFilter['f'], $nPriceFromTo);
            $to = Site::currencyPriceConvertToDefault($aPriceFilter['t'], $nPriceFromTo);
            if ($from > 0 && $to > 0 && $from >= $to) $from = 0;
            $sql[] = '(' . ($from > 0 ? "$sPriceField >= " . $from . ($to > 0 ? " AND $sPriceField <= " . $to : '') : "$sPriceField <= " . $to) . ')';
        }
        # диапазоны
        if (!empty($aCategoryData['price_sett']['ranges']) && !empty($aPriceFilter['r'])) {
            foreach ($aPriceFilter['r'] as $v) {
                if (isset($aCategoryData['price_sett']['ranges'][$v])) {
                    $v = $aCategoryData['price_sett']['ranges'][$v];
                    $v['from'] = Site::currencyPriceConvertToDefault($v['from'], $nPriceCurrID);
                    $v['to'] = Site::currencyPriceConvertToDefault($v['to'], $nPriceCurrID);
                    $sql[] = '(' . ($v['from'] ? "$sPriceField >= " . $v['from'] . ($v['to'] ? " AND $sPriceField <= " . $v['to'] : '') : "$sPriceField <= " . $v['to']) . ')';
                }
            }
        }

        return (!empty($sql) ? '(' . join(' OR ', $sql) . ')' : '');
    }

    /**
     * Поиск категории по названию для автокомплитера
     * @param string $q
     * @param array $filter
     * @param string $limit
     * @return array|mixed
     */
    public function catsAutocompleter($q, $filter = array(), $limit = 15)
    {
        if(empty($q)) return array();
        $filter[] = $this->db->langAnd(false, 'C', 'CL');
        $filter[':q'] = array('CL.title LIKE :q ', ':q' => '%'.$q.'%');
        $filter = $this->prepareFilter($filter);

        return $this->db->select('
            SELECT C.id, CL.title
            FROM '.TABLE_BBS_CATEGORIES.' C, '.TABLE_BBS_CATEGORIES_LANG.' CL
            '.$filter['where'].$this->db->prepareLimit(0, $limit), $filter['bind']);
    }

    /**
     * Дата последнего изменения структуры категорий объявлений
     * @param bool $update true - обновить до текущей даты, false - вернуть дату последнего изменения
     * @return mixed|string
     */
    public function catsStructureChanged($update = false)
    {
        if ($update) {
            $now = $this->db->now();
            $this->db->update(TABLE_BBS_CATEGORIES, array(
                'modified' => $now,
            ), array(
                'id' => BBS::CATS_ROOTID,
            ));
            return $now;
        } else {
            return $this->db->one_data('SELECT modified FROM '.TABLE_BBS_CATEGORIES.' WHERE id = :id', array(
                ':id' => BBS::CATS_ROOTID,
            ));
        }
    }

    # ----------------------------------------------------------------
    # Типы категорий объявлений

    public function cattypesListing($aFilter)
    {
        if (empty($aFilter)) {
            $aFilter = array();
        }

        $aFilter[] = 'T.cat_id = C.id';
        $aFilter = $this->prepareFilter($aFilter);

        return $this->db->select('SELECT T.*, T.title_' . LNG . ' as title, C.title as cat_title
                    FROM ' . TABLE_BBS_CATEGORIES_TYPES . ' T,
                         ' . TABLE_BBS_CATEGORIES . ' C
                    ' . $aFilter['where'] . '
                    ORDER BY C.numleft, T.num ASC', $aFilter['bind']
        );
    }

    public function cattypeData($nTypeID, $aFields = array(), $bEdit = false)
    {
        if (empty($aFields)) $aFields = '*';

        $aParams = array();
        if (!is_array($aFields)) $aFields = array($aFields);
        foreach ($aFields as $v) {
            $aParams[] = $v;
        }

        $aData = $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_CATEGORIES_TYPES . '
                       WHERE id = :id
                       LIMIT 1', array(':id' => $nTypeID)
        );

        if ($bEdit) {
            $this->db->langFieldsSelect($aData, $this->langCategoriesTypes);
        }

        return $aData;
    }

    public function cattypeSave($nTypeID, $nCategoryID, $aData)
    {
        if ($nTypeID) {
            $aData['modified'] = $this->db->now();
            $this->db->langFieldsModify($aData, $this->langCategoriesTypes, $aData);

            return $this->db->update(TABLE_BBS_CATEGORIES_TYPES, $aData, array('id'=>$nTypeID));
        } else {
            $nNum = (integer)$this->db->one_data('SELECT MAX(num) FROM ' . TABLE_BBS_CATEGORIES_TYPES . ' WHERE cat_id = ' . $nCategoryID);
            $aData['num'] = $nNum + 1;
            $aData['cat_id'] = $nCategoryID;
            $aData['created'] = $aData['modified'] = $this->db->now();
            $this->db->langFieldsModify($aData, $this->langCategoriesTypes, $aData);

            return $this->db->insert(TABLE_BBS_CATEGORIES_TYPES, $aData, 'id');
        }
    }

    public function cattypeDelete($nTypeID)
    {
        if (!$nTypeID || !BBS::CATS_TYPES_EX) return false;

        # удаляем только "свободный" тип
        $nItems = $this->db->one_data('SELECT COUNT(id) FROM ' . TABLE_BBS_ITEMS . ' WHERE cat_type = :id', array(':id' => $nTypeID));
        if (!empty($nItems)) {
            $this->errors->set('Невозможно удалить тип категории с объявлениями');

            return false;
        }

        $res = $this->db->delete(TABLE_BBS_CATEGORIES_TYPES, array('id' => $nTypeID));

        return !empty($res);
    }

    public function cattypeToggle($nTypeID, $sField)
    {
        if (!$nTypeID) return false;

        switch ($sField) {
            case 'enabled':
            {
                return $this->toggleInt(TABLE_BBS_CATEGORIES_TYPES, $nTypeID, 'enabled', 'id');
            }
            break;
        }

        return false;
    }

    public function cattypesRotate($nCategoryID)
    {
        return $this->db->rotateTablednd(TABLE_BBS_CATEGORIES_TYPES, ' AND cat_id = ' . $nCategoryID);
    }

    /**
     * Формирование списка типов, привязанных к категории
     * @param integer $nCategoryID ID категории
     * @param mixed $mOptions формировать select-options или FALSE
     * @return array|string
     */
    public function cattypesByCategory($nCategoryID, $mOptions = false)
    {
        $aData = array();
        do {
            if (empty($nCategoryID)) break;

            $aCategoryParentsID = $this->catParentsID($nCategoryID);
            if (empty($aCategoryParentsID)) break;

            $aData = $this->db->select_key('SELECT T.id, T.title_' . LNG . ' as title, T.items
                FROM ' . TABLE_BBS_CATEGORIES_TYPES . ' T, ' . TABLE_BBS_CATEGORIES . ' C
                WHERE T.cat_id IN (' . join(',', $aCategoryParentsID) . ') AND T.cat_id = C.id
                ORDER BY C.numleft, T.num ASC', 'id'
            );
        } while (false);

        if (!empty($mOptions)) {
            return HTML::selectOptions($aData, $mOptions['sel'], $mOptions['empty'], 'id', 'title');
        } else {
            return $aData;
        }
    }

    /**
     * Формирование списка простых типов (BBS::TYPE_)
     * @param array $aCategoryData данные о категории
     * @param bool $bSearch для поиска
     * @return array|string
     */
    public function cattypesSimple(array $aCategoryData, $bSearch)
    {
        if (empty($aCategoryData) || !isset($aCategoryData['seek'])) return array();
        $aTypes = array(
            BBS::TYPE_OFFER => array('id' => BBS::TYPE_OFFER, 'title' => ''),
            BBS::TYPE_SEEK  => array('id' => BBS::TYPE_SEEK, 'title' => ''),
        );
        if ($bSearch) {
            $aTypes[BBS::TYPE_OFFER]['title'] = (!empty($aCategoryData['type_offer_search']) ? $aCategoryData['type_offer_search'] : _t('bbs', 'Объявления'));
            $aTypes[BBS::TYPE_SEEK]['title'] = (!empty($aCategoryData['type_seek_search']) ? $aCategoryData['type_seek_search'] : _t('bbs', 'Объявления'));
        } else {
            $aTypes[BBS::TYPE_OFFER]['title'] = (!empty($aCategoryData['type_offer_form']) ? $aCategoryData['type_offer_form'] : _t('bbs', 'Предлагаю'));
            $aTypes[BBS::TYPE_SEEK]['title'] = (!empty($aCategoryData['type_seek_form']) ? $aCategoryData['type_seek_form'] : _t('bbs', 'Ищу'));
        }
        if (!$aCategoryData['seek']) unset($aTypes[BBS::TYPE_SEEK]);

        return $aTypes;
    }

    # ----------------------------------------------------------------
    # Жалобы

    public function claimsListing($aFilter, $bCount = false, $sqlLimit = '')
    {
        if ($bCount) {
            $aFilter = $this->prepareFilter($aFilter, 'CL');
            return (int)$this->db->tag('bbs-claims-listing-count', array('filter'=>&$aFilter))->one_data('SELECT COUNT(CL.id)
                                FROM ' . TABLE_BBS_ITEMS_CLAIMS . ' CL
                                ' . $aFilter['where'], $aFilter['bind']
            );
        }

        $aFilter[':jitem'] = 'CL.item_id = I.id ';
        $aFilter = $this->prepareFilter($aFilter, 'CL');
        return $this->db->tag('bbs-claims-listing-data', array('filter'=>&$aFilter))->select('SELECT CL.*, U.name, U.login, U.blocked as ublocked, U.deleted as udeleted, I.link
                                FROM ' . TABLE_BBS_ITEMS_CLAIMS . ' CL
                                    LEFT JOIN ' . TABLE_USERS . ' U ON CL.user_id = U.user_id,
                                    '.TABLE_BBS_ITEMS.' I
                                ' . $aFilter['where'] . '
                            ORDER BY CL.created DESC' . $sqlLimit, $aFilter['bind']
        );
    }

    public function claimData($nClaimID, $aFields = array())
    {
        if (empty($aFields)) $aFields = '*';
        $aParams = array();
        if (!is_array($aFields)) $aFields = array($aFields);
        foreach ($aFields as $v) {
            $aParams[] = $v;
        }

        return $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_ITEMS_CLAIMS . '
                       WHERE id = :cid
                       LIMIT 1', array(':cid' => $nClaimID)
        );
    }

    public function claimSave($nClaimID, $aData)
    {
        if ($nClaimID) {
            return $this->db->update(TABLE_BBS_ITEMS_CLAIMS, $aData, array('id' => $nClaimID));
        } else {
            $aData['created'] = $this->db->now();
            $aData['user_id'] = User::id();
            $aData['user_ip'] = Request::remoteAddress();

            $nClaimID = $this->db->insert(TABLE_BBS_ITEMS_CLAIMS, $aData, 'id');
            if ($nClaimID > 0) {
                $aData['id'] = $nClaimID;
                bff::hook('bbs.item.claim.create', $aData);
            }
            return $nClaimID;
        }
    }

    public function claimDelete($nClaimID)
    {
        if (!$nClaimID) return false;

        return $this->db->delete(TABLE_BBS_ITEMS_CLAIMS, array('id' => $nClaimID));
    }

    # ----------------------------------------------------------------
    # Платные лимиты

    /**
     * Платные лимиты: Сохранение настроек
     * Данные о регионах должны передаваться всегда. Если нет, то регионы удаляются
     * @param integer $limitID ID настройки
     * @param array $data данные
     * @return integer ID настройки
     */
    public function limitsPayedSaveRegions($limitID, $data)
    {
        if (empty($data)) return false;
        if (isset($data['title']) && is_array($data['title'])) {
            $data['title'] = serialize($data['title']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = serialize($data['settings']);
        }
        $regions = false;
        if (isset($data['regions'])) {
            $regions = $data['regions'];
            unset($data['regions']);
        }
        if ($limitID) {
            if ( ! empty($regions)) {
                $region = array_shift($regions);
                $data += $region;
            } else {
                $data += array(
                    'reg1_country' => 0,
                    'reg2_region' => 0,
                    'reg3_city' => 0,
                );
            }
            # удалим данные о регионах
            $this->db->delete(TABLE_BBS_ITEMS_LIMITS, array('group_id' => $limitID));
            $this->db->update(TABLE_BBS_ITEMS_LIMITS, $data, array('id' => $limitID));
        } else {
            if ( ! empty($regions)) {
                $region = array_shift($regions);
                $data += $region;
            }
            $limitID = $this->db->insert(TABLE_BBS_ITEMS_LIMITS, $data, 'id');
        }
        # сохраним регионы, если есть
        if ( ! empty($regions)) {
            unset($data['title']);
            $data['group_id'] = $limitID;

            foreach ($regions as & $v) {
                $v += $data;
            } unset($v);
            $this->db->multiInsert(TABLE_BBS_ITEMS_LIMITS, $regions);
        }
        return $limitID;
    }

    /**
     * Платные лимиты: Обновление настроек, без учета региональных
     * @param integer $limitID ID настройки
     * @param array $data данные
     * @return bool
     */
    public function limitsPayedUpdate($limitID, $data)
    {
        if ( ! $limitID) return false;
        if (empty($data)) return false;
        if (isset($data['title']) && is_array($data['title'])) {
            $data['title'] = serialize($data['title']);
        }
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = serialize($data['settings']);
        }
        return $this->db->update(TABLE_BBS_ITEMS_LIMITS, $data, array('id' => $limitID));
    }

    /**
     * Платные лимиты: Выборка по фильтру настроек
     * @param array $filter фильтр
     * @param bool|array $fields поля, если false - только подсчет количества
     * @param bool|true $oneArray вернуть только одну строку
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function limitsPayedByFilter(array $filter, $fields = false, $oneArray = true, $sqlLimit = '', $sqlOrder = '')
    {
        $from = ' FROM ' . TABLE_BBS_ITEMS_LIMITS . ' L ';
        $group = '';

        if (empty($fields)) {
            # только подсчет количества
            $filter = $this->prepareFilter($filter, 'L');
            return $this->db->one_data('SELECT COUNT(*) '. $from . $filter['where'], $filter['bind']);
        } else {
            foreach ($fields as &$v) {
                if ($v == 'active') {
                    $v = 'COUNT(U.id) AS active';
                    if (empty($filter['free']))  {
                        $from .= ' LEFT JOIN ' . TABLE_BBS_ITEMS_LIMITS_USERS . ' U ON L.id = U.paid_id ';
                    } else {
                        $from .= ' LEFT JOIN ' . TABLE_BBS_ITEMS_LIMITS_USERS . ' U ON L.id = U.free_id ';
                    }
                    $group = 'GROUP BY L.id';
                } else if( ! strpos($v, '.')) {
                    $v = 'L.'.$v;
                }
            } unset($v);
        }
        $filter = $this->prepareFilter($filter, 'L');

        if ($oneArray) {
            # вернуть только одну строку
            $data = $this->db->one_array('SELECT ' . join(',', $fields) . $from . $filter['where'].$group.' LIMIT 1', $filter['bind']);
            $this->limitsPayedByFilterPrepare($data);
        } else {
            if ($sqlOrder == 'cat') {
                # сортировка по категории
                $from .= ' LEFT JOIN '.TABLE_BBS_CATEGORIES.' C ON L.cat_id = C.id ';
                $sqlOrder = 'C.numleft';
            }
            $data = $this->db->select('
                SELECT ' . join(',', $fields) .
                $from.
                $filter['where'] . $group. '
                ' . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '') . '
                ' . $sqlLimit, $filter['bind']);
            foreach ($data as & $v) {
                $this->limitsPayedByFilterPrepare($v);
            }
        }
        return $data;
    }

    /**
     * Платные лимиты: Подготовка данных настроек
     * @param array $data @ref
     */
    protected function limitsPayedByFilterPrepare(& $data)
    {
        if ( ! empty($data['title'])) {
            $data['title'] = func::unserialize($data['title']);
            if ( ! empty($data['title']['cat'])) {
                $title = array();
                foreach ($data['title']['cat'] as $v) {
                    $title[] = $v['title'];
                }
                $data['title']['cat_title'] = join(' / ', $title);
            }
        }
        if ( ! empty($data['settings'])) {
            $data['settings'] = func::unserialize($data['settings']);
        }
    }

    /**
     * Платные лимиты: Удаление настроек по фильтру
     * @param integer|array $filter
     * @return bool
     */
    public function limitsPayedDelete($filter)
    {
        if (empty($filter)) return false;
        if ( ! is_array($filter)) {
            $filter = array('id' => $filter);
        }
        return $this->db->delete(TABLE_BBS_ITEMS_LIMITS, $filter);
    }

    /**
     * Определим количество бесплатных объявлений для категории
     * @param integer $catID ID категории
     * @param bool $shop флаг магазина
     * @param array $catParents родители категории, если есть
     * @return int
     */
    public function limitsPayedFreeForCategory($catID, $shop, $catParents = array())
    {
        if (empty($catParents) && $catID != 0) {
            # найдем паренты, если не указано
            $catParents = $this->catParentsID($catID);
        }
        array_unshift($catParents, 0);

        # сушествующие поинты для всех парентов
        $limits = $this->limitsPayedByFilter(array(
            'cat_id' => $catParents,
            'shop' => $shop,
            'free' => 1
        ), array('items', 'cat_id'), false);

        if ( ! empty($limits)) {
            $limits = func::array_transparent($limits, 'cat_id', true);
        }

        # вернем лимит для категории или первого встретившегося родителя
        $catParents = array_reverse($catParents);
        foreach ($catParents as $v) {
            if (isset($limits[$v])) {
                return $limits[$v]['items'];
            }
        }
        return 0;
    }

    /**
     * Определим настройки для категории, ближайший поинт, лимит бесплатных и настройки платных
     * @param integer $catID ID категории
     * @param bool $shop флаг магазина
     * @param array $catParents родители категории, если есть
     * @param array $region искать для региона, если указан
     * @return array|boolean массив с результатом
     * array(
     *      'point'    => ID категории ближайшего поинта
     *      'limit'    => количество бесплатных
     *      'settings' => настройки стоимости
     *      'free_id'  => ID лимита бесплатных
     *      'paid_id'  => ID лимита стоимости
     * )
     */
    public function limitsPayedPointForCategory($catID, $shop, $catParents = array(), $region = array())
    {
        if (empty($catParents) && $catID != 0) {
            # найдем паренты, если не указано
            $catParents = $this->catParentsID($catID);
        }
        array_unshift($catParents, 0);
        $paidRegion = array();
        if ( ! empty($region)) {
            # сушествующие поинты с региональными настройками оплаты для всех парентов
            $tmp = $this->limitsPayedByFilter(array(
                'cat_id' => $catParents,
                'shop' => $shop,
                'free' => 0,
                'enabled' => 1,
                ':r' => array(' ( ( reg1_country = :country AND reg2_region = :region AND reg3_city = :city ) 
                               OR ( reg1_country = :country AND reg2_region = :region AND reg3_city = 0 )
                               OR ( reg1_country = :country AND reg2_region = 0       AND reg3_city = 0 ) ) ',
                                ':country' => $region['reg1_country'],
                                ':region'  => $region['reg2_region'],
                                ':city'    => $region['reg3_city'],
                             ),
                ),
                array('id', 'cat_id', 'settings', 'reg1_country', 'reg2_region', 'reg3_city'), false);
            # Выберем наиболее подходяшие поинты по региону
            if ( ! empty($tmp)) {
                foreach ($tmp as $v) {
                    if ( ! isset($paidRegion[ $v['cat_id'] ])) {
                        $paidRegion[ $v['cat_id'] ] = $v;
                    } else {
                        if ($v['reg3_city']) {
                            $paidRegion[ $v['cat_id'] ] = $v;
                        } else if( ! $paidRegion[ $v['cat_id'] ]['reg3_city'] && $v['reg2_region']) {
                            $paidRegion[ $v['cat_id'] ] = $v;
                        }
                    }
                }
            }
        }

        # сушествующие поинты с настройками оплаты для всех парентов
        $paid = $this->limitsPayedByFilter(array(
            'cat_id' => $catParents,
            'shop' => $shop,
            'free' => 0,
            'enabled' => 1,
            'reg1_country' => 0,
            'reg2_region' => 0,
            'reg3_city' => 0,
        ), array('id', 'cat_id', 'settings'), false);
        if ( ! empty($paid)) {
            $paid = func::array_transparent($paid, 'cat_id', true);
        }
        if ( ! empty($paidRegion)) {
            foreach ($paid as $k => $v) {
                if (isset($paidRegion[$k])) {
                    $paid[$k] = $paidRegion[$k];
                    $paid[$k]['id'] = $v['id'];
                    $paid[$k]['reg_id'] = $paidRegion[$k]['id'];
                }
            }
        }

        # сушествующие поинты с лимитами бесплатных для всех парентов
        $limits = $this->limitsPayedByFilter(array(
            'cat_id' => $catParents,
            'shop' => $shop,
            'free' => 1,
            'enabled' => 1,
        ), array('id', 'items', 'cat_id'), false);

        if ( ! empty($limits)) {
            $limits = func::array_transparent($limits, 'cat_id', true);
        }

        # вернем лимит и оплату для категории или первого встретившегося родителя
        $catParents = array_reverse($catParents);
        $limit = false; $pay = false;
        $i = 1;
        foreach ($catParents as $v) {
            if (isset($limits[$v]) && ! $limit) {
                $limit = array('point' => $v, 'limit' => $limits[$v]['items'], 'free_id' => $limits[$v]['id'], 'i' => $i);
            }
            if (isset($paid[$v]) && ! $pay) {
                $pay = array('point' => $v, 'settings' => $paid[$v]['settings'],'paid_id' => $paid[$v]['id'], 'i' => $i);
            }
            $i++;
        }
        if (empty($limit)) {
            return false;
        }

        # ID поинта по первой встретившийся оплате или лимитом
        if ($pay['i'] < $limit['i']) {
            $limit['point'] = $pay['point'];
        }
        unset($limit['i']);
        $limit['settings'] = $pay['settings'];
        $limit['paid_id'] = $pay['paid_id'];
        return $limit;
    }

    /**
     * Проверим лимиты для пользователей у которых истек срок действия купленных лимитов. Раз в 10 мин по 10 юзеров за итерацию
     */
    public function limitsPayedCron()
    {
        if ( ! BBS::limitsPayedEnabled()) return;
        if ( ! config::get('bbs_limits_payed_days', 0, TYPE_UINT)) {
            return; # включен беcсрочный режим
        }

        $users = $this->db->select_one_column('
            SELECT user_id
            FROM '.TABLE_BBS_ITEMS_LIMITS_USERS.'
            WHERE expire < :now AND active = 1
            GROUP BY user_id
            LIMIT 10', array(':now' => $this->db->now()));
        if (empty($users)) return;

        $this->db->exec('
            UPDATE '.TABLE_BBS_ITEMS_LIMITS_USERS.'
            SET active = 0
            WHERE user_id IN ('.join(',', $users).') AND expire < :now AND active = 1', array(
                ':now' => $this->db->now()
        ));

        foreach ($users as $u) {
            $this->limitsPayedUserUnpublicate($u);
        }
    }

    /**
     * Снятие с публикации объявлений, превысивших лимит для пользователя
     * @param integer $userID ID пользователя
     */
    public function limitsPayedUserUnpublicate($userID)
    {
        if ( ! $userID) return;
        $user = Users::model()->userDataByFilter($userID, array('shop_id'));
        $shop = array(0);
        # если есть магазин, то проверим и для магазина
        if ($user['shop_id'] && ! Shops::abonementEnabled()) {
            $shop[] = $user['shop_id'];
        }
        foreach ($shop as $shopID) {
            # превышение лимитов по поинтам
            $limits = $this->limitsPayedCategoriesForUser(array(
                'user_id' => $userID,
                'shop_id' => $shopID,
            ));
            foreach ($limits as $l) {
                if ($l['cnt'] <= $l['limit']) continue;
                /**
                 * Есть превышение, снимем с публикации объявлений выходящих за лимит
                 * При объявления с активными платными услугами в последнюю очередь.
                 */
                $this->itemsUpdateByFilter(array(
                    'status'         => BBS::STATUS_PUBLICATED_OUT,
                    'status_prev'    => BBS::STATUS_PUBLICATED,
                    'status_changed' => $this->db->now(),
                    'is_publicated'  => 0,
                ), array(
                    'user_id' => $userID,
                    'shop_id' => $shopID,
                    'is_publicated' => 1,
                    'status' => BBS::STATUS_PUBLICATED,
                    'cat_id' => array_keys($l['cats']),
                ), array(
                    'context' => __FUNCTION__,
                    'orderBy' => 'svc, svc_fixed_order, publicated_order',
                    'limit'   => ($l['cnt'] - $l['limit'])
                ));
            }
        }
    }

    /**
     * Определим регион пользователя
     * @param integer $userID ID пользователя
     * @return array
     */
    public function limitsPayedUserRegion($userID)
    {
        if ( ! $userID) return array();
        $user = Users::model()->userDataByFilter($userID, array('reg1_country','reg2_region','reg3_city'));
        # Регион - 2+ объявления из одного региона берем этот регион, иначе берем из профиля
        $regions = $this->db->one_array('
            SELECT reg1_country, reg2_region, reg3_city, count(*) AS cnt
            FROM '.TABLE_BBS_ITEMS.'
            WHERE user_id = :user
            GROUP BY reg1_country, reg2_region, reg3_city
            ORDER BY cnt
            LIMIT 1
        ', array(':user' => $userID));
        if ( ! empty($regions) && $regions['cnt'] > 1) {
            unset($regions['cnt']);
            return $regions;
        }
        return $user;
    }

    /**
     * Определим количество объявлений для поинтов у пользователя, с учетом купленных лимитов
     * @param array $filter фильтр объявлений isset('id') => добавить объявления с указанными ID
     * @param mixed $count только посчитать количество isset(array('strict')) - только количество объявлений превышающих лимит
     * @return array|int int - количество или
     * array( - массив поинтов с данными
     *  'pointID' => array(
     *      'point'    => ID категории ближайшего поинта
     *      'limit'    => количество бесплатных + количество купленных
     *      'free'     => количество бесплатных
     *      'buy'      => массив купленных array('id' => кол) (id => bff_bbs_items_limits_users)
     *      'settings' => настройки стоимости
     *      'free_id'  => ID лимита бесплатных
     *      'paid_id'  => ID лимита стоимости
     *      'cnt'      => количество объявлений
     *      'cats'     => массив всех дочерних категорий с объявлениями входящих в поинт array('cat_id' => кол-во объявл)
     * ))
     */
    public function limitsPayedCategoriesForUser($filter, $count = false)
    {
        if(empty($filter['user_id'])) return array(); # пользователь обязателен
        if( ! isset($filter['shop_id'])) return array(); # одновременно и для пользователя и магазина нельзя
        $filterMain = $filter;
        $filterShop = ! empty($filter['shop_id']);
        if ($filterShop && Shops::abonementEnabled()) {
            return array(); # включена услуга абонемент - лимиты для магазинов не используются
        }

        # если есть фильтр по категории, проверим лимиты для поинта в котором эта категория
        if(isset($filter['cat_id'])){
            $filterCat = $filter['cat_id'];
            $f = $filter;
            unset($f['cat_id']);
            $limitAll = $this->limitsPayedCategoriesForUser($f);
        }

        # все активные купленные пакеты пользователя
        $buyPoints = $this->limitsPayedUserAll($filter['user_id'], ! empty($filter['shop_id']) ? 1 : 0);
        # только опубликованные объявления
        if( ! isset($filter['status'])){
            $filter['is_publicated'] = 1;
            $filter['status'] = BBS::STATUS_PUBLICATED;
        }

        # с учетом настроек модерации
        if (BBS::premoderation()) {
            $filter[':mod'] = 'I.moderated > 0';
        }

        # все возможные паренты категории
        $cats = array();
        for($i = 1; $i <= BBS::CATS_MAXDEEP; $i++){
            $cats[] = 'I.cat_id'.$i;
        }

        $ids = array();
        if (isset($filter['id']))
        {
            # проверить превышение лимита с учетом объявлений c id из массива $filter['id']
            $idsData = $this->db->select('
                SELECT I.shop_id, I.cat_id, COUNT(*) AS cnt, '.join(',', $cats).'
                FROM '.TABLE_BBS_ITEMS.' I
                WHERE I.id IN('.join(',', $filter['id']).')
                GROUP BY I.shop_id, I.cat_id ');
            foreach ($idsData as $v) {
                $shop = $v['shop_id'] > 0 ? 1 : 0;
                $ids[ $v['cat_id'] ][ $shop ] = $v['cnt'];
            }
            unset($filter['id']);
        }

        $filter = $this->prepareFilter($filter, 'I');

        $data = $this->db->select('
            SELECT I.shop_id, I.cat_id, COUNT(*) AS cnt, '.join(',', $cats).'
            FROM '.TABLE_BBS_ITEMS.' I
            ' . $filter['where'].'
            GROUP BY I.shop_id, I.cat_id ', $filter['bind']);

        if ( ! empty($ids)) {
            # проверка на пустую категорию
            foreach ($data as $v) {
                foreach ($idsData as $k => $vv) {
                    if ($v['shop_id'] == $vv['shop_id'] && $v['cat_id'] == $vv['cat_id']) {
                        unset($idsData[$k]);
                        break;
                    }
                }
            }
            if ( ! empty($idsData)) {
                # добавление в категорию, в которой раньше не было объявлений
                foreach ($idsData as $v) {
                    $shop = $v['shop_id'] > 0 ? 1 : 0;
                    $data[] = $v;
                    unset($ids[ $v['cat_id'] ][ $shop ]);
                }
            }
        }

        # массив поинтов с данными
        $limits = array();
        foreach ($data as $v)
        {
            # определим паррентов для категорий
            $cats = array();
            for ($i = 1; $i <= BBS::CATS_MAXDEEP; $i++) {
                if (!empty($v['cat_id' . $i])) {
                    $cats[] = $v['cat_id' . $i];
                }
            }
            # флаг магазина
            $shop = $v['shop_id'] > 0 ? 1 : 0;
            if (isset($ids[ $v['cat_id'] ][$shop])) {
                # добавим объявления из массива $filter['id']
                $v['cnt'] += $ids[ $v['cat_id'] ][$shop];
            }
            # найдем поинта для категории
            $limit = $this->limitsPayedPointForCategory($v['cat_id'], $shop, $cats);
            if (empty($limit)) continue;
            $point = $limit['point'];
            $limit['free'] = $limit['limit'];
            $limit['buy'] = array();

            if ( ! isset($limits[ $point ])) {
                # новый поинт
                $limit['cnt'] = $v['cnt'];
                if(isset($buyPoints[$point])){
                    $limit['limit'] += $buyPoints[$point]['sum'];
                    $limit['buy'] = $buyPoints[$point]['buy'];
                }
                $limits[ $point ] = $limit;
            } else {
                # добавим к существующему
                $limits[ $point ]['cnt'] += $v['cnt'];
            }
            # перечень категорий, входящих в поинт
            $limits[ $point ]['cats'][ $v['cat_id'] ] = $v['cnt'];
        }

        # фильтр по категории
        if (isset($filterCat)) {
            # проверим лимиты для поинта в котором эта категория 
            $catPoint = $this->limitsPayedPointForCategory($filterCat, $filterShop);
            $p = $catPoint['point'];
            if (isset($limitAll[$p])) {
                if ( ! isset($limits[$p])) {
                    $limits[$p] = $limitAll[$p];
                } else {
                    $limits[$p]['cnt'] = $limitAll[$p]['cnt'];
                }
            } else {
                # проверка для нулевого лимита
                if ($catPoint['limit'] == 0 && ! isset($buyPoints[$p])) {
                    $catPoint['cnt'] = 0;
                    $limits[ $p ] = $catPoint;
                }
            }
        }

        # проверим для общего лимита = 0
        if (empty($limits)) {
            if( ! empty($filterCat) && ! empty($catPoint)) {
                # Найден поинт для категории
                if ( ! isset($catPoint['cnt'])) $catPoint['cnt'] = 0;
                $limits[ $catPoint['point'] ] = $catPoint;
            } else {
                # формируем поинт для общего лимита
                $limitFree = $this->limitsPayedByFilter(array(
                    'shop'   => $filterShop,
                    'free'   => 1,
                    'cat_id' => 0,
                ), array('id', 'items', 'cat_id'));
                if ( ! empty($limitFree['id']) && $limitFree['items'] == 0) {
                    # общий лимит равен 0
                    $limitPaid = $this->limitsPayedByFilter(array(
                        'shop'   => $filterShop,
                        'free'   => 0,
                        'cat_id' => 0,
                        'reg1_country' => 0,
                        'reg2_region' => 0,
                        'reg3_city' => 0,
                    ), array('id', 'cat_id', 'settings'));
                    if ( ! empty($limitPaid['id'])) {
                        $limits[ 0 ] = array(
                            'point' => 0,
                            'limit' => 0,
                            'cnt' => 0,
                            'settings' => $limitPaid['settings'],
                            'free_id' => $limitFree['id'],
                            'paid_id' => $limitPaid['id'],
                        );
                    }
                }
            }
            # учтем купленные лимиты
            foreach ($limits as $k => & $v) {
                if ( isset($buyPoints[ $v['point'] ])) {
                    $v['limit'] += $buyPoints[ $v['point'] ]['sum'];
                }
            } unset($v);
        }

        # проверим нулевые лимиты с учетом не опубликованных объявлений
        if ( ! isset($filterMain['status'])) {
            $filterMain['status'] = array(BBS::STATUS_PUBLICATED, BBS::STATUS_PUBLICATED_OUT);
            $limitOut = $this->limitsPayedCategoriesForUser($filterMain);
            foreach ($limitOut as $k => $v) {
                if ($v['limit'] > 0) continue;
                if (isset($limits[$k])) continue;
                $v['cnt'] = 0;
                $limits[$k] = $v;
            }
        }

        if ($count) {
            # только расчет количества поинтов
            $cnt = 0;
            if (is_array($count) && ! empty($count['strict'])) {
                # количество поинтов превышающих лимит
                foreach ($limits as $v) {
                    if ($v['cnt'] > $v['limit']) {
                        $cnt++;
                    }
                }
            } else {
                # количество поинтов равных лимиту
                foreach ($limits as $v) {
                    if ($v['cnt'] >= $v['limit']) {
                        $cnt++;
                    }
                }
            }
            return $cnt;
        }

        return $limits;
    }

    /**
     * Сохранение купленного пользователем лимита
     * @param int|array $limitID ID купленного лимита или 0
     * @param array $data данные
     * @return bool|int
     */
    public function limitsPayedUserSave($limitID, $data)
    {
        if (empty($data)) {
            return false;
        }
        if ($limitID) {
            return $this->db->update(TABLE_BBS_ITEMS_LIMITS_USERS, $data, array('id' => $limitID));
        } else {
            $data['created'] = $this->db->now();
            return $this->db->insert(TABLE_BBS_ITEMS_LIMITS_USERS, $data);
        }
    }

    /**
     * Выборка купленных пользователем лимитов по фильтру
     * @param array $filter фильтр
     * @param mixed $fields поля, если false - только подсчет количества
     * @param bool $oneArray вернуть только одну строку
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function limitsPayedUserByFilter($filter, $fields = false, $oneArray = true, $sqlLimit = '', $sqlOrder = '')
    {
        $from = ' FROM ' . TABLE_BBS_ITEMS_LIMITS_USERS . ' L ';
        $filter = $this->prepareFilter($filter, 'L');

        if (empty($fields)) {
            # только подсчет количества
            return $this->db->one_data('SELECT COUNT(*) '. $from . $filter['where'], $filter['bind']);
        } else {
            foreach ($fields as &$v) {
                if ( ! strpos($v, '.')) {
                    $v = 'L.'.$v;
                }
            } unset($v);
        }

        if ($oneArray) {
            # вернуть только одну строку
            $data = $this->db->one_array('SELECT ' . join(',', $fields) . $from . $filter['where'].' LIMIT 1', $filter['bind']);
        } else {
            if (strpos($sqlOrder,'cat') !== false) {
                # запрошена сортировка по категории
                $from .= ' LEFT JOIN '.TABLE_BBS_CATEGORIES.' C ON L.cat_id = C.id ';
                $sqlOrder = str_replace('cat', 'C.numleft', $sqlOrder);
            }
            $data = $this->db->select('
                SELECT ' . join(',', $fields) .
                $from.
                $filter['where'] . '
                ' . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '') . '
                ' . $sqlLimit, $filter['bind']);
        }
        return $data;
    }

    /**
     * Данные о всех купленных активных пакетах пользователя
     * @param integer $userID ID пользователя
     * @param bool $shop флаг магазина
     * @return array
     */
    public function limitsPayedUserAll($userID, $shop)
    {
        static $cache;
        if ( isset($cache[$userID][$shop]) ) {
            return $cache[$userID][$shop];
        }
        $data = $this->limitsPayedUserByFilter(array(
            'user_id' => $userID,
            'shop'    => $shop,
            'active'  => 1,
        ), array('id', 'cat_id', 'items'), false);
        $result = array();
        foreach ($data as $v) {
            if ( ! isset($result[ $v['cat_id'] ])) {
                $result[ $v['cat_id'] ] = array('sum' => 0, 'buy' => array());
            }
            $result[ $v['cat_id'] ]['sum'] += $v['items'];
            $result[ $v['cat_id'] ]['buy'][ $v['id'] ] = $v['items'];
        }
        $cache[$userID][$shop] = $result;
        return $result;
    }

    /**
     * Расчет количества регионов, для заголовков в админке
     * @param integer $catID ID категории
     * @param bool $shop флаг магазина
     */
    public function limitsPayedRegionsCnt($catID, $shop)
    {
        $data = $this->limitsPayedByFilter(array(
            'cat_id' => $catID,
            'shop'   => $shop,
            'free'   => 0,
            'reg1_country' => 0,
            'reg2_region'  => 0,
            'reg3_city'    => 0,
        ), array('id', 'title'));
        if (empty($data)) return;

        $title = $data['title'];
        $cnt = $this->limitsPayedByFilter(array(
            'cat_id'    => $catID,
            'shop'      => $shop,
            'free'      => 0,
            ':r'        => ' ( reg1_country > 0 OR reg2_region > 0 OR reg3_city > 0 ) ',
        ));

        if ( ! isset($title['regions_cnt']) || $title['regions_cnt'] != $cnt) {
            $title['regions_cnt'] = $cnt;
            $this->limitsPayedUpdate($data['id'], array('title' => $title));
        }
    }

    /**
     * Включение / выключение поинтов (платные лимиты)
     * @param integer $limitID ID поинта
     * @param bool $enabled включить / выключить
     * @param bool & $toggling @ref false - операция завершена, true запущена проверка активных купленных лимитов
     * @return bool false - ошибка, нельзя переключить
     */
    public function limitsPayedToggle($limitID, $enabled, & $toggling)
    {
        # все переключаем поочереди
        $toggling = true;
        $data = config::get('bbs_limitsPayedToggle', '', TYPE_STR);
        if ( ! empty($data)) return false;

        $toggling = false;
        if ( ! $limitID) return false;
        $data = $this->limitsPayedByFilter(array('id' => $limitID), array(
            'id', 'cat_id', 'enabled', 'shop', 'free', 'reg1_country', 'reg2_region', 'reg3_city', 'group_id'
        ));
        if (empty($data)) return false;
        if ($data['enabled'] == $enabled) return true;
        if ($data['cat_id'] == 0) {
            # корневая категория
            if ($data['enabled']) {
                if ($data['reg1_country'] != 0) {
                    # региональную стоимость можно выключить (теряется активность, при включении)
                    $this->limitsPayedUpdate($limitID, array('enabled' => 0));
                    $this->db->update(TABLE_BBS_ITEMS_LIMITS, array('enabled' => 0), array('group_id' => $limitID));
                }
                # выключить нельзя
            } else {
                # включаем без проверок
                $this->limitsPayedUpdate($limitID, array('enabled' => 1));
            }
            return true;
        }
        if ($data['reg1_country']) {
            # изменение региональной стоимости
            if ($data['enabled']) {
                # выключаем, региональную выключить можно всегда
                $this->limitsPayedUpdate($limitID, array('enabled' => 0));
                $this->db->update(TABLE_BBS_ITEMS_LIMITS, array('enabled' => 0), array('group_id' => $limitID));
                return true;
            } else {
                # включаем, включить можно только, если включена основная
                $main = $this->limitsPayedByFilter(array(
                    'cat_id' => $data['cat_id'],
                    'shop'   => $data['shop'],
                    'free'   => $data['free'],
                    'reg1_country' => 0,
                    'reg2_region'  => 0,
                    'reg3_city'    => 0,
                ), array(
                    'id', 'enabled',
                ));
                if (empty($main)) {
                    $this->errors->impossible();
                    return false;
                }
                if ($main['enabled']) {
                    $this->limitsPayedUpdate($limitID, array('enabled' => 1));
                    $this->db->update(TABLE_BBS_ITEMS_LIMITS, array('enabled' => 1), array('group_id' => $limitID));
                    return true;
                } else {
                    $this->errors->set('Включите основную категорию');
                    return false;
                }
            }
        }

        $catParents = $this->catParentsID($data['cat_id']);
        array_unshift($catParents, 0);

        $cat = $this->catData($data['cat_id'], array('id', 'numleft', 'numright'));
        $catChildren = $this->catChildsTree($cat['numleft'], $cat['numright']);
        $catChildren = array_keys($catChildren);

        if ($data['enabled']) {
            # выключаем все с регионами
            $this->db->update(TABLE_BBS_ITEMS_LIMITS, array('enabled' => 0), array(
                'cat_id' => $data['cat_id'],
                'shop'   => $data['shop'],
                'free'   => $data['free'],
            ));

            # проверим активные купленные лимиты
            $filter = array(
                'active'  => 1,
                'shop'    => $data['shop'],
            );
            $field = $data['free'] ? 'free_id' : 'paid_id';
            $filter[$field] = $limitID;
            $cnt = $this->limitsPayedUserByFilter($filter);
            if ( ! $cnt) { # нет активных купленных лимитов
                return true;
            }

            # есть активные купленные лимиты
            $update = array();
            # найдем ближайшего родителя
            $parentsPoints = $this->limitsPayedByFilter(array(
                'cat_id'  => $catParents,
                'enabled' => 1,
                'shop'    => $data['shop'],
                'free'    => $data['free'],
                'reg1_country' => 0,
                'reg2_region'  => 0,
                'reg3_city'    => 0,
            ), array('id', 'cat_id'), false);
            $parentsPoints = func::array_transparent($parentsPoints, 'cat_id', true);
            $catParents = array_reverse($catParents);
            foreach ($catParents as $c) {
                if (isset($parentsPoints[$c])) {
                    $point = $parentsPoints[$c];
                    # данные о активности
                    $update = array($field => $point['id']);
                    break;
                }
            }

            # проверим существование поинта
            $cnt = $this->limitsPayedByFilter(array(
                'cat_id'  => $data['cat_id'],
                'enabled' => 1,
                'shop'    => $data['shop'],
                'free'    => ! $data['free'],
            ));
            if ( ! $cnt) {
                # поинт удалился, пометим активные купленные лимиты, для поиска нового поинта
                $update['need_check']  = 1;
            }
            if ( ! empty($update)) {
                $this->db->update(TABLE_BBS_ITEMS_LIMITS_USERS, $update, $filter);
            }
            if ($cnt) {
                # поинт не удалился
                return true;
            }
            # обновим отмеченные купленные лимиты
            config::save('bbs_limitsPayedToggle', serialize(array('id' => $limitID, 'enabled' => $enabled)));
            $toggling = ! $this->limitsPayedToggleCheck();
            return true;
        } else {
            # включаем
            # проверка создания нового поинта
            $point = $this->limitsPayedPointForCategory($data['cat_id'], $data['shop']);
            if ($point['point'] == $data['cat_id']) {
                # новый поинт не образовался, можно включать
                $this->limitsPayedUpdate($limitID, array('enabled' => 1));
                # обновим данные о активности
                if ($data['free']) {
                    $this->db->update(TABLE_BBS_ITEMS_LIMITS_USERS, array('free_id' => $limitID), array('cat_id' => $data['cat_id']));
                } else {
                    $this->db->update(TABLE_BBS_ITEMS_LIMITS_USERS, array('paid_id' => $limitID), array('cat_id' => $data['cat_id']));
                }
                return true;
            }

            # новый поинт
            $filter = array(
                'active'  => 1,
                'shop'    => $data['shop'],
                'cat_id'  => $catParents,
            );
            $cnt = $this->limitsPayedUserByFilter($filter);
            if ( ! $cnt) {
                # нет активных купленных лимитов, можно включать
                $this->limitsPayedUpdate($limitID, array('enabled' => 1));
                return true;
            }

            # есть активные купленные лимиты, помечаем для проверки
            $this->db->update(TABLE_BBS_ITEMS_LIMITS_USERS, array('need_check' => 1), $filter);

            # проверка апдейта активности существующих поинтов
            $filter = array(
                'active'  => 1,
                'shop'    => $data['shop'],
                'cat_id'  => $catChildren,
            );
            $cnt = $this->limitsPayedUserByFilter($filter);
            if ($cnt) {
                # есть активные купленные лимиты, помечаем для проверки
                $this->db->update(TABLE_BBS_ITEMS_LIMITS_USERS, array('need_check' => 2), $filter);
            }

            config::save('bbs_limitsPayedToggle', serialize(array('id' => $limitID, 'enabled' => $enabled)));
            $toggling = ! $this->limitsPayedToggleCheck();
            return true;
        }
    }

    /**
     * Проверка активных купленных лимитов, при включении / выключении поинта
     * @return bool true - проверка закончена, false - нет
     */
    public function limitsPayedToggleCheck()
    {
        $data = config::get('bbs_limitsPayedToggle', '', TYPE_STR);
        if (empty($data)) return true;

        $data = func::unserialize($data, array());
        if (empty($data['id'])) {
            config::save('bbs_limitsPayedToggle', '');
            return true;
        }
        $limitID = $data['id'];
        $enabled = $data['enabled'];

        $data = array();

        if ($enabled) {
            $finished = $this->limitsPayedToggleCheckEnableAdd($limitID, $data);
            $finished = $finished && $this->limitsPayedToggleCheckEnableUpdate($limitID, $data);
        } else {
            $finished = $this->limitsPayedToggleCheckDisable();
        }
        if ($finished) {
            $this->limitsPayedUpdate($limitID, array('enabled' => $enabled));
            config::save('bbs_limitsPayedToggle', '');
            return true;
        }
        return false;
    }

    /**
     * Проверка активных купленных лимитов, при включении поинта
     * в случае перекрытия старых поинтов, добавляет пользователю новые лимиты
     * @param integer $limitID ID включаемого поинта
     * @param array & $data кеш с данными о поинте, если есть
     * @return bool true - проверка закончена, false - нет
     */
    protected function limitsPayedToggleCheckEnableAdd($limitID, & $data)
    {
        # очередная порция поинтов для проверки
        $limits = $this->limitsPayedUserByFilter(array('need_check' => 1), array(
            'id', 'user_id', 'cat_id', 'shop', 'items', 'expire', 'paid_id', 'free_id'), false, 'LIMIT 100');
        if (empty($limits)) {
            return true;
        }

        # изменяемый лимит
        if (empty($data)) {
            $data = $this->limitsPayedByFilter(array('id' => $limitID), array(
                'cat_id', 'enabled', 'shop', 'free', 'items', 'reg1_country', 'reg2_region', 'reg3_city', 'group_id'
            ));
        }
        # какое поле будем обновлять
        $field = $data['free'] ? 'free_id' : 'paid_id';

        $point = $data['cat_id'];
        $catParents = $this->catParentsID($point);
        array_unshift($catParents, 0);
        $users = array();
        foreach ($limits as $v) {
            $user = $v['user_id'];
            if ( ! isset($users[$user])) {
                $shopID = 0;
                if ($data['shop']) {
                    $shopID = Users::model()->userData($v['user_id'], array('shop_id'));
                    $shopID = $shopID['shop_id'];
                }
                # найдем все поинты для опубликованых объявлений юзера
                $users[$user] = $this->limitsPayedCategoriesForUser(array(
                    'user_id' => $v['user_id'],
                    'shop_id' => $shopID,
                ));
                foreach ($users[$user] as & $p) {
                    if (empty($p['cats'])) continue;
                    if ( ! empty($p['buy'])) {
                        ksort($p['buy'], SORT_NUMERIC);
                    }
                    foreach ($p['cats'] as $c => $cnt) {
                        if (isset($p['parents'][ $c ])) continue;
                        $p['parents'][ $c ] = $this->catParentsID($c);
                        array_unshift($p['parents'][ $c ], 0);
                    }
                } unset($p);
            }
            if (isset($users[$user][ $v['cat_id'] ])) {
                $inCat = array();
                # новый поинт перекрывает существующий у юзера
                $p = $users[$user][ $v['cat_id'] ];
                if ( ! empty($p['parents'])) {
                    foreach ($p['parents'] as $c => $pp) {
                        if (in_array($point, $pp)) {
                            # и в нем есть опубликованные объявления
                            $inCat[] = $c;
                        }
                    }
                }
                if ( ! empty($inCat)) {
                    $cnt = 0;
                    foreach ($inCat as $c) {
                        # учитываем только объявления из перекрывающихся категорий
                        $cnt += isset($p['cats'][$c]) ? $p['cats'][$c] : 0;
                    }
                    if ($cnt > 0) {
                        foreach ($p['buy'] as $i => $vv) {
                            if ($i == $v['id']) {
                                # кол опубликованных объявлений, приходящихся на обрабатываемый лимит
                                # если лимит без опубликованных объявлений, он пропускается. Во включаемой категории действовать не будет.
                                if ($cnt >= $vv) {
                                    # весь лимит опубликован, меняем поинт
                                    $this->limitsPayedUserSave($v['id'], array(
                                        'cat_id'  => $point,
                                        $field    => $limitID,
                                    ));
                                } else if($cnt > 0) {
                                    # опубликована часть лимита, уменьшим основной и добавим новый на опубликованную часть
                                    $this->limitsPayedUserSave($v['id'], array(
                                        'items' => $v['items'] - $cnt
                                    ));
                                    $insert = array(
                                        'user_id' => $v['user_id'],
                                        'cat_id'  => $point,
                                        'items'   => $cnt,
                                        'shop'    => $v['shop'],
                                        'active'  => 1,
                                        'expire'  => $v['expire'],
                                        'paid_id' => $v['paid_id'],
                                        'free_id' => $v['free_id'],
                                    );
                                    $insert[$field] = $limitID;
                                    $this->limitsPayedUserSave(0, $insert);
                                }
                                break;
                            }
                            $cnt -= $vv;
                        }
                    }
                }
            }
            $this->limitsPayedUserSave($v['id'], array('need_check' => 0));
        }

        $cnt = $this->limitsPayedUserByFilter(array('need_check' => 1));
        if ( ! $cnt) {
            # все проверили
            return true;
        }
        return false;
    }

    /**
     * Проверка активных купленных лимитов, при включении поинта
     * в случае перекрытия старых поинтов, обновляет для лимита ID поинта
     * @param integer $limitID ID включаемого поинта
     * @param array & $data кеш с данными о поинте, если есть
     * @return bool true - проверка закончена, false - нет
     */
    protected function limitsPayedToggleCheckEnableUpdate($limitID, & $data)
    {
        # очередная порция поинтов для проверки
        $limits = $this->limitsPayedUserByFilter(array('need_check' => 2), array(
            'id', 'user_id', 'cat_id', 'shop', 'items', 'expire', 'paid_id', 'free_id'), false, 'LIMIT 100');
        if (empty($limits)) {
            return true;
        }

        # изменяемый лимит
        if (empty($data)) {
            $data = $this->limitsPayedByFilter(array('id' => $limitID), array(
                'cat_id', 'enabled', 'shop', 'free', 'reg1_country', 'reg2_region', 'reg3_city', 'group_id'
            ));
        }

        # какое поле будем обновлять
        $field = $data['free'] ? 'free_id' : 'paid_id';

        # родители включаемого поинта
        $parents = $this->catParentsID($data['cat_id']);
        array_unshift($parents, 0);
        $points = array();
        $cats = array();
        foreach ($limits as $v) {
            $point = $v[$field];
            if ( ! isset($points[$point])) {
                # родители старого поинта
                $c = $this->limitsPayedByFilter(array('id' => $point), array('cat_id'));
                $c = $c['cat_id'];
                $t = $c ? $this->catParentsID($c) : array();
                array_unshift($t, 0);
                $points[$point] = $t;
            }
            $c = $v['cat_id'];
            if ( ! isset($cats[$c])) {
                # подители категории лимита
                $t = $c ? $this->catParentsID($c) : array();
                array_unshift($t, 0);
                $cats[$c] = $t;
            }
            $point = $points[$point];
            $cat = $cats[$c];
            $replace = count($point) < count($parents);  # для замены новая точка должна быть ближе к листу, чем существующая
            # все три точки должны лежать на одной ветке (иметь одинаковых родителей)
            for ($i = 0; $i <= BBS::CATS_MAXDEEP; $i++) {
                if( ! $replace) break;
                $val = false;
                if (isset($parents[$i])) {
                    $val = $parents[$i];
                }
                if (isset($point[$i])) {
                    if ($val === false) {
                        $val = $point[$i];
                    } else {
                        $replace = $point[$i] == $val;
                    }
                }
                if (isset($cat[$i])) {
                    if ($val !== false && $replace) {
                        $replace = $cat[$i] == $val;
                    }
                }
            }

            # лимит проверен, обновляем
            $update = array('need_check' => 0);
            if ($replace) {
                $update[$field] = $limitID;
            }
            $this->limitsPayedUserSave($v['id'], $update);
        }

        $cnt = $this->limitsPayedUserByFilter(array('need_check' => 2));
        if ( ! $cnt) {
            # все проверили
            return true;
        }
        return false;
    }

    /**
     * Обновление поинта для отмеченных активных купленных лимитов, при выключении поинта
     * @return bool true - проверка закончена, false - нет
     */
    protected function limitsPayedToggleCheckDisable()
    {
        # очередная порция поинтов для проверки
        $limits = $this->limitsPayedUserByFilter(array('need_check' => 1), array(
            'id', 'user_id', 'paid_id', 'free_id'), false, 'LIMIT 100');
        if (empty($limits)) {
            return true;
        }

        $parents = function($id){
            $data = $this->limitsPayedByFilter(array('id' => $id), array('cat_id'));
            if ($data['cat_id']) {
                $parents = $this->catParentsID($data['cat_id']);
                array_unshift($parents, 0);
                return array_reverse($parents);
            }
            return array(0);
        };

        $users = array();
        # кеш поинтов
        $points = array();
        foreach ($limits as $v) {
            if ( ! isset($points[ $v['paid_id'] ])) {
                $points[ $v['paid_id'] ] = $parents($v['paid_id']);
            }
            if ( ! isset($points[ $v['free_id'] ])) {
                $points[ $v['free_id'] ] = $parents($v['free_id']);
            }
            $users[] = $v['user_id'];
            $paid = $points[ $v['paid_id'] ];
            $free = $points[ $v['free_id'] ];
            $update = array('need_check' => 0);
            # ставим категорию поинта, который глубже от корня
            if (count($paid) < count($free)) {
                $update['cat_id'] = reset($free);
            } else {
                $update['cat_id'] = reset($paid);
            }
            $this->limitsPayedUserSave($v['id'], $update);
        }

        # по возможности объеденим одинаковые лимиты (cat_id и expire совпадает)
        if ( ! empty($users)) {
            $users = array_unique($users);
            $merged = array();
            $deleted = array();
            $limits = $this->db->select('
                SELECT id, user_id, shop, cat_id, free_id, paid_id, items, expire
                FROM ' . TABLE_BBS_ITEMS_LIMITS_USERS . '
                WHERE ' . $this->db->prepareIN('user_id', $users).' AND active = 1
                ORDER BY user_id, shop, cat_id, free_id, paid_id, expire, id');
            $prev = false;
            foreach ($limits as $v) {
                if ($prev) {
                    $merge = true;
                    foreach (array('user_id', 'shop', 'cat_id', 'free_id', 'paid_id', 'expire') as $f) {
                        if ($prev[$f] != $v[$f]) {
                            $merge = false;
                            break;
                        }
                    }
                    if ($merge) {
                        $id = $prev['id'];
                        if ( ! isset($merged[$id])) {
                            $merged[$id] = $prev['items'];
                        }
                        $merged[$id] += $v['items'];
                        $deleted[] = $v['id'];
                        continue;
                    }
                }
                $prev = $v;
            }
            if ( ! empty($merged)) {
                $update = array();
                foreach ($merged as $k => $v) {
                    $update[] = 'WHEN '.$k.' THEN '.$v;
                }
                $this->db->exec('
                    UPDATE '.TABLE_BBS_ITEMS_LIMITS_USERS.' 
                    SET items = CASE id '.join(' ', $update).' ELSE items END
                    WHERE '.$this->db->prepareIN('id', array_keys($merged)));
            }
            if ( ! empty($deleted)) {
                $this->db->delete(TABLE_BBS_ITEMS_LIMITS_USERS, array('id' => $deleted));
            }
        }

        $cnt = $this->limitsPayedUserByFilter(array('need_check' => 1));
        if ( ! $cnt) {
            # все проверили
            return true;
        }
        return false;
    }

    /**
     * Обновление даты окончания действия активных купленных пакетов пользователей
     * @param integer $days кол дней до окончания действия пакета
     */
    public function limitsPayedUsersCheckDays($days)
    {
        if (empty($days)) return;
        $this->db->exec('UPDATE '.TABLE_BBS_ITEMS_LIMITS_USERS.' SET expire = :expire WHERE active = 1 AND expire < :now', array(
            ':expire' => date('Y-m-d H:i:s', strtotime('+'.$days.'days')),
            ':now' => $this->db->now(),
        ));
    }

    # ----------------------------------------------------------------
    # Услуги / Пакеты услуг

    /**
     * Данные об услугах (frontend)
     * @param integer $nTypeID ID типа Svc::type...
     * @return array
     */
    public function svcPromoteData($nTypeID)
    {
        if ($nTypeID == Svc::TYPE_SERVICE || empty($nTypeID)) {
            $aData = $this->db->tag('bbs-svc-promote-data-service')->select_key('SELECT id, keyword, price, settings
                            FROM ' . TABLE_SVC . ' WHERE type = :type',
                'keyword', array(':type' => Svc::TYPE_SERVICE)
            );

            if (empty($aData)) return array();

            foreach ($aData as $k => $v) {
                $sett = func::unserialize($v['settings']);
                unset($v['settings']);
                $aData[$k] = array_merge($v, $sett);
            }

            if (isset($aData['press']) && !BBS::PRESS_ON) {
                unset($aData['press']);
            }

            return $aData;

        } elseif ($nTypeID == Svc::TYPE_SERVICEPACK) {
            $aData = $this->db->tag('bbs-svc-promote-data-servicepack')->select('SELECT id, keyword, price, settings
                                FROM ' . TABLE_SVC . ' WHERE type = :type ORDER BY num',
                array(':type' => Svc::TYPE_SERVICEPACK)
            );

            foreach ($aData as $k => $v) {
                $sett = func::unserialize($v['settings']);
                unset($v['settings']);
                // оставляем текущую локализацию
                foreach ($this->langSvcPacks as $lngK => $lngV) {
                    $sett[$lngK] = (isset($sett[$lngK][LNG]) ? $sett[$lngK][LNG] : '');
                }
                $aData[$k] = array_merge($v, $sett);
            }

            return $aData;
        }
    }

    /**
     * Данные об услугах для формы добавления объявления (BBS), страницы продвижения, страницы списка услуг
     * @return array
     */
    public function svcData()
    {
        $aFilter = array('module' => 'bbs');
        $aFilter = $this->prepareFilter($aFilter, 'S');

        $aData = $this->db->select_key('SELECT S.*
                                    FROM ' . TABLE_SVC . ' S
                                    ' . $aFilter['where']
            . ' ORDER BY S.type, S.num',
            'id', $aFilter['bind'], 60
        );
        if (empty($aData)) return array();

        $oIcon = BBS::svcIcon();
        foreach ($aData as $k => &$v) {
            $v['id'] = intval($v['id']);
            $v['disabled'] = false;
            $sett = func::unserialize($v['settings']);
            unset($v['settings']);
            if (!empty($sett)) {
                $v = array_merge($sett, $v);
            }
            $v['title_view'] = (isset($v['title_view'][LNG]) ? $v['title_view'][LNG] : '');
            $v['description'] = (isset($v['description'][LNG]) ? $v['description'][LNG] : '');
            $v['description_full'] = (isset($v['description_full'][LNG]) ? $v['description_full'][LNG] : '');
            $v['icon_b'] = $oIcon->url($v['id'], $v['icon_b'], BBSSvcIcon::BIG);
            $v['icon_s'] = $oIcon->url($v['id'], $v['icon_s'], BBSSvcIcon::SMALL);
            # исключаем выключенные услуги
            if (empty($v['on']) ||
                ($v['id'] == BBS::SERVICE_PRESS && !BBS::PRESS_ON)
            ) {
                unset($aData[$k]);
            }
        }
        unset($v);

        return $aData;
    }

    /**
     * Получение региональной стоимости услуги в зависимости от категории/города
     * @param array $svcID ID услуг
     * @param integer $categoryID ID категории любого уровня
     * @param integer $cityID ID города
     * @return array - региональной стоимость услуг для указанной категории/региона
     */
    public function svcPricesEx(array $svcID, $categoryID, $cityID)
    {
        if (empty($svcID) || !$categoryID || !$cityID) return array();
        $result = array_fill_keys($svcID, 0);

        $cityData = Geo::regionData($cityID);
        if (empty($cityData) || !Geo::isCity($cityData) || !$cityData['pid']) return $result;

        $catParents = $this->catParentsID($categoryID);
        if (empty($catParents) || !isset($catParents[1])) return $result;
        $categoryID1 = $catParents[1];
        $categoryID2 = (isset($catParents[2]) ? $catParents[2] : 0);

        # получаем доступные варианты региональной стоимости услуг
        $prices = $this->db->select('SELECT * FROM ' . TABLE_BBS_SVC_PRICE . '
                    WHERE ' . $this->db->prepareIN('svc_id', $svcID) . ' AND category_id IN(:cat1, :cat2)
                    ORDER BY num',
            array(':cat1' => $categoryID1, ':cat2' => $categoryID2)
        );
        if (empty($prices)) return array();

        foreach ($svcID as $id) {
            # категория(2) + город
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == $cityID) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(2) + регион(область)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == $cityData['pid']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(2) + страна
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == $cityData['country']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1) + город
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == $cityID) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1) + регион(область)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == $cityData['pid']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1) + страна
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == $cityData['country']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(2)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == 0) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == 0) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
        }

        return $result;
    }

    /**
     * Загрузка настроек региональной стоимости услуг, для редактирования
     * @return array
     */
    public function svcPriceExEdit()
    {
        $aResult = array();
        $aData = $this->db->select('SELECT * FROM ' . TABLE_BBS_SVC_PRICE . ' ORDER BY svc_id, id, num');
        if (!empty($aData)) {
            $aRegionsID = array();
            foreach ($aData as $v) {
                if (!isset($aResult[$v['svc_id']])) {
                    $aResult[$v['svc_id']] = array();
                }
                if (!isset($aResult[$v['svc_id']][$v['id']])) {
                    $aResult[$v['svc_id']][$v['id']] = array(
                        'price'   => $v['price'],
                        'cats'    => array(),
                        'regions' => array()
                    );
                }
                if ($v['category_id'] > 0) $aResult[$v['svc_id']][$v['id']]['cats'][] = $v['category_id'];
                if ($v['region_id'] > 0) {
                    $aResult[$v['svc_id']][$v['id']]['regions'][] = $v['region_id'];
                    $aRegionsID[] = $v['region_id'];
                }
            }

            $aRegionsID = array_unique($aRegionsID);
            $aLvl = array(Geo::lvlRegion, Geo::lvlCity);
            $bCountries = Geo::coveringType(Geo::COVERING_COUNTRIES);
            if($bCountries){
                $aLvl[] = Geo::lvlCountry;
            }
            $aRegionsData = Geo::model()->regionsList($aLvl, array('id' => $aRegionsID));
            if($bCountries){
                $aCountries = Geo::countriesList();
            }

            foreach ($aResult as &$v) {
                foreach ($v as &$vv) {
                    $vv['cats'] = array_unique($vv['cats']);
                    $vv['regions'] = array_unique($vv['regions']);
                    $regionsResult = array();
                    foreach ($vv['regions'] as $id) {
                        if (isset($aRegionsData[$id])) {
                            if($bCountries){
                                $r = $aRegionsData[$id];
                                if($r['numlevel'] == Geo::lvlCountry){
                                    $t = $r['title'];
                                }else{
                                    $t = $aCountries[ $r['country'] ]['title'].' / '.$r['title'];
                                }
                            }else{
                                $t = $aRegionsData[$id]['title'];
                            }
                            $regionsResult[] = array('id' => $id, 't' => $t);
                        }
                    }
                    $vv['regions'] = $regionsResult;
                }
                unset($vv);
            }
            unset($v);
        }

        return $aResult;
    }

    /**
     * Сохранение настроек региональной стоимости услуг
     * @param integer $nSvcID ID услуг
     * @param array $aData данные
     */
    public function svcPriceExSave($nSvcID, array $aData)
    {
        if ($nSvcID <= 0) return;

        $sql = array();
        $id = 1;
        $num = 1;
        foreach ($aData as $v) {
            if ($v['price'] <= 0 || empty($v['cats'])) {
                continue;
            }

            $v['cats'] = array_unique($v['cats']);
            $v['regions'] = array_unique($v['regions']);
            foreach ($v['cats'] as $cat) {
                foreach ($v['regions'] as $region) {
                    $sql[] = array(
                        'id'          => $id,
                        'svc_id'      => $nSvcID,
                        'price'       => $v['price'],
                        'category_id' => $cat,
                        'region_id'   => $region,
                        'num'         => $num++,
                    );
                }
                if (empty($v['regions'])) {
                    $sql[] = array(
                        'id'          => $id,
                        'svc_id'      => $nSvcID,
                        'price'       => $v['price'],
                        'category_id' => $cat,
                        'region_id'   => 0,
                        'num'         => $num++,
                    );
                }
            }
            $id++;
        }

        $this->db->delete(TABLE_BBS_SVC_PRICE, array('svc_id' => $nSvcID));
        if (!empty($sql)) {
            foreach (array_chunk($sql, 25) as $v) {
                $this->db->multiInsert(TABLE_BBS_SVC_PRICE, $v);
            }
        }
    }

    /**
     * Период: 1 раз в сутки
     */
    public function svcCron()
    {
        $sNow = $this->db->now();
        $sEmpty = '0000-00-00 00:00:00';
        $optsContext = __FUNCTION__;

        # Деактивируем услугу "Выделение"
        $this->itemsUpdateByFilter(array(
            'svc = (svc - :id)',
            'svc_marked_to' => $sEmpty,
        ), array(
            'svc >= :id',
            '(svc & :id)',
            'svc_marked_to <= :now',
        ), array(
            'context' => $optsContext,
            'tag' => 'bbs-svc-cron-service-mark',
            'bind' => array(':id'=>BBS::SERVICE_MARK, ':now' => $sNow),
        ));

        # Деактивируем услугу "Срочно"
        $this->itemsUpdateByFilter(array(
            'svc = (svc - :id)',
            'svc_quick_to' => $sEmpty,
        ), array(
            'svc >= :id',
            '(svc & :id)',
            'svc_quick_to <= :now',
        ), array(
            'context' => $optsContext,
            'tag' => 'bbs-svc-cron-service-quick',
            'bind' => array(':id'=>BBS::SERVICE_QUICK, ':now' => $sNow),
        ));

        # Деактивируем услугу "Закрепление"
        $this->itemsUpdateByFilter(array(
            'svc = (svc - :id)',
            'svc_fixed' => 0,
            'svc_fixed_to' => $sEmpty,
            'svc_fixed_order' => $sEmpty,
        ), array(
            'svc >= :id',
            'svc_fixed' => 1,
            'svc_fixed_to <= :now',
        ), array(
            'context' => $optsContext,
            'tag' => 'bbs-svc-cron-service-fix',
            'bind' => array(':id'=>BBS::SERVICE_FIX, ':now' => $sNow),
        ));

        # Деактивируем услугу "Премиум"
        $this->itemsUpdateByFilter(array(
            'svc = (svc - :id)',
            'svc_premium' => 0,
            'svc_premium_to' => $sEmpty,
            'svc_premium_order' => $sEmpty,
        ), array(
            'svc >= :id',
            'svc_premium' => 1,
            'svc_premium_to <= :now',
        ), array(
            'context' => $optsContext,
            'tag' => 'bbs-svc-cron-service-premium',
            'bind' => array(':id'=>BBS::SERVICE_PREMIUM, ':now' => $sNow),
        ));

        # Снимаем пометку об активации услуги "Поднятие", выполненную 3 и более дней назад
        $this->itemsUpdateByFilter(array(
            'svc = (svc - :id)',
        ), array(
            'svc >= :id',
            '(svc & :id)',
            'svc_up_date <= :now',
        ), array(
            'context' => $optsContext,
            'tag' => 'bbs-svc-cron-service-up',
            'bind' => array(':id'=>BBS::SERVICE_UP, ':now' => date('Y-m-d', strtotime('-3 days'))),
        ));

        # Разрешаем повторную печать в прессе для отправленных на печать на следующий день
        $this->itemsUpdateByFilter(array(
            'svc_press_date_last = svc_press_date',
            'svc_press_date' => $sEmpty,
            'svc_press_status' => 0,
        ), array(
            'svc_press_status' => BBS::PRESS_STATUS_PUBLICATED,
            'svc_press_date <= :now',
        ), array(
            'context' => $optsContext,
            'tag' => 'bbs-svc-cron-service-press',
            'bind' => array(':now' => date('Y-m-d')),
        ));

        # Дополнительно
        bff::hook('bbs.svc.cron');

    }

    public function getLocaleTables()
    {
        $data = array(
            TABLE_BBS_CATEGORIES                => array(
                'type' => 'table',
                'fields' => $this->langCategories,
                'title' => _t('bbs', 'Категории'),
                'fields-serialized' => array(
                    'price_sett' => array('title', 'mod_title'),
                ),
            ),
            TABLE_BBS_CATEGORIES_TYPES          => array('type' => 'fields', 'fields' => $this->langCategoriesTypes, 'title' => _t('bbs', 'Типы категорий')),
            TABLE_BBS_CATEGORIES_DYNPROPS       => array(
                'type'   => 'fields',
                'fields' => array(
                    'title'       => TYPE_NOTAGS,
                    'description' => TYPE_NOTAGS
                ),
                'title' => _t('bbs', 'Дин. свойства'),
            ),
            TABLE_BBS_CATEGORIES_DYNPROPS_MULTI => array(
                'type' => 'fields',
                'fields' => array('name' => TYPE_NOTAGS),
                'title' => _t('bbs', 'Дин. свойства (значения)'),
                'id' => array('dynprop_id', 'value'),
            ),
        );
        if ( ! BBS::CATS_TYPES_EX) {
            unset($data[TABLE_BBS_CATEGORIES_TYPES]);
        }
        return $data;
    }
}