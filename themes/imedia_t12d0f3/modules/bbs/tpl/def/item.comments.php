<?php
/**
 * Комментарии объявления: layout
 * @var $this BBS
 * @var $comments string список комментариев (HTML блок)
 * @var $commentsTotal integer кол-во комментариев
 * @var $itemID integer ID объявления
 * @var $itemUserID integer ID автора объявления
 * @var $itemStatus integer статус объявления
 * @var $userID integer ID текущего пользователя
 */
if (!DEVICE_DESKTOP_OR_TABLET) return;
tpl::includeJS('bbs.comments', false, 1);
?>

<div class="l-blockHeading">
  <h2 class="l-blockHeading-title"><?= _t('comments', 'Комментарии'); ?></h2>
  <span class="label label-default"><?= $commentsTotal ?></span>
</div>

<?php if ($itemStatus == BBS::STATUS_PUBLICATED): ?>
  <?php if($userID): ?>
  <div class="l-comments-leave" id="commentsLeave">
    <div class="j-comment">
      <a href="#commentsLeave-form" class="l-comments-leave-toggle j-comment-add" data-toggle="collapse"><i class="panel-title-icon fa fa-comment-o"></i> <?= _t('comments', 'Опубликовать комментарий'); ?></a>
      <div class="l-comments-leave-form collapse" id="commentsLeave-form">
        <div class="l-comments-leave-form-in">
          <form class="j-comment-add-form" role="form" method="post" action="">
            <input type="hidden" name="item_id" value="<?= $itemID ?>" />
            <div class="form-group j-required">
              <textarea rows="4" class="form-control j-message" name="message" placeholder="<?= _te('comments', 'Ваш комментарий'); ?>"></textarea>
            </div>
            <button type="submit" class="btn btn-success j-submit"><?= _t('comments', 'Опубликовать'); ?></button>
            <button type="button" class="btn btn-default" data-parent="#commentsLeave" data-target="#commentsLeave-form" data-toggle="collapse"><?= _t('', 'Отмена'); ?></button>
          </form>
        </div>
      </div>
    </div>
  </div>
<?php else: ?>
<div class="alert alert-warning"><?= _t('comments', 'Чтобы опубликовать свой комментарий, Вы должны <a [link_reg]>зарегистрироваться</a> или <a [link_login]>войти</a>.', array(
  'link_reg'   => 'href="'.Users::url('register').'"',
  'link_login' => 'href="'.Users::url('login').'"',
  )); ?></div>
<?php endif; ?>
<?php else: ?>
<div class="alert alert-warning"><?= _t('comments', 'Комментарии к этому объявлению закрыты'); ?></div>
<?php endif; ?>

<div class="l-comments-list j-comment-block">
  <?= $comments ?>
</div>

<script type="text/javascript">
  <?php js::start(); ?>
  $(function(){
    jComments.init(<?= func::php2js(array(
      'lang'=>array(
        'premod_message' => _t('comments', 'После проверки модератором ваш комментарий будет опубликован'),
        ),
      'item_id' => $itemID,
      )) ?>);
  });
  <?php js::stop(); ?>
</script>