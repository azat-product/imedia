<?php
/**
 * Форма выбора города (frontend)
 * @var $this Geo
 * @var $options array
 */

$U = mt_rand(1,100);
$placeholder = ( ! empty($options['placeholder']) ? HTML::escape($options['placeholder']) : _t('','Введите название города') );
$params = array();
if ( ! empty($options['reg']) ) {
    $params['reg'] = 1;
}

$bSelectCountry = ($covering_type == Geo::COVERING_COUNTRIES);
if ($bSelectCountry) {
    $params['country_id'] = intval($country_id);
}
?>
<div class="dropdown">
    <?php if($bSelectCountry): ?>
        <select name="<?= $field_country_name ?>" class="form-control j-geo-city-select-country" id="j-geo-country-select-id<?= $U ?>" autocomplete="off" style="<?php if ( ! empty($options['country_width'])){ ?>width:<?= $options['country_width'] ?>;<?php } ?>"><?= $country_options ?></select>
        <div class="mrgt10">
    <?php else: ?>
        <input type="hidden" class="form-control j-geo-city-select-country" value="<?= HTML::escape(Geo::regionTitle($country_id)) ?>" />
    <?php endif;

if ($covering_type == Geo::COVERING_CITY && ($city_id == $covering_city_id)) { ?>
    <input type="hidden" name="<?= $field_name ?>" class="j-geo-city-select-id" id="j-geo-city-select-id<?= $U ?>" value="<?= $city_id ?>" />
    <input type="hidden" class="j-geo-city-select-ac" id="j-geo-city-select-ac<?= $U ?>" value="<?= HTML::escape($city['title']) ?>" />
    <div class="pdt5"><strong><?= $city['title'] ?></strong></div>
<?php } else { ?>
    <?php tpl::includeJS('autocomplete', true); ?>
    <input type="hidden" name="<?= $field_name ?>" class="j-geo-city-select-id<?php if(!empty($options['required'])){ ?> j-required<?php } ?>" id="j-geo-city-select-id<?= $U ?>" value="<?= $city_id ?>" />
    <input type="text" class="form-control j-geo-city-select-ac" id="j-geo-city-select-ac<?= $U ?>" value="<?= ( ! empty($city['title']) ? HTML::escape($city['title']) : '' ) ?>" placeholder="<?= $placeholder ?>" autocomplete="off" style="<?= ! $country_id ? 'display:none;' : '' ?>"/>
   <?php if($bSelectCountry): ?></div><?php endif; ?>
    <script type="text/javascript">
    <?php js::start() ?>
        $(function(){
            var api;
            var $ac = $('#j-geo-city-select-ac<?= $U ?>').autocomplete(bff.ajaxURL('geo', 'region-suggest'),
                {valueInput: $('#j-geo-city-select-id<?= $U ?>'),
                 params:<?= ( ! empty($params) ? func::php2js($params) : '{}') ?>,
                 suggest: <?= Geo::regionPreSuggest($country_id) ?>,
                 onSelect: <?php if( ! empty($options['on_change'])) { echo $options['on_change']; } else { ?>function(){}<?php } ?>,
                 doPrepareText: function(html){
                     var regionTitlePos = html.toLowerCase().indexOf('<br');
                     if( regionTitlePos != -1 ) {
                        html = html.substr(0, regionTitlePos);
                     }
                     html = html.replace(/<\/?[^>]+>/gi, ''); // striptags
                     return $.trim(html);
                 }
            }, function(){ api = this; });
            <?php if($bSelectCountry): ?>
            var cache = {};
            $('#j-geo-country-select-id<?= $U ?>').change(function(){
                var country = intval($(this).val());
                if (country) {
                    $ac.show();
                    $('#j-geo-city-select-ac-cancel<?= $U ?>').removeClass('displaynone');
                    api.setParam('country_id', country);
                    if (cache.hasOwnProperty(country)) {
                        api.setSuggest(cache[country], true);
                    } else {
                        bff.ajax(bff.ajaxURL('geo', 'country-presuggest'), {country: country}, function (data) {
                            cache[country] = data;
                            api.setSuggest(data, true);
                        });
                    }
                    $(this).find('option[value="0"]').hide();
                } else {
                    $ac.hide();
                    $('#j-geo-city-select-id<?= $U ?>').val(0);
                    $('#j-geo-city-select-ac<?= $U ?>').val('');
                    $('#j-geo-city-select-ac-cancel<?= $U ?>').addClass('displaynone');
                }
                <?= ! empty($options['country_on_change']) ? $options['country_on_change'].'(country);' : '' ?>
            });
            <?php endif; ?>

        });
    <?php js::stop() ?>
    </script>
<?php } ?>
</div>