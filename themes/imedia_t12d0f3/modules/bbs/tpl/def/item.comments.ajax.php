<?php
/**
 * Комментарии объявления: список
 * @var $this BBS
 * @var $level integer уровень комментариев
 * @var $comments array список комментариев (данные)
 * @var $itemID integer ID объявления
 * @var $itemUserID integer ID автора объявления
 * @var $userID integer ID текущего пользователя
 * @var $perPage integer кол-во видимых комментариев
 * @var $allowAdd boolean доступно ли добавление новых комментариев
 * @var $hideReasons array причины сокрытия комментария
 * @var $lang array текстовки
 */

$i = 0;
foreach ($comments as $v):
  $i++;
$levelOne = ($v['numlevel'] <= 1);
$isItemAuthor = ($itemUserID == $v['user_id']);
$isCommentOwner = ($userID == $v['user_id']);
$allowDelete = ($allowAdd && $isCommentOwner);
$allowAnswer = ($allowAdd && $levelOne);
?>

<?php if ($levelOne): ?><div class="l-comments-list-item j-comment-block<?= ($i > $perPage ? ' hide' : '') ?>">
<?php else: ?><div class="l-comments-list-item-answer j-comment-block j-comment-block-answer<?= ($i > $perPage ? ' hide' : '') ?>">
<?php endif; ?>

<a href="<?= $v['user_url_profile'] ?>" class="l-comments-list-item-avatar">
  <img src="<?= $v['user_url_avatar'] ?>" alt="" />
</a>
<div class="l-comments-list-item-content">
  <div class="l-comments-list-item-content-top">
    <strong><a href="<?= $v['user_url_profile'] ?>"><?= $v['name'] ?></a>
      <?php if ($isCommentOwner) { ?>
      <span class="label label-default l-comments-list-item-label"><?= $lang['you'] ?></span>
      <?php } elseif ($isItemAuthor) { ?>
      <span class="label label-success l-comments-list-item-label"><?= $lang['author'] ?></span>
      <?php } ?>
    </strong>
    <span class="l-comments-list-item-date"><?= tpl::date_format_pub($v['created'], $lang['date']) ?></span>
  </div>

  <?php if ($v['deleted']): ?>
  <div class="alert alert-default mrgb0">
    <?php switch ($v['deleted']):
    case BBSItemComments::commentDeletedByItemOwner:
    echo ( $itemUserID == $userID ? $lang['you_delete'] : $hideReasons[$v['deleted']] );
    break;
    case BBSItemComments::commentDeletedByCommentOwner:
    echo ( $isCommentOwner ? $lang['you_delete'] : $hideReasons[$v['deleted']] );
    break;
    default:
    echo $hideReasons[$v['deleted']];
    endswitch; ?>
  </div>
<?php else: ?>
<div class="j-comment">
  <div class="l-comments-list-item-text">
    <?= $v['message'] ?>
  </div>

  <div class="l-comments-list-item-controls j-comment-actions">
    <?php if($allowAnswer){ ?><a href="#" class="link-ajax j-comment-add"><span><?= $lang['answer'] ?></span></a><?php } ?>
    <?php if($allowDelete){ ?><a href="#" class="link-ajax link-red j-comment-delete" data-id="<?= $v['id'] ?>"><i class="fa fa-times"></i> <span><?= $lang['delete'] ?></span></a><?php } ?>
  </div>

  <?php if ($allowAnswer): ?>
  <div class="l-comments-list-item-answerForm hide">
    <form role="form" class="form j-comment-add-form" method="post" action="">
      <input type="hidden" name="item_id" value="<?= $itemID ?>" />
      <input type="hidden" name="parent" value="<?= $v['id'] ?>" />
      <div class="form-group-sm j-required">
        <textarea rows="3" name="message" class="form-control j-message"></textarea>
      </div>
      <button type="submit" class="btn btn-success btn-sm j-submit"><?= $lang['answer'] ?></button>
      <a href="#" class="btn btn-default btn-sm j-comment-cancel"><?= $lang['cancel'] ?></a>
    </form>
  </div>
<?php endif; ?>
</div>
<?php endif; ?>
</div>

<?php if (!empty($v['sub'])):
            # Ответы на комментарий:
echo $this->commentsList($v['sub'], array(
  'itemID'     => $itemID,
  'itemUserID' => $itemUserID,
  'itemStatus' => $itemStatus,
  ), 2);
  endif; ?>

    
  </div>

<?php endforeach;

if ($i > $perPage): ?>
<?php if ($level > 1): ?>
<div class="l-comments-list-allAnswers j-comments-more-block">
  <a href="#" class="link-ajax j-comments-more" data-answers="1"><?= $lang['show_answers'] ?></a>
</div>
<?php else: ?>
<div class="l-commentsList-item-more j-comments-more-block">
  <a href="#" class="ajax j-comments-more" data-answers="0"><?= $lang['show_more'] ?></a>
</div>
<?php endif; ?>
<?php endif; ?>