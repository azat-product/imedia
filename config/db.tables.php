<?php

    # Настройки сайта (site, config)
    define('TABLE_CONFIG',                      DB_PREFIX.'config'); // настройки сайта
    define('TABLE_MIGRATIONS',                  DB_PREFIX.'migrations'); // миграции

    # Пользователи (users)
    define('TABLE_USERS',                       DB_PREFIX.'users'); // пользователи
    define('TABLE_USERS_STAT',                  DB_PREFIX.'users_stat'); // стат. данные пользователей
    define('TABLE_USERS_SOCIAL',                DB_PREFIX.'users_social'); // соц. аккаунты пользователей
    define('TABLE_USERS_GROUPS',                DB_PREFIX.'users_groups'); // группы пользователей
    define('TABLE_USER_IN_GROUPS',              DB_PREFIX.'user_in_group'); // вхождение пользователей в группы
    define('TABLE_USERS_GROUPS_PERMISSIONS',    DB_PREFIX.'users_groups_permissions'); // доступ группы
    define('TABLE_USERS_BANLIST',               DB_PREFIX.'users_banlist'); // бан-списки пользователей
    define('TABLE_MODULE_METHODS',              DB_PREFIX.'module_methods'); // схема доступа групп

    # Объявления (bbs)
    define('TABLE_BBS_CATEGORIES',                DB_PREFIX.'bbs_categories'); // категории ОБ
    define('TABLE_BBS_CATEGORIES_LANG',           DB_PREFIX.'bbs_categories_lang'); // категории ОБ - lang
    define('TABLE_BBS_CATEGORIES_DYNPROPS',       DB_PREFIX.'bbs_categories_dynprops'); // настройки дин.свойств категорий
    define('TABLE_BBS_CATEGORIES_DYNPROPS_MULTI', DB_PREFIX.'bbs_categories_dynprops_multi'); // настройки дин.свойств категорий (multi)
    define('TABLE_BBS_CATEGORIES_TYPES',          DB_PREFIX.'bbs_categories_types'); // типы категорий
    define('TABLE_BBS_ITEMS',                     DB_PREFIX.'bbs_items'); // объявления
    define('TABLE_BBS_ITEMS_LANG',                DB_PREFIX.'bbs_items_lang'); // объявления lang
    define('TABLE_BBS_ITEMS_IMAGES',              DB_PREFIX.'bbs_items_images'); // изображения к объявлениям
    define('TABLE_BBS_ITEMS_COMMENTS',            DB_PREFIX.'bbs_items_comments'); // комментарии к объявлениям
    define('TABLE_BBS_ITEMS_CLAIMS',              DB_PREFIX.'bbs_items_claims'); // жалобы на объявления
    define('TABLE_BBS_ITEMS_VIEWS',               DB_PREFIX.'bbs_items_views'); // статистика просмотров
    define('TABLE_BBS_ITEMS_FAV',                 DB_PREFIX.'bbs_items_fav'); // избранные объявления
    define('TABLE_BBS_SVC_PRICE',                 DB_PREFIX.'bbs_svc_price'); // настройки региональной стоисмости платных услуг
    define('TABLE_BBS_ITEMS_ENOTIFY',             DB_PREFIX.'bbs_items_enotify'); // хранение id объявлений и даты отправки уведомления
    define('TABLE_BBS_ITEMS_IMPORT',              DB_PREFIX.'bbs_items_import'); // хранение иформации об импорте объявлений
    define('TABLE_BBS_ITEMS_COUNTERS',            DB_PREFIX.'bbs_items_counters'); // счетчики объявлений
    define('TABLE_BBS_ITEMS_LIMITS',              DB_PREFIX.'bbs_items_limits'); // лимиты объявлений
    define('TABLE_BBS_ITEMS_LIMITS_USERS',        DB_PREFIX.'bbs_items_limits_users'); // лимиты объявлений, купленные пользователем
    define('TABLE_BBS_ITEMS_RATINGS',             DB_PREFIX.'bbs_items_ratings'); // рейтинг объявлений

    # Регионы(страны, области, города, ...) (geo)
    define('TABLE_REGIONS',                     DB_PREFIX.'regions'); // регионы
    define('TABLE_REGIONS_DISTRICTS',           DB_PREFIX.'regions_districts');
    define('TABLE_REGIONS_METRO',               DB_PREFIX.'regions_metro'); // метро / ветки метро
    define('TABLE_REGIONS_GEOIP',               DB_PREFIX.'regions_geoip'); // данные ipgeobase

    # Страницы (site)
    define('TABLE_PAGES',                       DB_PREFIX.'pages'); // страницы
    define('TABLE_PAGES_LANG',                  DB_PREFIX.'pages_lang'); // страницы - lang

    # Карта сайта (sitemap)
    define('TABLE_SITEMAP',                     DB_PREFIX.'sitemap'); // карта сайта
    define('TABLE_SITEMAP_LANG',                DB_PREFIX.'sitemap_lang'); // карта сайта - lang

    # Счетчики (site)
    define('TABLE_COUNTERS',                    DB_PREFIX.'counters'); // счетчики

    # Валюты (site)
    define('TABLE_CURRENCIES',                  DB_PREFIX.'currencies'); // валюты
    define('TABLE_CURRENCIES_LANG',             DB_PREFIX.'currencies_lang'); // валюты - lang

    # Оплата(счета), услуги (bills, svc)
    define('TABLE_BILLS',                       DB_PREFIX.'bills'); // счета
    define('TABLE_SVC',                         DB_PREFIX.'svc'); // настройки платных услуг