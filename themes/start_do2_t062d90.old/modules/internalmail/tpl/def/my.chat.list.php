<?php

/**
 * Кабинет пользователя: Сообщения / Переписка - список
 * @var $this InternalMail
 * @var $attach InternalMailAttachment
 * @var $messages array список сообщений
 */

$lang_number = _t('bbs', 'номер');
$lang_blocked = _t('internalmail', 'Сообщение заблокировано модератором');
$date_last = 0;

foreach ($messages as &$v) { ?>
<?php if ($date_last !== $v['created_date']) { ?>
  <div class="usr-chat-box-date">
    <?= tpl::datePublicated($v['created_date'], 'Y-m-d', false, ' ') ?>
  </div>
<?php } ?>
<?php $date_last = $v['created_date']; ?>
<?php if (!$v['blocked']) { ?>
<div class="usr-chat-box-item">
  <div class="usr-chat-box-item-in <?= ($v['my'] ? 'usr-chat-box-item_self' : 'usr-chat-box-item_others') ?>">
    <div class="usr-chat-box-item-content">
      <?= $v['message'] ?>
      <?php if ( InternalMail::attachmentsEnabled() && ! empty($v['attach']) ) { ?>
        <div class="usr-chat-box-item-file"><?= $attach->getAttachLink($v['attach']); ?></div>
      <?php } ?>
    </div>
    <?php if ($v['item_id'] > 0 && ! empty($items[$v['item_id']])) { $item = &$items[$v['item_id']]; ?>
    <div class="usr-chat-box-item-ad"> 
      <?php if ($item['imgs'] && DEVICE_DESKTOP) { ?>
      <div class="usr-chat-box-item-ad-img hidden-xs">
        <a title="<?= $item['title'] ?>" href="<?= $item['link'] ?>">
          <img alt="<?= $item['title'] ?>" src="<?= $item['img_s'] ?>" />
        </a>
      </div>
      <?php } ?>
      <div class="usr-chat-box-item-ad-content">
        <div class="usr-chat-box-item-ad-title">
          <a href="<?= $item['link'] ?>"><?= $item['title'] ?></a>
        </div>
        <small>
          <?= $lang_number ?>: <?= $item['id'] ?>
        </small>
        <?php if ($item['price_on']) { ?>
        <div class="c-price">
          <?= $item['price'] ?>
          <small><?= $item['price_mod'] ?></small>
        </div>
        <?php } ?>
      </div>
    </div>
    <?php } ?>
  </div>
</div>
<?php } else { ?>
<div class="usr-chat-box-item">
  <div class="usr-chat-box-item-in <?= ($v['my'] ? 'usr-chat-box-item_self' : 'usr-chat-box-item_others') ?>">
    <div class="usr-chat-box-item-content">
      <div class="text-muted"><?= $lang_blocked ?></div>
    </div>
  </div>
</div>
<?php } ?>
<?php } unset($v, $item);

if (empty($messages)) {
  echo $this->showInlineMessage(_t('internalmail', 'Список сообщений пустой'));
}