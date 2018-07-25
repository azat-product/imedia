<?php

return array(
    array(
        'file' => '/modules/bbs/tpl/def/search.cats.desktop.php',
        'search' => '<span class="count f12"><?= number_format($total, 0, \'.\', \' \') ?> <?= tpl::declension($total, _t(\'filter\',\'объявление;объявления;объявлений\'), false) ?> - ',
        'replace' => '',
        'position' => 'replace',
    ),
    array(
        'file' => '/modules/bbs/bbs.class.php',
        'search' => '$catData = bff::filterData(\'bbs-search-category\');',
        'replace' => '$catData = bff::filter(\'counters.catData\', $catData);',
        'position' => 'after',
    ),
    array(
        'file' => '/modules/bbs/tpl/def/search.form.php',
        'search' => '_te(\'bbs\',\'Поиск объявлений...\')',
        'replace' => '_te(\'bbs\',\'Искать среди [amount]\', array(\'amount\' => tpl::declension(array_sum(array_column($catData[\'items\'], \'items\')), _t(\'bbs\', \'объявление;объявления;объявлений\'))));',
        'position' => 'replace',
    ),
);
