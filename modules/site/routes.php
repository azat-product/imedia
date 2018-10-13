<?php

return [
    # главная страница
    'index' => [
        'pattern'  => '',
        'callback' => 'site/index/',
        'priority' => 10,
    ],
    # статические страницы
    'page' => [
        'pattern'  => '{filename}.html',
        'callback' => 'site/pageView/page=$1',
        'where'    => array(
            'filename' => '([a-z0-9\-]+)',
        ),
        'priority' => 270,
    ],
    # страница "Услуги"
    'services' => [
        'pattern'  => 'services{/any?}',
        'callback' => 'site/services/',
        'priority' => 280,
    ],
    # внешние ссылки
    'away' => [
        'pattern'  => 'away{/any?}',
        'callback' => 'site/away/',
        'priority' => 290,
    ],
    # карта сайта
    'sitemap' => [
        'pattern'  => 'sitemap{/any?}',
        'callback' => 'site/sitemap/',
        'priority' => 390,
    ],
];