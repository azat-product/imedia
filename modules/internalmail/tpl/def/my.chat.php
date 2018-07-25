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

<div class="u-cabinet__sub-navigation">
    <div class="u-cabinet__sub-navigation_desktop u-cabinet__sub-navigation_mailchat hidden-phone">
        <table>
            <tr>
                <td><a href="<?= $url_back ?>" class="ico"><i class="fa fa-chevron-left"></i> <span><?= _t('internalmail', 'Все сообщения') ?></span></a></td>
                <td class="align-center"><a href="<?= $i['url_profile'] ?>" class="v-author__avatar u-cabinet__sub-navigation__back"><img src="<?= $i['avatar'] ?>" class="img-circle" alt="" /></a> <?= $i['url_title'] ?></td>
                <td class="align-right">
                    <? if($is_shop) { ?>
                        <a href="<?= $i['url_profile'] ?>" class="ico"><span><?= _t('internalmail', 'Объявления магазина') ?></span> <i class="fa fa-chevron-right"></i></a>
                    <? } else { ?>
                        <a href="<?= $i['url_profile'] ?>" class="ico"><span><?= _t('internalmail', 'Объявления этого пользователя') ?></span> <i class="fa fa-chevron-right"></i></a>
                    <? } ?>
                </td>
            </tr>
        </table>
    </div>
    <div class="u-cabinet__sub-navigation_mobile u-cabinet__sub-navigation_mailchat visible-phone">
        <div class="u-cabinet__sub-navigation__type">
            <table>
                <tr>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_left"><a href="<?= $url_back ?>"><i class="fa fa-chevron-left"></i></a></div></td>
                    <td class="u-cabinet__sub-navigation__type__title"><?= $i['url_title'] ?></td>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_right"><a href="<?= $i['url_profile'] ?>"><i class="fa fa-chevron-right"></i></a></div></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<div class="u-mail__chat">

    <form action="" id="j-my-chat-list-form">
        <input type="hidden" name="page" value="<?= $page ?>" />
        <? if($is_shop) { ?>
        <input type="hidden" name="shop" value="<?= $i['shop_key'] ?>" />
        <? } else { ?>
        <input type="hidden" name="user" value="<?= $i['login'] ?>" />
        <input type="hidden" name="shop" value="<?= ($shop_id ? 1 : 0) ?>" />
        <? } ?>

        <? # Список сообщений ?>
        <div class="u-mail__chat__content" style="max-height: 350px; min-height: 60px;" id="j-my-chat-list">
            <?= $list ?>
        </div>

        <? # Постраничная навигация ?>
        <div class="u-cabinet__pagination">
            <div id="j-my-chat-list-pgn" class="text-center">
                <?= $pgn ?>
            </div>
        </div>
    </form>

    <? # Форма отправки сообщения ?>
    <? if ( $i['ignoring'] ) { ?>
        <div class="alert alert-error text-center">
            <? if ($is_shop) { ?>
                <?= _t('internalmail', 'Магазин запретил отправлять ему сообщения') ?>
            <? } else { ?>
                <?= _t('internalmail', 'Пользователь запретил отправлять ему сообщения') ?>
            <? } ?>
        </div>
    <? } else if ( $i['blocked'] ) { ?>
        <div class="alert alert-error text-center">
            <?= $i['blocked_message'] ?>
        </div>
    <? } else { ?>
    <div class="u-mail__chat__form">
        <form method="POST" action="<?= InternalMail::url('my.chat') ?>" id="j-my-chat-form" enctype="multipart/form-data">
            <input type="hidden" name="act" value="send" />
            <? if($is_shop) { ?>
            <input type="hidden" name="shop" value="<?= $i['shop_key'] ?>" />
            <? } else { ?>
            <input type="hidden" name="user" value="<?= $i['login'] ?>" />
            <input type="hidden" name="shop" value="<?= ($shop_id ? 1 : 0) ?>" />
            <? } ?>
            <textarea name="message" placeholder="<?= _te('internalmail', 'Текст сообщения...') ?>" autocapitalize="off"></textarea>
            <? if(InternalMail::attachmentsEnabled()) { ?>
            <div class="v-descr_contact__form_file pull-left attach-file j-attach-block">
                <div class="upload-btn j-upload">
                    <span class="upload-mask">
                        <input type="file" name="attach" class="j-upload-file" />
                    </span>
                    <a href="#" onclick="return false;" class="ajax"><?= _t('internalmail', 'Прикрепить файл (до [maxSize])', array('maxSize'=>tpl::filesize($attach->getMaxSize()) )) ?></a>
                </div>
                <div class="j-cancel hide">
                    <span class="j-cancel-filename"></span>
                    <a href="#" class="ajax pseudo-link-ajax j-cancel-link"><?= _t('internalmail', 'Удалить') ?></a>
                </div>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= $attach->getMaxSize() ?>" />
            </div>
            <? } ?>
            <div class="v-descr_contact__form_submit pull-right"><button type="submit" class="btn"><i class="fa fa-envelope"></i> <?= _t('internalmail', 'Отправить') ?></button></div>
            <div class="clearfix"></div>
        </form>
    </div>
    <? } ?>

</div>

<script type="text/javascript">
<? js::start() ?>
    $(function(){
        jMyChat.init(<?= func::php2js(array(
            'lang' => array(
                'message' => _t('internalmail','Сообщение слишком короткое'),
                'success' => _t('internalmail','Сообщение было успешно отправлено'),
            ),
            'ajax' => true,
        )) ?>);
    });
<? js::stop() ?>
</script>