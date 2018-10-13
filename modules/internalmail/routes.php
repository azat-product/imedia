<?php

return [
    # кабинет: сообщения пользователя
    'internalmail-my.messages' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) {
            $p['tab'] = 'messages';
            return $p;
        },
    ],
    # кабинет: переписка c пользователем
    'internalmail-my.chat' => [
        'alias'    => 'users-cabinet',
        'before'   => function($p) {
            $p['tab'] = 'messages/chat';
            return $p;
        },
    ],
    # кабинет: переписка c пользователем
    'internalmail-item.messages' => [
        'alias'    => 'internalmail-my.messages',
        'before'   => function($p) {
            if ( ! empty($p['item']) ) {
                $p['qq'] = 'item:'.$p['item'];
                unset($p['item']);
            }
            return $p;
        },
    ],
];