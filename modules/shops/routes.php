<?php

return [
    # просмотр магазина + страницы
    'shops-view' => [
        'pattern'  => 'shop/(.*)\-([\d]+)(.*)',
        'callback' => 'shops/view/id=$2&tab=$3',
        'priority' => 210,
    ],
    # просмотр магазина + страницы (при изменении формирования URL)
    'shops-view-geo' => [
        'pattern'  => '(.*)/shop/(.*)\-([\d]+)(.*)',
        'callback' => 'shops/view/id=$3&tab=$4',
        'priority' => 220,
    ],
    # открытие магазина, продвижение, ...
    'shops-actions' => [
        'pattern'  => 'shop/(open|logo|promote|request)',
        'callback' => 'shops/$1/',
        'priority' => 230,
    ],
    # поиск магазинов
    'shops-search' => [
        'pattern'  => 'shops(.*)',
        'callback' => 'shops/search/cat=$1',
        'priority' => 240,
    ],
    # поиск магазинов (при изменении формирования URL)
    'shops-search-geo' => [
        'pattern'  => '(.*)/shops(.*)',
        'callback' => 'shops/search/cat=$2',
        'priority' => 250,
    ],
];