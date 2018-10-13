<?php
/**
 * Форма выбора города (admin)
 * @var $this Geo
 */

if ( empty($is_form) && $covering_type == Geo::COVERING_CITY ) {
    return;
}

$U = mt_rand(1,100);
$cancelCity = ! empty($options['cancel']);
$placeholder = ( ! empty($options['placeholder']) ? HTML::escape($options['placeholder']) : _t('geo','Введите название города') );
$params = array();
if ( ! empty($options['reg']) ) {
    $params['reg'] = 1;
}

$bSelectCountry = $covering_type == Geo::COVERING_COUNTRIES;

if ( ! $bSelectCountry): ?><input type="hidden" class="j-geo-city-select-country" value="<?= HTML::escape(Geo::regionTitle($country_id)) ?>" /><? endif;

if ($covering_type == Geo::COVERING_CITY && ($city_id == $covering_city_id)) { ?>
    <input type="hidden" name="<?= $field_name ?>" class="j-geo-city-select-id" id="j-geo-city-select-id<?= $U ?>" value="<?= $city_id ?>" />
    <input type="hidden" class="j-geo-city-select-ac" id="j-geo-city-select-ac<?= $U ?>" value="<?= HTML::escape($city['title']) ?>" />
    <div style="height: 25px;"><strong><?= $city['title'] ?></strong></div>
<? } else { ?>
    <? tpl::includeJS('autocomplete', true); ?>
    <? if ($bSelectCountry): ?>
        <select name="<?= $field_country_name ?>" class="left j-geo-city-select-country" id="j-geo-country-select-id<?= $U ?>" autocomplete="off" style="<? if ( ! empty($options['country_width'])){ ?>width:<?= $options['country_width'] ?>;<? } ?>"><?= $country_options ?></select>
        <div class="clear"></div>
        <div class="relative left">
    <? endif; ?>
    <input type="hidden" name="<?= $field_name ?>" class="j-geo-city-select-id" id="j-geo-city-select-id<?= $U ?>" value="<?= $city_id ?>" />
    <input type="text" class="autocomplete j-geo-city-select-ac" id="j-geo-city-select-ac<?= $U ?>" value="<?= ( ! empty($city['title']) ? HTML::escape($city['title']) : '' ) ?>" placeholder="<?= $placeholder ?>" style="width:<?= ! empty($options['width']) ? $options['width'] : '212px' ?>;<?= $bSelectCountry && ! $country_id ? 'display:none;' : '' ?>" />
    <? if($bSelectCountry): ?></div><? endif; ?>
    <? if ($cancelCity) { ?><a href="javascript:void(0);" id="j-geo-city-select-ac-cancel<?= $U ?>" class="disabled" style="<? if( ! $city_id){ ?>display:none;<? } ?>margin-left:<?= $bSelectCountry ? '-22px;':'-22px;'?>"><i class="icon-remove"></i></a><? } ?>
    <? if($bSelectCountry): ?><div class="clear"></div><? endif; ?>
    <script type="text/javascript">
    <? js::start() ?>
        $(function(){
            var api;
            var $ac = $('#j-geo-city-select-ac<?= $U ?>').autocomplete('<?= $this->adminLink('regionSuggest','geo','js') ?>',
                {valueInput: $('#j-geo-city-select-id<?= $U ?>'),
                 params:<?= ( ! empty($params) ? func::php2js($params) : '{}') ?>,
                 suggest: <?= Geo::regionPreSuggest($country_id) ?>,
                 onSelect: <? if( ! empty($options['on_change'])) { echo $options['on_change']; } else { ?>function(){}<? } ?>,
                 cancel:<? if ($cancelCity) { ?>$('#j-geo-city-select-ac-cancel<?= $U ?>')<? } else { ?>''<? } ?>,
                 country: <?= $country_id ?>
                }, function(){ api = this; });
            <? if($bSelectCountry): ?>
                var cache = {};
                $('#j-geo-country-select-id<?= $U ?>').on('change',function(){
                    var country = intval($(this).val());
                    if (country) {
                        $ac.show();
                        $('#j-geo-city-select-ac-cancel<?= $U ?>').removeClass('displaynone');
                        api.setParam('country_id', country);
                        if (cache.hasOwnProperty(country)) {
                            api.setSuggest(cache[country], true);
                        } else {
                            bff.ajax('<?= $this->adminLink('ajax&act=country-presuggest','geo','js') ?>', {country: country}, function (data) {
                                cache[country] = data;
                                api.setSuggest(data, true);
                            });
                        }
                    } else {
                        $ac.hide();
                        $('#j-geo-city-select-id<?= $U ?>').val(0);
                        $('#j-geo-city-select-ac<?= $U ?>').val('');
                        $('#j-geo-city-select-ac-cancel<?= $U ?>').addClass('displaynone');
                    }
                    <?= ! empty($options['country_on_change']) ? $options['country_on_change'].'(country);' : '' ?>
                });
            <? endif; ?>
        });
    <? js::stop() ?>
    </script>
<? }