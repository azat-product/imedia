<?php
/**
 * Кабинет пользователя: Сообщения - список контактов
 * @var $this InternalMail
 * @var $list array список контактов
 * @var $my_shop_id integer ID магазина текущего пользователя
 */

$lng_fav = _te('internalmail', 'Избранные');
$lng_ignore = _te('internalmail', 'Игнорирую');
$lng_blocked = _t('internalmail', 'Сообщение заблокировано модератором');

$inFolder = function(&$userFolders, $folderID){
  return ( ! empty($userFolders) && in_array($folderID, $userFolders) );
};

foreach($list as &$v) { $message = &$v['message']; ?>

<div class="usr-dialogs-item<?php if ($v['msgs_new']>0) { ?> active<?php } ?>">
  <?php if (DEVICE_DESKTOP) { ?>
  <div class="usr-dialogs-item-avatar">
    <a href="<?= $v['c_url'] ?>">
      <img src="<?= $v['c_logo'] ?>" alt="" />
    </a>
  </div>
  <?php } ?>
  <div class="usr-dialogs-item-content j-contact" data-contact="<?= $v['c_url'] ?>">
    <div class="usr-dialogs-item-content-top">
      <div class="usr-dialogs-item-title">
        <a href="<?= $v['c_url'] ?>"><?= $v['c_name'] ?></a>
        <!-- plugin_user_online_do_block -->
        <?php if ($v['msgs_new']>0) { ?>
          <span class="label label-success">+<?= $v['msgs_new'] ?></span><?php } else { ?><span class="label label-default"><?= $v['msgs_total'] ?></span>
        <?php } ?>
        <?php if ($v['shop_id'] && $v['shop_id_my']) { ?>
          <span class="text-success"><i class="fa fa-shopping-cart"></i></span>
        <?php } ?>
      </div>
      <div class="usr-dialogs-item-actions">
        <?php if (InternalMail::foldersEnabled()) { ?>
        <a title="<?= $lng_fav ?>" data-user-id="<?= $v['user_id'] ?>" data-shop-id="<?= $v['shop_id'] ?>" data-folder-id="<?= InternalMail::FOLDER_FAVORITE ?>" class="btn btn-fav btn-xs has-tooltip j-f-action<?php if ( $inFolder($v['folders'], InternalMail::FOLDER_FAVORITE) ) { ?> active<?php } ?>" href="#"><span><i class="fa fa-star white"></i></span></a>
        <a title="<?= $lng_ignore ?>" data-user-id="<?= $v['user_id'] ?>" data-shop-id="<?= $v['shop_id'] ?>" data-folder-id="<?= InternalMail::FOLDER_IGNORE ?>" class="btn btn-delete btn-xs has-tooltip j-f-action<?php if ( $inFolder($v['folders'], InternalMail::FOLDER_IGNORE) ) { ?> active<?php } ?>" href="#"><span><i class="fa fa-ban white"></i></span></a>
        <?php } ?>
      </div>
    </div><!-- /.usr-dialogs-item-content-top -->
    <div class="usr-dialogs-item-msg">
      <div class="usr-dialogs-item-msg-date">
        <?= tpl::date_format3($message['created']) ?>
      </div>
      <?php if ($message['item_id'] > 0 && ! empty($v['item']) ) { ?>
        <div class="usr-dialogs-item-msg-title">
          <?= $v['item']['title'] ?>
        </div>
      <?php } ?>
      <div class="usr-dialogs-item-msg-text">
        <?php if (!$message['blocked']) { ?>
          <?= tpl::truncate(strip_tags($message['message']), 200); ?>
          <?php } else { ?>
          <i><?= $lng_blocked ?></i>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<?php } unset ($v);

if (empty($list)) {
  echo $this->showInlineMessage(_t('internalmail', 'Список сообщений пустой'));
} else {
  unset($v, $message);
}