<?php

return [
    # баннеры: отображение, переход
    'banners-view' => [
        'pattern'  => 'bn/(click|show)/(.*)',
        'callback' => 'banners/$1/id=$2',
        'priority' => 260,
    ],
];