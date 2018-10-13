<?php
  /**
   * Кабинет пользователя: Сообщения - переписка
   * @var $this InternalMail
   * @var $i array данные о собеседнике
   * @var $is_shop boolean представляет ли собеседник магазин
   * @var $list string список сообщений (HTML)
   * @var $pgn string постранична навигация (HTML)
   * @var $attach InternalMailAttachment
   * @var $url_back string URL возврата к списку всех переписок
   */
  tpl::includeJS(array('history'), true);
  tpl::includeJS(array('internalmail.my'), false);
?>
<div class="usr-chat-nav">
  <div class="usr-chat-nav-person">
    <a href="<?= $i['url_profile'] ?>" class="link-ico">
      <img src="<?= $i['avatar'] ?>" alt="" />
      <span><?= $i['url_title'] ?></span>
    </a>
    <!-- plugin_user_online_do_block -->
  </div>
  <div class="usr-chat-nav-r">
    <a href="<?= $url_back ?>" class="btn btn-default btn-sm"><i class="fa fa-chevron-left"></i> <?= _t('internalmail', 'Все сообщения') ?></a>
    <span class="hidden-xs">
      <?php if ($is_shop) { ?>
      <a href="<?= $i['url_profile'] ?>" class="btn btn-default btn-sm"><?= _t('internalmail', 'Объявления магазина') ?> <i class="fa fa-chevron-right"></i></a>
      <?php } else { ?>
      <a href="<?= $i['url_profile'] ?>" class="btn btn-default btn-sm"><?= _t('internalmail', 'Объявления этого пользователя') ?> <i class="fa fa-chevron-right"></i></a>
      <?php } ?>
    </span>
  </div>
</div>

<div class="usr-chat-box">

  <form action="" id="j-my-chat-list-form">
    <input type="hidden" name="page" value="<?= $page ?>" />
    <?php if ($is_shop) { ?>
    <input type="hidden" name="shop" value="<?= $i['shop_key'] ?>" />
    <?php } else { ?>
    <input type="hidden" name="user" value="<?= $i['login'] ?>" />
    <input type="hidden" name="shop" value="<?= ($shop_id ? 1 : 0) ?>" />
    <?php } ?>

    <!-- Messages -->
    <div class="usr-chat-box-content" style="max-height: 350px; min-height: 60px;" id="j-my-chat-list">
      <?= $list ?>
    </div>

    <!-- Pagination -->
    <div class="usr-pagination text-center" id="j-my-chat-list-pgn">
      <?= $pgn ?>
    </div>
  </form>

  <!-- Message Form -->
  <?php if ($i['ignoring']) { ?>
  <div class="alert alert-danger text-center">
    <?php if ($is_shop) { ?>
    <?= _t('internalmail', 'Магазин запретил отправлять ему сообщения') ?>
    <?php } else { ?>
    <?= _t('internalmail', 'Пользователь запретил отправлять ему сообщения') ?>
    <?php } ?>
  </div>
  <?php } else if ( $i['blocked'] ) { ?>
  <div class="alert alert-danger text-center">
    <?= $i['blocked_message'] ?>
  </div>
  <?php } else { ?>
  <div class="usr-chat-box-form">
    <form method="POST" action="<?= InternalMail::url('my.chat') ?>" id="j-my-chat-form" enctype="multipart/form-data">
      <input type="hidden" name="act" value="send" />
      <?php if ($is_shop) { ?>
      <input type="hidden" name="shop" value="<?= $i['shop_key'] ?>" />
      <?php } else { ?>
      <input type="hidden" name="user" value="<?= $i['login'] ?>" />
      <input type="hidden" name="shop" value="<?= ($shop_id ? 1 : 0) ?>" />
      <?php } ?>
      <div class="form-group">
        <textarea name="message" class="form-control" rows="3" placeholder="<?= _te('internalmail', 'Текст сообщения...') ?>" autocapitalize="off"></textarea>
      </div>
      <?php if (InternalMail::attachmentsEnabled()) { ?>
      <div class="usr-chat-box-form-file j-attach-block">
        <div class="upload-btn j-upload">
          <span class="upload-mask">
            <input type="file" name="attach" class="j-upload-file" />
          </span>
          <a href="#" onclick="return false;" class="link-ajax"><span><?= _t('internalmail', 'Прикрепить файл (до [maxSize])', array('maxSize'=>tpl::filesize($attach->getMaxSize()) )) ?></span></a>
        </div>
        <div class="j-cancel hide">
          <span class="j-cancel-filename"></span>
          <a href="#" class="link-ajax link-red j-cancel-link"><i class="fa fa-times"></i> <span><?= _t('internalmail', 'Удалить') ?></span></a>
        </div>
        <input type="hidden" name="MAX_FILE_SIZE" value="<?= $attach->getMaxSize() ?>" />
      </div>
      <?php } ?>
      <button type="submit" class="btn btn-default"><i class="fa fa-envelope"></i> <?= _t('internalmail', 'Отправить') ?></button>
      
      <div class="v-descr_contact__form_submit pull-right"></div>
      <div class="clearfix"></div>
    </form>
  </div>
  <?php } ?>

</div>

<script type="text/javascript">
  <?php js::start() ?>
  $(function(){
    jMyChat.init(<?= func::php2js(array(
      'lang' => array(
        'message' => _t('internalmail','Сообщение слишком короткое'),
        'success' => _t('internalmail','Сообщение было успешно отправлено'),
        ),
      'ajax' => true,
      )) ?>);
  });
  <?php js::stop() ?>
</script>