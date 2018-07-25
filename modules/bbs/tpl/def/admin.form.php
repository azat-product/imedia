<?php
    /**
     * @var $this BBS
     */
    Geo::mapsAPI(true);
    tpl::includeJS(array('autocomplete'), true); # адрес
    $aData = HTML::escape($aData, 'html', array('title', 'addr_addr', 'video', 'name', 'email', 'descr'));

    $edit = ($id > 0);
    $tab = $this->input->get('tab', TYPE_NOTAGS);
    $aTabs = array(
        'info' => _t('bbs', 'Описание'),
        'images' => _t('bbs', 'Фото').($edit ? ' ('.$imgcnt.')' : '' ),
    );
    if($edit) {
        if (BBS::commentsEnabled()) { $aTabs['comments'] = _t('bbs', 'Комментарии').' ('.$comments_cnt.')'; }
        $aTabs['claims'] = _t('bbs', 'Жалобы').($claims_cnt ? ' ('.$claims_cnt.')' : '');
        if (bff::servicesEnabled()) {
            $aTabs['svc'] = _t('bbs', 'Услуги');
        }
    }
    $aTabs = bff::filter('bbs.admin.item.form.tabs', $aTabs, array('edit'=>$edit,'data'=>&$aData,'tab'=>&$tab));
    if( ! isset($aTabs[$tab])) {
        $tab = 'info';
    }
    $translate = BBS::translate();

    if( ! isset($moderated_data)){
        $moderated_data = array();
    } else {
        $moderated_data = HTML::escape($moderated_data, 'html', array('title', 'addr_addr', 'video', 'name', 'email', 'descr'));
    }
    $isModerated = function($field, $value, $subKey = false) use( & $moderated_data, $edit){
        if (empty($subKey)) {
            return ($edit && ! empty($moderated_data[ $field ]) && $moderated_data[ $field ] != $value);
        }
        return ($edit && ! empty($moderated_data[ $subKey ][ $field ]) && $moderated_data[ $subKey ][ $field ] != $value);
    };
    $autoTitle = $aData['autoTitle'] = ! empty($cat['tpl_title_view']);
?>

<div class="tabsBar">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$tab ? ' tab-active' : '' ?>"><a href="#" onclick="jItem.onTab('<?= $k ?>', this); return false;"><?= $v ?></a></span>
    <? } ?>
    <div class="progress" style="margin-left: 5px; display: none;" id="form-progress"></div>
    <? if($edit) { ?>
    <div class="pull-right"><a href="<?= BBS::urlDynamic($link, array('from'=>'adm','mod'=>BBS::moderationUrlKey($id))) ?>" class="pull-right" target="_blank"><?= _t('bbs', 'Страница объявления [arrow]', array('arrow'=>'&rarr;')) ?></a></div>
    <div class="clearfix"></div>
    <? } ?>
</div>

<div id="item-form-block-info" class="hidden">
<form method="post" action="">
<? if($translate && $edit): ?><input type="hidden" name="lang" value="<?= $lang ?>" /><? endif; ?>
<table class="admtbl tbledit">
<? if($translate): ?>
    <?= $this->locale->buildForm($aData, 'bbs-item-form','
        <tr>
            <td class="row1" width="115"><span class="field-title"><?= _t(\'\', \'Заголовок\'); ?></span><span class="required-mark">*</span>:</td>
            <td class="row2">
                <input class="stretch j-title <?= $key ?>" type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" maxlength="100" <?= $aData[\'autoTitle\'] ? \'disabled="disabled"\' : \'\' ?> />
            </td>
        </tr>
    '); ?>
<? else: ?>
    <tr>
        <td class="row1" width="115"><span class="field-title"><?= _t('', 'Заголовок'); ?></span><span class="required-mark">*</span>:</td>
        <td class="row2">
            <input class="stretch j-title" type="text" name="title" value="<?= $title ?>" maxlength="100" <?= $autoTitle ? 'disabled="disabled"' : '' ?> />
        </td>
    </tr>
<? endif; ?>
<? if ($isModerated('title', $title)): ?>
    <tr class="moderated-data">
        <td class="row1"></td>
        <td class="row2">
            <div class="moderation-compare"><?= $moderated_data['title'] ?></div>
        </td>
    </tr>
<? endif; ?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('bbs', 'Категория'); ?></span><span class="required-mark">*</span>:</td>
    <td class="row2">
        <input type="hidden" name="cat_id" value="<?= $cat_id ?>" />
        <? foreach($cats as $lvl=>$v):
                ?><select class="cat-select" autocomplete="off" style="margin: 0 5px 7px 0;"><?= $v['cats'] ?></select><?
           endforeach;
        ?>
    </td>
</tr>
<tr>
    <td colspan="2" id="item-form-category" style="padding: 0;">
        <?= ( ! empty($cat['form']) ? $cat['form'] : '' ) ?>
    </td>
</tr>
<tbody id="item-city">
    <tr>
        <td class="row1"><span class="field-title"><?= _t('', 'Город'); ?></span><span class="required-mark">*</span>:</td>
        <td class="row2">
            <?= Geo::i()->citySelect($city_id, true, 'city_id', array(
                'on_change'=>'jItem.onCitySelect',
                'form' => 'bbs-form',
                'country_value' => ($edit ? $reg1_country : 0),
            )); ?>
            <div id="item-regions-delivery" style="<? if (empty($cat['regions_delivery'])) { ?>display: none;<? } ?>">
                <label class="checkbox"><input type="checkbox" name="regions_delivery" class="j-checkbox" <? if (!empty($regions_delivery)) { ?> checked="checked"<? } ?> /><?= _t('bbs', 'Доставка в регионы'); ?></label>
            </div>
        </td>
    </tr>
    <? if (Geo::districtsEnabled()): $bDistricts = false; if($city_id) { $bDistricts = count(Geo::districtList($city_id)) > 0; } ?>
        <tr<?= ! $bDistricts ? ' class="displaynone"' : '' ?>>
            <td class="row1 field-title"><?= _t('', 'Район:'); ?></td>
            <td class="row2">
                <select name="district_id"><?= Geo::districtOptions($city_id, $district_id, _t('', 'Не указан')) ?></select>
            </td>
        </tr>
    <? endif; ?>
    <tr id="item-metro-block" <? if( (! $edit && empty($city_metro['data'])) || empty($city_metro['data'])){ ?>style="display: none;"<? } ?>>
        <td class="row1"><span class="field-title"><?= _t('', 'Метро'); ?></span>:</td>
        <td class="row2">
            <select name="metro_id" id="item-metro-sel"><?= $city_metro['html'] ?></select>
        </td>
    </tr>
</tbody>
<tbody id="item-addr" <? if( ! $edit || ($edit && empty($cat['addr']))){ ?> style="display: none;"<? } ?>>
    <tr>
        <td class="row1"><span class="field-title"><?= _t('', 'Адрес'); ?></span>:</td>
        <td class="row2">
            <input type="hidden" name="addr_lat" id="item-addr-lat" value="<?= $addr_lat ?>" />
            <input type="hidden" name="addr_lon" id="item-addr-lon" value="<?= $addr_lon ?>" />
            <input type="text"   name="addr_addr" id="item-addr-addr" value="<?= $addr_addr ?>" style="width: 475px;" />
            <a href="#" class="ajax" onclick="jItem.onMapSearch(); return false;"><?= _t('', 'найти адрес'); ?></a>
            <? if ($isModerated('addr_addr', $addr_addr)): ?>
                <div class="moderated-data">
                    <div class="moderation-compare moderation-compare-inline" style="margin-top: 7px; width: 460px;"><?= $moderated_data['addr_addr'] ?></div>
                </div>
            <? endif; ?>
            <div id="item-addr-map" class="map-google" style="width: 485px; height: 260px; margin-top: 5px;"></div>
        </td>
    </tr>
</tbody>
<? if($translate): ?>
    <?= $this->locale->buildForm($aData, 'bbs-item-form','
        <tr>
            <td class="row1"><span class="field-title"><?= _t(\'\', \'Описание\'); ?></span><span class="required-mark">*</span>:</td>
            <td class="row2">
                <textarea class="stretch" rows="5" cols="" name="descr[<?= $key ?>]"><?= HTML::escape($aData[\'descr\'][$key]); ?></textarea>
            </td>
        </tr>
    '); ?>
<? else: ?>
    <tr>
        <td class="row1"><span class="field-title"><?= _t('', 'Описание'); ?></span><span class="required-mark">*</span>:</td>
        <td class="row2">
            <textarea class="stretch" rows="5" cols="" name="descr"><?= $descr ?></textarea>
        </td>
    </tr>
<? endif; ?>
<? if ($isModerated('descr', $descr)): ?>
    <tr class="moderated-data">
        <td class="row1"></td>
        <td class="row2">
            <div class="moderation-compare"><?= \bff\utils\TextParser::highlightStringCompare($moderated_data['descr'], $translate ? $descr[$lang] : $descr); ?></div>
        </td>
    </tr>
<? endif; ?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('bbs', 'Ссылка на видео'); ?></span>:</td>
    <td class="row2">
        <input class="stretch" type="text" name="video" value="<?= $video ?>" maxlength="2000" />
        <span class="desc small"><?= _t('bbs', 'Youtube, Rutube, Vimeo'); ?></span>
    </td>
</tr>
<? bff::hook('bbs.admin.item.form', array('edit'=>$edit,'data'=>&$aData,'isModerated'=>$isModerated)); ?>
<tr>
    <td colspan="2"><hr class="cut" /></td>
</tr>
<? if($edit):
    # Редактирование:
?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('users', 'Пользователь'); ?></span>:</td>
    <td class="row2">
        <a href="#" class="ajax" onclick="return bff.userinfo(<?= $user_id ?>);"><?= $email ?></a>
        <? if(BBS::publisher(BBS::PUBLISHER_USER_OR_SHOP) && $user_shop_id) { ?>
            <div id="j-item-user-publisher" style="margin: 5px;">
                <label class="inline radio"><input type="radio" name="shop" value="0"<? if( ! $shop_id) { ?> checked="checked"<? } ?> /><?= _t('bbs', 'Частное лицо'); ?></label>
                <label class="inline radio"><input type="radio" name="shop" value="1"<? if( $shop_id) { ?> checked="checked"<? } ?> /><?= _t('bbs', 'Магазин'); ?></label>
            </div>
        <? } else if(BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP) && $user_shop_id) { ?>
            <input type="hidden" name="shop" value="1" />
        <? } ?>
    </td>
</tr>
<tbody<? if($edit && $this->getItemContactsFromProfile()){ ?> class="displaynone"<? } ?>>
<tr>
    <td class="row1"><span class="field-title"><?= _t('users', 'Имя'); ?></span><span class="required-mark">*</span>:</td>
    <td class="row2">
        <input type="text" name="name" value="<?= $name ?>" maxlength="50" />
    </td>
</tr>
<? if ($isModerated('name', $name)): ?>
    <tr class="moderated-data">
        <td class="row1"></td>
        <td class="row2">
            <div class="moderation-compare moderation-compare-inline" style="width: 192px;"><?= $moderated_data['name'] ?></div>
        </td>
    </tr>
<? endif; ?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('users', 'Телефоны'); ?></span>:</td>
    <td class="row2">
        <div id="j-item-phones"></div>
    </td>
</tr>
<? if ( $edit && ! empty($moderated_data['phones'])): $equvel = true;
    foreach ($moderated_data['phones'] as $v) {
        $exist = false;
        foreach ($phones as $vv) {
            if($v['v'] == $vv['v']){
                $exist = true;
                break;
            }
        }
        if ( ! $exist) {
            $equvel = false;
            break;
        }
    }
    if ( ! $equvel): ?>
    <tr class="moderated-data">
        <td class="row1"></td>
        <td class="row2">
            <? foreach ($moderated_data['phones'] as $v): ?>
                <div><div class="moderation-compare moderation-compare-inline" style="width: 192px;"><?= $v['v'] ?></div></div>
            <? endforeach; ?>
        </td>
    </tr>
<? endif; endif; ?>

<? foreach (Users::contactsFields() as $contact_key => $contact): ?>
    <tr>
        <td class="row1 field-title">
            <?= $contact['title'] ?>:
        </td>
        <td class="row2">
            <input type="text" name="contacts[<?= $contact_key ?>]" value="<?= isset($contacts[$contact_key]) ? HTML::escape($contacts[$contact_key]) : '' ?>" />
        </td>
    </tr>
    <? if (isset($contacts[$contact_key]) && $isModerated($contact_key, $contacts[$contact_key], 'contacts')): ?>
        <tr class="moderated-data">
            <td class="row1"></td>
            <td class="row2">
                <div class="moderation-compare moderation-compare-inline" style="width: 192px;"><?= $moderated_data['contacts'][$contact_key] ?></div>
            </td>
        </tr>
    <? endif; ?>
<? endforeach; ?>
</tbody>
<tr>
    <td class="row1" colspan="2">
        <? $aData['user'] = array('blocked'=>$user_blocked); ?>
        <?= $this->viewPHP($aData, 'admin.form.status'); ?>
    </td>
</tr>
<? else:
    # Добавление:
?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('users', 'Пользователь'); ?></span><span class="required-mark">*</span>:</td>
    <td class="row2">
        <input type="hidden" name="user_id" value="0" id="j-item-user-id" />
        <input type="text" name="email" value="" id="j-item-user-email" class="autocomplete input-large" placeholder="<?= _te('users', 'Введите e-mail пользователя'); ?>" />
        <? if( BBS::publisher(BBS::PUBLISHER_SHOP) ) { ?>
            <a href="javascript:void(0);" id="j-item-user-help" data-placement="right" data-toggle="tooltip" title="<?= _te('bbs', 'только пользователи с открытыми магазинами'); ?>"><i class="icon-question-sign"></i></a>
            <script type="text/javascript">$(function(){ if(bff.bootstrapJS()) { $('#j-item-user-help').tooltip(); } });</script>
        <? } else if( BBS::publisher(BBS::PUBLISHER_USER_OR_SHOP) ) { ?>
            <div id="j-item-user-publisher" style="display: none; margin:5px;">
                <label class="inline radio"><input type="radio" name="shop" value="0" checked="checked" /><?= _t('bbs', 'Частное лицо'); ?></label>
                <label class="inline radio"><input type="radio" name="shop" value="1" /><?= _t('bbs', 'Магазин'); ?></label>
            </div>
        <? } else if( BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP) ) { ?>
            <input type="hidden" name="shop" value="0" />
        <? } ?>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title"><?= _t('bbs', 'Публикация'); ?></span>:</td>
    <td class="row2">
        <? if(BBS::formPublicationPeriod()): $publicationPeriodOpts = $this->publicationPeriodOptions($daysDefault); ?>
            <select name="publicated_period" class="input-small"><?= HTML::selectOptions($publicationPeriodOpts, $daysDefault, false, 'days', 't') ?></select>
            <span class="desc j-publicated-period-help">
                <?= _t('item-form', 'до [date]', array('date'=>tpl::date_format2(time() + $daysDefault * 86400, false, true))); ?>
            </span>
        <? else: ?>
            <span><?= _t('', 'от'); ?> <?= date('d.m.Y') ?> <?= _t('', 'до'); ?> <b><?= $this->getItemPublicationPeriod(false, 'd.m.Y') ?></b></span>
        <? endif; ?>
    </td>
</tr>
<? endif; ?>
<tr class="footer">
    <td class="row1" colspan="2">
        <input type="button" class="btn btn-success button submit" onclick="jItem.onSubmit(this);" value="<?= _te('', 'Save') ?>" data-loading-text="<?= _te('', 'Сохранение...'); ?>" />
        <? if($edit){ ?><input type="button" class="btn btn-success success button" onclick="jItem.onSubmit(this, 1);"  value="<?= _te('', 'Сохранить и вернуться к списку'); ?>" data-loading-text="<?= _te('', 'Сохранение...'); ?>" /><? } ?>
        <input type="button" class="btn button cancel" value="<?= _te('', 'Cancel') ?>" onclick="history.back();" />
    </td>
</tr>
</table>
</form>
</div>

<div id="item-form-block-images" class="hidden"><?= $this->viewPHP($aData, 'admin.form.images'); ?></div>

<div id="item-form-block-comments" class="hidden"></div>

<div id="item-form-block-claims" class="hidden"></div>

<? bff::hook('bbs.admin.item.form.tabs.content', array('edit'=>$edit,'data'=>&$aData,'tabs'=>$aTabs)); ?>

<? if (bff::servicesEnabled()) { ?>
<div id="item-form-block-svc" class="hidden"><? if($edit) { echo $this->viewPHP($aData, 'admin.form.svc'); } ?></div>
<? } ?>

<script type="text/javascript">
var jItem = (function(){
    var id = <?= $id ?>, blocksPrefix = 'item-form-block-';
    var $form, $formCat, $progress, $cityBlock, $delivery;
    var addr = {inited:false,$block:0,$addr:0,$lat:0,$lon:0,lastQuery:''};
    var metro = {$block:0,$sel:0,data:{}};
    var catDataCache = {}, catTypesEx = intval(<?= (BBS::CATS_TYPES_EX ? 1 : 0) ?>);
    var url = '<?= $this->adminLink('edit&id='.$id.'&act='); ?>';
    var $districts = false, distCache = {};
    var memPrice = {};
    var autoTitle = <?= $autoTitle ? 'true' : 'false' ?>, $title;

    $(function(){
        $progress = $('#form-progress');
        initBlock('<?= $tab ?>', false);
    });

    function initBlock(key, $block)
    {
        if($block === false) {
            $block = $('#'+blocksPrefix+key);
        }
        switch(key)
        {
            case 'info':
            {
                $form = $block.find('form:first');
                $formCat = $block.find('#item-form-category');

                $form.on('change', 'select.cat-select', function(){
                    catSelect($(this));
                });

                $cityBlock = $form.find('#item-city');
                metro.$block = $cityBlock.find('#item-metro-block');
                metro.$sel = $cityBlock.find('#item-metro-sel');
                $delivery = $form.find('#item-regions-delivery');

                $districts = $form.find('[name="district_id"]');
                $title = $form.find('.j-title');

                addr.$block = $form.find('#item-addr');
                $block.removeClass('hidden');
                geoInitAddr(function(){});

                if( id > 0 ) {
                    catType($('.j-item-cattype-select'));
                } else {
                    $form.find('#j-item-user-email').autocomplete('<?= $this->adminLink('ajax&act=item-user') ?>',
                        {valueInput: $form.find('#j-item-user-id'), onSelect: function(id, title, ex){
                            var $publisher = $('#j-item-user-publisher');
                            if($publisher.length) $publisher.toggle(intval(ex.data[2])>0);
                            <? if(BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP)): ?>
                            $form.find('[name="shop"]').val(intval(ex.data[2])>0 ? 1 : 0);
                            <? endif; ?>
                        }});
                }

                initPhones(<?= Users::i()->profilePhonesLimit ?>, <?= func::php2js($phones) ?>);
                <? if($edit) {
                    ?>savePrice();<?
                } else {
                    if (BBS::formPublicationPeriod()) {
                        foreach ($publicationPeriodOpts as &$v) {
                            $v = _t('item-form', 'до [date]', array('date'=>tpl::date_format2(time() + $v['days'] * 86400, false, true)));
                        } unset($v);
                ?>
                        var periodOpts = <?= func::php2js($publicationPeriodOpts) ?>;
                        var $periodHelp = $form.find('.j-publicated-period-help');
                        $form.find('[name="publicated_period"]').change(function(){
                            var v = $(this).val();
                            if (periodOpts.hasOwnProperty(v)){
                                $periodHelp.html(periodOpts[v]);
                            }
                        });
                <? } } ?>

                $form.on('change', function(){
                    fillTitle();
                });
            } break;
            case 'images':
            {
                // see jItemImages.init
            } break;
            case 'comments':
            {
                bff.ajax(url+'comments-init', {id:id}, function(data){
                    if(data) {
                        $block.html(data.html);
                    }
                }, $progress);
            } break;
            case 'claims':
            {
                bff.ajax(url+'claims-init', {id:id}, function(data){
                    if(data) {
                        $block.html(data.html);
                    }
                }, $progress);
            } break;
        }
        $block.addClass('inited').removeClass('hidden');
    }

    function catForm(data, $select)
    {
        addr.$block.hide();
        if(data === 'empty') {
            $formCat.empty();
            return;
        }

        if(data.subs > 0) {
            $select.after('<select class="cat-select" autocomplete="off" style="margin: 0 5px 7px 0;">'+data.cats+'</select>');
            return;
        }

        $formCat.html( data.form );
        <? if($edit) { ?>restorePrice();<? } ?>

        if(intval(data.addr) > 0) {
            addr.$block.show();
            geoInitAddr(function(){
                addr.mapEditor.centerByMarker();
            });
        }
        if(intval(data.regions_delivery) > 0) {
            $delivery.show();
            $delivery.find('.j-checkbox').prop('checked', intval(data.item.regions_delivery) > 0);
        }
        autoTitle = intval(data.tpl_title_enabled) ? true : false;
        $title.prop('disabled', autoTitle);
        fillTitle();
    }

    function catSelect($select)
    {
        catForm('empty');

        var catID = intval($select.val());
        $form.find('[name="cat_id"]').val(catID);
        $select.nextAll().remove();
        if( ! catID) return;

        if(catDataCache.hasOwnProperty(catID)) {
            catForm( catDataCache[catID], $select );
        } else {
            bff.ajax('<?= $this->adminLink('ajax&act=item-form-cat'); ?>', {cat_id: catID}, function(data){
                if(data && data.success) {
                    catForm( (catDataCache[catID] = data), $select );
                }
            }, $progress);
        }
    }

    function catType($select)
    {
        if( ! $select || catTypesEx ) return;
        if( intval($select.val()) == <?= BBS::TYPE_SEEK ?> ) {
            $formCat.find('.j-item-dp tr:not(.extra-sett-in_seek)').addClass('hidden');
        } else {
            $formCat.find('.j-item-dp tr.hidden').removeClass('hidden');
        }
    }

    function geoInitAddr(callback)
    {
        if( ! $('#item-addr-map').is(':visible')) return;
        if( addr.inited ) {
            callback();
            return;
        }
        addr.inited = true;
        addr.$addr = addr.$block.find('#item-addr-addr');
        addr.$lat  = addr.$block.find('#item-addr-lat');
        addr.$lon  = addr.$block.find('#item-addr-lon');

        addr.map = bff.map.init('item-addr-map', [addr.$lat.val(), addr.$lon.val()], function(map){
            if (this.isYandex()) {
                map.controls.add('zoomControl', {top:5,left:5});
            }

            addr.mapEditor = bff.map.editor();
            addr.mapEditor.init({
                map: map, version: '2.1',
                coords: [addr.$lat, addr.$lon],
                address: addr.$addr,
                addressKind: 'house',
                updateAddressIgnoreClass: 'typed'
            });

            addr.$addr.bind('change keyup input', $.debounce(function(){
                if( ! $.trim(addr.$addr.val()).length ) {
                    addr.$addr.removeClass('typed');
                } else {
                    addr.$addr.addClass('typed');
                    jItem.onMapSearch();
                }
            }, 700));
            jItem.onMapSearch();
        }, {zoom:12});

    }

    function geoRefreshMetro(cityID)
    {
        if( metro.data.hasOwnProperty(cityID) ) {
            metro.$sel.html( metro.data[cityID] ).val(0);
            metro.$block.show();
        } else {
            bff.ajax('<?= $this->adminLink('ajax&act=city-metro', 'geo'); ?>', {city:cityID}, function(data, errors){
                if(data && data.success) {
                    metro.data[cityID] = data.html;
                    geoRefreshMetro(cityID);
                } else {
                    bff.error(errors);
                }
            });
        }
    }

    function initPhones(limit, phones)
    {
        var index  = 0, total = 0;
        var $block = $('#j-item-phones');

        function add(value)
        {
            if(limit>0 && total>=limit) return;
            index++; total++;
            $block.append('<div class="j-phone">\
                                <input type="text" maxlength="40" name="phones['+index+']" value="'+value.replace(/"/g, "&quot;")+'" class="left j-value" placeholder="<?= _te('item-form', 'Номер телефона') ?>" />\
                                <div class="left" style="margin: 3px 0 0 4px;">'+(total==1 ? '<a class="ajax desc j-plus" href="#"><?= _t('item-form', '+ ещё<span [attr]> телефон</span>', array('attr'=>'')) ?></a>' : '<a href="#" class="but cross j-remove"></a>')+'</div>\
                                <div class="clear"></div>\
                            </div>');
        }

        $block.on('click', 'a.j-plus', function(e){ nothing(e);
            add('');
        });

        $block.on('click', 'a.j-remove', function(e){ nothing(e);
            var $ph = $(this).closest('.j-phone');
            if( $ph.find('.j-value').val() != '' ) {
                if(confirm('<?= _t('bbs', 'Удалить телефон?'); ?>')) {
                    $ph.remove(); total--;
                }
            } else {
                $ph.remove(); total--;
            }
        });

        phones = phones || {};
        for(var i in phones) {
            if( phones.hasOwnProperty(i) ) {
                add(phones[i].v);
            }
        }
        if( ! total ) {
            add('');
        }
    }

    function savePrice()
    {
        $formCat.find('.j-item-price').find(':input').each(function(){
            var name = $(this).attr('name');
            if( ! name) return;
            memPrice[name] = $(this).val();
        });
    }

    function restorePrice()
    {
        $formCat.find('.j-item-price').find(':input').each(function(){
            var name = $(this).attr('name');
            if( ! name) return;
            if( ! memPrice.hasOwnProperty(name)) return;
            $(this).val(memPrice[name]);
        });
    }

    function fillTitle()
    {
        if ( ! autoTitle) return;
        bff.ajax('<?= $this->adminLink('ajax&act=item-auto-title') ?>', $form.serialize(), function(data){
            if(data && data.success) {
                <? if ($translate): ?>
                    for (var i in data.title) {
                        if ( ! data.title.hasOwnProperty(i)) continue;
                        $title.filter('.'+i).val(data.title[i]);
                    }
                <? else: ?>
                    $title.val(data.title);
                <? endif; ?>
            }
        }, $progress);
    }

    return {
        onTab: function(key, tabLink)
        {
            if(key == 'services') return;
            $('[id^="'+blocksPrefix+'"]').addClass('hidden');
            var $block = $('#'+blocksPrefix+key).removeClass('hidden');
            if(!$block.hasClass('inited')) {
                initBlock(key, $block);
            }
            $(tabLink).parent().addClass('tab-active').siblings().removeClass('tab-active');
        },
        onCategoryType: catType,
        onSubmit: function(btn, returnToList)
        {
            // check selected cats
            var catSelected = true;
            $form.find('select.cat-select:visible').each(function(){
                if( ! intval( $(this).val() ) ) {
                    bff.error('<?= _t('bbs', 'Выберите категорию'); ?>');
                    catSelected = false;
                }
            }); if( ! catSelected ) return false;

            if(id > 0) {
                // edit
                bff.ajax(url+'info', $form.serialize(), function(data){
                    if (data.success) {
                        if (returnToList === 1) {
                            history.back();
                        }
                        bff.success('<?= _t('bbs', 'Объявление успешно сохранено'); ?>');
                    }
                }, function(p){ $progress.toggle(); $(btn).button((p?'loading':'reset')); });
            } else {
                // add
                bff.ajax('<?= $this->adminLink('add') ?>', $form.serialize()+'&'+$('#item-form-images').serialize(), function(data){
                    if(data.success) {
                        id = intval(data.id);
                        $('#item-form-images').find('input.imgfn').remove();
                        bff.redirect('<?= $this->adminLink('listing&errno=1') ?>');
                    }
                }, function(p){ $progress.toggle(); $(btn).button((p?'loading':'reset')); });
            }
        },
        onMapSearch: function()
        {
            if( ! addr.inited ) { geoInitAddr(function(){}); return ;}
            var $country = $cityBlock.find('.j-geo-city-select-country');
            var country = ( $country.is('select') ? $country.find('option:selected').text() :
                            $country.val() );
            var q = [country];
            var q_city = $.trim( $cityBlock.find('.j-geo-city-select-ac').val() );
            if( q_city.length ) q.push( 'г. '+q_city );
            var q_addr = $.trim( addr.$addr.val() ); if( q_addr.length ) q.push( q_addr );
            q = q.join(', '); if( addr.lastQuery == q ) return;
            addr.mapEditor.search( ( addr.lastQuery = q ), 0 );
        },
        onCitySelect: function(cityID, cityTitle, ex)
        {
            cityID = intval(cityID);
            // metro
            if( ex.data === false || intval(ex.data[2]) === 0 ) {
                metro.$block.hide();
                metro.$sel.html('').val(0);
            } else {
                geoRefreshMetro(cityID);
            }
            // map
            if(ex.title.length > 0) {
                jItem.onMapSearch();
            }
            if ($districts.length) {
                if (distCache.hasOwnProperty(cityID)) {
                    $districts.html(distCache[cityID]);
                    $districts.closest('tr').toggleClass('displaynone', $districts.find('option').length <= 1);
                } else {
                    bff.ajax('<?= $this->adminLink('ajax&act=district-options', 'geo'); ?>', {city:cityID, empty:'<?= _t('', 'Не указан'); ?>'}, function(data, errors){
                        if (data && data.success) {
                            distCache[cityID] = data.html;
                            $districts.html(distCache[cityID]);
                            $districts.closest('tr').toggleClass('displaynone', $districts.find('option').length <= 1);
                        } else {
                            bff.error(errors);
                        }
                    });
                }
            }
            fillTitle();
        }
    };
}());
</script>