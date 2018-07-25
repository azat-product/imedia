<?php
/**
 * Постраничная навигация: стандартный вид
 * @var $pages array ссылки на страницы
 * @var $first array первая страница
 * @var $last array последняя страница
 * @var $total integer общее кол-во страниц
 * @var $settings array дополнительные настройки
 */

# данные отсутствуют (общее их кол-во == 0)
if( $total <= 1 || ! $settings['pages'] ) return;

?>
<!-- START pagination.standart -->
<div class="pagination pagination-centered j-pgn-pages">
    <ul>
        <? if( $settings['arrows'] ) { ?>
            <li><a<? if($prev){ echo $prev; } else { ?> href="javascript:void(0);" class="disabled grey"<? } ?>><span>&larr;</span><span class="hidden-phone"> <?= _t('pgn', 'Предыдущая') ?></span></a></li>
        <? } ?>
        <? if($first) { ?><li><a<?= $first['attr'] ?>><?= $first['page'] ?></a></li><li><a<?= $first['dots'] ?>>...</a></li><? } ?>
        <? foreach($pages as $v) { ?>
            <li<? if($v['active']){ ?> class="active"<? } ?>><a<?= $v['attr'] ?>><?= $v['page'] ?></a></li>
        <? } ?>
        <? if($last) { ?><li><a<?= $last['dots'] ?>>...</a></li><li><a<?= $last['attr'] ?>><?= $last['page'] ?></a></li><? } ?>
        <? if( $settings['arrows'] ) { ?>
            <li><a<? if($next){ echo $next; } else { ?> href="javascript:void(0);" class="disabled grey"<? } ?>><span class="hidden-phone"><?= _t('pgn', 'Следующая') ?> </span><span>&rarr;</span></a></li>
        <? } ?>
    </ul>
</div>
<!-- END pagination.standart -->