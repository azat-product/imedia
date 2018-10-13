<?php

return [
    # просмотр объявления
    'bbs-view' => [
        'pattern'  => 'search/(.*)/(.*)\-([\d]+)\.html',
        'callback' => 'bbs/view/item_view=1&cat=$1&id=$3',
        'priority' => 80,
    ],
    # просмотр объявления (при изменении формирования URL)
    'bbs-view-geo' => [
        'pattern'  => '(.*)/search/(.*)/(.*)\-([\d]+)\.html',
        'callback' => 'bbs/view/item_view=1&cat=$2&id=$4',
        'priority' => 90,
    ],
    # просмотр объявления + посадочные страницы категорий
    'bbs-view-landingpages' => [
        'pattern'  => '(.*)/(.*)\-([\d]+)\.html',
        'callback' => 'bbs/view/item_view=1&id=$3',
        'priority' => 100,
    ],
    # поиск объявлений
    'bbs-search' => [
        'pattern'  => 'search(.*)',
        'callback' => 'bbs/search/cat=$1',
        'priority' => 110,
    ],
    # поиск объявлений (при изменении формирования URL)
    'bbs-search-geo' => [
        'pattern'  => '(.*)/search(.*)',
        'callback' => 'bbs/search/cat=$2',
        'priority' => 120,
    ],
    # объявления: добавление, редактирование, активация ...
    'bbs-item-actions' => [
        'pattern'  => 'item/(.*)',
        'callback' => 'bbs/$1/',
        'priority' => 130,
    ],
    # услуга платного расширения лимитов
    'bbs-payed-limits' => [
        'pattern'  => 'limits(.*)',
        'callback' => 'bbs/limits_payed/',
        'priority' => 140,
    ],
    # rss-лента
    'bbs-rss' => [
        'pattern'  => 'rss(.*)',
        'callback' => 'bbs/rss/',
        'priority' => 150,
    ],
    # прайс-лист Яндекс.Маркет
    'bbs-yml' => [
        'pattern'  => 'yml(.*)',
        'callback' => 'bbs/yml/',
        'priority' => 160,
    ],
];