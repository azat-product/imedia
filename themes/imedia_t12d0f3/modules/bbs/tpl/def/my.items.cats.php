<?php
/**
 * Кабинет пользователя: Мои объявления - категории
 * @var $this BBS
 * @var $cats array категории
 */

foreach ($cats as $v):
    if( empty($v['sub']) ) {
        ?><li><a href="#" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?php
    } else {
        ?><li class="dropdown-menu-nav-header"><?= $v['title'] ?></li><?php
        foreach ($v['sub'] as $vv):
            ?><li><a href="#" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?php
        endforeach;
    }
endforeach;