<?php 
    $f = HTML::escape($f, 'html', array('page','status','order','q'));
    tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>_t('shops', '+ добавить магазин'), 'href'=>$this->adminLink('add')),
    ));
    $aTabs = bff::filter('shops.admin.shop.list.tabs', array(
        0 => array('t'=>_t('shops', 'Активные')),
        1 => array('t'=>_t('shops', 'Неактивные')),
        2 => array('t'=>_t('shops', 'На модерации'), 'counter'=>config::get('shops_moderating', 0, TYPE_UINT),),
        3 => array('t'=>_t('shops', 'Заблокированные')),
        4 => array('t'=>_t('', 'Все')),
    ));
?>

<div class="tabsBar" id="shops-status-tabs">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$f['status'] ? ' tab-active' : '' ?>"><a href="javascript:void(0);" onclick="return jShops.onStatus(<?= $k ?>, this);"<?= (!empty($v['c']) ? $v['c'] : '') ?>><?= $v['t'] ?><? if(! empty($v['counter'])){ ?> (<span class="j-counter"><?= $v['counter'] ?></span>)<? } ?></a></span>
    <? } ?>
</div>

<div class="actionBar">
    <form action="" method="get" name="filters" id="shops-filters" class="form-inline" onsubmit="return false;">
        <input type="hidden" name="page" value="<?= $f['page'] ?>" />
        <input type="hidden" name="order" value="<?= $f['order'] ?>" />
        <input type="hidden" name="status" value="<?= $f['status'] ?>" />
        <div class="controls controls-row">
            <? if (Shops::categoriesEnabled()) { ?>
                <select name="owner" onchange="jShops.submit(false);" style="width: 130px;"><?= HTML::selectOptions(
                    array(0=>_t('', 'Все'),1=>_t('shops', 'C владельцем'),2=>_t('shops', 'Без владельца')), $f['owner']
                ) ?></select>
            <? } ?>
            <select name="cat" onchange="jShops.submit(false);" style="width: 170px;"><?= $cats ?></select>
            <input type="text" maxlength="150" name="q" placeholder="<?= _te('shops', 'ID / Название магазина'); ?>" value="<?= $f['q'] ?>" style="width: 150px;" />
            <input type="text" maxlength="150" name="u" placeholder="<?= _te('shops', 'ID / E-mail пользователя'); ?>" value="<?= $f['u'] ?>" style="width: 155px;" />
            <input type="submit" class="btn btn-small button cancel" onclick="jShops.submit(false);" value="<?= _te('', 'найти'); ?>" style="margin-top: -1px;" />
            <a class="ajax cancel" onclick="jShops.submit(true); return false;"><?= _t('', 'сбросить'); ?></a>
        </div>
    </form>
</div>

<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <th width="40"><?= _t('', 'ID'); ?></th>
        <th class="left" style="padding-left: 18px;"><?= _t('shops', 'Название'); ?></th>
        <th width="30"></th>
        <th width="135">
            <a href="javascript: jShops.onOrder('created');" class="ajax"><?= _t('', 'Created'); ?></a>
            <div class="order-<?= $f['order_dir'] ?>" <? if($f['order_by']!='created') { ?>style="display:none;"<? } ?> id="shops-order-created"></div>
        </th>
        <th width="110"><?= _t('', 'Action') ?></th>
    </tr>
</thead>
<tbody id="shops-list">
    <?= $list; ?>
</tbody>
</table>
<div id="shops-pgn"><?= $pgn; ?></div>

<script type="text/javascript">
var jShops = (function()
{
    var $progress, $list, $listPgn, filters;
    var url = '<?= $this->adminLink(bff::$event); ?>';
    var orders = <?= func::php2js($orders) ?>;
    var orderby = '<?= $f['order_by'] ?>';
    var status = intval(<?= $f['status'] ?>);
    var _processing = false;
    
    $(function(){
        $progress = $('#progress-shops');
        $list     = $('#shops-list');
        $listPgn  = $('#shops-pgn');
        filters   = $('#shops-filters').get(0);

        $list.on('click', '.j-act-del', function(e){ nothing(e);
            if( ! bff.confirm('sure')) return;
            bff.ajax('<?= $this->adminLink('ajax&act=shop-delete') ?>', {id:$(this).data('id')}, function(data){
                if(data && data.success) {
                    bff.success('<?= _t('shops', 'Магазин был успешно удален'); ?>');
                    setTimeout(function(){ location.reload(); }, 1000);
                }
            });
        });
        $list.on('click', '.j-act-info-popup', function(e){ nothing(e);
            bff.shopInfo($(this).data('id'));
        });
    });
    
    function isProcessing()
    {
        return _processing;
    }

    function updateList()
    {
        if(isProcessing()) return;
        bff.ajax(url, $(filters).serializeArray(), function(data){
            if(data) {
                $list.html( data.list );
                $listPgn.html( data.pgn );
                var f = $(filters).serialize();
                if(bff.h) {
                    window.history.pushState({}, document.title, url + '&' + f);
                }
            }
        }, function(p){
            _processing = p;
            $progress.toggle();
            $list.toggleClass('disabled');
        });
    }
    
    function setPage(id)
    {
        filters.page.value = intval(id);
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return;
            setPage(1);
            if(resetForm) {
                filters.q.value = '';
                filters.u.value = '';
                if($(filters).find('[name="cat"]').length) filters.cat.value = 0;
            }
            updateList();
        },
        page: function (id)
        {
            if(isProcessing()) return;
            setPage(id);
            updateList();
        }, 
        onOrder: function(by)
        {
            if(isProcessing() || !orders[by])
                return;

            orders[by] = (orders[by] == 'asc' ? 'desc' : 'asc');
            //hide prev order direction
            $('#shops-order-'+orderby).hide();
            //show current order direction
            orderby = by;
            $('#shops-order-'+orderby).removeClass('order-asc order-desc').addClass('order-'+orders[by]).show();

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
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            return false;
        }
    };
}());
</script>