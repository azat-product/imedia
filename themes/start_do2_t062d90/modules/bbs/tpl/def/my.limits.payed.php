<?php
?>
<?php if( ! empty($shopNavigation)) {
$navs = array(
  1 => array('shop' => 1, 't' => _t('users', 'Магазин'), 'link' => Shops::url('my.limits.payed')),
  0 => array('shop' => 0, 't' => _t('users', 'Частные объявления'), 'link' => BBS::url('my.limits.payed')),
  );
?>

<div class="usr-content-top">
  <ul class="nav nav-pills nav-pills-sm">
    <?php foreach($navs as $v) { ?>
      <li class="u-cabinet__sub-navigation__sort<?= $shop == $v['shop'] ? '  active' : '' ?>"><a href="<?= $v['link'] ?>"><?= $v['t'] ?></a></li>
    <?php } ?>
  </ul>
</div>

<?php } # $shopNavigation ?>

<?php if( ! empty($points)): ?>
  <h2 class="l-pageSubheading"><?= _t('bbs', 'Купленные платные пакеты'); ?></h2>
<?php foreach($points as $v):
  $cnt = $v['cnt'] - $v['free'];
  if($cnt < 0 ) $cnt = 0;
  $fst = true;
  foreach($v['limits'] as $vv):
    $rest = $cnt > $vv['items'] ? 0 : $vv['items'] - $cnt; $cnt -= $vv['items']; if($cnt < 0 ) $cnt = 0;
?>
  <div class="usr-limit">
    <div class="usr-limit-services">
      <strong><?= $v['parent'] ?></strong><br>
      <?= $v['title'] ?>
      <div class="usr-limit-services-count">
        <?php $am = $fst ? $vv['items'] + $v['free'] : $vv['items']; ?>
        <span class="label label-default"><?= $am ?></span> <?= tpl::declension($am, _t('bbs', 'объявление;объявления;объявлений'), false) ?> <?= $fst && $v['free'] ? _t('bbs', 'включая бесплатные') : '' ?>
      </div>
    </div>
    <div class="usr-limit-stat">
      <strong><?= $rest ?></strong> <?= tpl::declension($rest, _t('bbs', 'объявление осталось;объявления осталось;объявлений осталось'), false) ?><br>
      <small><?= _t('bbs', 'Активно: [n] из [m]', array('n' => $vv['items'] - $rest, 'm' => $vv['items'])); ?></small>
      <a href="<?= BBS::url('limits.payed', array('point' => $v['cat_id'], 'shop' => $shop)) ?>" class="btn btn-info btn-block mrgt10"><?= _t('bbs', 'Расширить'); ?></a>
    </div>
    <?php if($term): $days = strtotime($vv['expire']) - time(); $days = round($days / 86400); ?>
    <div class="usr-limit-stat">
      <strong><?= $days ?></strong> <?= tpl::declension($days, _t('bbs', 'день остался;дня осталось;дней осталось'), false) ?><br>
      <small><?= _t('bbs', 'Активно до [date]', array('date' => tpl::date_format2($vv['expire']))); ?></small>
      <?php if( ! empty($vv['allowExtend'])): ?>
      <a href="<?= BBS::url('limits.payed', array('id' => $vv['id'], 'extend' => 1)) ?>" class="btn btn-success btn-block mrgt10"><?= _t('bbs', 'Продлить'); ?></a>
    <?php else: ?>
    <div data-placement="bottom" title="<?= _te('bbs', 'Вы не можете продлить пакет'); ?>" data-toggle="tooltip">
      <a class="btn btn-success btn-block mrgt10 disabled" href="#"><?= _t('bbs', 'Продлить'); ?></a>
    </div>
  <?php endif; ?>
  </div>
  <?php endif; ?>
  </div>
<?php $fst = false;
endforeach;
endforeach; else: ?>
  <div class="alert alert-info"><?= _t('bbs', 'Нет активных платных пакетов'); ?></div>
<?php endif; ?>

