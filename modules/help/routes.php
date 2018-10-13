<?php

$prefix = bff::urlPrefix('help', 'list', 'help');

return [
    # помощь: просмотр вопроса
    'help-view' => [
        'pattern'  => $prefix.'/{keyword}-{id}.html',
        'callback' => 'help/view/id=$2',
        'priority' => 20,
        'before'   => function($p) {
            if (isset($p['title'])) {
                $p['keyword'] = mb_substr(mb_strtolower(\func::translit($p['title'])), 0, 100);
                unset($p['title']);
            }
            return $p;
        },
    ],
    # помощь: поиск
    'help-search' => [
        'pattern'  => $prefix.'/search{/any?}',
        'callback' => 'help/search/',
        'priority' => 30,
    ],
    # помощь: список вопросов в категории
    'help-cat' => [
        'pattern'  => $prefix.'/{keyword/}',
        'callback' => 'help/listing/cat=$1',
        'priority' => 35,
    ],
    # помощь: главная
    'help-index' => [
        'pattern'  => $prefix.'{/any?}',
        'callback' => 'help/listing/',
        'priority' => 40,
    ],
];