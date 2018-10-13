<?php

return [
    # sitemap.xml
    'seo-sitemap-xml' => [
        'pattern'  => 'sitemap.xml{any?}',
        'callback' => 'seo/sitemap_xml/',
        'priority' => 360,
    ],
    'seo-sitemap-xml-part' => [
        'pattern'  => '{region}sitemap{number}.xml',
        'callback' => 'seo/sitemap_xml_part/',
        'where'    => array(
            'region' => '([a-z_]*)',
            'number' => '([0-9]+)',
        ),
        'priority' => 370,
    ],
    'seo-sitemap-xml-part-gz' => [
        'pattern'  => '{region}sitemap{number}.xml.gz',
        'callback' => 'seo/sitemap_xml_part_gz/',
        'where'    => array(
            'region' => '([a-z_]*)',
            'number' => '([0-9]+)',
        ),
        'priority' => 380,
    ],
    # robots.txt
    'seo-robots-txt' => [
        'pattern'  => 'robots.txt{any?}',
        'callback' => 'seo/robots_txt/',
        'priority' => 400,
    ],

];