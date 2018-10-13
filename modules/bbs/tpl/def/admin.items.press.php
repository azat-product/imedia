<?php
    tpl::includeJS(array('datepicker'), true);
?>

<div class="tabsBar" id="items-status-tabs">
    <? foreach($tabs as $k=>$v) { ?>
        <span class="tab <? if($f['status']==$k) { ?>tab-active<? } ?>"><a href="javascript:void(0);" onclick="return jItemsPress.onStatus(<?= $k ?>, this);"><?= $v['t'] ?></a></span>
    <? } ?>
    <div class="right"><div class="progress" style="display:none;" id="progress-items"></div></div>
    <div class="clear"></div>
</div>

<form action="" method="get" name="filters" id="items-filters" onsubmit="return false;">
    <input type="hidden" name="page" value="<?= $f['page'] ?>" />
    <input type="hidden" name="order" value="<?= $f['order'] ?>" />
    <input type="hidden" name="status" value="<?= $f['status'] ?>" />
    <div class="actionBar<?= ($f['status'] != BBS::PRESS_STATUS_PAYED ? ' hidden' : '' ) ?>" id="press-actions">
        <div class="left">
            <?= _t('bbs', 'Отправить'); ?><select name="type" class="input-medium" id="press-type" style="margin:0 0 0 5px; width: auto;"><option value="1"><?= _t('bbs', 'отмеченные'); ?></option><option value="2"><?= _t('', 'все'); ?></option></select>
            <?= _t('bbs', 'на печать:'); ?> <input type="text" name="date" value="<?= date('d-m-Y') ?>" style="width: 70px;" id="items-press-date" />
            <input type="button" class="btn btn-mini btn-success button submit" onclick="jItemsPress.press('press');" value="<?= _te('bbs', 'отправить'); ?>" />
            <?= _t('bbs', '&nbsp;или&nbsp;экспортировать в файл:'); ?> <input type="button" class="btn btn-mini bold" id="j-export" onclick="jItemsPress.press('export');" value="XML" />
        </div>
        <div class="clear"></div>
    </div>
    <div class="actionBar<?= ($f['status'] == BBS::PRESS_STATUS_PAYED ? ' hidden' : '' ) ?>" id="pressed-actions">
        <div class="left">
            <?= _t('bbs', 'Дата печати:'); ?> <input type="text" name="pressed" value="<?= HTML::escape($f['pressed']) ?>" class="input-small" id="items-pressed-date" />
            &nbsp;<input type="button" class="btn btn-small button submit" onclick="jItemsPress.submit();" value="<?= _te('', 'найти'); ?>" />
        </div>
        <div class="clear"></div>
    </div>
</form>

<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <th width="20"><label class="checkbox inline"><input type="checkbox" onclick="jItemsPress.press('check-all',this);" /></label></th>
        <th width="30"><?= _t('', 'ID'); ?></th>
        <th class="left" style="padding-left: 18px;"><?= _t('bbs', 'Заголовок'); ?></th>
        <th width="140">
            <a href="javascript: jItemsPress.onOrder('svc_press_date');" class="ajax"><?= _t('bbs', 'Дата печати'); ?></a>
            <div class="order-<?= $f['order_dir'] ?>" <? if($f['order_by']!='svc_press_date') { ?>style="display:none;"<? } ?> id="items-order-press_date"></div>
        </th>
        <th width="110"><?= _t('', 'Action') ?></th>
    </tr>
</thead>
<tbody id="items-list">
    <?= $list ?>
</tbody>
</table>
<div id="items-pgn"><?= $pgn ?></div>

<script type="text/javascript">
var jItemsPress = (function()
{
    var $progress, $list, $listPgn, filters, $pressActions, $pressedActions;
    var url = '<?= $this->adminLink('listing_press'); ?>';
    var orders = <?= func::php2js($orders) ?>;
    var orderby = '<?= $f['order_by'] ?>';
    var status = <?= $f['status'] ?>;
    var _processing = false; 
    
    $(function(){
        $progress = $('#progress-items');
        $list     = $('#items-list');
        $listPgn  = $('#items-pgn');
        filters   = $('#items-filters').get(0);
        $pressActions = $('#press-actions');
        $pressedActions = $('#pressed-actions');

        bff.datepicker('#items-press-date', {yearRange: '-3:+1'});
        bff.datepicker('#items-pressed-date', {yearRange: '-3:+1'});
    });
    
    function isProcessing()
    {
        return _processing;
    }

    function updateList()
    {
        if(isProcessing()) return;
        var f = $(filters).serialize();
        bff.ajax(url, f, function(data){
            if(data && data.success) {
                $list.html(data.list);
                $listPgn.html(data.pgn);
                if(bff.h) {
                    window.history.pushState({}, document.title, url+'&'+f);
                }
            }
        }, function(p) { _processing = p; $progress.toggle(); $list.toggleClass('disabled'); });
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
            updateList();
        },
        page: function (id)
        {
            if(isProcessing()) return false;
            setPage(id);
            updateList();
        },
        onOrder: function(by)
        {
            if(isProcessing() || !orders[by])
                return;
                
            orders[by] = (orders[by] == 'asc' ? 'desc' : 'asc');
            //hide prev order direction
            $('#items-order-'+orderby).hide();
            //show current order direction
            orderby = by;
            $('#items-order-'+orderby).removeClass('order-asc order-desc').addClass('order-'+orders[by]).show();
                
            filters.order.value = orderby+'-'+orders[by];
            setPage(1);
            
            updateList();
        },
        onStatus: function(statusNew, link)
        {
            if(isProcessing() || status == statusNew) return false;
            status = statusNew;
            setPage(1);
            filters.status.value = statusNew;
            updateList();
            switch (intval(statusNew)){
                case <?= BBS::PRESS_STATUS_PAYED ?>:
                    $pressActions.removeClass('hidden');
                    $pressedActions.addClass('hidden');
                    break;
                default:
                    $pressActions.addClass('hidden');
                    $pressedActions.removeClass('hidden');
                    break;
            }
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            return false;
        },
        press: function(act, extra)
        {
            var $c = $('input.j-item-check:visible:checked:not(:disabled)');

            switch(act) {
                case 'check-all': {
                    var $all = $('input.j-item-check:visible:not(:disabled)');
                    if(!$all.length) return false;
                    if(!$c.length || $c.length < $all.length) {
                        $all.prop('checked', true); //доотмечаем неотмеченные
                        $(extra).prop('checked', true);
                    } else {
                        $all.prop('checked',false);//снимаем все
                        $(extra).prop('checked',false);
                    }
                    return false;
                } break;
            }

            var type = intval($(filters).find('#press-type').val());
            if( ! $c.length && type == 1) {
                bff.error('<?= _t('bbs', 'Нет отмеченных объявлений'); ?>');
                return false;
            }

            if(_processing) return false;
            _processing = true;
            switch(act) {
                case 'press': {
                    if(type != 1){
                        if(!bff.confirm('sure')) return false;
                    }
                    bff.ajax('<?= $this->adminLink('listing_press&act=press'); ?>', $(filters).serialize()+'&'+$c.serialize(), function(data) {
                        _processing = false;
                        if(data && data.success) {
                            $c.prop('disabled',true);
                            bff.success('<?= _t('bbs', 'Успешно отправлено на печать:'); ?> '+data.updated);
                            setTimeout(function(){
                                setPage(1);
                                updateList();
                            }, 1000);
                        }
                    }, $progress);
                } break;
                case 'export': {
                    var params = $(filters).serialize()+'&'+$c.serialize();
                    bff.ajax('<?= $this->adminLink('listing_press&act=export-check'); ?>', params, function(data) {
                        _processing = false;
                        if(data && data.success) {
                            bff.redirect('<?= $this->adminLink('listing_press&act=export'); ?>&'+params);
                        }
                    }, $progress);
                } break;
            }

        }
    };
}());
</script>