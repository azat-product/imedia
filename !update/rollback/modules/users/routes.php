<?php

return [
    # авторизация через соц. сети
    'users-loginsocial' => [
        'pattern'  => 'user/loginsocial/(.*)',
        'callback' => 'users/loginSocial/provider=$1',
        'priority' => 170,
    ],
    # авторизация, регистрация ...
    'users-actions' => [
        'pattern'  => 'user/(.*)',
        'callback' => 'users/$1/',
        'priority' => 180,
    ],
    # профиль пользователя
    'users-profile' => [
        'pattern'  => 'users/([^/]+)/(.*)',
        'callback' => 'users/profile/login=$1&tab=$2',
        'priority' => 190,
    ],
    # кабинет пользователя
    'users-cabinet' => [
        'pattern'  => 'cabinet/(.*)',
        'callback' => 'users/my/tab=$1',
        'priority' => 200,
    ],
];