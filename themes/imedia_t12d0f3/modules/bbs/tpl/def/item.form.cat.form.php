<?php
/**
 * Форма объявления: добавление / редактирование - параметры формы зависимые от настроек категории
 * @var $this BBS
 * @var $edit boolean редактирование (true), добавление (false)
 * @var $item array данные об объявлении
 * @var $types array типы объявления (продам/куплю)
 * @var $price boolean цена true/false
 * @var $price_label string название поля "цена"
 * @var $price_sett array настройки цены
 * @var $price_curr_selected integer ID текущей выбранной валюты цены
 * @var $dp string HTML блок дин. свойств
 */

# Тип размещения ("Тип объявления") ?>
<div class="form-group" <?php if( empty($types) || sizeof($types) == 1) { ?>style="display: none;"<?php } ?>>
  <label class="col-sm-3 control-label"><?= _t('item-form', 'Тип объявления') ?><span class="required-mark">*</span></label>
  <div class="col-md-6 col-sm-9">
    <?php if( is_array($types) ) {
     foreach($types as $v) { ?>
     <div class="radio-inline">
       <label><input name="cat_type" value="<?= $v['id'] ?>" type="radio" class="j-cat-type j-required" <?php if( $item['cat_type'] == $v['id'] ) { ?> checked="checked"<?php } ?> /> <?= $v['title'] ?></label>
     </div>
     
     <?php }
   } ?>
 </div>
</div>
<?php

# Цена
if( ! empty($price) ) { ?>
<div class="form-group j-price-block">
  <label class="col-sm-3 control-label"><?= $price_label ?><span class="required-mark">*</span></label>
  <div class="col-md-6 col-sm-9">
    <?php if( $price_sett['ex'] > 0 ) { ?>
    <?php if($price_sett['ex'] & BBS::PRICE_EX_AGREED) { ?>
      <div class="radio">
        <label>
          <input class="j-price-var" type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_AGREED ?>" <?php if($item['price_ex'] & BBS::PRICE_EX_AGREED) { ?> checked="checked" <?php } ?> /> <?= _t('item-form', 'Договорная') ?>
        </label>
      </div>
    <?php } ?>
    <?php if($price_sett['ex'] & BBS::PRICE_EX_FREE) { ?>
      <div class="radio">
        <label>
          <input class="j-price-var" type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_FREE ?>" <?php if($item['price_ex'] & BBS::PRICE_EX_FREE) { ?> checked="checked" <?php } ?> /> <?= _t('item-form', 'Бесплатно') ?>
        </label>
      </div>
    <?php } ?>
    <?php if($price_sett['ex'] & BBS::PRICE_EX_EXCHANGE) { ?>
      <div class="radio">
        <label>
          <input class="j-price-var" type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_EXCHANGE ?>" <?php if($item['price_ex'] & BBS::PRICE_EX_EXCHANGE) { ?> checked="checked" <?php } ?> /> <?= _t('item-form', 'Обмен') ?>
        </label>
      </div>
    <?php } ?>
    <div class="radio"<?php if($price_sett['ex'] == BBS::PRICE_EX_MOD) { ?> style="display: none;" <?php } ?>>
      <label>
      <input type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_PRICE ?>" <?php if($item['price_ex'] <= BBS::PRICE_EX_MOD) { ?> checked="checked" <?php } ?> /> <?= _t('item-form', 'Цена') ?>&nbsp;
      </label>
    </div>
    <?php } ?>
    
    <div class="mrgt5">
      <input type="text" name="price" class="form-control input-inline j-price<?php if( ! $price_sett['ex'] ){ ?> j-required<?php } ?>" value="<?= $item['price'] ?>" pattern="[0-9\.,]*" />
      <select name="price_curr" class="form-control input-inline"><?= Site::currencyOptions($price_curr_selected) ?></select>
      <?php if($price_sett['ex'] & BBS::PRICE_EX_MOD) { ?>
      <div class="checkbox">
        <label>
          <input type="checkbox" name="price_ex[2]" class="j-price-mod" value="<?= BBS::PRICE_EX_MOD ?>" <?php if($item['price_ex'] & BBS::PRICE_EX_MOD) { ?> checked="checked" <?php } ?> />
          <?= ( ! empty($price_sett['mod_title'][LNG]) ? $price_sett['mod_title'][LNG] : _t('item-form', 'Торг возможен') ) ?>
        </label>
      </div>
    <?php } ?>
    </div>
  </div>
</div>
<?php }

# Дин.свойства
if( ! empty($dp) ) {
  echo $dp;
}