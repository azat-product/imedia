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

foreach($messages as &$v)
{ ?>
    <? if( $date_last !== $v['created_date']) { ?><div class="u-mail__chat__date"><span><?= tpl::datePublicated($v['created_date'], 'Y-m-d', false, ' ') ?></span></div><? } ?>
    <? $date_last = $v['created_date']; ?>
    <? if (!$v['blocked']) { ?>

    <div class="u-mail__chat__item u-mail__conversation__item_ad">
        <div class="u-mail__chat__item__speek <?= ($v['my'] ? 'right' : 'left') ?>">
            <div class="arrow"></div>
            <div class="u-mail__chat__item__speek__content">
                <p><?= $v['message'] ?></p>
                <? if( InternalMail::attachmentsEnabled() && ! empty($v['attach']) ) {
                    ?><div class="u-mail__chat__item__speek__file"><?= $attach->getAttachLink($v['attach']); ?></div><?
                } ?>
            </div>
            <? if($v['item_id'] > 0 && ! empty($items[$v['item_id']]))
            {
                $item = &$items[$v['item_id']];
            ?>
            <div class="u-mail__chat__ad">
                <table>
                    <tr>
                        <td class="u-mail__chat__ad__img hidden-phone">
                            <? if($item['imgs']) { ?>
                            <a title="<?= $item['title'] ?>" href="<?= $item['link'] ?>" class="thumb stack rel inlblk">
                                <img alt="<?= $item['title'] ?>" src="<?= $item['img_s'] ?>" class="rel br2 zi3 shadow" />
                            </a>
                            <? } ?>
                        </td>
                        <td class="u-mail__chat__ad__content">
                            <a href="<?= $item['link'] ?>"><?= $item['title'] ?></a><? if(DEVICE_PHONE) { ?><? if($item['price_on']) { ?><? if($item['price']) { ?>, <span><?= $item['price'] ?></span><? } ?><? if($item['price_mod']) { ?>, <small><?= $item['price_mod'] ?></small><? } ?><? } ?><? } ?>
                            <br />
                            <small>
                                <?= $lang_number ?>: <?= $item['id'] ?>
                            </small>
                            <? if($item['price_on']) { ?>
                            <span class="hidden-phone">
                                <br />
                                <strong><?= $item['price'] ?></strong>
                                <small><?= $item['price_mod'] ?></small>
                            </span>
                            <? } ?>
                        </td>
                    </tr>
                </table>
            </div>
            <? } ?>
        </div>
    </div>
    <? } else { ?>
    <div class="u-mail__chat__item u-mail__conversation__item_ad">
        <div class="u-mail__chat__item__speek <?= ($v['my'] ? 'right' : 'left') ?>">
            <div class="arrow"></div>
            <div class="u-mail__chat__item__speek__content">
                <p class="grey"><?= $lang_blocked ?></p>
             </div>
        </div>
    </div>
    <? } ?>
<? } unset($v, $item);

?><div class="clearfix"></div><?

if( empty($messages) ) {
    echo $this->showInlineMessage(_t('internalmail', 'Список сообщений пустой'));
}