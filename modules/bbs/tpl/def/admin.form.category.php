<?php

?>
<table class="admtbl tbledit">
<? # Тип размещения ?>
<tr>
    <td class="row1" width="115"></td>
    <td class="row2"></td>
</tr>
<tr class="j-item-cattype" <? if( empty($types) || sizeof($types) == 1) { ?>style="display: none;"<? } ?>>
    <td class="row1" width="115"><span class="field-title"><?= _t('bbs', 'Тип объявления'); ?></span>:</td>
    <td class="row2">
        <select name="cat_type" class="j-item-cattype-select" onchange="jItem.onCategoryType($(this));"><?= ( ! empty($types) ? HTML::selectOptions($types, $item['cat_type'], false, 'id', 'title') : '') ?></select>
    </td>
</tr>
<?

# Дин.свойства
if( ! empty($dp) ) {
    ?><tbody class="j-item-dp"><?
    echo $dp;
    ?></tbody><?
}

# Цена
if( ! empty($price) ) {
    $price_curr_selected = ( $edit && $item['price_curr'] ? $item['price_curr'] : ( ! empty($price_sett['curr']) ? $price_sett['curr'] : Site::currencyDefault('id') ) );
?>
<tr class="j-item-price">
    <td class="row1" width="115"><span class="field-title"><?= ( ! empty($price_sett['title'][LNG]) ? $price_sett['title'][LNG] : _t('filter', 'Цена') ) ?></span>:</td>
    <td class="row2">
        <? if( $price_sett['ex'] > 0 ) { ?>
            <? if($price_sett['ex'] & BBS::PRICE_EX_FREE) { ?><label class="radio" style="margin-bottom: 4px;"><input type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_FREE ?>" <? if($item['price_ex'] & BBS::PRICE_EX_FREE) { ?> checked="checked" <? } ?> /> <?= _t('bbs', 'Бесплатно'); ?></label><? } ?>
            <? if($price_sett['ex'] & BBS::PRICE_EX_EXCHANGE) { ?><label class="radio" style="margin-bottom: 4px;"><input type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_EXCHANGE ?>" <? if($item['price_ex'] & BBS::PRICE_EX_EXCHANGE) { ?> checked="checked" <? } ?> /> <?= _t('bbs', 'Обмен'); ?></label><? } ?>
            <? if($price_sett['ex'] & BBS::PRICE_EX_AGREED) { ?><label class="radio" style="margin-bottom: 4px;"><input type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_AGREED ?>" <? if($item['price_ex'] & BBS::PRICE_EX_AGREED) { ?> checked="checked" <? } ?> /> <?= _t('bbs', 'Договорная'); ?></label><? } ?>
            <label class="radio inline"<? if($price_sett['ex'] == BBS::PRICE_EX_MOD) { ?> style="display: none;" <? } ?>><input type="radio" name="price_ex[1]" value="<?= BBS::PRICE_EX_PRICE ?>" <? if($item['price_ex'] <= BBS::PRICE_EX_MOD) { ?> checked="checked" <? } ?> /> <?= _t('bbs', 'Цена'); ?>&nbsp;</label>
        <? } ?>
        <input type="text" name="price" class="input-medium" value="<?= $item['price'] ?>" />
        <select name="price_curr" class="input-mini"><?= Site::currencyOptions($price_curr_selected) ?></select>
        <span class="j-item-price-mod">
            <? if($price_sett['ex'] & BBS::PRICE_EX_MOD) { ?><label class="checkbox inline"><input type="checkbox" name="price_ex[2]" value="<?= BBS::PRICE_EX_MOD ?>" <? if($item['price_ex'] & BBS::PRICE_EX_MOD) { ?> checked="checked" <? } ?> /><?= ( ! empty($price_sett['mod_title'][LNG]) ? $price_sett['mod_title'][LNG] : _t('filter', 'Торг возможен') ) ?></label><? } ?>
        </span>
    </td>
</tr>
<? if ($edit && ! empty($moderated_data['price']) && $moderated_data['price'] != $item['price']): ?>
    <tr class="moderated-data">
        <td class="row1"></td>
        <td class="row2">
            <div class="moderation-compare moderation-compare-inline" style="width: 136px;"><?= $moderated_data['price'] ?></div>
        </td>
    </tr>
<? endif; ?>
<? }

# Частное лицо/Бизнес ?>
<? if($owner_business) {
    $owner_types = array(
        BBS::OWNER_PRIVATE=>( ! empty($owner_private_form) ? $owner_private_form : _t('bbs','Частное лицо')),
        BBS::OWNER_BUSINESS=>( ! empty($owner_business_form) ? $owner_business_form : _t('bbs','Бизнес'))
    );
?>
<tr class="j-item-owner">
    <td class="row1" width="115"><span class="field-title"><?= join(' / ', $owner_types) ?></span>:</td>
    <td class="row2">
        <select name="owner_type" class="j-item-owner-select">
            <?= HTML::selectOptions($owner_types, $item['owner_type'] ) ?>
        </select>
    </td>
</tr>
<? } ?>
<? bff::hook('bbs.admin.item.form.category', array('edit'=>$edit,'data'=>&$aData)); ?>
</table>