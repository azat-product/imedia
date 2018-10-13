<?php

abstract class ShopsBase_ extends Module
    implements IModuleWithSvc
{
    /** @var ShopsModel */
    var $model = null;
    var $securityKey = '425ea0b5fe88d011dbbe85b29173e741';

    # Типы ссылок соц. сетей
    const SOCIAL_LINK_FACEBOOK      = 1;
    const SOCIAL_LINK_VKONTAKTE     = 2;
    const SOCIAL_LINK_ODNOKLASSNIKI = 4;
    const SOCIAL_LINK_GOOGLEPLUS    = 8;
    const SOCIAL_LINK_YANDEX        = 16;
    const SOCIAL_LINK_MAILRU        = 32;

    # Статус магазина
    const STATUS_REQUEST    = 0; # заявка на открытие (используется совместно с premoderation()==true)
    const STATUS_ACTIVE     = 1; # активен
    const STATUS_NOT_ACTIVE = 2; # неактивен
    const STATUS_BLOCKED    = 3; # заблокирован

    # Настройки категорий
    const CATS_ROOTID  = 1; # ID "Корневой категории" (изменять не рекомендуется)
    const CATS_MAXDEEP = 2; # Максимальная глубина вложенности категорий (допустимые варианты: 1,2)

    # Типы отображения списка
    const LIST_TYPE_LIST = 1; # строчный вид
    const LIST_TYPE_MAP  = 3; # карта

    # ID Услуг
    const SERVICE_FIX  = 64; # закрепление
    const SERVICE_MARK = 128; # выделение
    const SERVICE_ABONEMENT = 256; # абонемент

    # Жалобы
    const CLAIM_OTHER = 1024; # тип жалобы: "Другое"

    const SVC_TERMLESS_DATE = '1999-11-11 11:11:11';  # Дата для бессрочного срока действия

    public function init()
    {
        parent::init();

        $this->module_title = _t('shops','Магазины');

        bff::autoloadEx(array(
                'ShopsLogo'         => array('app', 'modules/shops/shops.logo.php'),
                'ShopsSvcIcon'      => array('app', 'modules/shops/shops.svc.icon.php'),
                'ShopsCategoryIcon' => array('app', 'modules/shops/shops.category.icon.php'),
                'ShopsSearchSphinx' => array('app', 'modules/shops/shops.search.sphinx.php'),
            )
        );
    }

    public function sendmailTemplates()
    {
        $aTemplates = array(
            'shops_shop_sendfriend' => array(
                'title'       => _t('shops','Магазины: отправить другу'),
                'description' => _t('shops','Уведомление, отправляемое по указанному email адресу'),
                'vars'        => array(
                    '{shop_title}' => _t('shops','Название магазина'),
                    '{shop_link}'  => _t('shops','Ссылка на страницу магазина'),
                ),
                'impl'        => true,
                'priority'    => 18,
                'enotify'     => -1,
                'group'       => 'shops',
            ),
            'shops_open_success' => array(
                'title'       => _t('shops','Магазины: уведомление об активации магазина'),
                'description' => _t('shops','Уведомление, отправляемое <u>пользователю</u> с оповещением об успешном открытии магазина (после проверки модератором)'),
                'vars'        => array(
                    '{name}'       => _t('users','Имя'),
                    '{email}'      => _t('','Email'),
                    '{shop_id}'    => _t('shops','ID магазина'),
                    '{shop_title}' => _t('shops','Название магазина'),
                    '{shop_link}'  => _t('shops','Ссылка на магазин'),
                ),
                'impl'        => true,
                'priority'    => 19,
                'enotify'     => 0, # всегда
                'group'       => 'shops',
            ),
            'shops_abonement_activated' => array(
                'title'       => _t('shops','Магазины: уведомление об активации тарифа'),
                'description' => _t('shops','Тариф был активирован (в момент первой активации либо продления)'),
                'vars'        => array(
                    '{email}'         => _t('','Email'),
                    '{shop_id}'       => _t('shops','ID магазина'),
                    '{shop_title}'    => _t('shops','Название магазина'),
                    '{shop_link}'     => _t('shops','Ссылка на магазин'),
                    '{tariff_title}'  => _t('shops','Название тарифа'),
                    '{tariff_expire}' => _t('shops','Окончание действия тарифа [open]до ДАТА или бессрочно[close]', array(
                                                    'open' => '<br/><span class="desc">', 'close' => '</span>')),
                ),
                'impl'        => true,
                'priority'    => 20,
                'enotify'     => 0, # всегда
                'group'       => 'shops',
            ),
            'shops_abonement_finish_soon' => array(
                'title'       => _t('shops','Магазины: предупреждение об окончании срока действия тарифа'),
                'description' => _t('shops','Срок действия тарифа заканчивается через 3 дня'),
                'vars'        => array(
                    '{email}'        => _t('','Email'),
                    '{shop_id}'      => _t('shops','ID магазина'),
                    '{shop_title}'   => _t('shops','Название магазина'),
                    '{shop_link}'    => _t('shops','Ссылка на магазин'),
                    '{tariff_title}' => _t('shops','Название тарифа'),
                    '{tariff_price}' => _t('shops','Стоимость тарифа (1 месяц - 10 грн.)'),
                    '{promote_link}' => _t('shops','Ссылка на продление'),
                ),
                'impl'        => true,
                'priority'    => 21,
                'enotify'     => 0, # всегда
                'group'       => 'shops',
            ),
            'shops_abonement_finish_soon_onetime' => array(
                'title'       => _t('shops','Магазины: предупреждение об окончании срока действия единоразового тарифа'),
                'description' => _t('shops','Срок действия единоразового тарифа заканчивается через 3 дня'),
                'vars'        => array(
                    '{email}'        => _t('','Email'),
                    '{shop_id}'      => _t('shops','ID магазина'),
                    '{shop_title}'   => _t('shops','Название магазина'),
                    '{shop_link}'    => _t('shops','Ссылка на магазин'),
                    '{tariff_title}' => _t('shops','Название тарифа'),
                    '{promote_link}' => _t('shops','Ссылка смены тарифа'),
                ),
                'impl'        => true,
                'priority'    => 22,
                'enotify'     => 0, # всегда
                'group'       => 'shops',
            ),
            'shops_abonement_finished' => array(
                'title'       => _t('shops','Магазины: уведомление об окончании срока действия тарифа'),
                'description' => _t('shops','Срок действия тарифа закончился'),
                'vars'        => array(
                    '{email}'        => _t('','Email'),
                    '{shop_id}'      => _t('shops','ID магазина'),
                    '{shop_title}'   => _t('shops','Название магазина'),
                    '{shop_link}'    => _t('shops','Ссылка на магазин'),
                    '{tariff_title}' => _t('shops','Название тарифа'),
                    '{tariff_price}' => _t('shops','Стоимость тарифа (1 месяц - 10 грн.)'),
                    '{promote_link}' => _t('shops','Ссылка на продление'),
                ),
                'impl'        => true,
                'priority'    => 23,
                'enotify'     => 0, # всегда
                'group'       => 'shops',
            ),
            'shops_abonement_finished_onetime' => array(
                'title'       => _t('shops','Магазины: уведомление об окончании срока действия единоразового тарифа'),
                'description' => _t('shops','Срок действия единоразового тарифа закончился'),
                'vars'        => array(
                    '{email}'        => _t('','Email'),
                    '{shop_id}'      => _t('shops','ID магазина'),
                    '{shop_title}'   => _t('shops','Название магазина'),
                    '{shop_link}'    => _t('shops','Ссылка на магазин'),
                    '{tariff_title}' => _t('shops','Название тарифа'),
                    '{promote_link}' => _t('shops','Ссылка смены тарифа'),
                ),
                'impl'        => true,
                'priority'    => 24,
                'enotify'     => 0, # всегда
                'group'       => 'shops',
            ),
            'shops_abonement_period_no_money' => array(
                'title'       => _t('shops','Магазины: уведомление о недостаточном количестве средств для автоматического продления действия тарифа'),
                'description' => _t('shops','Недостаточное количество средств для автоматического продления тарифа (за 5 дней до окончания срока действия)'),
                'vars'        => array(
                    '{email}'         => _t('','Email'),
                    '{shop_id}'       => _t('shops','ID магазина'),
                    '{shop_title}'    => _t('shops','Название магазина'),
                    '{shop_link}'     => _t('shops','Ссылка на магазин'),
                    '{tariff_title}'  => _t('shops','Название тарифа'),
                    '{tariff_expire}' => _t('shops','Окончание действия тарифа'),
                    '{tariff_price}'  => _t('shops','Стоимость тарифа (1 месяц - 10 грн.)'),
                    '{promote_link}'  => _t('shops','Ссылка на продление'),
                ),
                'impl'        => true,
                'priority'    => 25,
                'enotify'     => 0, # всегда
                'group'       => 'shops',
            ),
        );

        Sendmail::i()->addTemplateGroup('shops', _t('shops', 'Магазины'), 2);

        return $aTemplates;
    }

    /**
     * Shortcut
     * @return Shops
     */
    public static function i()
    {
        return bff::module('shops');
    }

    /**
     * Shortcut
     * @return ShopsModel
     */
    public static function model()
    {
        return bff::model('shops');
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts доп. параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        $url = $base = static::urlBase(LNG, $dynamic);
        switch ($key) {
            # список магазинов (geo)
            case 'search':
                # формируем ссылку с учетом указанной области (region), [города (city)]
                # либо с учетом текущих настроек фильтра по региону
                $url = Geo::url($opts, $dynamic) . 'shops/' . (!empty($opts['keyword']) ? $opts['keyword'] . '/' : '');
                break;
            # просмотр страницы магазина (geo)
            case 'shop.view':
                # формируем ссылку с учетом указанной области (region), [города (city)]
                # либо с учетом текущих настроек фильтра по региону
                $url = Geo::url($opts, $dynamic) . 'shop/';
                break;
            # страница продвижения магазина
            case 'shop.promote':
                $url .= '/shop/promote?' . http_build_query($opts);
                break;
            # заявка на закрепление магазина за пользователем
            case 'request':
                $url = '/shop/request' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # форма открытия магазина
            case 'my.open':
                $url .= '/cabinet/shop/open' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # форма открытия магазина
            case 'my.shop':
                $url .= '/cabinet/shop' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # форма смены абонемента
            case 'my.abonement':
                $url .= '/cabinet/shop/abonement' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # купленные услуги платного расширения лимитов
            case 'my.limits.payed':
                $url = BBS::url('my.limits.payed') . '?shop=1';
                break;
        }
        return bff::filter('shops.url', $url, array('key'=>$key, 'opts'=>$opts, 'dynamic'=>$dynamic, 'base'=>$base));
    }

    /**
     * Формирование URL страниц магазина
     * @param string $shopLink ссылка на магазин
     * @param string $tab ключ страницы магазина
     * @param array $opts доп. параметры
     * @return string
     */
    public static function urlShop($shopLink, $tab = '', array $opts = array())
    {
        return $shopLink . (!empty($tab) ? '/' . $tab . '/' : '') . (!empty($opts) ? '?' . http_build_query($opts) : '');
    }

    /**
     * URL для формы связи с магазином
     * @param string $shopLink ссылка на магазин
     * @return string
     */
    public static function urlContact($shopLink)
    {
        return static::urlShop($shopLink, 'contact');
    }

    /**
     * Использовать мультиязычные поля
     * @return mixed
     */
    public static function titlesLang()
    {
        return config::sysAdmin('shops.titles.lang', false, TYPE_BOOL);
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        $templates = array(
            'pages'  => array(
                'search'          => array(
                    't'      => _t('shops', 'Поиск (все категории)'),
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(),
                    'fields'  => array(
                        'titleh1' => array(
                            't'    => _t('', 'Заголовок H1'),
                            'type' => 'text',
                        ),
                        'seotext' => array(
                            't'    => _t('', 'SEO текст'),
                            'type' => 'wy',
                        ),
                    ),
                ),
                'search-category' => array(
                    't'       => static::categoriesEnabled() ? _t('shops', 'Поиск в категории магазинов') : _t('shops', 'Поиск в категории'),
                    'list'    => true,
                    'i'       => true,
                    'macros'  => array(
                        'category'           => array('t' => _t('shops', 'Название текущей категории')),
                        'category+parent'    => array('t' => _t('shops', 'Название текущей категории + категории выше')),
                        'categories'         => array('t' => _t('shops', 'Название всех категорий')),
                        'categories.reverse' => array('t' => _t('shops', 'Название всех категорий<br />(обратный порядок)')),
                    ),
                    'fields'  => array(
                        'breadcrumb' => array(
                            't'    => _t('', 'Хлебная крошка'),
                            'type' => 'text',
                        ),
                        'titleh1' => array(
                            't'    => _t('', 'Заголовок H1'),
                            'type' => 'text',
                        ),
                        'seotext' => array(
                            't'    => _t('', 'SEO текст'),
                            'type' => 'wy',
                        ),
                    ),
                    'inherit' => static::categoriesEnabled(),
                ),
                'shop-view'       => array(
                    't'      => _t('shops', 'Просмотр магазина'),
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'title'       => array('t' => _t('shops', 'Название магазина')),
                        'description' => array('t' => _t('shops', 'Описание магазина (до 150 символов)')),
                        'country'     => array('t' => _t('shops', 'Страна магазина')),
                        'region'      => array('t' => _t('shops', 'Регион магазина')),
                    ),
                    'fields' => array(
                        'share_title'       => array(
                            't'    => _t('shops', 'Заголовок (поделиться в соц. сетях)'),
                            'type' => 'text',
                        ),
                        'share_description' => array(
                            't'    => _t('shops', 'Описание (поделиться в соц. сетях)'),
                            'type' => 'textarea',
                        ),
                        'share_sitename'    => array(
                            't'    => _t('shops', 'Название сайта (поделиться в соц. сетях)'),
                            'type' => 'text',
                        ),
                    ),
                ),
            ),
            'macros' => array(
                'region' => array('t' => _t('', 'Регион поиска')),
            ),
        );

        if (Geo::coveringType(Geo::COVERING_COUNTRIES)) {
            $templates['macros']['region']['t'] = _t('', 'Регион поиска: Страна / Область / Город');
            $templates['pages']['search']['macros']['city'] = array('t' => _t('', 'Город'));
            $templates['pages']['search']['macros']['country'] = array('t' => _t('', 'Страна'));
            $templates['pages']['search-category']['macros']['city'] = array('t' => _t('', 'Город'));
            $templates['pages']['search-category']['macros']['country'] = array('t' => _t('', 'Страна'));
        }

        return $templates;
    }

    /**
     * Инициализация компонента работы с логотипом магазина
     * @param integer $nShopID ID магазина
     * @return ShopsLogo объект
     */
    public function shopLogo($nShopID = 0)
    {
        static $i;
        if (!isset($i)) {
            $i = new ShopsLogo();
        }
        $i->setRecordID($nShopID);

        return $i;
    }

    /**
     * Включена ли премодерация магазинов
     * @return bool
     */
    public static function premoderation()
    {
        return config::sysAdmin('shops.premoderation', true, TYPE_BOOL);
    }

    /**
     * Включены ли категории магазинов (true), false - используются категории объявлений
     * @return bool
     */
    public static function categoriesEnabled()
    {
        return config::sysAdmin('shops.categories', false, TYPE_BOOL);
    }

    /**
     * Максимально допустимое кол-во категорий магазинов, связываемых с магазинами, 0 - без ограничений
     * @return integer
     */
    public static function categoriesLimit()
    {
        return config::sysAdmin('shops.categories.limit', 5, TYPE_UINT);
    }

    /**
     * Инициализация компонента обработки иконок основных категорий ShopsCategoryIcon
     * @param mixed $nCategoryID ID категории
     * @return ShopsCategoryIcon component
     */
    public static function categoryIcon($nCategoryID = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new ShopsCategoryIcon();
        }
        $i->setRecordID($nCategoryID);

        return $i;
    }

    /**
     * Формирование хлебных крошек
     * @param integer $nCategoryID ID категории (на основе которой выполняем формирование)
     * @param string $sMethodName имя метода
     * @param array $aOptions доп. параметры: city, region
     */
    protected function categoryCrumbs($nCategoryID, $sMethodName, array $aOptions = array())
    {
        $model = (static::categoriesEnabled() ? $this->model : BBS::model());
        $aData = $model->catParentsData($nCategoryID, array('id', 'title', 'keyword', 'breadcrumb', 'mtemplate'));
        if (!empty($aData)) {
            foreach ($aData as &$v) {
                # ссылка
                $aOptions['keyword'] = $v['keyword'];
                $v['link'] = static::url('search', $aOptions);
                # активируем
                $v['active'] = ($v['id'] == $nCategoryID);
                # хлебная крошка
                if ( ! static::categoriesEnabled()) {
                    $v['breadcrumb'] = '';
                }
            }
            unset($v);
        }
        if (isset($aOptions['keyword'])) {
            unset($aOptions['keyword']);
        }
        $aData = array(
                array(
                    'id'     => 0,
                    'title'  => '',
                    'breadcrumb'  => _t('search', 'Магазины'),
                    'link'   => static::url('search', $aOptions),
                    'active' => empty($aData)
                )
            ) + $aData;

        return $aData;
    }

    /**
     * Проверка данных магазина
     * @param integer $nShopID ID магазина
     * @param array $aData @ref данные магазина
     */
    public function validateShopData($nShopID = 0, &$aData = array())
    {
        $params = array(
            'cats'      => TYPE_ARRAY_UINT, # категории
            //'skype'     => array(TYPE_NOTAGS, 'len' => 32, 'len.sys' => 'shops.form.skype.limit'), # skype
            //'icq'       => array(TYPE_NOTAGS, 'len' => 20, 'len.sys' => 'shops.form.icq.limit'), # icq
            'phones'    => TYPE_ARRAY_NOTAGS, # телефоны
            'site'      => array(TYPE_TEXT, 'len' => 200, 'len.sys' => 'shops.form.site.limit'), # сайт
            'social'    => array(
                TYPE_ARRAY_ARRAY, # соц. сети
                't' => TYPE_UINT, # тип ссылки
                'v' => array(TYPE_TEXT, 'len' => 300, 'len.sys' => 'shops.form.social.limit'), # ссылка
            ),
            # адрес
            'region_id' => TYPE_UINT, # регион (город или 0)
            'addr_lat'  => TYPE_NUM, # координаты на карте
            'addr_lon'  => TYPE_NUM, # координаты на карте
            'contacts'  => TYPE_ARRAY_NOTAGS, # контакты
        );
        $titlesLang = static::titlesLang(); # Использовать мультиязычные поля
        if ( ! $titlesLang) {
            $params += $this->model->langShops;
        }

        $this->input->postm($params, $aData, (!bff::adminPanel() ? false : 'shop_'));
        if ($titlesLang) {
            $this->input->postm_lang($this->model->langShops, $aData, (!bff::adminPanel() ? false : 'shop_'));
        }

        if (bff::adminPanel()) {
            $aData['import'] = $this->input->post('shop_import', TYPE_BOOL); # доступность импорта
        }

        if (Request::isPOST()) {
            do {
                if ($titlesLang) {
                    # чистим мультиязычное название
                    $title = '';
                    $errors = array();
                    foreach ($this->locale->getLanguages() as $l) {
                        if (isset($aData['title'][$l])) {
                            $aData['title'][$l] = trim($aData['title'][$l], ' -');
                            if (empty($aData['title'][$l])) {
                                $errors['title'] = _t('shops', 'Укажите название магазина');
                                unset($aData['title'][$l]);
                            } else {
                                if (mb_strlen($aData['title'][$l]) < config::sys('shops.form.title.min', 2, TYPE_UINT)) {
                                    $errors['title'] = _t('shops', 'Название магазина слишком короткое');
                                    unset($aData['title'][$l]);
                                    continue;
                                }
                                $aData['title'][$l] = bff::filter('shops.form.title.validate', $aData['title'][$l]);

                                # проквочиваем название
                                $aData['title_edit'][$l] = $aData['title'][$l];
                                $aData['title'][$l] = HTML::escape($aData['title_edit'][$l]);
                            }
                        }
                    }
                    if (empty($aData['title'])) {
                        foreach ($errors as $k => $v) {
                            $this->errors->set($v, $k.'['.LNG.']');
                        }
                    } else {
                        if (isset($aData['title'][LNG])) {
                            $title = $aData['title'][LNG];
                        } else {
                            $title = $aData['title'];
                            $title = reset($title);
                        }
                    }

                    # чистим мультиязычное описание:
                    $errors = array();
                    foreach ($this->locale->getLanguages() as $l) {
                        if (isset($aData['descr'][$l])) {
                            if (mb_strlen($aData['descr'][$l]) < config::sys('shops.form.descr.min',12,TYPE_UINT)) {
                                $errors['descr'] = _t('shops', 'Опишите подробнее чем занимается ваш магазин');
                                unset($aData['descr'][$l]);
                                continue;
                            }
                            $aData['descr'][$l] = bff::filter('shops.form.descr.validate', $aData['descr'][$l]);
                        }
                    }
                    if (empty($aData['descr'])) {
                        foreach ($errors as $k => $v) {
                            $this->errors->set($v, $k.'['.LNG.']');
                        }
                    }

                } else {
                    $aData['title'] = trim($aData['title'], ' -');
                    if (empty($aData['title'])) {
                        $this->errors->set(_t('shops', 'Укажите название магазина'), 'title');
                    } else {
                        if (mb_strlen($aData['title']) < config::sys('shops.form.title.min', 2, TYPE_UINT)) {
                            $this->errors->set(_t('shops', 'Название магазина слишком короткое'), 'title');
                        }
                        $aData['title'] = bff::filter('shops.form.title.validate', $aData['title']);
                    }
                    $title = $aData['title'];

                    # чистим описание, дополнительно:
                    if (mb_strlen($aData['descr']) < config::sys('shops.form.descr.min',12,TYPE_UINT)) {
                        $this->errors->set(_t('shops', 'Опишите подробнее чем занимается ваш магазин'), 'descr');
                    }
                    $aData['descr'] = bff::filter('shops.form.descr.validate', $aData['descr']);

                    # проквочиваем название
                    $aData['title_edit'] = $aData['title'];
                    $aData['title'] = HTML::escape($aData['title_edit']);
                }

                # URL keyword
                $aData['keyword'] = trim(preg_replace('/[^a-z0-9\-]/', '', mb_strtolower(
                            func::translit($title)
                        )
                    ), '- '
                );

                # категории
                if (!static::categoriesEnabled()) {
                    unset($aData['cats']);
                } else {
                    if (empty($aData['cats'])) {
                        $this->errors->set(_t('shops', 'Укажите категорию магазина'), 'cats');
                    }
                }

                # чистим контакты
                Users::i()->cleanUserData($aData, array('phones', 'contacts', 'site'), array(
                        'phones_limit' => static::phonesLimit(),
                    )
                );

                # соц. сети (корректируем ссылки)
                $aSocial = array();
                $aSocialTypes = static::socialLinksTypes();
                foreach ($aData['social'] as $v) {
                    if (strlen($v['v']) >= 5 && array_key_exists($v['t'], $aSocialTypes)) {
                        if (stripos($v['v'], 'http') !== 0) {
                            $v['v'] = 'http://' . $v['v'];
                        }
                        $v['v'] = str_replace(array('"', '\''), '', $v['v']);
                        $aSocial[] = array(
                            't' => $v['t'],
                            'v' => $v['v'],
                        );
                    }
                }
                $limit = static::socialLinksLimit();
                if ($limit > 0 && sizeof($aSocial) > $limit) {
                    $aSocial = array_slice($aSocial, 0, $limit);
                }
                $aData['social'] = $aSocial;

                # регион
                if ($aData['region_id']) {
                    if (!Geo::isCity($aData['region_id'])) {
                        $this->errors->set(_t('shops', 'Город указан некорректно'), 'region');
                    }
                }
                if (!$aData['region_id']) {
                    $this->errors->set(_t('shops', 'Укажите регион деятельности магазина'), 'region');
                }
                if (!Geo::coveringType(Geo::COVERING_COUNTRY)) {
                    $regionData = Geo::regionData($aData['region_id']);
                    if (!$regionData || !Geo::coveringRegionCorrect($regionData)) {
                        $this->errors->set(_t('shops', 'Город указан некорректно'));
                        break;
                    }
                }

                # разворачиваем регион: region_id => reg1_country, reg2_region, reg3_city
                $aRegions = Geo::model()->regionParents($aData['region_id']);
                $aData = array_merge($aData, $aRegions['db']);

                # формируем URL магазина
                $sLink = static::url('shop.view', array(
                        'region' => $aRegions['keys']['region'],
                        'city'   => $aRegions['keys']['city']
                    ), true
                );
                if ($nShopID) {
                    $sLink .= $aData['keyword'] . '-' . $nShopID;
                } else {
                    # дополняем в ShopsModel::shopSave
                }
                $aData['link'] = $sLink;

                if ($titlesLang) {
                    # продублируем мультиязычные данные для незаполненных языков
                    foreach ($this->model->langShops as $k => $v) {
                        $def = isset($aData[$k][ LNG ]) ? $aData[$k][ LNG ] : false;
                        if ($def == false) {
                            $def = $aData[$k];
                            $def = reset($def);
                        }
                        foreach ($this->locale->getLanguages() as $l) {
                            if ( ! isset($aData[$k][$l])) {
                                $aData[$k][$l] = $def;
                            }
                        }
                    }
                }

                bff::hook('shops.shop.validate', array('id'=>$nShopID,'data'=>&$aData));

            } while (false);
        }
    }

    /**
     * Инициализация компонента ShopsSearchSphinx
     * @return ShopsSearchSphinx component
     */
    public function shopsSearchSphinx()
    {
        static $i;
        if (!isset($i)) {
            $i = new ShopsSearchSphinx();
        }

        return $i;
    }

    /**
     * Настройки Sphinx
     * @param array $settings
     */
    public function sphinxSettings(array $settings)
    {
        $this->shopsSearchSphinx()->moduleSettings($settings);
    }

    /**
     * Удаления магазина
     * @param integer $nShopID ID магазина
     * @return boolean
     */
    public function shopDelete($nShopID)
    {
        if (!$nShopID || empty($nShopID)) {
            return false;
        }
        $aData = $this->model->shopData($nShopID, array('id', 'user_id', 'logo', 'status'));
        if (empty($aData)) {
            return false;
        }

        # удаляем магазин
        $res = $this->model->shopDelete($nShopID);
        if (!$res) {
            return false;
        }

        if ($aData['user_id']) {
            # удаляем связь пользователя с магазином
            Users::model()->userSave($aData['user_id'], array('shop_id' => 0));
            # отвязываем связанные с магазином объявления
            BBS::model()->itemsUnlinkShop($nShopID);
            # актуализируем счетчик заявок
            if ($aData['status'] == static::STATUS_REQUEST) {
                $this->updateRequestsCounter(-1);
            }
        }

        # удаляем логотип
        $this->shopLogo($nShopID)->delete(false, $aData['logo']);

        return true;
    }

    /**
     * Получение списка доступных причин жалобы на магазин
     * @return array
     */
    public function getShopClaimReasons()
    {
        $list = bff::filter('shops.claim.reasons', array(
            1                 => _t('shops', 'Неверная рубрика'),
            2                 => _t('shops', 'Запрещенный товар/услуга'),
            8                 => _t('shops', 'Неверный адрес'),
            self::CLAIM_OTHER => _t('shops', 'Другое'),
        ));
        # Перемещаем пункт "Другое" в конец списка
        $tmp = array();
        foreach ($list as $k => &$v) {
            if (!is_array($v)) {
                $v = array('title' => $v);
            }
            if ($k === static::CLAIM_OTHER && !isset($v['priority'])) {
                $v['priority'] = 1000;
            }
            $tmp[$k] = $v;
        } unset($v);
        # Сортируем по priority + корректируем ключи
        func::sortByPriority($tmp, 'priority', 2);
        $list = $tmp;
        foreach ($list as $k => &$v) {
            $list[$k] = $v['title'];
        } unset($v);
        return $list;
    }

    /**
     * Актуализация счетчика необработанных жалоб на магазины
     * @param integer|null $increment
     */
    public function claimsCounterUpdate($increment)
    {
        if (empty($increment)) {
            $count = $this->model->claimsListing(array('viewed' => 0), true);
            config::save('shops_claims', $count, true);
        } else {
            config::saveCount('shops_claims', $increment, true);
        }
    }

    /**
     * Формирование текста описания жалобы, с учетом отмеченных причин
     * @param integer $nReasons битовое поле причин жалобы
     * @param string $sComment комментарий к жалобе
     * @return string
     */
    protected function getItemClaimText($nReasons, $sComment)
    {
        $reasons = $this->getShopClaimReasons();
        if (!empty($nReasons) && !empty($reasons)) {
            $res = array();
            foreach ($reasons as $rk => $rv) {
                if ($rk != self::CLAIM_OTHER && $rk & $nReasons) {
                    $res[] = $rv;
                }
            }
            if (($nReasons & self::CLAIM_OTHER) && !empty($sComment)) {
                $res[] = $sComment;
            }

            return join(', ', $res);
        }

        return '';
    }

    /**
     * Получение списка доступных типов для ссылок соц. сетей
     * @param boolean $bSelectOptions в формате HTML::selectOptions
     * @param integer $nSelectedID ID выбранного типа
     * @return array
     */
    public static function socialLinksTypes($bSelectOptions = false, $nSelectedID = 0)
    {
        $aTypes = bff::filter('shops.social.links.types', array(
            self::SOCIAL_LINK_FACEBOOK      => array(
                'title' => _t('shops', 'Facebook'),
                'icon'  => 'fb'
            ),
            self::SOCIAL_LINK_VKONTAKTE     => array(
                'title' => _t('shops', 'Вконтакте'),
                'icon'  => 'vk'
            ),
            self::SOCIAL_LINK_ODNOKLASSNIKI => array(
                'title' => _t('shops', 'Одноклассники'),
                'icon'  => 'od'
            ),
            self::SOCIAL_LINK_GOOGLEPLUS    => array(
                'title' => _t('shops', 'Google+'),
                'icon'  => 'gg'
            ),
            self::SOCIAL_LINK_YANDEX        => array(
                'title' => _t('shops', 'Yandex'),
                'icon'  => 'ya'
            ),
            self::SOCIAL_LINK_MAILRU        => array(
                'title' => _t('shops', 'Мой мир'),
                'icon'  => 'mm'
            ),
        ));
        func::sortByPriority($aTypes, 'priority', 2);
        foreach ($aTypes as $k=>$v) {
            if (!isset($v['id'])) {
                $aTypes[$k]['id'] = $k;
            }
        }

        if ($bSelectOptions) {
            return HTML::selectOptions($aTypes, $nSelectedID, false, 'id', 'title');
        }

        return $aTypes;
    }

    /**
     * Работа со счетчиком кол-ва новых запросов на открытие / закрепление магазина
     * @param integer $nIncrement , пример: -2, -1, 1, 2
     * @param boolean $bJoin true - закрепление, false - открытие
     */
    public function updateRequestsCounter($nIncrement, $bJoin = false)
    {
        config::saveCount('shops_requests', $nIncrement, true);
        config::saveCount('shops_requests_' . ($bJoin ? 'join' : 'open'), $nIncrement, true);
    }

    /**
     * Актуализация счетчика магазинов ожидающих модерации
     * @param integer|null $increment
     */
    public function updateModerationCounter($increment = null)
    {
        if (empty($increment)) {
            $count = $this->model->shopsModeratingCounter();
            config::save('shops_moderating', $count, true);
        } else {
            config::saveCount('shops_moderating', $increment, true);
        }
    }
    
    /**
     * Метод обрабатывающий ситуацию с блокировкой/разблокировкой пользователя
     * @param integer $nUserID ID пользователя
     * @param boolean $bBlocked true - заблокирован, false - разблокирован
     */
    public function onUserBlocked($nUserID, $bBlocked)
    {
        $aUserData = Users::model()->userData($nUserID, array('shop_id'));
        if (empty($aUserData['shop_id'])) {
            return;
        }

        if ($bBlocked) {
            # при блокировке пользователя -> блокируем его магазин
            $this->model->shopSave($aUserData['shop_id'], array(
                    'status_prev = status',
                    'status'         => self::STATUS_BLOCKED,
                    'blocked_reason' => _t('shops', 'Аккаунт пользователя заблокирован'),
                )
            );
        } else {
            # при разблокировке -> разблокируем
            $this->model->shopSave($aUserData['shop_id'], array(
                    'status = (CASE status_prev WHEN ' . self::STATUS_BLOCKED . ' THEN ' . self::STATUS_NOT_ACTIVE . ' ELSE status_prev END)',
                    'status_prev' => self::STATUS_BLOCKED,
                    //'blocked_reason' => '', # оставляем последнюю причину блокировки
                )
            );
        }
    }

    /**
     * Метод обрабатывающий ситуацию с закреплением магазина за пользователем
     * @param integer $nUserID ID пользователя
     * @param integer $nShopID ID магазина
     */
    public function onUserShopCreated($nUserID, $nShopID)
    {
        # Привязываем магазин к пользователю
        Users::model()->userSave($nUserID, array('shop_id' => $nShopID));
        if (bff::adminPanel()) {
            Users::i()->userSessionUpdate($nUserID, array('shop_id' => $nShopID), false);
        } else {
            $this->security->updateUserInfo(array('shop_id' => $nShopID));
        }

        # Привязываем объявления пользователя к магазину
        # - при включенной премодерации - привязка выполняется на этапе одобрения заявки
        if (!static::premoderation() && BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP)) {
            BBS::model()->itemsLinkShop($nUserID, $nShopID);
        }
    }

    # --------------------------------------------------------
    # Активация услуг

    /**
     * Активация услуги/пакета услуг для Магазина
     * @param integer $nItemID ID магазина
     * @param integer $nSvcID ID услуги/пакета услуг
     * @param mixed $aSvcData данные об услуге(*)/пакете услуг или FALSE
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return boolean true - успешная активация, false - ошибка активации
     */
    public function svcActivate($nItemID, $nSvcID, $aSvcData = false, array &$aSvcSettings = array())
    {
        if (!$nSvcID) {
            $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

            return false;
        }
        if (empty($aSvcData)) {
            $aSvcData = Svc::model()->svcData($nSvcID);
            if (empty($aSvcData)) {
                $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

                return false;
            }
        }

        # получаем данные о магазине
        if (empty($aShopData)) {
            $aShopData = $this->model->shopData($nItemID, bff::filter('shops.svc.activate.shop.fields', array(
                    'id',
                    'user_id',
                    'link',
                    'title',
                    'status', # ID, статус
                    'svc', # битовое поле активированных услуг
                    'reg2_region',
                    'reg3_city', # ID региона(области), ID города
                    'svc_fixed_to', # дата окончания "Закрепления"
                    'svc_marked_to',
                    'svc_abonement_id', # ID тарифа
                    'svc_abonement_expire', # дата окончания абонемента
                    'svc_abonement_one_time', # единоразовый тариф
                ))
            ); # дата окончания "Выделение"
        }

        # проверяем статус магазина, если абонемент то пропускаем активацию
        if (empty($aShopData) || ($aShopData['status'] != self::STATUS_ACTIVE && $nSvcID != self::SERVICE_ABONEMENT)) {
            $this->errors->set(_t('shops', 'Для указанного магазина невозможно активировать данную услугу'));

            return false;
        }

        # хуки
        $customActivation = bff::filter('shops.svc.activate', $nSvcID, $aSvcData, $nItemID, $aShopData, $aSvcSettings);
        if (is_bool($customActivation)) {
            return $customActivation;
        }

        # активируем пакет услуг
        if (isset($aSvcData['type']) && $aSvcData['type'] == Svc::TYPE_SERVICEPACK) {
            $aServices = (isset($aSvcData['svc']) ? $aSvcData['svc'] : array());
            if (empty($aServices)) {
                $this->errors->set(_t('shops', 'Неудалось активировать пакет услуг'));

                return false;
            }
            $aServicesID = array();
            foreach ($aServices as $v) {
                $aServicesID[] = $v['id'];
            }
            $aServices = Svc::model()->svcData($aServicesID, array('*'));
            if (empty($aServices)) {
                $this->errors->set(_t('shops', 'Неудалось активировать пакет услуг'));

                return false;
            }

            # проходимся по услугам, входящим в пакет
            # активируем каждую из них
            $nSuccess = 0;
            foreach ($aServices as $k => $v) {
                # при пакетной активации, период действия берем из настроек пакета услуг
                $v['cnt'] = $aSvcData['svc'][$k]['cnt'];
                if (!empty($v['cnt'])) {
                    $v['period'] = $v['cnt'];
                }
                $res = $this->svcActivateService($nItemID, $v['id'], $v, $aShopData, true, $aSvcSettings);
                if ($res) {
                    $nSuccess++;
                }
            }

            return true;
        } else {
            # активируем услугу
            return $this->svcActivateService($nItemID, $nSvcID, $aSvcData, $aShopData, false, $aSvcSettings);
        }
    }

    /**
     * Активация услуги для Магазина
     * @param integer $nItemID ID магазина
     * @param integer $nSvcID ID услуги
     * @param mixed $aSvcData данные об услуге(*) или FALSE
     * @param mixed $aShopData @ref данные о магазине или FALSE
     * @param boolean $bFromPack услуга активируется из пакета услуг
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return boolean|integer
     *      1, true - услуга успешно активирована,
     *      2 - услуга успешно активирована без необходимости списывать средства со счета пользователя
     *      false - ошибка активации услуги
     */
    protected function svcActivateService($nItemID, $nSvcID, $aSvcData = false, &$aShopData = false, $bFromPack = false, array &$aSvcSettings = array())
    {
        if (empty($nItemID) || empty($aShopData) || empty($nSvcID)) {
            $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

            return false;
        }
        if (empty($aSvcData)) {
            $aSvcData = Svc::model()->svcData($nSvcID);
            if (empty($aSvcData)) {
                $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

                return false;
            }
        }

        $nSvcID = intval($nSvcID);
        $aShopData['svc'] = intval($aShopData['svc']);

        # период действия услуги (в днях)
        # > при пакетной активации, период действия берется из настроек активируемого пакета услуг
        $nPeriodDays = (!empty($aSvcData['period']) ? intval($aSvcData['period']) : 1);
        if ($nPeriodDays < 1) {
            $nPeriodDays = 1;
        }

        $sNow = $this->db->now();
        $aUpdate = array();
        $mResult = true;
        switch ($nSvcID) {
            case self::SERVICE_FIX: # Закрепление
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aShopData['svc'] & $nSvcID) ? strtotime($aShopData['svc_fixed_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                # помечаем активацию услуги
                $aUpdate['svc_fixed'] = 1;
                # помечаем срок действия услуги
                $aUpdate['svc_fixed_to'] = date('Y-m-d H:i:s', $to);
                # ставим выше среди закрепленных
                $aUpdate['svc_fixed_order'] = $sNow;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aShopData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_MARK: # Выделение
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aShopData['svc'] & $nSvcID) ? strtotime($aShopData['svc_marked_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                # помечаем срок действия услуги
                $aUpdate['svc_marked_to'] = date('Y-m-d H:i:s', $to);
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aShopData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_ABONEMENT: # Абонемент
            {
                # считаем дату окончания действия услуги
                $abonementID = $aSvcSettings['abonement_id'];
                $to = strtotime('+' . $aSvcSettings['abonement_period'] . ' month',
                    # если услуга уже активна => продлеваем срок действия
                    $aShopData['svc_abonement_id'] == $abonementID
                    ? strtotime($aShopData['svc_abonement_expire']) :
                    # если неактивна => активируем на требуемый период от текущей даты
                    time()
                );
                $abonement = $this->model->abonementData($abonementID);

                # проверка на повторное использование единоразового тарифа
                $aOneTime = func::unserialize($aShopData['svc_abonement_one_time']);
                if (!empty($aOneTime)) {
                    if ($abonement['one_time'] && in_array($abonementID, $aOneTime)) {
                        $this->errors->set(_t('shops', 'Нет возможности выбрать данный абонемент'));
                        return false;
                    }
                }

                # если тариф единоразовый - отмечаем его использование
                if ($abonement['one_time']) {
                    $aOneTime[] = $abonementID;
                    $aUpdate['svc_abonement_one_time'] = serialize($aOneTime);
                }
                # деактивируем старый пакет
                if ($aShopData['svc_abonement_id'] && $aShopData['svc_abonement_id'] != $abonementID) {
                    $aShopData = $this->model->abonementDeactivate($aShopData['id']) + $aShopData;
                }
                # если бессрочный пакет
                $termless = false;
                if ($abonement['price_free'] && $abonement['price_free_period'] == 0) {
                    $aUpdate['svc_abonement_termless'] = 1;
                    $termless = true;
                }

                # помечаем активацию услуг
                $aUpdate['svc'] = ($aShopData['svc'] | $nSvcID);
                if ($abonement['svc_mark']) {
                    $aUpdate['svc_marked_to'] = $termless ? static::SVC_TERMLESS_DATE : date('Y-m-d H:i:s', $to);
                    $aUpdate['svc'] |= self::SERVICE_MARK;
                }
                if ($abonement['svc_fix']) {
                    $aUpdate['svc_fixed_to'] = $termless ? static::SVC_TERMLESS_DATE : date('Y-m-d H:i:s', $to);
                    $aUpdate['svc_fixed_order'] = $sNow;
                    $aUpdate['svc'] |= self::SERVICE_FIX;
                }

                # устанавливаем ID тарифа
                $aUpdate['svc_abonement_id'] = $abonementID;
                # помечаем срок действия тарифа
                $aUpdate['svc_abonement_expire'] = date('Y-m-d H:i:s', $to);
                # устанавливаем ID тарифа
                $aUpdate['svc_abonement_auto_id'] = $aSvcSettings['abonement_period'];

                if (empty($aSvcSettings['email_not_send']))
                {
                    # отправляем письмо об активации подписки
                    $aUserData = Users::model()->userData($aShopData['user_id'], array('email', 'user_id', 'user_id_ex', 'last_login'));
                    $aMailData = array(
                        'email' => $aUserData['email'],
                        'user_id' => $aShopData['user_id'],
                        'shop_id' => $aShopData['id'],
                        'shop_title' => $aShopData['title'],
                        'shop_link' => $aShopData['link'].'?alogin='.Users::loginAutoHash($aUserData),
                        'tariff_title' => $abonement['title'],
                        'tariff_expire' => ! empty($aUpdate['svc_abonement_termless']) ? _t('shops', 'бессрочно') : _t('', 'до [date]', array('date' => tpl::date_format2($to)))
                    );
                    bff::sendMailTemplate($aMailData, 'shops_abonement_activated', $aUserData['email']);
                }
            }
            break;
            default: # другая услуга
            {
                bff::hook('shops.svc.activate.custom', $nSvcID, $aSvcData, $nItemID, $aShopData, array('fromPack'=>$bFromPack, 'settings'=>&$aSvcSettings, 'update'=>&$aUpdate));
            }
            break;
        }

        $res = $this->model->shopSave($nItemID, $aUpdate);
        if (!empty($res)) {
            # актуализируем данные о магазине для корректной пакетной активации услуг
            if (!empty($aUpdate)) {
                foreach ($aUpdate as $k => $v) {
                    $aShopData[$k] = $v;
                }
            }
            # снимем лишние объявления с публикации
            $this->model->abonementItemsUnpublicate($nItemID);
            return $mResult;
        }

        return false;
    }

    /**
     * Формируем описание счета активации услуги (пакета услуг)
     * @param integer $nItemID ID магазина
     * @param integer $nSvcID ID услуги
     * @param array|boolean $aData false или array('item'=>array('id',...),'svc'=>array('id','type'))
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return string
     */
    public function svcBillDescription($nItemID, $nSvcID, $aData = false, array &$aSvcSettings = array())
    {
        if ($nSvcID == self::SERVICE_ABONEMENT) {
            $aSvc = $this->model->abonementData($aSvcSettings['abonement_id']);
            $aSvc['type'] = Svc::TYPE_SERVICE;
        } else {
            $aSvc = (!empty($aData['svc']) ? $aData['svc'] :
                Svc::model()->svcData($nSvcID));
        }

        $aShop = (!empty($aData['item']) ? $aData['item'] :
            $this->model->shopData($nItemID, array('id', 'title', 'link')));

        $sLink = (!empty($aShop['link']) ? 'href="' . $aShop['link'] . '" class="j-bills-shops-item-link" data-item="' . $nItemID . '"' : 'href=""');

        if ($aSvc['type'] == Svc::TYPE_SERVICE) {
            switch ($nSvcID) {
                case self::SERVICE_FIX:
                {
                    return _t('shops', 'Закрепление магазина<br /><small><a [link]>[title]</a></small>', array(
                            'link'  => $sLink,
                            'title' => $aShop['title']
                        )
                    );
                }
                break;
                case self::SERVICE_MARK:
                {
                    return _t('shops', 'Выделение магазина<br /><small><a [link]>[title]</a></small>', array(
                            'link'  => $sLink,
                            'title' => $aShop['title']
                        )
                    );
                }
                break;
                case self::SERVICE_ABONEMENT:
                {
                    return _t('shops', 'Покупка абонемента по тарифу "[tariff]" для магазина<br /><small><a [link]>[title]</a></small>', array(
                            'link'  => $sLink,
                            'title' => $aShop['title'],
                            'tariff' => $aSvc['title']
                        )
                    );
                }
                break;
            }
        } else {
            if ($aSvc['type'] == Svc::TYPE_SERVICEPACK) {
                return _t('shops', 'Пакет услуг "[pack]" <br /><small><a [link]>[title]</a></small>',
                    array('pack' => $aSvc['title'], 'link' => $sLink, 'title' => $aShop['title'])
                );
            }
        }
        return bff::filter('shops.svc.description.custom', '', $nSvcID, $aSvc, $aShop, $sLink);
    }

    /**
     * Инициализация компонента обработки иконок услуг/пакетов услуг ShopsSvcIcon
     * @param mixed $nSvcID ID услуги / пакета услуг
     * @return ShopsSvcIcon component
     */
    public static function svcIcon($nSvcID = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new ShopsSvcIcon();
        }
        $i->setRecordID($nSvcID);

        return $i;
    }

    /**
     * Включена ли услуга "Абонемент"
     * @return bool
     */
    public static function abonementEnabled()
    {
        $enabled = (bff::servicesEnabled() && config::sysAdmin('shops.abonement', false, TYPE_BOOL));
        if (!bff::adminPanel() && $enabled) {
            static $abonementsExists;
            if (!isset($abonementsExists)) {
                # есть ли 1+ включенный тариф
                $abonementsExists = (static::model()->abonementsList(array(), true) > 0);
            }
            if (!$abonementsExists) {
                $enabled = false;
            }
        }
        return $enabled;
    }

    /**
     * Максимальное кол-во объявлений магазина, при отсутствии активного тарифного плана.
     * 0 - публикация объявлений без активного тарифного плана недоступна.
     * @return integer
     */
    public static function abonementLimitDefault()
    {
        return config::sysAdmin('shops.abonement.default.limit', 100, TYPE_UINT);
    }

    /**
     * Проверка превышения лимита для магазина
     * @param integer $shopID ID магазина
     * @param integer $count кол-во проверяемых объявлений
     * @return bool true - лимит превышен, false - нет
     */
    public function abonementLimitExceed($shopID, $count = 1)
    {
        if ( ! static::abonementEnabled()) {
            return false;
        }
        $data = $this->model->shopData($shopID, array('svc_abonement_id'));
        if (empty($data) || ! $data['svc_abonement_id']) {
            $limit = static::abonementLimitDefault();
            if ( ! $limit) {
                # публикация объявлений без активного тарифного плана недоступна
                return true;
            }
        } else {
            $abonement = $this->model->abonementData($data['svc_abonement_id']);
            if (empty($abonement['items'])) {
                # нет ограничения
                return false;
            }
            $limit = $abonement['items'];
        }
        $items = BBS::model()->itemsCount(array('shop_id' => $shopID, 'status' => BBS::STATUS_PUBLICATED));
        $items += $count;
        if ($items <= $limit) {
            return false;
        }
        return true;
    }

    /**
     * Инициализация компонента обработки иконок тарифов услуги "Абонемент"
     * @param mixed $nAbonementID ID тарифа
     * @return ShopsSvcIcon component
     */
    public static function abonementIcon($nAbonementID = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new ShopsSvcIcon();
        }
        $i->setTable(TABLE_SHOPS_ABONEMENTS);
        $i->setRecordID($nAbonementID);

        return $i;
    }

    /**
     * Период: 1 раз в сутки
     */
    public function svcCron()
    {
        if (!bff::cron()) {
            return;
        }

        $this->model->svcCron();
    }

    /**
     * Обработка копирования данных локализации
     */
    public function onLocaleDataCopy($from, $to)
    {
        # услуги (services, packs)
        $svc = Svc::model();
        $data = $svc->svcListing(0, $this->module_name, array(), false);
        foreach ($data as $v) {
            if (!empty($v['settings'])) {
                $langFields = ($v['type'] == Svc::TYPE_SERVICE ?
                    $this->model->langSvcServices :
                    $this->model->langSvcPacks);

                foreach ($langFields as $kk => $vv) {
                    if (isset($v['settings'][$kk][$from])) {
                        $v['settings'][$kk][$to] = $v['settings'][$kk][$from];
                    }
                }
                $svc->svcSave($v['id'], array('settings' => $v['settings']));
            }
        }
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function onGeoUrlTypeChanged($prevType, $nextType)
    {
        $this->model->shopsGeoUrlTypeChanged($prevType, $nextType);
    }

    /**
     * Кол-во доступных телефонов (0 - без ограничений)
     * @return integer
     */
    public static function phonesLimit()
    {
        return config::sysAdmin('shops.phones.limit', 5, TYPE_UINT);
    }

    /**
     * Кол-во доступных ссылок соц.сетей (0 - без ограничений)
     * @return integer
     */
    public static function socialLinksLimit()
    {
        return config::sysAdmin('shops.social.limit', 5, TYPE_UINT);
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('shop'.DS.'logo', 'images') => 'dir-split', # изображения магазинов
            bff::path('shop'.DS.'cats', 'images') => 'dir-only', # изображения категорий
            bff::path('svc', 'images')   => 'dir-only', # изображения платных услуг
            bff::path('tmp', 'images')   => 'dir-only', # tmp
        ));
    }
}