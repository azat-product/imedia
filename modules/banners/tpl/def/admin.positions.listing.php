<?php
    
?>
<?= tplAdmin::blockStart(_t('banners', 'Добавление позиции'), false, array('id'=>'BannersPositionsFormBlock','style'=>'display:none;')); ?>
    <div id="BannersPositionsFormContainer"></div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart(_t('banners', 'Баннеры / Позиции'), true, array('id'=>'BannersPositionsListBlock','class'=>(!empty($act) ? 'hidden' : '')),
    ( FORDEV ? array('title'=>_t('banners', '+ добавить позицию'),'class'=>'ajax','onclick'=>'return jBannersPositionsFormManager.action(\'add\',0);') : array() )
    ); ?>
            <div class="actionBar">
                <form method="get" action="<?= $this->adminLink(NULL) ?>" id="BannersPositionsListFilters" onsubmit="return false;" class="form-inline">
                    <input type="hidden" name="s" value="<?= bff::$class ?>" />
                    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
                    <div class="left"></div>
                    <div class="right"></div>
                    <div class="clear"></div>
                </form>
            </div>

            <table class="table table-condensed table-hover admtbl tblhover" id="BannersPositionsListTable">
            <thead>
            <tr class="header nodrag nodrop">
                <?php if(FORDEV) { ?>
                <th width="40"><?= _t('', 'ID'); ?></th>
                <th width="110" class="left"><?= _t('', 'Keyword'); ?></th>
                <?php } ?>
                <th class="left"><?= _t('', 'Название'); ?></th>
                <th width="90"><?= _t('banners', 'Размер'); ?></th>
                <th width="85"><?= _t('banners', 'Ротация'); ?></th>
                <th width="70"><?= _t('banners', 'Баннеры'); ?></th>
                <th width="135"><?= _t('', 'Action') ?></th>
            </tr>
            </thead>
            <tbody id="BannersPositionsList">
                <?= $list ?>
            </tbody>
            </table>
            
<?= tplAdmin::blockStop(); ?>
<div>
    <div class="left">
        
    </div>
    <div class="right desc" style="width:60px; text-align:right;">
        
    </div>
</div>

<script type="text/javascript">
var jBannersPositionsFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer, process = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#BannersPositionsFormContainer');
        $progress = $('#BannersPositionsProgress');
        $block = $('#BannersPositionsFormBlock');
        $blockCaption = $block.find('span.caption');

        <?php if(!empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<?php } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jBannersPositionsList.toggle(false);
            if(jBannersPositionsForm) jBannersPositionsForm.onShow();
        } else {
            jBannersPositionsList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        if( process ) return;
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html((type == 'add' ? '<?= _t('banners', 'Добавление позиции'); ?>' : '<?= _t('banners', 'Редактирование позиции'); ?>'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, {duration:500, offset:-300});
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jBannersPositionsList.toggle(true);
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

var jBannersPositionsList =
(function()
{
    var $progress, $block, $list, $listTable, filters, processing = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';
    
    $(function(){
        $progress  = $('#BannersPositionsProgress');
        $block     = $('#BannersPositionsListBlock');
        $list      = $block.find('#BannersPositionsList');
        $listTable = $block.find('#BannersPositionsListTable');
        filters    = $block.find('#BannersPositionsListFilters').get(0);

        $list.on('click', 'a.position-edit', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) jBannersPositionsFormManager.action('edit',id);
            return false;
        });

        $list.on('click', 'a.position-toggle', function(){
            var id = intval($(this).attr('rel'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.on('click', 'a.position-del', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) {
                bff.confirm('sure', {r: '<?= $this->adminLink('position_delete&id=') ?>'+id});
            }
            return false;
        });

        $(window).on('popstate',function(e){
            if('state' in window.history && window.history.state === null) return;
            var loc = document.location;
            var actForm = /act=(add|edit)/.exec( loc.search.toString() );
            if( actForm!=null ) {
                var actId = /id=([\d]+)/.exec(loc.search.toString());
                jBannersPositionsFormManager.action(actForm[1], actId && actId[1]);
            } else {
                jBannersPositionsFormManager.action('cancel');
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
                $list.html(data.list);
                if(updateUrl !== false && bff.h) {
                    window.history.pushState({}, document.title, $(filters).attr('action') + '?' + f);
                }
            }
        }, function(p){ $progress.toggle(); processing = p; $list.toggleClass('disabled'); });
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return false;
            if(resetForm) {
                //
            }
            updateList();
        },
        refresh: function(resetPage,updateUrl)
        {
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