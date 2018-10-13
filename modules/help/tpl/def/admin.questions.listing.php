<?php
    /**
     * @var $this Help
     */
    $lang_questions = _t('help', 'Вопросы').' / ';
?>
<?= tplAdmin::blockStart($lang_questions._t('', 'Add'), false, array('id'=>'HelpQuestionsFormBlock','style'=>'display:none;')); ?>
    <div id="HelpQuestionsFormContainer"></div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart($lang_questions._t('', 'List'), true, array('id'=>'HelpQuestionsListBlock','class'=>(!empty($act) ? 'hidden' : '')),
        array('title'=>_t('help','+ добавить вопрос'),'class'=>'ajax','onclick'=>'return jHelpQuestionsFormManager.action(\'add\',0);')); ?>
            <?
                $aTabs = array(
                    0 => array('t'=>_t('','Все'), 'link'=> $this->adminLink(bff::$event.'&tab=0')),
                    1 => array('t'=>_t('help','Частые вопросы'), 'link'=> $this->adminLink(bff::$event.'&tab=1')),
                );
            ?>
            <div class="tabsBar" id="HelpQuestionsListTabs">
                <? foreach($aTabs as $k=>$v) { ?>
                    <span class="tab <? if($f['tab']==$k) { ?>tab-active<? } ?>"><a href="<?= $v['link'] ?>"><?= $v['t'] ?></a></span>
                <? } ?>
                <div id="HelpQuestionsProgress" class="progress" style="display: none;"></div>
                <? if($rotate){ ?><div class="right desc">&darr; &uarr;</div><div class="clear"></div><? } ?>
            </div>
            <div class="actionBar">
                <form method="get" action="<?= $this->adminLink(NULL) ?>" id="HelpQuestionsListFilters" onsubmit="return false;" class="form-inline">
                <input type="hidden" name="s" value="<?= bff::$class ?>" />
                <input type="hidden" name="ev" value="<?= bff::$event ?>" />
                <input type="hidden" name="page" value="<?= $f['page'] ?>" />
                <input type="hidden" name="tab" value="<?= $f['tab'] ?>" />
                
                <div class="left<? if($f['tab'] == 1){ ?> hidden<? } ?>">
                    <label><?= _t('help', 'Категория') ?> <select name="cat" onchange="jHelpQuestionsList.onCategory();"><?= $cats ?></select></label>
                </div>
                <div class="right">
                    
                </div>
                <div class="clear"></div>
                </form>
            </div>

            <table class="table table-condensed table-hover admtbl tblhover" id="HelpQuestionsListTable">
                <thead>
                    <tr class="header nodrag nodrop">
                        <th width="40">ID</th>
                        <th class="left"><?= _t('help','Заголовок') ?></th>
                        <th width="130"><?= _t('help', 'Категория') ?></th>
                        <th width="120"><?= _t('', 'Created') ?></th>
                        <th width="135"><?= _t('', 'Action') ?></th>
                    </tr>
                </thead>
                <tbody id="HelpQuestionsList">
                    <?= $list ?>
                </tbody>
            </table>
            <div id="HelpQuestionsListPgn"><?= $pgn ?></div>
            
<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
<? js::start(); ?>
var jHelpQuestionsFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer, process = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#HelpQuestionsFormContainer');
        $progress = $('#HelpQuestionsProgress');
        $block = $('#HelpQuestionsFormBlock');
        $blockCaption = $block.find('span.caption');

        <? if( ! empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<? } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jHelpQuestionsList.toggle(false);
            if(jHelpQuestionsForm) jHelpQuestionsForm.onShow();
        } else {
            jHelpQuestionsList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        if( process ) return;
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html('<?= $lang_questions ?>'+(type == 'add' ? '<?= _t('', 'Add') ?>' : '<?= _t('', 'Edit') ?>'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, {duration:500, offset:-300});
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jHelpQuestionsList.toggle(true);
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
                $block.find('form:first').trigger('form.hide');
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

var jHelpQuestionsList =
(function()
{
    var $progress, $block, $list, $listTable, $listPgn, filters, processing = false, tab = <?= $f['tab'] ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';
    
    $(function(){
        $progress  = $('#HelpQuestionsProgress');
        $block     = $('#HelpQuestionsListBlock');
        $list      = $block.find('#HelpQuestionsList');
        $listTable = $block.find('#HelpQuestionsListTable');
        $listPgn   = $block.find('#HelpQuestionsListPgn');
        filters    = $block.find('#HelpQuestionsListFilters').get(0);

        $list.on('click','a.question-edit', function(){
            var id = intval($(this).data('id'));
            if(id>0) jHelpQuestionsFormManager.action('edit',id);
            return false;
        });

        $list.on('click','a.question-toggle', function(){
            var id = intval($(this).data('id'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.on('click','a.question-del', function(){
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
                jHelpQuestionsFormManager.action(actForm[1], actId && actId[1]);
            } else {
                jHelpQuestionsFormManager.action('cancel');
                updateList(false);
            }
        });

    <? if($rotate) { ?>
        bff.rotateTable($list, ajaxUrl+'rotate', $progress, false, {tab: tab, cat: parseInt('<?= $f['cat'] ?>')});
    <? } ?>

    });

    function isProcessing()
    {
        return processing;
    }

    function del(id, link)
    {
        bff.ajaxDelete('<?= _t('','Delete?') ?>', id, ajaxUrl+'delete&id='+id, link, {progress: $progress, repaint: false});
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
                <? if($rotate) { ?>$list.tableDnDUpdate();<? } ?>
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
                filters['cat'].value = 0;
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
        onTab: function(tabNew, link)
        {
            if(isProcessing() || tabNew == tab) return false;
            setPage(1);
            tab = filters.tab.value = tabNew;
            updateList();
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            return false;
        },
        onCategory: function()
        {
            filters.submit();
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
<? js::stop(); ?>
</script>