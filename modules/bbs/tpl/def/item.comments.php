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
tpl::includeJS('bbs.comments', false, 1);
?>
<div id="j-comments-block">
    <div class="l-comments-heading">
        <?= _t('comments', 'Комментарии'); ?>
        <span class="l-comments-heading-count"><?= $commentsTotal ?></span>
    </div>

    <? if ($itemStatus == BBS::STATUS_PUBLICATED): ?>
        <? if($userID): ?>
        <div class="accordion l-comments-accordion" id="commentsLeave" role="tablist" aria-multiselectable="true">
            <div class="accordion-group j-comment">
                <div class="accordion-heading" role="tab">
                    <strong>
                        <a class="accordion-toggle collapsed j-comment-add" data-toggle="collapse" data-parent="#commentsLeave" href="#commentsLeave-form">
                            <i class="panel-title-icon fa fa-comment-o"></i> <?= _t('comments', 'Опубликовать комментарий'); ?>
                        </a>
                    </strong>
                </div>
                <div id="commentsLeave-form" class="accordion-body collapse">
                    <div class="accordion-inner">
                        <form class="j-comment-add-form" role="form" method="post" action="">
                            <input type="hidden" name="item_id" value="<?= $itemID ?>" />
                            <div class="controls j-required">
                                <textarea rows="4" class="span12 j-message" name="message" placeholder="<?= _te('comments', 'Ваш комментарий'); ?>"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success j-submit"><?= _t('comments', 'Опубликовать'); ?></button>
                            <button type="button" class="btn btn-default" data-parent="#commentsLeave" data-target="#commentsLeave-form" data-toggle="collapse"><?= _t('', 'Отмена'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <? else: ?>
        <div class="alert alert-warning mrgb0"><?= _t('comments', 'Чтобы опубликовать свой комментарий, Вы должны <a [link_reg]>зарегистрироваться</a> или <a [link_login]>войти</a>.', array(
                'link_reg'   => 'href="'.Users::url('register').'"',
                'link_login' => 'href="'.Users::url('login').'"',
            )); ?></div>
        <? endif; ?>
    <? else: ?>
        <div class="alert alert-warning mrgb0 hide"><?= _t('comments', 'Комментарии к этому объявлению закрыты'); ?></div>
    <? endif; ?>

    <ul class="l-commentsList media-list j-comment-block">
        <?= $comments ?>
    </ul>
</div>
<br />
<script type="text/javascript">
<? js::start(); ?>
$(function(){
    jComments.init(<?= func::php2js(array(
        'lang'=>array(
            'premod_message' => _t('comments', 'После проверки модератором ваш комментарий будет опубликован'),
        ),
        'item_id' => $itemID,
    )) ?>);
});
<? js::stop(); ?>
</script>