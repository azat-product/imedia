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
<?php if( $settings['pageto'] || $settings['arrows'] )  { ?>
  <div class="pager-wrapper">
    <?php if( $settings['pageto'] ) { ?>
    <form onsubmit="return false;" class="form-inline pager-input hidden-xs">
      <div class="form-group">
        <label for="page-num"><?= _t('', 'Перейти на страницу') ?></label>
        <input type="text" id="page-num" class="j-pgn-goto form-control" placeholder="№" />
      </div>
    </form>
    <?php } ?>
    <?php if( $settings['arrows'] && ($prev || $next) ) { ?>
    <ul class="pager">
      <li><a<?php if($prev){ echo $prev; } else { ?> href="javascript:void(0);" class="disabled"<?php } ?>><i class="fa fa-angle-left"></i> <?= _t('pgn', 'Предыдущая') ?></a></li>
      <li><a<?php if($next){ echo $next; } else { ?> href="javascript:void(0);" class="disabled"<?php } ?>><?= _t('pgn', 'Следующая') ?> <i class="fa fa-angle-right"></i></a></li>
    </ul>
    <?php } ?>
  </div>
<?php } ?>
<div class="j-pgn-pages hidden-xs">
  <ul class="pagination">
    <?php if($first) { ?><li><a<?= $first['attr'] ?>><?= $first['page'] ?></a></li><li><a<?= $first['dots'] ?>>...</a></li><?php } ?>
    <?php foreach($pages as $v) { ?>
    <li<?php if($v['active']){ ?> class="active"<?php } ?>><a<?= $v['attr'] ?>><?= $v['page'] ?></a></li>
    <?php } ?>
    <?php if($last) { ?><li><a<?= $last['dots'] ?>>...</a></li><?php if ($settings['pagelast']) { ?><li><a<?= $last['attr'] ?>><?= $last['page'] ?></a></li><?php } } ?>
  </ul>
</div>
<!-- END pagination.standart -->