<?php

    tpl::includeJS(array('wysiwyg','autocomplete','ui.sortable'), true);
    $aData = HTML::escape($aData, 'html', array('title','keyword'));
    $saveUrl = $this->adminLink('svc_packs&act=update');
    $nActiveTab = $this->input->get('tab',TYPE_UINT);
    $pricePrefix = '&nbsp;<span class="desc">'.Site::currencyDefault().'</span>';
    $bSelectCountry = Geo::coveringType(Geo::COVERING_COUNTRIES);

    tplAdmin::adminPageSettings(array('link'=>array('title'=>_t('bbs', '+ добавить пакет'), 'href'=>$this->adminLink('svc_packs_create')), 'icon'=>false));
?>
<? if ( ! bff::servicesEnabled(true)) { ?>
    <div class="alert alert-info" style="margin-bottom: 10px;">
        <?= _t('svc', 'Доступность платных услуг отключена в <a [setting_link]>системных настройках</a> и не отображается пользователям сайта.', array('setting_link'=>'href="'.Site::settingsSystemLink('site','services.enabled').'"')); ?>
    </div>
<? } ?>
<div class="tabsBar">
    <form id="j-bbs-svc-packs-tabs" action="">
    <? foreach($packs as $v) { $packID = $v['id']; if(empty($nActiveTab)) $nActiveTab = $packID; ?>
        <div class="left">
            <span style="margin: 0 2px;" class="tab<? if($nActiveTab == $packID){ ?> tab-active<? } ?>" data-id="<?= $packID ?>" onclick="return jSvcServicepacks.onTab(this);"><?= $v['title'] ?></span>
            <input type="hidden" name="svc[<?= $packID ?>]" value="<?= $packID ?>" />
        </div>
    <? } ?>
    </form>
    <div class="progress right" style="display:none;" id="j-bbs-svc-packs-progress"></div>
    <div class="clear"></div>
</div>
<script type="text/javascript">
    var jSvcServicepacks = (function(){
        var urlAjax = '<?= $this->adminLink('svc_packs&act='); ?>';
        var $tabs, $progress, priceEx = {};
            <? if($bSelectCountry): ?>var countries = <?= func::php2js(Geo::countriesList()) ?>;<? endif; ?>

        $(function(){
            $tabs = $('#j-bbs-svc-packs-tabs-content > div');
            $progress = $('#j-bbs-svc-packs-progress');

            var $packsOrder = $('#j-bbs-svc-packs-tabs').sortable({
                update: function( event, ui ) {
                    bff.ajax(urlAjax+'reorder', $packsOrder.serialize(), function(data,errors) {
                        if(data && data.success) {
                            bff.success('<?= _t('bbs', 'Порядок пакетов услуг был успешно изменен'); ?>');
                        }
                    }, $progress);
                }
            });
            $packsOrder.sortable('refresh');

            $tabs.on('click', '.j-price-ex-plus', function(e){ nothing(e);
                var svc = $(this).data('svc');
                if( ! priceEx.hasOwnProperty(svc) ) {
                    priceEx[svc] = priceExInit(svc);
                }
                priceEx[svc].plus();
            });

            $('.j-svc-pack-form').each(function(){
                var $form = $(this);
                bff.iframeSubmit($form, function(data){
                    if(data && data.success) {
                        bff.success('<?= _t('', 'Настройки успешно сохранены'); ?>');
                        setTimeout(function(){ location.reload(); }, 1000);
                    }
                });
            });
        });

        function priceExInit(key)
        {
            var block_class = 'j-price-ex-block';
            var $cats = $('#j-price-ex-cats');
            var $block = $tabs.find('#j-price-ex-block-'+key);
            var iterator = intval($block.find('.'+block_class).length) + 1;

            $block.on('click', '.j-del', function(e){ nothing(e);
                var $i = $(this).closest('.'+block_class);
                $i.remove();
            });

            function add(i, data)
            {
                data = $.extend({price:1,cats:[],regions:[]}, data||{});
                var namePrefix = 'price_ex['+i+']';
                $block.append('<div class="well well-small relative '+block_class+' '+block_class+'-'+i+'" style="margin-bottom:5px;">'+
                    '<table class="admtbl tbledit">'+
                    '<tr><td width="73"><?= _t('bbs', 'Стоимость'); ?><span class="required-mark">*</span>:</td><td style="padding-bottom: 3px;"><input type="text" name="'+namePrefix+'[price]" value="'+data.price+'" class="input-mini" pattern="[0-9\.,]*" /><?= $pricePrefix ?></td></tr>'+
                    '<tr><td class="row1"><?= _t('bbs', 'Категории'); ?><span class="required-mark">*</span>:</td><td class="row2" style="padding-bottom: 3px;">'+
                    '<select class="j-cat-select" style="margin-bottom: 3px;"></select>'+
                    '<div class="j-cats-selected hide"></div></td></tr>'+
                    '<tr><td class="row1"><?= _t('', 'Регионы:'); ?></td><td class="row2">'+
                    '<input type="hidden" class="j-region-id" />'+
                    '<div class="relative" style="margin-bottom: 3px;"><input type="text" class="j-region autocomplete" placeholder="<?= _te('', 'Введите название региона'); ?>" /></div>'+
                    '<div class="j-regions-selected hide"></div></td></tr>'+
                    '</table>'+
                    '<button type="button" class="close j-del" style="position:absolute; right:5px; top:0px;">&times;</button>'+
                    '</div>');
                var $i = $block.find('.'+block_class+'-'+i);
                // -------------------------------
                // cats
                var $catsSelected = $i.find('.j-cats-selected');
                $catsSelected.on('click', '.j-cat-del', function(e){ nothing(e);
                    $(this).parent().remove();
                    if( ! $catsSelected.find('.j-selected').length ) {
                        $catsSelected.addClass('hide');
                    }
                });
                $i.find('.j-cat-select').html($cats.html()).on('change', function(){
                    var $sel = $(this);
                    var $opt = $sel.find('option:selected');
                    setTimeout(function(){
                        addCat( intval($opt.attr('value')) );
                    },1);
                    $opt.prop('selected', false);
                });
                function addCat(id)
                {
                    if( id > 0 && ! $catsSelected.find('.j-selected-id[value="'+id+'"]').length )
                    {
                        var $option = $cats.find('option[value="'+id+'"]');
                        var title = $option.text().trim();
                        var $optionParent = $cats.find('option[value="'+$option.data('pid')+'"]');
                        if( $optionParent.length ) {
                            title = $optionParent.text().trim() + ' / ' + title;
                        }
                        $catsSelected.append(
                            '<span class="label j-selected" style="margin:0 2px 2px 2px;">'+title+'<a href="javascript:void(0);" class="j-cat-del" style="margin-left: 3px;"><i class="icon-remove icon-white" style="margin-top: 0px;"></i></a><input type="hidden" name="'+namePrefix+'[cats][]" class="j-selected-id" value="'+id+'" /></span>'
                        ).removeClass('hide');
                    }
                }
                if(data.cats) {
                    for(var c in data.cats) {
                        addCat(intval(data.cats[c]));
                    }
                }
                // -------------------------------
                // regions
                var $regionsSelected = $i.find('.j-regions-selected');
                $regionsSelected.on('click', '.j-region-del', function(e){ nothing(e);
                    $(this).parent().remove();
                    if( ! $regionsSelected.find('.j-selected').length ) {
                        $regionsSelected.addClass('hide');
                    }
                });
                var ac = $.autocomplete($i.find('.j-region'), '<?= $this->adminLink('regionSuggest', 'geo') ?>',
                    {valueInput: $i.find('.j-region-id') <?= ! $bSelectCountry ? ', suggest: '.Geo::regionPreSuggest() : '' ?>, params:{reg:1<?= $bSelectCountry ? ', country:1' : ''?>},
                        onSelect: function(regionID, regionTitle, ex){
                            if( ! ex.changed ) return;
                            <? if($bSelectCountry): ?>
                            var c = intval(ex.data[4]);
                            if(c && countries.hasOwnProperty(c)){
                                regionTitle = countries[c].title + ' / ' + regionTitle;
                            }
                            <? endif; ?>
                            addRegion(regionID, regionTitle);
                            ac.reset();
                        }});
                function addRegion(id, title)
                {
                    if( id > 0 && ! $regionsSelected.find('.j-selected-id[value="'+id+'"]').length ) {
                        $regionsSelected.append(
                            '<span class="label j-selected" style="margin:0 2px 2px 2px;">'+title+'<a href="javascript:void(0);" class="j-region-del" style="margin-left: 3px;"><i class="icon-remove icon-white" style="margin-top: 0px;"></i></a><input type="hidden" name="'+namePrefix+'[regions][]" class="j-selected-id" value="'+id+'" /></span>'
                        ).removeClass('hide');
                    }
                }
                if(data.regions) {
                    for(var r in data.regions) {
                        addRegion(data.regions[r]['id'], data.regions[r]['t']);
                    }
                }
            }

            return {
                plus: function(data){
                    add(iterator++, data);
                }
            };
        }

        return {
            onTab: function(link)
            {
                var packID = $(link).data('id');
                $tabs.addClass('hidden');
                $tabs.filter('#j-bbs-svc-packs-'+packID).removeClass('hidden');
                $(link).addClass('tab-active').parent().siblings().find('.tab').removeClass('tab-active');
                if( bff.h ) {
                    window.history.pushState({}, document.title, '<?= $this->adminLink('svc_packs&tab=') ?>'+packID);
                }
                return false;
            },
            priceExEdit: function(svc, data)
            {
                if( ! priceEx.hasOwnProperty(svc) ) {
                    priceEx[svc] = priceExInit(svc);
                }
                data = data || {};
                for(var j in data) {
                    priceEx[svc].plus(data[j]);
                }
            },
            del: function(packID)
            {
                if(!bff.confirm('sure')) return;
                bff.ajax(urlAjax+'del', {id:packID}, function(data){
                    if(data && data.success){
                        bff.error('<?= _t('bbs', 'Пакет услуг успешно удалён'); ?>', {success: true});
                        setTimeout(function(){
                            bff.redirect(data.redirect);
                        }, 1000);
                    }
                }, $progress);
            },
            onSvc: function(check)
            {
                var block = $(check).parents('.svc-line:first');
                var cnt = block.find('.j-cnt');
                if( $(check).is(':checked') ) {
                    cnt.focus();
                } else {
                    cnt.val('');
                }
            },
            iconDelete: function(link){
                var $block = $(link).parent();
                $block.hide().find('input.del-icon').val(1);
                $block.prev().show();
                return false;
            }
        };
    }());
</script>

<div id="j-bbs-svc-packs-tabs-content">
    <?
    foreach($packs as $k=>$v):
        $packID = $v['id'];
    ?>
    <div id="j-bbs-svc-packs-<?= $packID ?>"<? if($nActiveTab != $packID){ ?> class="hidden"<? } ?>>
        <form action="<?= $saveUrl ?>" class="j-svc-pack-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $packID ?>" />
        <table class="admtbl tbledit">
            <?= $this->locale->buildForm($v, 'bbs-svc-packs-'.$packID,'
            <tr>
                <td class="row1"><span class="field-title">'._t('bbs', 'Название').'</span>:</td>
                <td class="row2">
                    <input type="text" name="title_view[<?= $key ?>]" value="<?= ( isset($aData[\'title_view\'][$key]) ? HTML::escape($aData[\'title_view\'][$key]) : \'\') ?>" class="stretch lang-field" />
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title">'._t('bbs', 'Описание<br />(краткое)').'</span>:</td>
                <td class="row2">
                    <textarea name="description[<?= $key ?>]" class="lang-field" rows="4"><?= (isset($aData[\'description\'][$key]) ? $aData[\'description\'][$key] : \'\'); ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title">'._t('bbs', 'Описание<br />(подробное)').'</span>:</td>
                <td class="row2">
                    <?= tpl::jwysiwyg((isset($aData[\'description_full\'][$key]) ? $aData[\'description_full\'][$key] : \'\'), \'description_full-\'.$key.$aData[\'id\'].\',description_full[\'.$key.\']\', 0, 130); ?>
                </td>
            </tr>
            '); ?>
            <tr>
                <td class="row1" width="130"><span class="field-title"><?= _t('bbs', 'Стоимость'); ?></span>:</td>
                <td class="row2">
                    <input type="text" name="price" value="<?= $v['price'] ?>" style="width: 60px;" pattern="[0-9\.,]*" />&nbsp;<span class="desc"><?= $curr['title_short'] ?></span>
                    <a href="javascript:void(0);" class="btn btn-mini j-price-ex-plus" data-svc="<?= $v['keyword'] ?>" style="margin-left: 5px;"><?= _t('bbs', 'добавить региональную стоимость'); ?></a>
                    <div id="j-price-ex-block-<?= $v['keyword'] ?>" style="margin: 5px 0;"></div>
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title"><?= _t('bbs', 'Услуги входящие<br/>в пакет'); ?></span>:</td>
                <td class="row2">
                    <table class="admtbl tbledit svc-block table-hover" style="width:420px;">
                    <? foreach($svc as $sk=>$sv)
                    {   if ($sv['id'] == BBS::SERVICE_LIMIT) continue;
                        $checked = !empty( $v['svc'][$sk] );
                        $enabled = ! empty( $sv['on'] );
                        if( $enabled ) {
                            if( $sv['id'] == BBS::SERVICE_PRESS && ! BBS::PRESS_ON ) {
                                $enabled = false;
                            }
                        }
                    ?>
                    <tr class="svc-line" data-svc="<?= $sk ?>"<? if( ! $enabled) { ?> style="display: none;" <? } ?>>
                        <td><label class="checkbox"><input type="checkbox" class="j-chk" <? if($checked){ ?> checked="checked" <? } ?> name="svc[<?= $sk ?>][id]" onclick="jSvcServicepacks.onSvc(this);" value="<?= $sv['id'] ?>" /><?= $sv['title'] ?></label></td>
                        <td>
                            <? switch($sv['id'])
                              {
                                case BBS::SERVICE_UP: { ?>
                                    <input type="text" class="j-cnt" name="svc[<?= $sk ?>][cnt]" value="<?= ($checked ? $v['svc'][$sk]['cnt'] : '')  ?>" style="width:50px;" maxlength="3" /><span class="desc">&nbsp;<?= _t('bbs', '- количество поднятий'); ?></span>
                                <? } break;
                                case BBS::SERVICE_MARK:
                                case BBS::SERVICE_FIX:
                                case BBS::SERVICE_PREMIUM:
                                case BBS::SERVICE_QUICK:
                                { ?>
                                    <input type="text" class="j-cnt" name="svc[<?= $sk ?>][cnt]" value="<?= ($checked ? $v['svc'][$sk]['cnt'] : '')  ?>" style="width:50px;" maxlength="3" /><span class="desc">&nbsp;<?= _t('bbs', '- количество дней'); ?></span>
                                <? } break;
                                case BBS::SERVICE_PRESS: { ?>
                                    <span class="desc"><?= _t('bbs', 'единоразово'); ?></span>
                                <? } break;
                              } ?>
                        </td>
                    </tr>
                    <? } ?>
                    </table>
                </td>
            </tr>
            <?  $oIcon = BBS::svcIcon($packID);
                foreach($oIcon->getVariants() as $iconField=>$icon) {
                    $oIcon->setVariant($iconField);
                    $icon['uploaded'] = ! empty($v[$iconField]);
                ?>
                <tr>
                    <td class="row1">
                        <span class="field-title"><?= $icon['title'] ?></span>:<? if(sizeof($icon['sizes']) == 1) { $sz = current($icon['sizes']); ?><br /><span class="desc"><?= ($sz['width'].'x'.$sz['height']) ?></span><? } ?>
                    </td>
                    <td class="row2">
                        <input type="file" name="<?= $iconField ?>" <? if($icon['uploaded']){ ?>style="display:none;" <? } ?> />
                        <? if($icon['uploaded']) { ?>
                            <div style="margin:5px 0;">
                                <input type="hidden" name="<?= $iconField ?>_del" class="del-icon" value="0" />
                                <img src="<?= $oIcon->url($packID, $v[$iconField], $icon['key']) ?>" alt="" /><br />
                                <a href="javascript:void(0);" class="ajax desc cross but-text" onclick="return jSvcServicepacks.iconDelete(this);"><?= _t('', 'Delete'); ?></a>
                            </div>
                        <? } ?>
                    </td>
                </tr>
                <? }
            ?>
            <tr>
                <td class="row1"><span class="field-title"><?= _t('bbs', 'Цвет'); ?></span>:</td>
                <td class="row2">
                    <input type="text" name="color" value="<?= $v['color'] ?>" class="input-mini" />
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title"><?= _t('bbs', 'В форме добавления'); ?></span>:</td>
                <td class="row2">
                    <input type="checkbox" name="add_form" <? if($v['add_form']){ ?>checked="checked"<? } ?> />
                </td>
            </tr>
            <? bff::hook('bbs.admin.svc-pack.form',array('id'=>$packID,'data'=>&$v)); ?>
            <tr>
                <td class="row1"><span class="field-title"><?= _t('bbs', 'Включен'); ?></span>:</td>
                <td class="row2">
                    <input type="checkbox" name="on" <? if($v['on']) { ?>checked="checked" <? } ?> />
                </td>
            </tr>
            <tr class="footer">
                <td colspan="2">
                    <div class="left">
                        <input type="submit" class="btn btn-success button submit" value="<?= _te('', 'Save') ?>" />
                        <input type="button" class="btn btn-danger button delete" value="<?= _te('', 'Delete') ?>" onclick="jSvcServicepacks.del(<?= $packID ?>);" />
                    </div>
                    <div class="right desc">
                        <?= _t('bbs', 'последние изменения:'); ?> <span class="j-last-modified"><?= tpl::date_format2($v['modified'], true); ?>, <a class="bold desc ajax" href="javascript:void(0);" onclick="return bff.userinfo(<?= $v['modified_uid'] ?>);"><?= $v['modified_login'] ?></a></span>
                    </div>
                    <div class="clear"></div>
                </td>
            </tr>
        </table>
        </form>
    </div>
    <? if( isset($price_ex[$packID]) ): ?>
        <script type="text/javascript">
            $(function(){
                jSvcServicepacks.priceExEdit('<?= HTML::escape($k, 'js') ?>', <?= func::php2js($price_ex[$packID]) ?>);
            });
        </script>
    <? endif; ?>
    <? endforeach; ?>
</div>
<div>
    <select id="j-price-ex-cats" class="hidden"><?= $cats ?></select>
</div>
<?
