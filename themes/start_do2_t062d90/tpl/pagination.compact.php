<?php
/**
 * Постраничная навигация: компактный вид
 * @var $pages array ссылки на страницы
 * @var $prev array предыдущая страница
 * @var $next array следующая страница
 * @var $first array первая страница
 * @var $last array последняя страница
 * @var $total integer общее кол-во страниц
 * @var $settings array дополнительные настройки
 */

# данные отсутствуют (общее их кол-во == 0)
if( $total <= 1 || ! $settings['pages'] ) return;

?>
<!-- START pagination.compact -->
<div class="j-pgn-pages">
  <ul class="pagination">
    <?php if( $settings['arrows'] ) { ?>
    <li><a<?php if($prev){ echo $prev; } else { ?> href="javascript:void(0);" class="disabled grey"<?php } ?>>&larr;<span class="hidden-xs"> <?= _t('pgn', 'Предыдущая') ?></span></a></li>
    <?php } ?>
    <?php if($first) { ?><li><a<?= $first['attr'] ?>><?= $first['page'] ?></a></li><li><a<?= $first['dots'] ?>>...</a></li><?php } ?>
    <?php foreach($pages as $v) { ?>
    <li<?php if($v['active']){ ?> class="active"<?php } ?>><a<?= $v['attr'] ?>><?= $v['page'] ?></a></li>
    <?php } ?>
    <?php if($last) { ?><li><a<?= $last['dots'] ?>>...</a></li><li><a<?= $last['attr'] ?>><?= $last['page'] ?></a></li><?php } ?>
    <?php if( $settings['arrows'] ) { ?>
    <li><a<?php if($next){ echo $next; } else { ?> href="javascript:void(0);" class="disabled"<?php } ?>><span class="hidden-xs"><?= _t('pgn', 'Следующая') ?> </span>&rarr;</a></li>
    <?php } ?>
  </ul>
</div>
<!-- END pagination.compact -->