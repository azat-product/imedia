<?php

return [
    # главная страница
    'index' => [
        'pattern'  => '',
        'callback' => 'site/index/',
        'priority' => 10,
    ],
    # статические страницы
    'pages' => [
        'pattern'  => '([a-z0-9\-]+)\.html',
        'callback' => 'site/pageView/page=$1',
        'priority' => 270,
    ],
    # услуги - список
    'services-list' => [
        'pattern'  => 'services(.*)',
        'callback' => 'site/services/',
        'priority' => 280,
    ],
    # внешние ссылки
    'away' => [
        'pattern'  => 'away/(.*)',
        'callback' => 'site/away/',
        'priority' => 290,
    ],
    # карта сайта
    'sitemap' => [
        'pattern'  => 'sitemap(.*)',
        'callback' => 'site/sitemap/',
        'priority' => 300,
    ],
];