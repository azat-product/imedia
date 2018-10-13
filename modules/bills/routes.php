<?php

return [
    # кабинет: история операций
    'bills-my.history' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) {
            $p['tab'] = 'bill';
            return $p;
        },
    ],
    # кабинет: пополнение счета
    'bills-my.pay' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) {
            $p['tab'] = 'bill';
            $p['pay'] = 1;
            return $p;
        },
    ],
    # процессинг оплаты услуг
    'bills-process' => [
        'pattern'  => 'bill/process/{ps}',
        'callback' => 'bills/processPayRequest/psystem=$1',
        'priority' => 320,
    ],
    # результат процессинга: успешно
    'bills-success' => [
        'pattern'  => 'bill/success',
        'callback' => 'bills/success/',
        'priority' => 330,
    ],
    # результат процессинга: ошибка
    'bills-fail' => [
        'pattern'  => 'bill/fail',
        'callback' => 'bills/fail/',
        'priority' => 332,
    ],
    # результат процессинга: result
    'bills-result' => [
        'pattern'  => 'bill/result',
        'callback' => 'bills/result/',
        'priority' => 334,
    ],
];