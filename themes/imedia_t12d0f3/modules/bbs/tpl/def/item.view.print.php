<?php
/**
 * Просмотр объявления для печати
 * @var $this BBS
 * @var $title string заголовок объявления
 * @var $dynprops string дин. свойства (HTML)
 * @var $descr string описание
 */

?>
<div class="l-headerShort">
  <div class="l-headerShort-logo">
    <a href="<?= bff::urlBase() ?>"><img src="<?= Site::logoURL('header.short') ?>" alt=""/></a>
  </div>
</div>
<div class="l-headerShort-heading">
  <h1 class="l-headerShort-heading-title"><?= $title ?></h1>
</div>
<div class="print-content">
  <div class="print-content-main">
    <?php if ($price_on) { ?>
      <div class="print-content-item print-price">
        <b><?= $price ?></b><?php if ($price_mod) { ?>,
          <small><?= $price_mod ?></small><?php } ?>
      </div>
    <?php } ?>
    <div class="print-content-item">
      <?= $city_title ?>
      | <?= _t('view', 'Добавлено: [date], номер: [id]', array('date' => tpl::date_format2($created), 'id' => $id)) ?>
    </div>
    <div class="print-content-item">
      <?php foreach ($images as $v): ?>
        <img src="<?= $v['url_view'] ?>" alt="<?= $v['t'] ?>"/>
        <?php break; endforeach; ?>
    </div>
    <div class="print-content-item">
      <?= _t('view', 'Адрес') ?>:
      <?= $city_title ?>, <?php
      if ($district_id && !empty($district_data['title'])) {
        echo _t('view', 'район [district]', array('district' => $district_data['title'])) . ', ';
      } ?><?php
      if ($metro_id && !empty($metro_data['title'])) {
        echo _t('view', 'метро [station]', array('station' => $metro_data['title'])) . ', ';
      } ?><?= $addr_addr ?>
    </div>
    <div class="ad-dynprops"><?= $dynprops ?></div>
  </div>
  <div class="print-content-sidebar">
    <?php if ($is_shop) { ?>
      <div class="print-content-item">
        <img src="<?= $shop['logo'] ?>" class="img" alt="<?= $shop['title'] ?>"/>
      </div>
    <?php } ?>
    <div class="print-content-item">
      <strong><?= $name ?></strong>
    </div>
    <div class="print-content-item">
      <?php if (!empty($phones)) { ?>
        <div class="print-content-item-i">
          <?= _t('theme.view', 'Тел.: [phones]', array('phones'=>Users::phonesView($phones, false))); ?>
        </div>
      <?php } ?>
      <?php if (!empty($contacts['contacts'])): ?>
        <?php foreach (Users::contactsFields($contacts['contacts']) as $contact): ?>
          <div class="print-content-item-i">
            <?= $contact['title'] ?>:
            <span><?= HTML::obfuscate($contact['value']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="print-content-item">
  <?= nl2br($descr) ?>
</div>

<div id="printBtn" class="print-func print-hide">
  <div class="print-func-in">
    <button class="btn btn-primary" onclick="window.print();"><i
        class="fa fa-print"></i> <?= _t('view', 'Распечатать') ?></button>
    <a class="btn btn-default" href="javascript:void(0);" onclick="history.back();"><?= _t('', 'Отмена') ?></a>
  </div>
</div>