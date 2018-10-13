<?php

return [
    # процессинг оплаты услуг
    'bills-process' => [
        'pattern'  => 'bill/process/(.*)',
        'callback' => 'bills/processPayRequest/psystem=$1',
        'priority' => 320,
    ],
    # результат процессинга
    'bills-result' => [
        'pattern'  => 'bill/(success|fail|result)',
        'callback' => 'bills/$1/',
        'priority' => 330,
    ],
];