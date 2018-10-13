<?php
    /**
     * @var $this Help
     */
    tpl::includeJS(array('tablednd'), true);
    $lang_categories = _t('categories', 'Categories').' / ';
?>
<?= tplAdmin::blockStart($lang_categories._t('', 'Add'), false, array('id'=>'HelpCategoriesFormBlock','style'=>'display:none;')); ?>
    <div id="HelpCategoriesFormContainer"></div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart($lang_categories._t('', 'List'), true, array('id'=>'HelpCategoriesListBlock','class'=>(!empty($act) ? 'hidden' : '')),
        array('title'=>_t('categories', '+ add category'),'class'=>'ajax','onclick'=>'return jHelpCategoriesFormManager.action(\'add\',0);'),
        array(
            array('title'=>_t('', 'nested-sets validation'), 'onclick'=>"return bff.redirect('".$this->adminLink('categories&act=dev-treevalidate')."');", 'icon'=>'icon-indent-left', 'debug-only'=>true),
            array('title'=>_t('categories', 'delete all categories'), 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('categories&act=dev-delete-all')."'})", 'icon'=>'icon-remove', 'debug-only'=>true),
        )
    ); ?>
            <div class="actionBar">
                <form method="get" action="<?= $this->adminLink(NULL) ?>" id="HelpCategoriesListFilters" onsubmit="return false;" class="form-inline">
                    <input type="hidden" name="s" value="<?= bff::$class ?>" />
                    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
                </form>
            </div>

            <table class="table table-condensed table-hover admtbl tblhover" id="HelpCategoriesListTable">
                <thead>
                    <tr class="header nodrag nodrop">
                        <th width="70"><?= _t('', 'ID') ?></th>
                        <th class="left"><?= _t('', 'Title') ?><div id="HelpCategoriesProgress" class="progress" style="display: none; margin: 0 0 5px 10px;"></div></th>
                        <th width="140"><?= _t('', 'Created') ?></th>
                        <th width="135"><?= _t('', 'Action') ?></th>
                    </tr>
                </thead>
                <tbody id="HelpCategoriesList">
                    <?= $list ?>
                </tbody>
            </table>
            
            
<?= tplAdmin::blockStop(); ?>

<div>
    <div class="left"></div>
    <div class="right desc" style="width:60px; text-align:right;">&darr; &uarr;</div>
</div>

<script type="text/javascript">
<? js::start(); ?>
var jHelpCategoriesFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer, process = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#HelpCategoriesFormContainer');
        $progress = $('#HelpCategoriesProgress');
        $block = $('#HelpCategoriesFormBlock');
        $blockCaption = $block.find('span.caption');

        <? if ( ! empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<? } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jHelpCategoriesList.toggle(false);
            if(jHelpCategoriesForm) jHelpCategoriesForm.onShow();
        } else {
            jHelpCategoriesList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        if( process ) return;
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html((type == 'add' ? '<?= $lang_categories._t('', 'Add') ?>' : '<?= $lang_categories._t('', 'Edit') ?>'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, {duration:500, offset:-300});
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jHelpCategoriesList.toggle(true);
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

var jHelpCategoriesList =
(function()
{
    var $progress, $block, $list, $listTable, $listPgn, filters, processing = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';
    
    $(function(){
        $progress  = $('#HelpCategoriesProgress');
        $block     = $('#HelpCategoriesListBlock');
        $list      = $block.find('#HelpCategoriesList');
        $listTable = $block.find('#HelpCategoriesListTable');
        $listPgn   = $block.find('#HelpCategoriesListPgn');
        filters    = $block.find('#HelpCategoriesListFilters').get(0);

        $list.on('click','a.category-edit', function(){
            var id = intval($(this).data('id'));
            if(id>0) jHelpCategoriesFormManager.action('edit',id);
            return false;
        });

        $list.on('click','a.category-toggle', function(){
            var id = intval($(this).data('id'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.on('click','a.category-del', function(){
            var id = intval($(this).data('id'));
            if(id>0) bff.redirect('<?= $this->adminLink('categories_delete&id=') ?>'+id);
            return false;
        });

        $list.on('click','a.category-expand', function(){
            var id = intval($(this).data('id'));
            if(id>0) {
                bff.expandNS(id, ajaxUrl+'expand&id=', {cookie:app.cookiePrefix+'help_categories_expand', progress:$progress});
            }
            return false;
        });

        $(window).on('popstate',function(e){
            if('state' in window.history && window.history.state === null) return;
            var loc = document.location;
            var actForm = /act=(add|edit)/.exec( loc.search.toString() );
            if( actForm!=null ) {
                var actId = /id=([\d]+)/.exec(loc.search.toString());
                jHelpCategoriesFormManager.action(actForm[1], actId && actId[1]);
            } else {
                jHelpCategoriesFormManager.action('cancel');
                updateList(false);
            }
        });

        bff.rotateTable($list, ajaxUrl+'rotate', $progress);
    });

    function isProcessing()
    {
        return processing;
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
                $list.tableDnDUpdate();
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
        refresh: function(resetPage, updateUrl)
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
<? js::stop(); ?>
</script>