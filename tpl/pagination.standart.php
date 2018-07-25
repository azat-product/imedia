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
<? if( $settings['arrows'] && ($prev || $next) ) { ?>
<ul class="pager pull-left">
    <li><a<? if($prev){ echo $prev; } else { ?> href="javascript:void(0);" class="disabled grey"<? } ?>>&larr; <?= _t('pgn', 'Предыдущая') ?></a></li>
    <li><a<? if($next){ echo $next; } else { ?> href="javascript:void(0);" class="disabled grey"<? } ?>><?= _t('pgn', 'Следующая') ?> &rarr;</a></li>
</ul>
<? } ?>
<? if( $settings['pageto'] ) { ?>
<div class="pageto pull-right hidden-phone">
    <form onsubmit="return false;" class="form-inline grey f11">
        <?= _t('', 'Перейти на страницу') ?>
        <input type="text" class="j-pgn-goto" placeholder="№" />
    </form>
</div>
<? } ?>
<div class="clearfix"></div>
<div class="pagination j-pgn-pages">
    <ul>
        <? if($first) { ?><li><a<?= $first['attr'] ?>><?= $first['page'] ?></a></li><li><a<?= $first['dots'] ?>>...</a></li><? } ?>
        <? foreach($pages as $v) { ?>
            <li<? if($v['active']){ ?> class="active"<? } ?>><a<?= $v['attr'] ?>><?= $v['page'] ?></a></li>
        <? } ?>
        <? if($last) { ?><li><a<?= $last['dots'] ?>>...</a></li><? if ($settings['pagelast']) { ?><li><a<?= $last['attr'] ?>><?= $last['page'] ?></a></li><? } } ?>
    </ul>
</div>
<!-- END pagination.standart -->