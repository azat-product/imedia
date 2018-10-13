<?php
    /**
     * @var $this Shops
     */
    
?>
<?= tplAdmin::blockStart(_t('shops', 'Магазины / Заявки / Просмотр'), false, array('id'=>'ShopsRequestsFormBlock','style'=>'display:none;')); ?>
    <div id="ShopsRequestsFormContainer"></div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart(_t('shops', 'Магазины / Заявки'), true, array('id'=>'ShopsRequestsListBlock','class'=>(!empty($act) ? 'hidden' : '')),
        array(),
        array()
    ); ?>
            <div class="tabsBar">
                <?
                    $cntOpen = config::get('shops_requests_open', 0, TYPE_UINT);
                    $cntJoin = config::get('shops_requests_join', 0, TYPE_UINT);
                ?>
                <? if(Shops::premoderation()) { ?><span class="tab"><a href="<?= $this->adminLink('requests_open') ?>"><?= _t('shops', 'Открытие'); ?></a>&nbsp;<span<? if($cntOpen > 0){?> class="bold" <? } ?>>(<?= $cntOpen; ?>)</span></span><? } ?>
                <span class="tab tab-active"><a href="<?= $this->adminLink('requests') ?>"><?= _t('shops', 'Закрепление за пользователем'); ?></a>&nbsp;<span<? if($cntJoin > 0){?> class="bold" <? } ?>>(<?= $cntJoin; ?>)</span></span>
                <div id="ShopsRequestsProgress" class="progress right" style="display: none;"></div>
                <div class="clear"></div>
            </div>

            <div class="actionBar">
                <form method="get" action="<?= $this->adminLink(NULL) ?>" id="ShopsRequestsListFilters" onsubmit="return false;" class="form-inline">
                    <input type="hidden" name="s" value="<?= bff::$class ?>" />
                    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
                    <input type="hidden" name="page" value="<?= $f['page'] ?>" />

                    <div class="left"></div>
                    <div class="right"></div>
                    <div class="clear"></div>
                </form>
            </div>

            <table class="table table-condensed table-hover admtbl tblhover" id="ShopsRequestsListTable">
                <thead>
                    <tr class="header nodrag nodrop">
                        <th width="70"><?= _t('', 'ID'); ?></th>
                        <th class="left"><?= _t('shops', 'Имя'); ?></th>
                        <th width="120"><?= _t('', 'Created'); ?></th>
                        <th width="110"><?= _t('shops', 'IP адрес'); ?></th>
                        <th width="90"><?= _t('', 'Action') ?></th>
                    </tr>
                </thead>
                <tbody id="ShopsRequestsList">
                    <?= $list ?>
                </tbody>
            </table>
            <div id="ShopsRequestsListPgn"><?= $pgn ?></div>
            
<?= tplAdmin::blockStop(); ?>

<div>
    <div class="left"></div>
    <div class="right desc" style="width:60px; text-align:right;">
        
    </div>
</div>

<script type="text/javascript">
var jShopsRequestsFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer, process = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#ShopsRequestsFormContainer');
        $progress = $('#ShopsRequestsProgress');
        $block = $('#ShopsRequestsFormBlock');
        $blockCaption = $block.find('span.caption');

        <? if( ! empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<? } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jShopsRequestsList.toggle(false);
            if(jShopsRequestsForm) jShopsRequestsForm.onShow();
        } else {
            jShopsRequestsList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        if( process ) return;
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html((type == 'add' ? '<?= _t('shops', 'Магазины / Заявки / Добавление'); ?>' : '<?= _t('shops', 'Магазины / Заявки / Просмотр'); ?>'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, {duration:500, offset:-300});
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jShopsRequestsList.toggle(true);
            }
        }, function(p){ process = p; $progress.toggle(); });
    }

    function action(type, id, params)
    {
        params = $.extend(params || {}, {act:type});
        switch(type) {
            case 'add':
            {
                if( id > 0 ) return action('edit', id, params);
                if($block.is(':hidden')) {
                    initForm(type, id, params);
                } else {
                    action('cancel');
                }
            } break;
            case 'cancel':
            {
                $block.hide();
                onFormToggle(false);
            } break;
            case 'edit':
            {
                if( ! (id || 0) ) return action('add', 0, params);
                params.id = id;
                initForm(type, id, params);
            } break;
        }
        return false;
    }

    return {
        action: action
    };
}());

var jShopsRequestsList =
(function()
{
    var $progress, $block, $list, $listTable, $listPgn, filters, processing = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';
    
    $(function(){
        $progress  = $('#ShopsRequestsProgress');
        $block     = $('#ShopsRequestsListBlock');
        $list      = $block.find('#ShopsRequestsList');
        $listTable = $block.find('#ShopsRequestsListTable');
        $listPgn   = $block.find('#ShopsRequestsListPgn');
        filters    = $block.find('#ShopsRequestsListFilters').get(0);

        $list.on('click','a.request-edit', function(){
            var id = intval($(this).data('id'));
            if(id>0) jShopsRequestsFormManager.action('edit',id);
            return false;
        });

        $list.on('click','a.request-toggle', function(){
            var id = intval($(this).data('id'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.on('click','a.request-del', function(){
            var id = intval($(this).data('id'));
            if(id>0) del(id, this);
            return false;
        });

        $(window).on('popstate',function(e){
            if('state' in window.history && window.history.state === null) return;
            var loc = document.location;
            var actForm = /act=(add|edit)/.exec( loc.search.toString() );
            if( actForm!=null ) {
                var actId = /id=([\d]+)/.exec(loc.search.toString());
                jShopsRequestsFormManager.action(actForm[1], actId && actId[1]);
            } else {
                jShopsRequestsFormManager.action('cancel');
                updateList(false);
            }
        });

    });

    function isProcessing()
    {
        return processing;
    }

    function del(id, link)
    {
        bff.ajaxDelete('<?= _t('', 'Delete?') ?>', id, ajaxUrl+'delete&id='+id, link, {progress: $progress, repaint: false});
        return false;
    }

    function updateList(updateUrl)
    {
        if(isProcessing()) return;
        var f = $(filters).serialize();
        bff.ajax(ajaxUrl, f, function(data){
            if(data) {
                $list.html( data.list );
                $listPgn.html( data.pgn );
                if(updateUrl !== false && bff.h) {
                    window.history.pushState({}, document.title, $(filters).attr('action') + '?' + f);
                }
            }
        }, function(p){ $progress.toggle(); processing = p; $list.toggleClass('disabled'); });
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
                //
            }
            updateList();
        },
        page: function (id)
        {
            if(isProcessing()) return false;
            setPage(id);
            updateList();
        },
        refresh: function(resetPage, updateUrl)
        {
            if(resetPage) setPage(0);
            updateList(updateUrl);
        },
        toggle: function(show)
        {
            if(show === true) {
                $block.show();
                if(bff.h) window.history.pushState({}, document.title, $(filters).attr('action') + '?' + $(filters).serialize());
            }
            else $block.hide();
        }
    };
}());
</script>