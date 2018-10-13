<?php

$prefix = bff::urlPrefix('bbs', 'list', 'search');

return [
    # просмотр объявления
    'bbs-view' => [
        'pattern'  => $prefix.'/{category}/{keyword}-{id}.html',
        'callback' => 'bbs/view/item_view=1&cat=$1&id=$3',
        'priority' => 80,
    ],
    # просмотр объявления (при изменении формирования URL)
    'bbs-view-geo' => [
        'pattern'  => '{any}/'.$prefix.'/{category}/{keyword}-{id}.html',
        'callback' => 'bbs/view/item_view=1&cat=$2&id=$4',
        'priority' => 90,
    ],
    # просмотр объявления + посадочные страницы категорий
    'bbs-view-landingpages' => [
        'pattern'  => '{any}/{keyword}-{id}.html',
        'callback' => 'bbs/view/item_view=1&id=$3',
        'priority' => 100,
    ],
    # поиск объявлений
    'bbs-items.search' => [
        'pattern'  => $prefix.'{/keyword/?}',
        'callback' => 'bbs/search/cat=$1',
        'url'      => function($p, $o) use ($prefix) {
            # формируем ссылку с учетом указанной области (region), [города (city)]
            # либо с учетом текущих настроек фильтра по региону
            if (empty($p['landing_url'])) {
                $url = Geo::url($p, $o['dynamic']) . $prefix.'/' . (!empty($p['keyword']) ? $p['keyword'] . '/' : '');
            } else {
                $url = Geo::url($p, $o['dynamic'], false) . $p['landing_url'];
            }
            # формируем ссылку на объявление
            if ( ! empty($p['item'])) {
                $url = rtrim($url, '/').'/'.$p['item']['keyword'].'-'.(!empty($p['item']['id']) ? $p['item']['id'] : '{item-id}').'.html';
            }
            return $url;
        },
        'priority' => 110,
    ],
    # поиск объявлений (при изменении формирования URL: host/region/ => host/)
    'bbs-items.search-geo' => [
        'pattern'  => '{any}/'.$prefix.'{/keyword/?}',
        'callback' => 'bbs/search/cat=$2',
        'priority' => 120,
    ],
    # объявления: добавление, редактирование, активация ...
    'bbs-item-actions' => [
        'pattern'  => 'item/{action}',
        'callback' => 'bbs/$1/',
        'priority' => 130,
    ],
    # объявления: активация
    'bbs-item.activate' => [
        'alias'    => 'bbs-item-actions',
        'before'   => function($p){ $p['action'] = 'activate'; return $p; },
    ],
    # объявления: форма добавление объявления
    'bbs-item.add' => [
        'alias'    => 'bbs-item-actions',
        'before'   => function($p){ $p['action'] = 'add'; return $p; },
    ],
    # объявления: форма редактирования объявления
    'bbs-item.edit' => [
        'alias'    => 'bbs-item-actions',
        'before'   => function($p){ $p['action'] = 'edit'; return $p; },
    ],
    # объявления: форма добавления копии объявления
    'bbs-item.copy' => [
        'alias'    => 'bbs-item-actions',
        'before'   => function($p){ $p['action'] = 'copy'; return $p; },
    ],
    # объявления: страница продвижения объявления
    'bbs-item.promote' => [
        'alias'    => 'bbs-item-actions',
        'before'   => function($p){ $p['action'] = 'promote'; return $p; },
    ],
    # кабинет: список моих объявлений
    'bbs-my.items' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) { $p['tab'] = 'items'; return $p; },
    ],
    # кабинет: импорт
    'bbs-my.import' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) { $p['tab'] = 'import'; return $p; },
    ],
    # кабинет: список избранных объявлений
    'bbs-my.favs' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) { $p['tab'] = 'favs'; return $p; },
    ],
    # покупка услуги платного расширения лимитов
    'bbs-limits.payed' => [
        'pattern'  => 'limits{any?}',
        'callback' => 'bbs/limits_payed/',
        'priority' => 140,
    ],
    # кабинет: услуги платного расширения лимитов
    'bbs-my.limits.payed' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) { $p['tab'] = 'limits'; return $p; },
    ],
    # RSS-лента
    'bbs-rss' => [
        'pattern'  => 'rss{/any?}',
        'callback' => 'bbs/rss/',
        'priority' => 150,
    ],
    # прайс-лист Яндекс.Маркет
    'bbs-yml' => [
        'pattern'  => 'yml{/any?}',
        'callback' => 'bbs/yml/',
        'priority' => 160,
    ],
];