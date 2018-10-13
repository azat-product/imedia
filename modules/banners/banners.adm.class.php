<?php

/**
 * Права доступа группы:
 *  - banners: Баннеры
 *      - listing: Просмотр списка баннеров / позиций баннеров / статистики
 *      - edit: Управление баннерами / позициями баннеров
 */
class Banners_ extends BannersBase
{
    # Баннеры

    public function listing()
    {
        if (!$this->haveAccessTo('listing')) {
            return $this->showAccessDenied();
        }

        $f = $this->input->getm(array(
                'pos'         => TYPE_UINT, # ID позиции, 0 - все позиции
                'region'      => TYPE_UINT, # ID региона, 0 - без учета региона
                'locale'      => TYPE_NOTAGS, # Локализация
                'show_start'  => TYPE_NOTAGS, # дата показа (с)
                'show_finish' => TYPE_NOTAGS, # дата показа (по)
                'q'           => TYPE_NOTAGS, # название
                'tab'         => TYPE_UINT, # статус
            )
        );

        $sql = array();
        if ($f['pos']) {
            $sql['pos'] = $f['pos'];
        }
        if ($f['region']) {
            $sql['region'] = $f['region'];
        }
        if (!empty($f['locale'])) {
            if ($f['locale'] == self::LOCALE_ALL) {
                $sql[':locale'] = array(
                    '(B.locale LIKE :locale OR B.locale = :locale2)',
                    ':locale' => '%'.$f['locale'].'%',
                    ':locale2' => ''
                );
            } else {
                $sql[':locale'] = array(
                    'B.locale LIKE :locale',
                    ':locale' => '%'.$f['locale'].'%'
                );
            }
        }

        $show_start = (!empty($f['show_start']) ? strtotime($f['show_start']) : 0);
        $show_finish = (!empty($f['show_finish']) ? strtotime($f['show_finish']) : 0);
        if ($show_start && $show_finish) {
            if ($show_start > $show_finish) {
                $sql[] = array('B.show_start >= :start', ':start' => date('Y-m-d', $show_start));
                $f['show_finish'] = '';
            } else {
                $sql[] = array(
                    'B.show_start >= :start AND B.show_finish <= :finish',
                    ':start'  => date('Y-m-d', $show_start),
                    ':finish' => date('Y-m-d', $show_finish)
                );
            }
        } else {
            if ($show_start) {
                $sql[] = array('B.show_start >= :start', ':start' => date('Y-m-d', $show_start));
            } else {
                if ($show_finish) {
                    $sql[] = array('B.show_finish <= :finish', ':finish' => date('Y-m-d', $show_finish));
                }
            }
        }

        if ($f['q']) {
            $sql[':q'] = array('(B.title LIKE :q OR B.alt LIKE :q OR B.description LIKE :q OR B.click_url LIKE :q)',
                ':q' => '%'.$f['q'].'%'
            );
        }

        if ($f['tab'] > 0) {
            switch ($f['tab']) {
            case 1:
                $sql['enabled'] = 0;
                break; # включенные
            case 2:
                $sql['enabled'] = 1;
                break; # выключенные
            }
        }

        $aPositions = $this->model->positionsList();
        $aOrders = array('id'          => 'desc',
                         'show_start'  => 'desc',
                         'show_finish' => 'desc',
                         'shows'       => 'desc',
                         'clicks'      => 'desc',
                         'ctr'         => 'desc'
        );
        $aData = $this->prepareOrder($orderBy, $orderDir, 'id' . tpl::ORDER_SEPARATOR . 'desc', $aOrders);

        $aData['banners'] = $this->model->bannersListing($sql);
        if (!empty($aData['banners'])) {
            foreach ($aData['banners'] as &$v) {
                $v['pos'] =& $aPositions[$v['pos']];
                $v['ctr'] = round(($v['clicks'] / ($v['shows'] ? $v['shows'] : 1)) * 100, 2);
                $v['region_title'] = _t('banners', 'Во всех регионах');
                if ( ! empty($v['regions'])) {
                    $regs = array();
                    foreach($v['regions'] as $vv) {
                        $regs[] = '<a href="javascript:void(0);" onclick="jBannersList.region('.$vv['id'].'); return false;" class="desc">'.$vv['title'].'</a>';
                        if (count($regs) > 3) {
                            $regs[] = '...';
                            break;
                        }
                    }
                    $v['region_title'] = join(', ', $regs);
                }

                if (static::FILTER_LOCALE) {
                    $v['locale'] = (!empty($v['locale']) ? explode(',', $v['locale']) : array());
                }
            }
            unset($v);
            if ($orderBy) {
                if ($orderBy == 'id' && ! empty($f['pos'])) {
                    $orderBy = 'num';
                    $orderDir = 'asc';
                    $aData['order_by'] = $orderBy;
                }
                usort($aData['banners'], create_function('$a, $b', 'return strnatcasecmp(' .
                        ($orderDir == 'asc' ? "\$a['$orderBy'], \$b['$orderBy']" : "\$b['$orderBy'], \$a['$orderBy']") .
                        ');'
                    )
                );
            }
        }

        $aData['f'] = $f;
        $aData['positions'] = $aPositions;

        return $this->viewPHP($aData, 'admin.listing');
    }

    public function add()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $aData = $this->validateBannerData(false);

        if (Request::isPOST()) {

            func::setSESSION('banner_position', $aData['pos']);

            if ($this->errors->no('banners.admin.banner.submit', array('id'=>0,'data'=>&$aData))) {
                $nBannerID = $this->model->bannerSave(0, $aData);
                if ($nBannerID > 0) {
                    $this->validateBannerTypeData($nBannerID, false);
                    if ($aData['enabled']) {
                        $this->model->cacheReset($nBannerID);
                    }
                }

                $this->adminRedirect(Errors::SUCCESS);
            }
        }

        # подготавливаем ссылку
        $aData['id_from'] = $this->model->bannerNextID();
        if ($aData['id_from'] > 0) {
            $aData['link'] = static::url('click', array('id' => $aData['id_from']));
        }

        return $this->form(0, $aData);
    }

    public function edit()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $nBannerID = $this->input->postget('id', TYPE_UINT);
        if (!$nBannerID) {
            $this->showImpossible(true);
        }

        $aData = $this->model->bannerData($nBannerID);
        if (empty($aData)) {
            $this->showImpossible(true);
        }

        if (Request::isPOST()) {

            $aDataNew = $this->validateBannerData(true, $aData);

            if ($this->errors->no('banners.admin.banner.submit', array('id'=>$nBannerID,'data'=>&$aDataNew))) {
                $this->model->bannerSave($nBannerID, $aDataNew);
                $this->validateBannerTypeData($nBannerID, true, $aData);
                $this->model->cacheReset($nBannerID);

                $this->adminRedirect(Errors::SUCCESS);
            }

            $aData = array_merge($aData, $aDataNew);
        }

        $aData['link'] = static::url('click', array('id' => $nBannerID));

        return $this->form($nBannerID, $aData);
    }

    protected function form($nBannerID, &$aData)
    {
        $aData['id'] = $nBannerID;

        if (empty($aData['pos'])) {
            $aData['pos'] = func::SESSION('banner_position');
        }

        $aData['positions'] = $this->model->positionsList();
        if ($nBannerID) {
            $aData['width'] = $aData['positions'][$aData['pos']]['width'];
            $aData['height'] = $aData['positions'][$aData['pos']]['height'];
            $category_module = $aData['positions'][$aData['pos']]['filter_category_module'];
        }

        # списки категорий модулей
        if (!is_array($aData['category_id'])) {
            $aData['category_id'] = (!empty($aData['category_id']) ? explode(',', $aData['category_id']) : array());
        }
        $category_modules = array('bbs');
        if (bff::shopsEnabled()) {
            $category_modules[] = 'shops';
        }
        foreach ($category_modules as $module) {
            $aData['categories'][$module] = $this->getCategories($module,
                (!empty($category_module) && $category_module == $module ?
                    $aData['category_id'] : array())
            );
        }

        return $this->viewPHP($aData, 'admin.form');
    }

    public function delete()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $nBannerID = $this->input->get('id', TYPE_UINT);
        if (!$nBannerID) {
            $this->showImpossible(true);
        }

        $this->deleteBanner($nBannerID);
        $this->model->cacheReset();

        $this->adminRedirect(Errors::SUCCESS);
    }

    public function preview()
    {
        do {
            if (!$this->haveAccessTo('listing')) {
                break;
            }

            $nBannerID = $this->input->post('id', TYPE_UINT);
            if (!$nBannerID) {
                break;
            }

            $aData = $this->model->bannerData($nBannerID);
            if (empty($aData)) {
                break;
            }

            if ($aData['type'] == self::TYPE_IMAGE) {
                $sImgPath = $this->buildPath($nBannerID, $aData['img'], self::szView);
                if (!file_exists($sImgPath)) {
                    break;
                }
                list($aData['img_width']) = getimagesize($sImgPath);
            }

            $this->ajaxResponse($this->viewPHP($aData, 'admin.preview'));
        } while (false);

        $this->ajaxResponse('');
    }

    # Статистика
    public function statistic()
    {
        if (!$this->haveAccessTo('listing')) {
            return $this->showAccessDenied();
        }

        $nBannerID = $this->input->postget('id', TYPE_UINT);
        $aData['banner'] = $this->model->bannerData($nBannerID);
        if (empty($aData['banner'])) {
            $this->showImpossible(true);
        }

        $f = $this->input->getm(array(
                'page'        => TYPE_UINT, # страница
                'order'       => TYPE_NOTAGS, # сортировка
                'date_start'  => TYPE_NOTAGS, # от
                'date_finish' => TYPE_NOTAGS, # до
            )
        );

        $aOrders = array('period' => 'asc', 'shows' => 'asc', 'clicks' => 'asc', 'ctr' => 'asc');
        $f = array_merge($this->prepareOrder($orderBy, $orderDir, 'period' . tpl::ORDER_SEPARATOR . 'desc', $aOrders), $f);

        $sql = array('banner_id' => $nBannerID);
        if (!empty($f['date_start'])) {
            $sql[] = array('period >= :periodStart', ':periodStart' => date('Y-m-d', strtotime($f['date_start'])));
        }
        if (!empty($f['date_finish'])) {
            $sql[] = array('period <= :periodFinish', ':periodFinish' => date('Y-m-d', strtotime($f['date_finish'])));
        }

        $nTotal = $this->model->bannerStatisticListing($sql, true);
        $oPgn = new Pagination($nTotal, 20, '#', 'jBannerStatistic.page(' . Pagination::PAGE_ID . '); return false;');
        $aData['pgn'] = $oPgn->view();
        $aData['stat'] = $this->model->bannerStatisticListing($sql, false, $f['order_by'] . ' ' . $f['order_dir'], $oPgn->getLimitOffset());

        $aData['positions'] = $this->model->positionsList();
        $aData['banner']['position'] = $aData['positions'][$aData['banner']['pos']];
        $aData['banner']['preview'] = $this->buildUrl($nBannerID, $aData['banner']['img'], self::szThumbnail);
        $aData['banner']['flash'] = $this->flashData($aData['banner']['type_data']);

        $f['id'] = $nBannerID;
        $aData['f'] = $f;

        return $this->viewPHP($aData, 'admin.statistic');
    }

    # Позиции баннеров
    public function positions()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $sAct = $this->input->postget('act', TYPE_STR);
        if (!empty($sAct) || Request::isPOST()) {
            $aResponse = array();
            switch ($sAct) {
            case 'add':
            {
                $bSubmit = $this->input->post('save', TYPE_BOOL);
                $aData = $this->validatePositionData(0, $bSubmit);
                if ($bSubmit) {

                    if ($this->errors->no('banners.admin.position.submit',array('id'=>0,'data'=>&$aData))) {
                        $nPositionID = $this->model->positionSave(0, $aData);
                        if ($nPositionID > 0) {
                        }
                    }
                }

                $aData['id'] = 0;

                $aData['category_modules'] = array('bbs' => _t('bbs', 'Объявления'));
                if (bff::shopsEnabled()) {
                    $aData['category_modules']['shops'] = _t('shops', 'Магазины');
                }

                $aResponse['form'] = $this->viewPHP($aData, 'admin.positions.form');
            }
                break;
            case 'edit':
            {
                $bSubmit = $this->input->post('save', TYPE_BOOL);
                $nPositionID = $this->input->postget('id', TYPE_UINT);
                if (!$nPositionID) {
                    $this->errors->unknownRecord();
                    break;
                }

                if ($bSubmit) {

                    $aData = $this->validatePositionData($nPositionID, $bSubmit);
                    if ($this->errors->no('banners.admin.position.submit',array('id'=>$nPositionID,'data'=>&$aData))) {
                        $this->model->positionSave($nPositionID, $aData);
                        $this->model->cacheReset();
                    }
                    $aData['id'] = $nPositionID;
                } else {
                    $aData = $this->model->positionData($nPositionID);
                    if (empty($aData)) {
                        $this->errors->unknownRecord();
                        break;
                    }
                }

                $aData['category_modules'] = array('bbs' => _t('bbs', 'Объявления'));
                if (bff::shopsEnabled() || $aData['filter_category_module'] == 'shops') {
                    $aData['category_modules']['shops'] = _t('shops', 'Магазины');
                }

                $aResponse['form'] = $this->viewPHP($aData, 'admin.positions.form');
            }
                break;
            case 'toggle':
            {
                $nPositionID = $this->input->postget('id', TYPE_UINT);
                if (!$nPositionID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $sToggleType = $this->input->get('type', TYPE_STR);
                $this->model->positionToggle($nPositionID, $sToggleType);
                $this->model->cacheReset();
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

        $aData['list'] = $this->model->positionsList();
        $aData['list'] = $this->viewPHP($aData, 'admin.positions.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                )
            );
        }

        $aData['f'] = $f;
        $aData['id'] = $this->input->get('id', TYPE_UINT);
        $aData['act'] = $sAct;

        return $this->viewPHP($aData, 'admin.positions.listing');
    }

    public function position_delete()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $nPositionID = $this->input->getpost('id', TYPE_UINT);
        $aData = $this->model->positionData($nPositionID);
        if (empty($aData)) {
            return $this->showImpossible(true, 'positions');
        }

        if (Request::isPOST()) {

            if (!empty($aData['banners'])) {
                $bDelBanners = $this->input->post('del', TYPE_BOOL);
                if ($bDelBanners) {
                    # удаляем баннеры связанные с позицией
                    $aBannersID = $this->model->bannersByPosition($nPositionID);
                    if (!empty($aBannersID)) {
                        $this->deleteBanner($aBannersID);
                        $this->model->cacheReset();
                    }
                    # удаляем позицию
                    $this->model->positionDelete($nPositionID);
                } else {
                    $nNextPositionID = $this->input->post('next', TYPE_UINT);
                    $aNextPositionData = $this->model->positionData($nNextPositionID);
                    if (empty($aNextPositionData) || $nNextPositionID == $nPositionID) {
                        $this->errors->set(_t('banners', 'Выберите позицию для перемещения баннеров'));
                    } else {
                        # перемещаем баннеры
                        $aBannersID = $this->model->bannersByPosition($nPositionID);
                        $this->model->bannersToPosition($aBannersID, $nNextPositionID);
                        $this->model->cacheReset();
                        # удаляем позицию
                        $this->model->positionDelete($nPositionID);
                    }
                }
            } else {
                # удаляем позицию
                $this->model->positionDelete($nPositionID);
            }

            $this->adminRedirect(Errors::SUCCESS, 'positions');
        }

        $aData['positions'] = $this->model->positionsList(array(array('P.id!=:id', ':id' => $nPositionID)));

        return $this->viewPHP($aData, 'admin.positions.delete');
    }

    public function ajax()
    {
        $action = $this->input->postget('act');
        switch ($action) {
        case 'banner-toggle': # включение / выключение баннера
        {
            if (!$this->haveAccessTo('edit')) {
                $this->showAccessDenied();
            }

            $nBannerID = $this->input->postget('rec', TYPE_UINT);
            if (!$nBannerID) {
                $this->showAccessDenied();
            }

            $aData = $this->model->bannerData($nBannerID);
            if (!$aData) {
                $this->showImpossible();
            }

            # Проверка возможно ли включить баннер (не используется ли на неротируемой позиции другой баннер)
            if (!$aData['enabled'] && $this->checkPositionRotation($aData['pos'])) {
                $this->model->bannerSave($nBannerID, array('enabled' => 1));
                $this->model->cacheReset($nBannerID);
            } elseif ($aData['enabled']) {
                $this->model->bannerSave($nBannerID, array('enabled' => 0));
                $this->model->cacheReset($nBannerID);
            } else {
                $this->errors->set(_t('banners', 'На данной позиции запрещена ротация нескольких баннеров'));
                $this->ajaxResponse(0);
            }
            $this->ajaxResponse(Errors::SUCCESS);
        }
        break;
        case 'rotate':
        {
            if (!$this->haveAccessTo('listing')) {
                $this->errors->accessDenied();
                $this->ajaxResponse(0);
            }
            $pos = $this->input->get('pos', TYPE_UINT);
            if ( ! $pos) break;

            $this->model->bannersRotate($pos);
            $this->ajaxResponse(Errors::SUCCESS);
        }
        break;
        case 'dev-reset-cache': # сброс кеша
        {
            if (!FORDEV) {
                $this->showAccessDenied();
            }

            $this->model->cacheReset();
            $this->adminRedirect(Errors::SUCCESS);
        }
        break;
        default:
        {
            bff::hook('banners.admin.ajax.default.action', $action, $this);
        }
        break;
        }

        $this->ajaxResponse(Errors::IMPOSSIBLE);
    }

    /**
     * Обрабатываем данные формы настроек баннера
     * @param boolean $bEdit true - редактирование, false - создание
     * @param array $aDataPrev настройки, до начала редактирования
     * @return array данные
     */
    protected function validateBannerData($bEdit, array $aDataPrev = array())
    {
        $aData = $this->input->postm(array(
                'pos'             => TYPE_UINT, # ID позиции
                'type'            => TYPE_UINT, # ID типа баннера (Banners::TYPE_...)
                'sitemap_id'      => TYPE_ARRAY_INT, # ID пунктов меню (TABLE_SITEMAP)
                'regions'         => TYPE_ARRAY_UINT, # ID регионов
                'list_pos'        => TYPE_INT, # № позиции в списке
                'locale'          => TYPE_ARRAY_NOTAGS, # Локализация
                'category_id'     => TYPE_ARRAY_UINT, # ID категорий или 0
                'show_limit'      => TYPE_UINT, # лимит показов
                'show_start'      => TYPE_NOTAGS, # начало показов
                'show_finish'     => TYPE_NOTAGS, # конец показов
                'click_url'       => TYPE_NOTAGS, # конечная ссылка перехода
                'url_match'       => TYPE_NOTAGS, # фильтр по REQUEST_URI
                'url_match_exact' => TYPE_BOOL, # Только точное совпадение
                'enabled'         => TYPE_BOOL, # включен / выключен
                'title'           => TYPE_NOTAGS, # title
                'alt'             => TYPE_NOTAGS, # alt
                'description'     => TYPE_NOTAGS, # заметка
                'target_blank'    => TYPE_BOOL, # открывать в новом окне
            )
        );
        extract($aData, EXTR_REFS);

        if (Request::isPOST()) {
            if (!$pos) {
                $this->errors->set(_t('banners', 'Укажите позицию'));
            }

            # Если на данной позиции запрещена ротация нескольких баннеров
            if ((!$bEdit || !$aDataPrev['enabled']) && $enabled && !$this->checkPositionRotation($pos)) {
                $enabled = 0; # добавляем - но выключенный
            }

            if ($type !== static::TYPE_CODE)
            {
                if (empty($click_url) || $click_url == '#') {
                    $this->errors->set(_t('banners', 'Укажите корректную ссылку'));
                } else {
                    if (preg_match('/^(http|https|ftp):\/\//xisu', $click_url) !== 1) {
                        if (strpos($click_url, 'www.') === 0) {
                            # корректируем протокол, если отсутствует
                            $click_url = 'http://' . $click_url;
                        } else {
                            if ($click_url{0} !== '/') {
                                # корректируем относительные ссылки
                                $click_url = '/' . $click_url;
                            }
                        }
                    }
                }
            }

            if (!empty($url_match)) {
                # приводим $url_match к корректной относительной ссылке
                if (preg_match('/^(http|https|ftp):\/\//xisu', $url_match) === 1) {
                    $url_match = parse_url($url_match, PHP_URL_PATH);
                } else {
                    if (strpos($url_match, 'www.') === 0) {
                        $url_match = mb_strcut($url_match, 4);
                    }
                }
                if (!empty($url_match) && $url_match{0} !== '/') {
                    $url_match = '/' . $url_match;
                }
            }

            $sitemap_id = join(',', $sitemap_id);

            $locales = bff::locale()->getLanguages();
            if (sizeof($locales) < 2 || empty($locale)) {
                $locale = self::LOCALE_ALL;
            } else {
                $locale = join(',', $locale);
            }

            $category_id = join(',', $category_id);

            $show_start = date('Y-m-d H:i:s', strtotime($show_start));
            $show_finish = date('Y-m-d H:i:s', strtotime($show_finish));

            $t = $regions;
            $regions = array();
            foreach($t as $v) {
                $r = Geo::model()->regionParents($v);
                if ( ! empty($r['db']['reg1_country'])) {
                    $regions[] = $r['db'];
                }
            }
            if( ! $aData['list_pos']){
                $aData['list_pos'] = 1;
            }
        }

        return $aData;
    }

    /**
     * Обрабатываем загрузку/сохранение данных баннера в зависимости от типа баннера (self::TYPE_)
     * @param integer $nBannerID ID баннера
     * @param boolean $bEdit true - редактирование, false - создание
     * @param array $aDataPrev настройки, до начала редактирования
     * @return array данные
     */
    protected function validateBannerTypeData($nBannerID, $bEdit, $aDataPrev = array())
    {
        $aData = $this->input->postm(array(
                'pos'          => TYPE_UINT, # ID позиции
                'type'         => TYPE_UINT, # тип баннера (self::TYPE_)
                # код
                'code'         => TYPE_STR,
                # flash
                'flash_width'  => TYPE_UINT,
                'flash_height' => TYPE_UINT,
                'flash_key'    => TYPE_STR,
                # тизер
                'teaser'       => TYPE_NOTAGS,
            )
        );
        extract($aData, EXTR_REFS);

        $sqlUpdate = array();

        $bDeleteFlash = false;
        switch ($type) {
        case self::TYPE_IMAGE:
        {
            # загружаем изображение
            $sFilename = $this->imgUpload($nBannerID, $pos);
            if ($sFilename !== false) {
                if ($bEdit) {
                    $this->imgDelete($nBannerID, $aDataPrev['img']);
                }
                $sqlUpdate['img'] = $sFilename;
            }
        }
        break;
        case self::TYPE_FLASH:
        {
            # загружаем изображение
            $sFilename = $this->imgUpload($nBannerID, $pos);
            if ($sFilename !== false) {
                if ($bEdit) {
                    $this->imgDelete($nBannerID, $aDataPrev['img']);
                }
                $sqlUpdate['img'] = $sFilename;
            }

            if (!$flash_height) {
                $this->errors->set(_t('banners', 'Не указаны размеры flash-баннера'));
                break;
            }

            $flash_data = ($bEdit ? $this->flashData($aDataPrev['type_data']) : array('file' => ''));
            $flash_file = $flash_data['file'];
            # загружаем flash файл
            $flash_upload_result = $this->flashUpload($nBannerID);
            if (!empty($flash_upload_result)) {
                $flash_file = $flash_upload_result;
                if ($bEdit) {
                    $this->flashDelete($nBannerID, $flash_data);
                }
            }

            $sqlUpdate['type_data'] = serialize(array(
                    'file'   => $flash_file,
                    'width'  => $flash_width,
                    'height' => $flash_height,
                    'key'    => $flash_key,
                )
            );
        }
        break;
        case self::TYPE_CODE:
        {
            $bDeleteFlash = true;
            $sqlUpdate['type_data'] = $code;
        }
        break;
        case self::TYPE_TEASER:
        {
            # загружаем изображение
            $sFilename = $this->imgUpload($nBannerID, $pos);
            if ($sFilename !== false) {
                if ($bEdit) {
                    $this->imgDelete($nBannerID, $aDataPrev['img']);
                }
                $sqlUpdate['img'] = $sFilename;
            }

            $bDeleteFlash = true;
            $sqlUpdate['type_data'] = $teaser;
        }
        break;
        }

        if (!empty($sqlUpdate)) {
            $res = $this->model->bannerSave($nBannerID, $sqlUpdate);
            if ($res && $bDeleteFlash && $bEdit) {
                $this->flashDelete($nBannerID, $aDataPrev['type_data']);
            }
        }
    }

    /**
     * Обрабатываем данные формы настроек позиции
     * @param integer $nPositionID ID позиции или 0
     * @param boolean $bSubmit выполняем сохранение/редактирование
     * @return array параметры
     */
    protected function validatePositionData($nPositionID, $bSubmit)
    {
        $aData = $this->input->postm(array(
                'title'                  => array(TYPE_NOTAGS, 'len'=>200), # Название
                'keyword'                => array(TYPE_NOTAGS, 'len'=>50), # Keyword
                'width'                  => TYPE_UINT, # Ширина
                'height'                 => TYPE_UINT, # Высота
                'rotation'               => TYPE_BOOL, # Ротация
                'rotation_visible'       => TYPE_UINT, # Количество одновременно отображаемых баннеров
                # фильтры:
                'filter_sitemap'         => TYPE_BOOL, # Раздел сайта
                'filter_region'          => TYPE_BOOL, # Регион
                'filter_category'        => TYPE_BOOL, # Категория
                'filter_category_module' => TYPE_NOTAGS, # Модуль категорий
                'filter_auth_users'      => TYPE_BOOL, # Скрывать для авторизованных пользователей
                'filter_list_pos'        => TYPE_BOOL, # позиция в списке
                'enabled'                => TYPE_BOOL, # Включен
            )
        );

        if ($bSubmit) {
            if (empty($aData['title'])) {
                $this->errors->set(_t('banners', 'Не указано название позиции'));
            }
            if (empty($aData['keyword'])) {
                $this->errors->set(_t('banners', 'Не указан keyword позиции'));
            }
            $aData['keyword'] = $this->model->positionKeywordValidate($aData['keyword'], $aData['title'], $nPositionID);

            if (!$aData['width'] && !$aData['height']) {
                $this->errors->set(_t('banners', 'Укажите как минимум один из размеров, ширину или высоту'));
            }
        }

        return $aData;
    }

}