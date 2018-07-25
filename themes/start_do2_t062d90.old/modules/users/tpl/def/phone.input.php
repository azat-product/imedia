<?php
/**
 * Поле ввода номера телефона
 * При включенной авторизации через sms
 * @var $this Users
 * @var $options array настройки
 * @var $countryList array список стран
 * @var $countrySelected array данные выбранной страны
 * @var $countrySelectedID integer ID выбранной страны
 * @var $itemForm boolean поле выводится в форме добавления/редактирования объявления
 */
?>
<div class="j-phone-number input-phone dropdown<?php if($itemForm){ ?> i-control-phone<?php } ?>">
  <a href="#" class="country-icon country-icon-<?= $countrySelected['country_code'] ?>" data-type="country-icon" data-default="<?= $countrySelected['country_code'] ?>"></a>
  <input type="text" <?= HTML::attributes($attr) ?> pattern="[0-9+]*" maxlength="30" class="form-control j-required j-phone-number-input" data-default="<?= '+'.intval($countrySelected['phone_code']) ?>" />
  <div class="dropdown-menu country-dropdown j-phone-number-country-list">
    <ul class="country-dropdown-list">
      <?php foreach ($countryList as $v) { ?>
      <li<?php if ($v['id'] == $countrySelectedID) { ?> class="active"<?php } ?>>
      <a href="#" data="{id:<?= $v['id'] ?>,cc:'<?= $v['country_code'] ?>',pc:'+<?= intval($v['phone_code']) ?>'}" class="j-country-item"><span class="country-icon country-icon-<?= $v['country_code'] ?>"></span> <?= $v['title'] ?> <i>+<?= $v['phone_code'] ?></i></a>
    </li>
    <?php } ?>
  </ul>
</div>
</div>