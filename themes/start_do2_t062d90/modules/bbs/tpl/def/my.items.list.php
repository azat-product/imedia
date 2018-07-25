<?php

/**
 * Кабинет пользователя: Мои объявления - список
 * @var $this BBS
 * @var $items array объявления
 * @var $device string текущее устройство bff::DEVICE_
 */

$lng_from = _t('bbs.my', 'С');
$lng_to = _t('bbs.my', 'По');
$lng_a_view = _t('bbs.my', 'Посмотреть');
$lng_a_edit = _t('bbs.my', 'Редактировать');
$lng_a_edit_phone = _t('bbs.my', 'Изменить');
$lng_a_unpublicate = _t('bbs.my', 'Деактивировать');
$lng_a_publicate = _t('bbs.my', 'Активировать');
$lng_a_promote = _t('bbs.my', 'Рекламировать');
$lng_a_up_free = _t('bbs.my','Поднять бесплатно');
$lng_a_delete = _t('bbs.my', 'Удалить');
$lng_st = _t('bbs.my', 'Статистика');
$lng_st_views = _t('bbs.my', 'просмотры');
$lng_st_contacts = _t('bbs.my', 'контакты');
$lng_st_messages = _t('bbs.my', 'сообщения');
$lng_blocked = _t('bbs.my', 'ЗАБЛОКИРОВАНО');
$lng_up_auto_edit = _t('bbs.my', 'Настроить автоподнятие');
$lng_up_auto_on = _t('bbs.my', 'Включить автоподнятие');

if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET || $device == bff::DEVICE_PHONE )
{
  foreach($items as $v):
    $ID = $v['id'];
  $messages_url = InternalMail::url('item.messages', array('item'=>$ID));
  $moderated = ($v['moderated'] > 0 || ! BBS::premoderation());
  ?>
  <div class="sr-list-item">
    <div class="sr-list-item-left sr-list-item-left-md">
      <div class="sr-list-item-date c-date">
        <span class="sr-list-item-date-i"><?= $lng_from ?>: <?= tpl::dateFormat($v['publicated']) ?></span>
        <span class="sr-list-item-date-i"><?= $lng_to ?>: <?= tpl::dateFormat($v['publicated_to']) ?></span>
      </div>
      <label><input type="checkbox" name="i[]" class="j-check-desktop" value="<?= $ID ?>" /></label>
    </div><!-- /.sr-list-item-left -->
    <div class="sr-list-item-img">
      <a href="<?= $v['link'].'?from=my' ?>" class="sr-glItem-img<?php if($v['imgs'] > 1) { ?> sr-glItem-img_multiple<?php } ?>">
        <img src="<?= $v['img_s'] ?>" alt="<?= $v['title'] ?>" />
      </a>
    </div><!-- /.sr-list-item-img -->
    <div class="sr-list-item-body">
      <div class="sr-list-item-body-in">
        <div class="sr-list-item-content">
          <div class="sr-list-item-heading">
            <h3 class="sr-list-item-heading-title">
              <a href="<?= $v['link'].'?from=my' ?>"><?= $v['title'] ?></a>
              <?php if($v['status'] == BBS::STATUS_BLOCKED) { ?><span class="text-danger">(<?= $lng_blocked ?>)</span><?php } ?>
            </h3>
          </div>
          <div class="sr-glItem-subtext">
            <span class="sr-glItem-subtext-i"><?= $v['cat_title'] ?></span>
          </div>
        </div>
        <div class="list-item-right">
          <div class="c-price sr-list-item-price">
            <?php if($v['price_on']) { ?>
            <?= $v['price'] ?>
            <div class="c-price-sub"><?= $v['price_mod'] ?></div>
            <?php } ?>
          </div>
        </div>
      </div><!-- /.sr-list-item-body-in -->
      <div class="sr-list-item-controls">
        <div class="sr-list-item-controls-buttons">
          <?php if( ! $v['messages_total']) { ?>
            <a href="#" onclick="return false;" class="btn btn-sm btn-default disabled"><i class="fa fa-envelope"></i> 0&nbsp;</a>
          <?php } else { if($v['messages_new']) { ?>
            <a href="<?= $messages_url ?>" class="btn btn-sm btn-success"><i class="fa fa-envelope"></i> +<?= $v['messages_new'] ?></a>
          <?php } else { ?>
            <a href="<?= $messages_url ?>" class="btn btn-sm btn-default"><i class="fa fa-envelope"></i> <?= $v['messages_total'] ?></a>
          <?php } } ?>
          <?php if($v['status'] == BBS::STATUS_PUBLICATED && $moderated && bff::servicesEnabled()) { ?>
           <a href="<?= BBS::url('item.promote', array('id'=>$ID,'from'=>'my')) ?>" class="btn btn-sm btn-success"><?= $lng_a_promote ?></a>
          <?php if ($upfree_days) { ?>
           <a href="#" data-id="<?= $ID ?>" class="btn btn-sm btn-info <?= strtotime($v['svc_up_free']) >= $upfree_to ? 'disabled' : '' ?> j-i-up-free" ><i class="fa fa-arrow-up"></i> <?= $lng_a_up_free ?></a>
          <?php } ?>
          <?php } else if($v['status'] == BBS::STATUS_PUBLICATED_OUT && $moderated) { ?>
          <a href="#" data-id="<?= $ID ?>" data-act="publicate" class="btn btn-sm btn-info j-i-status"><i class="fa fa-arrow-up white"></i> <?= $lng_a_publicate ?></a>
          <?php } ?>
        </div>
        <div class="sr-list-item-controls-links">
          <?php if($v['status'] != BBS::STATUS_BLOCKED && $moderated) { ?>
          <a href="<?= $v['link'].'?from=my' ?>" class="link-ico"><i class="fa fa-check"></i> <span><?= $lng_a_view ?></span></a>
          <?php } ?>
          <a href="<?= BBS::url('item.edit', array('id'=>$ID,'from'=>'my')) ?>" class="link-ico"><i class="fa fa-edit"></i> <span><?= $lng_a_edit ?></span></a>
          <?php if($v['status'] == BBS::STATUS_PUBLICATED && $moderated) { ?>
          <a href="#" data-id="<?= $ID ?>" data-act="unpublicate" class="link-ico link-red j-i-status"><i class="fa fa-times"></i> <span><?= $lng_a_unpublicate ?></span></a>
          <?php } else if($v['status'] == BBS::STATUS_PUBLICATED_OUT) { ?>
          <a href="#" data-id="<?= $ID ?>" data-act="delete" class="link-ico link-red j-i-status"><i class="fa fa-times"></i> <span><?= $lng_a_delete ?></span></a>
          <?php } ?>
        </div>
      </div>
      <?php if (BBS::svcUpAutoEnabled()) { ?>
      <div class="sr-list-item-autoup">
        <a href="#" class="link-ajax j-i-up-auto" data-id="<?= $ID ?>">
          <i class="fa fa-refresh <?= $v['svc_upauto_on'] ? 'text-blue' : '' ?>"></i> <span><?= $v['svc_upauto_on'] ? $lng_up_auto_edit : $lng_up_auto_on ?></span>
        </a>
      </div>
      <?php } ?>
      <ul class="sr-list-item-stats">
        <li><?= $lng_st ?>:</li>
        <li><i class="fa fa-eye"></i> <?= $lng_st_views ?>: <b><?= $v['views_item_total'] ?></b></li>
        <li> <?= $lng_st_contacts ?>: <b><?= $v['views_contacts_total'] ?></b></li>
        <li><i class="fa fa-comment"></i> <?= $lng_st_messages ?>: <b><?= $v['messages_total'] ?></b></li>
      </ul>
    </div><!-- /.sr-list-item-body -->
    
    
  </div><!-- /.sr-list-item -->

<?php
endforeach;
if( empty($items) ) {
  echo $this->showInlineMessage(_t('bbs.my', 'Список объявлений пустой'));
}
}

if( false && $device == bff::DEVICE_PHONE )
{
  foreach($items as $v):
    $ID = $v['id'];
  $messages_url = InternalMail::url('item.messages', array('item'=>$ID));
  $moderated = ( $v['moderated'] > 0 );
  ?>
  
  <!-- Mobile List Layout (if needed) -->

<?php
endforeach;
if( empty($items) ) {
  echo $this->showInlineMessage(_t('bbs.my', 'Список объявлений пустой'));
}
}