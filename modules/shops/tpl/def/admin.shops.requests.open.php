<?php
    $f = HTML::escape($f, 'html', array('page','order'));
?>

<div class="tabsBar">
    <?
        $cntOpen = config::get('shops_requests_open', 0, TYPE_UINT);
        $cntJoin = config::get('shops_requests_join', 0, TYPE_UINT);
    ?>
    <span class="tab tab-active"><a href="<?= $this->adminLink('requests_open') ?>"><?= _t('shops', 'Открытие'); ?></a>&nbsp;<span<? if($cntOpen > 0){?> class="bold" <? } ?>>(<?= $cntOpen; ?>)</span></span>
    <? if(Shops::categoriesEnabled()) { ?><span class="tab"><a href="<?= $this->adminLink('requests') ?>"><?= _t('shops', 'Закрепление за пользователем'); ?></a>&nbsp;<span<? if($cntJoin > 0){?> class="bold" <? } ?>>(<?= $cntJoin; ?>)</span></span><? } ?>
</div>

<div class="actionBar">
    <form action="" method="get" name="filters" id="shops-filters" class="form-inline" onsubmit="return false;">
        <input type="hidden" name="page" value="<?= $f['page'] ?>" />
        <input type="hidden" name="order" value="<?= $f['order'] ?>" />
    </form>
</div>

<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <th width="40"><?= _t('', 'ID'); ?></th>
        <th class="left" style="padding-left: 18px;"><?= _t('shops', 'Название'); ?></th>
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
    var $list, $listPgn, filters;
    var url = '<?= $this->adminLink(bff::$event); ?>';
    var orders = <?= func::php2js($orders) ?>;
    var orderby = '<?= $f['order_by'] ?>';
    var _processing = false;
    
    $(function(){
        $list     = $('#shops-list');
        $listPgn  = $('#shops-pgn');
        filters   = $('#shops-filters').get(0);

        $list.on('click', '.j-act-del', function(e){ nothing(e);
            var id = $(this).data('id');
            if( ! bff.confirm('sure')) return;
            bff.ajax(url, {act:'delete',id:id}, function(data){
                if(data && data.success) {
                    location.reload();
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
            $list.toggleClass('disabled');
        });
    }
    
    function setPage(id)
    {
        filters.page.value = intval(id);
    }

    return {
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
        }
    };
}());
</script>