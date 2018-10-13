<?php

return [
    'users-auth' => [
        'pattern'  => 'user/{action}',
        'callback' => 'users/$1/',
        'priority' => 180,
    ],
    # авторизация
    'users-login' => [
        'alias'    => 'users-auth',
        'before'   => function($p){ $p['action'] = 'login'; return $p; },
    ],
    # выход
    'users-logout' => [
        'alias'    => 'users-auth',
        'before'   => function($p){ $p['action'] = 'logout'; return $p; },
    ],
    # регистрация
    'users-register' => [
        'alias'    => 'users-auth',
        'before'   => function($p){ $p['action'] = 'register'; return $p; },
    ],
    # восстановление пароля
    'users-forgot' => [
        'alias'    => 'users-auth',
        'before'   => function($p){ $p['action'] = 'forgot'; return $p; },
    ],
    # активация аккаунта
    'users-activate' => [
        'alias'    => 'users-auth',
        'before'   => function($p){ $p['action'] = 'activate'; return $p; },
    ],
    # смена email адреса
    # - ссылка подтверждения email-адреса при смене инициированной из кабинета
    'users-email_change' => [
        'alias'    => 'users-auth',
        'before'   => function($p){ $p['action'] = 'email_change'; return $p; },
    ],
    # отписаться от рассылки
    'users-unsubscribe' => [
        'alias'    => 'users-auth',
        'before'   => function($p){ $p['action'] = 'unsubscribe'; return $p; },
    ],
    # авторизация через соц. сети
    'users-login.social' => [
        'pattern'  => 'user/loginsocial/{provider?}',
        'callback' => 'users/loginSocial/provider=$1',
        'priority' => 170,
    ],
    # профиль пользователя
    'users-user.profile' => [
        'pattern'  => 'users/{login}/{tab/?}',
        'callback' => 'users/profile/login=$1&tab=$2',
        'where'    => [
            'login' => '([^/]+)',
        ],
        'priority' => 190,
    ],
    # кабинет пользователя
    'users-cabinet' => [
        'pattern'  => 'cabinet/{tab}',
        'callback' => 'users/my/tab=$1',
        'priority' => 200,
    ],
    # кабинет: настройки профиля
    # t = подтаб
    'users-my.settings' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p){
            $p['tab'] = 'settings';
            return $p;
        },
    ],
    # пользовательское соглашение
    'users-agreement' => [
        'alias'    => 'page',
        'before'   => function($p){
            $p['filename'] = strtr(config::sys('users.agreement.page', 'agreement.html', TYPE_STR), array(
                '.html' => '',
            ));
            return $p;
        },
    ],
];