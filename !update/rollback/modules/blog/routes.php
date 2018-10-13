<?php

return [
    # блог: просмотр поста
    'blog-view' => [
        'pattern'  => 'blog/(.*)\-([\d]+)\.html',
        'callback' => 'blog/view/id=$2',
        'priority' => 50,
    ],
    # блог: список постов по тегу
    'blog-list-tag' => [
        'pattern'  => 'blog/tag/(.*)\-([\d]+)',
        'callback' => 'blog/listingTag/tag=$2',
        'priority' => 60,
    ],
    # блог: список постов (в категории)
    'blog-list' => [
        'pattern'  => 'blog(.*)',
        'callback' => 'blog/listing/cat=$1',
        'priority' => 70,
    ],
];