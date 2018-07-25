<?php
$U = mt_rand(1,100);
$placeholder = ! empty($placeholder) ? HTML::escape($placeholder) : _t('geo','Введите название региона');
$cancel = ! empty($cancel);
if ($covering_type == Geo::COVERING_CITY): ?>
    <input type="hidden" name="<?= $field_name ?>" value="<?= $field_value ?>" />
<? else:
    tpl::includeJS('autocomplete', true); ?>
    <input type="hidden" name="<?= $field_name ?>" class="j-geo-region-select-id" id="j-geo-region-select-id<?= $U ?>" value="<?= $field_value ?>" />
    <input type="text" class="autocomplete j-geo-region-select-ac" id="j-geo-region-select-ac<?= $U ?>" value="<?= HTML::escape($field_title) ?>" placeholder="<?= $placeholder ?>" style="<?= ! empty($width) ? 'width:'.$width : '' ?>" />
    <? if ($cancel): ?><a href="#" id="j-geo-region-select-ac-cancel<?= $U ?>" class="disabled" style="<? if( ! $field_value){ ?>display:none;<? } ?>margin-left:-22px;"><i class="icon-remove"></i></a><? endif; ?>
    <script type="text/javascript">
        <? js::start() ?>
        $(function(){
            $('#j-geo-region-select-ac<?= $U ?>').autocomplete('<?= $this->adminLink('regionSuggest', 'geo') ?>',
                {valueInput: $('#j-geo-region-select-id<?= $U ?>'),
                    params:<?= ( ! empty($params) ? func::php2js($params) : '{reg:1,country:1}') ?>,
                    suggest: <?= Geo::regionPreSuggest(Geo::defaultCountry()) ?>,
                    onSelect: <? if( ! empty($on_change)) { echo $on_change; } else { ?>function(){}<? } ?>,
                    cancel:<? if ($cancel) { ?>$('#j-geo-region-select-ac-cancel<?= $U ?>')<? } else { ?>''<? } ?>
                });
        });
        <? js::stop() ?>
    </script>
<? endif;