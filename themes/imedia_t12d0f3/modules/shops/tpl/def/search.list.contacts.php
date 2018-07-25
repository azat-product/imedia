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

// List
if ($listType == Shops::LIST_TYPE_LIST) { ?>

  <?php if ($phones) { ?>
  <div class="sh-list-item-contact-dropdown-item">
    <div class="sh-list-item-contact-dropdown-item-l"><?= _t('shops', 'Телефон') ?>:</div>
    <div class="sh-list-item-contact-dropdown-item-r"><?= $phones ?></div>
  </div>
  <?php } ?>
  <?php if (!empty($contacts)) { ?>
    <?php foreach (Users::contactsFields($contacts) as $contact): ?>
      <div class="sh-list-item-contact-dropdown-item">
        <div class="sh-list-item-contact-dropdown-item-l"><?= $contact['title'] ?>:</div>
        <div class="sh-list-item-contact-dropdown-item-r"><?= HTML::escape($contact['value']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php } ?>
  <?php if ($social && $socialTypes) { ?>
  <div class="sh-list-item-contact-dropdown-social">
    <?php foreach ($social as $v) {
      if ($v && isset($socialTypes[$v['t']])) {
        ?><a href="<?= bff::urlAway($v['v']) ?>" rel="nofollow noreferrer noopener" target="_blank"><?= $socialTypes[$v['t']]['icon'] ?></a><?php
      }
    } ?>
  </div>
  <?php } ?>

<?php }

// Map
else if ($listType = Shops::LIST_TYPE_MAP) { ?>
<div class="sh-map-baloon">
  <?php if ($logo && ! $is_phone){ ?><div class="sh-map-baloon-logo"><a href="<?= $link ?>"><img class="rel br2 zi3 shadow" src="<?= $logo ?>" alt="<?= $title ?>" /></a></div><?php } ?>
  <div class="sh-map-baloon-descr">
    <div class="sh-map-baloon-title"><a href="<?= $link ?>"><?= $title ?></a></div>
    <?php if ($has_contacts) { ?>
    <table class="sh-map-baloon-contacts">
      <tbody>
        <?php if ($phones) { ?><tr><td width="60"><?= _t('shops', 'Телефон') ?>:</td><td><?= $phones ?></td></tr><?php } ?>
        <?php if (!empty($contacts)) { ?>
          <?php foreach (Users::contactsFields($contacts) as $contact): ?>
            <tr><td width="60"><?= $contact['title'] ?>:</td><td><?= HTML::escape($contact['value']) ?></td></tr>
          <?php endforeach; ?>
        <?php } ?>
      </tbody>
    </table>
    <?php } ?>
    <?php if ($region_id) { ?><div style="margin:5px 0;"><?= $region_title.', '.$addr_addr ?></div><?php } ?>
    <?php if ($items) { ?><a href="<?= $link ?>"><?= _t('shops', 'Показать').' '.tpl::declension($items, _t('shops','объявление;объявления;объявлений')) ?> &rsaquo;</a><?php } ?>
  </div>
</div>
<?php }