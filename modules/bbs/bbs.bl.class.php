<?php

use bff\db\Dynprops;

# cookie-ключ для избранных ОБ
define('BBS_FAV_COOKIE', DB_PREFIX . 'fav');

abstract class BBSBase_ extends Module
implements IModuleWithSvc
{
    /** @var BBSModel */
    public $model = null;
    public $securityKey = 'f59d7393ccf683db2a63b795f75cd9e3';

    # Статус объявления
    const STATUS_NOTACTIVATED = 1; # не активировано
    const STATUS_PUBLICATED = 3; # опубликованное
    const STATUS_PUBLICATED_OUT = 4; # истекший срок публикации
    const STATUS_BLOCKED = 5; # заблокированное
    const STATUS_DELETED = 6; # удалено

    # Настройки категорий
    const CATS_ROOTID = 1; # ID "Корневой категории" (изменять не рекомендуется)
    const CATS_TYPES_EX = false; # Использовать расширенные типы категорий (TABLE_BBS_CATEGORIES_TYPES)

    /**
     * Максимальная глубина вложенности категорий.
     *  - при изменении, не забыть привести в соответствие столбцы cat_id(1-n) в таблице TABLE_BBS_ITEMS
     *  - минимальное значение = 1
     */
    const CATS_MAXDEEP = 4;

    # Тип размещения объявления
    const TYPE_OFFER = 0; # Тип размещения: "предлагаю"
    const TYPE_SEEK = 1; # Тип размещения: "ищу"

    # Тип владельца объявления
    const OWNER_PRIVATE = 1; # Частное лицо (Владелец, Собственник)
    const OWNER_BUSINESS = 2; # Бизнес (Агенство, Посредник)

    # Тип пользователя, публикующего объявление
    const PUBLISHER_USER = 'user';
    const PUBLISHER_SHOP = 'shop';
    const PUBLISHER_USER_OR_SHOP = 'user-or-shop';
    const PUBLISHER_USER_TO_SHOP = 'user-to-shop';

    # Тип доп. модификаторов цены
    const PRICE_EX_PRICE = 0; # Базовая настройка цены
    const PRICE_EX_MOD = 1; # Модификатор (Торг, По результатам собеседования)
    const PRICE_EX_EXCHANGE = 2; # Обмен
    const PRICE_EX_FREE = 4; # Бесплатно (Даром)
    const PRICE_EX_AGREED = 8; # Договорная

    # ID Услуг
    const SERVICE_UP = 1; # поднятие
    const SERVICE_MARK = 2; # выделение
    const SERVICE_FIX = 4; # закрепление
    const SERVICE_PREMIUM = 8; # премиум
    const SERVICE_PRESS = 16; # в прессу
    const SERVICE_QUICK = 32; # срочно
    const SERVICE_LIMIT = 512; # платное расширение лимитов

    # Публикация объявления в прессе
    const PRESS_ON = true; # доступна ли услуга "публикации объявления в прессе"
    const PRESS_STATUS_PAYED = 1; # статус: публикация в прессе оплачена
    const PRESS_STATUS_PUBLICATED = 2; # статус: опубликовано в прессе
    const PRESS_STATUS_PUBLICATED_EARLIER = 3; # раннее опубликованные (только для фильтра)

    # Жалобы
    const CLAIM_OTHER = 1024; # тип жалобы: "Другое"

    # Блокировки
    const BLOCK_OTHER   = 1024; # тип блокировки: "Другое"
    const BLOCK_FOREVER = 2048; # пожизненная блокировка

    # Типы отображения списка
    const LIST_TYPE_LIST    = 1; # строчный вид
    const LIST_TYPE_GALLERY = 2; # галерея
    const LIST_TYPE_MAP     = 3; # карта

     # Доступность импорта объявлений
    const IMPORT_ACCESS_ADMIN  = 0; # администратор
    const IMPORT_ACCESS_CHOSEN = 1; # избранные магазины
    const IMPORT_ACCESS_ALL    = 2; # все

    # Лимитирование объявлений
    const LIMITS_NONE = 0;     # без ограничений
    const LIMITS_COMMON = 1;   # общий лимит
    const LIMITS_CATEGORY = 2; # в категорию

    # типы времени для услуги автоподнятия объявления
    const SVC_UP_AUTO_SPECIFIED = 1; # Точно указанное время
    const SVC_UP_AUTO_INTERVAL  = 2; # Промежуток времени с до, с указанием интервала

    # варианты стоимости услуги закрепления
    const SVC_FIX_PERIOD  = 1; # стоимость указывается за период
    const SVC_FIX_PER_DAY = 2; # стоимость указывается за день (пользователь сам вводит период действия)

    const PUBLICATION_SOON_LEFT = 518400; # менее 6 дней осталось до завершения публикации

    public function init()
    {
        parent::init();

        $this->module_title = _t('bbs','Объявления');

        bff::autoloadEx(array(
                'BBSItemImages'   => array('app', 'modules/bbs/bbs.item.images.php'),
                'BBSItemsImport'  => array('app', 'modules/bbs/bbs.items.import.php'),
                'BBSYandexMarket' => array('app', 'modules/bbs/bbs.yandex.market.php'),
                'BBSTranslate'    => array('app', 'modules/bbs/bbs.item.translate.php'),
                'BBSCategoryIcon' => array('app', 'modules/bbs/bbs.category.icon.php'),
                'BBSItemVideo'    => array('app', 'modules/bbs/bbs.item.video.php'),
                'BBSItemComments' => array('app', 'modules/bbs/bbs.item.comments.php'),
                'BBSItemsSearchSphinx' => array('app', 'modules/bbs/bbs.items.search.sphinx.php'),
                'BBSSvcIcon'      => array('app', 'modules/bbs/bbs.svc.icon.php'),
            )
        );
        # инициализируем модуль дин. свойств
        if (strpos(bff::$event, 'dynprops') === 0) {
            $this->dp();
        }
    }

    public function sendmailTemplates()
    {
        $aTemplates = array(
            'bbs_item_activate'      => array(
                'title'       => _t('bbs','Объявления: активация объявления'),
                'description' => _t('bbs','Уведомление, отправляемое <u>незарегистрированному пользователю</u> после добавления объявления'),
                'vars'        => array(
                    '{name}'          => _t('users','Имя'),
                    '{email}'         => _t('','Email'),
                    '{activate_link}' => _t('bbs','Ссылка активации объявления'),
                )
            ,
                'impl'        => true,
                'priority'    => 10,
                'enotify'     => 0, # всегда
                'group'       => 'bbs',
            ),
            'bbs_item_sendfriend'    => array(
                'title'       => _t('bbs','Объявления: отправить другу'),
                'description' => _t('bbs','Уведомление, отправляемое по указанному email адресу'),
                'vars'        => array(
                    '{item_id}'    => _t('bbs','ID объявления'),
                    '{item_title}' => _t('bbs','Заголовок объявления'),
                    '{item_link}'  => _t('bbs','Ссылка на объявление'),
                )
            ,
                'impl'        => true,
                'priority'    => 11,
                'enotify'     => -1,
                'group'       => 'bbs',
            ),
            'bbs_item_deleted'       => array(
                'title'       => _t('bbs','Объявления: удаление объявления'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> в случае удаления объявления модератором'),
                'vars'        => array(
                    '{name}'       => _t('users','Имя'),
                    '{email}'      => _t('','Email'),
                    '{item_id}'    => _t('bbs','ID объявления'),
                    '{item_title}' => _t('bbs','Заголовок объявления'),
                    '{item_link}'  => _t('bbs','Ссылка на объявление'),
                )
            ,
                'impl'        => true,
                'priority'    => 14,
                'enotify'     => 0, # всегда
                'group'       => 'bbs',
            ),
            'bbs_item_photo_deleted' => array(
                'title'       => _t('bbs','Объявления: удаление фотографии объявления'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> в случае удаления фотографии объявления модератором'),
                'vars'        => array('{name}'       => _t('users','Имя'),
                                       '{email}'      => _t('','Email'),
                                       '{item_id}'    => _t('bbs','ID объявления'),
                                       '{item_title}' => _t('bbs','Заголовок объявления'),
                                       '{item_link}'  => _t('bbs','Ссылка на объявление'),
                )
            ,
                'impl'        => true,
                'priority'    => 15,
                'enotify'     => 0, # всегда
                'group'       => 'bbs',
            ),
            'bbs_item_blocked'       => array(
                'title'       => _t('bbs','Объявления: блокировка объявления'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> в случае блокировки объявления модератором'),
                'vars'        => array(
                    '{name}'           => _t('users','Имя'),
                    '{email}'          => _t('','Email'),
                    '{item_id}'        => _t('bbs','ID объявления'),
                    '{item_title}'     => _t('bbs','Заголовок объявления'),
                    '{item_link}'      => _t('bbs','Ссылка на объявление'),
                    '{blocked_reason}' => _t('bbs','Причина блокировки'),
                )
            ,
                'impl'        => true,
                'priority'    => 16,
                'enotify'     => 0, # всегда
                'group'       => 'bbs',
            ),
            'bbs_item_unpublicated_soon'       => array(
                'title'       => _t('bbs','Объявления: уведомление о завершении публикации одного объявления'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> с оповещением о завершении публикации его объявления'),
                'vars'        => array(
                    '{name}'           => _t('users','Имя'),
                    '{item_id}'        => _t('bbs','ID объявления'),
                    '{item_title}'     => _t('bbs','Заголовок объявления'),
                    '{item_link}'      => _t('bbs','Ссылка на объявление'),
                    '{days_in}'        => _t('bbs','Кол-во дней до завершения публикации'),
                    '{publicate_link}' => _t('bbs','Ссылка продления публикации'),
                    '{edit_link}     ' => _t('bbs','Ссылка редактирования объявления'),
                    '{svc_up}'         => _t('bbs','Ссылка "поднять"'),
                    '{svc_quick}'      => _t('bbs','Ссылка "сделать срочным"'),
                    '{svc_fix}'        => _t('bbs','Ссылка "закрепить"'),
                    '{svc_mark}'       => _t('bbs','Ссылка "выделить"'),
                    '{svc_press}'      => _t('bbs','Ссылка "печать в прессе"'),
                    '{svc_premium}'    => _t('bbs','Ссылка "премиум"'),
                )
            ,
                'impl'        => true,
                'priority'    => 17,
                'enotify'     => Users::ENOTIFY_NEWS,
                'group'       => 'bbs',
            ),
            'bbs_item_unpublicated_soon_group'       => array(
                'title'       => _t('bbs','Объявления: уведомление о завершении публикации нескольких объявлений'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> с оповещением о завершении публикации его объявлений'),
                'vars'        => array(
                    '{name}'           => _t('users','Имя'),
                    '{count}'          => _t('bbs','Кол-во объявлений,').'<div class="desc">'._t('bbs','например - 10').'</div>',
                    '{count_items}'    => _t('bbs','Кол-во объявлений,').'<div class="desc">'._t('bbs','например - 10 объявлений').'</div>',
                    '{days_in}'        => _t('bbs','Кол-во дней до завершения публикации'),
                    '{publicate_link}' => _t('bbs','Ссылка продления публикации'),
                )
            ,
                'impl'        => true,
                'priority'    => 18,
                'enotify'     => Users::ENOTIFY_NEWS,
                'group'       => 'bbs',
            ),
            'bbs_item_upfree' => array(
                'title'       => _t('bbs','Объявления: уведомление о возможности бесплатного поднятия объявления'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> в случае доступности бесплатного поднятия его объявления'),
                'vars'        => array(
                    '{name}'           => _t('users','Имя'),
                    '{item_id}'        => _t('bbs','ID объявления'),
                    '{item_title}'     => _t('bbs','Заголовок объявления'),
                    '{item_link}'      => _t('bbs','Ссылка на объявление'),
                    '{item_link_up}'   => _t('bbs','Ссылка бесплатного поднятия объявления'),
                )
            ,
                'impl'        => true,
                'priority'    => 19,
                'enotify'     => Users::ENOTIFY_NEWS,
                'group'       => 'bbs',
            ),
            'bbs_item_upfree_group' => array(
                'title'       => _t('bbs','Объявления: уведомление о возможности бесплатного поднятия нескольких объявлений'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> в случае доступности бесплатного поднятия нескольких его объявлений'),
                'vars'        => array(
                    '{name}'           => _t('users','Имя'),
                    '{count}'          => _t('bbs','Кол-во объявлений,').'<div class="desc">'._t('bbs','например - 10').'</div>',
                    '{count_items}'    => _t('bbs','Кол-во объявлений,').'<div class="desc">'._t('bbs','например - 10 объявлений').'</div>',
                    '{items_link_up}'  => _t('bbs','Ссылка бесплатного поднятия всех объявлений'),
                )
            ,
                'impl'        => true,
                'priority'    => 20,
                'enotify'     => Users::ENOTIFY_NEWS,
                'group'       => 'bbs',
            ),
        );
        if( ! static::PRESS_ON){
            unset($aTemplates['bbs_item_unpublicated_soon']['vars']['{svc_press}']);
        }

        if (static::commentsEnabled()) {
            $aTemplates['bbs_item_comment'] = array(
                'title'       => _t('bbs','Объявления: новый комментарий к объявлению'),
                'description' => _t('bbs','Уведомление, отправляемое <u>пользователю</u> в случае нового комментария к объявлению'),
                'vars'        => array('{name}'       => _t('users','Имя'),
                                       '{email}'      => _t('','Email'),
                                       '{item_id}'    => _t('bbs','ID объявления'),
                                       '{item_title}' => _t('bbs','Заголовок объявления'),
                                       '{item_link}'  => _t('bbs','Ссылка на объявление'),
                )
            ,
                'impl'        => true,
                'priority'    => 16,
                'enotify'     => Users::ENOTIFY_BBS_COMMENTS,
                'group'       => 'bbs',
            );
        }

        Sendmail::i()->addTemplateGroup('bbs', _t('bbs', 'Объявления'), 1);

        $packs = Svc::model()->svcListing(Svc::TYPE_SERVICEPACK, $this->module_name);
        foreach ($packs as $v) {
            if (empty($v['keyword']) || empty($v['on'])) continue;
            $aTemplates['bbs_item_unpublicated_soon']['vars']['{pack_'.$v['keyword'].'}'] = _t('bbs','Ссылка пакета "[title]"', array('title' => $v['title']));
        }

        return $aTemplates;
    }

    /**
     * Shortcut
     * @return BBS
     */
    public static function i()
    {
        return bff::module('bbs');
    }

    /**
     * Shortcut
     * @return BBSModel
     */
    public static function model()
    {
        return bff::model('bbs');
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
        return bff::router()->url('bbs-'.$key, $opts, ['dynamic'=>$dynamic,'module'=>'bbs']);
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
                    't'      => _t('bbs', 'Поиск (все категории)'),
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
                    't'       => _t('bbs', 'Поиск в категории'),
                    'list'    => true,
                    'i'       => true,
                    'macros'  => array(
                        'category'           => array('t' => _t('bbs', 'Название текущей категории')),
                        'category+parent'    => array('t' => _t('bbs', 'Название текущей категории + категории выше')),
                        'categories'         => array('t' => _t('bbs', 'Название всех категорий')),
                        'categories.reverse' => array('t' => _t('bbs', 'Название всех категорий<br />(обратный порядок)')),
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
                    'inherit' => true,
                    'landing' => true,
                ),
                'view'            => array(
                    't'      => _t('bbs', 'Просмотр объявления'),
                    'list'   => false,
                    'i'      => true,
                    'macros' => array(
                        'id'                 => array('t' => _t('bbs', 'ID объявления')),
                        'name'               => array('t' => _t('bbs', 'Имя пользователя')),
                        'title'              => array('t' => _t('', 'Заголовок')),
                        'description'        => array('t' => _t('bbs', 'Описание (до 150 символов)')),
                        'price'              => array('t' => _t('bbs', 'Стоимость')),
                        'city'               => array('t' => _t('geo', 'Город')),
                        'region'             => array('t' => _t('geo', 'Область')),
                        'country'            => array('t' => _t('geo', 'Страна')),
                        'category'           => array('t' => _t('bbs', 'Текущая категория объявления')),
                        'category+parent'    => array('t' => _t('bbs', 'Текущая категория + категория выше')),
                        'categories'         => array('t' => _t('bbs', 'Название всех категорий')),
                        'categories.reverse' => array('t' => _t('bbs', 'Название всех категорий<br />(обратный порядок)')),
                    ),
                    'fields' => array(
                        'share_title'       => array(
                            't'    => _t('bbs', 'Заголовок (поделиться в соц. сетях)'),
                            'type' => 'text',
                        ),
                        'share_description' => array(
                            't'    => _t('bbs', 'Описание (поделиться в соц. сетях)'),
                            'type' => 'textarea',
                        ),
                        'share_sitename'    => array(
                            't'    => _t('bbs', 'Название сайта (поделиться в соц. сетях)'),
                            'type' => 'text',
                        ),
                    ),
                ),
                'add'             => array(
                    't'             => _t('bbs', 'Добавление объявления'),
                    'list'          => false,
                    'i'             => true,
                    'macros'        => array(),
                    'macros.ignore' => array('region'),
                    'fields'  => array(
                        'breadcrumb' => array(
                            't'    => _t('', 'Хлебная крошка'),
                            'type' => 'text',
                        ),
                        'titleh1' => array(
                            't'    => _t('', 'Заголовок H1'),
                            'type' => 'text',
                        ),
                    ),
                ),
                'user-items'      => array(
                    't'      => _t('bbs', 'Объявления пользователя'),
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'name'   => array('t' => _t('bbs', 'Имя пользователя')),
                        'country' => array('t' => _t('bbs', 'Страна пользователя')),
                        'region' => array('t' => _t('bbs', 'Регион пользователя')),
                    ),
                    'fields'  => array(
                        'titleh1' => array(
                            't'    => _t('', 'Заголовок H1'),
                            'type' => 'text',
                        ),
                    ),
                ),
            ),
            'macros' => array(
                'region' => array('t' => _t('bbs', 'Регион поиска')),
            ),
        );

        if (Geo::coveringType(Geo::COVERING_COUNTRIES)) {
            $templates['macros']['region']['t'] = _t('bbs', 'Регион поиска: Страна / Область / Город');
            $templates['pages']['search']['macros']['city'] = array('t' => _t('geo', 'Город'));
            $templates['pages']['search']['macros']['country'] = array('t' => _t('geo', 'Страна'));
            $templates['pages']['search-category']['macros']['city'] = array('t' => _t('geo', 'Город'));
            $templates['pages']['search-category']['macros']['country'] = array('t' => _t('geo', 'Страна'));
        }

        return $templates;
    }

    /**
     * Включена ли премодерация объявлений
     * @return bool
     */
    public static function premoderation()
    {
        return config::sysAdmin('bbs.premoderation', true, TYPE_BOOL);
    }

    /**
     * Включена ли премодерация объявлений при редактировании
     * @return bool
     */
    public static function premoderationEdit()
    {
        return static::premoderation() && config::sysAdmin('bbs.premoderation.edit', false, TYPE_BOOL);
    }

    /**
     * Получение настройки: доступный тип пользователя публикующего объявление
     * @param array|string|null $type проверяемый тип
     * @return mixed
     */
    public static function publisher($type = null)
    {
        $sys = config::sysAdmin('bbs.publisher', static::PUBLISHER_USER_OR_SHOP, TYPE_NOTAGS);
        if (!bff::moduleExists('shops') && ($sys === static::PUBLISHER_SHOP || $sys === static::PUBLISHER_USER_OR_SHOP)) {
            $sys = static::PUBLISHER_USER;
        }
        if (empty($type)) {
            return $sys;
        }

        return (is_array($type) ? in_array($sys, $type, true) : ($type === $sys));
    }

    /**
     * Включен ли вертикальный фильтр
     * @return bool
     */
    public static function filterVertical()
    {
        $enabled = config::sysTheme('bbs.search.filter.vertical', false, TYPE_BOOL);

        return $enabled;
    }

    /**
     * Варианты списка объявлений
     * @return array
     */
    public static function itemsSearchListTypes()
    {
        $types = bff::filter('bbs.search.list.type.list', [
            static::LIST_TYPE_LIST => [
                'title' => _t('bbs', 'Список'),
                't'     => _t('search','Списком'),
                'i'     => 'fa fa-th-list',
                'is_map'=> false,
                'image' => ['sizes' => [BBSItemImages::szSmall, BBSItemImages::szMedium], 'extensions' => ['svg']],
            ],
            static::LIST_TYPE_GALLERY => [
                'title' => _t('bbs', 'Галерея'),
                't'     => _t('search','Галереей'),
                'i'     => 'fa fa-th',
                'is_map'=> false,
                'image' => ['sizes' => [BBSItemImages::szSmall, BBSItemImages::szMedium], 'extensions' => ['svg']],
            ],
            static::LIST_TYPE_MAP => [
                'title' => _t('bbs', 'Карта'),
                't'     => _t('search','На карте'),
                'i'     => 'fa fa-map-marker',
                'is_map'=> true,
                'image' => ['sizes' => [BBSItemImages::szSmall, BBSItemImages::szMedium], 'extensions' => ['svg']],
            ],
        ]);
        func::sortByPriority($types, 'priority', true);
        foreach ($types as $k=>&$v) {
            $v['id'] = $k;
            $v['a']  = 0;
        } unset($v);
        return $types;
    }

    /**
     * Включена ли услуга платного расширения лимитов
     * @return bool
     */
    public static function limitsPayedEnabled()
    {
        return config::sysAdmin('bbs.limits.payed', false, TYPE_BOOL);
    }

    /**
     * Набор значений для услуги платного расширения лимитов
     * @return array
     */
    public static function limitsPayedNumbers()
    {
        return bff::filter('bbs.items.limits.payed.numbers', array(
            1 => array('id' => 1, 'items' => 1,  'price' => 0, 'checked' => 1),
            2 => array('id' => 2, 'items' => 5,  'price' => 0, 'checked' => 1),
            3 => array('id' => 3, 'items' => 10, 'price' => 0, 'checked' => 1),
            4 => array('id' => 4, 'items' => 20, 'price' => 0, 'checked' => 1),
            5 => array('id' => 5, 'items' => 50, 'price' => 0, 'checked' => 1),
        ));
    }

    /**
     * Включена ли возможность периодического импорта объявлений из внешнего URL
     * @return bool
     */
    public static function importUrlEnabled()
    {
        return config::sys('bbs.import.url', true, TYPE_BOOL);
    }

    /**
     * Включена ли возможность загрузки CSV файла на фронтенде
     * @return bool
     */
    public static function importCsvFrontendEnabled()
    {
        return config::sysAdmin('bbs.import.csv.frontend', true, TYPE_BOOL);
    }

    /**
     * Причины блокировки для модератора
     * @return array
     */
    public static function blockedReasons()
    {
        $list = bff::filter('bbs.items.blocked.reasons', array(
            1                 => _t('bbs', 'Неверная рубрика'),
            2                 => _t('bbs', 'Некорректный заголовок'),
            4                 => _t('bbs', 'Запрещенный товар/услуга'),
            8                 => _t('bbs', 'Объявление не актуально'),
            static::BLOCK_OTHER => _t('bbs', 'Другая причина'),
            static::BLOCK_FOREVER => _t('bbs', 'Заблокировано навсегда'),
        ));
        # Перемещаем пункт "Другая причина" в конец списка
        $tmp = array();
        foreach ($list as $k => &$v) {
            if ( ! is_array($v)) {
                $v = array('title' => $v);
            }
            if ( ! isset($v['priority'])) {
                if ($k === static::BLOCK_OTHER) {
                    $v['priority'] = 1000;
                } else if ($k === static::BLOCK_FOREVER) {
                    $v['priority'] = 1001;
                }
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
     * Использовать мультиязычный контент для объявлений
     * @return mixed
     */
    public static function translate()
    {
        return config::sysAdmin('bbs.translate', '', TYPE_NOTAGS) == 'google';
    }

    /**
     * Массив соответствия полей для генерации автозаголовков для таблиц категорий и объявлений
     * @return array
     */
    public static function autoTplFields()
    {
        return array('tpl_title_list' => 'title_list', 'tpl_title_view' => 'title');
    }

    /**
     * Проверка типа публикующего пользователя
     * @param integer $shopID ID магазина пользователя, публикующего объявление
     * @param string $shopUseField название поля, отвечающего за использование магазина для публикации
     * @return integer итоговый ID магазина, закрепляемый за публикуемым объявлением
     */
    public function publisherCheck($shopID, $shopUseField = 'shop')
    {
        switch (static::publisher()) {
            # только пользователь (добавление объявлений доступно сразу, объявления размещаются только от "частного лица")
            case static::PUBLISHER_USER:
            {
                return 0;
            }
            break;
            # только магазин (добавление объявлений доступно после открытия магазина, только от "магазина")
            case static::PUBLISHER_SHOP:
            {
                if (!$shopID) {
                    if (bff::adminPanel()) {
                        $this->errors->set(_t('bbs', 'Указанный пользователь не создал магазин'), 'email');
                    } else {
                        $this->errors->reloadPage();
                    }

                    return 0;
                }
            }
            break;
            # пользователь и магазин (добавление объявлений доступно сразу только от "частного лица", после открытия магазина - объявления размещаются только от "магазина")
            case static::PUBLISHER_USER_TO_SHOP:
            {
                return ($shopID && bff::moduleExists('shops') ? $shopID : 0);
            }
            break;
            # пользователь или магазин (добавление объявлений доступно сразу только от "частного лица",
            # после открытия магазина - объявления размещаются от "частного лица" или "магазина")
            case static::PUBLISHER_USER_OR_SHOP:
            {
                $byShop = $this->input->postget($shopUseField, TYPE_BOOL);
                if (!$byShop || !$shopID || !bff::moduleExists('shops')) {
                    return 0;
                }
            }
            break;
        }

        return $shopID;
    }

    /**
     * Инициализация компонента работы с дин. свойствами
     * @return \bff\db\Dynprops объект
     */
    public function dp()
    {
        static $dynprops = null;
        if (isset($dynprops)) {
            return $dynprops;
        }

        # подключаем "Динамические свойства"
        $dynprops = new Dynprops('cat_id',
            TABLE_BBS_CATEGORIES,
            TABLE_BBS_CATEGORIES_DYNPROPS,
            TABLE_BBS_CATEGORIES_DYNPROPS_MULTI,
            1); # полное наследование
        $this->attachComponent('dynprops', $dynprops);

        $dynprops->setSettings(bff::filter('bbs.dp.settings', array(
                'module_name'          => $this->module_name,
                'typesAllowed'         => array(
                    Dynprops::typeCheckboxGroup,
                    Dynprops::typeRadioGroup,
                    Dynprops::typeRadioYesNo,
                    Dynprops::typeCheckbox,
                    Dynprops::typeSelect,
                    Dynprops::typeInputText,
                    Dynprops::typeTextarea,
                    Dynprops::typeNumber,
                    Dynprops::typeRange,
                ),
                'langs'                => $this->locale->getLanguages(false),
                'langText'             => array(
                    'yes'    => _t('bbs', 'Да'),
                    'no'     => _t('bbs', 'Нет'),
                    'all'    => _t('bbs', 'Все'),
                    'select' => _t('', 'Выбрать'),
                ),
                'cache_method'         => 'BBS_dpSettingsChanged',
                'typesAllowedParent'   => array(Dynprops::typeSelect),
                /**
                 * Настройки доступных int/text столбцов динамических свойств для хранения числовых/тестовых данных.
                 * При изменении, не забыть привести в соответствие столбцы f(1-n) в таблице TABLE_BBS_ITEMS
                 */
                'datafield_int_last'   => 15,
                'datafield_text_first' => 16,
                'datafield_text_last'  => 20,
                'searchRanges'         => true,
                'cacheKey'             => true,
            ))
        );
        $dynprops->extraSettings(bff::filter('bbs.dp.settings.extra', array(
                'in_seek'   => array('title' => _t('bbs', 'заполнять в объявлениях типа "Ищу"'), 'input' => 'checkbox'),
                'num_first' => array('title' => _t('bbs', 'отображать перед наследуемыми (первым)'), 'input' => 'checkbox'),
            ))
        );
        $dynprops->setCurrentLanguage(LNG);

        return $dynprops;
    }

    /**
     * Получаем дин. свойства категории
     * @param integer $nCategoryID ID категории
     * @param boolean $bResetCache обнулить кеш
     * @return mixed
     */
    public function dpSettings($nCategoryID, $bResetCache = false)
    {
        if ($nCategoryID <= 0) {
            return array();
        }

        $cache = Cache::singleton('bbs-dp', 'file');
        $cacheKey = 'cat-dynprops-' . LNG . '-' . $nCategoryID;
        if ($bResetCache) {
            # сбрасываем кеш настроек дин. свойств категории
            return $cache->delete($cacheKey);
        } else {
            if (($aSettings = $cache->get($cacheKey)) === false) { # ищем в кеше
                $aSettings = $this->dp()->getByOwner($nCategoryID, true, true, false);
                $cache->set($cacheKey, $aSettings); # сохраняем в кеш
            }

            return $aSettings;
        }
    }

    /**
     * Метод вызываемый модулем \bff\db\Dynprops, в момент изменения настроек дин. свойств категории
     * @param integer $nCategoryID id категории
     * @param integer $nDynpropID id дин.свойства
     * @param string $sEvent событие, генерирующее вызов метода
     * @return mixed
     */
    public function dpSettingsChanged($nCategoryID, $nDynpropID, $sEvent)
    {
        if (empty($nCategoryID)) {
            return false;
        }
        $this->dpSettings($nCategoryID, true);
    }

    /**
     * Формирование SQL запроса для сохранения дин.свойств
     * @param integer $nCategoryID ID подкатегории
     * @param string $sFieldname ключ в $_POST массиве
     * @return array
     */
    public function dpSave($nCategoryID, $sFieldname = 'd')
    {
        $aData = $this->input->post($sFieldname, TYPE_ARRAY);

        $aDynpropsData = array();
        foreach ($aData as $props) {
            foreach ($props as $id => $v) {
                $aDynpropsData[$id] = $v;
            }
        }

        $aDynprops = $this->dp()->getByID(array_keys($aDynpropsData), true);

        return $this->dp()->prepareSaveDataByID($aDynpropsData, $aDynprops, 'update', true);
    }

    /**
     * Формирование формы редактирования/фильтра дин.свойств
     * @param integer $nCategoryID ID категории
     * @param boolean $bSearch формирование формы поиска
     * @param array|boolean $aData данные или FALSE
     * @param array $aExtra доп.данные
     * @param bool $adminPanel контекст админ панели
     * @return string HTML template
     */
    protected function dpForm($nCategoryID, $bSearch = true, $aData = array(), $aExtra = array(), $adminPanel = null)
    {
        if (empty($nCategoryID)) {
            return '';
        }

        if (is_null($adminPanel)) {
            $adminPanel = bff::adminPanel();
        }

        if ($adminPanel) {
            if ($bSearch) {
                $aForm = $this->dp()->form($nCategoryID, $aData, true, true, 'd', 'search.inline', false, $aExtra);
            } else {
                if ( ! empty($aData['moderated_data'])) {
                    $aExtra['compare'] = $aData['moderated_data'];
                }
                $aForm = $this->dp()->form($nCategoryID, $aData, true, false, 'd', 'form.table', false, $aExtra);
            }
        } else {
            if (!$bSearch) {
                $aForm = $this->dp()->form($nCategoryID, $aData, true, false, 'd', 'item.form.dp', $this->module_dir_tpl, $aExtra);
            }
        }

        return (!empty($aForm['form']) ? $aForm['form'] : '');
    }

    /**
     * Отображение дин. свойств
     * @param integer $nCategoryID ID категории
     * @param array $aData данные
     */
    public function dpView($nCategoryID, $aData)
    {
        $sKey = 'd';
        if (!bff::adminPanel()) {
            $aForm = $this->dp()->form($nCategoryID, $aData, true, false, $sKey, 'item.view.dp', $this->module_dir_tpl);
        } else {
            $aForm = $this->dp()->form($nCategoryID, $aData, true, false, $sKey, 'view.table');
        }

        return (!empty($aForm['form']) ? $aForm['form'] : '');
    }

    /**
     * Подготовка запроса полей дин. свойств на основе значений "cache_key"
     * @param string $sPrefix префикс таблицы, например "I."
     * @param int $nCategoryID ID категории
     * @return string
     */
    public function dpPrepareSelectFieldsQuery($sPrefix = '', $nCategoryID = 0)
    {
        if (empty($nCategoryID) || $nCategoryID<0) {
            return '';
        }

        $fields = array();
        foreach($this->dpSettings($nCategoryID) as $v)
        {
            $f = $sPrefix.$this->dp()->datafield_prefix.$v['data_field'];
            if (!empty($v['cache_key'])) {
               $f .= ' as `'.$v['cache_key'].'`';
            }
            $fields[] = $f;
        }
        return (!empty($fields) ? join(', ', $fields) : '');
    }

    /**
     * Подготовка данных для заполнения шаблона автозаголовка
     * @param integer $categoryID категория
     * @param string $format шаблон
     * @param int|array $dp @ref дин. свойства
     * @param string $lang язык
     * @return array данные для заполнения шаблона
     */
    public function dpPrepareTpl($categoryID, $format, & $dp = 0, $lang = LNG)
    {
        $key = 'd';
        # Получим и кешируем дин. св. для категории
        static $cat;
        if ( ! isset($cat[$categoryID][$lang])) {
            if ($lang != LNG) $this->dp()->setCurrentLanguage($lang);
            $cat[$categoryID][$lang] = $this->dp()->getByOwner($categoryID, true, true, false);
            if ($lang != LNG) $this->dp()->setCurrentLanguage(LNG);
        }
        $dp = $cat[$categoryID][$lang];
        $result = array();
        # игнорируем макросы offer и seek
        $format = strtr($format, array(':offer' => '', ':seek' => ''));
        foreach ($dp as $k => $v) {
            # добавим дин. св. найденное по хеш ключу
            if ( ! empty($v['cache_key']) && mb_strpos($format, '{'.$v['cache_key'].'}') !== false) {
                $result[ $v['cache_key'] ] = array(
                    'name' => $key.'['.$v['cat_id'].']['.$v['id'].']',
                    'keys' => array($key, $v['cat_id'], $v['id']),
                    'id'   => $v['id'],
                    'f'    => $this->dp()->datafield_prefix.$v['data_field'],
                );
            }
            # добавим дин. св. найденное по ID
            if (mb_strpos($format, '{'.$v['id'].'}') !== false){
                $result[ $v['id'] ] = array(
                    'name' => $key.'['.$v['cat_id'].']['.$v['id'].']',
                    'keys' => array($key, $v['cat_id'], $v['id']),
                    'id'   => $v['id'],
                    'f'    => $this->dp()->datafield_prefix.$v['data_field'],
                );
            }
        }
        return $result;
    }

    /**
     * Формирование автозаголовка по шаблону
     * @param integer $categoryID категория
     * @param string $format шаблон
     * @param array $data данные для заполнения
     * @param string $lang язык
     * @return string
     */
    public function dpFillTpl($categoryID, $format, $data = array(), $lang = LNG)
    {
        # Извлечение значения из данных по ключу (рекурсивно для массивов d[13][91])
        $extractValue = function($data, $key) use(& $extractValue){
            if (is_array($key)) {
                $fst = reset($key);
                if (isset($data[$fst])) {
                    if (count($key) > 1) {
                        array_shift($key);
                        return $extractValue($data[$fst], $key);
                    } else {
                        return is_array($data[$fst]) ? $data[$fst] : $this->input->cleanTextPlain($data[$fst]);
                    }
                }
            } else {
                if (isset($data[$key])) {
                    return is_array($data[$key]) ? $data[$key] : $this->input->cleanTextPlain($data[$key]);
                }
            }
            return false;
        };

        # Получение названия валют с кешированием
        static $currenciesCache;
        $currency = function($id) use(& $currenciesCache, $lang) {
            if ( ! isset($currenciesCache[$id])) {
                $currenciesCache[$id] = Site::model()->currencyData($id, true);
            }
            return isset($currenciesCache[$id]['title_short'][$lang]) ? $currenciesCache[$id]['title_short'][$lang] : '';
        };

        # Получение названия городов с кешированием
        static $cityCache;
        $city = function($id, $field) use(&$cityCache, $lang) {
            if ( ! isset($cityCache[$id])) {
                $cityCache[$id] = Geo::model()->regionData(array('id' => $id), true);
            }
            if ( ! isset($cityCache[$id][$field])) return '';
            if (is_array($cityCache[$id][$field])) {
                return isset($cityCache[$id][$field][$lang]) ? $cityCache[$id][$field][$lang] : '';
            }
            return $cityCache[$id][$field];
        };

        # Получение названия станций метро с кешированием
        static $metroCache;
        $metro = function($id) use(& $metroCache, $lang) {
            if ( ! isset($metroCache[$id])) {
                $metroCache[$id] = Geo::model()->metroData($id, true);
            }
            return isset($metroCache[$id]['title'][$lang]) ? $metroCache[$id]['title'][$lang] : '';
        };

        # Получение названия районов с кешированием
        static $districtsCache;
        $district = function($id) use(& $districtsCache, $lang) {
            if ( ! isset($districtsCache[$id])) {
                $districtsCache[$id] = Geo::model()->districtData($id, true);
            }
            return isset($districtsCache[$id]['title'][$lang]) ? $districtsCache[$id]['title'][$lang] : '';
        };

        # Получение названия категорий с кешированием
        static $catCache;
        $catTitle = function($id) use(&$catCache, $lang) {
            if ( ! isset($catCache[$id][$lang])) {
                $catCache[$id][$lang] = $this->model->catDataByFilter(array('id' => $id, 'lang' => $lang), array('title'));
            }
            return isset($catCache[$id][$lang]['title']) ? $catCache[$id][$lang]['title'] : '';
        };

        # Получение парент категорий с кешированием
        static $pathCache;
        $catPath = function($id) use(&$pathCache, $lang) {
            if ( ! isset($pathCache[$id][$lang])) {
                $data = $this->model->catParentsData($id, array('id', 'title', 'numlevel'), true, true, $lang);
                if ( ! empty($data)) {
                    $pathCache[$id][$lang] = array();
                    foreach($data as $v) {
                        $pathCache[$id][$lang][ $v['numlevel'] ] = $v['title'];
                    }
                }
            }
            return isset($pathCache[$id][$lang]) ? $pathCache[$id][$lang] : array();
        };

        # Получение прикрепленных дин. свойств по значению с кешированием
        static $dpChildren;
        $dpChild = function($parent, $value) use(&$dpChildren) {
            if ( ! isset($dpChildren[$parent][$value])) {
                $res = $this->dp()->getByParentIDValuePairs(array(array('parent_id' => $parent, 'parent_value'=>$value)));
                if (isset($res[$parent][$value])) {
                    $dpChildren[$parent][$value] = $res[$parent][$value];
                }
            }
            return isset($dpChildren[$parent][$value]) ? $dpChildren[$parent][$value] : array();
        };

        $result = '';
        if (empty($format)) return '';
        $prepare = $this->dpPrepareTpl($categoryID, $format, $dp, $lang); # данные для заполнения
        $view = explode('|', $format);                                    # разделитель полей в шаблоне
        foreach ($view as $v) {
            preg_match_all('/\{([\w:\-\.]+)\}/', $v, $m);
            if ( ! empty($m[1])) {
                foreach ($m[1] as $kk => $vv) {
                    # обработаем макросы offer и seek
                    $pos = mb_strpos($vv, ':');
                    if ($pos !== false) {
                        $offerSeek = mb_substr($vv, $pos + 1);
                        $vv = mb_substr($vv, 0, $pos);
                        if ( ! isset($data['cat_type'])) continue 2;
                        switch ($offerSeek) {
                            case 'offer':
                                if ($data['cat_type'] != static::TYPE_OFFER)  continue 3;
                                break;
                            case 'seek':
                                if ($data['cat_type'] != static::TYPE_SEEK)  continue 3;
                                break;
                        }
                    }
                    # прикрепленное дин св.
                    $dpsub = false;
                    $pos = mb_strpos($vv, '.sub');
                    if ($pos !== false) {
                        $dpsub = true;
                        $vv = mb_substr($vv, 0, $pos);
                    }
                    $val = '';
                    # заполняем для дин. свойств
                    if (isset($prepare[$vv])) {
                        $keys = $prepare[$vv]['keys'];
                        $id = $prepare[$vv]['id'];
                        if (isset($data[ $prepare[$vv]['f'] ])) {
                            $val = $data[ $prepare[$vv]['f'] ];
                        } else {
                            $val = $extractValue($data, $keys);
                        }
                        if ($dpsub) {
                            # для прикрепленного
                            if (empty($dp[$id]['parent']) || empty($dp[$id]['multi'])) continue 2;
                            $child = $dpChild($id, $val); # данные о выбранном прикрепленном дин. св.

                            if (empty($child['id']) || empty($child['multi'])) continue 2;
                            $val = '';

                            if (isset($data[ $child['data_field'] ])) {
                                $childVal = $data[ $child['data_field'] ];
                            } else {
                                # заменим id исходного на id выбранного прикрепленного
                                $childKeys = $keys;
                                foreach($childKeys as & $vvv) {
                                    if ($vvv == $id) {
                                        $vvv = $child['id'];
                                    }
                                } unset($vvv);
                                $childVal = $extractValue($data, $childKeys); # значение выбранного прикрепленного дин. св.
                            }
                            foreach($child['multi'] as $ml) {
                                if($ml['value'] == $childVal){
                                    $val = $ml['name'];
                                    break;
                                }
                            }
                            if (empty($val))
                                continue 2;
                        } else {
                            if (empty($val)) {
                                continue 2;
                            }
                            if (!empty($data['cat_type']) && empty($dp[$id]['in_seek'])) {
                                continue 2;
                            }
                            if (!empty($dp[$id]['multi'])) {
                                if (is_array($val)) {
                                    $aval = $val;
                                    $val = '';
                                    $ares = array();
                                    foreach($dp[$id]['multi'] as $ml) {
                                        if (in_array($ml['value'], $aval)) {
                                            $ares[] = $ml['name'];
                                        }
                                    }
                                    if ( ! empty($ares)) {
                                        $val = join(' ', $ares);
                                    }
                                } else {
                                    foreach($dp[$id]['multi'] as $ml) {
                                        if($ml['value'] == $val){
                                            $val = $ml['name'];
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        # общие поля для любой категории
                        switch ($vv) {
                            case 'price':
                                if (empty($data['price'])) continue 3;
                                if (empty($data['price_curr'])) continue 3;
                                $val = tpl::currency($data['price']).' '.$currency($data['price_curr']);
                                break;
                            case 'category':
                                $val = $catTitle($categoryID);
                                if (empty($val)) continue 3;
                                break;
                            case 'geo.city':
                                if (empty($data['city_id'])) continue 3;
                                $val = $city($data['city_id'], 'title');
                                break;
                            case 'geo.city.in':
                                if (empty($data['city_id'])) continue 3;
                                $val = $city($data['city_id'], 'declension');
                                break;
                            case 'geo.metro':
                                if (empty($data['metro_id'])) continue 3;
                                $val = $metro($data['metro_id']);
                                break;
                            case 'geo.district':
                                if (empty($data['district_id'])) continue 3;
                                $val = $district($data['district_id']);
                                break;
                            default:
                                if (mb_substr($vv, 0, 9) == 'category-') {
                                    $n = mb_substr($vv, 9);
                                    $path = $catPath($categoryID);
                                    if (empty($path)) break;
                                    if ($n == 'parent') {
                                        end($path);
                                        $val = prev($path);
                                    } else {
                                        $n = intval($n);
                                        if (isset($path[$n])) {
                                            $val = $path[$n];
                                        }
                                    }
                                }
                                break;
                        }
                    }
                    if ($val) {
                        $v = str_replace($m[0][$kk], $val, $v);
                    }
                }
            }
            $result .= $v;
        }
        return $result;
    }

    /**
     * Поиск ближайшего родителя с заполненными наблонами
     * @param integer $categoryID категория
     * @param array $fields для каких полей искать родителя
     * @param array $data @ref данные о категории (заполняем значение шаблонов)
     * @param bool $includingSelf анализировать и указанную категорию или только родителей
     * @return int ID найденного родителя
     */
    public function catNearestParent($categoryID, $fields, &$data, $includingSelf = true)
    {
        if (empty($categoryID)) return 0;
        if (empty($fields)) return 0;

        # анализировать флаг tpl_title_enabled или нет
        $isEnabled = true;
        if (in_array('tpl_descr_list', $fields) && count($fields) == 1) {
            $isEnabled = false;
        }

        if ($includingSelf) {
            $empty = true;
            foreach ($fields as $f) {
                if ( ! empty($data[$f])) {
                    $empty = false;
                }
            }
            if ( ! $empty) {
                if ($isEnabled && empty($data['tpl_title_enabled'])) return 0;
                return $categoryID;
            }
        }
        if ($isEnabled) {
            $fields[] = 'tpl_title_enabled';
        }
        # найдем родителей
        if ( ! in_array('id', $fields)) $fields[] = 'id';
        $parents = $this->model->catParentsData($categoryID, $fields, $includingSelf);
        unset($fields['id'], $fields['tpl_title_enabled']);
        foreach ($fields as $f) {
            if ( ! isset($data[$f])) {
                $data[$f] = '';
            }
        }
        $result = 0;
        $parents = array_reverse($parents);
        $cur = reset($parents);
        if ($isEnabled) {
            if ( ! $cur['tpl_title_enabled']) {
                return 0;
            }
            $data['tpl_title_enabled'] = 1;
        }
        # запишем данные
        foreach ($parents as $v) {
            if ($isEnabled && ! $v['tpl_title_enabled']) continue;
            foreach ($fields as $f) {
                if ( ! empty($v[$f]) && empty($data[$f])) {
                    $data[$f] = $v[$f];
                    $result = $v['id'];
                }
            }
        }
        return $result;
    }

    /**
     * Инициализация компонента BBSItemImages
     * @param integer $nItemID ID объявления
     * @return BBSItemImages component
     */
    public function itemImages($nItemID = 0)
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSItemImages();
        }
        $i->setRecordID($nItemID);

        return $i;
    }

    /**
     * Допустимое кол-во фотографий
     * @param bool $max максимальное (true), минимальное (false)
     * @return mixed integer
     */
    public static function itemsImagesLimit($max = true)
    {
        return config::sysAdmin('bbs.items.images.limit.'.($max ? 'max' : 'min'), ($max ? 20 : 4), TYPE_UINT);
    }

    /**
     * Пересчет hash-сумм изображений объявлений
     * @param array $params
     */
    public function itemsImagesHashUpdate($params)
    {
        if (!bff::cron()) return;

        $force = (!empty($params['force']) ? true : false);
        $this->itemImages()->updateFilesHash($force);
    }

    /**
     * Инициализация компонента BBSItemVideo
     * @return BBSItemVideo component
     */
    public function itemVideo()
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSItemVideo();
        }

        return $i;
    }

    /**
     * Инициализация компонента BBSItemComments
     * @return BBSItemComments component
     */
    public function itemComments()
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSItemComments();
        }

        return $i;
    }

    /**
     * Включены ли комментарии
     * @return bool
     */
    public static function commentsEnabled()
    {
        return config::sysAdmin('bbs.comments', true, TYPE_BOOL);
    }

    /**
     * Инициализация компонента BBSItemsSearchSphinx
     * @return BBSItemsSearchSphinx component
     */
    public function itemsSearchSphinx()
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSItemsSearchSphinx();
        }

        return $i;
    }

    /**
     * Настройки Sphinx
     * @param array $settings
     */
    public function sphinxSettings(array $settings)
    {
        $this->itemsSearchSphinx()->moduleSettings($settings);
    }

    /**
     * Инициализация компонента импорта/экспорта объявлений
     * @return BBSItemsImport component
     */
    public function itemsImport()
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSItemsImport();
            $i->init();
        }

        return $i;
    }

    /**
     * Доступна ли возможность редактирования категории при редактировании ОБ
     * @return bool
     */
    public static function categoryFormEditable()
    {
        return config::sysAdmin('bbs.form.category.edit', false, TYPE_BOOL);
    }

    /**
     * Инициализация компонента обработки иконок основных категорий BBSCategoryIcon
     * @param mixed $nCategoryID ID категории
     * @return BBSCategoryIcon component
     */
    public static function categoryIcon($nCategoryID = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSCategoryIcon();
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
        $aData = $this->model->catParentsData($nCategoryID, array('id', 'title', 'keyword', 'breadcrumb', 'landing_url', 'mtemplate'));
        if (!empty($aData)) {
            foreach ($aData as &$v) {
                # ссылка
                $aOptions['keyword'] = $v['keyword'];
                $aOptions['landing_url'] = $v['landing_url'];
                $v['link'] = static::url('items.search', $aOptions);
                # название
                //$v['title'] = $v['title'];
                # активируем
                $v['active'] = ($v['id'] == $nCategoryID);
                # хлебная крошка + макрос {category}
                if ($sMethodName == 'view') {
                    if (empty($v['breadcrumb'])) { $v['breadcrumb'] = '{category}'; }
                    $v['breadcrumb'] = strtr($v['breadcrumb'], array('{category}'=>$v['title']));
                }
            }
            unset($v);
        } else {
            if ($sMethodName == 'search') {
                $aData = array(array('id' => 0, 'breadcrumb' => _t('search', 'Объявления'), 'active' => true));
            } else {
                $aData = array();
            }
        }

        return $aData;
    }

    /**
     * Подготовка данных списка ОБ
     * @param array $aItems @ref данные о найденных ОБ
     * @param integer $nListType тип списка (static::LIST_TYPE_)
     * @param integer $nNumStart изначальный порядковый номер
     */
    protected function itemsListPrepare(array &$aItems, $nListType, $nNumStart = 1)
    {
        # формируем URL изображений
        $aListTypes = static::itemsSearchListTypes();
        if ( ! isset($aListTypes[$nListType])) {
            reset($aListTypes);
            $nListType = key($aListTypes);
        }
        $aImage = $aListTypes[$nListType]['image'];
        $oImages = $this->itemImages();
        $urlDefaults = [];
        foreach ($aImage['sizes'] as $size) {
            $urlDefaults['img_'.$size] = $oImages->urlDefault($size);
            foreach ($aImage['extensions'] as $sizeExt) {
                $urlDefaults['img_'.$size.':'.$sizeExt] = $oImages->urlDefault($size.':'.$sizeExt);
            }
        }

        $i = $nNumStart;
        foreach ($aItems as &$item) {
            # порядковый номер (для карты)
            $item['num'] = $i++;
            # подставляем заглушку для изображения
            if ( ! $item['imgs']) {
                foreach ($urlDefaults as $defField=>$defUrl) {
                    $item[$defField] = $defUrl;
                }
            }
            # форматируем дату публикации
            $item['publicated'] = tpl::datePublicated($item['publicated'], 'Y-m-d H:i:s', true, '<br />'); # первичная публикация
            $item['publicated_last'] = tpl::datePublicated($item['publicated_last'], 'Y-m-d H:i:s', true, '<br />'); # последнее поднятие
        }
        unset($item);
    }

    /**
     * Получаем данные о категории для формы добавления/редактирования объявления
     * @param int $nCategoryID ID категории
     * @param array $aItemData параметры объявления
     * @param array $aFieldsExtra дополнительно необходимые данные о категории
     * @param bool $adminPanel контекст админ панели
     * @return array
     */
    protected function itemFormByCategory($nCategoryID, $aItemData = array(), $aFieldsExtra = array(), $adminPanel = null)
    {
        if (is_null($adminPanel)) {
            $adminPanel = bff::adminPanel();
        }

        # получаем данные о категории:
        $aFields = array(
            'id',
            'pid',
            'addr',
            'photos',
            'subs',
            'price',
            'price_sett',
            'seek',
            'type_offer_form',
            'type_seek_form',
            'owner_business',
            'owner_private_form',
            'owner_business_form',
            'regions_delivery',
            'tpl_title_view',
            'tpl_title_enabled',
        );
        if (!empty($aFieldsExtra)) {
            $aFields = array_merge($aFields, $aFieldsExtra);
            $aFields = array_unique($aFields);
        }

        $nCategoryID = $this->model->catToReal($nCategoryID);

        $aData = $this->model->catDataByFilter(array('id' => $nCategoryID), $aFields);
        if (empty($aData)) {
            return array();
        }

        if ($aData['subs'] > 0) {
            # есть подкатегории => формируем список подкатегорий
            if ($adminPanel) {
                $aData['cats'] = $this->model->catSubcatsData($nCategoryID, array('sel' => 0, 'empty' => _t('', 'Выбрать')));
            }
            $aData['types'] = false;
        } else {
            # формируем форму дин. свойств:
            $aData['dp'] = $this->dpForm($nCategoryID, false, $aItemData, array(), $adminPanel); // todo memory leak
            # формируем список типов:
            if (static::CATS_TYPES_EX) {
                $aData['types'] = $this->model->cattypesByCategory($nCategoryID);
            } else {
                $aData['types'] = $this->model->cattypesSimple($aData, false);
            }
            if (empty($aData['types'])) {
                $aData['types'] = false;
            }
            $this->catNearestParent($nCategoryID, array('tpl_title_view'), $aData);
            if ( ! empty($aData['tpl_title_view'])) {
                $aData['tpl_data']= $this->dpPrepareTpl($nCategoryID, $aData['tpl_title_view']);
            }
        }

        $aData['edit'] = !empty($aItemData['id']);
        # корректируем необходимые данные объявления
        $aData['item'] = $this->input->clean_array($aItemData, array(
                'cat_type'   => TYPE_UINT,
                'price'      => TYPE_PRICE,
                'price_curr' => TYPE_UINT,
                'price_ex'   => TYPE_UINT,
                'owner_type' => TYPE_UINT,
                'regions_delivery' => TYPE_BOOL,
            )
        );
        if ($adminPanel) {
            if (isset($aItemData['moderated_data'])) {
                $aData['moderated_data'] = $aItemData['moderated_data'];
            }
            $aData['form'] = $this->viewPHP($aData, 'admin.form.category');
        } else {
            if (!$aData['edit']) {
                $aData['item']['price'] = '';
            }
            # цена:
            if ( ! empty($aData['price']) ) {
                $aData['price_label'] = ( ! empty($aData['price_sett']['title'][LNG]) ? $aData['price_sett']['title'][LNG] : _t('item-form', 'Цена') );
                $aData['price_curr_selected'] = ( $aData['edit'] && $aData['item']['price_curr'] ? $aData['item']['price_curr'] : ( ! empty($aData['price_sett']['curr']) ? $aData['price_sett']['curr'] : Site::currencyDefault('id') ) );
            }
            # доступный тип владельца (Частное лицо/Бизнес):
            {
                $aData['owner_types'] = array(
                    static::OWNER_PRIVATE => ( ! empty($aData['owner_private_form']) ? $aData['owner_private_form'] : _t('bbs','Частное лицо')),
                );
                if ($aData['owner_business']) {
                    $aData['owner_types'][static::OWNER_BUSINESS] = ( ! empty($aData['owner_business_form']) ? $aData['owner_business_form'] : _t('bbs','Бизнес'));
                }
                if (empty($aData['item']['owner_type'])) $aData['item']['owner_type'] = static::OWNER_PRIVATE;
            }
            $aData['form'] = $this->viewPHP($aData, 'item.form.cat.form');
            $aData['owner'] = $this->viewPHP($aData, 'item.form.cat.owner');
        }

        return $aData;
    }

    /**
     * Получаем ID объявлений, добавленных текущим пользователем в избранные
     * @param integer $nUserID ID пользователя или 0
     * @param integer $bOnlyCounter только счетчик кол-ва
     * @param array $aOnlyID фильтр по ID объявлений
     * @return array|integer ID избранных объявлений или только счетчик
     */
    public function getFavorites($nUserID = 0, $bOnlyCounter = false, array $aOnlyID = array())
    {
        if ($nUserID) {
            # для авторизованного => достаем из базы
            $aItemsID = $this->model->itemsFavData($nUserID, $aOnlyID);
            if (empty($aItemsID)) {
                $aItemsID = array();
            }
        } else {
            # для неавторизованного => достаем из куков
            $itemsCookie = $this->input->cookie(BBS_FAV_COOKIE, TYPE_STR);
            if (!empty($itemsCookie)) {
                $aItemsID = explode('.', $itemsCookie);
                $this->input->clean($aItemsID, TYPE_ARRAY_UINT);
                if ( ! empty($aOnlyID)) {
                    foreach ($aItemsID as $k=>$v) {
                        if ( ! in_array($v, $aOnlyID)) {
                            unset($aItemsID[$k]);
                        }
                    }
                }
            } else {
                $aItemsID = array();
            }
        }

        if ($bOnlyCounter) {
            return sizeof($aItemsID);
        }
        return $aItemsID;
    }

    /**
     * Пересохраняем избранные ОБ пользователя($nUserID) из куков и БД
     * @param integer $nUserID ID пользователя
     */
    public function saveFavoritesToDB($nUserID)
    {
        do {
            if (empty($nUserID)) {
                break;
            }
            # переносим избранные ОБ из куков в БД
            $itemsCookie = $this->getFavorites(0);
            if (empty($itemsCookie)) {
                break;
            }

            $itemsExists = $this->getFavorites($nUserID);

            # пропускаем уже существующие в БД
            if (!empty($itemsExists)) {
                $itemsNew = array();
                foreach ($itemsCookie as $id) {
                    if (!in_array($id, $itemsExists)) {
                        $itemsNew[] = $id;
                    }
                }
            } else {
                $itemsNew = $itemsCookie;
            }

            if (!empty($itemsNew)) {
                # сохраняем
                $res = $this->model->itemsFavSave($nUserID, $itemsNew);

                # удаляем из куков
                if (!empty($res)) {
                    Request::deleteCOOKIE(BBS_FAV_COOKIE);
                }

                # обновляем счетчик избранных пользователя
                Users::model()->userSave($nUserID, false, array(
                        'items_fav' => sizeof($itemsExists) + sizeof($itemsNew)
                    )
                );
            }

        } while (false);
    }

    /**
     * Проверяем, находится ли ОБ в избранных
     * @param integer $nItemID ID объявления
     * @param integer $nUserID ID пользователя или 0
     * @return bool true - избранное, false - нет
     */
    public function isFavorite($nItemID, $nUserID)
    {
        if (empty($nItemID)) {
            return false;
        }
        $aFavorites = $this->getFavorites($nUserID);

        return in_array($nItemID, $aFavorites);
    }

    /**
     * Получение списка доступных причин жалобы на объявление
     * @return array
     */
    protected function getItemClaimReasons()
    {
        $list = bff::filter('bbs.items.claim.reasons', array(
            1                 => _t('item-claim', 'Неверная рубрика'),
            2                 => _t('item-claim', 'Запрещенный товар/услуга'),
            4                 => _t('item-claim', 'Объявление не актуально'),
            8                 => _t('item-claim', 'Неверный адрес'),
            self::CLAIM_OTHER => _t('item-claim', 'Другое'),
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
     * Формирование текста описания жалобы, с учетом отмеченных причин
     * @param integer $nReasons битовое поле причин жалобы
     * @param string $sComment комментарий к жалобе
     * @return string
     */
    protected function getItemClaimText($nReasons, $sComment)
    {
        $reasons = $this->getItemClaimReasons();
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
     * Актуализация счетчика необработанных жалоб на объявления
     * @param integer|null $increment
     */
    public function claimsCounterUpdate($increment)
    {
        if (empty($increment)) {
            $count = $this->model->claimsListing(array('viewed' => 0), true);
            config::save('bbs_items_claims', $count, true);
        } else {
            config::saveCount('bbs_items_claims', $increment, true);
        }
    }

    /**
     * Актуализация счетчика объявлений ожидающих модерации
     * @param integer|null $increment
     */
    public function moderationCounterUpdate($increment = null)
    {
        $key = 'bbs_items_moderating';
        if ($increment === null) {
            $count = $this->model->itemsModeratingCounter();
            config::save($key, $count, true);
        } else if ($increment === false) {
            return config::get($key, 0, TYPE_UINT);
        } else {
            config::saveCount($key, $increment, true);
        }
    }

    /**
     * Включена ли возможность указать период публикации в форме
     * @return mixed
     */
    public static function formPublicationPeriod()
    {
        return config::sysAdmin('bbs.form.publication.period', false, TYPE_BOOL);
    }

    /**
     * Набор значений для возможности выбора периода публикации
     * @return array
     */
    public function publicationPeriodVariants()
    {
        $days   = _t('', 'день;дня;дней');
        $weeks  = _t('', 'неделя;недели;недель');
        $months = _t('', 'месяц;месяца;месяцев');
        $years  = _t('', 'год;года;лет');
        $days = bff::filter('bbs.items.publication.period.variants', array(
            3   => array('t' => tpl::declension(3, $days),  'a' => 0, 'def' => 0),
            7   => array('t' => tpl::declension(1, $weeks), 'a' => 1, 'def' => 0),
            14  => array('t' => tpl::declension(2, $weeks), 'a' => 1, 'def' => 0),
            30  => array('t' => tpl::declension(1, $months),'a' => 1, 'def' => 1),
            60  => array('t' => tpl::declension(2, $months),'a' => 1, 'def' => 0),
            90  => array('t' => tpl::declension(3, $months),'a' => 1, 'def' => 0),
            180 => array('t' => tpl::declension(6, $months),'a' => 0, 'def' => 0),
            365 => array('t' => tpl::declension(1, $years), 'a' => 0, 'def' => 0),
            730 => array('t' => tpl::declension(2, $years), 'a' => 0, 'def' => 0),
        ), array('days'=>$days, 'weeks'=>$weeks, 'months'=>$months, 'years'=>$years));

        foreach ($days as $k=>&$v) {
            $v['days'] = $k;
        } unset($v);

        return $days;
    }

    /**
     * Значения периода публикации для HTML::selectOptions
     * @param integer $default @ref кол-во дней по-умолчанию
     * @return array
     */
    public function publicationPeriodOptions(& $default)
    {
        $save = func::unserialize(config::get('bbs_item_publication_periods', ''));
        $result = $this->publicationPeriodVariants();
        $default = 0;
        foreach ($result as $k => &$v) {
            if (empty($save[ $k ]['a'])) {
                unset($result[$k]);
                continue;
            }
            $v['def'] = ! empty($save[ $k ]['def']);
            if ($v['def']) { $default = $v['days']; }
        } unset($v);
        if (empty($result)) {
            $result = $this->publicationPeriodVariants();
            if ( ! $default) {
                foreach ($result as $v) {
                    if ($v['def']) {
                        $default = $v['days'];
                        break;
                    }
                }
            }
        }
        foreach ($result as & $v) {
            $v['a'] = $default == $v['days'];
            unset($v['def']);
        } unset($v);
        return $result;
    }

    /**
     * Получаем срок публикации объявления в днях
     * @param integer $nDays срок публикации в днях
     * @param mixed $mFrom дата, от которой выполняется подсчет срока публикации
     * @param string $mFormat тип требуемого результата, строка = формат даты, false - unixtime
     * @return int
     */
    public function getItemPublicationPeriod($nDays = 0, $mFrom = false, $mFormat = 'Y-m-d H:i:s')
    {
        if (static::formPublicationPeriod()) {
            $options = $this->publicationPeriodOptions($default);
            if ( ! isset($options[ $nDays ])) {
                $nDays = $default;
            }
        } else {
            $nDays = config::get('bbs_item_publication_period', 30, TYPE_UINT);
        }
        if ($nDays <= 0) {
            $nDays = 7;
        }

        if (empty($mFrom) || is_bool($mFrom)) {
            $mFrom = strtotime($this->db->now());
        } else if (is_string($mFrom)) {
            $mFrom = strtotime($mFrom);
            if ($mFrom === false) {
                $mFrom = strtotime($this->db->now());
            }
        }

        $nPeriod = strtotime('+' . $nDays . ' days', $mFrom);
        if (!empty($mFormat)) {
            return date($mFormat, $nPeriod);
        } else {
            return $nPeriod;
        }
    }

    /**
     * Получаем срок продления объявления в днях
     * @param mixed $mFrom дата, от которой выполняется подсчет срока публикации
     * @param string $mFormat тип требуемого результата, строка = формат даты, false - unixtime
     * @return int
     */
    public function getItemRefreshPeriod($mFrom = false, $mFormat = 'Y-m-d H:i:s')
    {
        $nDays = config::get('bbs_item_refresh_period', 0, TYPE_UINT);
        if ($nDays <= 0) {
            $nDays = 7;
        }

        if (static::formPublicationPeriod()) {
            $options = $this->publicationPeriodOptions($default);
            $options = end($options);
            $maxDays = isset($options['days']) ? $options['days'] : 0;
            $maxDays = max($nDays, $maxDays);
        } else {
            $publicationPeriod = config::get('bbs_item_publication_period', 30, TYPE_UINT);
            $maxDays = max($nDays, $publicationPeriod);
        }
        $maxDate = strtotime('+' . $maxDays . ' days');

        if (empty($mFrom) || is_bool($mFrom)) {
            $mFrom = $this->db->now();
        }
        if (is_string($mFrom)) {
            $mFrom = strtotime($mFrom);
            if ($mFrom === false) {
                $mFrom = strtotime($this->db->now());
            }
        }
        $nPeriod = strtotime('+' . $nDays . ' days', $mFrom);
        if ($nPeriod > $maxDate) {
            $nPeriod = $maxDate;
        }
        if (!empty($mFormat)) {
            return date($mFormat, $nPeriod);
        } else {
            return $nPeriod;
        }
    }

    /**
     * Брать контакты объявления из профиля (пользователя / магазина)
     * @return boolean
     */
    public function getItemContactsFromProfile()
    {
        return (config::sysAdmin('bbs.item.contacts', config::get('bbs_items_contacts', 0, TYPE_UINT), TYPE_UINT) === 2);
    }

    /**
     * Формируем ключ активации ОБ
     * @return array (code, link, expire)
     */
    protected function getActivationInfo()
    {
        $aData = array();
        $aData['key'] = md5(uniqid(SITEHOST . config::sys('bbs.items.activation.salt','ASDAS(D90--00];&%#97665.,:{}',TYPE_STR) . BFF_NOW, true));
        $aData['link'] = static::url('item.activate', array('c' => $aData['key']));
        $aData['expire'] = date('Y-m-d H:i:s', strtotime('+'.config::sys('bbs.items.activation.expire','1 day',TYPE_STR)));

        return $aData;
    }

    /**
     * Подготовка данных для формы редактирования объявления в админ панели и крон менеджере
     * @param integer $nItemID ID объявления
     * @return mixed
     */
    protected function adminEditPrepare($nItemID)
    {
        $aData = $this->model->itemData($nItemID, array(), true);
        if (empty($aData)) return false;

        if (!empty($aData['cat_id_virtual'])) {
            $aData['cat_id'] = $aData['cat_id_virtual'];
        }

        if(static::translate()){
            $translates = $this->model->itemDataTranslate($nItemID);
            $fields = array_keys($this->model->langItem);
            $lang = $aData['lang'];
            foreach($fields as $f){
                $aData[$f] = array($lang => $aData[$f]);
            }
            $languages = $this->locale->getLanguages();
            foreach($languages as $l){
                if($l == $lang) continue;
                foreach($fields as $f){
                    $aData[$f][$l] = isset($translates[$l][$f]) ? $translates[$l][$f] : '';
                }
            }
        }

        $aData['moderated_data'] = func::unserialize($aData['moderated_data']);
        # формируем форму дин. свойств, типы
        $aData['cat'] = $this->itemFormByCategory($aData['cat_id'], $aData, array(), true);

        # выбор категории
        $aData['cats'] = $this->model->catParentsID($aData['cat_id']);
        $aData['cats'] = $this->model->catsOptionsByLevel($aData['cats'], array('empty' => _t('', 'Выбрать')));

        # формируем данные об изображениях
        $aData['img'] = $this->itemImages($nItemID);
        $aData['images'] = $aData['img']->getData($aData['imgcnt']);

        # город, метро
        $aData['city_data'] = Geo::regionData($aData['city_id']);
        $aData['city_metro'] = Geo::cityMetro($aData['city_id'], $aData['metro_id'], true);

        return $aData;
    }

    /**
     * Обработка данных объявления
     * @param array $aData @ref обработанные данные
     * @param integer $nItemID ID объявления
     * @param array $aItemData данные объявления (при редактировании)
     * @param bool $adminPanel контекст админ панели
     */
    protected function validateItemData(&$aData, $nItemID, $aItemData = array(), $adminPanel = null)
    {
        if (is_null($adminPanel)) {
            $adminPanel = bff::adminPanel();
        }

        $aParams = array(
            'cat_id'     => TYPE_UINT, # категория
            'cat_type'   => TYPE_UINT, # тип объявления
            'owner_type' => TYPE_UINT, # тип владельца
            'video'      => array(TYPE_STR, 'len' => 1500, 'len.sys' => 'bbs.form.video.limit'), # видео ссылка (теги допустимы)
            # цена
            'price'      => TYPE_PRICE, # сумма
            'price_curr' => TYPE_UINT, # валюта
            'price_ex'   => TYPE_ARRAY, # модификаторы цены (торг, обмен, ...)
            # регион
            'city_id'    => TYPE_UINT, # город
            'district_id'=> TYPE_UINT, # район
            'metro_id'   => TYPE_UINT, # станция метро
            'regions_delivery' => TYPE_BOOL, # доставка в регионы
            # адрес
            'addr_addr'  => array(TYPE_TEXT, 'len' => 400, 'len.sys' => 'bbs.form.addr.limit'), # адрес
            'addr_lat'   => TYPE_NUM, # адрес, координата LAT
            'addr_lon'   => TYPE_NUM, # адрес, координата LON
            # контакты
            'name'       => array(TYPE_TEXT, 'len' => 50, 'len.sys' => 'bbs.form.name.limit'), # имя
            'phones'     => TYPE_ARRAY_NOTAGS, # телефоны
            'contacts'   => TYPE_ARRAY_NOTAGS, # контакты
        );

        $translate = static::translate() && $adminPanel;
        if ( ! $translate) {
            $aParams += $this->model->langItem;
        }

        if ( ! $nItemID && static::formPublicationPeriod()) {
            $aParams['publicated_period'] = TYPE_UINT; # срок публикации
        }

        $this->input->postm($aParams, $aData);
        if ($translate) {
            $lng = $this->input->post('lang', TYPE_STR);
            if (empty($lng)) {
                $lng = LNG;
            }
            $this->input->postm_lang($this->model->langItem, $aData);
        }
        $byShop = $this->input->postget('shop', TYPE_BOOL);

        # виртуальная категория => реальная
        $catID = $this->model->catToReal($aData['cat_id']);
        $aData['cat_id_virtual'] = ($catID != $aData['cat_id'] ? $aData['cat_id'] : null);
        $aData['cat_id'] = $catID;

        $autoTplFields = static::autoTplFields();

        if (Request::isPOST()) {
            do {
                if (!$catID) {
                    $this->errors->set(_t('bbs', 'Укажите категорию'), 'cat');
                }
                # Категория
                $catData = $this->model->catData($catID, array_merge(array(
                        'id',
                        'pid',
                        'subs',
                        'numlevel',
                        'numleft',
                        'numright',
                        'addr',
                        'regions_delivery',
                        'price',
                        'keyword',
                        'landing_url',
                        'photos',
                        'tpl_title_enabled',
                    ), array_keys($autoTplFields))
                );
                if (empty($catData) || $catData['subs'] > 0) {
                    $this->errors->set(_t('bbs', 'Категория указана некорректно'), 'cat');
                }
                if ($nItemID && !static::categoryFormEditable() && !$adminPanel &&
                    $catID != $aItemData['cat_id']
                ) {
                    $this->errors->set(_t('bbs', 'Ваше объявление было закреплено за этой категорией. Вы не можете изменить её.'), 'cat');
                }

                # Заголовок
                if ($translate) {
                    $title = $aData['title'];
                    $aData['title'] = $title[$lng];
                    if (empty($title[$lng])) {
                        foreach ($title as $k => $v) {
                            if ( ! empty($v)) {
                                $lng = $k;
                                $aData['title'] = $v;
                                break;
                            }
                        }
                    }
                    if ( ! $nItemID) {
                        $aData['lang'] = $lng;
                    }
                    unset($title[$lng]);
                    $aData['translates']['title'] = $title;
                }
                if ( ! isset($lng)) {
                    $lng = LNG;
                }
                $nearest = $this->catNearestParent($catID, array_keys($autoTplFields), $catData);
                if ($nearest) {
                    $catLang = $this->model->catDataLang($nearest, array_keys($autoTplFields));
                    foreach ($catLang as $v) {
                        foreach ($autoTplFields as $kk => $vv) {
                            if ($catData['tpl_title_enabled']) {
                                $aData['translates'][$vv][$v['lang']] = $this->dpFillTpl($catID, $v[$kk], $_POST, $v['lang']);
                                if ($v['lang'] == $lng) {
                                    $aData[$vv] = $aData['translates'][$vv][$v['lang']];
                                    unset($aData['translates'][$vv][$v['lang']]);
                                }
                            }
                        }
                    }
                }
                $nearest = $this->catNearestParent($catID, array('tpl_descr_list'), $catData);
                if ($nearest) {
                    $catLang = $this->model->catDataLang($nearest, array('tpl_descr_list'));
                    foreach ($catLang as $v) {
                        if ( ! empty($catData['tpl_descr_list'])) {
                            $aData['translates']['descr_list'][$v['lang']] = $this->dpFillTpl($catID, $v['tpl_descr_list'], $_POST, $v['lang']);
                            if ($v['lang'] == $lng) {
                                $aData['descr_list'] = $aData['translates']['descr_list'][$v['lang']];
                                unset($aData['translates']['descr_list'][$v['lang']]);
                            }
                        }
                    }
                }
                if (empty($aData['title'])) {
                    $this->errors->set(_t('bbs', 'Укажите заголовок объявления'), 'title');
                } elseif (mb_strlen($aData['title']) < config::sys('bbs.form.title.min', 5, TYPE_UINT)) {
                    $this->errors->set(_t('bbs', 'Заголовок слишком короткий'), 'title');
                }
                $aData['title'] = trim(preg_replace('/\s+/', ' ', $aData['title']));
                $aData['title'] = \bff\utils\TextParser::antimat($aData['title']);
                $aData['title'] = bff::filter('bbs.form.title.validate', $aData['title']);

                # Описание
                if ($translate) {
                    $aData['translates']['descr'] = $aData['descr'];
                    $aData['descr'] = $aData['descr'][$lng];
                    unset($aData['translates']['descr'][$lng]);
                }
                if (mb_strlen($aData['descr']) < config::sys('bbs.form.descr.min', 12, TYPE_UINT)) {
                    $this->errors->set(_t('bbs', 'Описание слишком короткое'), 'descr');
                }
                $aData['descr'] = trim(preg_replace('/\s{2,}$/m', '', $aData['descr']));
                $aData['descr'] = preg_replace('/ +/', ' ', $aData['descr']);
                $aData['descr'] = \bff\utils\TextParser::antimat($aData['descr']);
                $aData['descr'] = bff::filter('bbs.form.descr.validate', $aData['descr']);

                if ($translate) {
                    # для любой локали должно быть заполнено и title и desc. Если не заполнены оба, то переводим.
                    $languages = $this->locale->getLanguages();
                    $k = array_search($lng, $languages); unset($languages[$k]);

                    foreach ($languages as $l) {
                        if (empty($aData['translates']['title'][$l]) && empty($aData['translates']['descr'][$l])) {
                            unset($aData['translates']['title'][$l], $aData['translates']['descr'][$l]);
                            continue;
                        }
                    }
                    foreach ($languages as $l) {
                        if (isset($aData['translates']['title'][$l]) && empty($aData['translates']['title'][$l])) {
                            $this->errors->set(_t('bbs', 'Введите название для языка [lng]', array('lng' => $l)), 'title');
                        }
                        if (isset($aData['translates']['descr'][$l]) && empty($aData['translates']['descr'][$l])) {
                            $this->errors->set(_t('bbs', 'Введите описание для языка [lng]', array('lng' => $l)), 'descr');
                        }
                    }
                }

                # Данные пользователя
                if ($adminPanel && !$nItemID) {
                    # данные пользователя при добавлении из админ. панели формируются позже
                } else {
                    Users::i()->cleanUserData($aData, array('name', 'contacts'));
                    $aData['phones'] = Users::validatePhones($aData['phones'], Users::i()->profilePhonesLimit);
                    if (empty($aData['name']) || mb_strlen($aData['name']) < 3) {
                        if ( ! $byShop && ! $this->getItemContactsFromProfile()) {
                            $this->errors->set(_t('bbs', 'Имя слишком короткое'), 'name');
                        }
                    }
                }

                # Город
                if (!$aData['city_id']) {
                    $this->errors->set(_t('bbs', 'Укажите город'), 'city');
                    break;
                } else {
                    if (!Geo::isCity($aData['city_id'])) {
                        $this->errors->set(_t('bbs', 'Город указан некорректно'), 'city');
                        break;
                    }
                }
                if (!Geo::coveringType(Geo::COVERING_COUNTRY)) {
                    $cityData = Geo::regionData($aData['city_id']);
                    if (!$cityData || !Geo::coveringRegionCorrect($cityData)) {
                        $this->errors->set(_t('bbs', 'Город указан некорректно'), 'city');
                        break;
                    }
                }
                if ($aData['city_id'] && $aData['district_id']) {
                    $aDistricts = Geo::districtList($aData['city_id']);
                    if (empty($aDistricts) || !array_key_exists($aData['district_id'], $aDistricts)) {
                        $aData['district_id'] = 0;
                    }
                }
                # Доставка в регионы
                if (!$catData['regions_delivery']) {
                    $aData['regions_delivery'] = 0;
                }

                if (!$this->errors->no('bbs.item.validate.step1', array('id'=>$nItemID,'data'=>&$aData,'adminPanel'=>$adminPanel))) {
                    break;
                }

                # Изображения (выставляем лимит для последующей загрузки)
                $this->itemImages($nItemID)->setLimit($catData['photos']);

                # Видео
                if (empty($aData['video'])) {
                    $aData['video_embed'] = '';
                } else {
                    if (!$nItemID || ($nItemID && $aData['video'] != $aItemData['video'])) {
                        $aVideo = $this->itemVideo()->parse($aData['video']);
                        $aData['video_embed'] = serialize($aVideo);
                        if (!empty($aVideo['video_url'])) {
                            $aData['video'] = $aVideo['video_url'];
                        }
                    }
                }

                # Адрес
                if (empty($catData['addr'])) {
                    unset($aData['addr_addr'], $aData['addr_lat'], $aData['addr_lon']);
                }

                # разворачиваем данные о регионе: city_id => reg1_country, reg2_region, reg3_city
                $aRegions = Geo::model()->regionParents($aData['city_id']);
                $aData = array_merge($aData, $aRegions['db']);
                # reg_path
                if ($aData['regions_delivery']) {
                    $aData['reg_path'] = '-'.$aData['reg1_country'].'-ANY-';
                } else {
                    $aData['reg_path'] = '-'.join('-', $aRegions['db']).'-';
                }

                # корректируем цену:
                $aData['price_ex'] = array_sum($aData['price_ex']);
                if ($catData['price']) {
                    # конвертируем цену в основную по курсу (для дальнейшего поиска)
                    $aData['price_search'] = Site::currencyPriceConvertToDefault($aData['price'], $aData['price_curr']);
                }

                # эскейпим заголовок
                $aData['title_edit'] = $aData['title'];
                $aData['title'] = HTML::escape($aData['title_edit']);

                # формируем URL-keyword на основе title
                $aData['keyword'] = mb_strtolower(func::translit($aData['title_edit']));
                $aData['keyword'] = preg_replace("/\-+/", '-', preg_replace('/[^a-z0-9_\-]/', '', $aData['keyword']));

                # формируем URL объявления (@items.search@translit-ID.html)
                $aData['link'] = static::url('items.search', array(
                        'keyword' => $catData['keyword'],
                        'landing_url' => $catData['landing_url'],
                        'region'  => $aRegions['keys']['region'],
                        'city'    => $aRegions['keys']['city'],
                        'item'    => array('id'=>$nItemID, 'keyword'=>$aData['keyword'], 'event'=>($nItemID?'edit':'add')),
                    ), true
                );

                # подготавливаем ID категорий ОБ для сохранения в базу:
                # cat_id(выбранная, самая глубокая), cat_id1, cat_id2, cat_id3 ...
                $catParents = $this->model->catParentsID($catData, true);
                $aData['cat_path'] = '-'.join('-', $catParents).'-';
                foreach ($catParents as $k => $v) {
                    $aData['cat_id' . $k] = $v;
                }
                # заполняем все оставшиеся уровни категорий нулями
                for ($i = static::CATS_MAXDEEP; $i>0; $i--) {
                    if (!isset($aData['cat_id' . $i])) {
                        $aData['cat_id' . $i] = 0;
                    }
                }

                bff::hook('bbs.item.validate.step2', array('id'=>$nItemID,'data'=>&$aData,'adminPanel'=>$adminPanel));

            } while (false);
        } else {
            $aData['cats'] = array();
        }
    }

    /**
     * Является ли текущий пользователь владельцем объявления
     * @param integer $nItemID ID объявления
     * @param integer|bool $nItemUserID ID пользователя объявления или FALSE (получаем из БД)
     * @return boolean
     */
    protected function isItemOwner($nItemID, $nItemUserID = false)
    {
        $nUserID = User::id();
        if (!$nUserID) {
            return false;
        }

        if ($nItemUserID === false) {
            $aData = $this->model->itemData($nItemID, array('user_id', 'status'));
            # ОБ не найдено или помечено как "удаленное"
            if (empty($aData) || $aData['status'] == self::STATUS_DELETED) {
                return false;
            }

            $nItemUserID = $aData['user_id'];
        }

        return ($nItemUserID > 0 && $nUserID == $nItemUserID);
    }

    /**
     * Метод обрабатывающий ситуацию с активацией пользователя
     * @param integer $nUserID ID пользователя
     * @param arrat $options доп. параметры активации
     */
    public function onUserActivated($nUserID, array $options = array())
    {
        # активируем объявления пользователя
        $filter = array(
            'user_id' => $nUserID,
            'status'  => self::STATUS_NOTACTIVATED,
        );
        $update = array(
            'activate_key'     => '', # чистим ключ активации
            'publicated'       => $this->db->now(),
            'publicated_order' => $this->db->now(),
            'status_prev'      => self::STATUS_NOTACTIVATED,
            'status'           => self::STATUS_PUBLICATED,
            'moderated'        => 0, # помечаем на модерацию
            'is_moderating'    => 1,
            'is_publicated'    => (static::premoderation() ? 0 : 1),
        );
        $optionsWrap = array(
            'context' => 'user-activated',
            'context-extra' => $options,
        );
        if (static::formPublicationPeriod()) {
            $periodDefault = 0; $this->publicationPeriodOptions($periodDefault);
            # default period:
            $activated = $this->model->itemsUpdateByFilter($update, $filter + array('publicated_period'=>$periodDefault), $optionsWrap);
            # custom period:
            $this->model->itemsDataByFilter($filter, array('id', 'publicated_period'),
                array('iterator' => function($item) use (&$activated, $update, $optionsWrap) {
                    $update['publicated_to'] = $this->getItemPublicationPeriod($item['publicated_period']);
                    $success = $this->model->itemsUpdateByFilter($update, array('id'=>$item['id']), $optionsWrap);
                    if ($success) {
                        $activated++;
                    }
                }, 'context'=>'user-activated', 'context-extra' => $options)
            );
        } else {
            $update['publicated_to'] = $this->getItemPublicationPeriod();
            $activated = $this->model->itemsUpdateByFilter($update, $filter, $optionsWrap);
        }

        if ($activated > 0) {
            # накручиваем счетчик кол-ва объявлений авторизованного пользователя
            $this->security->userCounter('items', $activated, $nUserID); # +N
            # обновляем счетчик "на модерации"
            $this->moderationCounterUpdate();
        }
    }

    /**
     * Метод обрабатывающий ситуацию с блокировкой/разблокировкой пользователя
     * @param integer $nUserID ID пользователя
     * @param boolean $bBlocked true - заблокирован, false - разблокирован
     * @param array $opts доп. параметры
     */
    public function onUserBlocked($nUserID, $bBlocked, array $opts = array())
    {
        func::array_defaults($opts, array(
            'shop_id' => array('>=', 0),
            'context' => ($bBlocked ? 'user-blocked' : 'user-unblocked'),
            'blocked_reason' => _t('bbs', 'Аккаунт пользователя заблокирован'),
        ));
        if (is_numeric($opts['shop_id'])) {
            $shopBlocked = true;
        }
        if ($bBlocked) {
            # при блокировке:
            # - скрываем из списка на модерации уже заблокированные
            $this->model->itemsUpdateByFilter(array(
                'is_moderating' => 0,
                # помечаем как заблокированные до блокировки аккаунта
                'status_prev'   => self::STATUS_BLOCKED,
            ), array(
                'user_id' => $nUserID,
                'shop_id' => $opts['shop_id'],
                'is_publicated' => 0,
                'status'  => self::STATUS_BLOCKED,
            ), array('context'=>$opts['context']));
            # - блокируем все публикуемые/снятые с публикации
            $this->model->itemsUpdateByFilter(array(
                'blocked_num = blocked_num + 1',
                'status_prev = status',
                'status'         => self::STATUS_BLOCKED,
                'is_publicated'  => 0,
                'is_moderating'  => 0, # скрываем из списка на модерации
                'blocked_reason' => $opts['blocked_reason'],
            ), array(
                'user_id' => $nUserID,
                'shop_id' => $opts['shop_id'],
                'status'  => array(self::STATUS_PUBLICATED, self::STATUS_PUBLICATED_OUT),
            ), array('context'=>$opts['context']));
            # - отменяем все импорты объявлений пользователя
            if ( ! isset($shopBlocked)) {
                $this->itemsImport()->cancelUserImport($nUserID, array('reason' => _t('bbs.import', 'Блокировка пользователя')));
            }
        } else {
            # при разблокировке:
            # - разблокируем (кроме заблокированных до блокировки аккаунта)
            $changed = $this->model->itemsUpdateByFilter(array(
                'status = status_prev',
                //'blocked_reason' => '', # оставляем последнюю причину блокировки
            ), array(
                'user_id'       => $nUserID,
                'shop_id'       => $opts['shop_id'],
                'is_publicated' => 0,
                'status'        => self::STATUS_BLOCKED,
                'status_prev'   => array(self::STATUS_PUBLICATED, self::STATUS_PUBLICATED_OUT),
            ), array('context'=>$opts['context']));
            if ($changed > 0) {
                # - публикуем опубликованные до блокировки аккаунта
                $filter = array(
                    'user_id'       => $nUserID,
                    'shop_id'       => $opts['shop_id'],
                    'is_publicated' => 0,
                    'status'        => self::STATUS_PUBLICATED,
                    'deleted'       => 0,
                );
                $update = array(
                    'is_publicated' => 1,
                );
                if (static::premoderation()) {
                    $filter['moderated'] = 1;
                }
                $this->model->itemsUpdateByFilter($update, $filter, array(
                    'context' => $opts['context'],
                ));
            }
            # - возвращаем в список на модерации
            $this->model->itemsUpdateByFilter(array(
                'is_moderating' => 1,
            ), array(
                'user_id' => $nUserID,
                'shop_id' => $opts['shop_id'],
                'is_publicated' => array('>=', 0),
                'status'  => array(self::STATUS_BLOCKED, self::STATUS_PUBLICATED, self::STATUS_PUBLICATED_OUT),
                'moderated' => array('!=', 1),
            ), array(
                'context' => $opts['context'],
            ));
        }
        # обновляем счетчик "на модерации"
        $this->moderationCounterUpdate();
    }

    /**
     * Метод обрабатывающий ситуацию с удалением пользователя
     * @param integer $userID ID пользователя
     * @param array $options доп. параметры удаления
     */
    public function onUserDeleted($userID, array $options = array())
    {
        # Объявления пользователя - помечаем как удаленные
        $this->model->itemsDeleteByUser($userID);
        # Комментарии к объявлениям (своим/других пользователей)
        $comments = $this->itemComments();
        $owner = BBSItemComments::commentDeletedByCommentOwner;
        if (isset($options['initiator']) && $options['initiator'] == 'admin') {
            $owner = BBSItemComments::commentDeletedByModerator;
        }
        $comments->commentsDeleteByUser($userID, $owner, array(
            'markDeleted' => true,
        ));
        # Избранные объявления
        $this->model->itemsFavDelete($userID);
        # Жалобы на объявления (не удаляем)
        # Импорт объявлений (отменяем)
        $this->itemsImport()->cancelUserImport($userID, array('reason'=>_t('users', 'Удаление пользователя')));
        # Платные лимиты (активные)
        $limitsPayed = $this->model->limitsPayedUserByFilter(array('user_id' => $userID, 'active' => 1), array('id'), false);
        if ( ! empty($limitsPayed)) {
            $limitsID = array();
            foreach ($limitsPayed as $v) {
                $limitsID[] = $v['id'];
            }
            $this->model->limitsPayedUserSave($limitsID, array(
                'active' => 0,
            ));
        }
    }

    /**
     * Метод обрабатывающий ситуацию с блокировкой/разблокировкой магазина
     * @param integer $nShopID ID магазина
     * @param boolean $bBlocked true - заблокирован, false - разблокирован
     * @param integer $nUserID ID пользователя (владельца магазина)
     */
    public function onShopBlocked($nShopID, $bBlocked, $nUserID)
    {
        # Блокируем объявления магазина
        $this->onUserBlocked($nUserID, $bBlocked, array(
            'shop_id' => $nShopID,
            'context' => ($bBlocked ? 'shop-blocked' : 'shop-unblocked'),
            'blocked_reason' => _t('bbs', 'Аккаунт магазина заблокирован'),
        ));
    }

    /**
     * Метод обрабатывающий событие смены курса валюты
     * @param integer $id ID валюты
     * @param float $rate новый курс валюты
     * @param array $options доп. параметры: context
     */
    public function onCurrencyRateChange($id, $rate, array $options = array())
    {
        if ( ! empty($options['context']) && $options['context'] === 'site-currency-rate-autoupdate' &&
             ! config::sysAdmin('currency.rate.auto.bbs', false, TYPE_BOOL)) {
            return;
        }

        $default = Site::currencyDefault('id');
        if ($id != $default) {
            $this->model->itemsUpdateByFilter(array(
                'price_search = ROUND(price * :rate, 2)'
            ), array(
                'is_publicated' => 1,
                'status' => BBS::STATUS_PUBLICATED,
                'price_curr' => $id,
            ), array(
                'bind' => array(
                    ':rate' => $rate,
                ),
                'context' => 'currency-rate-change',
            ));
        }
    }

    # --------------------------------------------------------
    # Активация услуг

    /**
     * Период доступности "Бесплатного поднятия" объявления, 0 - недоступно (выключено), 1+ кол-во дней
     * @return int
     */
    public static function svcUpFreePeriod()
    {
        $aSvc = Svc::model()->svcData(static::SERVICE_UP);
        if (empty($aSvc['on'])) {
            return false;
        }

        return ! empty($aSvc['free_period']) ? $aSvc['free_period'] : 0;
    }

    /**
     * Автоматическое поднятие
     * @return bool true - включено
     */
    public static function svcUpAutoEnabled()
    {
        $aSvc = Svc::model()->svcData(static::SERVICE_UP);
        if (empty($aSvc['on'])) {
            return false;
        }

        return !empty($aSvc['auto_enabled']);
    }

    /**
     * Варианты периодичности для услуги автоподнятия
     * @return array
     */
    public static function svcUpAutoPeriods()
    {
        return bff::filter('bbs.svc.upauto.periods', array(
            1 => array('id' => 1, 't' => _t('bbs', 'Каждый день')),
            3 => array('id' => 3, 't' => _t('bbs', 'Раз в 3 дня')),
            7 => array('id' => 7, 't' => _t('bbs', 'Раз в неделю')),
            -1 => array('id' => -1, 't' => _t('bbs', 'Каждый будний день')),
        ));
    }

    /**
     * Активация услуги/пакета услуг для Объявления
     * @param integer $nItemID ID объявления
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

        # получаем данные об объявлении
        if (empty($aItemData)) {
            $aItemData = $this->model->itemData($nItemID, bff::filter('bbs.svc.activate.item.fields', array(
                    'id',
                    'status',
                    'publicated_to', # дата окончания публикации
                    'svc', # битовое поле активированных услуг
                    'svc_up_activate', # кол-во оставшихся оплаченных поднятий (оплаченных пакетно)
                    'reg2_region',
                    'reg3_city', # ID региона(области), ID города
                    'cat_id1',
                    'cat_id2', # ID основной категории, ID подкатегории
                    'svc_fixed', # статус активации "Закрепления"
                    'svc_fixed_to', # дата окончания "Закрепления"
                    'svc_premium', # статус активации "Премиум"
                    'svc_premium_to', # дата окончания "Премиум"
                    'svc_marked_to', # дата окончания "Выделение"
                    'svc_quick_to', # дата окончания "Срочно"
                    'svc_press_status'
                ))
            ); # статус "Печать в прессе"
        }

        # проверяем статус объявления
        if ($nItemID && (empty($aItemData) || $aItemData['status'] == self::STATUS_DELETED)) {
            $this->errors->set(_t('bbs', 'Для указанного объявления невозможно активировать данную услугу'));

            return false;
        }

        # хуки
        $customActivation = bff::filter('bbs.svc.activate', $nSvcID, $aSvcData, $nItemID, $aItemData, $aSvcSettings);
        if (is_bool($customActivation)) {
            return $customActivation;
        }

        # активируем пакет услуг
        if ($aSvcData['type'] == Svc::TYPE_SERVICEPACK) {
            $aServices = (isset($aSvcData['svc']) ? $aSvcData['svc'] : array());
            if (empty($aServices)) {
                $this->errors->set(_t('bbs', 'Неудалось активировать пакет услуг'));

                return false;
            }
            $aServicesID = array();
            foreach ($aServices as $v) {
                $aServicesID[] = $v['id'];
            }
            $aServices = Svc::model()->svcData($aServicesID);
            if (empty($aServices)) {
                $this->errors->set(_t('bbs', 'Неудалось активировать пакет услуг'));

                return false;
            }

            # проходимся по услугам, входящим в пакет
            # активируем каждую из них
            $nSuccess = 0;
            foreach ($aServices as $k => $v) {
                # исключаем выключенные услуги
                if (empty($v['on'])) {
                    continue;
                }
                # при пакетной активации, период действия берем из настроек пакета услуг
                $v['cnt'] = $aSvcData['svc'][$k]['cnt'];
                if (!empty($v['cnt'])) {
                    $v['period'] = $v['cnt'];
                }
                $res = $this->svcActivateService($nItemID, $v['id'], $v, $aItemData, true, $aSvcSettings);
                if ($res) {
                    $nSuccess++;
                }
            }

            return true;
        } else {
            # активируем услугу
            return $this->svcActivateService($nItemID, $nSvcID, $aSvcData, $aItemData, false, $aSvcSettings);
        }
    }

    /**
     * Активация услуги для Объявления
     * @param integer $nItemID ID объявления
     * @param integer $nSvcID ID услуги
     * @param mixed $aSvcData данные об услуге(*) или FALSE
     * @param mixed $aItemData @ref данные об объявлении или FALSE
     * @param boolean $bFromPack услуга активируется из пакета услуг
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return boolean|integer
     *      1, true - услуга успешно активирована,
     *      2 - услуга успешно активирована без необходимости списывать средства со счета пользователя
     *      false - ошибка активации услуги
     */
    protected function svcActivateService($nItemID, $nSvcID, $aSvcData = false, &$aItemData = false, $bFromPack = false, array &$aSvcSettings = array())
    {
        if (empty($nSvcID) || (! empty($nItemID) && empty($aItemData))) {
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
        $aItemData['svc'] = intval($aItemData['svc']);

        # период действия услуги (в днях)
        # > при пакетной активации, период действия берется из настроек активируемого пакета услуг
        $nPeriodDays = (!empty($aSvcData['period']) ? intval($aSvcData['period']) : 1);
        if( ! empty($aSvcSettings['period'])){
            $nPeriodDays = $aSvcSettings['period'];
        }
        if ($nPeriodDays < 1) {
            $nPeriodDays = 1;
        }

        $sNow = $this->db->now();
        $publicatedTo = $nItemID ? strtotime($aItemData['publicated_to']) : false;
        $aUpdate = array();
        $mResult = true;
        switch ($nSvcID) {
            case self::SERVICE_UP: # Поднятие
            {
                $nPosition = $this->model->itemPositionInCategory($nItemID, $aItemData['cat_id1']);

                if ($bFromPack) {
                    if ($nPosition === 1) {
                        # если ОБ находится на первой позиции в основной категории
                        # НЕ выполняем "поднятие", только помечаем доступное для активации кол-во поднятий
                        $aUpdate['svc_up_activate'] = $aSvcData['cnt'];
                        break;
                    }
                    # при "поднятии" пакетно помечаем доступное для активации кол-во "поднятий"
                    # -1 ("поднятие" при активации пакета услуг)
                    $aUpdate['svc_up_activate'] = ($aSvcData['cnt'] - 1);
                } else {
                    if ($nPosition == 1) {
                        $this->errors->set(_t('svc', 'Объявление находится на первой позиции, нет необходимости выполнять его поднятие'));

                        return false;
                    }
                    # если есть неиспользованные "поднятия", используем их
                    if (!empty($aItemData['svc_up_activate'])) {
                        $aUpdate['svc_up_activate'] = ($aItemData['svc_up_activate'] - 1);
                        $mResult = 2; # без списывание средств со счета
                    }
                }
                # если объявление закреплено, поднимаем также среди закрепленных
                if ($aItemData['svc'] & static::SERVICE_FIX) {
                    $aUpdate['svc_fixed_order'] = $sNow;
                }
                $aUpdate['publicated_order'] = $sNow;
                $aUpdate['svc_up_date'] = date('Y-m-d H:i:s');

                # если период публикации завершается раньше чем срок продления объявления
                $to = $this->getItemRefreshPeriod(false, '');
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = date('Y-m-d H:i:s', $to);
                }

                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_MARK: # Выделение
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_marked_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем срок действия услуги
                $aUpdate['svc_marked_to'] = $toStr;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_QUICK: # Срочно
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_quick_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем срок действия услуги
                $aUpdate['svc_quick_to'] = $toStr;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_FIX: # Закрепление
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_fixed_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем активацию услуги
                $aUpdate['svc_fixed'] = 1;
                # помечаем срок действия услуги
                $aUpdate['svc_fixed_to'] = $toStr;
                # ставим выше среди закрепленных
                $aUpdate['svc_fixed_order'] = $sNow;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_PREMIUM: # Премиум
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_premium_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем активацию услуги
                $aUpdate['svc_premium'] = 1;
                # помечаем срок действия услуги
                $aUpdate['svc_premium_to'] = $toStr;
                # ставим выше среди "премиум" объявлений
                $aUpdate['svc_premium_order'] = $sNow;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_PRESS: # Печать в прессе
            {
                if (!static::PRESS_ON) {
                    break;
                }

                switch ($aItemData['svc_press_status']) {
                    case self::PRESS_STATUS_PAYED:
                    {
                        if (!$bFromPack) {
                            $this->errors->set(_t('svc', 'Объявление будет опубликовано в прессе в ближайшее время'));
                        }

                        return false;
                    }
                    break;
                    case self::PRESS_STATUS_PUBLICATED:
                    {
                        if (!$bFromPack) {
                            $this->errors->set(_t('svc', 'Объявление уже опубликовано в прессе'));
                        }

                        return false;
                    }
                    break;
                    default:
                    {
                        # помечаем на "Публикацию в прессе"
                        $aUpdate['svc_press_status'] = self::PRESS_STATUS_PAYED;
                        # помечаем активацию услуги
                        $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
                    }
                    break;
                }
            }
            break;
            case static::SERVICE_LIMIT: # платное расширение лимитов
            {
                return $this->svcActivateLimitsPayed($aSvcSettings);
            }
            break;
            default: # другая услуга
            {
                bff::hook('bbs.svc.activate.custom', $nSvcID, $aSvcData, $nItemID, $aItemData, array('fromPack'=>$bFromPack, 'settings'=>&$aSvcSettings, 'update'=>&$aUpdate));
            }
            break;
        }
        $res = $this->model->itemSave($nItemID, $aUpdate);
        if (!empty($res)) {
            if ($nSvcID == self::SERVICE_PRESS) {
                # +1 к счетчику "печать в прессе"
                $this->pressCounterUpdate(1);
            }
            # актуализируем данные об объявлении для корректной пакетной активации услуг
            if (!empty($aUpdate)) {
                foreach ($aUpdate as $k => $v) {
                    $aItemData[$k] = $v;
                }
            }

            return $mResult;
        }

        return false;
    }

    /**
     * Формируем описание счета активации услуги (пакета услуг)
     * @param integer $nItemID ID Объявления
     * @param integer $nSvcID ID услуги
     * @param array|boolean $aData false или array('item'=>array('id',...),'svc'=>array('id','type'))
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return string
     */
    public function svcBillDescription($nItemID, $nSvcID, $aData = false, array &$aSvcSettings = array())
    {
        $aSvc = (!empty($aData['svc']) ? $aData['svc'] :
            Svc::model()->svcData($nSvcID));

        $aItem = (!empty($aData['item']) ? $aData['item'] :
            $this->model->itemData($nItemID, array('id', 'keyword', 'title', 'link')));

        $sLink = (!empty($aItem['link']) ? 'href="' . $aItem['link'] . '" class="j-bills-bbs-item-link" data-item="' . $nItemID . '"' : 'href=""');

        if ($aSvc['type'] == Svc::TYPE_SERVICE) {
            switch ($nSvcID) {
                case self::SERVICE_UP:
                {
                    return _t('bbs', 'Поднятие объявления в списке<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                                     'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_MARK:
                {
                    return _t('bbs', 'Выделение объявления<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                             'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_FIX:
                {
                    if( ! empty($aSvcSettings['period'])){
                        return _t('bbs', 'Закрепление объявления на [days]<br /><small><a [link]>[title]</a></small>', array(
                                'days'  => tpl::declension($aSvcSettings['period'], _t('', 'день;дня;дней')),
                                'link'  => $sLink,
                                'title' => $aItem['title']
                            )
                        );
                    }
                    return _t('bbs', 'Закрепление объявления<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                               'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_PREMIUM:
                {
                    return _t('bbs', 'Премиум размещение<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                           'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_QUICK:
                {
                    return _t('bbs', 'Срочное размещение<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                           'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_PRESS:
                {
                    return _t('bbs', 'Размещение объявления в прессе<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                                       'title' => $aItem['title']
                        )
                    );
                }
                break;
                case static::SERVICE_LIMIT:
                {
                    if ( ! empty($aSvcSettings['extend'])) {
                        return _t('bbs', 'Продление пакета [items]<br /><small>[point]</small>', array(
                                'items' => tpl::declension($aSvcSettings['items'], _t('bbs', 'объявление;объявления;объявлений')),
                                'point' => $this->limitsPayedCatTitle($aSvcSettings['point']),
                            )
                        );
                    } else {
                        return _t('bbs', 'Платный пакет [items]<br /><small>[point]</small>', array(
                                'items' => tpl::declension($aSvcSettings['items'], _t('bbs', 'объявление;объявления;объявлений')),
                                'point' => $this->limitsPayedCatTitle($aSvcSettings['point']),
                            )
                        );
                    }
                }
                break;
            }
        } else {
            if ($aSvc['type'] == Svc::TYPE_SERVICEPACK) {
                return _t('bbs', 'Пакет услуг "[pack]" <br /><small><a [link]>[title]</a></small>',
                    array('pack' => $aSvc['title'], 'link' => $sLink, 'title' => $aItem['title'])
                );
            }
        }
        return bff::filter('bbs.svc.description.custom', '', $nSvcID, $aSvc, $aItem, $sLink);
    }

    /**
     * Инициализация компонента обработки иконок услуг/пакетов услуг BBSSvcIcon
     * @param mixed $nSvcID ID услуги / пакета услуг
     * @return BBSSvcIcon component
     */
    public static function svcIcon($nSvcID = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSSvcIcon();
        }
        $i->setRecordID($nSvcID);

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
     * Актуализация счетчика объявлений ожидающих печати в прессе
     * @param integer|null $increment
     */
    public function pressCounterUpdate($increment)
    {
        if (empty($increment)) {
            $count = $this->model->itemsListing(array('svc_press_status' => self::PRESS_STATUS_PAYED), true);
            config::save('bbs_items_press', $count, true);
        } else {
            config::saveCount('bbs_items_press', $increment, true);
        }
    }

    /**
     * Активация услуги платного расширения лимитов
     * @param array $settings @ref данные для активации
     * @return bool
     */
    public function svcActivateLimitsPayed(& $settings = array())
    {
        if (empty($settings['user_id']) || ! isset($settings['point'])  || empty($settings['items'])) {
            $this->errors->set(_t('svc', 'Неудалось активировать услугу'));
            return false;
        }
        # кол-во дней активации или продления, 0 - бессрочно
        $term = config::get('bbs_limits_payed_days', 0, TYPE_UINT);

        # продление существующей услуги
        if ( ! empty($settings['extend']) &&
             ! empty($settings['id']))
         {
            # проверка существования поинта
            $limit = $this->model->limitsPayedUserByFilter(array(
                'id'      => $settings['id'],
                'user_id' => $settings['user_id'],
                'active'  => 1,
            ), array('cat_id', 'shop', 'expire', 'items'));
            if (empty($limit) || ! $term) {
                $this->errors->set(_t('svc', 'Неудалось активировать услугу'));
                return false;
            }
            $expire = strtotime('+'.$term.'days', strtotime($limit['expire']));
            $update = array(
                'expire' => date('Y-m-d H:i:s', $expire),
            );
            if ($this->model->limitsPayedUserSave($settings['id'], $update)) {
                return true;
            }
        } else {
            $data = array(
                'user_id' => $settings['user_id'],
                'cat_id'  => $settings['point'],
                'items'   => $settings['items'],
                'shop'    => $settings['shop'],
                'free_id' => $settings['free_id'],
                'paid_id' => $settings['paid_id'],
                'active'  => 1,
            );
            if ($term) {
                $data['expire'] = date('Y-m-d H:i:s', strtotime('+' . $term . 'days'));
            }

            if ($limitID = $this->model->limitsPayedUserSave(0, $data)) {
                $settings['id'] = $limitID;
                return true;
            }
        }
        return false;
    }

    /**
     * Формирование названия поинта (платные лимиты)
     * @param integer $catID ID категории
     * @param bool|true $oneString только строка с названием или массив
     * @return array|string
     */
    public function limitsPayedCatTitle($catID, $oneString = true)
    {
        if ( ! $catID) {
            if ($oneString) return _t('bbs', 'Общий лимит');
            else return array('parent' => _t('bbs', 'Общий лимит'), 'title' => '', 'numleft' => 0);
        }
        $title = array();
        $parents = $this->model->catParentsData($catID, array('id', 'title', 'numleft'));
        foreach ($parents as $v) {
            $title[] = $v['title'];
        }
        if ($oneString) {
            return join(' / ', $title);
        }
        $parent = array_shift($title);
        $last = end($parents);
        return array('parent' => $parent, 'title' => join(' / ', $title), 'numleft' => $last['numleft']);
    }

    /**
     * Привяжем ID списания средств к услуге
     * @param integer $billID ID сформированного счета
     */
    public function bindBillID($billID)
    {
        if ( ! $billID) {
            return;
        }
        $data = Bills::model()->billData($billID, array('svc_id', 'svc_settings'));
        $svcSettings = $data['svc_settings'];
        switch ($data['svc_id']) {
            case static::SERVICE_LIMIT: # платное расширение лимитов
            {
                if (empty($svcSettings['id'])) {
                    return;
                }
                $id = $svcSettings['id'];
                $data = $this->model->limitsPayedUserByFilter(array('id' => $id), array('bill_id'));
                if (empty($data)) return;
                $bill_id = $data['bill_id'];
                if ( ! empty($bill_id)) {
                    $bill_id .= ', ';
                }
                $bill_id .= $billID;
                $this->model->limitsPayedUserSave($id, array('bill_id' => $bill_id));
            }
            break;
        }
    }


    /**
     * Обработка копирования данных локализации
     */
    public function onLocaleDataCopy($from, $to)
    {
        # услуги (services, packs)
        if (bff::servicesEnabled()) {
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
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function onGeoUrlTypeChanged($prevType, $nextType)
    {
        $this->model->itemsGeoUrlTypeChanged($prevType, $nextType);
    }
    
    /**
     * Получение списка возможных дней для оповещения о завершении публикации объявления
     * @return array
     */
    protected function getUnpublicatedDays()
    {
        return bff::filter('bbs.items.unpublicate.days.enotify',array(1,2,5));
    }
    
    /**
     * Получение ключа для модерации объявлений на фронтенде
     * @param integer $itemID ID объявления
     * @param string|boolean $checkKey ключ для проверки или FALSE
     * @return string|boolean
     */
    public static function moderationUrlKey($itemID = 0, $checkKey = false)
    {
        if ($checkKey !== false) {
            if (empty($checkKey) || strlen($checkKey) != 5) {
                return false;
            }
        }

        $key = substr(hash('sha256', $itemID . SITEHOST), 0, 5);
        if ($checkKey !== false) {
            return ($key === $checkKey);
        }

        return $key;
    }
    
    /**
     * Получаем уровень подкатегории, отображаемый в фильтре
     * @param boolean $asSetting как указано в настройке
     * @return integer
     */
    public static function catsFilterLevel($asSetting = false)
    {
        $level = config::sysAdmin('bbs.search.filter.catslevel', config::get('bbs_categories_filter_level', 3, TYPE_UINT), TYPE_UINT);
        if ($level < 2) $level = 2;
        return ( $asSetting ? $level : $level - 1 );
    }

    /**
     * Получаем данные о самой глубокой из parent-категорий не отображаемой в фильтре
     * @param array $catData @ref данные о текущей категории поиска
     * @return array
     */
    public function catsFilterParent(array &$catData)
    {
        return $this->model->catDataByFilter(array(
            'numlevel' => static::catsFilterLevel(),
            'numleft <= ' . $catData['numleft'],
            'numright > ' . $catData['numright'],
        ), array('id','pid','title'));
    }

    /**
     * Получаем данные для формирование фильтров подкатегорий
     * @param array $catData @ref данные о текущей категории поиска
     * @return array
     */
    public function catsFilterData(array &$catData)
    {
        $filterLevel = static::catsFilterLevel();
        if ($catData['numlevel'] < $filterLevel) return array();

        # основные категории
        $cats = $this->model->catParentsData($catData, array('id','pid','numlevel','keyword','landing_url','subs_filter_title as subs_title','subs',));
        foreach ($cats as $k=>&$v) {
            if ($v['numlevel'] < $filterLevel || ! $v['subs']) {
                unset($cats[$k]);
            }
            $v['link'] = static::url('items.search', array('keyword'=>$v['keyword'],'landing_url'=>$v['landing_url'])); unset($v['keyword']);
            $v['subs'] = array();
        } unset($v);
        if (empty($cats)) return array();
        $catsID = $catsID_All = array_keys($cats);

        # подкатегории
        $subcats = $this->model->catsDataByFilter(array('pid'=>$catsID,'enabled'=>1), array('id','pid','title','keyword','landing_url'));
        foreach ($subcats as &$v) {
            $v['link'] = static::url('items.search', array('keyword'=>$v['keyword'],'landing_url'=>$v['landing_url'])); unset($v['keyword']);
            $v['active'] = (in_array($v['id'], $catsID) || $v['id'] == $catData['id']);
            $cats[$v['pid']]['subs'][$v['id']] = $v;
            $catsID_All[] = $v['id'];
        } unset($v, $subcats);

        # количество объявлений в категориях
        $items = $this->model->catsItemsCountersByID($catsID_All, Geo::filter());
        foreach ($cats as $k => & $v) {
            $v['items'] = (array_key_exists($k, $items) ? $items[$k] : 0);
            foreach ($v['subs'] as $kk => & $vv) {
                $vv['items'] = (array_key_exists($kk, $items) ? $items[$kk] : 0);
            } unset($vv);
        } unset($v);

        return $cats;
    }
    
    /**
     * Проверка возможности импорта объявлений
     * @return bool
     */
    public static function importAllowed()
    {
        if (bff::adminPanel()) return true;
        if (!bff::shopsEnabled()) return false;

        $shopID = User::shopID();
        if ($shopID <= 0) return false;

        $shopData = Shops::model()->shopData($shopID, array('status','import','svc_abonement_id'));
        if (empty($shopData)) {
            bff::log(_t('bbs', 'Ошибка получение данных о магазине #[id]', array('id' => $shopID)));
            return false;
        } else {
            if ($shopData['status'] != Shops::STATUS_ACTIVE) {
                return false;
            }
            # проверяем возможность импорта абонемента
            if (Shops::abonementEnabled() && $shopData['svc_abonement_id']) {
                $aAbonement = Shops::model()->abonementData($shopData['svc_abonement_id']);
                if (!$aAbonement['import']) {
                    return false;
                } else {
                    return true;
                }
            }
        }

        $access = config::sysAdmin('bbs.import.access', config::get('bbs_items_import', self::IMPORT_ACCESS_ADMIN, TYPE_UINT), TYPE_UINT);
        switch($access)
        {
            case self::IMPORT_ACCESS_ADMIN: {
                return false;
            } break;
            case self::IMPORT_ACCESS_CHOSEN: {
                if ($shopData['import'] > 0) return true;
            } break;
            case self::IMPORT_ACCESS_ALL: {
                return true;
            } break;
        }

        return false;
    }

    /**
     * Проверка превышения лимита добавления новых объявлений
     * @param integer $nUserID ID пользователя
     * @param integer $nShopID ID магазина
     * @param int $nCatID ID категории или 0
     * @param int $nLimit @ref значение лимита для выбранного режима
     * @return bool true - лимит превышен, false - нет
     */
    protected function itemsLimitExceeded($nUserID, $nShopID, $nCatID = 0, & $nLimit = 0)
    {
        if ( ! $nUserID) {
            return false;
        }
        if (static::limitsPayedEnabled()) {
            # если выключена услуга платного расширения лимитов - превышение лимита не действует
            return false;
        }
        $now = date('Y-m-d 00:00:00');
        $mode = 'user';
        $aFilter = array(
            'user_id'  => $nUserID,
            ':created' => array('created >= :now', ':now' => $now),
            'shop_id'  => $nShopID,
        );

        if ($nShopID) {
            $mode = 'shop';
            # если включено лимитирование по абонементу превышение лимита не действует
            if (Shops::abonementEnabled()) {
                return false;
            }

            if (static::importAllowed()) { # если разрешен импорт - лимитирование не действует
                return false;
            }
        }
        switch (config::get('bbs_items_limits_'.$mode, static::LIMITS_NONE)) {
            case static::LIMITS_COMMON: # общий лимит
                $nLimit = config::get('bbs_items_limits_'.$mode.'_common', 0, TYPE_UINT);
                if ($nLimit > 0) {
                    $nCnt = $this->model->itemsCount($aFilter);
                    if ($nCnt >= $nLimit) {
                        return true;
                    }
                }
                break;
            case static::LIMITS_CATEGORY: # лимит по категориям
                if ( ! $nCatID) {
                    break;
                }
                $nLimit = config::get('bbs_items_limits_'.$mode.'_category_default', 0, TYPE_UINT);
                $aLimit = func::unserialize(config::get('bbs_items_limits_'.$mode.'_category', false));
                if ($nLimit > 0 && (empty($aLimit) || ! isset($aLimit[$nCatID]))) {
                    # общий для всех категорий
                    if ( ! empty($aLimit)) {
                        # исключим перечисленные категории
                        $aFilter[':cat_id1'] = $this->db->prepareIN('cat_id1', array_keys($aLimit), true);
                    }
                    $nCnt = $this->model->itemsCount($aFilter);
                    if ($nCnt >= $nLimit) {
                        return true;
                    }
                    break;
                }
                if (isset($aLimit[$nCatID]) && $aLimit[$nCatID] > 0) { # лимит в конкретной категории
                    $nLimit = $aLimit[$nCatID];
                    $aFilter['cat_id1'] = $nCatID;
                    $nCnt = $this->model->itemsCount($aFilter);
                    if ($nCnt >= $nLimit) {
                        return true;
                    }
                }
                break;
            case static::LIMITS_NONE: # без ограничений
                break;
        }
        return false;
    }

    /**
     * Поиск "минус слов" в строке
     * @param  string $sString строка
     * @param string $sWord @ref найденное слово
     * @return bool true - нашли минус слово, false - нет
     */
    protected function spamMinusWordsFound($sString, & $sWord = '')
    {
        static $aMinusWords;
        if ( ! isset($aMinusWords)) {
            $aMinusWords = func::unserialize(config::get('bbs_items_spam_minuswords', ''));
        }
        if (empty($aMinusWords[LNG])) return false;
        return \bff\utils\TextParser::minuswordsSearch($sString, $sWord, $aMinusWords[LNG]);
    }

    /**
     * Проверка дублирования пользователем объявлений
     * @param integer $nUserID ID пользователя
     * @param array $aData @ref данные ОБ, ищем по заголовку 'title' и/или описанию 'descr'
     * @return bool true - нашли похожее объявление, false - нет
     */
    protected function spamDuplicatesFound($nUserID, &$aData)
    {
        if (!$nUserID) return false;
        if (!config::get('bbs_items_spam_duplicates', false)) return false;

        $query = array(0=>array());
        if (!empty($aData['title'])) {
            $query[0][] = 'title LIKE :title';
            $query[':title'] = $aData['title'];
        }
        if (!empty($aData['descr'])) {
            $query[0][] = 'descr LIKE :descr';
            $query[':descr'] = $aData['descr'];
        }
        if (!empty($query[0])) {
            $query[0] = '('.join(' OR ', $query[0]).')';
            if ($this->model->itemsCount(array(
                'user_id' => $nUserID,
                'status != '.self::STATUS_DELETED,
                ':query'  => $query,
            ))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Включено формирование RSS
     * @return boolean
     */
    public static function rssEnabled()
    {
        return config::sysAdmin('bbs.rss.enabled', true, TYPE_BOOL);
    }

    /**
     * Отображать кнопку "Продвинуть объявление" на странице ее просмотра
     * @param boolean $isItemOwner текущий пользователь является втором объявления
     * @return bool
     */
    public static function itemViewPromoteAvailable($isItemOwner)
    {
        $sys = config::sysAdmin('bbs.view.promote', 0, TYPE_UINT);
        switch ($sys) {
            case 0: # всем
                return true;
            case 1: # авторизованным
                return (User::id() > 0);
            case 2: # автору объявления
                return !empty($isItemOwner);
        }
        return false;
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('items', 'images') => 'dir-split', # изображения объявлений
            bff::path('cats', 'images')  => 'dir-only', # изображения категорий
            bff::path('svc', 'images')   => 'dir-only', # изображения платных услуг
            bff::path('tmp', 'images')   => 'dir-only', # tmp
            bff::path('import')          => 'dir-only', # импорт
            PATH_BASE.'files'            => 'dir-only', # выгрузка Яндекс.Маркет
        ));
    }

}