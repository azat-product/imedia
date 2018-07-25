<?php
/**
 * Поиск магазинов: список - блок просмотра контактов магазина
 * @var $this Shops
 * @var $listType integer тип списка Shops::LIST_TYPE_
 * @var $device string тип устройства bff::DEVICE_
 * @var $has_contacts boolean был ли указан 1 и более контакт (телефоны, Skype, ICQ)
 * @var $phones string номера телефонов
 * @var $contacts array контакты
 * @var $social array ссылки на соц. сети
 * @var $socialTypes array данные о соц. сетях
 * @var $logo string URL логотипа
 * @var $link string URL страницы магазина
 */

$is_phone = ($device == bff::DEVICE_PHONE);

if($listType == Shops::LIST_TYPE_LIST) { ?>
<ul class="sh-page__list__contacts__dropdown__table">
    <? if($phones){ ?>
    <li>
        <div style="vertical-align: top;"><?= _t('shops', 'Телефон') ?>:&nbsp;</div>
        <div><?= $phones ?></div>
    </li>
    <? } ?>
    <? if (!empty($contacts)) { ?>
        <?php foreach (Users::contactsFields($contacts) as $contact): ?>
            <li><div><?= $contact['title'] ?></div><div><?= HTML::escape($contact['value']) ?></div></li>
        <?php endforeach; ?>
    <? } ?>
    <? if($social && $socialTypes) { ?>
    <li class="sh-page__list__item_social">
        <? foreach($social as $v) {
            if ($v && isset($socialTypes[$v['t']])) {
                ?><a href="<?= bff::urlAway($v['v']) ?>" rel="nofollow noreferrer noopener" target="_blank" class="sh-social sh-social_<?= $socialTypes[$v['t']]['icon'] ?>"></a><?
            }
           } ?>
    </li>
    <? } ?>
</ul>
<? }
else if($listType = Shops::LIST_TYPE_MAP) { ?>
    <div class="sr-page__map__balloon<? if($is_phone){ ?> sr-page__map__balloon_mobile<? } ?>">
        <? if($logo && ! $is_phone){ ?><div class="sh-page__map__balloon_img"><a href="<?= $link ?>"><img class="rel br2 zi3 shadow" src="<?= $logo ?>" alt="<?= $title ?>" /></a></div><? } ?>
        <div class="sh-page__map__balloon_descr">
            <h6><a href="<?= $link ?>"><?= $title ?></a></h6>
            <? if($has_contacts) { ?>
                <table>
                    <tbody>
                        <? if($phones){ ?><tr><td width="60"><?= _t('shops', 'Телефон') ?>:</td><td><?= $phones ?></td></tr><? } ?>
                        <? if (!empty($contacts)) { ?>
                            <? foreach (Users::contactsFields($contacts) as $contact): ?>
                                <tr><td width="60"><?= $contact['title'] ?>:</td><td><?= HTML::escape($contact['value']) ?></td></tr>
                            <? endforeach; ?>
                        <? } ?>
                    </tbody>
                </table>
            <? } ?>
            <? if($region_id){ ?><div style="margin:5px 0;"><?= $region_title.', '.$addr_addr ?></div><? } ?>
            <? if($items){ ?><a href="<?= $link ?>"><?= _t('shops', 'Показать').' '.tpl::declension($items, _t('shops','объявление;объявления;объявлений')) ?> &rsaquo;</a><? } ?>
        </div>
    </div>
<? }