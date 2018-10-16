<?php

define('TABLE_SHOPS',                   DB_PREFIX . 'shops'); // магазины
define('TABLE_SHOPS_LANG',              DB_PREFIX . 'shops_lang'); // магазины - lang
define('TABLE_SHOPS_REQUESTS',          DB_PREFIX . 'shops_requests'); // заявки на прикрепление
define('TABLE_SHOPS_CATEGORIES',        DB_PREFIX . 'shops_categories'); // категории магазинов
define('TABLE_SHOPS_CATEGORIES_LANG',   DB_PREFIX . 'shops_categories_lang'); // категории магазинов - lang
define('TABLE_SHOPS_IN_CATEGORIES',     DB_PREFIX . 'shops_in_categories'); // связь магазинов с категориями
define('TABLE_SHOPS_IN_CATEGORIES_BBS', DB_PREFIX . 'shops_in_categories_bbs'); // связь магазинов с категориями объявлений (bbs)
define('TABLE_SHOPS_CLAIMS',            DB_PREFIX . 'shops_claims'); // жалобы на магазины
define('TABLE_SHOPS_SVC_PRICE',         DB_PREFIX . 'shops_svc_price'); // настройки региональной стоимости платных услуг
define('TABLE_SHOPS_ABONEMENTS',        DB_PREFIX . 'shops_abonements'); // тарифы услуги "Абонемент"

use bff\db\NestedSetsTree;

class ShopsModel_ extends Model
{
    /** @var ShopsBase */
    public $controller;

    /** @var NestedSetsTree для категорий */
    public $treeCategories;
    public $langCategories = array(
        'title'        => TYPE_NOTAGS, # название
        'mtitle'       => TYPE_NOTAGS, # meta-title
        'mkeywords'    => TYPE_NOTAGS, # meta-keywords
        'mdescription' => TYPE_NOTAGS, # meta-description
        'seotext'      => TYPE_STR, # seotext
        'titleh1'      => TYPE_STR, # H1
        'breadcrumb'   => TYPE_STR, # хлебная крошка
    );

    public $langShops = array(
        'title'         => array(TYPE_TEXT, 'len' => 50, 'len.sys' => 'shops.form.title.limit'), # название
        'title_edit'    => TYPE_STR,
        'descr'         => array(TYPE_TEXT, 'len' => 600, 'len.sys' => 'shops.form.descr.limit', 'activate-links'=>true), # описание (чем занимается)
        'addr_addr'     => array(TYPE_TEXT, 'len' => 300, 'len.sys' => 'shops.form.addr.limit'), # точный адрес
    );

    public $langSvcServices = array(
        'title_view'       => TYPE_STR, # название
        'description'      => TYPE_STR, # описание (краткое)
        'description_full' => TYPE_STR, # описание (подробное)
    );

    public $langSvcPacks = array(
        'title_view'       => TYPE_NOTAGS, # название
        'description'      => TYPE_STR, # описание (краткое)
        'description_full' => TYPE_STR, # описание (подробное)
    );

    public $langAbonements = array(
        'title'            => TYPE_STR, # название
    );

    public function init()
    {
        parent::init();

        # подключаем nestedSets для категорий
        if (Shops::categoriesEnabled()) {
            $this->treeCategories = new NestedSetsTree(TABLE_SHOPS_CATEGORIES);
            $this->treeCategories->init();
        }
    }

    /**
     * Список магазинов по фильтру (admin)
     * @param array $aFilterRaw фильтр списка (требует подготовки)
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function shopsListing(array $aFilterRaw, $bCount = false, $sqlOrderBy = '', $sqlLimit = '')
    {
        $aFilter = array();
        foreach (array('status', ':status', 'moderated', ':owner') as $k) {
            if (isset($aFilterRaw[$k])) {
                $aFilter[$k] = $aFilterRaw[$k];
            }
        }
        if (!empty($aFilterRaw['q'])) {
            $aFilter[':q'] = array(
                '(S.id = :q_id OR SL.title LIKE :q_title)',
                ':q_id'    => intval($aFilterRaw['q']),
                ':q_title' => '%' . $aFilterRaw['q'] . '%',
            );
        }
        if (!empty($aFilterRaw['u'])) {
            $aFilter[':u'] = array(
                '(S.user_id = :u_id OR U.email LIKE :u_email)',
                ':u_id'    => intval($aFilterRaw['u']),
                ':u_email' => $aFilterRaw['u'] . '%',
            );
        }

        if ($bJoinCategories = !empty($aFilterRaw['cat'])) {
            $aFilter[':cat'] = array(
                'C.category_id = :cat',
                ':cat' => $aFilterRaw['cat'],
            );
            $categoriesTable = (Shops::categoriesEnabled() ? TABLE_SHOPS_IN_CATEGORIES :
                TABLE_SHOPS_IN_CATEGORIES_BBS);
        }
        $aFilter = $this->prepareFilter($aFilter, 'S');

        if ($bCount) {
            return (integer)$this->db->tag('shops-shops-listing-count', array('filter'=>&$aFilter))->one_data('SELECT COUNT(S.id)
                                FROM ' . TABLE_SHOPS . ' S
                                    LEFT JOIN ' . TABLE_USERS . ' U ON S.id = U.shop_id
                                    INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL'). '
                                     ' . ($bJoinCategories ? ' INNER JOIN ' . $categoriesTable . ' C ON S.id = C.shop_id' : '') . '
                                ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        return $this->db->tag('shops-shops-listing-data', array('filter'=>&$aFilter))->select('SELECT S.id, S.created, SL.title, S.link, S.status, S.moderated,
                    U.login as user_login, U.email, U.user_id
                    FROM ' . TABLE_SHOPS . ' S
                         LEFT JOIN ' . TABLE_USERS . ' U ON S.id = U.shop_id
                         INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL'). '
                         ' . ($bJoinCategories ? ' INNER JOIN ' . $categoriesTable . ' C ON S.id = C.shop_id' : '') . '
                    ' . $aFilter['where'] . '
                    GROUP BY S.id
                    ORDER BY S.' . $sqlOrderBy . ' ' . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Данные о магазинах для списка пользователей (admin)
     * @param array $aUserID ID пользователей
     * @return array|mixed
     */
    public function shopsDataToUsersListing(array $aUserID)
    {
        if (empty($aUserID)) {
            return array();
        }
        return $this->db->tag('shops-shops-data-to-users-listing')->select_key('SELECT S.id, S.user_id, S.link, SL.title
            FROM ' . TABLE_SHOPS . ' S INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL'). '
            WHERE S.user_id IN (' . join(',', $aUserID) . ')', 'user_id'
        );
    }

    /**
     * Данные о магазинах для списка сообщений (Мои сообщения)
     * @param array $aShopID ID магазинов
     * @return array|mixed
     */
    public function shopsDataToMessages(array $aShopID)
    {
        if (empty($aShopID)) {
            return array();
        }
        return $this->db->tag('shops-shops-data-to-messages')->select_key('SELECT S.id, SL.title, S.logo, S.keyword
            FROM ' . TABLE_SHOPS . ' S INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL'). '
            WHERE S.id IN (' . join(',', $aShopID) . ')', 'id'
        );
    }

    /**
     * Список магазинов по фильтру (frontend)
     * @param array $aFilter фильтр списка
     * @param integer $nCategoryID ID категории
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @param string $sqlOrderBy
     * @return mixed
     */
    public function shopsList(array $aFilter, $nCategoryID, $bCount = false, $sqlLimit = '', $sqlOrderBy = 'svc_fixed DESC, S.svc_fixed_order DESC, S.items_last DESC', $orderByRating = false)
    {
        $sqlFields = array(
            'id',
            'id_ex',
            'title',
            'link',
            'descr',
            'logo',
            'site',
            'phones',
            'contacts',
            'social',
            'addr_addr',
            'addr_lat',
            'addr_lon',
            'region_id',
            'items',
        );

        foreach ($sqlFields as & $v) {
            $v = (array_key_exists($v, $this->langShops) ? 'SL.' : 'S.').$v;
        } unset($v);
        $sqlFields = join(',', $sqlFields) . ', R.title_' . LNG . ' as region_title';
        $sqlFields .= ', ((S.svc & ' . Shops::SERVICE_MARK . ') > 0) as svc_marked
                       , ((S.svc & ' . Shops::SERVICE_FIX . ') > 0) as svc_fixed';

        $sqlOrderBy = $orderByRating ? "shop_avarage_value DESC, ".$sqlOrderBy : $sqlOrderBy;

        if ($nCategoryID > 0) {
            $sCategoriesTable = (Shops::categoriesEnabled() ? TABLE_SHOPS_IN_CATEGORIES : TABLE_SHOPS_IN_CATEGORIES_BBS);

            $aFilter[':cat'] = array('C.category_id = :cat', ':cat' => $nCategoryID);
            $aFilter = $this->prepareFilter($aFilter, 'S');
            if ($bCount) {
                return (integer)$this->db->one_data('SELECT COUNT(S.id)
                                    FROM ' . TABLE_SHOPS . ' S
                                         INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL').'
                                         INNER JOIN ' . $sCategoriesTable . ' C ON S.id = C.shop_id
                                    ' . $aFilter['where'],
                    $aFilter['bind']
                );
            }

            return $this->db->tag('shops-shops-list-data-cat', array('fields'=>&$sqlFields,'filter'=>&$aFilter))->select('SELECT ' . $sqlFields . ',
                                        SUM(IR.value)/COUNT(IR.value) as shop_avarage_value
                                    FROM ' . TABLE_SHOPS . ' S
                                         INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL').'
                                         INNER JOIN ' . $sCategoriesTable . ' C ON S.id = C.shop_id
                                         LEFT JOIN ' . TABLE_REGIONS . ' R ON R.id = S.region_id
                                         LEFT JOIN ' . TABLE_BBS_ITEMS.' I ON S.id = I.shop_id
                                         LEFT JOIN ' .TABLE_BBS_ITEMS_RATINGS.' IR ON I.id = IR.item_id
                                    ' . $aFilter['where'] . '
                                    GROUP BY S.id
                                    ORDER BY ' . $sqlOrderBy . ' ' . $sqlLimit,
                $aFilter['bind']
            );
        } else {
            $aFilter = $this->prepareFilter($aFilter, 'S');
            if ($bCount) {
                return (integer)$this->db->one_data('SELECT COUNT(S.id)
                                    FROM ' . TABLE_SHOPS . ' S
                                        INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL').'
                                    ' . $aFilter['where'],
                    $aFilter['bind']
                );
            } else {
                return $this->db->tag('shops-shops-list-data', array('fields'=>&$sqlFields,'filter'=>&$aFilter))->select('SELECT ' . $sqlFields . ',
                                SUM(IR.value)/COUNT(IR.value) as shop_avarage_value
                            FROM ' . TABLE_SHOPS . ' S
                                INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL'). '
                                LEFT JOIN ' . TABLE_REGIONS . ' R ON R.id = S.region_id
                                LEFT JOIN ' . TABLE_BBS_ITEMS.' I ON S.id = I.shop_id
                                LEFT JOIN ' .TABLE_BBS_ITEMS_RATINGS.' IR ON I.id = IR.item_id
                            ' . $aFilter['where'] . '
                            GROUP BY S.id
                            ORDER BY ' . $sqlOrderBy . ' ' . $sqlLimit, $aFilter['bind']
                );
            }
        }
    }

    /**
     * Формирование данных о магазинах для файла Sitemap.xml
     * @param array $aFilter фильтр
     * @param string $sPriority приоритетность url
     * @return callable callback-генератор строк вида array [['l'=>'url страницы','m'=>'дата последних изменений'],...]
     */
    public function shopsSitemapXmlData(array $aFilter = array(), $sPriority = '')
    {
        $aFilter['status'] = Shops::STATUS_ACTIVE;
        if (Shops::premoderation()) {
            $aFilter[] = 'moderated > 0';
        }

        return function($count = false, callable $callback = null) use ($aFilter, $sPriority) {
            if ($count) {
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->one_data('SELECT COUNT(*) FROM '.TABLE_SHOPS.' '.$aFilter['where'], $aFilter['bind']);
            } else {
                $aFilter = $this->prepareFilter($aFilter, '', array(
                    ':format' => '%Y-%m-%d',
                ));
                $this->db->tag('shops-shops-sitemap-xml-data', array('filter'=>&$aFilter))->select_iterator('
                    SELECT link as l, DATE_FORMAT(modified, :format) as m
                    FROM ' . TABLE_SHOPS . '
                    '. $aFilter['where'] .'
                    ORDER BY modified DESC',
                $aFilter['bind'],
                function(&$item) use (&$callback, $sPriority){
                    $item['l'] = Shops::urlDynamic($item['l']);
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
     * Данные о магазине
     * @param integer $nShopID ID магазина
     * @param array $aFields ключи требуемых данных
     * @param bool $bEdit для редактирования
     * @return array|mixed
     */
    public function shopData($nShopID, $aFields = array(), $bEdit = false)
    {
        if (empty($aFields)) {
            return array();
        }
        if (!is_array($aFields)) {
            if($aFields == '*') {
                $aFields = array('*');
            } else {
                $aFields = array($aFields);
            }
        }
        foreach ($aFields as & $v) {
            if (strpos($v, '.')) continue;
            if ($v == '*') $aFields[] = 'SL.*';
            $v = (array_key_exists($v, $this->langShops) ? 'SL.' : 'S.').$v;
        } unset($v);


        $aData = $this->db->one_array('SELECT ' . join(',', $aFields) . '
                       FROM ' . TABLE_SHOPS . ' S 
                           INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL'). '
                       WHERE S.id = :id
                       LIMIT 1', array(':id' => $nShopID)
        );
        if (empty($aData)) {
            return array();
        }

        if ($bEdit) {
            # берем title для редактирования
            if (Shops::titlesLang()) {
                $this->db->langSelect($aData['id'], $aData, $this->langShops, TABLE_SHOPS_LANG);
                foreach ($aData['title'] as $k => & $v) {
                    if (isset($aData['title_edit'][$k])) {
                        $v = $aData['title_edit'][$k];
                    }
                } unset($v);
            } else {
                $aData['title'] = $aData['title_edit'];
            }
            $aData['region_title'] = (isset($aData['region_id']) ? Geo::regionTitle($aData['region_id']) : '');
        }

        if (isset($aData['social'])) {
            $aData['social'] = (!empty($aData['social']) ? func::unserialize($aData['social']) : array());
        }
        if (isset($aData['phones'])) {
            $aData['phones'] = (!empty($aData['phones']) ? func::unserialize($aData['phones']) : array());
        }
        if (isset($aData['contacts'])) {
            $aData['contacts'] = Users::contactsToArray($aData['contacts']);
        }
        if (!empty($aData['link'])) {
            $aData['link'] = Shops::urlDynamic($aData['link']);
        }

        return $aData;
    }

    /**
     * Данные о магазине для правого блока (просмотр страниц магазина)
     * @param integer $nShopID ID магазина
     * @return array
     */
    public function shopDataSidebar($nShopID)
    {
        $aData = $this->shopData($nShopID, array('*', 'link as link_dynamic'));
        if (empty($aData)) {
            return array();
        }

        $aData['logo_small'] = ShopsLogo::url($nShopID, $aData['logo'], ShopsLogo::szList);
        $aData['logo'] = ShopsLogo::url($nShopID, $aData['logo'], ShopsLogo::szView);
        $aData['region_title'] = (isset($aData['region_id']) ? Geo::regionTitle($aData['region_id']) : '');
        $aData['country'] = Geo::regionData($aData['reg1_country']);
        $aData['region'] = Geo::regionData($aData['reg2_region']);
        $aData['city'] = Geo::regionData($aData['reg3_city']);

        return $aData;
    }

    /**
     * Получение значения мультиязычного поля магазина для указанного языка
     * @param integer $shopID ID магазина
     * @param string $lang ключ языка
     * @param string $field ключ поля
     * @return string
     */
    public function shopDataLangField($shopID, $lang, $field = 'title')
    {
        if ( ! $shopID) return '';

        $langCurrent = $this->locale->getCurrentLanguage();
        $data = $this->db->select_key('SELECT lang, '.$field.' FROM '.TABLE_SHOPS_LANG.' WHERE id = :id', 'lang', array(':id' => $shopID));
        if ( ! empty($data)) {
            if (isset($data[ $lang ])) {
                return $data[ $lang ][$field];
            } else if (isset($data[ $langCurrent ])) {
                return $data[ $langCurrent ][$field];
            } else {
                $data = reset($data);
                return $data[$field];
            }
        }
        return '';
    }

    /**
     * Сохранение данных магазина
     * @param integer $nShopID ID магазина
     * @param array $aData данные
     * @return mixed
     */
    public function shopSave($nShopID, array $aData)
    {
        if (isset($aData['social'])) {
            if (!is_array($aData['social'])) {
                $aData['social'] = array();
            }
            $aData['social'] = serialize($aData['social']);
        }
        if (isset($aData['cats'])) {
            $aCats = $aData['cats'];
            unset($aData['cats']);
        }
        if (isset($aData['status']) || isset($aData['status_prev'])) {
            $aData['status_changed'] = $this->db->now();
        }

        foreach ($this->langShops as $k => $v) {
            if (isset($aData[$k]) && ! is_array($aData[$k])) {
                $val = $aData[$k];
                $aData[$k] = array();
                foreach ($this->locale->getLanguages() as $l) {
                    $aData[$k][$l] = $val;
                }
            }
        }

        if ($nShopID) {
            $aData['modified'] = $this->db->now();

            $this->db->langUpdate($nShopID, $aData, $this->langShops, TABLE_SHOPS_LANG);
            $aDataNonLang = array_diff_key($aData, $this->langShops);
            $res = $this->db->update(TABLE_SHOPS, $aDataNonLang, array('id' => $nShopID));
            \bff::hook('shops.shop.save', $nShopID, array('data'=>&$aData));
        } else {
            $aData['created'] = $this->db->now();
            $aData['modified'] = $this->db->now();
            $aData['id_ex'] = func::generator(6);
            $aDataNonLang = array_diff_key($aData, $this->langShops);
            $res = $nShopID = $this->db->insert(TABLE_SHOPS, $aDataNonLang, 'id');
            if ($nShopID && isset($aData['link'])) {
                $this->db->langInsert($nShopID, $aData, $this->langShops, TABLE_SHOPS_LANG);
                # дополняем ссылку
                $this->db->update(TABLE_SHOPS, array(
                        'link' => strtr($aData['link'], ['{keyword}'=>$aData['keyword'], '{id}'=>$nShopID])
                    ), array('id' => $nShopID)
                );
                \bff::hook('shops.shop.create', $nShopID, array('data'=>&$aData));
            }
        }
        if (Shops::categoriesEnabled() && isset($aCats) && $nShopID) {
            $this->shopSaveCategories($nShopID, $aCats);
        }

        return $res;
    }

    /**
     * Сохранение связи магазина с категориями
     * @param integer $nShopID ID магазина
     * @param array $aCategoriesID @ref ID категорий
     */
    public function shopSaveCategories($nShopID, array $aCategoriesID)
    {
        $this->db->delete(TABLE_SHOPS_IN_CATEGORIES, array('shop_id' => $nShopID));

        $aCategoriesID = array_unique($aCategoriesID);
        if (empty($aCategoriesID)) {
            return;
        }
        # проверяем допустимый лимит
        if (($nLimit = Shops::categoriesLimit()) && sizeof($aCategoriesID) > $nLimit) {
            $aCategoriesID = array_slice($aCategoriesID, 0, $nLimit);
        }

        $sql = array();
        $i = 1;
        foreach ($aCategoriesID as $v) {
            $sql[] = array(
                'shop_id'     => $nShopID,
                'category_id' => $v,
                'is_parent'   => 0,
                'num'         => $i++,
            );
        }
        # сохраняем связь (по 25 за запрос)
        foreach (array_chunk($sql, 25) as $v) {
            $this->db->multiInsert(TABLE_SHOPS_IN_CATEGORIES, $v);
        }

        # дополнительно сохраняем связь с основными категориями
        $parentsID = $this->db->select_one_column('SELECT pid FROM ' . TABLE_SHOPS_CATEGORIES . '
                            WHERE id IN(' . join(',', $aCategoriesID) . ')'
        );
        if (!empty($parentsID)) {
            $parentsID = array_unique($parentsID);
            $sql = array();
            foreach ($parentsID as $v) {
                if ($v != Shops::CATS_ROOTID) {
                    $sql[] = array(
                        'shop_id'     => $nShopID,
                        'category_id' => $v,
                        'is_parent'   => 1,
                    );
                }
            }
            foreach (array_chunk($sql, 25) as $v) {
                $this->db->multiInsert(TABLE_SHOPS_IN_CATEGORIES, $v);
            }
        }
    }

    /**
     * Формирование списка категорий в которые входит магазин
     * @param integer $nShopID ID магазина
     * @param string $sIconSize размер иконки
     * @return array
     */
    public function shopCategoriesIn($nShopID, $sIconSize)
    {
        if (!$nShopID) {
            return array();
        }
        $sIconField = 'C.icon_' . $sIconSize . ' as icon';
        $aData = (array)$this->db->select('SELECT C.id, C.pid, L.title, ' . $sIconField . '
                FROM ' . TABLE_SHOPS_IN_CATEGORIES . ' I
                 INNER JOIN ' . TABLE_SHOPS_CATEGORIES . ' C ON C.id = I.category_id
                 INNER JOIN ' . TABLE_SHOPS_CATEGORIES_LANG . ' L ON L.id = I.category_id AND L.lang = :lang
                WHERE I.shop_id = :shop AND I.is_parent = 0
                ORDER BY I.num', array(':shop' => $nShopID, ':lang' => LNG)
        );
        if (!empty($aData)) {
            $oCategoryIcon = Shops::categoryIcon(0);
            $aParentID = array();
            foreach ($aData as &$v) {
                $v['icon'] = $oCategoryIcon->url($v['id'], $v['icon'], $sIconSize);
                if ($v['pid'] > Shops::CATS_ROOTID) {
                    $aParentID[] = $v['pid'];
                }
            }
            unset($v);
            if (!bff::adminPanel() && Shops::CATS_MAXDEEP == 2 && sizeof($aParentID) > 0) {
                $aParentData = (array)$this->db->select_key('SELECT C.id, L.title, ' . $sIconField . '
                    FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                         ' . TABLE_SHOPS_CATEGORIES_LANG . ' L
                    WHERE C.id IN (' . join(',', $aParentID) . ')
                      AND C.id = L.id AND L.lang = :lang', 'id',
                    array(':lang' => LNG)
                );
                if (!empty($aParentData)) {
                    foreach ($aData as &$v) {
                        if (isset($aParentData[$v['pid']])) {
                            $v['ptitle'] = $aParentData[$v['pid']]['title'];
                            $v['picon'] = $oCategoryIcon->url($v['pid'], $aParentData[$v['pid']]['icon'], $sIconSize);
                        }
                    }
                    unset($v);
                }
            }
        }

        return $aData;
    }

    /**
     * Подсчет кол-ва объявлений, связанных с магазином
     * @param integer $nShopID
     * @return integer
     */
    public function shopItemsCounter($nShopID)
    {
        if (empty($nShopID) || $nShopID < 0) {
            return 0;
        }

        return BBS::model()->itemsCount(array('shop_id' => $nShopID));
    }

    /**
     * Получение информации об активации магазина
     * @param integer $nShopID ID магазина
     * @return boolean
     */
    public function shopActive($nShopID)
    {
        return ($this->shopStatus($nShopID) == Shops::STATUS_ACTIVE);
    }

    /**
     * Текущий статус магазина
     * @param integer $nShopID ID магазина
     * @return integer
     */
    public function shopStatus($nShopID)
    {
        $aShopData = $this->shopData($nShopID, array('status'));
        if (empty($aShopData)) {
            return 0;
        }

        return $aShopData['status'];
    }

    /**
     * Получаем общее кол-во активных магазинов
     * @param array $aFilter доп. фильтр
     * @return integer
     */
    public function shopsActiveCounter(array $aFilter = array())
    {
        $aFilter['status'] = Shops::STATUS_ACTIVE;
        $aFilter = $this->prepareFilter($aFilter);

        return (int)$this->db->one_data('SELECT COUNT(id) FROM ' . TABLE_SHOPS . '
                ' . $aFilter['where'], $aFilter['bind']
        );
    }

    /**
     * Актуализация счетчиков магазинов (cron)
     * Рекомендуемый период: раз в 10 минут
     */
    public function shopsCronCounters()
    {
        if (Shops::categoriesEnabled()) {
            # пересчет кол-ва магазинов в категориях магазинов (TABLE_SHOPS_CATEGORIES::shops)
            $this->db->exec('UPDATE ' . TABLE_SHOPS_CATEGORIES . ' SET shops = 0');
            $this->db->exec('UPDATE ' . TABLE_SHOPS_CATEGORIES . ' C,
                     ( SELECT SC.category_id as id, COUNT(DISTINCT SC.shop_id) as shops
                       FROM ' . TABLE_SHOPS_IN_CATEGORIES . ' as SC
                         INNER JOIN ' . TABLE_SHOPS . ' S ON SC.shop_id = S.id AND S.status = ' . Shops::STATUS_ACTIVE . '
                       GROUP BY 1 ) as X
                SET C.shops = X.shops
                WHERE C.id = X.id
            '
            );
        }

        # пересчет связи магазинов с категориями объявлений (TABLE_SHOPS_IN_CATEGORIES_BBS)
        $this->db->exec('DELETE FROM ' . TABLE_SHOPS_IN_CATEGORIES_BBS);
        $insert = array();
        for ($i = 1; $i <= 2; $i++) {
            if ($i > BBS::CATS_MAXDEEP) {
                break;
            }
            $this->db->select_iterator('SELECT S.id, I.cat_id' . $i . ' AS cat, COUNT(I.id) as items
                FROM ' . TABLE_SHOPS . ' as S
                  LEFT JOIN ' . TABLE_BBS_ITEMS . ' as I ON I.user_id = S.user_id AND I.shop_id = S.id
                WHERE I.is_publicated = 1 AND I.status = ' . BBS::STATUS_PUBLICATED . '
                GROUP BY 1, 2
                ORDER BY shop_id ASC, items DESC', array(),
            function($row) use($i, & $insert) {
                $insert[] = array(
                    'shop_id'       => $row['id'],
                    'category_id'   => $row['cat'],
                    'numlevel'      => $i,
                    'items'         => $row['items'],
                );
                if (count($insert) > 20) {
                    $this->db->multiInsert(TABLE_SHOPS_IN_CATEGORIES_BBS, $insert);
                    $insert = array();
                }
            });
        }
        if ( ! empty($insert)) {
            $this->db->multiInsert(TABLE_SHOPS_IN_CATEGORIES_BBS, $insert);
        }


        # пересчет кол-ва опубликованных объявлений в магазинах (TABLE_SHOPS::items)
        $this->db->exec('UPDATE ' . TABLE_SHOPS . ' SET items = 0, items_last = :last',
            array(':last' => '0000-00-00 00:00:00')
        );
        if (Shops::categoriesEnabled()) {
            $this->db->exec('UPDATE ' . TABLE_SHOPS . ' S,
                        ( SELECT I.shop_id, COUNT(*) as items
                          FROM ' . TABLE_BBS_ITEMS . ' as I
                          WHERE I.shop_id > 0
                            AND I.is_publicated = 1
                            AND I.status = ' . BBS::STATUS_PUBLICATED . '
                          GROUP BY I.shop_id ) as X
                    SET S.items = X.items
                    WHERE S.id = X.shop_id
            ');
        } else {
            $this->db->exec('UPDATE ' . TABLE_SHOPS . ' S,
                        ( SELECT I.shop_id, SUM(I.items) as items
                          FROM ' . TABLE_SHOPS_IN_CATEGORIES_BBS . ' as I
                          WHERE I.numlevel = 1
                          GROUP BY I.shop_id ) as X
                    SET S.items = X.items
                    WHERE S.id = X.shop_id
            '
            );
        }
        # обновление даты последнего опубликованного объявления в магазинах (TABLE_SHOPS::items_last)
        $update = array();
        $updateExec = function() use (& $update){
            $this->db->exec('UPDATE '.TABLE_SHOPS.' SET items_last = CASE '.join(' ', $update).' ELSE items_last END 
                             WHERE id IN ('.join(',', array_keys($update)).')');
            $update = array();
        };
        $this->db->select_iterator('SELECT I.shop_id, MAX(I.publicated_order) as last_publicated
              FROM ' . TABLE_BBS_ITEMS . ' as I
              WHERE I.shop_id > 0 AND I.is_publicated = 1 AND I.status = ' . BBS::STATUS_PUBLICATED . '
              GROUP BY I.shop_id', array(),
        function($row) use(& $update, & $updateExec) {
            $update[ $row['shop_id'] ] = 'WHEN id = '.$row['shop_id'].' THEN '.$this->db->str2sql($row['last_publicated']);
            if (count($update) > 50) {
                $updateExec();
            }
        });
        if ( ! empty($update)) {
            $updateExec();
        }

        # пересчет кол-ва магазинов в категориях объявлений (TABLE_BBS_CATEGORIES::shops)
        $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES . ' SET shops = 0');
        for ($i = 1; $i <= 2; $i++) {
            if ($i > BBS::CATS_MAXDEEP) {
                break;
            }
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES . ' C,
                     ( SELECT S.category_id as id, COUNT(DISTINCT S.shop_id) as shops
                       FROM ' . TABLE_SHOPS_IN_CATEGORIES_BBS . ' as S
                       WHERE S.numlevel = ' . $i . '
                       GROUP BY 1 ) as X
                SET C.shops = X.shops
                WHERE C.numlevel = ' . $i . ' AND C.id = X.id
            '
            );
        }

        # пересчет общего кол-ва активных магазинов (config::site)
        config::save('shops_total_active', $this->shopsActiveCounter(), true);
    }

    /**
     * Удаление магазина
     * @param integer $nShopID ID магазина
     * @return boolean
     */
    public function shopDelete($nShopID)
    {
        # связь с категориями (TABLE_SHOPS_IN_CATEGORIES) удаляется по внешнему ключу
        # связь с жалобами (TABLE_SHOPS_CLAIMS) удаляется по внешнему ключу
        # связь с мультиязычными данными (TABLE_SHOPS_LANG) удаляется по внешнему ключу
        return $this->db->delete(TABLE_SHOPS, array('id' => $nShopID));
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL магазинов
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function shopsGeoUrlTypeChanged($prevType, $nextType)
    {
        if ($prevType == $nextType) {
            return;
        }

        $aData = $this->db->select('SELECT
                RR.keyword as region, RR.id as region_id,
                RC.keyword as city, RC.id as city_id
            FROM ' . TABLE_SHOPS . ' S
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON S.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON S.reg3_city = RC.id
            WHERE S.reg3_city > 0 AND S.reg2_region > 0
            GROUP BY S.reg3_city
            ORDER BY S.reg3_city
        '
        );

        $coveringType = Geo::coveringType();

        if ($prevType == Geo::URL_SUBDOMAIN) {
            foreach ($aData as &$v) {
                switch ($nextType) {
                    case Geo::URL_SUBDIR:
                        $to = '//{sitehost}/' . $v['city'] . '/';
                        break;
                    case Geo::URL_NONE:
                        if ($coveringType == Geo::COVERING_CITY) {
                            continue 2;
                        }
                        $to = '//{sitehost}/';
                        break;
                }
                switch ($coveringType) {
                    case Geo::COVERING_COUNTRIES:
                    case Geo::COVERING_COUNTRY:
                    case Geo::COVERING_REGION:
                    case Geo::COVERING_CITIES:
                        $from = '//' . $v['city'] . '.{sitehost}/';
                        break;
                    case Geo::COVERING_CITY:
                        $from = '//{sitehost}/';
                        break;
                }
                $this->db->update(TABLE_SHOPS,
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
                                    $to = '//' . $v['city'] . '.{sitehost}/';
                                    break;
                                case Geo::COVERING_CITY:
                                    $to = '//{sitehost}/';
                                    break;
                            }
                        }
                            break;
                        case Geo::URL_NONE:
                            if ($coveringType == Geo::COVERING_CITY) {
                                continue 2;
                            }
                            $to = '//{sitehost}/';
                            break;
                    }
                    switch ($coveringType) {
                        case Geo::COVERING_COUNTRIES:
                        case Geo::COVERING_COUNTRY:
                        case Geo::COVERING_REGION:
                        case Geo::COVERING_CITIES:
                            $from = '//{sitehost}/' . $v['city'] . '/';
                            break;
                        case Geo::COVERING_CITY:
                            $from = '//{sitehost}/';
                            break;
                    }
                    $this->db->update(TABLE_SHOPS,
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
                                $to = '//' . $v['city'] . '.{sitehost}/';
                                break;
                            case Geo::URL_SUBDIR:
                                $to = '//{sitehost}/' . $v['city'] . '/';
                                break;
                        }
                        $this->db->update(TABLE_SHOPS,
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
     * Перестраиваем URL всех страниц магазинов
     */
    public function shopsUrlsRefresh()
    {
        $this->db->select_iterator('SELECT S.id, S.keyword, RR.keyword as region, RC.keyword as city
            FROM ' . TABLE_SHOPS . ' S
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON S.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON S.reg3_city = RC.id
            ORDER BY S.id', [], function($shop) {
            $url = Shops::url('shop.view', $shop, true);
            $this->db->update(TABLE_SHOPS, ['link' => $url], ['id' => $shop['id']]);
        });
    }

    /**
     * Получаем общее кол-во магазинов, ожидающих модерации
     * @return integer
     */
    public function shopsModeratingCounter()
    {
        $filter = $this->prepareFilter(array(
            ':mod'    => 'S.moderated != 1',
            ':stat'   => 'S.status!=' . Shops::STATUS_REQUEST,
        ));

        return (int)$this->db->one_data('SELECT COUNT(S.id) FROM ' . TABLE_SHOPS . ' S ' . $filter['where'], $filter['bind']);
    }

    # --------------------------------------------------------------------
    # Заявки на закрепление

    /**
     * Список заявок (admin)
     * @param array $aFilter фильтр списка заявок
     * @param bool $bCount только подсчет кол-ва заявок
     * @param string $sqlLimit
     * @return mixed
     */
    public function requestsListing(array $aFilter, $bCount = false, $sqlLimit = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'R');

        if ($bCount) {
            return $this->db->tag('shops-requests-listing-count', array('filter'=>&$aFilter))->one_data('SELECT COUNT(R.id) FROM ' . TABLE_SHOPS_REQUESTS . ' R ' . $aFilter['where'], $aFilter['bind']);
        }

        return $this->db->tag('shops-requests-listing-data', array('filter'=>&$aFilter))->select('SELECT R.id, R.created, R.name, R.email, R.viewed, R.user_ip,
                    R.user_id, U.email as user_email
               FROM ' . TABLE_SHOPS_REQUESTS . ' R
                    LEFT JOIN ' . TABLE_USERS . ' U ON R.user_id = U.user_id
               ' . $aFilter['where']
            . ' ORDER BY R.created DESC'
            . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Получение данных заявки
     * @param integer $nRequestID ID заявки
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function requestData($nRequestID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT R.*
                    FROM ' . TABLE_SHOPS_REQUESTS . ' R
                    WHERE R.id = :id',
                array(':id' => $nRequestID)
            );

        } else {
            //
        }

        return $aData;
    }

    /**
     * Сохранение заявки
     * @param integer $nRequestID ID заявки
     * @param array $aData данные заявки
     * @return boolean|integer
     */
    public function requestSave($nRequestID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        if ($nRequestID > 0) {
            $aData['modified'] = $this->db->now(); # Дата изменения
            $res = $this->db->update(TABLE_SHOPS_REQUESTS, $aData, array('id' => $nRequestID));

            return !empty($res);
        } else {
            $aData['created'] = $this->db->now(); # Дата создания
            $aData['modified'] = $this->db->now(); # Дата изменения
            $aData['user_id'] = User::id(); # Пользователь
            $aData['user_ip'] = Request::remoteAddress(true); # IP адрес

            $nRequestID = $this->db->insert(TABLE_SHOPS_REQUESTS, $aData);
            if ($nRequestID > 0) {
                //
            }

            return $nRequestID;
        }
    }

    /**
     * Переключатели заявки
     * @param integer $nRequestID ID заявки
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function requestToggle($nRequestID, $sField)
    {
        switch ($sField) {
            case '?':
            {
                // $this->toggleInt(TABLE_SHOPS_REQUESTS, $nRequestID, $sField, 'id');
            }
            break;
        }
    }

    /**
     * Удаление заявки
     * @param mixed $filter фильтр или ID заявки
     * @return boolean
     */
    public function requestDelete($filter)
    {
        if (empty($filter)) return false;
        if ( ! is_array($filter)) {
            $filter = array('id' => $filter);
        }
        $res = $this->db->delete(TABLE_SHOPS_REQUESTS, $filter);
        if (!empty($res)) {
            return true;
        }

        return false;
    }

    /**
     * Количество не просмотренных заявок
     * @param boolean $join true - закрепление, false - открытие
     * @return int
     */
    public function shopsRequestsCounter($join = false)
    {
        if ($join) {
            $filter = $this->prepareFilter(array(
                'status' => Shops::STATUS_REQUEST,
            ));

            return (int)$this->db->one_data('SELECT COUNT(id) FROM ' . TABLE_SHOPS . $filter['where'], $filter['bind']);
        } else {
            $filter = $this->prepareFilter(array(
                'viewed' => 0,
            ));

            return (int)$this->db->one_data('SELECT COUNT(id) FROM ' . TABLE_SHOPS_REQUESTS . $filter['where'], $filter['bind']);
        }
    }

    # ----------------------------------------------------------------
    # Жалобы

    public function claimsListing($aFilter, $bCount = false, $sqlLimit = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'CL');

        if ($bCount) {
            return (int)$this->db->tag('shops-claims-listing-count', array('filter'=>&$aFilter))->one_data('SELECT COUNT(CL.id)
                                FROM ' . TABLE_SHOPS_CLAIMS . ' CL
                                ' . $aFilter['where'], $aFilter['bind']
            );
        }

        return $this->db->tag('shops-claims-listing-data', array('filter'=>&$aFilter))->select('SELECT CL.*, U.name, U.login, U.blocked as ublocked, U.deleted as udeleted
                                FROM ' . TABLE_SHOPS_CLAIMS . ' CL
                                    LEFT JOIN ' . TABLE_USERS . ' U ON CL.user_id = U.user_id
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
                       FROM ' . TABLE_SHOPS_CLAIMS . '
                       WHERE id = :cid
                       LIMIT 1', array(':cid' => $nClaimID)
        );
    }

    public function claimSave($nClaimID, $aData)
    {
        if ($nClaimID) {
            return $this->db->update(TABLE_SHOPS_CLAIMS, $aData, array('id' => $nClaimID));
        } else {
            $aData['created'] = $this->db->now();
            $aData['user_id'] = User::id();
            $aData['user_ip'] = Request::remoteAddress();

            $nClaimID = $this->db->insert(TABLE_SHOPS_CLAIMS, $aData, 'id');
            if ($nClaimID > 0) {
                $aData['id'] = $nClaimID;
                bff::hook('shops.shop.claim.create', $aData);
            }
            return $nClaimID;
        }
    }

    public function claimDelete($filter)
    {
        if (empty($filter)) return false;
        if ( ! is_array($filter)) {
            $filter = array('id' => $filter);
        }

        return $this->db->delete(TABLE_SHOPS_CLAIMS, $filter);
    }

    # ----------------------------------------------------------------
    # Категории магазинов (используются при Shops::categoriesEnabled())

    /**
     * Данные для формирования списка категорий
     * @param string $type тип списка категорий
     * @param string $device тип устройства
     * @param integer $parentID ID parent-категории
     * @param string $iconVariant размер иконки
     * @param boolean $ignoreVirtual игнорировать виртуальные категории (неиспользуемое поле)
     * @return mixed
     */
    public function catsList($type, $device, $parentID, $iconVariant, $ignoreVirtual = false)
    {
        $filter = array(
            'C.pid != 0',
            'C.enabled = 1',
        );

        switch ($type) {
            case 'search':
            case 'form':
            {
                if ($device == bff::DEVICE_DESKTOP) {
                    //$iconVariant = ShopsCategoryIcon::BIG;
                    if ($parentID > 0) {
                        $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                    } else {
                        $filter[] = 'C.numlevel = 1';
                    }
                } else if ($device == bff::DEVICE_PHONE) {
                    //$iconVariant = ShopsCategoryIcon::SMALL;
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
        $filter = $this->prepareFilter($filter);

        return $this->db->tag('shops-cats-list-data', array('filter'=>&$filter))->select('SELECT C.id, C.pid, C.icon_' . $iconVariant . ' as i, CL.title as t, C.keyword as k,
                                         (C.numright-C.numleft)>1 as subs, C.numlevel as lvl
                            FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                                 ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
                            ' . $filter['where'] . '
                            ORDER BY C.numleft ASC', $filter['bind']
        );
    }

    /**
     * Список категорий магазинов
     * @param array $aFilter
     * @return mixed
     */
    public function catsListing(array $aFilter = array())
    {
        $aFilter = $this->prepareFilter($aFilter);

        return $this->db->tag('shops-cats-listing-data', array('filter'=>&$aFilter))->select('SELECT C.id, C.pid, C.enabled, C.numlevel,
                                IF(C.numright-C.numleft>1,1,0) as node, C.title, COUNT(S.shop_id) as shops
                            FROM ' . TABLE_SHOPS_CATEGORIES . ' C
                                LEFT JOIN ' . TABLE_SHOPS_IN_CATEGORIES . ' S ON S.category_id = C.id
                            ' . $aFilter['where'] . '
                            GROUP BY C.id
                            ORDER BY C.numleft ASC', $aFilter['bind']
        );
    }

    public function catData($nCategoryID, $aFields = array(), $bEdit = false)
    {
        if (empty($nCategoryID)) return array();

        return $this->catDataByFilter(array('id' => $nCategoryID), $aFields, $bEdit);
    }

    public function catDataByFilter($aFilter, $aFields = array(), $bEdit = false)
    {
        $aParams = array();
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

        $aFilter[':lng'] = $this->db->langAnd(false, 'C', 'CL');
        $aFilter = $this->prepareFilter($aFilter, 'C');

        $aData = $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                            ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
                       ' . $aFilter['where'] . '
                       LIMIT 1', $aFilter['bind']
        );

        if ($bEdit) {
            $aData['node'] = ($aData['numright'] - $aData['numleft']);
            if (!Request::isPOST()) {
                $this->db->langSelect($aData['id'], $aData, $this->langCategories, TABLE_SHOPS_CATEGORIES_LANG);
            }
        }

        return $aData;
    }

    public function catSave($nCategoryID, $aData)
    {
        if ($nCategoryID) {
            # запрет именения parent'a
            if (isset($aData['pid'])) unset($aData['pid']);
            $aData['modified'] = $this->db->now();
            $this->db->langUpdate($nCategoryID, $aData, $this->langCategories, TABLE_SHOPS_CATEGORIES_LANG);
            $aDataNonLang = array_diff_key($aData, $this->langCategories);
            if (isset($aData['title'][LNG])) $aDataNonLang['title'] = $aData['title'][LNG];

            return $this->db->update(TABLE_SHOPS_CATEGORIES, $aDataNonLang, array('id' => $nCategoryID));
        } else {
            $nCategoryID = $this->treeCategories->insertNode($aData['pid']);
            if (!$nCategoryID) return 0;
            unset($aData['pid']);
            $aData['created'] = $this->db->now();
            $this->catSave($nCategoryID, $aData);

            return $nCategoryID;
        }
    }

    public function catDelete($nCategoryID)
    {
        if (!$nCategoryID) return false;

        # проверяем наличие подкатегорий
        $aData = $this->catData($nCategoryID, '*', true);
        if ($aData['node'] > 1) {
            $this->errors->set('Невозможно удалить категорию с подкатегориями');

            return false;
        }

        # проверяем наличие связанных с категорией магазинов
        # ...

        # удаляем
        $aDeleteID = $this->treeCategories->deleteNode($nCategoryID);
        if (empty($aDeleteID)) {
            $this->errors->set('Ошибка удаления категории');

            return false;
        }

        return true;
    }

    public function catDeleteAll()
    {
        # чистим таблицу категорий (+ зависимости по внешним ключам)
        $this->db->exec('DELETE FROM ' . TABLE_SHOPS_CATEGORIES . ' WHERE id > 0');
        $this->db->exec('ALTER TABLE ' . TABLE_SHOPS_CATEGORIES . ' AUTO_INCREMENT = 2');

        # создаем корневую директорию
        $nRootID = Shops::CATS_ROOTID;
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
        );
        $res = $this->db->insert(TABLE_SHOPS_CATEGORIES, $aData);
        if (!empty($res)) {
            $aDataLang = array('title' => array());
            foreach ($this->locale->getLanguages() as $lng) {
                $aDataLang['title'][$lng] = $sRootTitle;
            }
            $this->db->langInsert($nRootID, $aDataLang, $this->langCategories, TABLE_SHOPS_CATEGORIES_LANG);
        }

        return !empty($res);
    }

    public function catToggle($nCategoryID, $sField)
    {
        if (!$nCategoryID) return false;

        switch ($sField) {
            case 'enabled':
            {
                $res = $this->toggleInt(TABLE_SHOPS_CATEGORIES, $nCategoryID, 'enabled', 'id');
                if ($res) {
                    $aCategoryData = $this->catData($nCategoryID, array('numleft', 'numright', 'enabled'));
                    if (!empty($aCategoryData)) {
                        $this->db->update(TABLE_SHOPS_CATEGORIES, array(
                                'enabled' => $aCategoryData['enabled'],
                            ), array(
                                'numleft > :left AND numright < :right'
                            ), array(
                                ':left'  => $aCategoryData['numleft'],
                                ':right' => $aCategoryData['numright'],
                            )
                        );
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
        return $this->treeCategories->rotateTablednd();
    }

    /**
     * Получаем данные о parent-категориях
     * @param int $nCategoryID ID категории
     * @param array $aFields требуемые поля parent-категорий
     * @param bool $bIncludingSelf включая категорию $nCategoryID
     * @param bool $bExludeRoot исключая данные о корневом элементе
     * @return array|mixed
     */
    public function catParentsData($nCategoryID, array $aFields = array(
        'id',
        'title',
        'keyword'
    ), $bIncludingSelf = true, $bExludeRoot = true
    ) {
        if ($nCategoryID <= 0) return array();
        $aParentsID = $this->treeCategories->getNodeParentsID($nCategoryID, ($bExludeRoot ? ' AND id != ' . Shops::CATS_ROOTID : ''), $bIncludingSelf);
        if (empty($aParentsID)) return array();
        if (empty($aFields)) $aFields[] = 'id';
        foreach ($aFields as $k => $v) {
            if ($v == 'id' || array_key_exists($v, $this->langCategories)) $aFields[$k] = 'CL.' . $v;
        }
        $aParentsData = $this->db->select('SELECT ' . join(',', $aFields) . '
            FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                 ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
            WHERE C.id IN(' . join(',', $aParentsID) . ')
            ' . $this->db->langAnd(true, 'C', 'CL') . '
            ORDER BY C.numleft
        '
        );

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
            $aData = $this->db->select('SELECT id, numlevel FROM ' . TABLE_SHOPS_CATEGORIES . '
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
                    FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                         ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
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
        if ($aCatData['pid'] == Shops::CATS_ROOTID) {
            $sFrom = $sKeywordPrev . '/';
        } else {
            $aParentCatData = $this->catData($aCatData['pid'], array('keyword'));
            if (empty($aParentCatData)) return false;
            $sFrom = $aParentCatData['keyword'] . '/' . $sKeywordPrev . '/';
        }

        # перестраиваем полный путь подкатегорий
        $nCatsUpdated = $this->db->update(TABLE_SHOPS_CATEGORIES,
            array('keyword = REPLACE(keyword, :from, :to)'),
            'numleft > :left AND numright < :right',
            array(
                ':from'  => $sFrom,
                ':to'    => $aCatData['keyword'] . '/',
                ':left'  => $aCatData['numleft'],
                ':right' => $aCatData['numright']
            )
        );

        return !empty($nCatsUpdated);
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
            return ($nParentID == Shops::CATS_ROOTID);
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
        $bCountShops = false;
        switch ($sType) {
            case 'adm-category-form-add':
                $sqlWhere[] = 'C.numlevel < ' . Shops::CATS_MAXDEEP;
                $bCountShops = true;
                break;
            case 'adm-shops-listing':
                $sqlWhere[] = '(C.numlevel IN(1,2) ' . ($nSelectedID > 0 ? ' OR C.id = ' . $nSelectedID : '') . ')';
                break;
            case 'adm-shop-form':
                $sqlWhere[] = 'C.numlevel IN (1,2)';
                break;
        }

        // TODO
        $aData = $this->db->select('SELECT C.id, C.pid, CL.title, C.numlevel, C.numleft, C.numright, 0 as disabled
                        ' . ($bCountShops ? ', COUNT(S.id) as shops ' : '') . '
                   FROM ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL,
                        ' . TABLE_SHOPS_CATEGORIES . ' C
                        ' . ($bCountShops ? ' LEFT JOIN ' . TABLE_SHOPS_IN_CATEGORIES . ' S ON C.id = S.category_id ' : '') . '
                   WHERE ' . join(' AND ', $sqlWhere) . '
                   GROUP BY C.id
                   ORDER BY C.numleft'
        );
        if (empty($aData)) $aData = array();

        if ($sType == 'adm-category-form-add') {
            foreach ($aData as &$v) {
                $v['disabled'] = ($v['numlevel'] > 0 && $v['shops'] > 0);
            }
            unset($v);
        } else if ($sType == 'adm-shop-form') {
            foreach ($aData as &$v) {
                # запрещаем выбор категорий с вложенными подкатегориями
                $v['disabled'] = (($v['numright'] - $v['numleft']) > 1);
            }
            unset($v);
        }

        $sHTML = '';
        $bUsePadding = (stripos(Request::userAgent(), 'chrome') === false);
        foreach ($aData as $v) {
            $sHTML .= '<option value="' . $v['id'] . '" data-pid="' . $v['pid'] . '" ' .
                ($bUsePadding && $v['numlevel'] > 1 ? 'style="padding-left:' . ($v['numlevel'] * 10) . 'px;" ' : '') .
                ($nSelectedID == $v['id'] ? ' selected="selected"' : '') .
                ($v['disabled'] ? ' disabled="disabled"' : '') .
                '>' . (!$bUsePadding && $v['numlevel'] > 1 ? str_repeat('&nbsp;&nbsp;', $v['numlevel']) : '') . $v['title'] . '</option>';
        }

        if ($mEmptyOpt !== false) {
            $nValue = 0;
            if (is_array($mEmptyOpt)) {
                $nValue = key($mEmptyOpt);
                $mEmptyOpt = current($mEmptyOpt);
            }
            $sHTML = '<option value="' . $nValue . '" class="bold">' . $mEmptyOpt . '</option>' . $sHTML;
        }

        return $sHTML;
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
            $aData = $this->db->tag('shops-svc-promote-data-service')->select_key('SELECT id, keyword, price, settings
                            FROM ' . TABLE_SVC . ' WHERE type = :type',
                'keyword', array(':type' => Svc::TYPE_SERVICE)
            );

            if (empty($aData)) return array();

            foreach ($aData as $k => $v) {
                $sett = func::unserialize($v['settings']);
                unset($v['settings']);
                $aData[$k] = array_merge($v, $sett);
            }

            return $aData;

        } elseif ($nTypeID == Svc::TYPE_SERVICEPACK) {
            $aData = $this->db->tag('shops-svc-promote-data-servicepack')->select('SELECT id, keyword, price, settings
                                FROM ' . TABLE_SVC . ' WHERE type = :type ORDER BY num',
                array(':type' => Svc::TYPE_SERVICEPACK)
            );

            foreach ($aData as $k => $v) {
                $sett = func::unserialize($v['settings']);
                unset($v['settings']);
                # оставляем текущую локализацию
                foreach ($this->langSvcPacks as $lngK => $lngV) {
                    $sett[$lngK] = (isset($sett[$lngK][LNG]) ? $sett[$lngK][LNG] : '');
                }
                $aData[$k] = array_merge($v, $sett);
            }

            return $aData;
        }
    }

    /**
     * Данные об услугах для формы, страницы продвижения
     * @return array
     */
    public function svcData()
    {
        $aFilter = array('module' => 'shops');
        $aFilter = $this->prepareFilter($aFilter, 'S');

        $aData = $this->db->select_key('SELECT S.*
                                    FROM ' . TABLE_SVC . ' S
                                    ' . $aFilter['where']
            . ' ORDER BY S.type, S.num',
            'id', $aFilter['bind']
        );
        if (empty($aData)) return array();

        $oIcon = Shops::svcIcon();
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
            $v['icon_b'] = $oIcon->url($v['id'], $v['icon_b'], ShopsSvcIcon::BIG);
            $v['icon_s'] = $oIcon->url($v['id'], $v['icon_s'], ShopsSvcIcon::SMALL);
            # исключаем выключенные услуги
            if (empty($v['on'])) unset($aData[$k]);
        }
        unset($v);

        return $aData;
    }

    /**
     * Получение региональной стоимости услуги в зависимости от города
     * @param array $svcID ID услуг
     * @param integer $cityID ID города
     * @return array - региональной стоимость услуг для указанного региона
     */
    public function svcPricesEx(array $svcID, $cityID)
    {
        if (empty($svcID) || !$cityID) return array();
        $result = array_fill_keys($svcID, 0);

        $cityData = Geo::regionData($cityID);
        if (empty($cityData) || !Geo::isCity($cityData) || !$cityData['pid']) return $result;

        # получаем доступные варианты региональной стоимости услуг
        $prices = $this->db->select('SELECT * FROM ' . TABLE_SHOPS_SVC_PRICE . '
                    WHERE ' . $this->db->prepareIN('svc_id', $svcID) . '
                    ORDER BY num'
        );
        if (empty($prices)) return array();

        foreach ($svcID as $id) {
            # город
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['region_id'] == $cityID) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # регион(область)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['region_id'] == $cityData['pid']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # страна
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['region_id'] == $cityData['country']) {
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
        $aData = $this->db->select('SELECT * FROM ' . TABLE_SHOPS_SVC_PRICE . ' ORDER BY svc_id, id, num');
        if (!empty($aData)) {
            $aRegionsID = array();
            foreach ($aData as $v) {
                if (!isset($aResult[$v['svc_id']])) {
                    $aResult[$v['svc_id']] = array();
                }
                if (!isset($aResult[$v['svc_id']][$v['id']])) {
                    $aResult[$v['svc_id']][$v['id']] = array('price' => $v['price'], 'regions' => array());
                }
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
            if ($v['price'] <= 0 || empty($v['regions'])) {
                continue;
            }

            $v['regions'] = array_unique($v['regions']);
            foreach ($v['regions'] as $region) {
                $sql[] = array(
                    'id'        => $id,
                    'svc_id'    => $nSvcID,
                    'price'     => $v['price'],
                    'region_id' => $region,
                    'num'       => $num++,
                );
            }
            $id++;
        }

        $this->db->delete(TABLE_SHOPS_SVC_PRICE, array('svc_id' => $nSvcID));
        if (!empty($sql)) {
            foreach (array_chunk($sql, 25) as $v) {
                $this->db->multiInsert(TABLE_SHOPS_SVC_PRICE, $v);
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

        if (Shops::abonementEnabled()) {
            # Предупреждение о недостаточном количестве средств для автопродления
            $this->cronAbonementPeriodNoMoney(5); # за 5 дней
            $this->cronAbonementPeriodNoMoney(1); # за 1 день

            # Предупреждение об окончании срока действия тарифного плана
            $this->cronAbonementFinishSoon();

            # Деактивируем услугу "Абонемент"
            $this->cronAbonementDeactivate();
        }

        # Деактивируем услугу "Выделение"
        $this->db->tag('shops-svc-cron-service-mark')->exec('UPDATE ' . TABLE_SHOPS . '
            SET svc = (svc - ' . Shops::SERVICE_MARK . '), svc_marked_to = :empty
            WHERE (svc & ' . Shops::SERVICE_MARK . ') AND svc_marked_to <= :now AND svc_marked_to != :termless',
            array(':now' => $sNow, ':empty' => $sEmpty, ':termless' => Shops::SVC_TERMLESS_DATE)
        );

        # Деактивируем услугу "Закрепление"
        $this->db->tag('shops-svc-cron-service-fix')->exec('UPDATE ' . TABLE_SHOPS . '
            SET svc = (svc - ' . Shops::SERVICE_FIX . '), svc_fixed = 0, svc_fixed_to = :empty, svc_fixed_order = :empty
            WHERE (svc & ' . Shops::SERVICE_FIX . ') AND svc_fixed_to <= :now AND svc_fixed_to != :termless',
            array(':now' => $sNow, ':empty' => $sEmpty, ':termless' => Shops::SVC_TERMLESS_DATE)
        );

        bff::hook('shops.svc.cron');
    }

    /**
     * Выполнение строго 1 раз в сутки
     * Предупреждение о недостаточном количестве средств для автопродления
     * за $days дней до окончания срока действия тарифного плана
     * @param integer $days кол-во дней
     */
    public function cronAbonementPeriodNoMoney($days)
    {
        if ($days <= 0) return;

        $bind = array(':svc'=>Shops::SERVICE_ABONEMENT);
        if ($days == 1) {
            $where = ' AND S.svc_abonement_expire <= :to ';
            $bind[':to'] = date('Y-m-d H:i:s', strtotime('+1 days'));
        } else {
            $where = ' AND S.svc_abonement_expire >= :from AND S.svc_abonement_expire <= :to ';
            $bind[':from'] = date('Y-m-d H:i:s', strtotime('+'.($days - 1).' days'));
            $bind[':to'] = date('Y-m-d H:i:s', strtotime('+'.$days.' days'));
        }

        $locales = $this->locale->getLanguages();
        $langCurrent = $this->locale->getCurrentLanguage();
        $titles = array();
        foreach ($locales as $v) {
            $titles[] = 'A.title_' . $v . ' AS tariff_title_' . $v;
        }
        $this->db->select_iterator('
            SELECT S.id, S.user_id as user_id, S.svc_abonement_id, SL.title, S.link, S.svc_abonement_auto_id, S.svc_abonement_expire, 
                U.email, U.user_id_ex, U.password, U.balance, US.last_login, U.lang,
                A.price, '.join(',', $titles).'
            FROM ' . TABLE_SHOPS . ' S, 
                 ' . TABLE_SHOPS_LANG . ' SL,
                 ' . TABLE_SHOPS_ABONEMENTS . ' A, 
                 ' . TABLE_USERS . ' U, 
                 ' . TABLE_USERS_STAT . ' US
            WHERE (S.svc & :svc) '.$where.'
             AND S.svc_abonement_termless = 0
             AND S.svc_abonement_id > 0
             AND S.svc_abonement_auto = 1
             AND S.svc_abonement_id = A.id
             AND S.user_id = U.user_id
             AND S.user_id = US.user_id
             '.$this->db->langAnd(true, 'S', 'SL').'
             AND A.price_free = 0', $bind, function($item) use ($langCurrent) {
                if (empty($item['lang'])){
                    $item['lang'] = $langCurrent;
                }
                if ($item['lang'] != $langCurrent) {
                    $item['title'] = $this->shopDataLangField($item['id'], $item['lang'], 'title');
                }

                if (empty($item['title'])) {
                }
                $price = func::unserialize($item['price']);
                $priceID = $item['svc_abonement_auto_id'];
                if ( ! isset($price[$priceID])) {
                    return;
                }
                $price = $price[$priceID];
                if ($item['balance'] < $price) {
                    # отправляем письмо
                    $price = _t('shops', ' [month] - [price] [curr];', array(
                        'month' => tpl::declension($priceID, _t('', 'месяц;месяца;месяцев')),
                        'price' => $price,
                        'curr'  => Site::currencyDefault()
                    ));
                    $aMailData = array(
                        'email'         => $item['email'],
                        'user_id'       => $item['user_id'],
                        'shop_id'       => $item['id'],
                        'shop_title'    => $item['title'],
                        'shop_link'     => Shops::urlDynamic($item['link']),
                        'tariff_title'  => $item['tariff_title_'.$item['lang']],
                        'tariff_price'  => $price,
                        'tariff_expire' => tpl::date_format2($item['svc_abonement_expire']),
                        'promote_link'  => Shops::url('shop.promote', array(
                            'id' => $item['id'],
                            'svc' => Shops::SERVICE_ABONEMENT,
                            'abonID' => $item['svc_abonement_id'],
                            'alogin' => Users::loginAutoHash($item),
                        )),
                    );
                    bff::sendMailTemplate($aMailData, 'shops_abonement_period_no_money', $item['email'], false, '', '', $item['lang']);
                }
            }
        );
    }

    /**
     * Деактивируем услугу "Абонемент" по крону, раз в сутки
     */
    public function cronAbonementDeactivate()
    {
        $bind = array();
        $bind[':svc'] = Shops::SERVICE_ABONEMENT;
        $bind[':now'] = $this->db->now();

        $locales = $this->locale->getLanguages();
        $langCurrent = $this->locale->getCurrentLanguage();
        $titles = array();
        foreach ($locales as $v) {
            $titles[] = 'A.title_' . $v . ' AS tariff_title_' . $v;
        }

        $this->db->select_iterator('
            SELECT S.id, S.user_id as user_id, S.svc_abonement_id, SL.title, S.link, 
                S.svc_abonement_auto, S.svc_abonement_auto_id, S.svc_abonement_one_time,
                U.email, U.user_id_ex, U.password, U.balance, US.last_login, U.lang,
                A.price, A.price_free, '.join(',', $titles).'
            FROM ' . TABLE_SHOPS . ' S, 
                 ' . TABLE_SHOPS_LANG.' SL,
                 ' . TABLE_SHOPS_ABONEMENTS . ' A, 
                 ' . TABLE_USERS . ' U, 
                 ' . TABLE_USERS_STAT . ' US
            WHERE  (S.svc & :svc)
                AND S.svc_abonement_id > 0 
                AND S.svc_abonement_expire <= :now
                AND S.svc_abonement_termless = 0
                AND S.user_id = U.user_id 
                AND S.user_id = US.user_id 
                '.$this->db->langAnd(true, 'S', 'SL').'
                AND S.svc_abonement_id = A.id 
            ', $bind, function ($item) use ($langCurrent) {
                if (empty($item['lang'])) {
                    $item['lang'] = $langCurrent;
                }
                if ($item['lang'] != $langCurrent) {
                    $item['title'] = $this->shopDataLangField($item['id'], $item['lang'], 'title');
                }

                # Автопродление тарифа
                if ($item['svc_abonement_auto'] && $item['price_free'] == 0)
                {
                    $price = func::unserialize($item['price']);
                    $priceID = $item['svc_abonement_auto_id'];
                    if (isset($price[$priceID])) {
                        if ($item['balance'] < $price[$priceID]) {
                            # недостаточно средств для продления на такой же период, найдем ближайший дешевле
                            unset($price[$priceID]);
                            $priceID = false;
                            $months = array_keys($price);
                            $months = array_reverse($months);
                            foreach ($months as $m) {
                                if ($item['balance'] >= $price[$m]) {
                                    $priceID = $m;
                                    break;
                                }
                            }
                        }
                        if ($priceID && isset($price[$priceID])) {
                            $price = $price[$priceID];
                            $aSvcSettings = array(
                                'abonement_id' => $item['svc_abonement_id'],
                                'abonement_period' => $priceID,
                            );
                            $aSvc['module'] = Shops::i()->module_name;
                            if (Svc::i()->activate(Shops::i()->module_name, Shops::SERVICE_ABONEMENT, $aSvc, $item['id'], $item['user_id'], $price, $price, $aSvcSettings)) {
                                return;
                            }
                        }
                    }
                }

                # Деактивируем
                $this->abonementDeactivate($item['id']);
                # Снимаем объявления с публикации
                $this->abonementItemsUnpublicate($item['id']);

                # отправляем письмо о деактивации тарифного плана
                $autoLogin = Users::loginAutoHash($item);
                $price = func::unserialize($item['price']);
                $priceID = $item['svc_abonement_auto_id'];
                if (isset($price[$priceID])) {
                    $price = $price[$priceID];
                    $price = _t('shops', ' [month] - [price] [curr];', array(
                        'month' => tpl::declension($priceID, _t('', 'месяц;месяца;месяцев')),
                        'price' => $price,
                        'curr' => Site::currencyDefault(),
                    ));
                } else {
                    $price = '';
                }

                $template = 'shops_abonement_finished';
                $oneTime = func::unserialize($item['svc_abonement_one_time']);
                if (in_array($item['svc_abonement_id'], $oneTime)) {
                    $template = 'shops_abonement_finished_onetime';
                }

                $aMailData = array(
                    'email'        => $item['email'],
                    'user_id'      => $item['user_id'],
                    'shop_id'      => $item['id'],
                    'shop_title'   => $item['title'],
                    'shop_link'    => Shops::urlDynamic($item['link']),
                    'tariff_title' => $item['tariff_title_'.$item['lang']],
                    'tariff_price' => $price,
                    'promote_link' => Shops::url('shop.promote', array(
                        'id'     => $item['id'],
                        'svc'    => Shops::SERVICE_ABONEMENT,
                        'abonID' => $item['svc_abonement_id'],
                        'alogin' => $autoLogin,
                    )),
                );

                bff::sendMailTemplate($aMailData, $template, $item['email'], false, '', '', $item['lang']);
            }
        );
    }

    /**
     * Выполнение строго 1 раз в сутки
     * Предупреждение за 3 дня до окончания срока действия тарифного плана услуги "Абонемент"
     */
    public function cronAbonementFinishSoon()
    {
        $locales = $this->locale->getLanguages();
        $langCurrent = $this->locale->getCurrentLanguage();
        $titles = array();
        foreach ($locales as $v) {
            $titles[] = 'A.title_' . $v . ' AS tariff_title_' . $v;
        }
        $this->db->select_iterator('
            SELECT S.id, S.user_id as user_id, S.svc_abonement_id, SL.title, S.link, 
                S.svc_abonement_auto, S.svc_abonement_auto_id, S.svc_abonement_one_time,
                U.email, U.user_id_ex, U.password, U.balance, US.last_login, U.lang,
                A.price, '.join(',', $titles).'
            FROM ' . TABLE_SHOPS . ' S,
                 ' . TABLE_SHOPS_LANG.' SL,
                 ' . TABLE_SHOPS_ABONEMENTS . ' A, 
                 ' . TABLE_USERS . ' U, 
                 ' . TABLE_USERS_STAT . ' US
            WHERE  (S.svc & ' . Shops::SERVICE_ABONEMENT . ') 
                AND S.svc_abonement_id > 0
                AND S.user_id = US.user_id
                AND S.svc_abonement_expire >= :from AND S.svc_abonement_expire <= :to
                AND S.svc_abonement_termless = 0 
                AND S.user_id = U.user_id AND S.svc_abonement_id = A.id 
                '.$this->db->langAnd(true, 'S', 'SL').'
            ', array(
                ':from' => date('Y-m-d H:i:s', strtotime('+2 days')),
                ':to' => date('Y-m-d H:i:s', strtotime('+3 days')),
        ), function ($item) use ($langCurrent) {
            if (empty($item['lang'])){
                $item['lang'] = $langCurrent;
            }
            if ($item['lang'] != $langCurrent) {
                $item['title'] = $this->shopDataLangField($item['id'], $item['lang'], 'title');
            }

            $price = func::unserialize($item['price']);
            $priceID = $item['svc_abonement_auto_id'];
            if ($item['svc_abonement_auto'] && isset($price[$priceID]) && $item['balance'] > $price[$priceID]) {
                # если автопродление включено и средств достаточно - уведомление не отправляем
                return;
            }
            if (isset($price[$priceID])) {
                $price = $price[$priceID];
                $price = _t('shops', ' [month] - [price] [curr];', array(
                    'month' => tpl::declension($priceID, _t('', 'месяц;месяца;месяцев')),
                    'price' => $price,
                    'curr' => Site::currencyDefault()
                ));
            } else {
                $price = '';
            }

            $template = 'shops_abonement_finish_soon';
            $oneTime = func::unserialize($item['svc_abonement_one_time']);
            if (in_array($item['svc_abonement_id'], $oneTime)) {
                $template = 'shops_abonement_finish_soon_onetime';
            }
            $autoLogin = Users::loginAutoHash($item);
            $aMailData = array(
                'email'        => $item['email'],
                'user_id'      => $item['user_id'],
                'shop_id'      => $item['id'],
                'shop_title'   => $item['title'],
                'shop_link'    => Shops::urlDynamic($item['link']),
                'tariff_title' => $item['tariff_title_'.$item['lang']],
                'tariff_price' => $price,
                'promote_link' => Shops::url('shop.promote', array(
                    'id'     => $item['id'],
                    'svc'    => Shops::SERVICE_ABONEMENT,
                    'abonID' => $item['svc_abonement_id'],
                    'alogin' => $autoLogin,
                )),
            );

            bff::sendMailTemplate($aMailData, $template, $item['email'], false, '', '', $item['lang']);
        });
    }

    # ----------------------------------------------------------------
    # Абонемент: тарифы

    /**
     * Список тарифов услуги абонемент (admin)
     * @param array $aFilter фильтр списка тарифов услуги абонемент
     * @param bool $bCount только подсчет кол-ва тарифов
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function abonementsListing(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = 'num')
    {
        $aFilter = $this->prepareFilter($aFilter, 'AB');
        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(AB.id) FROM '.TABLE_SHOPS_ABONEMENTS.' AB '.$aFilter['where'], $aFilter['bind']);
        }

        return $this->db->select('SELECT AB.id, AB.title_'.LNG.' as title, AB.enabled, AB.is_default
               FROM '.TABLE_SHOPS_ABONEMENTS.' AB
               '.$aFilter['where']
            .( ! empty($sqlOrder) ? ' ORDER BY '.$sqlOrder : '')
            .$sqlLimit, $aFilter['bind']);
    }

    /**
     * Список тарифов услуги абонемент (frontend)
     * @param array $aFilter фильтр списка тарифов услуги абонемент
     * @param bool $bCount только подсчет кол-ва тарифов
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function abonementsList(array $aFilter = array(), $bCount = false, $sqlLimit = '', $sqlOrder = 'num')
    {
        $aFilter['enabled'] = array('enabled' => 1);

        $aFilter = $this->prepareFilter($aFilter, 'AB');
        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(AB.id) FROM '.TABLE_SHOPS_ABONEMENTS.' AB '.$aFilter['where'], $aFilter['bind']);
        }

        $aData =  $this->db->select_key('SELECT AB.*, AB.title_'.LNG.' as title
               FROM '.TABLE_SHOPS_ABONEMENTS.' AB
               '.$aFilter['where']
            .( ! empty($sqlOrder) ? ' ORDER BY '.$sqlOrder : '')
            .$sqlLimit, 'id', $aFilter['bind']);

        if (empty($aData))
            return array();

        foreach ($aData as $key => &$v)
        {
            $v['img'] = $this->controller->abonementIcon($v['id'])->url($v['id'], $v['icon_b']);
            $v['price'] = func::unserialize($v['price']);
            if ($v['price_free']) {
                $v['price'] = array($v['price_free_period'] => 0);
            }

            # форматирование цены
            if (empty($v['price'])) continue;
            foreach ($v['price'] as $k => &$vv) {
                if ($k == 0) {
                    $vv = array(
                        'pr' => $vv,
                        'ex' => _t('shops','неограниченного периода'),
                        'm' => ''
                    );
                    continue;
                }
                $now = new DateTime();
                $vv = array(
                    'pr' => $vv,
                    'ex' => $now->modify('+'.$k.' month')->format('d.m.Y'),
                    'm' => tpl::declension($k, _t('', 'месяц;месяца;месяцев'))
                ); unset($vv);
            }
        } unset ($v);

        return $aData;
    }

    /**
     * Получение данных тарифа услуги абонемент
     * @param integer $nAbonementID ID тарифа
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function abonementData($nAbonementID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT AB.*
                    FROM '.TABLE_SHOPS_ABONEMENTS.' AB
                    WHERE AB.id = :id',
                array(':id'=>$nAbonementID));
            $this->db->langFieldsSelect($aData, $this->langAbonements);
        } else {
            $aData = $this->db->one_array('SELECT AB.*, AB.title_'.LNG.' as title
                    FROM '.TABLE_SHOPS_ABONEMENTS.' AB
                    WHERE AB.id = :id',
                array(':id'=>$nAbonementID));
        }

        if (empty($aData)) {
            return array();
        }
        $aData['price'] = func::unserialize($aData['price']);
        $aData['discount'] = func::unserialize($aData['discount']);

        return $aData;
    }

    /**
     * Сохранение тарифа услуги абонемент
     * @param integer $nAbonementID ID тарифа
     * @param array $aData данные тарифа
     * @return boolean|integer
     */
    public function abonementSave($nAbonementID, array $aData)
    {
        if (empty($aData)) return false;
        if (isset($aData['price'])) {
            $aData['price'] = serialize($aData['price']);
        }
        if (isset($aData['discount'])) {
            $aData['discount'] = serialize($aData['discount']);
        }
        $this->db->langFieldsModify($aData, $this->langAbonements, $aData);
        if ($nAbonementID > 0) {
            $this->db->update(TABLE_SHOPS_ABONEMENTS, $aData, array('id' => $nAbonementID));
        } else {
            $nAbonementID = $this->db->insert(TABLE_SHOPS_ABONEMENTS, $aData);
        }

        if ($nAbonementID > 0) {
            if (!empty($aData['is_default'])) {
                $this->db->update(TABLE_SHOPS_ABONEMENTS, array('is_default' => 0), 'is_default = 1 AND id != ' . $nAbonementID);
            }
        }

        return $nAbonementID;
    }

    /**
     * Переключатели тарифов
     * @param integer $nAbonementID ID тарифа
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function abonementToggle($nAbonementID, $sField)
    {
        switch ($sField) {
            case 'enabled': { # Включен
                return $this->toggleInt(TABLE_SHOPS_ABONEMENTS, $nAbonementID, $sField, 'id');
            } break;
        }
    }

    /**
     * Сортировка тарифов
     * @param string $orderField поле сортировки
     * @return bool
     */
    public function abonementRotate($orderField = 'num')
    {
        return $this->db->rotateTablednd(TABLE_SHOPS_ABONEMENTS, '', 'id', $orderField);
    }

    /**
     * Установить тариф по-умолчанию
     * @param integer $nAbonementID ID абонемента
     * @return mixed @see toggleInt
     */
    public function abonementSetDefault($nAbonementID)
    {
        $this->db->update(TABLE_SHOPS_ABONEMENTS, array('is_default' => 0), 'is_default = 1 AND id != '.$nAbonementID);
        return $this->toggleInt(TABLE_SHOPS_ABONEMENTS, $nAbonementID, 'is_default', 'id');
    }

    /**
     * Удаление тарифа
     * @param integer $nAbonementID ID тарифа
     * @param integer $nAbonementNewID ID тарифа для замены
     * @return mixed
     */
    public function abonementDelete($nAbonementID, $nAbonementNewID = 0)
    {
        if (empty($nAbonementID)) return false;

        $aShopIDs = $this->db->select_one_column('SELECT id  FROM ' . TABLE_SHOPS . '
            WHERE (svc & ' . Shops::SERVICE_ABONEMENT . ') AND svc_abonement_id = :id',
            array(':id' => $nAbonementID)
        );
        # если тариф используется заменяем его
        if ( ! empty($aShopIDs))
        {
            if ( ! $nAbonementNewID) {
                return array('used' => count($aShopIDs));
            }

            $aAbonNew = $this->abonementData($nAbonementNewID);
            $aAbonOld = $this->abonementData($nAbonementID);
            if (empty($aAbonNew) || empty($aAbonOld)) {
                return false;
            }

            $aUpdate['svc_abonement_id'] = $aAbonNew['id'];
            $aUpdate['svc_abonement_auto'] = 0;
            $aUpdate['svc_abonement_auto_id'] = 0;
            $sEmpty = '0000-00-00 00:00:00';
            foreach ($aShopIDs as $nShopID) {
                $aShop = $this->shopData($nShopID, array('svc', 'user_id', 'svc_abonement_id', 'svc_abonement_expire', 'svc_abonement_termless'));
                $aUpdate['svc'] = $aShop['svc'];
                # меняем значения услуг
                if ($aAbonNew['svc_mark'] && !$aAbonOld['svc_mark']) {
                    $aUpdate['svc_marked_to'] = $aShop['svc_abonement_termless'] ? Shops::SVC_TERMLESS_DATE : $aShop['svc_abonement_expire'];
                    $aUpdate['svc'] += Shops::SERVICE_MARK;
                }

                if (!$aAbonNew['svc_mark'] && $aAbonOld['svc_mark']) {
                    $aUpdate['svc_marked_to'] = $sEmpty;
                    $aUpdate['svc'] -= Shops::SERVICE_MARK;
                }

                if (!$aAbonNew['svc_fix'] && $aAbonOld['svc_fix']) {
                    $aUpdate['svc_fixed_to'] = $sEmpty;
                    $aUpdate['svc_fixed_order'] = $sEmpty;
                    $aUpdate['svc'] -= Shops::SERVICE_FIX;
                }

                if ($aAbonNew['svc_fix'] && !$aAbonOld['svc_fix']) {
                    $aUpdate['svc_fixed_to'] = $aShop['svc_abonement_termless'] ? Shops::SVC_TERMLESS_DATE : $aShop['svc_abonement_expire'];
                    $aUpdate['svc_fixed_order'] = $this->db->now();
                    $aUpdate['svc'] += Shops::SERVICE_FIX;
                }

                # деактивируем ОБ при превышении допустимого предела
                if ($aAbonNew['items'] && $aAbonNew['items'] < $aAbonOld['items']) {
                    $nLimit = $aAbonNew['items'];
                    $nCount = BBS::model()->itemsCount(array('user_id' => $aShop['user_id'], 'shop_id' => $nShopID, 'is_publicated' => 1, 'status' => BBS::STATUS_PUBLICATED));
                    if ($nLimit && ($nLimit < $nCount)) {
                        BBS::model()->itemsUpdateByFilter(array(
                            'status'         => BBS::STATUS_PUBLICATED_OUT,
                            'status_prev'    => BBS::STATUS_PUBLICATED,
                            'status_changed' => $this->db->now(),
                            'publicated_to'  => $this->db->now(),
                            'is_publicated'  => 0,
                        ), array(
                            'is_publicated'  => 1,
                            'status'         => BBS::STATUS_PUBLICATED,
                            'user_id'        => $aShop['user_id'],
                            'shop_id'        => $nShopID,
                        ), array(
                            'context' => __FUNCTION__,
                            'orderBy' => 'svc, publicated',
                            'limit'   => ($nCount - $nLimit),
                        ));
                    }
                }

                $this->shopSave($nShopID, $aUpdate);
            }
        }

        $res = $this->db->delete(TABLE_SHOPS_ABONEMENTS, array('id'=>$nAbonementID));
        if ( ! empty($res)) {
            return true;
        }
        return false;
    }

    /**
     * Деактивация услуги абонемент
     * @param integer $nShopID ID магазина
     * @return boolean
     */
    public function abonementDeactivate($nShopID)
    {
        if (empty($nShopID)) return false;

        $aShop = $this->shopData($nShopID, array('svc', 'user_id', 'svc_abonement_id'));
        if (empty($aShop) || ! $aShop['svc_abonement_id']) {
            return false;
        }
        $abonementID = $aShop['svc_abonement_id'];

        static $svcCache = array();
        if ( ! isset($svcCache[$abonementID])) {
            $svcCache[$abonementID] = $this->abonementData($abonementID);
        }

        $aSvcData = $svcCache[$abonementID];
        if (empty($aSvcData)) {
            return false;
        }

        $sEmpty = '0000-00-00 00:00:00';
        $aUpdate['svc_abonement_id'] = 0;
        $aUpdate['svc_abonement_auto'] = 0;
        $aUpdate['svc_abonement_auto_id'] = 0;
        $aUpdate['svc_abonement_expire'] = $sEmpty;
        if ($aShop['svc'] & Shops::SERVICE_ABONEMENT) {
            $aShop['svc'] -= Shops::SERVICE_ABONEMENT;
        }
        # деактивируем платные услуги
        if ($aSvcData['svc_mark']) {
            $aUpdate['svc_marked_to'] = $sEmpty;
            if ($aShop['svc'] & Shops::SERVICE_MARK) {
                $aShop['svc'] -= Shops::SERVICE_MARK;
            }
        }
        if ($aSvcData['svc_fix']) {
            $aUpdate['svc_fixed_to'] = $sEmpty;
            $aUpdate['svc_fixed_order'] = $sEmpty;
            if ($aShop['svc'] & Shops::SERVICE_FIX) {
                $aShop['svc'] -= Shops::SERVICE_FIX;
            }
        }
        $aUpdate['svc'] = $aShop['svc'];
        $this->shopSave($nShopID, $aUpdate);

        return $aUpdate;
    }

    /**
     * Деактивация лишних объявлений магазина (услуга абонемент),
     * исходя из настроек текущего активированного тарифа
     * @param integer $shopID ID магазина
     */
    public function abonementItemsUnpublicate($shopID)
    {
        if ( ! Shops::abonementEnabled() || empty($shopID)) {
            return;
        }
        $shop = $this->shopData($shopID, array('svc', 'user_id', 'svc_abonement_id'));
        if (empty($shop)) {
            return;
        }
        $abonementID = $shop['svc_abonement_id'];

        static $svcCache = array();
        if ( ! isset($svcCache[$abonementID])) {
            $svcCache[$abonementID] = $this->abonementData($abonementID);
        }
        $abonement = $svcCache[$abonementID];
        $limit = (!empty($abonement['items']) ? $abonement['items'] : 0);
        if ($limit <= 0 || empty($abonement)) {
            return;
        }
        $count = BBS::model()->itemsCount(array('user_id' => $shop['user_id'], 'shop_id' => $shopID, 'is_publicated' => 1, 'status' => BBS::STATUS_PUBLICATED));
        if ($limit < $count) {
            BBS::model()->itemsUpdateByFilter(array(
                'status'         => BBS::STATUS_PUBLICATED_OUT,
                'status_prev'    => BBS::STATUS_PUBLICATED,
                'status_changed' => $this->db->now(),
                'publicated_to'  => $this->db->now(),
                'is_publicated'  => 0,
            ), array(
                'is_publicated'  => 1,
                'status'         => BBS::STATUS_PUBLICATED,
                'user_id'        => $shop['user_id'],
                'shop_id'        => $shopID,
            ), array(
                'context' => __FUNCTION__,
                'orderBy' => 'svc, svc_fixed_order, publicated_order',
                'limit'   => ($count - $limit),
            ));
        }
    }

    /**
     * Применить функцию для магазинов, у которых нет и небыло ранее активированного тарифного плана
     * @param callable $callback функция
     */
    public function abonementActivateAll(callable $callback)
    {
        $this->db->select_iterator('
            SELECT S.*, SL.*
            FROM ' . TABLE_SHOPS . ' S INNER JOIN '.TABLE_SHOPS_LANG.' SL ON '.$this->db->langAnd(false, 'S', 'SL'). '
            WHERE S.svc_abonement_id = 0 AND (S.svc_abonement_one_time IS NULL OR S.svc_abonement_one_time = :empty)
        ', array(':empty'=>''), $callback);
    }

    /**
     * Применить функцию для магазинов, выбранных по фильтру
     * @param array $filter фильтр выборки магазинов
     * @param callable $callback функция
     */
    public function abonementUpdateAll($filter, callable $callback)
    {
        $filter = $this->prepareFilter($filter, 'S');

        $this->db->select_iterator('
            SELECT S.*
            FROM '.TABLE_SHOPS.' S
            '.$filter['where'], $filter['bind'], $callback);
    }


    public function getLocaleTables()
    {
        return array(
            TABLE_SHOPS             => array('type' => 'table',  'fields' => $this->langShops,      'title' => _t('shops', 'Магазины')),
            TABLE_SHOPS_CATEGORIES  => array('type' => 'table',  'fields' => $this->langCategories, 'title' => _t('shops', 'Категории')),
            TABLE_SHOPS_ABONEMENTS  => array('type' => 'fields', 'fields' => $this->langAbonements, 'title' => _t('shops', 'Абонемент: тарифы')),
        );
    }
}