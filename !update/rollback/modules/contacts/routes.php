<?php

return [
    # форма контактов
    'contacts-form' => [
        'pattern'  => 'contact/?',
        'callback' => 'contacts/write/',
        'priority' => 310,
    ],
];