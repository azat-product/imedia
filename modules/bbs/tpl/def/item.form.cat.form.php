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
<div class="control-group" <? if( empty($types) || sizeof($types) == 1) { ?>style="display: none;"<? } ?>>
    <label class="control-label"><?= _t('item-form', 'Тип объявления') ?><span class="required-mark">*</span></label>
    <div class="controls j-cat-types">
    <?  if( is_array($types) ) {
               foreach($types as $v) { ?>
                <label class="radio inline"><input name="cat_type" value="<?= $v['id'] ?>" type="radio" class="j-cat-type j-required" <? if( $item['cat_type'] == $v['id'] ) { ?> checked="checked"<? } ?> /> <?= $v['title'] ?></label>
            <? }
        } ?>
    </div>
</div>
<?

# Цена
if( ! empty($price) ) { ?>
<div class="control-group j-price-block">
    <label class="control-label"><?= $price_label ?><span class="required-mark">*</span></label>
    <div class="controls">
        <? if( $price_sett['ex'] > 0 ) { ?>
            <? if($price_sett['ex'] & BBS::PRICE_EX_AGREED) { ?><label class="radio"><input class="j-price-var" type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_AGREED ?>" <? if($item['price_ex'] & BBS::PRICE_EX_AGREED) { ?> checked="checked" <? } ?> /> <?= _t('item-form', 'Договорная') ?></label><? } ?>
            <? if($price_sett['ex'] & BBS::PRICE_EX_FREE) { ?><label class="radio"><input class="j-price-var" type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_FREE ?>" <? if($item['price_ex'] & BBS::PRICE_EX_FREE) { ?> checked="checked" <? } ?> /> <?= _t('item-form', 'Бесплатно') ?></label><? } ?>
            <? if($price_sett['ex'] & BBS::PRICE_EX_EXCHANGE) { ?><label class="radio"><input class="j-price-var" type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_EXCHANGE ?>" <? if($item['price_ex'] & BBS::PRICE_EX_EXCHANGE) { ?> checked="checked" <? } ?> /> <?= _t('item-form', 'Обмен') ?></label><? } ?>
            <label class="radio inlblk"<? if($price_sett['ex'] == BBS::PRICE_EX_MOD) { ?> style="display: none;" <? } ?>><input type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_PRICE ?>" <? if($item['price_ex'] <= BBS::PRICE_EX_MOD) { ?> checked="checked" <? } ?> /> <?= _t('item-form', 'Цена') ?>&nbsp;</label>
        <? } ?>

        <input type="text" name="price" class="input-small j-price<? if( ! $price_sett['ex'] ){ ?> j-required<? } ?>" value="<?= $item['price'] ?>" pattern="[0-9\.,]*" />
        <select name="price_curr" class="input-small"><?= Site::currencyOptions($price_curr_selected) ?></select>
        <? if($price_sett['ex'] & BBS::PRICE_EX_MOD) { ?>
        <span>
            &nbsp;&nbsp;<label class="checkbox inline"><input type="checkbox" name="price_ex[2]" class="j-price-mod" value="<?= BBS::PRICE_EX_MOD ?>" <? if($item['price_ex'] & BBS::PRICE_EX_MOD) { ?> checked="checked" <? } ?> /><small><?= ( ! empty($price_sett['mod_title'][LNG]) ? $price_sett['mod_title'][LNG] : _t('item-form', 'Торг возможен') ) ?></small></label>
        </span>
        <? } ?>

    </div>
</div>
<? }

# Дин.свойства
if( ! empty($dp) ) {
    echo $dp;
}