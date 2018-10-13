<?php

$prefixList = bff::urlPrefix('shops', 'list', 'shops');
$prefixView = bff::urlPrefix('shops', 'view', 'shop');

return [
    # просмотр магазина + страницы
    'shops-shop.view' => [
        'pattern'  => $prefixView.'/{keyword}-{id}{tab?}',
        'callback' => 'shops/view/id=$2&tab=$3',
        'url'      => function($p, $o) use ($prefixView) {
            # формируем ссылку с учетом указанной области (region), [города (city)]
            # либо с учетом текущих настроек фильтра по региону
            return Geo::url($p, $o['dynamic']) . $prefixView . '/' .
                (!empty($p['keyword']) ? $p['keyword'] : '{keyword}') . '-' .
                (!empty($p['id']) ? $p['id'] : '{id}');
        },
        'priority' => 210,
    ],
    # просмотр магазина + страницы (при изменении формирования URL: host/region/ => host/)
    'shops-shop.view-geo' => [
        'pattern'  => '{any}/'.$prefixView.'/{keyword}-{id}{tab?}',
        'callback' => 'shops/view/id=$3&tab=$4',
        'priority' => 220,
    ],
    # действия: открытие магазина, продвижение, ...
    'shops-actions' => [
        'pattern'  => $prefixView.'/{action}',
        'callback' => 'shops/$1/',
        'priority' => 230,
    ],
    # поиск магазинов (список)
    'shops-search' => [
        'pattern'  => $prefixList.'{/keyword/?}',
        'callback' => 'shops/search/cat=$1',
        'url'      => function($p, $o) use ($prefixList) {
            # формируем ссылку с учетом указанной области (region), [города (city)]
            # либо с учетом текущих настроек фильтра по региону
            return Geo::url($p, $o['dynamic']) . $prefixList . '/'
                . (!empty($p['keyword']) ? $p['keyword'] . '/' : '');
        },
        'priority' => 240,
    ],
    # поиск магазинов (при изменении формирования URL: host/region/ => host/)
    'shops-search-geo' => [
        'pattern'  => '{any}/'.$prefixList.'{/keyword/?}',
        'callback' => 'shops/search/cat=$2',
        'priority' => 250,
    ],
    # страница продвижения магазина
    'shops-shop.promote' => [
        'alias'    => 'shops-actions',
        'before'   => function($p){ $p['action'] = 'promote'; return $p; },
    ],
    # заявка на закрепление магазина за пользователем
    'shops-request' => [
        'alias'    => 'shops-actions',
        'before'   => function($p){ $p['action'] = 'request'; return $p; },
    ],
    # форма открытия магазина
    'shops-my.open' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p){ $p['tab'] = 'shop/open'; return $p; },
    ],
    # кабинет: форма смены абонемента
    'shops-my.abonement' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p){ $p['tab'] = 'shop/abonement'; return $p; },
    ],
    # кабинет: магазин
    'shops-my.shop' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p){ $p['tab'] = 'shop'; return $p; },
    ],
    # купленные услуги платного расширения лимитов
    'shops-my.limits.payed' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p){ $p['tab'] = 'limits'; $p['shop'] = 1; return $p; },
    ],
];