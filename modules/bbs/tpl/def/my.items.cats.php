<?php
/**
 * Кабинет пользователя: Мои объявления - категории
 * @var $this BBS
 * @var $cats array категории
 */

foreach ($cats as $v):
    if( empty($v['sub']) ) {
        ?><li><a href="javascript:void(0);" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?
    } else {
        ?><li class="nav-header"><?= $v['title'] ?></li><?
        foreach ($v['sub'] as $vv):
            ?><li><a href="javascript:void(0);" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?
        endforeach;
    }
endforeach;