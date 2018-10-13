<?php
    /**
     * @var $this Banners
     */
    tpl::includeJS(array('datepicker','autocomplete'), true);
    $aData = HTML::escape($aData, 'html', array('click_url','link','url_match','title','alt','description'));
    $edit = ! empty($id);

    $aTypes = bff::filter('banners.admin.banner.form.types', array(
        Banners::TYPE_IMAGE => array('t'=>_t('banners', 'Изображение'), 'key'=>'image', 'image'=>true, 'click_url'=>true, 'target_blank'=>true),
        Banners::TYPE_FLASH => array('t'=> _t('banners', 'Flash'), 'key'=>'flash', 'image'=>true, 'click_url'=>true, 'target_blank'=>true),
        Banners::TYPE_CODE  => array('t'=>_t('banners', 'Код'), 'key'=>'code', 'image'=>false, 'click_url'=>1, 'target_blank'=>false),
        //Banners::TYPE_TEASER=> array('t'=>_t('banners', 'Тизер'), 'key'=>'teaser', 'image'=>true, 'click_url'=>true, 'target_blank'=>true),
    ), array('edit'=>$edit));
    if( ! isset($aTypes[$type]) ) {
        $type = key($aTypes);
    }

    $sitemap = ( ! empty($sitemap_id) ? explode(',', $sitemap_id) : array());
    $sitemap = $this->getSitemap($sitemap, 'checkbox', 'sitemap_id');

    $flash = $this->flashData( (isset($type_data) ? $type_data : '') );
    $showCountry = Geo::coveringType(Geo::COVERING_COUNTRIES);
    if ($showCountry) {
        $countries = array();
        $t = Geo::countriesList();
        foreach($t as $v){
            $countries[ $v['id'] ] = array('id' => $v['id'], 'title' => $v['title']);
        }
    }
?>
<form method="post" action="" enctype="multipart/form-data" id="j-banner-form" class="hidden">
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr>
    <td class="row1" width="130"><span class="field-title"><?= _t('banners', 'Позиция баннера'); ?></span>:</td>
    <td class="row2">
        <select name="pos" onchange="jBanners.onPosition();" id="j-banner-position" style="width: auto; height: 27px;">
            <?php foreach($positions as $v): ?>
                <option value="<?= $v['id'] ?>" data="{sitemap:<?= $v['filter_sitemap'] ?>,region:<?= $v['filter_region'] ?>,category:<?= $v['filter_category'] ?>,category_module:'<?= $v['filter_category_module'] ?>',list_pos:'<?= $v['filter_list_pos'] ?>'}"<?php if($pos == $v['id']){ ?> selected="selected"<?php } ?>><?= $v['title'] ?> (<?= $v['sizes'] ?>)</option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>
<tr class="j-banner-filter hidden" id="j-banner-filter-sitemap">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Раздел сайта'); ?></span>:</td>
    <td class="row2"><div style="overflow-y:scroll; overflow-x:hidden; height: 250px; width: 240px; border: 1px solid #DDD9D8; padding:10px; background-color: #fff;"><?= $sitemap ?></div></td>
</tr>
<tr class="j-banner-filter hidden" id="j-banner-filter-category">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Категория'); ?></span>:</td>
    <td class="row2">
        <?php foreach($categories as $k=>$v) { ?>
            <div class="j-category-select" data-module="<?= $k ?>" style="overflow-y:scroll; overflow-x:hidden; height: 300px; width: 240px; border: 1px solid #DDD9D8; padding:10px; background-color: #fff;"><?= $v ?></div>
        <?php } ?>
    </td>
</tr>
<tr class="j-banner-filter hidden" id="j-banner-filter-region">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Регион'); ?></span>:</td>
    <td class="row2">
        <?= Geo::i()->regionSelect(0, 'region', array(
            'on_change' => 'jBanners.onRegionSelect',
            'placeholder' => Geo::coveringType(Geo::COVERING_COUNTRIES) ? _t('', 'Страна / Регион / Город') : _t('', 'Регион')
        )); ?>
        <? $regionHTML = function($r = array('id' => '__id__',  'title' => '__title__')) { ob_start(); ob_implicit_flush(false); ?>
            <span class="label j-region" style="margin:0 2px 2px 2px;"><?= $r['title'] ?><?
                ?><a href="javascript:void(0);" class="j-region-del" style="margin-left: 3px;"><i class="icon-remove icon-white" style="margin-top: 0px;"></i></a><?
                ?><input type="hidden" name="regions[]" class="j-region-id" value="<?= $r['id'] ?>"></span>
        <? return ob_get_clean(); }; ?>
        <div class="j-regions-list">
            <span class="label j-empty"<?= ! empty($regions) ? ' style="display:none;" ' : '' ?>><?= _t('', 'Во всех регионах'); ?></span>
            <? if ( ! empty($regions)) { foreach($regions as $k => $v){ echo $regionHTML(array('id' => $k, 'title' => $showCountry && isset($countries[ $v['country'] ]) ? $countries[ $v['country'] ]['title'].' / '.$v['title'] : $v['title'])); } } ?>
        </div>
    </td>
</tr>
    <tr class="j-banner-filter hidden" id="j-banner-filter-list_pos">
        <td class="row1"><span class="field-title"><?= _t('banners', '№ позиции в списке'); ?></span>:</td>
        <td class="row2">
            <select id="j-list-pos-type" style="width: auto; height: 27px;">
                <option value="<?= Banners::LIST_POS_FIRST ?>" <?= $list_pos == Banners::LIST_POS_FIRST ? 'selected="selected"' : '' ?>><?= _t('banners', 'Первая'); ?></option>
                <option value="1" <?= $list_pos > 0 ? 'selected="selected"' : '' ?>><?= _t('banners', 'Указанная'); ?></option>
                <option value="<?= Banners::LIST_POS_LAST ?>" <?= $list_pos == Banners::LIST_POS_LAST ? 'selected="selected"' : '' ?>><?= _t('banners', 'Последняя'); ?></option>
            </select>
            <input type="number" name="list_pos" value="<?= $list_pos ?>" class="input-mini <?= $list_pos < 1 ? ' displaynone' : '' ?>" />
        </td>
    </tr>
<?php $locales = bff::locale()->getLanguages(false); $locale = (empty($locale) ? array(Banners::LOCALE_ALL) : explode(',', $locale)); ?>
<tr id="j-banner-filter-locale" <?php if(sizeof($locales) == 1 || ! Banners::FILTER_LOCALE) { ?> style="display: none;"<?php } ?>>
    <td class="row1 field-title"><?= _t('banners', 'Локализация:'); ?></td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" class="j-locale-filter j-all" name="locale[]" value="<?= Banners::LOCALE_ALL ?>" <?php if(in_array(Banners::LOCALE_ALL,$locale)){ ?> checked="checked"<?php } ?> /><?= _t('', 'Все'); ?></label>
        <?php foreach($locales as $k=>$v) { ?>
            <label class="checkbox inline"><input type="checkbox" class="j-locale-filter" name="locale[]" value="<?= $k ?>" <?php if(in_array($k,$locale)){ ?> checked="checked"<?php } ?> /><?= $v['title'] ?></label>
        <?php } ?>
    </td>
</tr>
<tr class="required">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Дата начала показа'); ?></span>:</td>
    <td class="row2">
        <input type="text" name="show_start" id="j-banner-show-start" value="<?= tpl::date_format_pub( (!empty($show_start) ? $show_start : time()) , 'd-m-Y') ?>" class="input-small" />
    </td>
</tr>
<tr class="required">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Дата окончания показа'); ?></span>:</td>
    <td class="row2">
        <input type="text" name="show_finish" id="j-banner-show-finish" value="<?= tpl::date_format_pub( (!empty($show_finish) ? $show_finish : time() + 604800) , 'd-m-Y') ?>" class="input-small" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title"><?= _t('banners', 'Лимит показов'); ?></span>:<br /><span class="desc"><?= _t('banners', '(число)'); ?></span></td>
    <td class="row2">
        <input type="text" name="show_limit" placeholder="<?= _te('banners', 'нет лимита'); ?>" value="<?= ($show_limit == 0 ? '' : $show_limit) ?>" class="input-small" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title"><?= _t('banners', 'Тип баннера'); ?></span>:</td>
    <td class="row2">
        <?php foreach($aTypes as $k=>$v) { ?>
            <label class="radio"><input type="radio" name="type" value="<?= $k ?>" <?php if($k == $type){ ?> checked="checked"<?php } ?> onclick="jBanners.onType(<?= $k ?>, false);" /><?= $v['t'] ?></label>
        <?php } ?>
    </td>
</tr>
<tr id="j-banner-type-data-image" class="j-banner-image hidden">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Изображение'); ?></span>:</td>
    <td class="row2">
        <?php if($edit && !empty($img)){ ?>
            <a href="<?= $this->buildUrl($id, $img, Banners::szView); ?>" id="j-banner-preview" target="_blank"><img src="<?= $this->buildUrl($id, $img, Banners::szThumbnail); ?>" alt="" title="<?= _te('banners', 'оригинальный размер'); ?>" /></a><br /><br />
        <?php } ?>
        <label class="inline"><input type="file" name="img" /></label><br />
        <label class="checkbox inline"><input type="checkbox" value="1" checked="checked" name="img_resize" /><?= _t('banners', 'уменьшать изображение (до требуемых размеров позиции)'); ?></label>
    </td>
</tr>
<tr id="j-banner-type-data-flash" class="hidden">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Flash'); ?></span>:</td>
    <td class="row2">
        <table style="margin-left: -3px;">  
            <tr>
                <td class="row1">
                    <?php if($edit && ! empty($flash['file']))
                    {
                        tpl::includeJS('swfobject', true);
                        ?>
                        <div id="flash_preview" style="display: none;"></div>
                        <script type="text/javascript">
                            swfobject.embedSWF("<?= $this->buildUrl($id, $flash['file'], Banners::szFlash) ?>", "flash_preview", "<?= ($flash['width'] > 0 ? $flash['width']*0.5 : '100%') ?>", "<?= $flash['height']*0.5 ?>", "9.0.0", "<?= SITEURL_STATIC.'/js/bff/swfobject/expressInstall.swf' ?>", false, {wmode:'opaque'});
                        </script>
                        <br /><br />
                    <?php } ?>
                    <input type="file" size="30" name="flash_file" />
                </td>
            </tr>
            <tr>
                <td class="row1 required">
                    <input type="text" name="flash_width" value="<?= floatval($flash['width']) ?>" class="input-mini" /><span class="help-inline"><?= _t('banners', 'Ширина, px'); ?></span>
                </td>
            </tr>
            <tr>
                <td class="row2 required">
                   <input type="text" name="flash_height" value="<?= floatval($flash['height']) ?>" class="input-mini" /><span class="help-inline"><?= _t('banners', 'Высота, px'); ?></span>
                </td>
            </tr> 
            <tr>
                <td class="row2">
                    <input type="text" name="flash_key" value="<?= HTML::escape($flash['key']) ?>" class="input-mini" /><span class="help-inline"><?= _t('banners', 'Ключ, для передачи ссылки подсчета переходов (flashvars)'); ?></span>
                </td>
            </tr>
        </table>
    </td>    
</tr>
<tr id="j-banner-type-data-code" class="hidden">
	<td class="row1 field-title">
	    <a href="javascript:void(0);" class="ajax" id="j-code-macro-link" data-original-title="" title=""><?= _t('banners', 'Код'); ?>:</a><div id="j-code-macro-popover"></div>
    </td>
	<td class="row2">
	    <textarea name="code" rows="5" class="stretch"><?php if($type == Banners::TYPE_CODE && !empty($type_data)){ echo HTML::escape($type_data); } ?></textarea>
    </td>
</tr>
<tr id="j-banner-type-data-teaser" class="hidden">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Текст тизера'); ?></span>:</td>
    <td class="row2"><input type="text" name="teaser" value="<?= ($type == Banners::TYPE_TEASER ? HTML::escape($type_data) : '') ?>" class="stretch" /></td>
</tr>
<tr class="j-banner-click-url required">
    <td class="row1"><span class="field-title"><?= _t('banners', 'Ссылка'); ?></span>:</td>
    <td class="row2">
        <input type="text" name="click_url" value="<?= $click_url ?>" class="stretch" />
    </td>
</tr>
<tr class="j-banner-target-blank <?= $type != Banners::TYPE_IMAGE && $type != Banners::TYPE_FLASH ? 'displaynone' : ''?>">
    <td class="row1"></td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" value="1" <?= $target_blank ? 'checked="checked"' : '' ?> name="target_blank" /><?= _t('banners', 'открывать в новом окне'); ?></label>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title"><?= _t('banners', 'Ссылка подсчета<br/>переходов'); ?></span>:</td>
    <td class="row2">
        <input type="text" name="link" value="<?= $link ?>" readonly="readonly" class="stretch" />
    </td>
</tr>
<tbody<?php if( ! Banners::FILTER_URL_MATCH ) { ?> class="hidden"<?php } ?>>
<tr>
    <td class="row1"><span class="field-title"><?= _t('banners', 'URL размещения:'); ?></span><br />
        <span class="desc small"><?= _t('banners', '(относительный URL)'); ?></span>
    </td>
    <td class="row2">
        <input type="text" name="url_match" value="<?= $url_match ?>" class="stretch" />
        <span class="desc"><?= _t('banners', 'Баннер будет отображаться только на странице с указанным адресом и вложенные.'); ?><br />
            <label class="inline checkbox"><input type="checkbox" name="url_match_exact"<?php if($url_match_exact){ ?> checked="checked"<?php } ?> /><?= _t('banners', 'Не учитывать вложенные страницы (относительно данной адреса)'); ?></label></span>
    </td>
</tr>
</tbody>
<tr>
    <td class="row1"><span class="field-title"><?= _t('banners', 'Title'); ?></span>:</td>
    <td class="row2">
        <input type="text" name="title" value="<?= $title ?>" class="stretch" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title"><?= _t('banners', 'Alt'); ?></span>:</td>
    <td class="row2">
        <input type="text" name="alt" value="<?= $alt ?>" class="stretch" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title"><?= _t('banners', 'Заметка'); ?></span>:</td>
    <td class="row2"><textarea name="description" rows="3"><?= $description ?></textarea></td>
</tr>
<? bff::hook('banners.admin.banner.form', array('edit'=>$edit,'data'=>&$aData)); ?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('','Enabled') ?></span>:</td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" name="enabled" value="1" <?php if($enabled){ ?> checked="checked"<?php } ?> /></label>
    </td>
</tr>
<tr class="footer">
    <td colspan="2">
        <input class="btn btn-success button submit" type="submit" value="<?= _te('', 'Save') ?>" />
        <input class="btn button cancel" type="button" value="<?= _te('', 'Cancel') ?>" onclick="history.back();" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
var jBanners = (function(){
    var $form, formChk, types = <?= func::php2js($aTypes) ?>,
        $categoryBlock, $regionsList;

    $(function(){
        $form = $('#j-banner-form');
        formChk = new bff.formChecker($form);

        var bannersShowDateMin = new Date(<?= date('Y,n,d', mktime(0,0,0,date('n')-1, date('d'), date('y'))); ?>);
        bff.datepicker($('#j-banner-show-start', $form), {minDate: bannersShowDateMin, yearRange: '-2:+2'});
        bff.datepicker($('#j-banner-show-finish', $form), {minDate: bannersShowDateMin, yearRange: '-2:+2'});
        $('#j-banner-preview', $form).fancybox();

        var sitemapChecks = $('#j-banner-filter-sitemap .j-check', $form).on('click', function(){
            var $c = $(this);
            var id = intval($c.val());
            if( $c.is(':checked') ) {
                if($c.hasClass('j-all')) {
                    sitemapChecks.filter(':not(.j-all)').prop('checked', false);
                } else {
                    sitemapChecks.filter('.j-all').prop('checked', false);
                }
            }
        });

        $categoryBlock = $('#j-banner-filter-category', $form);
        var $categoryChecks = $('.j-check', $categoryBlock).on('click',function(){
            var $c = $(this);
            if($c.hasClass('j-all')) {
                $categoryChecks.not($c).prop({disabled:$c.is(':checked')});
                return;
            }

            var parent = (intval($c.data('lvl')) == 1);
            var parentClass = '.'+$c.data('pclass')+':visible';
            if( ! $c.is(':checked') ) {
                if( parent ) {
                    $(parentClass, $categoryBlock).not($c).prop('checked', false);
                } else {
                    $(parentClass+':first', $categoryBlock).prop('checked', false);
                }
            } else {
                if(parent) {
                    $(parentClass, $categoryBlock).not($c).prop('checked', true);
                } else {
                    var nonChecked = $(parentClass+':not(:first,:checked)', $categoryBlock);
                    if( ! nonChecked.length ) {
                        $(parentClass+':first', $categoryBlock).prop('checked', true);
                    }
                }
            }
        });

        jBanners.onType(intval(<?= $type ?>), true);
        jBanners.onPosition();
        $form.removeClass('hidden');

        if (bff.bootstrapJS()) {
            <?php $macroses = array(
                '{query}'     => _tejs('banners', 'Текст поискового запроса'),
                '{click_url}' => _tejs('banners', 'Ссылка подсчета переходов'),
            ); ?>
            $('#j-code-macro-link').popover({
                trigger: 'click',
                placement: 'bottom',
                container: '#j-code-macro-popover',
                title: '<?= _t('', 'Доступные макросы:'); ?>',
                html: true,
                content: '<?php foreach ($macroses as $k => $v){ echo('<div><a href="javascript:" class="ajax" onclick="return jBanners.onCodeMacros(this);">'.$k.'</a> - '.$v.'</div>'); } ?>'
            });
        }

        var $localeFilter = $form.find('.j-locale-filter');
        $form.on('click', '.j-locale-filter', function(){
            var $c = $(this);
            if ($c.hasClass('j-all')) {
                if ($c.is(':checked')) {
                    $localeFilter.not($c).prop({checked:false});
                }
            } else {
                if ($c.is(':checked')) {
                    $localeFilter.filter('.j-all').prop({checked:false});
                }
            }
        });

        var $listPos = $form.find('[name="list_pos"]');
        $form.find('#j-list-pos-type').on('change',function(){
            var v = intval($(this).val());
            $listPos.val(v).toggleClass('displaynone', v < 1);
        });

        $regionsList = $form.find('.j-regions-list');
        $regionsList.on('click', '.j-region-del', function (e) {
            e.preventDefault();
            $(this).closest('.j-region').remove();
            if ( ! $regionsList.find('.j-region').length) {
                $regionsList.find('.j-empty').show();
            }
        });
    });

    function onRegionSelect(id, title, ex)
    {
        $form.find('.j-geo-region-select-ac').val('');
        id = intval(id);
        if ( ! id) return;
        var $exist = $regionsList.find('.j-region-id[value="'+id+'"]');
        if ($exist.length) {
            $exist = $exist.closest('.j-region');
            $regionsList.append($exist);
            return;
        }
        <? if($showCountry): ?>var countries = <?= func::php2js($countries) ?>;
        var country = intval(ex.data[4]);
        if ( ! country) country = id;
        if ( ! countries.hasOwnProperty(country)) return;
        if ( country != id ) {
            title = countries[country]['title'] + ' / ' + title;
        }
        <? endif; ?>
        var html = <?= func::php2js($regionHTML()) ?>;
        html = html.replace(/__id__/g, id);
        html = html.replace(/__title__/g, title);
        $regionsList.append(html);
        $regionsList.find('.j-empty').hide();
    }

    return {
        onPosition: function() {
            // скрываем/отображаем фильтры в зависимости от настроек позиции
            var filters = $('#j-banner-position option:selected', $form).metadata();
            $('.j-banner-filter', $form).hide();
            if (intval(filters['category']) === 1)
            {
                $categoryBlock.find('.j-category-select').hide().find('input').prop('disabled', true);
                $categoryBlock.find('.j-category-select[data-module="'+filters['category_module']+'"]').show()
                              .find('input').prop('disabled', false);
                $categoryBlock.show();
            }
            if (intval(filters['sitemap']) === 1) $('#j-banner-filter-sitemap', $form).show();
            if (intval(filters['region']) === 1) $('#j-banner-filter-region', $form).show();
            if (intval(filters['list_pos']) === 1) $('#j-banner-filter-list_pos', $form).show();
        },
        onType: function(typeID, isInit)
        {
            var typeKey = types[typeID].key;
            $('[id^="j-banner-type-data-"]', $form).hide();
            $('.j-banner-image', $form).toggle(types[typeID].target_blank);
            $('.j-banner-click-url', $form).toggle(types[typeID].click_url !== false).removeClass('clr-error');
            $('.j-banner-click-url', $form).toggleClass('required', types[typeID].click_url === true);
            $('#j-banner-type-data-'+typeKey, $form).show();
            $('.j-banner-target-blank', $form).toggle(types[typeID].target_blank);
            $(function(){ formChk.check(false, true); });
        },
        onRegionSelect: onRegionSelect,
        onCodeMacros: function(el)
        {
            bff.textInsert($form.find('#j-banner-type-data-code textarea').get(0), $(el).text());
        }
    };
}());
</script>