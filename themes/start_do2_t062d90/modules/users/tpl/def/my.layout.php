<?php
/**
 * Кабинет пользователя (Основное меню)
 * @var $this Users
 * @var $user array данные пользователя
 * @var $tabs array табы
 * @var $shop_open array ссылка на форму открытия магазина или FALSE (магазин уже был открыт)
 * @var $content string содержание текущего раздела кабинета (HTML)
 */
?>
<?php if ($user){ ?>
<div class="l-pageHeading">
  <h1 class="l-pageHeading-title"><?= _t('users', 'Кабинет пользователя') ?>: <small><?= $user['name'] ?></small></h1>
</div>

<a href="#user-tabs" data-toggle="collapse" class="btn btn-default btn-toggle btn-toggle_md collapsed">
  <i class="fa fa-chevron-down btn-toggle-ico"></i>
  <?= _t('users', 'Меню') ?>
</a>
<ul class="nav nav-tabs nav-tabs-md collapse" id="user-tabs">
  <?php foreach($tabs as $k=>$v) { if(empty($v['t'])) continue; ?>
  <li<?php if( ! empty($v['active']) ){ ?> class="active"<?php } ?>>
    <a href="<?= $v['url'] ?>"><?= $v['t'] ?>
        <?php if( ! empty($v['counter']) ) { ?>
            <?php if ($k === 'messages') { ?>
                (+ <?= $v['counter']; ?>)
            <?php } else if ($k === 'bill') { ?>
                (<?= $v['counter']; ?>)
            <?php } else { ?>
                <?= $v['counter']; ?>
            <?php } ?>
        <?php } ?>
    </a>
  </li>
  <?php } ?>
  <?php if($shop_open) { ?>
    <li class="pull-right<?php if($shop_open['active']){ ?> active<?php } ?>">
      <a href="<?= $shop_open['url'] ?>">
        <i class="fa fa-plus u-cabinet__main-navigation__shop-open"></i>
        <?= _t('users', 'Открыть магазин') ?>
      </a>
    </li>
  <?php } ?>
</ul>
<?php } ?>

<div class="usr-content">
  <?= $content ?>
</div>