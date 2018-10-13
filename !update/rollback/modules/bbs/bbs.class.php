<?php

class BBS_ extends BBSBase
{
    public function init()
    {
        parent::init();

        if (bff::$class == $this->module_name && Request::isGET()) {
            bff::setActiveMenu('//index');
        }
    }

    /**
     * Блок последних / премиум объявлений на главной
     * @param mixed $type тип объявлений: false - премиум => последние, 'last' - последние, 'premium' - премиум
     * @param array $opts доп. параметры
     * @return string|array HTML
     */
    public function indexLastBlock($type = false, $opts = array())
    {
        # доп. параметры:
        if ( ! is_array($opts)) {
            $opts = array('dataOnly'=>!empty($opts));
        }
        func::array_defaults($opts, array(
            'dataOnly' => false,
            'category' => 0,
            'region'   => 0,
            'limit'    => 0,
            'title'    => '',
        ));
        $dataOnly = $opts['dataOnly'];
        $limit = $opts['limit'];

        # тип объявлений:
        $premiumEnabled = Svc::model()->svcEnabled(static::SERVICE_PREMIUM);
        if (empty($type)) {
            $type = ($premiumEnabled ? 'premium' : 'last');
        } else if ($type == 'premium' && !$premiumEnabled) {
            if ($dataOnly) return array();
            return '';
        }
        if (!$limit) {
            $limit = config::sysTheme('bbs.index.'.$type.'.limit', 10, TYPE_UINT);
        }
        if (!$limit) {
            if ($dataOnly) return array();
            return '';
        }

        $aData = array();
        $sOrder = 'publicated_order DESC';

        $aFilter = array(
            'is_publicated' => 1,
            'status' => self::STATUS_PUBLICATED,
        );
        if ($opts['category']) {
            $aFilter[':cat-filter'] = $opts['category'];
        }
        if ( ! $opts['region']) {
            if (config::sysAdmin('bbs.index.'.$type.'.region', false, TYPE_BOOL)) {
                $aFilter[':region-filter'] = Geo::filter('id');
            }
        } else {
            $aFilter[':region-filter'] = $opts['region'];
        }

        $aData['type'] = $type;
        $aData['title'] = _t('bbs', 'Последние объявления');
        if ($type == 'premium') {
            $aData['title'] = _t('bbs', 'Премиум объявления');
            $sOrder = 'svc_premium_order DESC';
            if (config::sysAdmin('bbs.index.premium.rand', false, TYPE_BOOL)) {
                $sOrder = 'RAND()';
            }
            $aFilter['svc'] = array('>', 0);
            $aFilter['svc_premium'] = 1;
        }
        if ($opts['title'] !== '') {
            $aData['title'] = $opts['title'];
        }

        $aData['items'] = $this->model->itemsList($aFilter, false, array(
            'context' => 'last-block',
            'orderBy' => $sOrder,
            'limit'   => $limit,
        ));
        if (empty($aData['items'])) {
            if ($dataOnly) return array();
            return '';
        }
        if ($dataOnly) {
            return $aData['items'];
        }

        return $this->viewPHP($aData, 'index.last.block');
    }

    /**
     * Список выбора категорий
     * @param string $sType тип списка
     * @param string $mDevice тип устройства bff::DEVICE_ или 'init'
     * @param int $nParentID ID parent-категории
     */
    public function catsList($sType = '', $mDevice = '', $nParentID = 0)
    {
        $showAll = false;
        
        if (Request::isAJAX()) {
            $sType = $this->input->getpost('act', TYPE_STR);
            $mDevice = $this->input->post('device', TYPE_STR);
            $nParentID = $this->input->post('parent', TYPE_UINT);
            $showAll = $this->input->post('showAll', TYPE_BOOL);
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $sListingUrl = static::url('items.search');
        $oIcon = static::categoryIcon(0);
        $ICON_BIG = BBSCategoryIcon::BIG;
        $ICON_SMALL = BBSCategoryIcon::SMALL;
        switch ($sType) {
            case 'index': # список категории на главной
            {
                if ($mDevice == bff::DEVICE_DESKTOP) # desktop+tablet
                {
                    /** @var integer $nSubSlice - максимально допустимое видимое кол-во подкатегорий */
                    $nSubSlice = config::sysTheme('bbs.index.subcats.limit', 5, TYPE_UINT);
                    $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_BIG);
                    if (!empty($aData)) {
                        $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
                        foreach ($aData as $k => $v) {
                            $v['l'] = static::url('items.search', array('keyword'=>$v['k'], 'landing_url'=>$v['lpu']));
                            $v['i'] = $oIcon->url($v['id'], $v['i'], $ICON_BIG);
                            foreach ($v['sub'] as $kk => $vv) {
                                $v['sub'][$kk]['l'] = static::url('items.search', array('keyword'=>$vv['k'], 'landing_url'=>$vv['lpu']));
                            }
                            $v['subn'] = sizeof($v['sub']); # всего подкатегорий
                            $v['sub'] = array_slice($v['sub'], 0, $nSubSlice); # оставляем не более {$nSubSlice}
                            $v['subv'] = sizeof($v['sub']); # кол-во отображаемых подкатегорий
                            $aData[$k] = $v;
                        }
                    }
                    return $aData;
                } else {
                    if ($mDevice == bff::DEVICE_PHONE) {
                        $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_SMALL);
                        if (!empty($aData)) {
                            foreach ($aData as $k => $v) {
                                $aData[$k]['l'] = static::url('items.search', array('keyword'=>$v['k'], 'landing_url'=>$v['lpu']));
                                $aData[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                            }
                        }
                        if ($nParentID > self::CATS_ROOTID) {
                            # список подкатегорий
                            $aParent = array(
                                'id',
                                'pid',
                                'numlevel',
                                'numleft',
                                'numright',
                                'title',
                                'keyword',
                                'landing_url',
                                'icon_' . $ICON_SMALL . ' as icon',
                                'subs'
                            );
                            $aParent = $this->model->catData($nParentID, $aParent);
                            if (!empty($aParent)) {
                                $aParent['link'] = static::url('items.search', array('keyword'=>$aParent['keyword'], 'landing_url'=>$aParent['landing_url']));
                                $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                                if ($aParent['main']) {
                                    $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                                } else {
                                    # глубже второго уровня, получаем иконку основной категории
                                    $aParentsID = $this->model->catParentsID($aParent, false);
                                    if (!empty($aParentsID[1])) {
                                        $aParentMain = $this->model->catData($aParentsID[1], array(
                                                'id',
                                                'icon_' . $ICON_SMALL . ' as icon'
                                            )
                                        );
                                        $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                    }
                                }
                                $aData = array('cats' => $aData, 'parent' => $aParent, 'step' => 2);
                                $aData = $this->viewPHP($aData, 'index.cats.phone');
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
                            # список основных категорий
                            $aData = array('cats' => $aData, 'step' => 1);

                            return $this->viewPHP($aData, 'index.cats.phone');
                        }
                    }
                }
            }
            break;
            case 'search': # фильтр категории
            {
                if ($mDevice == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'landing_url',
                            'icon_' . $ICON_BIG . ' as icon',
                            'items',
                            'subs'
                        );
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_BIG);
                    if (!empty($aData)) {
                        foreach ($aData as &$v) {
                            $v['l'] = static::url('items.search', array('keyword'=>$v['k'], 'landing_url'=>$v['lpu']));
                            $v['i'] = $oIcon->url($v['id'], $v['i'], $ICON_BIG);
                            $v['active'] = ($v['id'] == $nSelectedID);
                        }
                        unset($v);
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['link'] = static::url('items.search', array('keyword'=>$aParent['keyword'], 'landing_url'=>$aParent['landing_url']));;
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем настройки основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array('cats' => $aData, 'parent' => $aParent, 'step' => 2);
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
                        $geo = Geo::filter();
                        if (!empty($geo['id'])) {
                            $nTotal = $this->model->itemsCountByFilter(array('cat_id' => 0, 'region_id' => $geo['id'], 'delivery' => 0), false, true, 60);
                            if ($geo['country']) {
                                $nTotal += $this->model->itemsCountByFilter(array('cat_id' => 0, 'region_id' => $geo['country'], 'delivery' => 1), false, true, 60);
                            }
                        } else {
                            $nTotal = $this->model->itemsCountByFilter(array('cat_id' => 0, 'region_id' => 0, 'delivery' => 0), false, true, 60);
                        }
                        $aData = array('cats' => $aData, 'total' => $nTotal, 'step' => 1);
                        return $this->viewPHP($aData, 'search.cats.desktop');
                    }
                } else if ($mDevice == bff::DEVICE_PHONE) {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'landing_url',
                            'icon_' . $ICON_SMALL . ' as icon',
                            'subs'
                        );
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_SMALL);
                    if (!empty($aData)) {
                        foreach ($aData as $k => $v) {
                            $aData[$k]['l'] = static::url('items.search', array('keyword'=>$v['k'], 'landing_url'=>$v['lpu']));
                            $aData[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                            $aData[$k]['active'] = ($v['id'] == $nSelectedID);
                        }
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['link'] = static::url('items.search', array('keyword'=>$aParent['keyword'], 'landing_url'=>$aParent['landing_url']));
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_SMALL . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                }
                            }
                            $aData = array('cats' => $aData, 'parent' => $aParent, 'step' => 2);
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
            break;
            case 'form': # форма объявления: выбор категории
            {
                if ($mDevice == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'landing_url',
                            'icon_' . $ICON_BIG . ' as icon',
                            'subs'
                        );
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_BIG);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_BIG);
                            $aCats[$k]['active'] = ($v['id'] == $nSelectedID);
                        }
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2, 'showAll' => $showAll);
                            $aData = $this->viewPHP($aData, 'item.form.cat.desktop');
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
                        $aData = array('cats' => $aCats, 'step' => 1, 'showAll' => $showAll);
                        return $this->viewPHP($aData, 'item.form.cat.desktop');
                    }
                } else if ($mDevice == bff::DEVICE_PHONE) {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'landing_url',
                            'icon_' . $ICON_SMALL . ' as icon',
                            'subs'
                        );
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                            $aCats[$k]['active'] = ($v['id'] == $nSelectedID);
                        }
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_SMALL . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2, 'showAll' => $showAll);
                            $aData = $this->viewPHP($aData, 'item.form.cat.phone');
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
                        $aData = array('cats' => $aCats, 'step' => 1, 'showAll' => $showAll);
                        return $this->viewPHP($aData, 'item.form.cat.phone');
                    }
                } else if ($mDevice == 'init') {
                    /**
                     * Формирование данных об основных категориях
                     * для jForm.init({catsMain:DATA});
                     */
                    $aCats = $this->model->catsList('form', bff::DEVICE_PHONE, $nParentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                        }
                    }

                    return $aCats;
                }
            }
            break;
        }
    }

    /**
     * Поиск и результаты поиска
     */
    public function search()
    {
        $nPerpage = config::sysAdmin('bbs.search.pagesize', 12, TYPE_UINT);
        $f = $this->searchFormData();
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

        # SEO данные
        $seoKey = '';
        $seoNoIndex = false;
        $seoData = array(
            'page'   => & $f['page'],
            'region' => Geo::regionData(($f_region ? $f_region : Geo::defaultCountry())),
        );
        if (Geo::coveringType(Geo::COVERING_COUNTRIES)) {
            $regionData = Geo::regionData($f_region);
            if (!$f_region) $seoData['region'] = '';
            $seoData['city'] = (Geo::isCity($regionData) ? Geo::regionData($f_region) : '');
            $seoData['country'] = (!empty($regionData) ? ($regionData['numlevel'] == Geo::lvlCountry ? $regionData : Geo::regionData($regionData['country'])) : '');
        } else if (Geo::coveringType(Geo::COVERING_REGION)) {
            if (!$f_region && ($region = Geo::coveringRegion())) {
                $seoData['region'] = Geo::regionData($region);
            }
        }

        # формируем данные о текущей категории:
        $catID = 0;
        $catData = array();
        $catFields = array(
            'id',
            'numlevel',
            'seek',
            'addr',
            'addr_metro',
            'keyword',
            'landing_url',
            'owner_business',
            'owner_search',
            'price',
            'price_sett',
            'photos',
            'enabled',
            'subs_filter_title',
        );
        if (!Request::isAJAX()) {
            $catKey = $this->input->get('cat', TYPE_STR);
            $catKey = trim($catKey, ' /\\');
            if (!empty($catKey)) {
                $catKeyReal = $this->model->catToReal($catKey, true);
                $catData = $this->model->catDataByFilter(array('keyword' => $catKeyReal), array_merge($catFields, array(
                    'pid', 'subs', 'numleft', 'numright', 'numlevel', 'enabled',
                    'title', 'mtitle', 'mkeywords', 'mdescription', 'mtemplate',
                    'seotext', 'titleh1', 'type_offer_search', 'type_seek_search',
                    'owner_private_search', 'owner_business_search', 'list_type',
                )));
                if (empty($catData) || !$catData['enabled']) {
                    $this->errors->error404();
                }

                $catID = $catData['id'];
                $catLevel = $catData['numlevel'];
                # подменяем часть данных реальной категории на данные из виртуальной категории
                if ($catKey != $catKeyReal) {
                    $catDataVirtual = $this->model->catDataByFilter(array('keyword' => $catKey), array(
                        'id', 'pid', 'subs', 'numleft', 'numright', 'numlevel', 'enabled',
                        'title', 'mtitle', 'mkeywords', 'mdescription', 'mtemplate',
                        'seotext', 'titleh1', 'keyword', 'landing_url',
                    ));
                    # кроме id/numlevel реальной категории
                    $catID = $catDataVirtual['id'];
                    $catLevel = $catDataVirtual['numlevel'];
                    unset($catDataVirtual['id'], $catDataVirtual['numlevel']);
                    $catData = array_merge($catData, $catDataVirtual);
                }

                # категории в фильтре:
                $tmpData = $catData;
                $tmpData['id'] = $catID;
                $tmpData['numlevel'] = $catLevel;
                if (DEVICE_DESKTOP_OR_TABLET) {
                    $filterLevel = static::catsFilterLevel();
                    # корректируем данные выпадающего списка - выбранной категории
                    $dropdown = array('id' => $tmpData['id'], 'title' => $catData['title']);
                    if ($catLevel > $filterLevel) {
                        $parentData = $this->catsFilterParent($tmpData);
                        $dropdown['id'] = $parentData['pid'];
                        $dropdown['title'] = $parentData['title'];
                    } else if ($catLevel == $filterLevel) {
                        $dropdown['id'] = $catData['pid'];
                    }
                    $catData['dropdown'] = $dropdown;
                    # формируем данные для фильтров подкатегорий - выбранной категории
                    if ($catLevel >= $filterLevel) {
                        $catData['subs_filter'] = $this->catsFilterData($tmpData);
                    }
                }

                bff::filterData('bbs-search-category', $catData);
                $f_c = $catData['id']; # ID реальной категории

                # хлебные крошки
                $catData['crumbs'] = $this->categoryCrumbs($catID, __FUNCTION__);

                # типы категорий
                if (static::CATS_TYPES_EX) {
                    $catData['types'] = $this->model->cattypesByCategory($catData['id']);
                    if (!empty($catData['types'])) {
                        if (!isset($catData['types'][$f_ct])) $f_ct = key($catData['types']);
                        foreach ($catData['types'] as &$v) {
                            if ($v['items'] >= 1000) $v['items'] = number_format($v['items'], 0, '', ' ');
                        }
                        unset($v);
                    }
                } else {
                    $catData['types'] = $this->model->cattypesSimple($catData, true);
                }

                # корректируем тип списка
                if (!$catData['addr'] && $f_lt == self::LIST_TYPE_MAP) {
                    $f_lt = self::LIST_TYPE_LIST;
                }

                # SEO: Поиск в категории
                $seoKey = 'search-category';
                $metaCategories = array();
                foreach ($catData['crumbs'] as $k => &$v) {
                    if ($k) $metaCategories[] = $v['title'];
                }
                unset($v);
                $seoData['category'] = $catData['title'];
                $seoData['categories'] = join(', ', $metaCategories);
                $seoData['categories.reverse'] = join(', ', array_reverse($metaCategories, true));
                $seoData['category+parent'] = join(', ', array_reverse(array_splice($metaCategories,(sizeof($metaCategories) > 2 ? sizeof($metaCategories) - 2 : 0))));
            } else {
                # SEO: Поиск (все категории)
                $seoKey = 'search';
            }

            # тип списка по-умолчанию
            if (!$f_lt) {
                if ( ! empty($catData['list_type'])) {
                    $f_lt = $catData['list_type'];
                } else {
                    $listType = config::sysAdmin('bbs.search.list.type', static::LIST_TYPE_LIST, TYPE_UINT);
                    if ($listType > 0) {
                        $f_lt = $listType;
                    }
                }
            }
        } else {
            $catID = $f_c;
            $catData = $this->model->catData($catID, $catFields);
            if (empty($catData) || !$catData['enabled']) $catID = 0;
        }
        if (!$catID) {
            $f_c = $f_ct = 0;
            $catData = array('id' => 0, 'addr' => 0, 'seek' => false, 'price' => false, 'keyword' => '', 'landing_url' => '');
            if (!Request::isAJAX()) {
                $catData['crumbs'] = $this->categoryCrumbs(0, __FUNCTION__);
            }
        }

        # Формируем запрос поиска:
        $sql = array(
            'is_publicated' => 1,
            'status' => self::STATUS_PUBLICATED,
        );
        if ($f_c > 0 && $catData['numlevel'] > 0) {
            $sql[':cat-filter'] = $f_c;
        }
        if (static::CATS_TYPES_EX) {
            if ($f_ct > 0) $sql['cat_type'] = $f_ct;
        } else {
            if ($catData['seek'] > 0) { $sql['cat_type'] = $f_ct; }
        }
        if ($f_region) {
            $sql[':region-filter'] = $f_region;
        }
        $seoResetCounter = sizeof($sql); # всю фильтрацию ниже скрываем от индексации

        $sphinxSearch = false;
        $seoData['query'] = '';
        if (strlen($f_q) > 1) {
            $sql[':query'] = $f_q;
            $seoData['query'] = $f_q;
            Banners::i()->viewQuery($f_q);
            $sphinxSearch = BBSItemsSearchSphinx::enabled();
        }
        if ($f_lt == self::LIST_TYPE_MAP) {
            # на карту выводим только с корректно указанными координатами
            $sql['addr_lat'] = array('!=', 0);
            $seoResetCounter++;
        }

        $device = bff::device();
        if (   $device == bff::DEVICE_DESKTOP
            || $device == bff::DEVICE_TABLET
            || ( $device == bff::DEVICE_PHONE && static::filterVertical())
        ){
            # дин. свойства:
            if ($catID > 0) {
                $dp = $this->dp()->prepareSearchQuery($f['d'], $f['dc'], $this->dpSettings($catID), '', array('sphinx'=>$sphinxSearch));
                if (!empty($dp)) $sql[':dp'] = $dp;
            }
            # с фото:
            if ($f['ph']) {
                $sql['imgcnt'] = array('>', 0);
            }
            # тип владельца:
            if (!empty($f['ow']) && $catData['owner_search']) { $sql['owner_type'] = $f['ow']; }
            # цена:
            if ($catID > 0 && $catData['price']) {
                $priceQuery = $this->model->preparePriceQuery($f['p'], $catData);
                if (!empty($priceQuery)) $sql[':price'] = $priceQuery;
            }
            # район:
            if (!empty($f['rd'])) {
                $sql['district_id'] = $f['rd'];
            }
            # метро:
            if (!empty($f['rm'])) {
                $sql['metro_id'] = $f['rm'];
            }
        } else if($device == bff::DEVICE_PHONE) {
            # дин. свойства:
            if ($catID > 0) {
                $dp = $this->dp()->prepareSearchQuery($f['md'], $f['mdc'], $this->dpSettings($catID), '', array('sphinx'=>$sphinxSearch));
                if (!empty($dp)) $sql[':dp'] = $dp;
            }
            # с фото:
            $sql['imgcnt'] = array(($f['mph'] ? '>' : '>='),0);
            # тип владельца:
            if (!empty($f['mow']) && $catData['owner_search']) { $sql['owner_type'] = $f['mow']; }
            # цена:
            if ($catID > 0 && $catData['price']) {
                $priceQuery = $this->model->preparePriceQuery(array('r' => array($f['mp'])), $catData);
                if (!empty($priceQuery)) $sql[':price'] = $priceQuery;
            }
            # район:
            if ($f['mrd']) {
                $sql['district_id'] = $f['mrd'];
            }
        }

        # Выполняем поиск ОБ:
        if (Svc::model()->svcEnabled(static::SERVICE_FIX)) {
            $sqlOrderBy = 'svc_fixed DESC, svc_fixed_order DESC, publicated_order DESC, id DESC';
        } else {
            $sqlOrderBy = 'publicated_order DESC, id DESC';
        }
        switch ($f_sort) {
            case 'price-desc':
                $sqlOrderBy = 'price_search DESC';
                $seoNoIndex = true;
                break;
            case 'price-asc':
                $sqlOrderBy = 'price_search ASC';
                $seoNoIndex = true;
                break;
        }
        $aData = array('items' => array(), 'pgn' => '');

        $nTotal = $this->model->itemsList($sql, true, array('context' => 'search'));
        if ($nTotal > 0) {
            # pagination links
            $aPgnLinkQuery = $f;
            if ($f['c']) unset($aPgnLinkQuery['c']);
            if ($f['region']) unset($aPgnLinkQuery['region']);
            if ($f['lt'] == self::LIST_TYPE_LIST) unset($aPgnLinkQuery['lt']);
            if ($f['sort'] == 'new') unset($aPgnLinkQuery['sort']);
            $nPgnPrice = 0;
            if (!empty($f['p'])) foreach ($f['p'] as &$v) {
                if (!empty($v)) $nPgnPrice++;
            }
            unset($v);
            if (!$nPgnPrice) unset($aPgnLinkQuery['p']);
            $oPgn = new Pagination($nTotal, $nPerpage, array(
                'link' => static::url('items.search', array('keyword' => $catData['keyword'], 'landing_url' => $catData['landing_url'])),
                'query' => $aPgnLinkQuery,
            ));
            # list
            $bUseCategoryCurrency = config::sysAdmin('bbs.search.category.currency', true, TYPE_BOOL);
            if ($bUseCategoryCurrency && empty($catData['price_sett']['curr'])) {
                $catData['price_sett']['curr'] = Site::currencyDefault('id');
            }
            $aData['items'] = $this->model->itemsList($sql, false, array(
                'context' => 'search',
                'orderBy' => $sqlOrderBy,
                'limit'   => $oPgn->getLimit(),
                'offset'  => $oPgn->getOffset(),
                'listCurrency'  => ($bUseCategoryCurrency ? $catData['price_sett']['curr'] : 0),
            ));
            $aData['pgn'] = $oPgn->view(array('pagelast'=>false));
            $f['page'] = $oPgn->getCurrentPage();
        }

        $nNumStart = ($f_page <= 1 ? 1 : (($f_page - 1) * $nPerpage) + 1);
        if (Request::isAJAX()) { # ajax ответ
            if ($this->input->post('mapVertical', TYPE_UINT)) {
                config::set('bbs-map-vertical', true);
            }
            $this->ajaxResponseForm(array(
                    'list'  => $this->searchList(bff::device(), $f_lt, $aData['items'], array('numStart' => $nNumStart, 'showBanners' => true, 'filter' => &$f)),
                    'items' => &$aData['items'],
                    'pgn'   => $aData['pgn'],
                    'total' => $nTotal,
                )
            );
        }

        # SEO
        $this->seo()->robotsIndex(!(sizeof($sql) - $seoResetCounter) && !$seoNoIndex);
        $this->seo()->canonicalUrl(static::url('items.search', array('keyword' => $catData['keyword'], 'landing_url' => $catData['landing_url']), true),
            array('page' => $f['page'], 'ct' => $f_ct),
            ($nTotal > 0 ? array('page-current' => $f['page'], 'page-last' => $oPgn->getPageLast()) : array())
        );
        # подготавливаем хлебные крошки для подстановки макросов
        if (!$f_region && Geo::coveringType(array(Geo::COVERING_COUNTRY,Geo::COVERING_REGION))) {
            $f_region = Geo::coveringRegion();
        }
        if ($catID > 0) {
            foreach ($catData['crumbs'] as &$v) {
                $seoData['category'] = $v['title'];
                $this->setMeta($seoKey, $seoData, $v, array(
                    'breadcrumb' => array('ignore' => array((!$f_region ? 'region' : ''),'city')),
                ));
            } unset($v);
        }
        $this->setMeta($seoKey, $seoData, $catData, array(
            'titleh1' => array('ignore' => array((!$f_region ? 'region' : ''),)),
        ));
        if (empty($catData['types'])) {
            $catData['types'] = array(array('id'=>static::TYPE_OFFER,'title'=>_t('search','Объявления'),'items'=>$nTotal));
        }
        # Заголовок H1
        $aData['titleh1'] = _t('search', 'Поиск объявлений');
        if (($catID > 0 || SEO::landingPage() !== false) && ! empty($catData['titleh1'])) {
            $aData['titleh1'] = $catData['titleh1'];
        } else if ( ! empty($f_q)) {
            $aData['titleh1'] = _t('search', 'Результаты поиска по запросу "[query]"', array('query'=>$f_q));
        }

        $aData['total'] = $nTotal;
        $aData['num_start'] = $nNumStart;
        $aData['cat'] = & $catData;
        $aData['f'] = & $f;

        # Типы списка:
        $listTypes = array(
            static::LIST_TYPE_LIST    => array('t'=>_t('search','Списком'), 'i'=>'fa fa-th-list','a'=>0),
            static::LIST_TYPE_GALLERY => array('t'=>_t('search','Галереей'),'i'=>'fa fa-th','a'=>0),
            static::LIST_TYPE_MAP     => array('t'=>_t('search','На карте'),'i'=>'fa fa-map-marker','a'=>0),
        );
        if( ! $catData['addr'] ) unset($listTypes[static::LIST_TYPE_MAP]);
        if( ! isset($listTypes[$f_lt]) ) $f_lt = key($listTypes);
        $listTypes[$f_lt]['a'] = true;
        $aData['listTypes'] = &$listTypes;
        $aData['isMap'] = ($f_lt == static::LIST_TYPE_MAP);

        # Типы сортировки:
        $sortTypes = array(
            'new' => array('t'=>_t('search','Самые новые')),
        );
        if ($catData['price']) {
            $sortTypes['price-asc'] = array('t'=>_t('search','От дешевых к дорогим'));
            $sortTypes['price-desc'] = array('t'=>_t('search','От дорогих к дешевым'));
        }
        if (!isset($sortTypes[$f_sort])) {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $f_sort = key($sortTypes);
        }
        $aData['sortTypes'] = &$sortTypes;

        # Вертикальный фильтр:
        $filterVertical = static::filterVertical();
        if ($filterVertical) {
            if (empty($catData['id'])) {
                $filterVertical = false;
            } else {
                $dp = $this->dp()->form($catData['id'], array(), true, true, false, 'search.form.desktop.vertical', $this->module_dir_tpl, array(
                    'price' => array(
                        'enabled' => $catData['price'],
                        'sett'    => ( $catData['price'] && ! empty($catData['price_sett']) ? $catData['price_sett'] : array() ) ),
                    'photos' => ($catData['photos'] > 0),
                    'owner_search' => $catData['owner_search'],
                    'owner_business' => ! empty($catData['owner_business']),
                    'owner_business_title' => array(
                        static::OWNER_PRIVATE  => ( ! empty($catData['owner_private_search']) ? $catData['owner_private_search'] : _t('bbs','От частных лиц')),
                        static::OWNER_BUSINESS => ( ! empty($catData['owner_business_search']) ? $catData['owner_business_search'] : _t('bbs','Только бизнес объявления'))
                    ),
                    'f'   => &$f,
                    'cat' => &$catData,
                ));
                $aData['filterVerticalBlock'] = $dp['form'];
            }
        }
        $aData['filterVertical'] = $filterVertical;

        # дополнительные блоки:
        $aData['catsBlock'] = $this->searchCategoriesBlock($catID);
        $aData['premiumBlock'] =  $this->searchPremiumBlock(array('id'=>$catID, 'search'=>true));
        $aData['relinkBlock'] = $this->searchRelinkBlock($catData);

        # RSS-ссылка:
        if (static::rssEnabled() && !empty($catData['id'])) {
            $aData['rss'] = array(
                'link' => static::url('rss', array('cat' => $catData['id'], 'region' => $f_region)),
                'title' => $catData['title'],
            );
        }

        return $this->viewPHP($aData, 'search');
    }

    /**
     * Форма поиска
     */
    public function searchForm()
    {
        $aData['f'] = $this->searchFormData();
        $aData['f']['seek'] = (!static::CATS_TYPES_EX && $aData['f']['ct'] == self::TYPE_SEEK);

        if (DEVICE_PHONE) {
            # определяем наличие отмеченных фильтров
            $f = & $aData['f'];
            $aData['f_filter_active'] = (
                !empty($f['d']) /* дин. св-ва */ || !empty($f['mp']) /* цена */ ||
                !empty($f['mph']) /* фото */ || !empty($f['mow']) /* тип владельца */
            );
        }

        $aData['filterVertical'] = static::filterVertical();

        # фильтр: категория (определяется в BBS::search)
        $catData = bff::filterData('bbs-search-category');
        $aData['catData'] = &$catData;
        $aData['catID'] = $catID = ( ! empty($catData['id']) ? $catData['id'] : 0 );
        $aData['catACTIVE'] = ($catID > 0);
        $aData['catACTIVE_STEP'] = ($aData['catACTIVE'] ? ($catData['subs'] || $catData['numlevel']>1 ? 2 : 1) : 1);

        # блок фильтров категории
        if ($aData['catACTIVE'] && !$aData['filterVertical']) {
            $dpExtra = array(
                'price' => array(
                    'enabled' => $catData['price'],
                    'sett'    => ( $catData['price'] && ! empty($catData['price_sett']) ? $catData['price_sett'] : array() ) ),
                'photos' => ($catData['photos'] > 0),
                'owner_search' => $catData['owner_search'],
                'owner_business' => ! empty($catData['owner_business']),
                'owner_business_title' => array(
                    static::OWNER_PRIVATE  => ( ! empty($catData['owner_private_search']) ? $catData['owner_private_search'] : _t('bbs','От частных лиц')),
                    static::OWNER_BUSINESS => ( ! empty($catData['owner_business_search']) ? $catData['owner_business_search'] : _t('bbs','Только бизнес объявления'))
                ),
                'f'   => &$aData['f'],
                'cat' => &$catData,
            );
            if (DEVICE_DESKTOP_OR_TABLET) {
                $dp = $this->dp()->form($catID, array(), true, true, false, 'search.form.desktop', $this->module_dir_tpl, $dpExtra);
                $aData['filterDesktopBlock'] = $dp['form'];
            }
            if (DEVICE_PHONE) {
                $dp = $this->dp()->form($catID, array(), true, true, false, 'search.form.phone', $this->module_dir_tpl, $dpExtra);
                $aData['filterPhoneBlock'] = $dp['form'];
            }
        }

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
            'ct'   => TYPE_UINT, # тип категории (продам, куплю, ...)
            'q'    => TYPE_TEXT, # поисковая строка
            'lt'   => TYPE_UINT, # тип списка (self::LIST_TYPE_)
            'sort' => TYPE_NOTAGS, # сортировка
            'cnt'  => TYPE_BOOL, # только кол-во
            'page' => TYPE_UINT, # страница
        );
        if (DEVICE_DESKTOP_OR_TABLET || static::filterVertical()) {
            $aParams += array(
                'd'  => TYPE_ARRAY, # дин. свойства
                'dc' => TYPE_ARRAY, # дин. свойства (child)
                'p'  => array(
                    TYPE_ARRAY, # цена
                    'f' => TYPE_PRICE, # от
                    't' => TYPE_PRICE, # до
                    'c' => TYPE_UINT, # ID валюты для "от-до"
                    'r' => TYPE_ARRAY_UINT, # диапазоны
                ),
                'rd' => TYPE_ARRAY_UINT, # район
                'rm' => TYPE_ARRAY_UINT, # метро
                'ph' => TYPE_BOOL, # с фото
                'ow' => TYPE_ARRAY_UINT, # тип владельца
            );
        }
        if (DEVICE_PHONE) {
            $aParams += array(
                'mq'  => TYPE_TEXT, # поисковая строка
                'md'  => TYPE_ARRAY, # дин. свойства
                'mdc' => TYPE_ARRAY, # дин. свойства (child)
                'mp'  => TYPE_UINT, # цена (только ID диапазона или 0)
                'mph' => TYPE_BOOL, # с фото
                'mow' => TYPE_ARRAY_UINT, # тип владельца
                'mrd' => TYPE_UINT, # район
            );
        }

        $data = $this->input->postgetm($aParams);

        # поисковая строка
        $device = bff::device();
        $data['q'] = $this->input->cleanSearchString(
            (in_array($device, array(
                    bff::DEVICE_DESKTOP,
                    bff::DEVICE_TABLET
                )
            ) ? $data['q'] : (isset($data['mq']) ? $data['mq'] : '')), 80
        );
        # страница
        if (!$data['page']) $data['page'] = 1;
        # регион
        $data['region'] = Geo::filter('id'); # user
        return $data;
    }

    /**
     * Формирование результатов поиска (список ОБ)
     * @param string $mDeviceID тип устройства
     * @param integer $nListType тип списка (self::LIST_TYPE_)
     * @param array $aItems @ref данные о найденных ОБ
     * @param array $extra доп параметры array(
     *      'numStart' => изначальный порядковый номер, default = 1
     *      'showBanners' => выводить банеры, default = false
     *      'filter' => текущие параметры фильтра
     * )
     * @return mixed
     */
    public function searchList($mDeviceID, $nListType, array &$aItems, array $extra = array())
    {
        $nNumStart = ( isset($extra['numStart']) ? $extra['numStart'] : 1 );
        if (!isset($extra['showBanners'])) $extra['showBanners'] = false;

        static $prepared = false;
        if (!$prepared) {
            $prepared = true;
            $this->itemsListPrepare($aItems, $nListType, $nNumStart);
        }
        if (empty($mDeviceID)) $mDeviceID = bff::device();

        if (empty($aItems)) {
            return $this->showInlineMessage(array(
                _t('bbs', 'Объявлений по вашему запросу не найдено')
            ));
        }

        $aTemplates = array(
            bff::DEVICE_DESKTOP => 'search.list.desktop',
            bff::DEVICE_TABLET  => 'search.list.desktop',
            bff::DEVICE_PHONE   => 'search.list.phone',
        );
        $aData = $extra;
        $aData['items'] = &$aItems;
        $aData['list_type'] = $nListType;
        return $this->viewPHP($aData, $aTemplates[$mDeviceID]);
    }

    /**
     * Быстрый поиск ОБ по строке
     * @param post ::string 'q' - строка поиска
     */
    public function searchQuick()
    {
        $nLimit = config::sysAdmin('bbs.search.quick.limit', 3, TYPE_UINT);
        if ( ! $nLimit) {
            $this->ajaxResponseForm(array('items'=>array(),'cnt'=>0));
        }
        $sQuery = $this->input->post('q', TYPE_NOTAGS, array('len' => 80));
        $sQuery = $this->input->cleanSearchString($sQuery, 80);

        $f = $this->input->postm(array(
            'c'  => TYPE_UINT, # категория
            'ct' => TYPE_UINT, # тип категории
        ));

        $aData = array();
        $sql = array(
            'is_publicated' => 1,
            'status' => self::STATUS_PUBLICATED,
        );
        if ($f['c'] > 0 && config::sysAdmin('bbs.search.quick.category', false, TYPE_BOOL)) {
            $sql[':cat-filter'] = $f['c'];
        }
        $nRegionID = Geo::filter('id'); # user
        if ($nRegionID > 0) {
            $sql[':region-filter'] = $nRegionID;
        }
        $sql[':query'] = $sQuery;
        $aData['items'] = $this->model->itemsQuickSearch($sql, $nLimit, 'publicated_order DESC');

        $aData['cnt'] = sizeof($aData['items']);
        foreach ($aData['items'] as &$v) {
            if (sizeof($v['img']) > 4) $v['img'] = array_slice($v['img'], 0, 4);
        }
        unset($v);
        $aData['items'] = $this->viewPHP($aData, 'search.quick');
        $this->ajaxResponseForm($aData);
    }

    /**
    * Блок перелинковки под списком объявлений
    * @param array $catData данные о категории
    * @return string HTML
    */
    public function searchRelinkBlock($catData)
    {
        if (!config::sysAdmin('bbs.search.relink.block', true, TYPE_BOOL)) {
            return '';
        }

        $data = array();
        $geo = Geo::filter(); # выбраный регион
        $coveringType = Geo::coveringType(); # тип покрытия
        $geoTitle = ''; # название региона, если есть
        if ( ! empty($geo['title']) && $coveringType != Geo::COVERING_CITY) {
            $geoTitle = ' '.(!empty($geo['declension']) ? $geo['declension'] : $geo['title']);
        }

        # категории
        $cats = array(static::CATS_ROOTID => array('t' => _t('bbs', 'Главные рубрики[region]', array('region'=>$geoTitle))));
        if ( ! empty($catData['crumbs'])) {
            foreach ($catData['crumbs'] as $v) {
                if ($catData['id'] == $v['id'] && empty($catData['subs'])) continue;
                $cats[ $v['id'] ] = array('t' => $v['title'].$geoTitle);
            }
        }
        foreach ($cats as $k => & $v) {
            $v['data'] = $this->model->catsDataByFilter(array('pid' => $k, 'enabled' => 1),
                array('id', 'pid', 'keyword', 'landing_url', 'title'), 60);
            foreach ($v['data'] as & $vv) {
                $vv['link'] = static::url('items.search', array('keyword' => $vv['keyword'], 'landing_url' => $vv['landing_url']));
                $vv['title'] .= $geoTitle;
            } unset($vv);
        } unset($v);
        $data['cats'] = & $cats;

        # регионы
        $addReg = '?region=';
        $linkParam = array('keyword' => $catData['keyword'], 'landing_url' => $catData['landing_url']);
        if ($geo['id']) {
            # есть фильтр по региону
            if ($geo['numlevel'] < Geo::lvlCity) {
                # выбрана страна или регион, выведем регионы или города
                $data['regs'] = $this->model->regionsItemsCounters(array(
                    'cat_id' => $catData['id'],
                    'pid' => $geo['id']));
                $paramName = '';
                switch ($geo['numlevel']) {
                    case Geo::lvlCountry:
                        $paramName = 'region';
                        break;
                    case Geo::lvlRegion:
                        $paramName = 'city';
                        break;
                }
                $link = $linkParam;
                foreach ($data['regs'] as &$v) {
                    $link[$paramName] = $v['keyword'];
                    $v['link'] = static::url('items.search', $link).($addReg ? $addReg.$v['id'] : '');
                }
                unset($v);
            }
            $crumb = array();
            $r = $geo;
            # хлебные крошки для выбранного региона
            do {
                switch ($coveringType) {
                    case Geo::COVERING_COUNTRY:
                        if ($r['numlevel'] == Geo::lvlCountry) {
                            break 2;
                        }
                        break;
                    case Geo::COVERING_REGION:
                        if ($r['numlevel'] == Geo::lvlRegion) {
                            break 2;
                        }
                        break;
                    case Geo::COVERING_CITIES:
                    case Geo::COVERING_CITY:
                        break 2;
                }

                $paramName = '';
                switch ($r['numlevel']) {
                    case Geo::lvlCountry:
                        $paramName = 'country';
                        break;
                    case Geo::lvlRegion:
                        $paramName = 'region';
                        break;
                    case Geo::lvlCity:
                        $paramName = 'city';
                        break;
                }

                $link = $linkParam;
                $link[$paramName] = $r['keyword'];
                $crumb[] = array(
                    'id' => $r['id'],
                    'title' => ! empty($r['declension']) ? $r['declension'] : $r['title'],
                    'link'    => static::url('items.search', $link).($addReg ? $addReg.$r['id'] : ''),
                );

                $r = $r['pid'] ? Geo::regionData($r['pid']) : false;
            } while( ! empty($r));
            $data['crumb'] = array_reverse($crumb);
        } else {
            # нет фильтра по региону, выбираем регионы в зависимости от настроек покрытия
            switch ($coveringType) {
                case Geo::COVERING_COUNTRIES:
                    $data['regs'] = $this->model->regionsItemsCounters(array(
                        'cat_id' => $catData['id'],
                        'id' => Geo::coveringRegion()), 60);
                    foreach ($data['regs'] as & $v) {
                        $linkParam['country'] = $v['keyword'];
                        $v['link'] = static::url('items.search', $linkParam).($addReg ? $addReg.$v['id'] : '');
                    } unset($v);
                    break;
                case Geo::COVERING_COUNTRY:
                    $data['regs'] = $this->model->regionsItemsCounters(array(
                        'cat_id' => $catData['id'],
                        'country' => Geo::coveringRegion(),
                        'numlevel' => Geo::lvlRegion,
                    ), 60);
                    foreach ($data['regs'] as &$v) {
                        $linkParam['region'] = $v['keyword'];
                        $v['link'] = static::url('items.search', $linkParam).($addReg ? $addReg.$v['id'] : '');
                    } unset($v);
                    break;
                case Geo::COVERING_REGION:
                    $data['regs'] = $this->model->regionsItemsCounters(array(
                        'cat_id' => $catData['id'],
                        'pid' => Geo::coveringRegion(),
                        'numlevel' => Geo::lvlCity,
                    ), 60);
                    foreach ($data['regs'] as & $v) {
                        $linkParam['city'] = $v['keyword'];
                        $v['link'] = static::url('items.search', $linkParam).($addReg ? $addReg.$v['id'] : '');
                    } unset($v);
                    break;
                case Geo::COVERING_CITIES:
                    $data['regs'] = $this->model->regionsItemsCounters(array(
                        'cat_id' => $catData['id'],
                        'id' => Geo::coveringRegion()), 60);
                    foreach ($data['regs'] as & $v) {
                        $linkParam['city'] = $v['keyword'];
                        $v['link'] = static::url('items.search', $linkParam).($addReg ? $addReg.$v['id'] : '');
                    } unset($v);
                    break;
            }
        }

        return $this->viewPHP($data, 'search.relink.block');
    }

    /**
     * Блок категорий со счетчиками под фильтром
     * @param array $catID ID категории, подкатегории которой будут выводиться в блоке
     * @return string HTML
     */
    public function searchCategoriesBlock($catID = 0)
    {
        if (!config::sysAdmin('bbs.search.categories.block', true, TYPE_BOOL)) {
            return '';
        }

        $data = array();
        $geo = Geo::filter();
        $country = 0;
        if ($geo['id']) {
            $country = $geo['country'] ? $geo['country'] : $geo['id'];
        }

        $cats = $this->model->catsItemsCounters(array(
            'pid'       => ($catID > 0 ? $catID : static::CATS_ROOTID),
            'region_id' => $geo['id'],
        ), $country);
        $linkParam = array();
        if ($geo['id']) {
            switch ($geo['numlevel']) {
                case Geo::lvlCountry:
                    $linkParam['country'] = $geo['keyword'];
                    break;
                case Geo::lvlRegion:
                    $linkParam['region'] = $geo['keyword'];
                    break;
                case Geo::lvlCity:
                    $linkParam['city'] = $geo['keyword'];
                    break;
            }
        }
        foreach ($cats as &$v) {
            $linkParam['keyword'] = $v['keyword'];
            $linkParam['landing_url'] = $v['landing_url'];
            $v['link'] = static::url('items.search', $linkParam);
        } unset($v);

        $data['cats'] = $cats;
        return $this->viewPHP($data, 'search.cats.block');
    }

    /**
     * Блок премиум объявлений на странице поиска
     * @param array $opts параметры
     * @return string HTML
     */
    public function searchPremiumBlock(array $opts = array())
    {
        func::array_defaults($opts, array(
            'id' => 0, # ID категории
            'region' => 0,
            'limit' => config::sysAdmin('bbs.search.premium.limit', 0, TYPE_UINT),
        ));

        if ( ! $opts['limit']) return '';

        if (!Svc::model()->svcEnabled(static::SERVICE_PREMIUM)) return '';

        $filter = array(
            'is_publicated' => 1,
            'status' => self::STATUS_PUBLICATED,
        );
        if ($opts['id']) {
            if ( ! empty($opts['search']) /* блок выводится в основном поиске */) {
                if (config::sysAdmin('bbs.search.premium.category', true, TYPE_BOOL)) {
                    $filter[':cat-filter'] = $opts['id'];
                }
            } else {
                $filter[':cat-filter'] = $opts['id'];
            }
        }
        if ($opts['region']) {
            $filter[':region-filter'] = $opts['region'];
        } else if (config::sysAdmin('bbs.search.premium.region', false, TYPE_BOOL)) {
            $region = Geo::filter('id');
            if ($region) {
                $filter[':region-filter'] = $region;
            }
        }
        $filter['svc'] = array('>',0);
        $filter['svc_premium'] = 1;

        $orderBy = 'svc_premium_order DESC';
        if (config::sysAdmin('bbs.search.premium.rand', false, TYPE_BOOL)) {
            $orderBy = 'RAND()';
        }

        $data = array();
        $data['items'] = $this->model->itemsList($filter, false, array(
            'context' => 'search-premium',
            'orderBy' => $orderBy,
            'limit'   => $opts['limit'],
        ));
        if (empty($data['items'])) return '';

        return $this->viewPHP($data, 'search.premium.block');
    }

    /**
     * Просмотр ОБ
     * @param getpost ::uint 'id' - ID объявления
     * @param get ::string 'from' - откуда выполняется переход на страницу просмотра (add,edit,adm)
     */
    public function view()
    {
        $nItemID = $this->input->getpost('id', TYPE_UINT);
        $nUserID = User::id();

        if (Request::isPOST()) {
            $aResponse = array();
            switch ($this->input->getpost('act', TYPE_STR)) {
                case 'contact-form': # Отправка сообщения из формы "Свяжитесь с автором"
                {
                    Users::i()->writeFormSubmit($nUserID, 0, $nItemID, true, -1);
                }
                break;
                case 'claim': # Пожаловаться
                {
                    if (!$nItemID || !$this->security->validateToken(true, false)) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $nReason = $this->input->post('reason', TYPE_ARRAY_UINT);
                    $nReason = array_sum($nReason);
                    $sMessage = $this->input->post('comment', TYPE_TEXT, array('len'=>1000));

                    if (!$nReason) {
                        $this->errors->set(_t('item-claim', 'Укажите причину'));
                        break;
                    } else if ($nReason & self::CLAIM_OTHER) {
                        if (mb_strlen($sMessage) < 10) {
                            $this->errors->set(_t('item-claim', 'Опишите причину подробнее'), 'comment');
                            break;
                        }
                    }

                    if (!$nUserID) {
                        if (Site::captchaCustom('bbs-item-view')) {
                            bff::hook('captcha.custom.check');
                            if ( ! $this->errors->no()) break;
                        } else {
                            $aResponse['captcha'] = false;
                            if (!CCaptchaProtection::isCorrect($this->input->post('captcha', TYPE_STR))) {
                                $aResponse['captcha'] = true;
                                $this->errors->set(_t('', 'Результат с картинки указан некорректно'), 'captcha');
                                break;
                            }
                        }
                    } else {
                        # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                        if (Site::i()->preventSpam('bbs-claim')) {
                            break;
                        }
                    }

                    $nClaimID = $this->model->claimSave(0, array(
                            'reason'  => $nReason,
                            'message' => $sMessage,
                            'item_id' => $nItemID,
                        )
                    );

                    if ($nClaimID > 0) {
                        $this->claimsCounterUpdate(1);
                        $this->model->itemSave($nItemID, array(
                                'claims_cnt = claims_cnt + 1'
                            )
                        );
                        if (!$nUserID) {
                            Request::deleteCOOKIE('c2');
                        }
                    }
                }
                break;
                case 'sendfriend': # Поделиться с другом
                {
                    $aResponse['later'] = false;
                    if (!$nItemID || !$this->security->validateToken(true, false)) {
                        $this->errors->reloadPage();
                        break;
                    }
                    $sEmail = $this->input->post('email', TYPE_NOTAGS, array('len' => 150));
                    if (!$this->input->isEmail($sEmail, false)) {
                        $this->errors->set(_t('', 'E-mail адрес указан некорректно'), 'email');
                        break;
                    }

                    $aData = $this->model->itemData($nItemID, array('id', 'title', 'link'));
                    if (empty($aData)) {
                        $this->errors->reloadPage();
                        break;
                    }

                    # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                    if (Site::i()->preventSpam('bbs-sendfriend')) {
                        $aResponse['later'] = true;
                        break;
                    }

                    bff::sendMailTemplate(array(
                            'item_id'    => $nItemID,
                            'item_title' => $aData['title'],
                            'item_link'  => $aData['link'],
                        ), 'bbs_item_sendfriend', $sEmail
                    );
                }
                break;
                case 'views-stat': # График статистики просмотров ОБ
                {
                    if (!$nItemID || !$this->security->validateReferer()) {
                        $this->errors->reloadPage();
                        break;
                    }

                    # получаем данные
                    $aStat = $this->model->itemViewsData($nItemID);

                    if (($aResponse['empty'] = empty($aStat['data']))) {
                        $this->errors->set(_t('bbs', 'Статистика просмотров для данного объявления отсутствует'));
                        break;
                    }

                    $aStat['promote_url'] = static::url('item.promote', array('id' => $nItemID, 'from' => 'view'));
                    $aStat['owner'] = $this->isItemOwner($nItemID);
                    $aResponse['popup'] = $this->viewPHP($aStat, 'item.view.statistic');
                    $aResponse['stat'] = & $aStat;
                    $aResponse['lang'] = array(
                        'y_title'        => _t('view', 'Количество просмотров'),
                        'total'          => _t('view', 'Всего'),
                        'item_views'     => _t('view', 'Просмотры объявления'),
                        'contacts_views' => _t('view', 'Просмотры контактов'),
                        'months'         => explode(',', _t('view', 'Января,Февраля,Марта,Апреля,Мая,Июня,Июля,Августа,Сентября,Октября,Ноября,Декабря')),
                        'shortMonths'    => explode(',', _t('view', 'янв,фев,мар,апр,май,июн,июл,авг,сен,окт,ноя,дек')),
                        //'weekdays' => explode(',', _t('view', 'Понедельник,Вторник,Среда,Четверг,Пятница,Суббота,Воскресенье')),
                    );
                }
                break;
                default:
                {
                    $this->errors->reloadPage();
                }
                break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->model->itemDataView($nItemID);
        if (empty($aData)) $this->errors->error404();

        # Last Modified
        if (!BFF_DEBUG) {
            Request::lastModified($aData['modified']);
        }

        # SEO: корректируем ссылку
        $this->urlCorrection(static::urlDynamic($aData['link']));

        $nCatID = $aData['cat_id'];
        $aUrlOptions = array('region' => $aData['region'], 'city' => $aData['city']);
        $aData['list_url'] = static::url('items.search', $aUrlOptions);
        $aData['cats'] = $this->categoryCrumbs($nCatID, __FUNCTION__, $aUrlOptions);

        # владелец
        $aData['owner'] = $this->isItemOwner($nItemID, $aData['user_id']);

        # модерация объявления
        $aData['moderation'] = $moderation = static::moderationUrlKey($nItemID, $this->input->get('mod', TYPE_STR));

        # проверяем статус ОБ
        if ($aData['status'] != self::STATUS_PUBLICATED) {
            if ($aData['status'] == self::STATUS_DELETED) {
                if (!empty($aData['cats'])) {
                    # возвращаем на список объявлений категории (в которой находилось данное объявление до удаления)
                    foreach (array_reverse($aData['cats']) as $v) {
                        if ($v['id']) $this->redirect($v['link']);
                    }
                    $this->redirect($aData['list_url']);
                }

                return $this->showForbidden(_t('view', 'Просмотр объявления'), _t('view', 'Объявление было удалено либо заблокировано модератором'));
            }
            if (!$moderation) {
                if ($aData['status'] == self::STATUS_BLOCKED && !$aData['owner']) {
                    return $this->showForbidden(
                        _t('view', 'Объявление заблокировано'),
                        _t('view', 'Причина блокировки:<br />[reason]', array('reason' => nl2br($aData['blocked_reason'])))
                    );
                }
                if ($aData['status'] == self::STATUS_NOTACTIVATED) {
                    return $this->showSuccess(_t('view', 'Просмотр объявления'), _t('view', 'Объявление еще неактивировано пользователем'));
                }
                if ($aData['moderated'] && $aData['status'] != self::STATUS_PUBLICATED_OUT && $aData['status'] != self::STATUS_BLOCKED && !$aData['owner']) {
                    return $this->showForbidden(
                        _t('view', 'Данное объявление находится на модерации'),
                        _t('view', 'После проверки оно будет вновь опубликовано')
                    );
                }
            }
            # self::STATUS_PUBLICATED_OUT => отображаем снятые с публикации
        } else if (!$aData['moderated'] && static::premoderation() && !$moderation && !$aData['owner']) {
            return $this->showForbidden(
                _t('view', 'Данное объявление находится на модерации'),
                _t('view', 'После проверки оно будет опубликовано')
            );
        }

        # информация о владельце
        $aData['user'] = Users::model()->userDataSidebar($aData['user_id']);

        # информация о магазине
        if ($aData['shop_id'] && $aData['user']['shop_id'] > 0 && bff::shopsEnabled()) {
            $aData['shop'] = Shops::model()->shopDataSidebar($aData['user']['shop_id']);
            if ($aData['shop']) {
                $aData['name'] = $aData['shop']['title'];
            }
        }
        $aData['is_shop'] = ($aData['shop_id'] && !empty($aData['shop']));

        # подставляем контактные данные из профиля
        if ($this->getItemContactsFromProfile())
        {
            if ($aData['is_shop']) {
                $contactsData = &$aData['shop'];
            } else {
                $contactsData = &$aData['user'];
                $aData['name'] = $contactsData['name'];
            }
            $contacts = array(
                'contacts' => $contactsData['contacts'],
                'phones'   => array(),
            );
            if (!empty($contactsData['phones'])) {
                foreach ($contactsData['phones'] as $v) {
                    $contacts['phones'][] = $v['m'];
                }
            }
            $aData['contacts'] = &$contacts; unset($contactsData);
            $aData['contacts']['has'] = !empty($contacts['contacts']) || !empty($contacts['phones']);
        }

        # изображения
        $oImages = $this->itemImages($nItemID);
        $aData['images'] = $oImages->getData($aData['imgcnt']);
        if (!empty($aData['images'])) {
            $aData['image_view'] = $oImages->getURL(reset($aData['images']), BBSItemImages::szView);
            $i = 1;
            foreach ($aData['images'] as &$v) {
                $v['t'] = _t('view', '[title] [city] - изображение [num]', array(
                    'title' => $aData['title'], 'city' => $aData['city_title'], 'num' => $i++,
                ));
                $v['url_small'] = $oImages->getURL($v, BBSItemImages::szSmall);
                $v['url_view'] = $oImages->getURL($v, BBSItemImages::szView);
                $v['url_zoom'] = $oImages->getURL($v, BBSItemImages::szZoom);
            }
            unset($v);
        } else {
            $aData['image_view'] = $oImages->urlDefault(BBSItemImages::szView);
        }

        # дин. свойства
        $aData['dynprops'] = $this->dpView($nCatID, $aData);

        # версия для печати
        if ($this->input->get('print', TYPE_BOOL) && $aData['status'] == self::STATUS_PUBLICATED) {
            View::setLayout('print');
            $this->seo()->robotsIndex(false);

            return $this->viewPHP($aData, 'item.view.print');
        }

        # комментарии
        $aData['comments'] = '';
        if (static::commentsEnabled()) {
            $aData['comments'] = $this->comments(array(
                'itemID' => $nItemID,
                'itemUserID' => $aData['user_id'],
                'itemStatus' => $aData['status'],
            ));
        }

        # похожие
        if ($aData['status'] == self::STATUS_PUBLICATED && !empty($aData['is_publicated']) && ! $moderation)
        {
            $region = Geo::filter();
            $similarCats = array_keys($aData['cats']); $i = sizeof($similarCats);
            while ($i--) {
                $similarFilter = array(
                    'is_publicated' => 1,
                    'status' => self::STATUS_PUBLICATED,
                );
                $similarFilter[':cat-filter'] = $similarCats; array_pop($similarCats);
                if ($region['id']) {
                    $similarFilter[':region-filter'] = $region['id'];
                }
                $similarFilter['cat_type'] = $aData['cat_type'];
                $similarFilter['id'] = array('!=', $nItemID);
                $aData['similar'] = $this->model->itemsList($similarFilter, false, array(
                    'context' => 'view-similar',
                    'orderBy' => 'publicated_order DESC',
                    'limit' => config::sysAdmin('bbs.view.similar.limit', 3, TYPE_UINT),
                ));
                if (!empty($aData['similar'])) {
                    $this->itemsListPrepare($aData['similar'], self::LIST_TYPE_LIST);
                    break;
                }
            } unset($i);
            $aData['similar'] = $this->viewPHP($aData, 'item.view.similar');
        } else {
            $aData['similar'] = '';
        }

        # избранное ОБ
        $aData['fav'] = $this->isFavorite($nItemID, $nUserID);

        # откуда пришли
        $aData['from'] = $this->input->get('from', TYPE_STR);

        # накручиваем счетчик просмотров если:
        # - не владелец
        # - не переход из админ панели
        # - не перешли с этой же страницы
        if (!$aData['owner'] && $aData['from'] != 'adm') {
            $sReferer = Request::referer();
            if (empty($sReferer) || mb_strpos($sReferer, '-' . $nItemID . '.html') === false) {
                if ($this->model->itemViewsIncrement($nItemID, 'item', $aData['views_today'])) {
                    $aData['views_total']++;
                    $aData['views_today']++;
                }
            }
        }

        if ($this->input->get('up_free', TYPE_UINT) == 1 && $aData['owner']) {
            $msg = $this->svcUpFree($nItemID);
            if ($this->errors->no()) {
                $aData['msg_success'] = $msg;
            } else {
                $aData['msg_error'] = join('<br />', $this->errors->get());
            }
        }

        # SEO: Просмотр объявления
        $this->seo()->canonicalUrl($aData['link']);
        $metaCategories = array(); $aData['breadcrumb'] = array();
        foreach ($aData['cats'] as $k => &$v) {
            if ($k) $metaCategories[] = $v['title'];
            $aData['breadcrumb'][] = &$v['breadcrumb'];
        } unset($v);
        $this->setMeta('view', array(
                'id'                 => $nItemID,
                'name'               => $aData['name'],
                'title'              => $aData['title'],
                'description'        => tpl::truncate($aData['descr'], 150),
                'price'              => ($aData['price_on'] ? $aData['price'] . (!empty($aData['price_mod']) ? ' ' . $aData['price_mod'] : '') : ''),
                'city'               => $aData['city'],
                'region'             => $aData['region'],
                'country'            => $aData['country'],
                'category'           => $aData['cat_title'],
                'categories'         => join(', ', $metaCategories),
                'categories.reverse' => join(', ', array_reverse($metaCategories, true)),
                'category+parent'    => join(', ', array_reverse(array_splice($metaCategories,(sizeof($metaCategories) > 2 ? sizeof($metaCategories) - 2 : 0)))),
            ), $aData, array(
                'breadcrumb'=>array('replace'=>array('region'=>'city','region.in'=>'city.in'), 'ignore'=>array('category')),
            )
        );
        $seoSocialImages = array();
        foreach ($aData['images'] as &$v) {
            $seoSocialImages[] = $v['url_zoom']; break;
        }
        unset($v);
        $this->seo()->setSocialMetaOG($aData['share_title'], $aData['share_description'], $seoSocialImages, $aData['link'], $aData['share_sitename']);

        # promote
        $aData['promote_url'] = static::url('item.promote', array('id' => $nItemID, 'from' => 'view'));

        # код "Поделиться"
        $aData['share_code'] = config::get('bbs_item_share_code');

        # фразы
        $aData['lang'] = array(
            'fav_in' => _te('bbs', 'Добавить в избранное'),
            'fav_out' => _te('bbs', 'Удалить из избранного'),
        );

        # сокращения:
        $aData['is_publicated'] = ($aData['status'] == static::STATUS_PUBLICATED);
        $aData['is_publicated_out'] = ($aData['status'] == static::STATUS_PUBLICATED_OUT);
        $aData['is_blocked'] = ($aData['status'] == static::STATUS_BLOCKED && ($aData['owner'] || $moderation));
        $aData['is_business'] = ($aData['owner_type'] == static::OWNER_BUSINESS);
        $aData['is_map'] = ( ! empty($aData['addr_addr']) && $aData['addr_lat'] !=0 && $aData['cat_addr'] );
        if ($aData['is_map']) {
            Geo::mapsAPI(false);
        }
        $aData['city_title_delivery'] = $aData['city_title'];
        if (!empty($aData['regions_delivery']) && !empty($aData['city_title'])) {
            $aData['city_title_delivery'] = _t('bbs', 'доставка из г.[city]', array('city'=>$aData['city_title']));
        }
        $aData['is_soon_left'] = (strtotime($aData['publicated_to']) - BFF_NOW) < static::PUBLICATION_SOON_LEFT;

        # Баннеры
        Banners::i()->viewQuery($aData['title']);

        return $this->viewPHP($aData, 'item.view');
    }

    /**
     * Добавление ОБ
     * @param get ::uint 'cat' - ID категории по-умолчанию
     */
    public function add()
    {
        if (!empty($_GET['success']))
        {
            return $this->itemStatus('new', $this->input->get('id', TYPE_UINT));
        }

        $this->security->setTokenPrefix('bbs-item-form');
        $nItemID = 0;
        $nShopID = User::shopID();
        $nUserID = User::id();
        $registerPhone = Users::registerPhone();
        $publisherOnlyShop = static::publisher(static::PUBLISHER_SHOP) ||
            (static::publisher(static::PUBLISHER_USER_TO_SHOP) && $nShopID);

        $this->validateItemData($aData, 0);

        if (Request::isPOST()) {
            $aResponse = array('id' => 0);
            $bNeedActivation = false;
            $users = Users::i();

            do {

                if ( ! $this->errors->no('bbs.item.add.step1',array('id'=>0,'data'=>&$aData))) {
                    break;
                }

                # проверка токена(для авторизованных) + реферера
                if (!$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                # антиспам фильтр: минус слова
                if ($this->spamMinusWordsFound($aData['title'], $sWord)) {
                    $this->errors->set(_t('bbs', 'В указанном вами заголовке присутствует запрещенное слово "[word]"', array('word' => $sWord)));
                    break;
                }
                if ($this->spamMinusWordsFound($aData['descr'], $sWord)) {
                    $this->errors->set(_t('bbs', 'В указанном вами описании присутствует запрещенное слово "[word]"', array('word' => $sWord)));
                    break;
                }

                if (!$nUserID) {
                    # проверяем IP для неавторизованных
                    $mBanned = $users->checkBan(true);
                    if ($mBanned) {
                        $this->errors->set(_t('users', 'В доступе отказано по причине: [reason]', array('reason' => $mBanned)));
                        break;
                    }
                    # проверка доступности публикации от "магазина"
                    // $aData['shop_id'] = 0;
                    if ($publisherOnlyShop) {
                        $this->errors->reloadPage();
                        break;
                    }
                    # номер телефона
                    if ($registerPhone) {
                        $phone = $this->input->post('phone', TYPE_NOTAGS, array('len' => 30));
                        if (!$this->input->isPhoneNumber($phone)) {
                            $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                            break;
                        }
                    }
                    # регистрируем нового или задействуем существующего пользователя
                    $sEmail = $this->input->post('email', TYPE_NOTAGS, array('len' => 100)); # E-mail
                    if (!$this->input->isEmail($sEmail)) {
                        $this->errors->set(_t('users', 'E-mail адрес указан некорректно'));
                        break;
                    }
                    # антиспам фильтр: временные ящики
                    if (Users::isEmailTemporary($sEmail)){
                        $this->errors->set(_t('', 'Указанный вами email адрес находится в списке запрещенных, используйте например @gmail.com'));
                        break;
                    }
                    $aUserData = $users->model->userDataByFilter(($registerPhone ?
                        array('phone_number'=>$phone) :
                        array('email' => $sEmail)),
                        array('user_id', 'email', 'shop_id', 'activated', 'activate_key',
                              'phone_number', 'phone_number_verified', 'blocked', 'blocked_reason')
                    );
                    if (empty($aUserData)) {
                        # проверяем уникальность email адреса
                        if ($registerPhone && $users->model->userEmailExists($sEmail, $aUserData['user_id'])) {
                            $this->errors->set(_t('users', 'Пользователь с таким e-mail адресом уже зарегистрирован. <a [link_forgot]>Забыли пароль?</a>',
                                    array('link_forgot' => 'href="' . Users::url('forgot') . '"')
                                ), 'email'
                            );
                            break;
                        }
                        # регистрируем нового пользователя
                        # подставляем данные в профиль из объявления
                        $aRegisterData = array('email'=>$sEmail,'phone'=>'');
                        if ($registerPhone) $aRegisterData['phone_number'] = $phone;
                        foreach (array('name','contacts','city_id'=>'region_id') as $k=>$v) {
                            if (is_int($k)) $k = $v;
                            if ( ! empty($aData[$k])) $aRegisterData[$v] = $aData[$k];
                        }
                        # сохраняем первый телефон в отдельное поле
                        if (!empty($aData['phones'])) {
                            $aPhoneFirst = reset($aData['phones']);
                            $aRegisterData['phone'] = $aPhoneFirst['v'];
                        }
                        $aRegisterData['phones'] = serialize($aData['phones']);
                        $aUserData = $users->userRegister($aRegisterData);
                        if (empty($aUserData['user_id'])) {
                            $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                            break;
                        }
                    } else {
                        # пользователь существует и его аккаунт заблокирован
                        if ($aUserData['blocked']) {
                            $this->errors->set(_t('users', 'В доступе отказано по причине: [reason]', array('reason' => $aUserData['blocked_reason'])));
                            break;
                        }
                        if (empty($aUserData['activate_key'])) {
                            $aActivation = $users->updateActivationKey($aUserData['user_id']);
                            $aUserData['activate_key'] = $aActivation['key'];
                        }
                    }
                    $nUserID = $aUserData['user_id'];
                    $bNeedActivation = true;
                    $aUserData['email'] = $sEmail;
                } else {
                    # проверка доступности публикации объявления
                    $aData['shop_id'] = $this->publisherCheck($nShopID, 'shop');
                    if ($aData['shop_id'] && !Shops::model()->shopActive($nShopID)) {
                        $this->errors->set(_t('item-form', 'Размещение объявления доступно только от активированного магазина'));
                    }
                    # если пользователь авторизован и при этом не вводил номер телефона ранее
                    if ($registerPhone)
                    {
                        $aUserData = User::data(array('phone_number', 'phone_number_verified'), true);
                        if (empty($aUserData['phone_number']) || !$aUserData['phone_number_verified']) {
                            $phone = $this->input->post('phone', TYPE_NOTAGS, array('len'=>30));
                            if (!$this->input->isPhoneNumber($phone)) {
                                $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                                break;
                            }
                            if ($users->model->userPhoneExists($phone, $nUserID)) {
                                $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован.'), 'phone');
                                break;
                            }
                            $aActivation = $users->getActivationInfo();
                            $users->model->userSave($nUserID, array(
                                'activate_key'    => $aActivation['key'],
                                'activate_expire' => $aActivation['expire'],
                                'phone_number'    => $phone,
                            ));
                            $aUserData['activate_key'] = $aActivation['key'];
                            $bNeedActivation = true;
                        }
                    }
                }

                # проверим лимитирование объявлений, если выключена услуга платного расширения лимитов
                $nCheckShopID = (! empty($aData['shop_id']) ? $aData['shop_id'] : 0);
                if ($this->itemsLimitExceeded($nUserID, $nCheckShopID, $aData['cat_id1'], $limit)) {
                    $limit = tpl::declension($limit, _t('bbs', 'объявление;объявления;объявлений'));
                    if (config::get('bbs_items_limits_' . ($nCheckShopID ? 'shop' : 'user')) == static::LIMITS_CATEGORY) {
                        $this->errors->set(_t('bbs', 'Возможность публикации объявлений в данную категорию на сегодня исчерпана ([limit] в сутки).', array('limit' => $limit)));
                    } else {
                        $this->errors->set(_t('bbs', 'Возможность публикации объявлений на сегодня исчерпана ([limit] в сутки).', array('limit' => $limit)));
                    }
                    break;
                }

                # антиспам фильтр: проверка дубликатов
                if ($this->spamDuplicatesFound($nUserID, $aData)) {
                    $this->errors->set(_t('bbs', 'Вы уже публиковали аналогичное объявление. Воспользуйтесь функцией поднятия объявления'));
                    break;
                }
                # антиспам фильтр: проверка дубликатов изображений
                $checkImagesDuplicates = config::get('bbs_items_spam_duplicates_images', false, TYPE_BOOL);

                if (!$this->errors->no('bbs.item.add.step2',array('id'=>0,'user'=>$nUserID,'data'=>&$aData))) break;

                if ($bNeedActivation) {
                    $aData['status'] = self::STATUS_NOTACTIVATED;
                    $aActivation = $this->getActivationInfo();
                    $aData['activate_key'] = $aActivation['key'];
                    $aData['activate_expire'] = $aActivation['expire'];
                } else {
                    $aData['status'] = self::STATUS_PUBLICATED;
                    $aData['publicated'] = $this->db->now();
                    $aData['publicated_order'] = $this->db->now();
                    $aData['publicated_to'] = $this->getItemPublicationPeriod(isset($aData['publicated_period']) ? $aData['publicated_period'] : 0);
                    $aData['moderated'] = 0; # помечаем на модерацию

                    if ($aData['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled()) {
                        # проверим превышение лимита лимитирования по абонементу
                        if (Shops::i()->abonementLimitExceed($aData['shop_id'])) {
                            $aData['status'] = self::STATUS_PUBLICATED_OUT;
                        }
                    } else if (static::limitsPayedEnabled()) {
                        # проверим превышение лимита
                        $limit = $this->model->limitsPayedCategoriesForUser(array(
                            'user_id' => $nUserID,
                            'shop_id' => $aData['shop_id'],
                            'cat_id'  => $aData['cat_id'],
                        ));
                        if ( ! empty($limit)) {
                            $limit = reset($limit);
                            if ($limit['cnt'] >= $limit['limit']) {
                                # лимит превышен, не публикуем
                                $aData['status'] = self::STATUS_PUBLICATED_OUT;
                            }
                        }
                    }
                    if ($aData['status'] === self::STATUS_PUBLICATED_OUT) {
                        $aData['publicated_to'] = $this->db->now();
                    }
                }

                # создаем объявление
                $aData['user_id'] = $nUserID;

                $nItemID = $this->model->itemSave(0, $aData, 'd');
                if (!$nItemID) {
                    $this->errors->set(_t('item-form', 'Ошибка публикации объявления, обратитесь в службу поддержки.'));
                    break;
                }

                if (!$this->errors->no('bbs.item.add.step3',array('user'=>$nUserID,'data'=>&$aData,'id'=>$nItemID))) break;

                $aResponse['id'] = $nItemID;

                # сохраняем / загружаем изображения
                $oImages = $this->itemImages($nItemID);
                $oImages->setAssignErrors(false);
                $oImages->setUserID($nUserID);
                if ($this->input->post('images_type', TYPE_STR) == 'simple') {
                    # загружаем
                    if (!empty($_FILES)) {
                        for ($i = 1; $i <= $oImages->getLimit(); $i++) {
                            $oImages->uploadFILES('images_simple_' . $i);
                        }
                        # удаляем загруженные через "удобный способ"
                        $aImages = $this->input->post('images', TYPE_ARRAY_STR);
                        $oImages->deleteImages($aImages);
                    }
                    if ( ! empty($checkImagesDuplicates)) {
                        $spamDuplicates = $oImages->filesHashExists($oImages->filesHash(false));
                    }
                } else {
                    if ( ! empty($checkImagesDuplicates)) {
                        $spamDuplicates = $oImages->filesHashExists($oImages->filesHash(true, 'images'));
                        # перемещаем из tmp-директории в постоянную
                        if ( ! $spamDuplicates) {
                            $oImages->saveTmp('images');
                        }
                    } else {
                        $oImages->saveTmp('images');
                    }
                }

                # антиспам фильтр: проверка дубликатов изображений
                if ( ! empty($spamDuplicates)) {
                    $this->errors->set(_t('bbs', 'Вы уже публиковали аналогичное объявление. Воспользуйтесь функцией поднятия объявления'));
                    $this->model->itemsDelete(array($nItemID), false);
                    break;
                }

                # если у пользователя в профиле не заполнено поле "город" берём его из объявления
                if (User::id() && $aData['city_id'] && !User::data('region_id', true)) {
                    $users->model->userSave(User::id(), array('region_id'=>$aData['city_id']));
                }
                # обновляем счетчик объявлений "на модерации"
                if (isset($aData['moderated']) && empty($aData['moderated'])) {
                    $this->moderationCounterUpdate(1);
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                Site::i()->preventSpam('bbs-add', 20);

                # требуется активация:
                if ($bNeedActivation) {
                    if ($registerPhone) {
                        # отправляем sms c кодом активации
                        $users->sms(false)->sendActivationCode($phone, $aUserData['activate_key']);
                    } else {
                        # отправляем письмо cо ссылкой на активацию объявления
                        $aMailData = array(
                            'name'          => $aData['name'],
                            'email'         => $aUserData['email'],
                            'user_id'       => $nUserID,
                            'activate_link' => $aActivation['link'] . '_' . $nItemID
                        );
                        bff::sendMailTemplate($aMailData, 'bbs_item_activate', $aUserData['email']);
                    }
                }

                $params = array(
                    'id'      => $nItemID,
                    'ak'      => (!empty($aActivation['key']) ? substr($aActivation['key'], 1, 8) : ''),
                    'success' => 1,
                    'svc'     => $this->input->post('svc', TYPE_UINT)
                );
                $this->formSvcParams($params['svc'], $params);
                $aResponse['successPage'] = static::url('item.add', $params);
            } while (false);

            $this->iframeResponseForm($aResponse);
        }

        $aData['status'] = self::STATUS_NOTACTIVATED;
        $aData['title_meta'] = _t('item-form', 'Разместить объявление');

        # подставляем данные пользователя:
        if ($nUserID) {
            $aUserData = Users::model()->userData($nUserID, array(
                    'name',
                    'email',
                    'phone_number',
                    'phone_number_verified',
                    'phones',
                    'contacts',
                    'region_id as city_id',
                    'shop_id',
                )
            );
            $aData = array_merge($aData, $aUserData);
        } else {
            $aData['phone_number'] = '';
            $aData['phone_number_verified'] = 0;
        }

        # проверка доступности публикации от "магазина"
        $aData['shop_id'] = 0;
        if ($publisherOnlyShop) {
            if (!$nUserID) {
                return $this->showInlineMessage(_t('item-form', 'Публикация объявления доступна только для авторизованных пользователей'),
                    array('auth' => true)
                );
            }
            if (!$nShopID) {
                return $this->showInlineMessage(_t('item-form', 'Для публикация объявления вам необходимо <a [link]>открыть магазин</a>.',
                        array('link' => 'href="' . Shops::url('my.open') . '"')
                    )
                );
            } else if (!Shops::model()->shopActive($nShopID)) {
                return $this->showInlineMessage(_t('item-form', 'Размещение объявления доступно только при <a [link]>активированном магазине</a>.',
                        array('link' => 'href="' . Shops::url('my.shop') . '"')
                    )
                );
            }
        }

        # SEO: Добавление объявления
        $this->urlCorrection(static::url('item.add'));
        $this->seo()->canonicalUrl(static::url('item.add', array(), true));
        $this->setMeta('add', array(), $aData);
        if (!empty($aData['breadcrumb'])) {
            $aData['title_meta'] = $aData['breadcrumb'];
        }

        return $this->form($nItemID, $aData);
    }

    /**
     * Редактирование ОБ
     * @param getpost ::uint 'id' - ID объявления
     */
    public function edit()
    {
        $this->security->setTokenPrefix('bbs-item-form');
        $nUserID = User::id();
        $nShopID = User::shopID();
        $nItemID = $this->input->getpost('id', TYPE_UINT);
        if (!empty($_GET['success']) && Request::isGET()) {
            # Результат редактирования
            return $this->itemStatus('edit', $nItemID);
        }

        if (Request::isPOST()) {
            $aResponse = array();
            do {
                if (!$nItemID ||
                    !$nUserID ||
                    !$this->security->validateToken()
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                $aItemData = $this->model->itemData($nItemID, array(
                        'user_id',
                        'city_id',
                        'cat_id',
                        'status',
                        'publicated_order',
                        'video',
                        'imgcnt',
                        'title',
                        'descr',
                        'price',
                        'moderated',
                        'shop_id'
                    )
                );
                if (empty($aItemData)) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$this->isItemOwner($nItemID, $aItemData['user_id'])) {
                    $this->errors->set(_t('item-form', 'Вы не является владельцем данного объявления.'));
                    break;
                }

                # проверка статуса объявления
                $aData['shop_id'] = $this->publisherCheck($nShopID, 'shop');
                if ($aData['shop_id'] && !Shops::model()->shopActive($nShopID)) {
                    $this->errors->set(_t('item-form', 'Ваш магазин был <a [link]>деактивирован или заблокирован</a>.<br/>Невозможно разместить объявление от магазина.', array(
                                'link' => 'href="' . Shops::url('my.shop') . '" target="_blank"'
                            )
                        )
                    );
                    break;
                }

                # проверяем данные
                $this->validateItemData($aData, $nItemID, $aItemData);



                if (!$this->errors->no('bbs.item.edit.step1',array('id'=>$nItemID,'data'=>&$aData,'item'=>$aItemData))) break;

                # антиспам фильтр: минус слова
                if ($this->spamMinusWordsFound($aData['title'], $sWord)) {
                    $this->errors->set(_t('bbs', 'В указанном вами заголовке присутствует запрещенное слово "[word]"', array('word' => $sWord)));
                    break;
                }
                if ($this->spamMinusWordsFound($aData['descr'], $sWord)) {
                    $this->errors->set(_t('bbs', 'В указанном вами описании присутствует запрещенное слово "[word]"', array('word' => $sWord)));
                    break;
                }

                if ($aItemData['status'] == self::STATUS_BLOCKED) {
                    # объявление заблокировано, помечаем на проверку модератору
                    $aData['moderated'] = 0;
                }

                # помечаем на модерацию при изменении: названия, описания, категории
                if ($aData['title'] != $aItemData['title'] || $aData['descr'] != $aItemData['descr'] ||
                    (static::categoryFormEditable() && $aData['cat_id'] != $aItemData['cat_id'])) {
                    if ($aItemData['moderated']) { $aData['moderated'] = (static::premoderationEdit() ? 0 : 2); }
                }

                # изменилось от кого публикуем объявление
                if($aItemData['shop_id'] != $aData['shop_id'] && $aItemData['status'] == static::STATUS_PUBLICATED){
                    # проверим лимиты
                    if ($aData['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled()) {
                        if (Shops::i()->abonementLimitExceed($aData['shop_id'])) {
                            $this->errors->set(_t('bbs', 'Превышение лимита тарифного плана. <a [link]>Изменить тариф</a>.', array('link'=>'href="'.Users::url('my.settings', array('t'=>'abonement')).'"')));
                            break;
                        }
                    } else if (static::limitsPayedEnabled()) {
                        $limit = $this->model->limitsPayedCategoriesForUser(array(
                            'user_id' => $nUserID,
                            'shop_id' => $aData['shop_id'],
                            'cat_id'  => $aItemData['cat_id'],
                        ));
                        if ( ! empty($limit)) {
                            $limit = reset($limit);
                            if ($limit['cnt'] >= $limit['limit']) {
                                $this->errors->set(_t('bbs', 'Превышение лимита публикаций: [title].',
                                    array('title' => $this->limitsPayedCatTitle($limit['point']))));
                                break;
                            }
                        }
                    }
                }

                if (!$this->errors->no('bbs.item.edit.step2',array('id'=>$nItemID,'data'=>&$aData,'item'=>$aItemData))) break;

                # сохраняем
                $bSuccess = $this->model->itemSave($nItemID, $aData, 'd');
                if ($bSuccess) {
                    # сохраняем / загружаем изображения
                    $oImages = $this->itemImages($nItemID);
                    if ($this->input->post('images_type', TYPE_STR) == 'simple') {
                        # загружаем
                        if (!empty($_FILES) && $aItemData['imgcnt'] < $oImages->getLimit()) {
                            for ($i = 1; $i <= $oImages->getLimit(); $i++) {
                                $oImages->uploadFILES('images_simple_' . $i);
                            }
                        }
                    } else {
                        # сохраняем порядок изображений
                        $aImages = $this->input->post('images', TYPE_ARRAY_STR);
                        $oImages->saveOrder($aImages, false);
                    }

                    # помечаем на модерацию при изменении: фотографий
                    if ($oImages->newImagesUploaded($this->input->post('images_hash', TYPE_STR))) {
                        if ($aItemData['moderated']) {
                            $aData['moderated'] = (static::premoderationEdit() ? 0 : 2);
                            $this->model->itemSave($nItemID, array('moderated' => $aData['moderated']), false);
                        }
                    }

                    # счетчик "на модерации"
                    if (isset($aData['moderated'])) {
                        $this->moderationCounterUpdate();
                    }
                }

                # URL страницы "успешно"
                $aResponse['successPage'] = static::url('item.edit', array(
                        'id'      => $nItemID,
                        'success' => 1,
                        'svc'     => $this->input->post('svc', TYPE_UINT)
                    )
                );

                bff::hook('bbs.item.edit.step3',array('id'=>$nItemID,'data'=>&$aData,'item'=>$aItemData,'response'=>&$aResponse));
            } while (false);

            $this->iframeResponseForm($aResponse);
        }

        if (!$nItemID || !$nUserID) $this->errors->error404();

        $aData = $this->model->itemData($nItemID, array(), true);
        if ( ! empty($aData['cat_id_virtual'])) {
            $aData['cat_id'] = $aData['cat_id_virtual'];
        }
        if (empty($aData)) $this->errors->error404();

        $aData['title_meta'] = _t('item-form', 'Редактирование объявления');
        if ($aData['user_id'] != $nUserID) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Вы не являетесь владельцем данного объявления'));
        }
        if ($aData['status'] == self::STATUS_NOTACTIVATED) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Объявление еще неактивировано'));
        }
        if ($aData['status'] == self::STATUS_BLOCKED && !$aData['moderated']) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Объявление ожидает проверки модератора'));
        }

        bff::setMeta($aData['title_meta']);
        $this->seo()->robotsIndex(false);
        $this->seo()->robotsFollow(false);

        return $this->form($nItemID, $aData);
    }

	/**
     * Копирование ОБ
     * @param getpost::uint 'id' - ID копируемого объявления
     */
    public function copy()
    {
		if (Request::isPOST()) {
			$this->add();
		}

        $this->security->setTokenPrefix('bbs-item-form');
        $nUserID = User::id();
        $nItemID = $this->input->getpost('id', TYPE_UINT);

        if (!$nItemID || !$nUserID) $this->errors->error404();

        $aData = $this->model->itemData($nItemID, array(), true);
        if (empty($aData)) $this->errors->error404();

        $aData['title_meta'] = _t('item-form', 'Разместить аналогичное объявление');
        bff::setMeta($aData['title_meta']);
        $this->seo()->robotsIndex(false);
        $this->seo()->robotsFollow(false);

        if ($aData['user_id'] != $nUserID) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Вы не являетесь владельцем данного объявления'));
        }
        if ($aData['status'] == self::STATUS_BLOCKED && !$aData['moderated']) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Объявление ожидает проверки модератора'));
        }

        unset($aData['id']);
        return $this->form(0, $aData);
    }

    /**
     * Формирование шаблона формы добавления / редактирования ОБ
     * @param integer $nItemID ID ОБ
     * @param array $aData @ref данные ОБ
     * @return string HTML
     */
    protected function form($nItemID, array &$aData)
    {
        # id
        $aData['id'] = $nItemID;
        $aData['edit'] = $edit = ($nItemID > 0);

        # текущий пользователь
        $aData['userID'] = User::id();

        # изображения
        $aData['img'] = $this->itemImages($nItemID);
        if ($edit) {
            $aImages = $aData['img']->getData($aData['imgcnt']);
            $aData['images'] = array();
            foreach ($aImages as $v) {
                $aData['images'][] = array(
                    'id'       => $v['id'],
                    'tmp'      => false,
                    'filename' => $v['filename'],
                    'i'        => $aData['img']->getURL($v, BBSItemImages::szSmall, false),
                    'rotate'   => $aData['img']->rotateAvailable($v),
                );
            }
            $aData['imghash'] = $aData['img']->getLastUploaded();
            $aData['imagesUploaded'] = $aData['imgcnt'];
        } else {
            $aData['images'] = array();
            $aData['imagesUploaded'] = 0;
            $aData['imghash'] = '';
        }

        # категория
        $catID = & $aData['cat_id'];
        if (!$edit && !empty($_GET['cat'])) {
            # предварительный выбор, при добавлении (?cat=X)
            $catID = $this->input->get('cat', TYPE_UINT);
        }
        # категория: формируем форму дин. свойств, типы, цену
        $aData['cat_data'] = $this->itemFormByCategory($catID, $aData);
        if (empty($aData['cat_data']) || $aData['cat_data']['subs'] > 0) {
            # ID категории указан некорректно (невалидный или есть подкатегории)
            $catID = 0;
        }

        # изображения: лимит с учетом настроек категории или по-умолчанию
        $aData['imagesLimit'] = ( $edit && $catID > 0 ? $aData['cat_data']['photos'] : ( $catID ? $aData['cat_data']['photos'] : static::itemsImagesLimit(false) ) );
        $aData['titleLimit'] = $this->model->langItem['title']['len'];
        $aData['descrLimit'] = $this->model->langItem['descr']['len'];

        # полный путь текущей выбранной категории + иконка основной категории
        $aData['cat_path'] = array();
        if ($catID > 0) {
            $catPath = $this->model->catParentsData($catID, array('id', 'title', 'icon_s'));
            foreach ($catPath as $v) {
                $aData['cat_path'][] = $v['title'];
            }
            $catParent = reset($catPath);
            $aData['cat_data']['icon'] = static::categoryIcon()->url($catParent['id'], $catParent['icon_s'], BBSCategoryIcon::SMALL);
        }

        # доступные для активации услуги
        $aData['curr'] = Site::currencyDefault();
        $aData['svc_data'] = $this->model->svcData();

        # город
        if (Geo::coveringType(Geo::COVERING_CITY)) {
            $aData['city_id'] = Geo::coveringRegion();
        }
        $aData['city_data'] = Geo::regionData($aData['city_id']);
        $aData['cityTitle'] = ( $aData['city_id'] > 0 && ! empty($aData['city_data']['title']) ? HTML::escape($aData['city_data']['title']) : '' );

        # публикация от "магазина"
        $nShopID = User::shopID();
        $aData['publisher'] = static::publisher();
        if ($aData['shop'] = ($nShopID && $aData['publisher'] != static::PUBLISHER_USER && bff::moduleExists('shops'))) {
            $aData['publisher_only_shop'] = static::publisher(array(
                    static::PUBLISHER_SHOP,
                    static::PUBLISHER_USER_TO_SHOP
                )
            );
            $aData['shop_data'] = Shops::model()->shopData($nShopID,
                array('phones', 'contacts', 'reg3_city as city_id', 'addr_addr', 'addr_lat', 'addr_lon', 'status'),
                false
            );
            foreach (Users::contactsFields($aData['shop_data']['contacts']) as $contact) {
                if (!isset($aData['shop_data'][$contact['key']])) {
                    $aData['shop_data'][$contact['key']] = $contact['value'];
                }
            }
            if (empty($aData['shop_data']) || $aData['shop_data']['status'] != Shops::STATUS_ACTIVE) {
                $aData['shop_data'] = false;
            } else {
                unset($aData['shop_data']['status']);
                $aData['shop_data']['city_data'] = Geo::regionData($aData['shop_data']['city_id']);
                # при публикации только от "магазина" - подставляем контакты магазина
                if ($aData['publisher_only_shop'] && !$edit) {
                    $aData['shop_data']['metro_id'] = 0;
                    foreach ($aData['shop_data'] as $k => $v) { $aData[$k] = $v; }
                }
            }
        }

        # контакты
        $aData['contactsFromProfile'] = ($this->getItemContactsFromProfile() && ($edit || (!$edit && $aData['userID'])));

        # районы города
        if ($aData['districtsEnabled'] = Geo::districtsEnabled()) {
            $aData['districtsVisible'] = ($aData['city_id'] > 0 && count(Geo::districtList($aData['city_id'])) > 0);
        }

        # метро
        $aData['metro_data'] = Geo::cityMetro($aData['city_id'], $aData['metro_id'], false);
        if (empty($aData['metro_data']['sel']['id'])) $aData['metro_id'] = 0;

        # координаты по-умолчанию
        Geo::mapDefaultCoordsCorrect($aData['addr_lat'], $aData['addr_lon']);

        # период публикации
        $aData['publicationPeriod'] = !$edit && static::formPublicationPeriod();
        if ($aData['publicationPeriod']) {
            $aData['publicationPeriodDays'] = 0;
            $aData['publicationPeriodOpts'] = $this->publicationPeriodOptions($aData['publicationPeriodDays']);
        }

        # видимость блоков
        $aData['servicesAvailable'] = (!$edit && bff::servicesEnabled() && (!Users::registerPhone() || User::phoneNumberVerified()));
        $aData['agreementAvailable'] = (!$aData['userID'] && config::sysAdmin('bbs.form.agreement', true, TYPE_BOOL));

        # H1
        $aData['h1'] = ( $edit ? _t('item-form', 'Редактировать объявление') : (!empty($aData['titleh1']) ? $aData['titleh1'] : _t('item-form', 'Разместить объявление')) );

        # хлебные крошки
        $aData['breadcrumbs'] = array(
            array('title'=>_t('bbs','Объявления'),'link'=>static::url('items.search'),'active'=>false),
            array('title'=>$aData['title_meta'],'active'=>true),
        );

        # фразы
        $aData['lang'] = array(
            'image_add' => _te('item-form', 'Добавить фото'),
            'image_del' => _te('item-form', 'Удалить фото'),
            'image_rotate' => _te('item-form', 'Повернуть фото'),
        );

        return $this->viewPHP($aData, 'item.form');
    }

    /**
     * Анализ и добавление параметров заказанных услуг для формы активации
     * (проброс параметров активации услуг со страницы добавления объявления на страницу активации promote)
     * @param integer $svcID ID услуги
     * @param array $params @ref параметры
     */
    protected function formSvcParams($svcID, array & $params = array())
    {
        $data = array(
            'fix_days' => TYPE_UINT, # количество дней закрепления объявления
        );
        foreach ($data as $k => $v) {
            $p = $this->input->postget($k, $v);
            if ( ! empty($p)) {
                $params[$k] = $p;
            }
        }
    }

    /**
     * Страница результата добавления / редактирование / управления ОБ
     * @param string $state ключ результата
     * @param integer $nItemID ID объявления
     * @param array $aData дополнительные данные
     * @return string HTML
     */
    protected function itemStatus($state, $nItemID, array $aData = array())
    {
        $title = '';

        do {
            # получаем данные об объявлении
            if (!$nItemID) {
                $state = false;
                break;
            }
            $aItemData = $this->model->itemData($nItemID, array(
                    'id',
                    'user_id',
                    'shop_id',
                    'title',
                    'link',
                    'status',
                    'status_prev',
                    'activate_key',
                    'svc',
                    'svc_up_activate',
                    'svc_premium_to',
                    'svc_marked_to',
                    'svc_press_status',
                    'svc_press_date',
                    'cat_id1',
                    'cat_id',
                    'cat_id_virtual',
                )
            );

            if (!empty($aItemData['cat_id_virtual'])) {
                $aItemData['cat_id'] = $aItemData['cat_id_virtual'];
            }

            if (empty($aItemData)) {
                $state = false;
                break;
            };

            if ($state == 'new' || $state == 'edit') {
                # активация услуги
                $nSvcID = $this->input->get('svc', TYPE_UINT);
                if ($nSvcID > 0 && bff::servicesEnabled()) {
                    $params = array(
                        'id'   => $nItemID,
                        'svc'  => $nSvcID,
                        'from' => $state
                    );
                    $this->formSvcParams($nSvcID, $params);
                    $this->redirect(static::url('item.promote', $params));
                }

                # проверяем владельца
                $nUserID = User::id();
                $nItemUserID = $aItemData['user_id'];
                if ($nUserID && $nItemUserID != $nUserID) {
                    break;
                }

                if ($aItemData['status'] == self::STATUS_NOTACTIVATED) {
                    # проверка корректности перехода, по совпадению части подстроки ключа активации
                    $activateCodePart = $this->input->get('ak', TYPE_STR);
                    if (stripos($aItemData['activate_key'], $activateCodePart) === false) {
                        $this->redirect(static::urlBase()); # не совпадают
                        break;
                    }

                    # Шаг активации объявления + телефона
                    $users = $this->users();
                    $registerPhone = Users::registerPhone();
                    $aData['new_user'] = false;
                    if (!$nUserID) {
                        # Запрещаем изменение номера телефона в случае если объявление добавляет
                        # неавторизованный пользователь от имени зарегистрированного с наличием одного и более активированных ОБ
                        $nUserItemsCounter = $this->model->itemsCount(array('user_id'=>$nItemUserID, 'status'=>array('!=',static::STATUS_NOTACTIVATED)));
                        if ($nUserItemsCounter > 1) {
                            $aData['new_user'] = true;
                        }
                    }
                    if ($registerPhone && Request::isAJAX())
                    {
                        $userData = $users->model->userData($nItemUserID, array(
                            'email', 'name', 'activated', 'activate_key', 'password', 'password_salt',
                            'phone_number', 'phone_number_verified',
                        ));
                        $act = $this->input->postget('act');
                        $response = array();
                        if (!$this->security->validateReferer() || empty($userData)) {
                            $this->errors->reloadPage(); $act = '';
                        }
                        $userPhone = $userData['phone_number'];
                        switch ($act)
                        {
                            # Проверка кода подтверждения
                            case 'code-validate':
                            {
                                $code = $this->input->postget('code', TYPE_NOTAGS);
                                if (mb_strtolower($code) !== $userData['activate_key']) {
                                    $this->errors->set(_t('users', 'Код подтверждения указан некорректно'), 'phone');
                                    break;
                                }
                                # Активируем аккаунт + объявления
                                if (empty($userData['activated']))
                                {
                                    $password = func::generator(12); # генерируем новый пароль
                                    $res = $users->model->userSave($nItemUserID, array(
                                        'phone_number_verified' => 1, 'activated' => 1, 'activate_key' => '',
                                        'password' => $this->security->getUserPasswordMD5($password, $userData['password_salt']),
                                    ));
                                    if ($res) {
                                        # Активируем объявления пользователя
                                        Users::i()->triggerOnUserActivated($nItemUserID, array(
                                            'context'   => 'item-activate-sms',
                                            'itemID'    => $nItemID,
                                            'itemState' => $state,
                                        ));
                                        # Авторизуем
                                        $users->userAuth($nItemUserID, 'user_id', $password, false);
                                        # Отправляем письмо об успешной регистрации
                                        bff::sendMailTemplate(array(
                                            'email'    => $userData['email'],
                                            'user_id'  => $nItemUserID,
                                            'password' => $password,
                                            'phone'    => $userData['phone_number'],
                                        ), 'users_register_phone', $userData['email']);
                                    } else {
                                        bff::log('bbs: Ошибка активации аккаунта пользователя по коду подтверждения [user-id="'.$nItemUserID.'"]');
                                        $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                                        break;
                                    }
                                } else {
                                    # Активируем объявления пользователя
                                    Users::i()->triggerOnUserActivated($nItemUserID, array(
                                        'context'   => 'item-activate-sms',
                                        'itemID'    => $nItemID,
                                        'itemState' => $state,
                                    ));
                                    # Авторизуем
                                    if (!$nUserID) {
                                        $users->userAuth($nItemUserID, 'user_id', $userData['password']);
                                    }
                                    # Помечаем успешное подтверждение номера телефона
                                    if (!$userData['phone_number_verified']) {
                                        $users->model->userSave($nItemUserID, array('phone_number_verified' => 1));
                                    }
                                }
                                $response['redirect'] = static::url('item.add', array(
                                    'id' => $nItemID, 'success' => 1, 'activated' => 1,
                                ));
                            } break;
                            # Повторная отправка кода подтверждения - OK
                            case 'code-resend':
                            {
                                $activationNew = $users->updateActivationKey($nItemUserID);
                                if ($activationNew) {
                                    $users->sms()->sendActivationCode($userPhone, $activationNew['key']);
                                }
                            } break;
                            # Смена номера телефона - OK
                            case 'phone-change':
                            {
                                if ($aData['new_user']) {
                                    $this->errors->reloadPage();
                                    break;
                                }
                                $phone = $this->input->postget('phone', TYPE_NOTAGS, array('len'=>30));
                                if (!$this->input->isPhoneNumber($phone)) {
                                    $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                                    break;
                                }
                                if ($phone === $userPhone) {
                                    break;
                                }
                                if ($users->model->userPhoneExists($phone, $nItemUserID)) {
                                    if ($nUserID) {
                                        $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован.'), 'phone');
                                    } else {
                                        $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован. <a [link_forgot]>Забыли пароль?</a>',
                                                array('link_forgot' => 'href="' . Users::url('forgot') . '"')
                                            ), 'phone'
                                        );
                                    }
                                    break;
                                }
                                $activationNew = $users->updateActivationKey($nItemUserID);
                                $res = $users->model->userSave($nItemUserID, array(
                                    'phone_number' => $phone,
                                    'phone_number_verified' => 0,
                                ));
                                if (!$res) {
                                    bff::log('bbs: Ошибка обновления номера телефона [user-id="'.$nItemUserID.'"]');
                                    $this->errors->reloadPage();
                                } else {
                                    $response['phone'] = '+'.$phone;
                                    $users->sms()->sendActivationCode($phone, $activationNew['key']);
                                }
                            } break;
                        }

                        $this->ajaxResponseForm($response);
                    }

                    $state = 'new.notactivated'.($registerPhone?'.phone':'');
                    $title = _t('bbs', 'Спасибо! Осталось всего лишь активировать объявление!');
                } else if ($aItemData['status'] == self::STATUS_BLOCKED) {
                    $state = 'edit.blocked.wait';
                    $title = _t('bbs', 'Вы успешно отредактировали объявление!');
                } else {
                    if ($state == 'new') {
                        $state = 'new.publicated';
                        if (!empty($aData['activated']) || $this->input->get('activated', TYPE_BOOL)) {
                            # активировали
                            $title = _t('bbs', 'Вы успешно активировали объявление!');
                            bff::hook('bbs.item.status.new.activated', $nItemID, $aItemData);
                        } else {
                            $title = _t('bbs', 'Вы успешно создали объявление!');
                            bff::hook('bbs.item.status.new.created', $nItemID, $aItemData);
                        }
                        if ($aItemData['status'] == self::STATUS_PUBLICATED_OUT) {
                            if ($aItemData['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled()) {
                                if (Shops::i()->abonementLimitExceed($aItemData['shop_id'])) {
                                    $aData['limitReason'] = _t('bbs', 'Превышение лимита тарифного плана. <a [link]>Изменить тариф</a>.', array('link'=>'href="'.Users::url('my.settings', array('t'=>'abonement')).'"'));
                                }
                            } else if (static::limitsPayedEnabled()) {
                                $limit = $this->model->limitsPayedCategoriesForUser(array(
                                    'user_id' => $nUserID,
                                    'shop_id' => $aItemData['shop_id'],
                                    'cat_id'  => $aItemData['cat_id'],
                                ));
                                if ( ! empty($limit)) {
                                    $limit = reset($limit);
                                    if ($limit['cnt'] >= $limit['limit']) {
                                        $aData['limitReason'] = _t('bbs', 'Превышение лимита платного пакета');
                                    }
                                }
                            }
                        }
                    } else {
                        if ($this->input->get('pub', TYPE_BOOL)) # изменился статус публикации
                        {
                            # опубликовали
                            if ($aItemData['status'] == self::STATUS_PUBLICATED &&
                                $aItemData['status_prev'] == self::STATUS_PUBLICATED_OUT
                            ) {
                                $state = 'edit.publicated';
                                $title = _t('bbs', 'Вы успешно опубликовали объявление!');
                            } # сняли с публикации
                            else if ($aItemData['status'] == self::STATUS_PUBLICATED_OUT &&
                                $aItemData['status_prev'] == self::STATUS_PUBLICATED
                            ) {
                                $state = 'edit.publicated.out';
                                $title = _t('bbs', 'Вы успешно сняли объявление с публикации!');
                            } else {
                                $state = 'edit.normal';
                                $title = _t('bbs', 'Вы успешно отредактировали объявление!');
                            }
                        } else {
                            # отредактировали без изменения статуса
                            $state = 'edit.normal';
                            $title = _t('bbs', 'Вы успешно отредактировали объявление!');
                        }
                    }
                }
            } else if ($state == 'promote.success') {
                $title = _t('bbs', 'Продвижение объявления');
            } else {
                $this->errors->error404();
            }

        } while (false);

        if ($state === false) {
            $this->errors->error404();
        }

        $aData['user'] = Users::model()->userData($aItemData['user_id'], array('email', 'phone_number', 'phone_number_verified', 'activated', 'name'));
        $aData['state'] = $state;
        $aData['item'] = & $aItemData;
        $aData['back'] = Request::referer(static::urlBase());
        $aData['from'] = $this->input->getpost('from', TYPE_STR);

        bff::setMeta($title);
        $this->seo()->robotsIndex(false);

        return $this->showShortPage($title, $this->viewPHP($aData, 'item.status'));
    }

    /**
     * Продвижение ОБ
     * @param getpost ::uint 'id' - ID объявления
     */
    public function promote()
    {
        $aData = array();
        $sTitle = _t('bbs', 'Продвижение объявления');
        $sFrom = $this->input->postget('from', TYPE_NOTAGS);
        $nUserID = User::id();
        $nSvcID = $this->input->postget('svc', TYPE_UINT);
        $nItemID = $this->input->getpost('id', TYPE_UINT);
        if (!empty($_GET['success'])) {
            return $this->itemStatus('promote.success', $nItemID);
        }

        $aItem = $this->model->itemData($nItemID, array(
                'user_id',
                'shop_id',
                'id',
                'status',
                'blocked_reason',
                'cat_id',
                'city_id',
                'title',
                'link',
                'svc',
                'svc_up_activate',
                'svc_fixed_to',
                'svc_premium_to',
                'svc_marked_to',
                'svc_press_status',
                'svc_press_date',
                'svc_press_date_last',
                'svc_quick_to',
                'svc_upauto_on',
                'svc_upauto_sett',
            )
        );
        $aPaySystems = Bills::getPaySystems($nUserID>0, true);

        $aSvc = $this->model->svcData();
        unset($aSvc[static::SERVICE_LIMIT]);
        $aSvcPrices = $this->model->svcPricesEx(array_keys($aSvc), $aItem['cat_id'], $aItem['city_id']);
        foreach ($aSvcPrices as $k => $v) {
            if (!empty($v)) $aSvc[$k]['price'] = $v;
        }

        $nUserBalance = $this->security->getUserBalance();

        # скидка услуги Абонемент
        if ($aItem['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled())
        {
            $shop = Shops::model()->shopData($aItem['shop_id'], array('svc_abonement_id'));
            if ($shop['svc_abonement_id']) {
                $tarif = Shops::model()->abonementData($shop['svc_abonement_id']);
                if ( ! empty($tarif['discount'])) {
                    $discounts = $tarif['discount'];
                    foreach ($aSvc as &$v) {
                        if ( ! empty($discounts[ $v['keyword'] ])) {
                            $discount = round($v['price'] * $discounts[ $v['keyword'] ] / 100, 2);
                            $v['price'] = round($v['price'] - $discount, 2);
                            $v['price_orig'] = $v['price'];
                            $v['discount'] = $discounts[ $v['keyword'] ];
                        }
                    } unset($v);
                }
            }
        }

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
                if (!$nItemID || empty($aItem) || in_array($aItem['status'], array(
                            self::STATUS_BLOCKED,
                            self::STATUS_DELETED,
                            self::STATUS_PUBLICATED_OUT
                        )
                    )
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$nSvcID || !isset($aSvc[$nSvcID])) {
                    $this->errors->set(_t('bbs', 'Выберите услугу'));
                    break;
                }
                $aSvcSettings = array();
                $nSvcPrice = $aSvc[$nSvcID]['price'];

                # сохраним настройки для услуги автоподнятие
                if ($nSvcID == static::SERVICE_UP && static::svcUpAutoEnabled()) {
                    $sett = $this->input->post('up_auto', TYPE_ARRAY);
                    $this->svcUpAutoSave($nItemID, $sett);
                }
                if ($nSvcID == static::SERVICE_FIX && ! empty($aSvc[$nSvcID]['period_type']) && $aSvc[$nSvcID]['period_type'] == static::SVC_FIX_PER_DAY) {
                    $days = $this->input->postget('fix_days', TYPE_UINT);
                    if ($days <= 0 || $days > 999) {
                        $this->errors->set(_t('bbs', 'Укажите количество дней для закрепления'));
                        break;
                    }
                    $aSvcSettings['period'] = $days;
                    $nSvcPrice = $aSvc[$nSvcID]['price'] * $days;
                }
                
                # конвертируем сумму в валюту для оплаты по курсу
                $pay = Bills::getPayAmount($nSvcPrice, $ps);

                if ($ps == 'balance' && $nUserBalance >= $nSvcPrice) {
                    # активируем услугу (списываем со счета пользователя)
                    $aResponse['redirect'] = static::url('item.promote', array(
                            'id'      => $nItemID,
                            'success' => 1,
                            'from'    => $sFrom
                        )
                    );
                    $aResponse['activated'] = $this->svc()->activate($this->module_name, $nSvcID, false, $nItemID, $nUserID, $nSvcPrice, $pay['amount'], $aSvcSettings);
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
                        $nItemID, $aSvcSettings
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

        # SEO
        $this->seo()->robotsIndex(false);
        bff::setMeta($sTitle);

        if (!$nItemID || empty($aItem)) {
            return $this->showForbidden($sTitle, _t('bbs', 'Объявление не найдено, либо ссылка указана некорректно'));
        }
        # Проверка доступности возможности продвижения
        if ($sFrom !== 'new' && !static::itemViewPromoteAvailable($this->isItemOwner($nItemID,$aItem['user_id']))) {
            return $this->showForbidden($sTitle, _t('bbs', 'Объявление не найдено, либо ссылка указана некорректно'));
        }
        # проверяем статус ОБ
        if ($aItem['status'] == self::STATUS_DELETED) {
            return $this->showForbidden($sTitle, _t('bbs', 'Объявление было удалено'));
        } else if ($aItem['status'] == self::STATUS_BLOCKED) {
            return $this->showForbidden($sTitle,
                _t('bbs', 'Объявление было заблокировано модератором, причина: [reason]', array(
                        'reason' => $aItem['blocked_reason']
                    )
                )
            );
        } else if ($aItem['status'] == self::STATUS_PUBLICATED_OUT) {
            return $this->showForbidden($sTitle,
                _t('bbs', 'Необходимо опубликовать объявление для дальнейшего его продвижения.')
            );
        }
        $aData['item'] = & $aItem;

        $this->urlCorrection(static::url('item.promote'));

        # способы оплаты
        $aData['curr'] = Site::currencyDefault();
        $aData['ps'] = & $aPaySystems;
        reset($aPaySystems);
        $aData['ps_active_key'] = key($aPaySystems);
        foreach ($aPaySystems as $k => &$v) {
            $v['active'] = ($k == $aData['ps_active_key']);
        }
        unset($v);

        # список услуг
        foreach ($aSvc as &$v) {
            $v['active'] = ($v['id'] == $nSvcID);
            if ($v['id'] == self::SERVICE_UP && $aItem['svc_up_activate'] > 0) {
                $v['price'] = 0;
            }
            if ($v['id'] == self::SERVICE_PRESS && $aItem['svc_press_status'] > 0) {
                $v['disabled'] = true;
                if ($v['active']) {
                    $v['active'] = false;
                    $nSvcID = 0;
                }
            }
            $aSvcPrices[$v['id']] = $v['price'];
        }
        unset($v);
        $aData['svc'] = & $aSvc;
        $aData['svc_id'] = $nSvcID;
        $aData['svc_prices'] = & $aSvcPrices;

        $aData['user_balance'] = & $nUserBalance;
        $aData['from'] = $sFrom;
        $aData['svc_autoup_form'] = '';
        if (static::svcUpAutoEnabled() && $this->isItemOwner($nItemID, $aItem['user_id'])) {
            $sett = array('id' => $nItemID, 'on' => $aItem['svc_upauto_on'], 'noForm' => 1, 'prefix' => 'up_auto');
            $sett = array_merge($sett, func::unserialize($aItem['svc_upauto_sett']));
            $aData['svc_autoup_form'] = $this->viewPHP($sett, 'item.svc.upauto');
        }

        return $this->viewPHP($aData, 'item.promote');
    }


    /**
     * Управление изображениями ОБ (ajax)
     * @param getpost ::uint 'item_id' - ID объявления
     * @param getpost ::string 'act' - action
     */
    public function img()
    {
        $this->security->setTokenPrefix('bbs-item-form');

        $nItemID = $this->input->getpost('item_id', TYPE_UINT);
        $oImages = $this->itemImages($nItemID);
        $aResponse = array();

        switch ($this->input->getpost('act')) {
            case 'upload': # загрузка
            {
                $aResponse = array('success' => false);
                do {
                    if (!$this->security->validateToken(true, false)) {
                        $this->errors->reloadPage();
                        break;
                    }
                    if ($nItemID) {
                        if (!$this->isItemOwner($nItemID)) {
                            $this->errors->set(_t('bbs', 'Вы не является владельцем данного объявления'));
                            break;
                        }
                    }

                    $result = $oImages->uploadQQ();

                    $aResponse['success'] = ($result !== false && $this->errors->no());
                    if ($aResponse['success']) {
                        $aResponse = array_merge($aResponse, $result);
                        $aResponse['tmp'] = empty($nItemID);
                        $aResponse['i'] = $oImages->getURL($result, BBSItemImages::szSmall, $aResponse['tmp']);
                        $aResponse['rotate'] = $oImages->rotateAvailable($result, $aResponse['tmp']);
                        unset($aResponse['dir'], $aResponse['srv']);
                    }
                } while (false);

                $aResponse['errors'] = $this->errors->get();
                $this->ajaxResponse($aResponse, true, false, true);
            }
            break;
            case 'delete': # удаление
            {
                $nImageID = $this->input->post('image_id', TYPE_UINT);
                $sFilename = $this->input->post('filename', TYPE_STR);

                # неуказан ID изображения ни filename временного
                if (!$nImageID && empty($sFilename)) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                if ($nItemID) {

                    # проверяем доступ на редактирование
                    if (!$this->isItemOwner($nItemID)) {
                        $this->errors->set(_t('bbs', 'Вы не является владельцем данного объявления'));
                        break;
                    }
                }

                if ($nItemID && $nImageID) {
                    # удаляем изображение по ID
                    $oImages->deleteImage($nImageID);
                } else {
                    # удаляем временное
                    $oImages->deleteTmpFile($sFilename);
                }
            }
            break;
            case 'rotate': # поворот изображения
            {
                $nImageID = $this->input->post('image_id', TYPE_UINT);
                $sFilename = $this->input->post('filename', TYPE_STR);

                # неуказан ID изображения ни filename временного
                if (!$nImageID && empty($sFilename)) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                if ($nItemID) {

                    # проверяем доступ на редактирование
                    if (!$this->isItemOwner($nItemID)) {
                        $this->errors->set(_t('bbs', 'Вы не является владельцем данного объявления'));
                        break;
                    }
                }

                if ($nItemID && $nImageID) {
                    # повернем изображение по ID
                    $result = $oImages->rotate($nImageID, -90);
                } else {
                    # повернем временное изображений
                    $result = $oImages->rotateTmp($sFilename, -90);
                }
                $aResponse['success'] = ($result !== false && $this->errors->no());
                if ($aResponse['success']) {
                    $aResponse = array_merge($aResponse, $result);
                    $aResponse['tmp'] = empty($nItemID);
                    $aResponse['i'] = $oImages->getURL($result, BBSItemImages::szSmall, $aResponse['tmp']);
                    unset($aResponse['dir'], $aResponse['srv']);
                }
            }
            break;
            case 'delete-tmp': # удаление временных
            {
                $aFilenames = $this->input->post('filenames', TYPE_ARRAY_STR);
                $oImages->deleteTmpFile($aFilenames);
            }
            break;
            default:
            {
                $this->errors->reloadPage();
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    /**
     * Активация ОБ
     * @param get ::string 'c' - ключ активации
     */
    public function activate()
    {
        $langActivateTitle = _t('bbs', 'Активация объявления');

        $sCode = $this->input->get('c', TYPE_STR); # ключ активации + ID объявления
        list($sCode, $nItemID) = explode('_', (!empty($sCode) && (strpos($sCode, '_') !== false) ? $sCode : '_'), 2);
        $nItemID = $this->input->clean($nItemID, TYPE_UINT);

        # SEO
        $this->seo()->robotsIndex(false);
        bff::setMeta($langActivateTitle);

        # 1. Получаем данные об ОБ:
        $aData = $this->model->itemData($nItemID, array(
                'user_id',
                'cat_id',
                'shop_id',
                'status',
                'activate_key',
                'activate_expire',
                'blocked_reason',
                'publicated_period',
            )
        );
        if (empty($aData)) {
            # не нашли такого объявления
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Объявление не найдено. Возможно период действия ссылки активации вашего объявления истек.', array('link_add' => 'href="' . static::url('item.add') . '"'))
            );
        }
        if ($aData['activate_key'] != $sCode || strtotime($aData['activate_expire']) < BFF_NOW) {
            # код неверный
            #  или
            # срок действия кода активации устек
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Срок действия ссылки активации истек либо она некорректна. Пожалуйста, <a [link_add]>добавьте новое объявление</a>.', array('link_add' => 'href="' . static::url('item.add') . '"'))
            );
        }
        if ($aData['status'] == self::STATUS_DELETED) {
            # объявление было удалено
            return $this->showForbidden($langActivateTitle, _t('bbs', 'Объявление было удалено'));
        }
        if ($aData['status'] == self::STATUS_BLOCKED) {
            # объявление было заблокировано
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Объявление было заблокировано модератором, причина: [reason]', array(
                        'reason' => $aData['blocked_reason']
                    )
                )
            );
        }

        # 2. Получаем данные о пользователе:
        $aUserData = Users::model()->userData($aData['user_id'], array(
                'user_id',
                'email',
                'name',
                'password',
                'password_salt',
                'activated',
                'blocked',
                'blocked_reason'
            )
        );
        if (empty($aUserData)) {
            # попытка активации объявления при отсутствующем профиле пользователя (публиковавшего объявление)
            return $this->showForbidden($langActivateTitle, _t('bbs', 'Ошибка активации, обратитесь в службу поддержки.'));
        } else {
            $nUserID = $aUserData['user_id'];
            # аккаунт заблокирован
            if ($aUserData['blocked']) {
                return $this->showForbidden($langActivateTitle,
                    _t('bbs', 'Ваш аккаунт заблокирован. За детальной информацией обращайтесь в службу поддержки.')
                );
            }
            # активируем аккаунт
            if (!$aUserData['activated']) {
                $sPassword = func::generator(12); # генерируем новый пароль
                $aUserData['password'] = $this->security->getUserPasswordMD5($sPassword, $aUserData['password_salt']);
                $bSuccess = Users::model()->userSave($nUserID, array(
                        'activated'    => 1,
                        'activate_key' => '',
                        'password'     => $aUserData['password'],
                    )
                );
                if ($bSuccess) {
                    $bUserActivated = true;
                    # отправляем письмо об успешной автоматической регистрации
                    bff::sendMailTemplate(array(
                            'name'     => $aUserData['name'],
                            'email'    => $aUserData['email'],
                            'password' => $sPassword,
                            'user_id'  => $nUserID,
                        ),
                        'users_register_auto', $aUserData['email']
                    );
                }
            }
            # авторизуем, если текущий пользователь неавторизован
            if (!User::id()) {
                Users::i()->userAuth($nUserID, 'user_id', $aUserData['password'], true);
            }
        }

        $status = self::STATUS_PUBLICATED;
        if ($aData['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled()) {
            # проверим превышение лимита лимитирования по абонементу
            if (Shops::i()->abonementLimitExceed($aData['shop_id'])) {
                $status = self::STATUS_PUBLICATED_OUT;
            }
        } else if (static::limitsPayedEnabled()) {
            # проверим превышение лимита
            $limit = $this->model->limitsPayedCategoriesForUser(array(
                'user_id' => $nUserID,
                'shop_id' => $aData['shop_id'],
                'cat_id'  => $aData['cat_id'],
            ));
            if ( ! empty($limit)) {
                $limit = reset($limit);
                if ($limit['cnt'] >= $limit['limit']) {
                    $status = self::STATUS_PUBLICATED_OUT;
                }
            }
        }

        # 3. Публикуем объявление:
        $bSuccess = $this->model->itemSave($nItemID, array(
                'activate_key'     => '', # чистим ключ активации
                'publicated'       => $this->db->now(),
                'publicated_order' => $this->db->now(),
                'publicated_to'    => $this->getItemPublicationPeriod($aData['publicated_period']),
                'status_prev'      => self::STATUS_NOTACTIVATED,
                'status'           => $status,
                'moderated'        => 0, # помечаем на модерацию
            )
        );

        if (isset($bUserActivated)) {
            # триггер активации аккаунта пользователя
            Users::i()->triggerOnUserActivated($nUserID, array(
                'context' => 'item-activate',
                'itemID'  => $nItemID,
                'itemStatus' => $status,
            ));
        }

        if (!$bSuccess) {
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Ошибка активации, обратитесь в службу поддержки.')
            );
        }

        # накручиваем счетчик кол-ва объявлений авторизованного пользователя
        $this->security->userCounter('items', 1, $nUserID); # +1
        # обновляем счетчик "на модерации"
        $this->moderationCounterUpdate();

        return $this->itemStatus('new', $nItemID, array('activated' => true));
    }

    /**
     * Кабинет пользователя: Импорт
     */
    public function my_import()
    {
        $nUserID = User::id();
        if (!$nUserID) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }
        
        if (static::publisher(static::PUBLISHER_SHOP) && !User::shopID()) {
            return $this->showInlineMessage(_t('bbs.import', 'Для возможности импорта объявлений <a [open_link]>откройте магазин</a>.',
                    array('open_link' => 'href="' . Shops::url('my.open') . '"')
                )
            );
        }

        $aData = array(
            'list'   => array(),
            'pgn'    => '',
            'pgn_pp' => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>15)), 'c' => 15),
                25 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>25)), 'c' => 25),
                50 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>50)), 'c' => 50),
            ),
        );

        $import = $this->itemsImport();
        $sAction = $this->input->getpost('sAction');
        switch ($sAction) {
            case 'template':
                $aSettings = array();
                $aSettings['catId'] = $this->input->get('cat_id', TYPE_UINT);
                $aSettings['state'] = $this->input->get('status', TYPE_UINT);
                $aSettings['extension'] = $this->input->get('extension', TYPE_NOTAGS);
                $aSettings['langKey'] = LNG;

                if (empty($aSettings['catId'])) {
                    $aData['errors'] = _t('bbs.import','Необходимо выбрать категорию');
                    break;
                }
                
                if (empty($aSettings['state'])) {
                    $aData['errors'] = _t('bbs.import', 'Необходимо выбрать статус объявлений');
                    break;
                }

                $import->importTemplate($aSettings);
                break;
            case 'import':
                if (Request::isPOST())
                {
                    $aResponse = array();
                    $aSettings = array(
                        'catId'  => $this->input->post('cat_id', TYPE_UINT),
                        'userId' => $nUserID,
                        'shop'   => User::shopID(),
                        'state'  => $this->input->post('status', TYPE_UINT),
                        'type'  => $this->input->post('type', TYPE_UINT),
                        'publicate_period' => $this->input->post('publicate_period', TYPE_UINT),
                    );

                    if (empty($aSettings['state'])) {
                        $this->errors->set(_t('bbs.import', 'Необходимо выбрать статус объявлений'));
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
                        if ($this->errors->no('bbs.import.url.submit',array('data'=>&$aSettings))) {
                            $aResponse['id'] = $import->importUrlStart($aSettings);
                        }
                    } else {
                        if ($this->errors->no('bbs.import.file.submit',array('data'=>&$aSettings))) {
                            $aResponse['id'] = $import->importStart('file', $aSettings);
                        }
                    }
                    $this->iframeResponseForm($aResponse);
                }
                break;
            case 'import-delete':
                $importID = $this->input->post('id', TYPE_UINT);

                if (!$importID || !$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }
                $importData = $this->model->importData($importID);
                if (empty($importData) || !User::isCurrent($importData['user_id'])) {
                    $this->errors->reloadPage();
                    break;
                }

                $aResponse = array();
                if ($this->errors->no())
                {
                    $aResponse['success'] = $this->model->importDelete($importID);
                    if ($aResponse['success']) {
                        $aResponse['message'] = _t('bbs.import', 'Данные об импорте успешно удалены');
                    }
                    $this->iframeResponseForm($aResponse);
                }
                $this->ajaxResponse($aResponse);
                break;

        }
        
        $f = $this->input->postgetm(array(
                'page' => TYPE_UINT, # страница
                'pp'   => TYPE_INT, # кол-во на страницу
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        if (!isset($aData['pgn_pp'][$f_pp])) {
            $f_pp = 15;
        }
        
        $aFilter = array(
            'user_id' => User::id(),
            'periodic' => BBSItemsImport::TYPE_FILE,
        );
        $sqlFields = array();
        $nTotal = $this->model->importListing($sqlFields, $aFilter, false, false, true);
        $oPgn = new Pagination($nTotal, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);
        if ($nTotal > 0) {
            $aData['pgn'] = $oPgn->view(array(), tpl::PGN_COMPACT);
            $aData['list'] = $this->model->importListing($sqlFields, $aFilter, $oPgn->getLimitOffset(), 'periodic DESC, created DESC');
            if (!empty($aData['list'])) {
                foreach ($aData['list'] as &$v) {
                    $v['comment_text'] = '';
                    $comment = func::unserialize($v['status_comment']);
                    if ($comment) {
                        if ($v['status'] == BBSItemsImport::STATUS_FINISHED) {
                            $details = array();
                            if ($v['items_ignored'] > 0)     $details[] = _t('bbs.import','пропущено: [count]',array('count'=>$v['items_ignored']));
                            if (!empty($comment['success'])) $details[] = _t('bbs.import','добавлено: [count]',array('count'=>$comment['success']));
                            if (!empty($comment['updated'])) $details[] = _t('bbs.import','обновлено: [count]',array('count'=>$comment['updated']));
                            if (!empty($details)) $v['comment_text'] = implode(', ',$details);
                        } elseif (isset($comment['message'])) {
                            $v['comment_text'] = _t('bbs.import', 'Ошибка обработки файла импорта, обратитесь к администратору');
                        }
                    }
                    
                    $v['file_link'] = false;
                    $file = func::unserialize($v['filename']);
                    if ( ! empty($file)) {
                        $v['file_link'] = BBSItemsImport::getImportPath(true, $file['filename']);
                    }
                } unset($v);
            }
        }

        $aData['list_total'] = $nTotal;
        $aData['status'] = $import->getStatusList();
        $aData['list'] = $this->viewPHP($aData, 'my.import.list');
        
        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'pgn'   => $aData['pgn'],
                    'list'  => $aData['list'],
                    'total' => $nTotal,
                )
            );
        }

        # Периодический импорт - список
        $aData['periodic'] = '';
        if (static::importUrlEnabled())
        {
            $filter = array(
                'user_id' => User::id(),
                'periodic' => BBSItemsImport::TYPE_URL,
            );
            $aData['periodic'] = $this->model->importListing(array('I.id', 'periodic_url', 'status_comment', 'periodic_timeout',
                'items_processed'), $filter);
            foreach ($aData['periodic'] as &$v) {
                $v['comment'] = '';
                $comment = func::unserialize($v['status_comment']);
                if (isset($comment['date']) && isset($comment['message'])) {
                    $v['comment'] = (is_array($comment['message']) ? join(';', $comment['message']) : $comment['message']);
                }
            } unset($v);
            $aData['periodic'] = $this->viewPHP($aData, 'my.import.periodic');
        }
        
        $aData['f'] = & $f;
        $aData['page'] = $oPgn->getCurrentPage();
        return $this->viewPHP($aData, 'my.import');
    }
    
    /**
     * Кабинет пользователя: Объявления
     * @param integer $nShopID ID магазина или 0
     * @param boolean $onlyIDs вернуть только ID объявлений
     */
    public function my_items($nShopID = 0, $onlyIDs = false)
    {
        $nUserID = User::id();
        if ( ! $nUserID) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }
        if (static::publisher(static::PUBLISHER_SHOP) && !User::shopID()) {
            return $this->showInlineMessage(_t('bbs.my', 'Для возможности публикации объявлений <a [open_link]>откройте магазин</a>.', array('open_link' => 'href="' . Shops::url('my.open') . '"')));
        }
        $messages = array();

        $sAction = $this->input->postget('act', TYPE_STR);
        if (Request::isGET() && ! Request::isAJAX())
        {
            switch ($sAction) {
                case 'email-publicate': # массовая публикация по ссылке из E-mail уведомления bbs_item_unpublicated_soon_group

                    $sAction = '';
                    $day = $this->input->get('day', TYPE_NOTAGS);
                    if (empty($day)) break;

                    $day = strtotime($day);
                    if ( ! $day || $day < time() || ($day - static::PUBLICATION_SOON_LEFT) > time()) {
                        break;
                    }

                    $data = $this->model->itemsUserUnpublicateDay($nUserID, $day);
                    if (empty($data))  break;

                    $items = array();
                    $itemsShop = array();
                    foreach ($data as $v) {
                        $items[] = $v['id'];
                        if ($v['shop_id']) {
                            $itemsShop[] = $v['id'];
                        }
                    }
                    $shopID = User::shopID();
                    if (static::limitsPayedEnabled()) {
                        # проверим превышение лимита
                        $limit = $this->model->limitsPayedCategoriesForUser(array(
                            'user_id' => $nUserID,
                            'shop_id' => 0
                        ), array('strict' => 1));
                        if ($limit) {
                            $this->errors->set(_t('bbs', 'Превышение лимита публикаций.'));
                            break;
                        }
                        if ($shopID && (bff::shopsEnabled() && ! Shops::abonementEnabled())) {
                            $limit = $this->model->limitsPayedCategoriesForUser(array(
                                'user_id' => $nUserID,
                                'shop_id' => $shopID
                            ), array('strict' => 1));
                            if ($limit) {
                                $this->errors->set(_t('bbs', 'Превышение лимита публикаций.'));
                                break;
                            }

                        }
                    }
                    if ($shopID && bff::shopsEnabled() && Shops::abonementEnabled()) {
                        # проверим превышение лимита лимитирования по абонементу
                        if (Shops::i()->abonementLimitExceed($nShopID, count($itemsShop))) {
                            $this->errors->set(_t('bbs', 'Превышение лимита тарифного плана. <a [link]>Изменить тариф</a>.', array('link'=>'href="'.Users::url('my.settings', array('t'=>'abonement')).'"')));
                            break;
                        }
                    }
                    if ( ! $this->errors->no()) break;
                    $cnt = $this->model->itemsRefresh(array(
                        'id'      => $items,
                        'user_id' => $nUserID,
                        'status'  => self::STATUS_PUBLICATED,
                    ));
                    if ($cnt) {
                        $messages[] = _t('bbs', 'Продлена публикация для [cnt].', array('cnt' => tpl::declension($cnt, _t('bbs', 'объявления;объявлений;объявлений'))));
                    }
                    break;
                case 'email-up-free': # массовое поднятие объявлений по ссылке из E-mail уведомления bbs_item_upfree_group

                    $sAction = '';
                    $days = static::svcUpFreePeriod();
                    if ( ! $days) {
                        break;
                    }

                    $items = $this->model->itemsUserUpFreeEnable($nUserID, $days);
                    if (empty($items))  break;

                    $cnt = $this->model->itemsUpFree(array('id' => $items));
                    if ($cnt) {
                        $messages[] = _t('bbs', 'Успешно поднято [cnt].', array('cnt' => tpl::declension($cnt, _t('bbs', 'объявления;объявлений;объявлений'))));
                    }
                    break;
                default:
                    bff::hook('bbs.items.my.default.action', $sAction);
                    break;
            }
        }

        if (!empty($sAction)) {
            $aResponse = array();

            if (!$this->security->validateToken()) {
                $this->errors->reloadPage();
            } else {
                $aItemID = $this->input->post('i', TYPE_ARRAY_UINT);
                if (empty($aItemID)) {
                    $this->errors->set(_t('bbs.my', 'Необходимо отметить минимум одно из ваших объявлений'));
                }
                if ($this->input->post('select_all', TYPE_UINT)) {
                    $_GET['act'] = ''; # TODO
                    $aItemID = $this->my_items($nShopID, true);
                    if (empty($aItemID)) {
                        $this->errors->reloadPage();
                    }
                }
            }

            if ($this->errors->no()) {
                switch ($sAction) {
                    case 'mass-publicate': # массовая публикация

                        if ($nShopID && bff::shopsEnabled() && Shops::abonementEnabled()) {
                            # проверим превышение лимита лимитирования по абонементу
                            if (Shops::i()->abonementLimitExceed($nShopID, count($aItemID))) {
                                $this->errors->set(_t('bbs', 'Превышение лимита тарифного плана. <a [link]>Изменить тариф</a>.', array('link'=>'href="'.Users::url('my.settings', array('t'=>'abonement')).'"')));
                                break;
                            }
                        } else if (static::limitsPayedEnabled()) {
                            # проверим превышение лимита
                            $limit = $this->model->limitsPayedCategoriesForUser(array(
                                'user_id' => $nUserID,
                                'shop_id' => $nShopID,
                                'id'      => $aItemID,
                            ), array('strict' => 1));
                            if ($limit) {
                                $this->errors->set(_t('bbs', 'Превышение лимита публикаций.'));
                                break;
                            }
                        }

                        $publicateFilter = array(
                            'id'      => $aItemID,
                            'user_id' => $nUserID,
                            'is_publicated' => 0,
                            'status'  => self::STATUS_PUBLICATED_OUT,
                        );
                        if (static::premoderation()) {
                            $publicateFilter[] = 'moderated > 0';
                        }
                        $aResponse['cnt'] = $this->model->itemsPublicate($publicateFilter);

                        if (!empty($aResponse['cnt'])) {
                            $aResponse['message'] = _t('bbs.my', 'Отмеченные объявления были успешно опубликованы');
                        }
                    break;
                    case 'mass-unpublicate': # массовое снятие с публикации

                        $aResponse['cnt'] = $this->model->itemsUnpublicate(array(
                            'id'      => $aItemID,
                            'user_id' => $nUserID,
                            'is_publicated' => 1,
                            'status'  => self::STATUS_PUBLICATED,
                        ));
                        if (!empty($aResponse['cnt'])) {
                            $aResponse['message'] = _t('bbs.my', 'Отмеченные объявления были успешно сняты с публикации');
                        }
                    break;
                    case 'mass-refresh': # массовое продление

                        if ($nShopID && bff::shopsEnabled() && Shops::abonementEnabled()) {
                            # проверим превышение лимита лимитирования по абонементу
                            if (Shops::i()->abonementLimitExceed($nShopID, count($aItemID))) {
                                $this->errors->set(_t('bbs', 'Превышение лимита тарифного плана. <a [link]>Изменить тариф</a>.', array('link'=>'href="'.Users::url('my.settings', array('t'=>'abonement')).'"')));
                                break;
                            }
                        } else if(static::limitsPayedEnabled()) {
                            # проверим превышение лимита
                            $limit = $this->model->limitsPayedCategoriesForUser(array(
                                'user_id' => $nUserID,
                                'shop_id' => $nShopID
                            ), array('strict' => 1));
                            if ($limit) {
                                $this->errors->set(_t('bbs', 'Превышение лимита публикаций.'));
                                break;
                            }
                        }

                        $aResponse['cnt'] = $this->model->itemsRefresh(array(
                            'id'      => $aItemID,
                            'user_id' => $nUserID,
                            'is_publicated' => 1,
                            'status'  => self::STATUS_PUBLICATED,
                        ));

                        if (!empty($aResponse['cnt'])) {
                            $aResponse['message'] = _t('bbs.my', 'Отмеченные объявления были успешно продлены');
                        }
                    break;
                    case 'mass-delete': # массовое удаление
                    {

                        $aResponse['cnt'] = $this->model->itemsUpdateByFilter(array(
                            'publicated_to' => $this->db->now(), # помечаем дату снятия с публикации
                            'status_prev = status',
                            'status_changed'=> $this->db->now(),
                            'status'        => self::STATUS_DELETED,
                            'deleted'       => 1,
                            'is_publicated' => 0,
                            'is_moderating' => 0,
                        ), array(
                            'id'      => $aItemID,
                            'user_id' => $nUserID,
                            # для удаления доступны только снятые с публикации или заблокированные
                            'is_publicated' => 0,
                            'status'  => array(self::STATUS_PUBLICATED_OUT, self::STATUS_BLOCKED),
                            'deleted' => 0,
                        ), array(
                            'context' => 'user-cabinet-mass-delete',
                        ));
                        if ($aResponse['cnt']) {
                            $aResponse['success_msg'] = _t('bbs', 'Отмеченные объявления были успешно удалены');
                            $aResponse['id'] = $aItemID;
                        }
                    }
                    break;
                    case 'mass-up-free': # массовое поднятие бесплатно
                    {

                        $days = static::svcUpFreePeriod();
                        if ( ! $days) {
                            $this->errors->reloadPage();
                            break;
                        }
                        $aResponse['cnt'] = $this->model->itemsUpFree(array(
                            'id' => $aItemID,
                            'user_id' => $nUserID,
                            'svc_up_free <= :date',
                        ), array(
                            'bind' => array(':date' => date('Y-m-d', strtotime('-'.$days.' days'))),
                        ));

                        if (!empty($aResponse['cnt'])) {
                            $aResponse['message'] = _t('bbs.my', 'Отмеченные объявления были успешно подняты');
                        }
                    }   break;
                    default:
                    {
                        $this->errors->reloadPage();
                    }
                    break;
                }
            }

            # проверим превышение лимита, для вывода алерта в кабинете
            if ($nShopID && bff::shopsEnabled() && Shops::abonementEnabled()) {
                $aResponse['shopAbonement'] = Shops::i()->abonementLimitExceed($nShopID);
            } else if (static::limitsPayedEnabled()) {
                $aResponse['limitsPayed'] = $this->model->limitsPayedCategoriesForUser(array(
                    'user_id' => $nUserID,
                    'shop_id' => $nShopID,
                ), true);
            }

            $this->ajaxResponseForm($aResponse);
        }

        $aData = array(
            'items'  => array(),
            'pgn'    => '',
            'pgn_pp' => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>15)), 'c' => 15),
                25 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>25)), 'c' => 25),
                50 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>50)), 'c' => 50),
            ),
        );

        $aFilter = array(
            'user_id' => $nUserID,
            'shop_id' => $nShopID,
        );

        $f = $this->input->postgetm(array(
            'status' => TYPE_UINT, # статус
            'c'      => TYPE_UINT, # ID категории
            'qq'     => TYPE_NOTAGS, # строка поиска
            'page'   => TYPE_UINT, # страница
            'pp'     => TYPE_INT, # кол-во на страницу
        ));
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        if (!isset($aData['pgn_pp'][$f_pp])) {
            $f_pp = 15;
        }

        if ($f_c > 0) {
            $aFilter[':cat-filter'] = $f_c;
        }

        if (!empty($f_qq)) {
            $f_qq = $this->input->cleanSearchString($f_qq, 50);
            if ( ! empty($f_qq)) {
                $aFilter[':query'] = $f_qq;
            }
        }

        $statusList = bff::filter('bbs.items.my.status.list', array(
            1 => array(
                'title' => _t('bbs.my', 'Активные'), 'left' => false, 'right' => 2,
                'filter' => function(&$filter) {
                    $filter['is_publicated'] = 1;
                    $filter['status'] = self::STATUS_PUBLICATED;
                    return $filter;
                }
            ),
            2 => array(
                'title' => _t('bbs.my', 'На проверке'), 'left' => 1, 'right' => 3,
                'filter' => function(&$filter) {
                    $filter['is_publicated'] = 0;
                    if (static::premoderation()) {
                        $filter['status'] = array('!=', self::STATUS_DELETED);
                    } else {
                        $filter['status'] = self::STATUS_BLOCKED;
                    }
                    return $filter;
                }
            ),
            3 => array(
                'title' => _t('bbs.my', 'Неактивные'), 'left' => 2, 'right' => false,
                'filter' => function(&$filter) {
                    $filter['is_publicated'] = 0;
                    $filter['status'] = self::STATUS_PUBLICATED_OUT;
                    return $filter;
                }
            ),
        ));

        if (!array_key_exists($f_status, $statusList)) {
            $f_status = key($statusList);
        }
        # дополняем фильтрами по статусу
        $statusList[$f_status]['filter']($aFilter);

        if ($onlyIDs) {
            $aFilter['onlyIDs'] = true;
            return $this->model->itemsListMy($aFilter);
        }

        $nTotal = $aData['total'] = $this->model->itemsListMy($aFilter, true);
        if ($nTotal > 0) {
            $oPgn = new Pagination($nTotal, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);
            $aData['pgn'] = $oPgn->view(array(), tpl::PGN_COMPACT);
            $aData['items'] = $this->model->itemsListMy($aFilter, false, array(
                'limit' => $oPgn->getLimit(),
                'offset' => $oPgn->getOffset(),
                'orderBy' => 'id DESC',
            ));
        }
        $aData['counters'] = array();
        foreach ($statusList as $k=>$v) {
            $aFilterCopy = $aFilter;
            $v['filter']($aFilterCopy); unset($statusList[$k]['filter']);
            $aData['counters'][$k] = $this->model->itemsListMy($aFilterCopy, true);
        }

        # бесплатное поднятие
        $aData['upfree_days'] = static::svcUpFreePeriod();
        if ($aData['upfree_days'] > 0) {
            $aData['upfree_to'] = strtotime('-' . $aData['upfree_days'] . ' days');
        }

        # формируем список
        $aData['device'] = bff::device();
        $aData['img_default'] = $this->itemImages()->urlDefault(BBSItemImages::szSmall);
        $aData['list'] = $this->viewPHP($aData, 'my.items.list'); unset($aData['items']);

        # список категорий (фильтр)
        $aCats = array(0 => array('id' => 0, 'title' => _t('bbs', 'Все категории')));
        $aCats += $this->model->itemsListCategoriesMain(array(
            'user_id' => $nUserID,
            'shop_id' => $nShopID,
        ));
        $aData['cats'] = &$aCats;
        $aData['cat_active'] = (isset($aCats[$f_c]) ? $aCats[$f_c] : $aCats[0]);
        $aData['cats'] = $this->viewPHP($aData, 'my.items.cats');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                'pgn'   => $aData['pgn'],
                'list'  => $aData['list'],
                'cats'  => $aData['cats'],
                'total' => $aData['total'],
                'counters' => $aData['counters'],
            ));
        }

        $aData['shop_id'] = User::shopID();
        $aData['shop'] = ($nShopID ? 1 : 0);
        $aData['f'] = & $f;
        $aData['empty'] = !$nTotal;
        $aData['status'] = $statusList;

        # проверим превышение лимита, для вывода алерта в кабинете
        if ($nShopID && bff::shopsEnabled() && Shops::abonementEnabled()) {
            $aData['shopAbonement'] = Shops::i()->abonementLimitExceed($nShopID);
        } else if(static::limitsPayedEnabled()) {
            $aData['limitsPayed'] = $this->model->limitsPayedCategoriesForUser(array(
                'user_id' => $nUserID,
                'shop_id' => $nShopID,
            ), true);
        }
        $aData['messages'] = $messages;

        return $this->viewPHP($aData, 'my.items');
    }

    /**
     * Кабинет пользователя: Избранные объявления
     */
    public function my_favs()
    {
        $nUserID = User::id();
        $sAction = $this->input->post('act', TYPE_STR);
        if (!empty($sAction)) {
            switch ($sAction) {
                # удаление всех избранных объявлений
                case 'cleanup':
                {
                    if ($nUserID) {
                        if (!$this->security->validateToken()) {
                            $this->errors->reloadPage();
                            break;
                        }
                        $this->model->itemsFavDelete($nUserID);
                        # актулизируем счетчик избранных пользователя
                        User::counterSave('items_fav', 0);
                    } else {
                        Request::deleteCOOKIE(BBS_FAV_COOKIE);
                    }
                } break;
                default: {
                    bff::hook('bbs.items.my.favs.default.action', $sAction);
                } break;
            }
            $this->ajaxResponseForm();
        }

        $aData = array(
            'items'  => array(),
            'pgn'    => '',
            'pgn_pp' => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>15)), 'c' => 15),
                25 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>25)), 'c' => 25),
                50 => array('t' => _t('pgn', 'по [cnt] на странице', array('cnt'=>50)), 'c' => 50),
            ),
            'device' => bff::device(),
        );
        $f = $this->input->postgetm(array(
            'c'    => TYPE_UINT, # ID категории
            'lt'   => TYPE_UINT, # тип списка
            'page' => TYPE_UINT, # страница
            'pp'   => TYPE_INT, # кол-во на страницу
        ));
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        $f_lt = self::LIST_TYPE_LIST;
        if (!isset($aData['pgn_pp'][$f_pp]))
            $f_pp = 15;

        $aFavoritesID = $this->getFavorites($nUserID);
        $aFilter = array('id' => $aFavoritesID, 'is_publicated' => 1, 'status' => self::STATUS_PUBLICATED);

        # корректируем счетчик избранных ОБ пользователя
        $aFavoritesExists = $this->model->itemsSearch($aFilter, array('context'=>'my-favs'));
        if (sizeof($aFavoritesID) != sizeof($aFavoritesExists)) {
            if ($nUserID) {
                $aDeleteID = array();
                foreach ($aFavoritesID as $v) {
                    if (!in_array($v, $aFavoritesExists)) $aDeleteID[] = $v;
                }
                if ( ! empty($aDeleteID)) {
                    $this->model->itemsFavDelete($nUserID, $aDeleteID);
                    User::counterSave('items_fav', sizeof($aFavoritesExists));
                }
            } else {
                Request::setCOOKIE(BBS_FAV_COOKIE, join('.', $aFavoritesExists), 2);
            }
        } else {
            $nCounter = User::counter('items_fav');
            if ($nCounter != sizeof($aFavoritesID)) {
                # актулизируем счетчик избранных пользователя
                User::counterSave('items_fav', sizeof($aFavoritesID));
            }
        }

        if ($f_c > 0) {
            $aFilter[':cat-filter'] = $f_c;
        }

        $nTotal = $this->model->itemsList($aFilter, true, array('context' => 'my-favs'));
        if ($nTotal > 0) {
            $oPgn = new Pagination($nTotal, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);
            $aData['items'] = $this->model->itemsList($aFilter, false, array(
                'context' => 'my-favs',
                'orderBy' => 'publicated_order DESC',
                'limit' => $oPgn->getLimit(),
                'offset' => $oPgn->getOffset(),
            ));
            $aData['pgn'] = $oPgn->view(array(), tpl::PGN_COMPACT);
        }

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                'list' => $this->searchList($aData['device'], $f_lt, $aData['items']),
                'pgn'  => $aData['pgn']
            ));
        }

        $aCats = array(0 => array('id' => 0, 'title' => _t('bbs', 'Все категории')));
        if ($nTotal > 0) {
            unset($aFilter[':cat-filter']);
            $aCats += $this->model->itemsListCategoriesMain($aFilter);
        }
        $aData['cat_active'] = (isset($aCats[$f_c]) ? $aCats[$f_c] : $aCats[0]);

        $aData['f'] = & $f;
        $aData['cats'] = & $aCats;
        $aData['empty'] = !$nTotal;
        $aData['total'] = $nTotal;

        return $this->viewPHP($aData, 'my.favs');
    }

    /**
     * Кабинет пользователя: Платные пакеты (купленные активные поинты)
     * @return string HTML
     */
    public function my_limits_payed()
    {
        $userID = User::id();
        if ( ! $userID ||  ! static::limitsPayedEnabled()) {
            $this->errors->error404();
        }
        $shopID = 0;
        $shop = 0;
        $shopNavigation = bff::shopsEnabled() && ! Shops::abonementEnabled() && User::shopID();
        if ($shopNavigation) {
            $shop = $this->input->get('shop', TYPE_UINT);
            if ($shop) {
                $shopID = User::shopID();
            }
        }
        $data = array('shop' => $shop, 'shopNavigation' => $shopNavigation);

        $term = config::get('bbs_limits_payed_days', 0, TYPE_UINT);
        $data['term'] = $term;
        $filter = array(
            'user_id' => $userID,
            'shop'    => $shop,
            'active'  => 1,
        );
        # список купленных пакетов
        $limits = $this->model->limitsPayedUserByFilter($filter, array('id', 'cat_id', 'items', 'expire', 'paid_id'), false, '', 'cat, id');

        if ($term) {
            # определим какие нельзя продлить
            $payed = array();
            foreach ($limits as $v) {
                $payed[] = $v['paid_id'];
            }
            $payed = $this->model->limitsPayedByFilter(array('id' => $payed), array('id', 'settings'), false);
            $payed = func::array_transparent($payed, 'id', true);
            foreach ($limits as & $v) {
                if ( ! isset($payed[$v['paid_id']])) continue;
                $settings = $payed[$v['paid_id']]['settings'];
                foreach ($settings as $vv) {
                    if ($vv['items'] == $v['items']) {
                        $v['allowExtend'] = true;
                        break;
                    }
                }
            } unset($v);
        }

        $points = array();
        foreach ($limits as $v) {
            if ( ! isset($points[ $v['cat_id'] ])) {
                $point = array(
                    'cat_id' => $v['cat_id'],
                    'limits' => array(),
                    'free'   => $this->model->limitsPayedFreeForCategory($v['cat_id'], $shop), # количество бесплатных объявлений
                    'cnt'    => 0,
                );
                # заголовок название категории
                $point += $this->limitsPayedCatTitle($v['cat_id'], false);
                $points[ $v['cat_id'] ] = $point;
            }
            $points[ $v['cat_id'] ]['limits'][] = $v;
        }

        # количество объявлений для поинтов у пользователя
        $items = $this->model->limitsPayedCategoriesForUser(array(
            'user_id' => $userID,
            'shop_id' => $shopID,
        ));
        foreach ($items as $v) {
            if (isset($points[ $v['point'] ])) {
                $points[ $v['point'] ]['cnt'] = $v['cnt'];
            }
        }

        # расчет количества и остатка для шаблона
        foreach ($points as & $v) {
            $v['items'] = 0;
            foreach ($v['limits'] as $vv) {
                $v['items'] += $vv['items'];
            }
            $v['total'] = $v['items'] + $v['free'];
            $v['rest'] = $v['total'] - $v['cnt'];
            if ($v['rest'] < 0) {
                $v['rest'] = 0;
            }
        } unset($v);

        $data['points'] = $points;
        return $this->viewPHP($data, 'my.limits.payed');
    }

    /**
     * Профиль пользователя: Объявления
     * @param integer $userID ID пользователя
     * @param array $userData данные пользователя
     */
    public function user_items($userID, $userData)
    {
        $pageSize = config::sysAdmin('bbs.user.items.pagesize', 10, TYPE_UINT);
        $data = array('items' => array(), 'pgn' => '', 'device' => bff::device());

        $f = $this->input->postgetm(array(
            'c'    => TYPE_UINT, # ID категории
            'page' => TYPE_UINT, # страница
        ));
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        $f['lt'] = self::LIST_TYPE_LIST;

        $filter = array('user_id' => $userID, 'shop_id' => 0, 'is_publicated' => 1, 'status' => self::STATUS_PUBLICATED);
        if ($f_c > 0) {
            $filter[':cat-filter'] = $f_c;
        }

        $total = $this->model->itemsList($filter, true, array('context' => 'user-items'));
        if ($total > 0) {
            $pgn = new Pagination($total, $pageSize, array(
                'link'  => $userData['profile_link'],
                'query' => array('page' => $f['page'], 'c' => $f['c']),
            ));
            $f['page'] = $pgn->getCurrentPage();
            $data['items'] = $this->model->itemsList($filter, false, array(
                'context' => 'user-items',
                'orderBy' => 'publicated_order DESC',
                'limit'   => $pgn->getLimit(),
                'offset'  => $pgn->getOffset(),
            ));
            $data['pgn'] = $pgn->view();
        }

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                'list' => $this->searchList($data['device'], $f['lt'], $data['items']),
                'pgn'  => $data['pgn']
            ));
        }

        # SEO: Объявления пользователя
        $this->urlCorrection($userData['profile_link']);
        $this->seo()->robotsIndex(!$f_c);
        $this->seo()->canonicalUrl($userData['profile_link_dynamic'], array('page' => $f['page']),
            ($total > 0 ? array('page-current' => $f['page'], 'page-last' => $pgn->getPageLast()) : array())
        );
        $this->setMeta('user-items', array(
                'name'   => $userData['name'],
                'region' => ($userData['region_id'] ? $userData['region_title'] : ''),
                'country' => ($userData['reg1_country'] ? Geo::regionData($userData['reg1_country']) : ''),
                'page'   => $f['page'],
            ), $data
        );

        # категории (фильтр)
        $cats = array(0 => array('id' => 0, 'title' => _t('bbs', 'Все категории')));
        if ($total > 0) {
            unset($filter[':cat-filter']);
            $cats += $this->model->itemsListCategoriesMain($filter);
        }
        $data['cat_active'] = (isset($cats[$f_c]) ? $cats[$f_c] : $cats[0]);

        $data['f'] = & $f;
        $data['cats'] = & $cats;
        $data['empty'] = !$total;

        return $this->viewPHP($data, 'user.items');
    }

    /**
     * Страница магазина: Объявления
     * @param integer $shopID ID магазина
     * @param array $shopData данные магазина
     */
    public function shop_items($shopID, $shopData)
    {
        $pageSize = config::sysAdmin('bbs.shop.items.pagesize', 10, TYPE_UINT);
        $data = array('items' => array(), 'pgn' => '', 'device' => bff::device());

        $f = $this->input->postgetm(array(
            'c'    => TYPE_UINT, # ID категории
            'page' => TYPE_UINT, # страница
        ));
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        $f['lt'] = self::LIST_TYPE_LIST;

        $filter = array(
            'user_id' => $shopData['user_id'],
            'shop_id' => $shopID,
            'is_publicated' => 1,
            'status' => self::STATUS_PUBLICATED,
        );
        if ($f_c > 0) {
            $filter[':cat-filter'] = $f_c;
        }

        $total = $this->model->itemsList($filter, true, array('context' => 'shop-items'));
        if ($total > 0) {
            $pgn = new Pagination($total, $pageSize, array(
                'link'  => $shopData['link'],
                'query' => array('page' => $f['page'], 'c' => $f['c']),
            ));
            $f['page'] = $pgn->getCurrentPage();
            $data['items'] = $this->model->itemsList($filter, false, array(
                'context' => 'shop-items',
                'orderBy' => 'publicated_order DESC',
                'limit'   => $pgn->getLimit(),
                'offset'  => $pgn->getOffset(),
            ));
            $data['pgn'] = $pgn->view();
        }

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                'list' => $this->searchList($data['device'], $f['lt'], $data['items']),
                'pgn'  => $data['pgn']
            ));
        }

        # Категории
        $cats = array(0 => array('id' => 0, 'title' => _t('bbs', 'Все категории')));
        if ($total > 0) {
            unset($filter[':cat-filter']);
            $cats += $this->model->itemsListCategoriesMain($filter);
        }
        $data['cat_active'] = (isset($cats[$f_c]) ? $cats[$f_c] : $cats[0]);

        # SEO: Страница магазина (с владельцем)
        $this->urlCorrection($shopData['link']);
        $this->seo()->robotsIndex(!$f_c);
        $this->seo()->canonicalUrl($shopData['link_dynamic'], array('page' => $f['page']),
            ($total > 0 ? array('page-current' => $f['page'], 'page-last' => $pgn->getPageLast()) : array())
        );
        $this->seo()->setPageMeta('shops', 'shop-view', array(
                'title'       => $shopData['title'],
                'description' => tpl::truncate($shopData['descr'], 150),
                'region'      => ($shopData['region_id'] ? $shopData['city'] : ''),
                'country'     => (!empty($shopData['country']['title']) ? $shopData['country']['title'] : ''),
                'page'        => $f['page'],
            ), $shopData
        );
        $this->seo()->setSocialMetaOG($shopData['share_title'], $shopData['share_description'], $shopData['logo'], $shopData['link'], $shopData['share_sitename']);

        $data['f'] = & $f;
        $data['cats'] = & $cats;
        $data['empty'] = !$total;

        return $this->viewPHP($data, 'shop.items');
    }

    /**
     * Список категорий для страницы "Карта сайта"
     */
    public function catsListSitemap()
    {
        $iconSize = BBSCategoryIcon::SMALL;
        $aData = $this->model->catsListSitemap($iconSize);
        if (!empty($aData)) {
            foreach ($aData as &$v) {
                $v['link'] = static::url('items.search', array('keyword' => $v['keyword'], 'landing_url' => $v['landing_url']));
            }
            unset($v);

            $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'subs');

            $oIcon = $this->categoryIcon(0);
            foreach ($aData as &$v) {
                $v['icon'] = $oIcon->url($v['id'], $v['icon'], $iconSize);
            }
            unset($v);
        }

        return $aData;
    }

    /**
     * Страница для покупки и продления платных пакетов (услуга платного расширения лимитов)
     * @return string
     */
    public function limits_payed()
    {
        $userID = User::id();
        if ( ! $userID ||  ! static::limitsPayedEnabled()) {
            $this->errors->error404();
        }
        $point = $this->input->get('point', TYPE_UINT);   # ID поинта
        $shop = $this->input->get('shop', TYPE_UINT);
        # для продления
        $extend = $this->input->get('extend', TYPE_UINT); # флаг продления
        $id = $this->input->get('id', TYPE_UINT);         # ID поинта, который продлеваем
        if ($shop) {
            $shopID = User::shopID();
            if ( ! $shopID) {
                $this->errors->error404();
            }
        }

        # на сколько дней продлеваем, 0 - бессрочно
        $term = config::get('bbs_limits_payed_days', 0, TYPE_UINT);
        if ($id && $extend && $term) {
            $limit = $this->model->limitsPayedUserByFilter(array(
                'id'      => $id,
                'user_id' => $userID,
                'active'  => 1,
            ), array('cat_id', 'shop', 'expire', 'items'));
            if (empty($limit)) {
                $this->errors->error404();
            }
            $point = $limit['cat_id'];
        }

        # регион пользователя, для расчета стоимости
        $region = $this->model->limitsPayedUserRegion($userID);
        $data = $this->model->limitsPayedPointForCategory($point, $shop, array(), $region); # данные о поинте,
        if ( ! isset($data['point']) || $data['point'] != $point) {
            $this->errors->error404();
        }
        $data['extend'] = $extend;
        $data['term'] = $term;
        if (isset($limit['items'])) {
            # нельзя продлить лимит, полученный при дроблении из админ. панели
            $price = false;
            foreach ($data['settings'] as $v) {
                if ($v['items'] == $limit['items']) {
                    $price = $v['price'];
                    break;
                }
            }
            if ( ! $price) {
                $this->errors->error404();
            }
        }

        $userBalance = $this->security->getUserBalance();
        $paySystems = Bills::getPaySystems($userID > 0, true);

        if (Request::isPOST()) {
            # сколько покупаем объявлений (штук)
            $items = $this->input->getpost('items', TYPE_UINT);
            # платежная система
            $ps = $this->input->getpost('ps', TYPE_STR);

            if (!$ps || !array_key_exists($ps, $paySystems)) {
                $ps = key($aPaySystems);
            }
            $paySystem = $paySystems[$ps]['id'];
            $paySystemWay = $paySystems[$ps]['way'];

            $response = array();
            do {

                # определим стоимость
                $price = false;
                foreach ($data['settings'] as $v) {
                    if ($v['items'] == $items) {
                        $price = $v['price'];
                        break;
                    }
                }
                if ( ! $price) {
                    $this->errors->reloadPage();
                    break;
                }

                # данные для активации услуги
                $svcSettings = array(
                    'user_id' => $userID,
                    'point'   => $point,
                    'shop'    => $shop,
                    'items'   => $items,
                    'price'   => $price,
                    'free_id' => $data['free_id'],
                    'paid_id' => $data['paid_id'],
                    'callback' => 'bindBillID',
                );
                if ($extend) {
                    $svcSettings['extend'] = 1;
                    $svcSettings['id'] = $id;
                }

                # конвертируем сумму в валюту для оплаты по курсу
                $pay = Bills::getPayAmount($price, $ps);
                if ($ps == 'balance' && $userBalance >= $price) {
                    # активируем услугу (списываем со счета пользователя)
                    $response['redirect'] = static::url('my.limits.payed');
                    $response['activated'] = $this->svc()->activate($this->module_name, static::SERVICE_LIMIT, false, 0, $userID, $price, $pay['amount'], $svcSettings);
                } else {
                    # создаем счет для оплаты
                    $nBillID = $this->bills()->createBill_InPay($userID, $userBalance,
                        $price,
                        $pay['amount'],
                        $pay['currency'],
                        Bills::STATUS_WAITING,
                        $paySystem, $paySystemWay,
                        _t('bills', 'Пополнение счета через [system]', array('system' => $this->bills()->getPaySystemTitle($paySystem))),
                        static::SERVICE_LIMIT, true, # помечаем необходимость активации услуги сразу после оплаты
                        0, $svcSettings
                    );
                    if (!$nBillID) {
                        $this->errors->set(_t('bills', 'Ошибка создания счета'));
                        break;
                    }
                    $response['pay'] = true;
                    # формируем форму оплаты для системы оплаты
                    $response['form'] = $this->bills()->buildPayRequestForm($paySystem, $paySystemWay, $nBillID, $pay['amount']);
                }
            } while (false);
            $this->ajaxResponseForm($response);
        }

        # разделим стоимость одного объявления и пакета (для верстки)
        foreach ($data['settings'] as $k => $v) {
            if ($v['items'] == 1 && $v['price'] && $v['checked']) {
                $data['single'] = $v;
            }
            if ( ! $v['checked'] || $v['price'] == 0 || $v['items'] == 1) {
                unset($data['settings'][$k]);
            }
        }

        if ($extend) {
            # при продлении удалим лишнее
            if ($limit['items'] == 1) {
                $data['settings'] = array();
            } else {
                unset($data['single']);
                foreach ($data['settings'] as $k => $v) {
                    if ($v['items'] != $limit['items']) {
                        unset($data['settings'][$k]);
                    }
                }
            }
            $data['id'] = $id;
            $data['expire'] = strtotime('+'.$term.'days', strtotime($limit['expire']));
        }

        # заголовок для поинта
        $data['title'] = $this->limitsPayedCatTitle($point);

        $data['ps'] = & $paySystems;
        reset($paySystems);
        $data['ps_active_key'] = key($paySystems);
        foreach ($paySystems as $k => &$v) {
            $v['active'] = ($k == $data['ps_active_key']);
        } unset($v);
        $data['user_balance'] = & $userBalance;

        return $this->viewPHP($data, 'limits.payed.buy');
    }

    public function ajax()
    {
        $nUserID = User::id();
        $aResponse = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            # форма добавления/редактирования (в зависимости от настроек категорий)
            case 'item-form-cat':
            {
                $nCategoryID = $this->input->post('id', TYPE_UINT);
                $aResponse['id'] = $nCategoryID;

                $aData = $this->itemFormByCategory($nCategoryID);
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                } else {
                    if ($aData['types'] === false) {
                        $aData['types'] = array();
                    }
                    $aData['types'] = HTML::selectOptions($aData['types'], 0, false, 'id', 'title');
                }
                $aResponse = array_merge($aData, $aResponse);

            }
            break;
            # стоимость услуг для формы добавления/редактирования
            case 'item-form-svc-prices':
            {
                if ( ! bff::servicesEnabled()) {
                    $aResponse['prices'] = array();
                    break;
                }
                $nCategoryID = $this->input->post('cat', TYPE_UINT);
                $nCityID = $this->input->post('city', TYPE_UINT);

                $aSvcData = $this->model->svcData();
                $aSvcPrices = $this->model->svcPricesEx(array_keys($aSvcData), $nCategoryID, $nCityID);
                foreach ($aSvcData as $k => $v) {
                    if (empty($aSvcPrices[$k]) || $aSvcPrices[$k] <= 0) {
                        $aSvcPrices[$k] = $v['price'];
                    }
                }
                $aResponse['prices'] = $aSvcPrices;
            }
            break;
            # дин. свойства: child-свойства
            case 'dp-child':
            {
                $p = $this->input->postm(array(
                        'dp_id'       => TYPE_UINT, # ID parent-дин.свойства
                        'dp_value'    => TYPE_UINT, # ID выбранного значения parent-дин.свойства
                        'name_prefix' => TYPE_NOTAGS, # Префикс для name
                        'search'      => TYPE_BOOL, # true - форма поиска ОБ, false - форма доб/ред ОБ
                        'format'      => TYPE_STR, # требуемый формат: 'f-desktop', 'f-phone', ''
                    )
                );

                if (empty($p['dp_id']) && empty($p['dp_value'])) {
                    $this->errors->impossible();
                } else {
                    $bFilter = (!empty($p['format']));
                    $aData = $this->dp()->formChildByParentIDValue($p['dp_id'], $p['dp_value'], array('name' => $p['name_prefix'], 'class' => 'form-control'), $p['search'], ($bFilter ? false : 'form.child'));
                    if ($bFilter && !empty($aData)) {
                        switch ($p['format']) {
                            case 'f-desktop':
                            case 'f-phone':
                            {
                                $aResponse = array(
                                    'id'    => $aData['id'],
                                    'df'    => $aData['data_field'],
                                    'multi' => $aData['multi']
                                );
                            }
                            break;
                        }
                    } else {
                        $aResponse['form'] = $aData;
                    }
                }
            }
            break;
            # просмотр контактов объявления
            case 'item-contacts':
            {
                $nItemID = $this->input->post('id', TYPE_UINT);

                if (!$nItemID || !$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                $aData = $this->model->itemData($nItemID,
                    array('user_id', 'shop_id', 'status', 'moderated', 'views_today', 'phones', 'contacts')
                );
                # неверно указан ID объявления
                if (empty($aData)) {
                    $this->errors->reloadPage();
                }
                # объявление не опубликовано / удалено
                $isOwner = User::isCurrent($aData['user_id']);
                $isModeration = static::moderationUrlKey($nItemID, $this->input->post('mod', TYPE_STR));
                if (($aData['status'] != self::STATUS_PUBLICATED && !($isModeration || $isOwner)) || $aData['status'] == self::STATUS_DELETED) {
                    $this->errors->reloadPage();
                }
                # объявление непромодерировано (при включенной "премодерации")
                if (!$aData['moderated'] && static::premoderation() && !($isModeration || $isOwner)) {
                    $this->errors->reloadPage();
                }
                # накручиваем статистику для всех кроме владельца объявления и модератора
                if (!$isModeration && !$isOwner) {
                    $this->model->itemViewsIncrement($nItemID, 'contacts', $aData['views_today']);
                }
                # подставляем контактные данные из профиля
                if ($this->getItemContactsFromProfile())
                {
                    if ($aData['shop_id']) {
                        $contactsData = Shops::model()->shopData($aData['shop_id'], array('phones','contacts'));
                    } else {
                        $contactsData = Users::model()->userData($aData['user_id'], array('phones','contacts'));
                    }
                    $aData['phones'] = $contactsData['phones'];
                    $aData['contacts'] = $contactsData['contacts'];
                }
                # + телефон регистрации
                if (Users::registerPhoneContacts() && $aData['phone_number'] && $aData['phone_number_verified']) {
                    array_unshift($aData['phones'], array('v'=>$aData['phone_number']));
                }

                $aResponse['phones'] = Users::phonesView($aData['phones']);

                if (!empty($aData['contacts'])) {
                    foreach (Users::contactsFields($aData['contacts']) as $contact) {
                        $aResponse['contacts'][$contact['key']] = (isset($contact['view'])
                            ? tpl::renderMacro($contact['value'], $contact['view'], 'value')
                            : HTML::obfuscate($contact['value']));
                    }
                }
            }
            break;
            # избранные объявления (добавление / удаление) - для авторизованных
            # для неавторизованных - процесс выполняется средствами javascript+cookie
            case 'item-fav':
            {
                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID || !$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                $nFavoritesTotal = $this->getFavorites($nUserID, true);
                if ($nFavoritesTotal > 0 && $this->isFavorite($nItemID, $nUserID)) {
                    $this->model->itemsFavDelete($nUserID, $nItemID);
                    $aResponse['added'] = false;
                } else {
                    $nFavoritesLimit = config::sysAdmin('bbs.fav.limit', 200, TYPE_UINT);
                    if ($nFavoritesTotal >= $nFavoritesLimit) {
                        $this->errors(_t('bbs', 'Достигнут лимит количества объявлений в избранном'));
                        break;
                    }
                    $this->model->itemsFavSave($nUserID, array($nItemID));
                    $aResponse['added'] = true;
                }
                $aResponse['cnt'] = $this->getFavorites($nUserID, true);

                # актулизируем счетчик избранных ОБ пользователя
                User::counterSave('items_fav', $aResponse['cnt']);
            }
            break;
            # смена статуса объявления
            case 'item-status':
            {
                $nItemID = $this->input->postget('id', TYPE_UINT);
                $bFrom = $this->input->postget('form', TYPE_BOOL);
                if ($bFrom) {
                    $this->security->setTokenPrefix('bbs-item-form');
                }
                if (!$nItemID || !$nUserID || !$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                $aData = $this->model->itemData($nItemID, array(
                        'user_id',
                        'shop_id',
                        'status',
                        'publicated_to',
                        'publicated_order',
                        'publicated_period',
                        'cat_id',
                        'shop_id'
                    )
                );
                if (empty($aData)) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$this->isItemOwner($nItemID, $aData['user_id'])) {
                    $this->errors->set(_t('bbs', 'Вы не является владельцем данного объявления'));
                    break;
                }

                switch ($this->input->getpost('status', TYPE_STR)) {
                    case 'unpublicate':
                    { # снятие с публикации

                        if ($aData['status'] != self::STATUS_PUBLICATED) {
                            $this->errors->reloadPage();
                            break;
                        }
                        $res = $this->model->itemSave($nItemID, array(
                                'status'        => self::STATUS_PUBLICATED_OUT,
                                'status_prev'   => $aData['status'],
                                'publicated_to' => $this->db->now(),
                            )
                        );
                        if (empty($res)) $this->errors->reloadPage();
                        else {
                            $aResponse['message'] = _t('bbs', 'Объявления было успешно снято с публикации');
                        }
                    }
                    break;
                    case 'publicate':
                    { # публикация
                        if ($aData['status'] != self::STATUS_PUBLICATED_OUT) {
                            $this->errors->reloadPage();
                            break;
                        }

                        if ($aData['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled()) {
                            # проверим превышение лимита лимитирования по абонементу
                            if (Shops::i()->abonementLimitExceed($aData['shop_id'])) {
                                $this->errors->set(_t('bbs', 'Превышение лимита тарифного плана. <a [link]>Изменить тариф</a>.', array('link'=>'href="'.Users::url('my.settings', array('t'=>'abonement')).'"')));
                                break;
                            }
                        } else if (static::limitsPayedEnabled()) {
                            # проверим превышение лимита
                            $limit = $this->model->limitsPayedCategoriesForUser(array(
                                'user_id' => $nUserID,
                                'shop_id' => $aData['shop_id'],
                                'cat_id'  => $aData['cat_id'],
                            ));
                            if ( ! empty($limit)) {
                                $limit = reset($limit);
                                if ($limit['cnt'] >= $limit['limit']) {
                                    $this->errors->set(_t('bbs', 'Вы достигли лимита публикаций: [title].',
                                        array('title' => $this->limitsPayedCatTitle($limit['point']))));
                                    break;
                                }
                            }
                        }

                        $aUpdate = array(
                            'status'        => self::STATUS_PUBLICATED,
                            'status_prev'   => $aData['status'],
                            'publicated'    => $this->db->now(),
                            'publicated_to' => $this->getItemPublicationPeriod(), # от текущей даты
                        );
                        /**
                         * Обновляем порядок публикации (поднимаем наверх)
                         * только в случае если разница между датой publicated_order и текущей более X дней
                         * т.е. тем самым закрываем возможность бесплатного поднятия за счет
                         * процедуры снятия с публикации => возобновления публикации (продления)
                         */
                        $topupTimeout = config::sysAdmin('bbs.publicate.topup.timeout', 7, TYPE_UINT);
                        if ($topupTimeout > 0 && (time() - strtotime($aData['publicated_order'])) >= (86400 * $topupTimeout)) {
                            $aUpdate['publicated_order'] = $this->db->now();
                        }
                        $res = $this->model->itemSave($nItemID, $aUpdate);
                        if (empty($res)) $this->errors->reloadPage();
                        else {
                            $aResponse['message'] = _t('bbs', 'Объявления было успешно опубликовано');
                        }
                    }
                    break;
                    case 'refresh':
                    { # продление публикации
                        if ($aData['status'] != self::STATUS_PUBLICATED) {
                            $this->errors->reloadPage();
                            break;
                        }

                        if ($aData['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled()) {
                            # проверим превышение лимита лимитирования по абонементу
                            if (Shops::i()->abonementLimitExceed($aData['shop_id'])) {
                                $this->errors->set(_t('bbs', 'Превышение лимита тарифного плана. <a [link]>Изменить тариф</a>.', array('link'=>'href="'.Users::url('my.settings', array('t'=>'abonement')).'"')));
                                break;
                            }
                        } else if (static::limitsPayedEnabled()) {
                            # проверим превышение лимита
                            $limit = $this->model->limitsPayedCategoriesForUser(array(
                                'user_id' => $nUserID,
                                'shop_id' => $aData['shop_id'],
                                'cat_id'  => $aData['cat_id'],
                            ));
                            if ( ! empty($limit)) {
                                $limit = reset($limit);
                                if (empty($limit['limit']) || $limit['cnt'] > $limit['limit']) {
                                    $this->errors->set(_t('bbs', 'Вы достигли лимита публикаций: [title].',
                                        array('title' => $this->limitsPayedCatTitle($limit['point']))));
                                    break;
                                }
                            }
                        }

                        # от даты завершения публикации
                        $res = $this->model->itemSave($nItemID, array(
                                'publicated_to' => $this->getItemRefreshPeriod($aData['publicated_to']),
                            )
                        );
                        if (empty($res)) $this->errors->reloadPage();
                        else {
                            $aResponse['message'] = _t('bbs', 'Срок публикации объявления был успешно продлен');
                        }
                    }
                    break;
                    case 'delete': # удаление
                    {

                        $is_soon_left = (strtotime($aData['publicated_to']) - time()) < static::PUBLICATION_SOON_LEFT;
                        if ($aData['status'] == self::STATUS_PUBLICATED && ! $is_soon_left) {
                            $this->errors->set(_t('bbs', 'Для возможности удаления объявления, необходимо снять его с публикации'));
                            break;
                        }
                        $aResponse['message'] = _t('bbs', 'Объявления было успешно удалено');
                        $aResponse['redirect'] = static::url('my.items');
                        if ($aData['status'] == self::STATUS_DELETED) break;
                        $res = $this->model->itemSave($nItemID, array(
                                # помечаем как удаленное + снимаем с публикации
                                'deleted'       => 1,
                                'status'        => self::STATUS_DELETED,
                                'status_prev'   => $aData['status'],
                                'publicated_to' => $this->db->now(),
                            )
                        );
                        if (empty($res)) {
                            $this->errors->set(_t('bbs', 'Неудалось удалить объявление, возможно данное объявление уже удалено'));
                        }
                    }
                    break;
                }
                # проверим превышение лимита, для вывода алерта в кабинете
                if ($aData['shop_id'] && bff::shopsEnabled() && Shops::abonementEnabled()) {
                    $aResponse['shopAbonement'] = Shops::i()->abonementLimitExceed($aData['shop_id']);
                } else if(static::limitsPayedEnabled()) {
                    $aResponse['limitsPayed'] = $this->model->limitsPayedCategoriesForUser(array(
                        'user_id' => $nUserID,
                        'shop_id' => $aData['shop_id'],
                    ), true);
                }
            }
            break;
            case 'limits-payed-popup': # попап о достигнутых лимитах (услуга платного расширения лимитов)
            {
                $nUserID = User::id();
                if ( ! static::limitsPayedEnabled() || ! $nUserID) {
                    $this->errors->reloadPage();
                    break;
                }
                $shop = $this->input->post('shop', TYPE_UINT);
                $shopID = 0;
                if ($shop) {
                    $shopID = User::shopID();
                }

                # количество объявлений для поинтов у пользователя
                $limits = $this->model->limitsPayedCategoriesForUser(array(
                    'user_id' => $nUserID,
                    'shop_id' => $shopID,
                ));
                if (empty($limits)) {
                    $this->errors->reloadPage();
                    break;
                }
                foreach ($limits as & $v) {
                    # название категории
                    $v += $this->limitsPayedCatTitle($v['point'], false);
                } unset($v);
                uasort($limits, function($a, $b){ return $a['numleft'] > $b['numleft']; });

                $data = array('limits' => $limits, 'shop' => $shop);
                $aResponse['html'] = $this->viewPHP($data, 'limits.payed.popup');
            }
            break;
            case 'item-svc-up-free': # бесплатное понятие объявления
            {
                $itemID = $this->input->post('id', TYPE_UINT);
                if (!$itemID ||
                    !$this->security->validateToken() ||
                    !$this->isItemOwner($itemID)) {
                    $this->errors->reloadPage();
                    break;
                }

                $aResponse['message'] = $this->svcUpFree($itemID);
            }
            break;
            case 'item-svc-up-auto-form':
            {
                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID || !$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                $aData = $this->model->itemData($nItemID, array('id', 'user_id', 'svc_upauto_on AS `on`', 'svc_upauto_sett'));
                if (!$aData || ! static::svcUpAutoEnabled()) {
                    $this->errors->impossible();
                    break;
                }
                if (!$this->isItemOwner($nItemID, $aData['user_id'])) {
                    $this->errors->reloadPage();
                    break;
                }

                $sett = func::unserialize($aData['svc_upauto_sett']);
                $aData = array_merge($aData, $sett);
                $aResponse['html'] = $this->viewPHP($aData, 'item.svc.upauto');
            }
            break;
            case 'item-svc-up-auto-save':
            {
                $nItemID = $this->input->post('id', TYPE_UINT);
                if ( ! $nItemID || ! $this->security->validateToken() || ! static::svcUpAutoEnabled()) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$this->isItemOwner($nItemID)) {
                    $this->errors->reloadPage();
                    break;
                }
                if ($this->svcUpAutoSave($nItemID, $_POST, $on)) {
                    $aResponse['message'] = $on ? _t('bbs', 'Автоматическое поднятие активированно') : _t('bbs', 'Автоматическое поднятие деактивированно');
                    $aResponse['on'] = $on;
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

    /**
     * Сохранение настроек услуги автоматического поднятия
     * @param integer $itemID ID объявления
     * @param array $data данные
     * @param integer $on @ref значение параметра on
     * @return bool|int
     */
    protected function svcUpAutoSave($itemID, $data, & $on = 0)
    {
        $sett = $this->input->clean_array($data, array(
            'on' => TYPE_UINT,
            'p'  => TYPE_UINT,
            't'  => TYPE_UINT,
            'h'  => TYPE_UINT,
            'm'  => TYPE_UINT,
            'fr_h' => TYPE_UINT,
            'fr_m' => TYPE_UINT,
            'to_h' => TYPE_UINT,
            'to_m' => TYPE_UINT,
            'int'  => TYPE_UINT,
        ));
        $on = $sett['on'];
        unset($sett['on']);

        $next = $this->svcUpAutoNext($sett);
        return $this->model->itemSave($itemID, array(
            'svc_upauto_on'   => $on,
            'svc_upauto_sett' => serialize($sett),
            'svc_upauto_next' => date('Y-m-d H:i:s', $next)));
    }

    /**
     * Расчет времени следующего запуска для услуги автоматического поднятия в соответствии с настройками
     * @param array $sett настройки услуги
     * @param int|bool $time время с которого начинать расчет, false - текущее время
     * @return int time
     */
    protected function svcUpAutoNext($sett, $time = false)
    {
        if ( ! $time) {
            $time = time();
        }
        $result = strtotime('+1 month', $time);
        do {
            if (empty($sett['p']) || empty($sett['t'])) break;

            if ($sett['t'] == static::SVC_UP_AUTO_SPECIFIED) {
                # Точно указанное время:
                $hour = empty($sett['h']) ? 0 : $sett['h'];
                if($hour < 0 || $hour > 24) $hour = 0;
                $hz = (string)$hour;
                $hz = strlen($hz) == 1 ? '0'.$hz : $hz;

                $min = empty($sett['m']) ? 0 : $sett['m'];
                $min = round($min / 10) * 10;
                if($min < 0 || $min > 59) $min = 0;
                $mz = (string)$min;
                $mz = strlen($mz) == 1 ? '0'.$mz : $mz;

                $result = strtotime(date('Y-m-d '.$hz.':'.$mz.':00', $time)); # указанное время для сегодня

                switch ($sett['p']) {
                    case 1: # каждый день
                    case 3: # Раз в 3 дня
                    case 7: # Раз в неделю
                        if($result <= $time) { # уже прошло, рассчитаем для запуска через 'p' дней
                            $result = strtotime('+'.$sett['p'].' day', $result);
                        }
                        break 2;
                    case -1: # Каждый будний день
                        $dw = date('w', $result);
                        if ($dw == 0 || $dw == 6 || $result <= $time) { # уже прошло, или сегодня выходной
                            do { # найдем след. рабочий день
                                $result = strtotime('+1 day', $result);
                                $dw = date('w', $result);
                            } while($dw == 0 || $dw == 6);
                        }
                        break 2;
                }

            } elseif ($sett['t'] == static::SVC_UP_AUTO_INTERVAL) {
                # Промежуток времени с-до, с указанием интервала:
                $fr_h = empty($sett['fr_h']) ? 0 : $sett['fr_h'];
                if ($fr_h < 0 || $fr_h > 24) $fr_h = 0;
                $fr_hz = (string)$fr_h;
                $fr_hz = strlen($fr_hz) == 1 ? '0'.$fr_hz : $fr_hz;

                $fr_m = empty($sett['fr_m']) ? 0 : $sett['fr_m'];
                $fr_m = round($fr_m / 10) * 10;
                if ($fr_m < 0 || $fr_m > 59) $fr_m = 0;
                $fr_mz = (string)$fr_m;
                $fr_mz = strlen($fr_mz) == 1 ? '0'.$fr_mz : $fr_mz;

                $to_h = empty($sett['to_h']) ? 0 : $sett['to_h'];
                if ($to_h < 0 || $to_h > 24) $to_h = 0;
                $to_hz = (string)$to_h;
                $to_hz = strlen($to_hz) == 1 ? '0'.$to_hz : $to_hz;

                $to_m = empty($sett['to_m']) ? 0 : $sett['to_m'];
                $to_m = round($to_m / 10) * 10;
                if ($to_m < 0 || $to_m > 59) $to_m = 0;
                $to_mz = (string)$to_m;
                $to_mz = strlen($to_mz) == 1 ? '0'.$to_mz : $to_mz;

                $int = empty($sett['int']) ? 60 : $sett['int'];
                if ($int < 30) $int = 30;

                $result_fr = strtotime(date('Y-m-d '.$fr_hz.':'.$fr_mz.':00', $time)); # указанное время для сегодня "c"
                $result_to = strtotime(date('Y-m-d '.$to_hz.':'.$to_mz.':00', $time)); # указанное время для сегодня "до"

                switch ($sett['p']) {
                    case 1: # каждый день
                    case 3: # Раз в 3 дня
                    case 7: # Раз в неделю
                        if ($result_to < $time) { # уже прошло время "до", рассчитаем для запуска через 'p' дней
                            $result = strtotime('+'.$sett['p'].' day', $result_fr);
                        } else if ($result_fr <= $time) { # уже прошло время "c" но еще не наступило "до"
                            $result = $result_fr;
                            do {
                                $result = strtotime('+'.$int.' minutes', $result);
                                if($time < $result && $result <= $result_to) {
                                    break 3;
                                }
                            } while($result < $result_to);
                            # с учетом указанного интервала сегодня до времени "до" не попадаем, запуска через 'p' дней
                            $result = strtotime('+'.$sett['p'].' day', $result_fr);
                        }else{
                            $result = $result_fr; # еще не наступило сегодня "с"
                        }
                        break 2;
                    case -1: # Каждый будний день
                        $dw = date('w', $result_fr);
                        if ($dw == 0 || $dw == 6) { # если сегодня выходной
                            $result = $result_fr;
                            do{ # найдем след. рабочий день
                                $result = strtotime('+1 day', $result);
                                $dw = date('w', $result);
                            }while($dw == 0 || $dw == 6);
                            break 2;
                        }
                        do {
                            if ($result_to < $time) { # уже прошло время "до", рассчитаем для запуска на завтра
                                $result = strtotime('+1 day', $result_fr);
                            } else if($result_fr <= $time) { # уже прошло время "c" но еще не наступило "до"
                                $result = $result_fr;
                                do{
                                    $result = strtotime('+'.$int.' minutes', $result);
                                    if ($time < $result && $result <= $result_to) {
                                        break 2;
                                    }
                                } while($result < $result_to);
                                # с учетом указанного интервала сегодня до времени "до" не попадаем,  рассчитаем для запуска на завтра
                                $result = strtotime('+1 day', $result_fr);
                            } else {
                                $result = $result_fr; # еще не наступило сегодня "с"
                            }
                        } while(false);
                        $dw = date('w', $result);
                        if ($dw == 0 || $dw == 6) { # если день запуска выходной => след. рабочий день
                            do {
                                $result = strtotime('+1 day', $result);
                                $dw = date('w', $result);
                            } while($dw == 0 || $dw == 6);
                        }
                        break 2;
                }
            } else {
                break;
            }
        } while(false);

        return $result;
    }

    /**
     * Активация услуги бесплатного поднятия объявления
     * @param integer $itemID ID объявления
     * @param bool $silent тихий режим, только поднять, если доступно
     * @param string $svc_up_free дата последнего бесплатного поднятия, если известна
     * @return string сообщение об успешной активации
     */
    protected function svcUpFree($itemID, $silent = false, $svc_up_free = '')
    {
        do{
            $days = static::svcUpFreePeriod();
            if ( ! $days) {
                if ( ! $silent) {
                    $this->errors->reloadPage();
                } else {
                    return false;
                }
                break;
            }

            $upTo = strtotime('-' . $days . ' days');
            if (empty($svc_up_free)) {
                $data = $this->model->itemData($itemID, array('user_id','svc_up_free'));
                if (empty($data)) {
                    if ( ! $silent) {
                        $this->errors->reloadPage();
                    } else {
                        return false;
                    }
                    break;
                }
                $svc_up_free = $data['svc_up_free'];
            }
            if (strtotime($svc_up_free) > $upTo) {
                if ( ! $silent) {
                    $allow = strtotime('+'.$days.' days', strtotime($data['svc_up_free']));
                    $this->errors->set(_t('bbs', 'Возможность бесплатного поднятия для данного объявления будет доступна [date]',
                        array('date' => tpl::dateFormat($allow))));
                } else {
                    return false;
                }
                break;
            }

            $now = $this->db->now();
            $res = $this->model->itemSave($itemID, array(
                'svc_up_free'      => $now,
                'publicated_order' => $now,
                'svc_up_date'      => $now,
            ));
            if (empty($res)) {
                if ( ! $silent) {
                    $this->errors->reloadPage();
                } else {
                    return false;
                }
            } else {
                if ($silent) {
                    return true;
                } else {
                    return _t('bbs', 'Объявление было успешно поднято');
                }
            }
        } while(false);
        if ($silent) {
            return false;
        }
        return '';
    }

    public function itemsCronStatus()
    {
        if (!bff::cron()) return;

        # Актуализация статуса объявлений
        $this->model->itemsCronStatus();

    }

    public function itemsCronUnpublicate()
    {
        if (!bff::cron()) return;

        # 1. Уведомление о скором завершении публикации объявлений
        $this->itemsCronUnpublicateSoon();

        # 2. Полное удаление объявлений
        $this->model->itemsCronDelete();

    }

    public function itemsCronViews()
    {
        if (!bff::cron()) return;

        $this->model->itemsCronViews();

        $this->model->itemsCountersCalculate();
        $this->model->itemsCountersCalculateVirtual();
    }

    /**
     * Запуск конкретного задания импорта
     * @param $params
     */
    public function itemsCronImportOnce($params)
    {
        if (!bff::cron()) return;

        $this->itemsImport()->importTaskOnce($params);
    }

    /**
     * Помечаем снятые с публикации объявления как удаленные для неактивных аккаунтов пользователей.
     * раз в сутки
     */
    public function itemsCronDeleteInactiveUsers()
    {
        if (!bff::cron()) return;

        $days = config::sysAdmin('bbs.delete.inactive.users.timeout', 0, TYPE_UINT);
        if (!$days) return;
        
        $this->model->itemsCronDeleteInactiveUsers($days);
    }

    public function itemsCronVirtualCategories()
    {
        if (!bff::cron()) return;

        $this->model->itemsCountersCalculateVirtual();
    }

    protected function itemsCronUnpublicateSoon()
    {
        if (!bff::cron())
            return;

        $days = func::unserialize(config::get('bbs_item_unpublicated_soon'));
        # уведомления были выключены в настройках
        if (empty($days)) {
            return;
        }
        # кол-во отправляемых объявлений за подход
        $limit = config::sysAdmin('bbs.items.unpublicated.soon.limit', 100, TYPE_UINT);
        if ($limit<=0) $limit = 100;
        if ($limit>300) $limit = 300;
        $now = date('Y-m-d');

        # очистка списка отправленных за предыдущие дни
        $last = config::get('bbs_item_unpublicated_soon_last_enotify');
        if ($last != $now) {
            config::save('bbs_item_unpublicated_soon_last_enotify', $now);
            $this->model->itemsCronUnpublicateClearLast($last);
        }

        # получаем пользователей у которых есть одно+ объявление у которого завершается срок публикации,
        # до завершения осталось {$days} дней (варианты)
        $users = $this->model->itemsCronUnpublicateSoon($days, $limit, $now);
        if (empty($users))
            return;

        $services = array(
            'up'      => static::SERVICE_UP,
            'quick'   => static::SERVICE_QUICK,
            'fix'     => static::SERVICE_FIX,
            'mark'    => static::SERVICE_MARK,
            'press'   => static::SERVICE_PRESS,
            'premium' => static::SERVICE_PREMIUM,
        );
        $packs = Svc::model()->svcListing(Svc::TYPE_SERVICEPACK, $this->module_name);
        foreach ($packs as $k => $v) {
            if (empty($v['keyword']) || empty($v['on'])) {
                unset($packs[$k]);
            }
        }

        foreach ($users as &$v) {
            $this->locale->setCurrentLanguage($v['lang'], true);
            $v['days_in'] = tpl::declension($v['days'], _t('', 'день;дня;дней'));
            $loginAuto = Users::loginAutoHash($v);

            if ($v['cnt'] == 1) {
                # у пользователя всего одно объявление
                # помечаем в таблице отправленных за сегодня (если еще нет)
                if ($this->model->itemsCronUnpublicateSended($v['item_id'], $now))
                    continue;

                $v['item_link'] = static::urlDynamic($v['item_link']);
                $v['publicate_link'] = $v['item_link'].'?alogin='.$loginAuto;
                $v['edit_link'] = static::url('item.edit', array('id' => $v['item_id'], 'alogin' => $loginAuto));
                foreach ($services as $kk=>$vv) {
                    $v['svc_'.$kk] = static::url('item.promote', array('id' => $v['item_id'], 'alogin' => $loginAuto, 'svc' => $vv));
                }
                if ( ! empty($packs)) {
                    foreach ($packs as $vv) {
                        $v['pack_'.$vv['keyword']] = static::url('item.promote', array('id' => $v['item_id'], 'alogin' => $loginAuto, 'svc' => $vv['id']));
                    }
                }

                bff::sendMailTemplate($v, 'bbs_item_unpublicated_soon', $v['email'], false, '', '', $v['lang']);
            } else {
                $v['items'] = explode(',', $v['items']);
                if ($this->model->itemsCronUnpublicateSended($v['items'], $now))
                    continue;

                $v['count'] = $v['cnt'];
                $v['count_items'] = tpl::declension($v['cnt'], _t('bbs', 'объявление;объявления;объявлений'));
                $v['publicate_link'] = static::url('my.items', array(
                    'day' => date('Y-m-d', strtotime('+'.$v['days'].'days')),
                    'act' => 'email-publicate',
                    'alogin' => $loginAuto,
                ));

                bff::sendMailTemplate($v, 'bbs_item_unpublicated_soon_group', $v['email'], false, '', '', $v['lang']);
            }
        } unset($v);
    }

    /**
     * Отправка почтовых уведомлений о возможности бесплатного поднятия объявлений
     */
    public function itemsCronUpFreeEnable()
    {
        if (!bff::cron())
            return;

        $days = static::svcUpFreePeriod();
        if ( ! $days) {
            return; # бесплатные поднятия отключены
        }
        # кол-во отправляемых объявлений за подход
        $limit = 100;
        $now = date('Y-m-d');

        # очистка списка отправленных за предыдущие дни
        $last = config::get('bbs_item_up_free_enable_last_enotify');
        if ($last != $now) {
            config::save('bbs_item_up_free_enable_last_enotify', $now);
            $this->model->itemsCronUpFreeEnableClear($last);
        }

        # получаем пользователей у которых есть одно+ объявление с доступным бесплатным поднятием,
        $users = $this->model->itemsCronUpFreeEnable($days, $limit);
        if (empty($users))
            return;

        foreach ($users as &$v) {
            $this->locale->setCurrentLanguage($v['lang'], true);
            $loginAuto = Users::loginAutoHash($v);

            if ($v['cnt'] == 1) {
                # у пользователя всего одно объявление
                # помечаем в таблице отправленных за сегодня (если еще нет)
                if ($this->model->itemsCronUpFreeEnableSended($v['item_id'], $now))
                    continue;

                $v['item_link'] = static::urlDynamic($v['item_link']);
                $v['item_link_up'] = $v['item_link'].'?up_free=1&alogin='.$loginAuto;

                bff::sendMailTemplate($v, 'bbs_item_upfree', $v['email'], false, '', '', $v['lang']);
            } else {
                $v['items'] = explode(',', $v['items']);
                if ($this->model->itemsCronUpFreeEnableSended($v['items'], $now))
                    continue;

                $v['count'] = $v['cnt'];
                $v['count_items'] = tpl::declension($v['cnt'], _t('bbs', 'объявление;объявления;объявлений'));
                $v['items_link_up'] = static::url('my.items', array(
                    'act' => 'email-up-free',
                    'alogin' => $loginAuto,
                ));

                bff::sendMailTemplate($v, 'bbs_item_upfree_group', $v['email'], false, '', '', $v['lang']);
            }
        } unset($v);
    }

    /**
     * Импорт объявлений (cron)
     * Рекомендуемый период: раз в 7 минут
     */
    public function itemsCronImport()
    {
        if (!bff::cron()) return;

        $this->itemsImport()->importCron();
    }

    /**
     * Автоматическое поднятие ОБ, исходя из настроек услуги (cron)
     * Рекомендуемый ериод: раз в 10 минут
     */
    public function itemsCronUpAuto()
    {
        if ( ! bff::cron()) {
            return;
        }


        if ( ! static::svcUpAutoEnabled() || ! bff::servicesEnabled()) {
            return;
        }

        $svc = Svc::model()->svcData(static::SERVICE_UP);
        $price = $svc['price'];

        $freeDays = static::svcUpFreePeriod();

        $this->model->itemsCronUpAutoData(function ($item) use ($price, $freeDays) {
            $sett = func::unserialize($item['svc_upauto_sett']);
            do {
                # проверяем позицию объявления в главной категории
                if ($this->model->itemPositionInCategory($item['id'], $item['cat_id1']) == 1) {
                    break;
                }

                # проверка бесплатного поднятия
                if ($freeDays) {
                    if ($this->svcUpFree($item['id'], true, $item['svc_up_free'])) {
                        # удалось поднять бесплатно
                        break;
                    }
                }

                $prices = $this->model->svcPricesEx(array(static::SERVICE_UP), $item['cat_id'], $item['city_id']);
                if (!empty($prices)) {
                    $price = reset($prices);
                }

                # проверяем достаточно ли средств на счету для активации услуги
                $balance = Users::model()->userBalance($item['user_id']);
                if ($balance < $price) {
                    if (config::sysAdmin('bbs.svc.upauto.nomoney.off', false, TYPE_BOOL)) {
                        # не достаточно средств - отключим услугу автоподнятия
                        $this->model->itemSave($item['id'], array('svc_upauto_on' => 0));
                    }
                    break;
                }
                Svc::i()->activate($this->module_name, static::SERVICE_UP, false, $item['id'], $item['user_id']);

            } while (false);

            # рассчитаем время следующего запуска
            $next = $this->svcUpAutoNext($sett);
            $this->model->itemSave($item['id'], array('svc_upauto_next' => date('Y-m-d H:i:s', $next)));
        });
    }

    /**
     * Формирование файла для выгрузки в Яндекс.Маркет
     */
    public function itemsCronYandexMarket()
    {
        if (!bff::cron()) return;

        BBSYandexMarket::i()->generate();
    }

    /**
     * Перемещение объявлений в подкатегории. Вызывается из cron-manager
     */
    public function itemsCatsRebuild()
    {
        if (!bff::cron())
            return;
        $this->model->itemsCatsRebuild();
        if( ! $this->errors->no()){
            $errors = $this->errors->get();
            bff::log($errors);
        }
    }

    /**
     * Обновление ссылок объявлений. Вызывается из cron-manager
     */
    public function itemsLinksRebuild()
    {
        if (!bff::cron())
            return;
        $this->model->itemsLinksRebuild();
        if( ! $this->errors->no()){
            $errors = $this->errors->get();
            bff::log($errors);
        }
    }

    /**
     * Загрузка файла Яндекс.Маркетом
     */
    function yml()
    {
        BBSYandexMarket::i()->download();
    }

    /**
     * Расписание запуска крон задач
     * @return array
     */
    public function cronSettings()
    {

        return array(
            'itemsCronStatus' => array('period' => '*/10 * * * *'),
            'itemsCronUnpublicate' => array('period' => '0 0,12 * * *'),
            'itemsCronViews'  => array('period' => '0 0 * * *'),
            'itemsCronDeleteInactiveUsers'  => array('period' => '30 0 * * *'),
            'itemsCronVirtualCategories' => array('period' => '* * * * *'),
            'itemsCronImport' => array('period' => '*/10 * * * *'),
            'itemsCronUpFreeEnable' => array('period' => '*/30 * * * *'),
            'itemsCronUpAuto' => array('period' => '*/10 * * * *'),
            'itemsCronYandexMarket'  => array('period' => '0 0 * * *'),
        );
    }

    /**
     * RSS Лента
     */
    function rss()
    {
        $category = $this->input->get('cat', TYPE_UINT);
        $region = $this->input->get('region', TYPE_UINT);
        $lng = LNG;

        if ( ! static::rssEnabled() || ! $category) {
            $this->errors->error404();
        }
        # Формируем + кешируем
        $minutes = config::sys('bbs.rss.cache', 15, TYPE_UINT);
        if ($minutes <= 0) {
            $minutes = 15;
        }
        $cache = Cache::singleton($this->module_name, 'file', array('lifeTime'=>($minutes*60))); # кешируем на Х минут
        $cacheKey = 'rss-'.$category.'-'.$region.'-'.$lng;
        if (($data = $cache->get($cacheKey)) === false) {
            $data = $this->rssGenerator($category, $region, $lng);
            $cache->set($cacheKey, $data);
        }

        header('Content-Type: application/rss+xml; charset=UTF-8');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

        echo $data;
        bff::shutdown();
    }

    /**
     * Формируем RSS ленту
     * @param integer $categoryID ID Категории
     * @param integer $region ID региона
     * @param string $lng Ключ языка
     * @return string
     */
    protected function rssGenerator($categoryID, $region, $lng)
    {
        $sql = array(
            'is_publicated' => 1,
            'status' => static::STATUS_PUBLICATED,
        );
        if ($categoryID > 0) {
            $catData = $this->model->catData($categoryID, array('enabled'));
            if (empty($catData['enabled'])) {
                $this->errors->error404();
            }
            $sql[':cat-filter'] = $categoryID;
        }
        if ($region) {
            $sql[':region-filter'] = $region;
        }
        $limit = config::sys('bbs.rss.limit', 30, TYPE_UINT);
        if ($limit <= 0) {
            $limit = 30;
        }

        $fields = array('I.descr');
        for ($i = static::CATS_MAXDEEP; $i>0; $i--) {
            $fields[] = 'I.cat_id'.$i;
        }

        $data = $this->model->itemsList($sql, false, array(
            'context' => 'rss',
            'orderBy' => 'publicated_order DESC',
            'limit' => $limit,
            'favs' => false,
            'lang' => $lng,
            'fields' => $fields,
        ));

        $items = array();
        if ( ! empty($data)) {
            $cats = array();
            foreach ($data as $v) {
                for ($i = static::CATS_MAXDEEP; $i > 0; $i--) {
                    if ($v['cat_id'.$i] && !in_array($v['cat_id'.$i], $cats)) {
                        $cats[] = $v['cat_id'.$i];
                    }
                }
            }
            $cats = $this->model->catsDataByFilter(array('id' => $cats, 'lang' => $lng), array('id', 'pid', 'title'));
            $cats = func::array_transparent($cats, 'id', true);
            foreach ($data as $v) {
                $catTitles = array();
                for ($i = 1; $i <= static::CATS_MAXDEEP; $i++) {
                    if ($v['cat_id'.$i] && isset($cats[ $v['cat_id'.$i] ])) {
                        $catTitles[] = $cats[ $v['cat_id'.$i] ]['title'];
                    }
                }
                $items[] = '<item>
                    <title>'.strip_tags($v['title']).'</title>
                    <link>'.$v['link'].'</link>
                    <description>'.htmlspecialchars($v['descr']).'</description>
                    <guid>'.$v['link'].'</guid>
                    <category>'.strip_tags(join(' / ', $catTitles)).'</category>
                    <pubDate>'.date('D, j M Y G:i:s O', strtotime($v['publicated'])).'</pubDate>
                </item>';
            }                
        }

        $title = _t('bbs', 'Объявления на [site]', array('site' => Site::title('bbs.rss')));
        if ($categoryID > 0) {
            $catTitles = array();
            $parents = $this->model->catParentsData($categoryID);
            foreach ($parents as $v) $catTitles[] = $v['title'];
            $title .= ' ('.join(' / ', $catTitles).')';
        }

        $lang = $this->locale->getLanguageSettings($lng, 'locale');
        $lang = str_replace('_', '-', mb_strtolower($lang));

        $description = '<title>'.$title.'</title>
                       <link>'.SITEURL.'</link>
                       <description>'.$title.'</description>
                       <language>'.$lang.'</language>
                       <pubDate>'.date('D, j M Y G:i:s O').'</pubDate>
                       <lastBuildDate>'.date('D, j M Y G:i:s O').'</lastBuildDate>
                       <image>
                        <url>'.Site::logoURL('bbs.rss').'</url>
                        <title>'.$title.'</title>
                        <link>'.SITEURL.'</link>
                       </image>';

        return strtr('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>{description}{items}</channel></rss>',
            array(
                '{description}' => $description,
                '{items}' => join('', $items)
            )
        );
    }

    /**
     * Комментарии ОБ
     * @param array $aData
     * @return mixed
     */
    public function comments(array $aData = array())
    {
        $nUserID = User::id();
        $oComments = $this->itemComments();

        if (Request::isAJAX())
        {
            $aResponse = array();
            switch ($this->input->getpost('act', TYPE_STR)) {
                case 'add': # комментарии: добавление
                {
                    if (!$this->security->validateToken(false)) {
                        $this->errors->reloadPage();
                        break;
                    }


                    $sMessage = $this->input->post('message', TYPE_NOTAGS);
                    $sMessage = $oComments->validateMessage($sMessage, false);
                    if (mb_strlen($sMessage) < 5) {
                        $this->errors->set(_t('comments', 'Комментарий не может быть короче 5 символов'), 'message');
                        break;
                    }

                    $nItemID = $this->input->post('item_id', TYPE_UINT);
                    if (!$nItemID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $aItemData = $this->model->itemData($nItemID, array('status', 'user_id'));
                    if (empty($aItemData)) {
                        $this->errors->reloadPage();
                    }
                    if ($aItemData['status'] != static::STATUS_PUBLICATED) {
                        $this->errors->impossible();
                        break;
                    }

                    # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                    if (Site::i()->preventSpam('bbs-comment', 10)) {
                        break;
                    }

                    # ID комментария на который отвечаем или 0
                    $nParent = $this->input->post('parent', TYPE_UINT);
                    $aData = array(
                        'message' => $sMessage,
                        'name' => User::data('name'),
                    );
                    $nCommentID = $oComments->commentInsert($nItemID, $aData, $nParent);
                    if ($nCommentID) {
                        # оставили комментарий
                        $aResponse['premod'] = $oComments->isPreModeration();
                        if (!$aResponse['premod']) {
                            $aComments = $oComments->commentsDataFrontend($nItemID, $nCommentID);
                            $aResponse['html'] = $this->commentsList($aComments['comments'], array(
                                'itemID'     => $nItemID,
                                'itemUserID' => $aItemData['user_id'],
                                'itemStatus' => $aItemData['status'],
                            ), (!$nParent ? 1 : 2));
                        }
                    }
                }   break;
                case 'delete': # комментарии: удаление
                {
                    if (!$this->security->validateToken(false, true)) {
                        $this->errors->reloadPage();
                        break;
                    }


                    $nCommentID = $this->input->post('id', TYPE_UINT);
                    if (!$nCommentID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $nItemID = $this->input->post('item_id', TYPE_UINT);
                    if (!$nItemID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $aItemData = $this->model->itemData($nItemID, array('status', 'user_id'));
                    if (empty($aItemData)) {
                        $this->errors->reloadPage();
                        break;
                    }

                    if ($aItemData['status'] != static::STATUS_PUBLICATED) {
                        $this->errors->impossible();
                        break;
                    }

                    $aCommentData = $oComments->commentData($nItemID, $nCommentID);
                    if (empty($aCommentData)) {
                        $this->errors->reloadPage();
                        break;
                    }
                    if ($aCommentData['user_id'] == $nUserID) { # владелец комментария
                        $oComments->commentDelete($nItemID, $nCommentID, BBSItemComments::commentDeletedByCommentOwner);
                    }else{
                        $this->errors->reloadPage();
                        break;
                    }

                    $aCommentsData = $oComments->commentsDataFrontend($nItemID, $nCommentID);
                    if ($aCommentsData['total'] > 0) {
                        $aResponse['html'] = $this->commentsList($aCommentsData['comments'], array(
                            'itemID'     => $nItemID,
                            'itemUserID' => $aItemData['user_id'],
                            'itemStatus' => $aItemData['status'],
                        ));
                    } else {
                        $aResponse['html'] = '';
                    }
                }  break;
                default:
                    $this->errors->impossible();
            }
            $this->ajaxResponseForm($aResponse);
        }

        $aCommentsData = $oComments->commentsDataFrontend($aData['itemID']);
        $aData['comments'] = $this->commentsList($aCommentsData['comments'], array(
            'itemID'     => $aData['itemID'],
            'itemUserID' => $aData['itemUserID'],
            'itemStatus' => $aData['itemStatus'],
        ));
        $aData['commentsTotal'] = $aCommentsData['total'];
        $aData['userID'] = $nUserID;
        return $this->viewPHP($aData, 'item.comments');
    }

    /**
     * Вывод списка комментариев, 1-2 уровень вложенности
     * @param array $comments комментариии
     * @param array $data данные об объявлении
     * @param integer $level уровень комментариев
     * @return string
     */
    public function commentsList(array $comments, array $data, $level = 1)
    {
        $data['comments'] = &$comments;
        $data['level'] = $level;
        $data['userID'] = User::id();
        $data['perPage'] = config::sysAdmin('bbs.comments.collapse'.($level > 1?'.answers':''), 10, TYPE_UINT);
        # Добавление комментариев доступно для объявлений в статусе "опубликованы"
        $data['allowAdd'] = ($data['itemStatus'] == static::STATUS_PUBLICATED && $data['userID']);
        $data['hideReasons'] = $this->itemComments()->getHideReasons();
        $data['lang'] = array(
            'you' => _t('comments', 'Ваш комментарий'),
            'author' => _t('comments', 'Автор объявления'),
            'you_delete' => _t('comments', 'Вы удалили этот комментарий'),
            'answer' => _t('comments', 'Ответить'),
            'delete' => _t('comments', 'Удалить'),
            'cancel' => _t('', 'Отмена'),
            'date' => _t('comments', 'd.m.Y в H:i'),
            'show_answers' => _t('comments', 'Показать все ответы'),
        );
        if ($level == 1) {
            $data['lang']['show_more'] = _t('comments', 'Еще комментарии ([num])', array('num'=>(count($data['comments'])-$data['perPage'])));
        }
        return $this->viewPHP($data, 'item.comments.ajax');
    }
}