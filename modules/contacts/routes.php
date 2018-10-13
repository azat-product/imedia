<?php

return [
    # форма контактов
    'contacts-form' => [
        'pattern'  => 'contact{/any?}',
        'callback' => 'contacts/write/',
        'priority' => 310,
    ],
];