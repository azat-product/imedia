<?php

class Site_ extends SiteBase
{
    /**
     * Главная страница
     */
    public function index()
    {
        $aData = array('titleh1' => '', 'seotext' => '');
        $region = Geo::filterUrl(); # seo
        if (!empty($region['id'])) {
            View::setPageData([
                'index_region_id' => $region['id'],
                'index_region_data' => $region,
            ]);
            # главная региона => список объявлений
            if (config::sysTheme('bbs.index.region.search', true, TYPE_BOOL)) {
                bff::setActiveMenu('//index');
                return BBS::i()->search();
            }
            # seo: Главная страница (регион)
            $this->seo()->canonicalUrl(Geo::url($region, true));
            $this->setMeta('index-region', array(
                'region' => ($region['city'] ? $region['city'] :
                    ($region['region'] ? $region['region'] :
                        ($region['country'] ? $region['country'] : '')))
            ), $aData);
        } else {
            # seo: Главная страница
            $this->seo()->canonicalUrl(static::url('index', array(), true));
            $this->setMeta('index', array(), $aData);
        }

        # Варианты центрального блока главной
        if (DEVICE_DESKTOP_OR_TABLET) {
            $indexTemplate = 'index.default';
            if (bff::theme() !== false && bff::theme()->configExists('site.index.template')) {
                $indexTemplate = bff::theme()->config('site.index.template', $indexTemplate);
            }

            $indexTemplate = static::indexTemplates($indexTemplate);
            $initRegions = !empty($indexTemplate['regions']);
            $initMap = !empty($indexTemplate['map']);
            $regionID = Geo::coveringRegion();
            if (is_array($regionID)) {
                $filter = Geo::filter();
                if ( ! empty($filter['id']) && in_array($filter['id'], $regionID)) {
                    # применим карту для страны из фильтра
                    $regionID = $filter['id'];
                } else {
                    $regionID = reset($regionID);
                }
            }

            if ($initRegions || $initMap) {
                $regions = Geo::model()->regionsList(array(Geo::lvlRegion, Geo::lvlCity), array(':reg' => '(R.country = ' . $regionID . ' AND R.main > 0) OR R.pid = ' . $regionID));
                $items = BBS::model()->itemsCountByFilter(array(
                    'cat_id' => 0,
                    'region_id' => array_keys($regions),
                    'delivery' => 0,
                ), array('region_id', 'items'), false, 60);
                $items = func::array_transparent($items, 'region_id', true);
                $aData['regions'] = array();
                if (!empty($regions)) {
                    foreach ($regions as &$v) {
                        $v['items'] = ! empty($items[ $v['id'] ]['items']) ? $items[ $v['id'] ]['items'] : 0;
                        $v['l'] = BBS::url('items.search', array('region' => $v['keyword']));
                    } unset($v);
                }
            }
            if ($initMap) {
                $aData['map'] = Geo::i()->regionMap($regionID, $regions);
            }
            if ($initRegions) {
                uasort($regions, function($a, $b){
                    if ($a['numlevel'] == $b['numlevel']) {
                        return $a['items'] < $b['items'];
                    }
                    return $a['numlevel'] < $b['numlevel'];
                });
                $aData['regions'] = $regions;
            }
        }

        $aData['last'] = '';
        foreach (bff::filter('bbs.index.last.blocks', array(false)) as $v) {
            $aData['last'] .= BBS::i()->indexLastBlock($v);
        }
        $aData['cats'] = BBS::i()->catsList('index', bff::DEVICE_DESKTOP, 0);
        if (DEVICE_DESKTOP_OR_TABLET) {
            $aData['centerBlock'] = $this->viewPHP($aData, $indexTemplate['file']);
        }
        $aData['lastBlog'] = Blog::i()->indexLastBlock();

        return $this->viewPHP($aData, 'index');
    }

    /**
     * Блок фильтра в шапке
     */
    public function filterForm()
    {
        if (bff::$class == 'shops' && bff::shopsEnabled()) {
            return Shops::i()->searchForm();
        } else {
            if (bff::$class == 'help') {
                return Help::i()->searchForm();
            } else {
                return BBS::i()->searchForm();
            }
        }
    }

    /**
     * Страница "Услуги"
     */
    public function services()
    {
        $aData = array();

        if (!bff::servicesEnabled()) {
            $this->errors->error404();
        }

        # SEO:
        $this->urlCorrection(static::url('services'));
        $this->seo()->canonicalUrl(static::url('services', array(), true));
        $this->setMeta('services', array(), $aData);

        $aData['svc_bbs'] = BBS::model()->svcData();
        if (isset($aData['svc_bbs'][BBS::SERVICE_LIMIT])) {
            unset($aData['svc_bbs'][BBS::SERVICE_LIMIT]);
        }
        if (bff::shopsEnabled(true)) {
            $aData['svc_shops'] = Shops::model()->svcData();
            $aData['shop_opened'] = User::shopID() > 0;
            if ($aData['shop_opened']) {
                $aData['shop_promote_url'] = Shops::url('shop.promote', array('id'   => User::shopID(),
                        'from' => 'services'
                    )
                );
            } else {
                $aData['shop_open_url'] = Shops::url('my.open');
            }
            if (isset($aData['svc_shops'][Shops::SERVICE_ABONEMENT])) {
                unset($aData['svc_shops'][Shops::SERVICE_ABONEMENT]);
            }
        } else {
            $aData['svc_shops'] = array();
        }
        $aData['user_logined'] = (User::id() > 0);

        bff::setActiveMenu('//services');

        return $this->viewPHP($aData, 'services');
    }

    /**
     * Статические страницы
     */
    public function pageView()
    {
        $sFilename = $this->input->get('page', TYPE_NOTAGS);
        $aData = $this->model->pageDataView($sFilename);
        if (empty($aData)) {
            $this->errors->error404();
        }

        # SEO: Статические страницы
        $this->urlCorrection(static::url('page', array('filename' => $sFilename)));
        $this->seo()->canonicalUrl(static::url('page', array('filename' => $sFilename), true));
        $this->setMeta('page-view', array('title' => $aData['title']), $aData);
        if (!empty($aData['noindex'])) {
            $this->seo()->robotsIndex(false); # Скрываем от поисковиков
        }

        return $this->viewPHP($aData, 'page.view');
    }

    /**
     * Site offline mode
     */
    public function offlinePage()
    {
        $data = array(
            'offlineReason' => config::get('offline_reason_'.LNG),
        );
        $this->setMeta('offline', array(), $data);
        View::setLayout(false);
        $template = View::renderTemplate($data, 'offline');
        $layout = View::getLayout();
        if ($layout !== false) {
            $data['centerblock'] = $template;
            return View::renderLayout($data, $layout);
        }
        return $template;
    }

    /**
     * Страница "Карта сайта"
     */
    public function sitemap()
    {
        $aData = array('seotext' => '');

        # SEO: Карта сайта
        $this->urlCorrection(static::url('sitemap'));
        $this->seo()->canonicalUrl(static::url('sitemap', array(), true));
        $this->setMeta('sitemap', array(), $aData);

        $aData['cats'] = BBS::i()->catsListSitemap();
        if (!empty($aData['cats'])) {
            $aData['cats'] = array_chunk($aData['cats'], (sizeof($aData['cats']) <=3 ? 1 : sizeof($aData['cats']) / 3));
        }

        return $this->viewPHP($aData, 'sitemap');
    }

    /**
     * Обработчик перехода по внешним ссылкам
     */
    public function away()
    {
        $sURL = $this->input->get('url', TYPE_STR);
        if (empty($sURL)) {
            $sURL = SITEURL;
        } else {
            $sURL = 'http://' . $sURL;
        }
        if (config::sysAdmin('site.away.page', true, TYPE_BOOL))
        {
            $this->seo()->robotsFollow(false);
            $this->seo()->robotsIndex(false);
            $data = array(
                'url'     => $sURL,
                'timeout' => config::sysAdmin('site.away.page.timeout', 5, TYPE_UINT)
            );
            return $this->showShortPage(_t('site', 'Идет перенаправление'), $this->viewPHP($data, 'away'));
        }
        $this->redirect($sURL);
    }

    /**
     * Дополнительный текст в футере
     * @param string|boolean $lang ключ языка
     */
    public static function footerText($lang = false)
    {
        if (empty($lang)) {
            $lang = bff::locale()->getCurrentLanguage();
        }
        $text = config::get('footer_text_'.$lang);
        if (mb_strlen(trim(strip_tags(str_replace('&nbsp;',' ',$text)))) > 0) {
            return $text;
        }
        return '';
    }

    public function ajax()
    {
        $aResponse = array();

        switch ($this->input->getpost('act', TYPE_STR)) {
            default:
                $this->errors->impossible();
        }

        $aResponse['res'] = $this->errors->no();

        $this->ajaxResponse($aResponse);
    }

    /**
     * Cron: Чистка мусора
     * Рекомендуемый период: раз в сутки
     */
    public function cronCleaner()
    {
        # Удаление временных файлов изображений / файлов
        $this->temporaryDirsCleanup(array(
            bff::path('tmp', 'images'),
        ));

        # Сброс автоматических meta
        if ($this->input->getpost('meta-reset', TYPE_BOOL)) {
            $this->seo()->metaReset(array(
                join('_', array($this->module_name,'meta','main')),
            ));
        }

        # Очистка неактуальных запросов действий
        $this->model->requestsClear();
    }

    /**
     * Cron: Автоматическое обновление курса валют
     * Рекомендуемый период: раз в сутки
     */
    public function cronCurrencyRate()
    {
        if ( ! bff::cron()) {
            return;
        }
        if ( ! config::sysAdmin('currency.rate.auto', false, TYPE_BOOL)) {
            return;
        }

        $rate = new SiteCurrencyRate();
        $rate->process();
        exit;
    }

    /**
     * Расписание запуска крон задач
     * @return array
     */
    public function cronSettings()
    {

        return array(
            'cronCleaner' => array('period' => '0 0 * * *'),
            'cronCurrencyRate' => array('period' => '0 10 * * *'),
        );
    }

}