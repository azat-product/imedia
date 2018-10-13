<?php
    /**
     * @var $this BBS
     */
    tpl::includeJS(array('datepicker'), true);

    $aTabs = bff::filter('bbs.admin.item.list.tabs', array(
        0 => array('t'=>_t('bbs', 'Опубликованные')),
        2 => array('t'=>_t('bbs', 'Снятые с публикации')),
        3 => array('t'=>_t('bbs', 'На модерации'), 'counter'=>$this->moderationCounterUpdate(false),'c'=>' class="j-mod-counter" '),
        4 => array('t'=>_t('bbs', 'Неактивированные'),'c'=>' class="disabled" '),
        5 => array('t'=>_t('bbs', 'Заблокированные')),
        6 => array('t'=>_t('bbs', 'Удаленные')),
        7 => array('t'=>_t('', 'Все')),
    ));

    tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>_t('bbs', '+ добавить объявление'), 'href'=>$this->adminLink('add')),
        'fordev'=>array(
            'links-rebuild' => array('title'=>_t('bbs', 'обновление ссылок объявлений'), 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('listing&act=dev-items-links-rebuild')."'})", 'icon'=>'icon-check'),
            'publicate-all-unpublicated' => array('title'=>_t('bbs', 'опубликовать все снятые с публикации'), 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('listing&act=dev-items-publicate-all-unpublicated')."'})", 'icon'=>'icon-arrow-up'),
            //'move-to-categories' => array('title'=>_t('bbs', 'переместить объявления в подкатегории'), 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('listing&act=dev-items-cats-rebuild')."'})", 'icon'=>'icon-leaf', 'debug-only'=>true),
            'default-currency' => array('title'=>_t('bbs', 'конвертировать цены в валюту по-умолчанию'), 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('listing&act=dev-items-default-currency')."'})", 'icon'=>'icon-share-alt'),
        ),
    ));
?>

<div class="tabsBar" id="items-status-tabs">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$f['status'] ? ' tab-active' : '' ?>"><a href="javascript:void(0);" onclick="return jItems.onStatus(<?= $k ?>, this);"<?= (!empty($v['c']) ? $v['c'] : '') ?>><?= $v['t'] ?><? if(! empty($v['counter'])){ ?> (<span class="j-counter"><?= $v['counter'] ?></span>)<? } ?></a></span>
    <? } ?>
    <span class="progress pull-right" style="display:none; position: absolute; right: 10px; top: 20px;" id="progress-items"></span>
</div>

<div class="actionBar">
    <form action="" method="get" name="filters" id="items-filters" class="form-inline" onsubmit="return false;">
        <input type="hidden" name="page" value="<?= $f['page'] ?>" />
        <input type="hidden" name="status" value="<?= $f['status'] ?>" />
        <div class="controls controls-row">
            <div class="left">
                <select name="cat" onchange="jItems.onCategory(intval(this.value), $(this));" style="width: 140px;"><?= $cats_select; ?></select>
                <input type="text" maxlength="150" name="title" id="items-title-or-id" placeholder="<?= _te('bbs', 'ID / Заголовок / Телефон'); ?>" value="<?= HTML::escape($f['title']) ?>" style="width: 125px;" />
                <input type="text" maxlength="130" name="uid" id="items-uid" placeholder="<?= _te('bbs', 'ID / E-mail пользователя'); ?>" value="<?= HTML::escape($f['uid']) ?>" style="width: 125px;" />
                <? if($shops_on) { ?><input type="text" maxlength="20" name="shopid" id="items-shop-id" placeholder="<?= _te('bbs', 'ID магазина'); ?>" value="<?= HTML::escape($f['shopid']) ?>" style="width: 75px;" /><? } ?>
                <select name="moderate_list" class="input-small"<? if($f['status']!==3){ ?> style="display:none"<? } ?> id="items-moderate-list"><?= HTML::selectOptions(array(0 => 'все', 1 => _t('bbs', 'отредактированные'), 2 => _t('bbs', 'импортированные')), $f['moderate_list']) ?></select>
            </div>
            <? if ( ! Geo::coveringType(Geo::COVERING_CITY)): ?>
                <div class="left" style="margin-left:4px;">
                    <?= Geo::i()->regionSelect($f['region'], 'region', array(
                        'on_change'=>'jItems.onRegionSelect', 'placeholder' => Geo::coveringType(Geo::COVERING_COUNTRIES) ? _t('', 'Страна / Регион') : _t('', 'Регион'), 'width' => '110px',
                    )); ?>
                </div>
            <? endif; ?>
            <div class="left" style="margin-left: 4px;">
                <div class="btn-group">
                <input type="submit" class="btn btn-small" onclick="jItems.submit(false);" value="<?= _te('', 'найти'); ?>" />
                <a class="btn btn-small" onclick="jItems.submit(true); return false;" title="<?= _te('', 'сбросить'); ?>"><i class="disabled icon icon-refresh"></i></a>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </form>
    <div id="j-massModerateInfo" class="well well-small hide">
        <span class="j-info"></span>&nbsp;
        <input type="submit" class="btn btn-mini btn-success success button" onclick="jItems.massModerate();" value="<?= _te('bbs', 'одобрить выбранные'); ?>" />
        <input type="button" class="btn btn-mini cancel button" onclick="jItems.massModerate('uncheck-all');" value="<?= _te('', 'отмена'); ?>" />
    </div>
</div>
<table class="table table-condensed table-hover admtbl">
<thead>
    <tr>
        <th width="20"><label class="checkbox inline"><input type="checkbox" id="j-check-all" onclick="jItems.massModerate('check-all',this);" /></label></th>
        <th width="30"><?= _t('', 'ID'); ?></th>
        <th class="left" style="padding-left: 10px;"><?= _t('', 'Заголовок'); ?></th>
        <th width="30"></th>
        <th width="130"><?= _t('', 'Created'); ?></th>
        <th width="135"><?= _t('', 'Action') ?></th>
    </tr>
</thead>
<tbody id="items-list">
<?= $list ?>
</tbody>
</table>
<div id="items-pgn"><?= $pgn; ?></div>

<script type="text/javascript">
var jItems = (function()
{
    var $progress, $list, $listPgn, filters, $moderateList, $tabs;
    var url = '<?= $this->adminLink('listing',null,'js'); ?>';
    var status = intval(<?= $f['status'] ?>), statusMod = 3;
    var cat = intval(<?= $f['cat'] ?>);
    var $checkAll, $checkAllTh, $moderate, $moderateInfo;
    var _processing = false; 
    
    $(function(){
        $progress = $('#progress-items');
        $list     = $('#items-list');
        $checkAll = $('#j-check-all');
        $checkAllTh = $checkAll.parents('th:eq(0)');
        $moderate = $('#j-massModerateInfo');
        $moderateInfo = $moderate.find('.j-info');
        $listPgn  = $('#items-pgn');
        filters   = $('#items-filters').get(0);
        $moderateList = $('#items-moderate-list');
        $tabs     = $('#items-status-tabs');

        $list.on('click', 'a.item-del', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) del( id, this );
            return false;
        });
        $list.on('click', '.j-item-import-info', function(){
            var importID = intval($(this).data('import-id'));
            if (importID > 0) {
                $.fancybox('', {ajax: true, href: '<?= $this->adminLink('ajax&act=import-info&id=',null,'js') ?>' + importID});
            }
        });
        $moderateList.on('change',function(){
            updateList();
        });
        
        bff.datepicker('#items-period-from', {yearRange: '-3:+3'});
        bff.datepicker('#items-period-to', {yearRange: '-3:+3'});

        massModerateActions();

        setInterval(function(){
            if (status === statusMod) {
                if (!$('input.j-item-check:visible:checked').length) {
                    updateList();
                }
            } else {
                updateModerationCounter(false);
            }
        }, 10000);
    });
    
    function isProcessing()
    {
        return _processing;
    }
    
    function del(id, link)
    {
        bff.ajaxDelete('<?= _t('bbs', 'Удалить объявление?'); ?>', id, url+'&act=delete&id='+id,
            link, {progress: $progress, repaint: false, onComplete: function(){
                updateModerationCounter(false);
            }});
        return false;
    } 

    function updateList()
    {
        if(isProcessing()) return;
        _processing = true;
        $list.addClass('disabled');
        var f = $(filters).serialize();
        bff.ajax(url, f, function(data){
            if(data) {
                $list.html( data.list );
                $listPgn.html( data.pgn );
                if (data.hasOwnProperty('mod_counter')) {
                    updateModerationCounter(data.mod_counter);
                }
                if(bff.h) {
                    window.history.pushState({}, document.title, url + '&' + f);
                }
            }
            $list.removeClass('disabled');
            
            massModerateActions();
            
            _processing = false;
        }, $progress);
    }
    
    function massModerateActions()
    {
        $checkAllTh.toggle(status === statusMod);
    }

    function updateModerationCounter(counter)
    {
        if (counter!==false) {
            $tabs.find('.j-mod-counter .j-counter').html(counter);
            $('#j-bbs-listing-counter').html(counter);
        } else {
            bff.ajax(url+'&act=moderation-counter', {}, function(data) {
                if (data && data.success && data.hasOwnProperty('mod_counter')) {
                    updateModerationCounter(data.mod_counter);
                }
            });
        }
    }
    
    function setPage(id)
    {
        filters.page.value = intval(id);
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return false;
            setPage(1);
            if(resetForm) {
                filters.cat.value = 0;
                filters.title.value = '';
                filters.uid.value = '';
                filters.shopid.value = '';
                $(filters).find('.j-geo-region-select-id').val(0);
                $(filters).find('.j-geo-region-select-ac').val('');
            }
            updateList();
            return true;
        },
        refresh: function()
        {
            updateList();
        },
        page: function (id)
        {
            if(isProcessing()) return false;
            setPage(id);
            updateList();
            return true;
        },
        onStatus: function(statusNew, link)
        {
            if(isProcessing() || status == statusNew) return false;
            status = statusNew;
            setPage(1);
            filters.status.value = statusNew;
            updateList();
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            $moderateList.toggle(status === statusMod);
            $moderate.hide();
            return false;
        },
        onCategory: function(catNew, $catSelect)
        {
            if(isProcessing() || cat == catNew) return false;
            cat = catNew;
            setPage(1);
            filters.cat.value = catNew;
            updateList();
            return false;
        },
        massModerate: function(act, extra)
        {
            var $c = $('input.j-item-check:visible:checked:not(:disabled)');
            var $all = $('input.j-item-check:visible:not(:disabled)');
            var $allCheck = $('#j-check-all');

            switch(act) {
                case 'check-all': {
                    if(!$all.length) return false;
                    if(!$c.length || $c.length < $all.length) {
                        $all.prop('checked', true); // доотмечаем неотмеченные
                        $(extra).prop('checked', true);
                        $moderateInfo.html('<?= HTML::escape(_t('bbs', 'Выбрано'),'js'); ?> <strong>' + $all.length + '</strong> ' + bff.declension($all.length,['<?= _t('bbs', 'объявление'); ?>','<?= _t('bbs', 'объявления'); ?>','<?= _t('bbs', 'объявлений'); ?>'], false));
                        $moderate.show();
                    } else {
                        $all.prop('checked',false);
                        $(extra).prop('checked',false);
                        $moderate.hide();
                    }
                    return false;
                } break;
                case 'check': {
                    if(!$c.length || $c.length <= 0) {
                        $moderate.hide();
                    } else {
                        $moderateInfo.html('<?= HTML::escape(_t('bbs', 'Выбрано'),'js'); ?> <strong>' + $c.length + '</strong> ' + bff.declension($c.length,['<?= _t('bbs', 'объявление'); ?>','<?= _t('bbs', 'объявления'); ?>','<?= _t('bbs', 'объявлений'); ?>'], false));
                        $moderate.show();
                    }
                    
                    if(!$c.length || $c.length < $all.length) $checkAll.prop('checked',false);
                    else $checkAll.prop('checked',true);
                    
                    return false;
                } break;
                case 'uncheck-all': {
                    $allCheck.removeProp('checked');
                    $all.prop('checked',false);
                    $(extra).prop('checked',false);
                    $moderate.hide();
                    return false;
                } break;
            }

            if(intval($c.length)<=0){ bff.error('<?= HTML::escape(_t('bbs', 'Нет отмеченных объявлений'),'js'); ?>'); return; }
            if(!bff.confirm('sure')) return;

            if(_processing) return false;
            _processing = true;
            bff.ajax('<?= $this->adminLink('ajax',null,'js'); ?>'+'&act=items-approve', $c.serialize(), function(resp){
                _processing = false;
                if(resp && resp.success) {
                    bff.success('<?= HTML::escape(_t('bbs', 'Успешно одобрено:'),'js'); ?> '+resp.updated);
                    jItems.massModerate('uncheck-all');
                    updateList();
                }
            }, $progress);
        },
        onRegionSelect: function(cityID, cityTitle, ex)
        {
            setPage(1);
            updateList();
        }
    };
}());
</script>