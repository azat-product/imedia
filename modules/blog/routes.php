<?php

$prefix = bff::urlPrefix('blog', 'list', 'blog');

return [
    # блог: просмотр поста
    'blog-view' => [
        'pattern'  => $prefix.'/{keyword}-{id}.html',
        'callback' => 'blog/view/id=$2',
        'priority' => 50,
        'before'   => function($p) {
            if (isset($p['title'])) {
                $p['keyword'] = mb_substr(mb_strtolower(\func::translit($p['title'])), 0, 100);
                unset($p['title']);
            }
            return $p;
        },
    ],
    # блог: список постов по тегу
    'blog-tag' => [
        'pattern'  => $prefix.'/tag/{tag}-{id}',
        'callback' => 'blog/listingTag/tag=$2',
        'priority' => 60,
    ],
    # блог: список постов в категории
    'blog-cat' => [
        'pattern'  => $prefix.'/{keyword/}',
        'callback' => 'blog/listing/cat=$1',
        'priority' => 70,
    ],
    # блог: главная
    'blog-index' => [
        'pattern'  => $prefix.'{/any?}',
        'callback' => 'blog/listing/',
        'priority' => 70,
    ],
];