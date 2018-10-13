<?php

return [
    # помощь: просмотр вопроса
    'help-view' => [
        'pattern'  => 'help/(.*)\-([\d]+)\.html',
        'callback' => 'help/view/id=$2',
        'priority' => 20,
    ],
    # помощь: поиск
    'help-search' => [
        'pattern'  => 'help/search(.*)',
        'callback' => 'help/search/',
        'priority' => 30,
    ],
    # помощь: список вопросов (в категории)
    'help-list' => [
        'pattern'  => 'help(.*)',
        'callback' => 'help/listing/cat=$1',
        'priority' => 40,
    ],
];