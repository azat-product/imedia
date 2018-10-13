<?php

$hookKey = $this->hookKey();
return array(
    array( # base, start, nelson
        'file' => [
            '/modules/users/tpl/def/profile.php',
            '/themes/start_do2_t062d90/modules/users/tpl/def/profile.contacts.php',
            '/themes/nelson_t001e26/modules/users/tpl/def/profile.owner.php',
        ],
        'search' => '<!-- plugin_user_online_do_block -->',
        'replace' => '<?php bff::hook(\''.$hookKey.'\', $user[\'user_id\'], \'page_userProfile\') ?>',
        'position' => 'replace-line',
        'offset' => 0,
        'index' => [1, 2],
    ),
    array( # base, start
        'file' => '/modules/bbs/tpl/def/item.view.owner.php',
        'search' => '<!-- plugin_user_online_do_block -->',
        'replace' => '<?php bff::hook(\''.$hookKey.'\', $user[\'id\'], \'page_itemShopView\') ?>',
        'position' => 'replace-line',
        'offset' => 0,
        'index' => 1,
    ),
    array( # base, start
        'file' => '/modules/bbs/tpl/def/item.view.owner.php',
        'search' => '<!-- plugin_user_online_do_block -->',
        'replace' => '<?php bff::hook(\''.$hookKey.'\', $user[\'id\'], \'page_itemView\') ?>',
        'position' => 'replace-line',
        'offset' => 0,
        'index' => 1,
    ),
    array( # base
        'file' => '/modules/internalmail/tpl/def/my.contacts.list.php',
        'search' => '<!-- plugin_user_online_do_block -->',
        'replace' => '<?php bff::hook(\''.$hookKey.'\', $v[\'user_id\'], \'page_cabinetMessages\') ?>',
        'position' => 'replace-line',
        'offset' => 0,
        'index' => 1,
    ),
    array( # base
        'file' => '/modules/internalmail/tpl/def/my.chat.php',
        'search' => '<!-- plugin_user_online_do_block -->',
        'replace' => '<?php bff::hook(\''.$hookKey.'\', $i[\'user_id\'], \'page_cabinetMessagesChat\') ?>',
        'position' => 'replace-line',
        'offset' => 0,
        'index' => 1,
    ),
);