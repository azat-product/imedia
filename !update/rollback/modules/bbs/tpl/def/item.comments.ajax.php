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

    <? if ($levelOne): ?><li class="l-commentsList-item media j-comment-block<?= ($i > $perPage ? ' hide' : '') ?>">
    <? else: ?><div class="l-commentsList-item-answer j-comment-block j-comment-block-answer<?= ($i > $perPage ? ' hide' : '') ?>">
    <? endif; ?>

        <a href="<?= $v['user_url_profile'] ?>" class="l-commentsList-item-avatar">
            <img src="<?= $v['user_url_avatar'] ?>" alt="" />
        </a>
        <div class="media-body">
            <div class="l-commentsList-l-author">
                <strong><a href="<?= $v['user_url_profile'] ?>"><?= $v['name'] ?></a>
                    <? if ($isCommentOwner) { ?>
                        <span class="label label-default l-commentsList-item-label"><?= $lang['you'] ?></span>
                    <? } elseif ($isItemAuthor) { ?>
                        <span class="label label-success l-commentsList-item-label"><?= $lang['author'] ?></span>
                    <? } ?>
                </strong>
                <span class="l-commentsList-item-date"><?= tpl::date_format_pub($v['created'], $lang['date']) ?></span>
            </div>

            <? if ($v['deleted']): ?>
            <div class="alert alert-default mrgb0">
                <? switch ($v['deleted']):
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
            <? else: ?>
            <div class="j-comment">
                <div class="l-commentsList-item-text">
                    <?= $v['message'] ?>
                </div>

                <div class="l-commentsList-item-controls j-comment-actions">
                    <? if($allowAnswer){ ?><a href="#" class="ajax j-comment-add"><?= $lang['answer'] ?></a><? } ?>
                    <? if($allowDelete){ ?><a href="#" class="ajax ico red j-comment-delete" data-id="<?= $v['id'] ?>"><i class="fa fa-times"></i> <span><?= $lang['delete'] ?></span></a><? } ?>
                </div>

                <? if ($allowAnswer): ?>
                <div class="l-commentsList-item-answerForm hide">
                    <form role="form" class="form j-comment-add-form" method="post" action="">
                        <input type="hidden" name="item_id" value="<?= $itemID ?>" />
                        <input type="hidden" name="parent" value="<?= $v['id'] ?>" />
                        <div class="controls j-required">
                            <textarea rows="3" name="message" class="span12 j-message"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm j-submit"><?= $lang['answer'] ?></button>
                        <a href="#" class="btn btn-default btn-sm j-comment-cancel"><?= $lang['cancel'] ?></a>
                    </form>
                </div>
                <? endif; ?>
            </div>
            <? endif; ?>
        </div>

        <? if (!empty($v['sub'])):
            # Ответы на комментарий:
            echo $this->commentsList($v['sub'], array(
                'itemID'     => $itemID,
                'itemUserID' => $itemUserID,
                'itemStatus' => $itemStatus,
            ), 2);
        endif; ?>

    <? if($levelOne): ?></li><? else: ?></div><? endif; ?>

<? endforeach;

if ($i > $perPage): ?>
    <? if ($level > 1): ?>
    <div class="l-commentsList-item-showall j-comments-more-block">
        <a href="#" class="ajax j-comments-more" data-answers="1"><?= $lang['show_answers'] ?></a>
    </div>
    <? else: ?>
    <li class="l-commentsList-item-more j-comments-more-block">
        <a href="#" class="ajax j-comments-more" data-answers="0"><?= $lang['show_more'] ?></a>
        <div class="c-spacer10 visible-xs"></div>
    </li>
    <? endif; ?>
<? endif;