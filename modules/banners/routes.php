<?php

return [
    # баннеры: отображение, переход
    'banners-click' => [
        'pattern'  => 'bn/click/{id}',
        'callback' => 'banners/click/id=$1',
        'priority' => 260,
    ],
    'banners-show' => [
        'pattern'  => 'bn/show/{id}',
        'callback' => 'banners/show/id=$1',
        'priority' => 265,
    ],
];