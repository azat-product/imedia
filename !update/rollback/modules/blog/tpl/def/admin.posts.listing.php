<?php
    /**
     * @var $this Blog
     */
    tpl::includeJS(array('tablednd','wysiwyg'), true);
    if(Blog::tagsEnabled()) {
        tpl::includeJS(array('autocomplete','autocomplete.fb'), true);
    }
    $bRotate = ($f['tab'] == 1);
    $lang_items = _t('blog', 'Посты').' / ';
?>
<?= tplAdmin::blockStart($lang_items._t('','Add'), false, array('id'=>'BlogPostsFormBlock','style'=>'display:none;')); ?>
    <div id="BlogPostsFormContainer"></div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart($lang_items._t('','List'), true, array('id'=>'BlogPostsListBlock','class'=>(!empty($act) ? 'hidden' : '')),
        array('title'=>_t('blog','+ добавить пост'),'class'=>'ajax','onclick'=>'return jBlogPostsFormManager.action(\'add\',0);')); ?>
            <?
                $aTabs = array(
                    0 => array('t'=>_t('','Все'), 'link'=> $this->adminLink(bff::$event.'&tab=0')),
                    1 => array('t'=>_t('blog','Избранные'), 'link'=> $this->adminLink(bff::$event.'&tab=1')),
                );
            ?>
            <div class="tabsBar" id="BlogPostsListTabs">
                <? foreach($aTabs as $k=>$v) { ?>
                    <span class="tab <? if($f['tab']==$k) { ?>tab-active<? } ?>"><a href="<?= $v['link'] ?>"><?= $v['t'] ?></a></span>
                <? } ?>
                <div id="BlogPostsProgress" class="progress" style="display: none;"></div>
                <? if($bRotate){ ?><div class="right desc">&darr; &uarr;</div><div class="clear"></div><? } ?>
            </div>
            <div class="actionBar">
                <form method="get" action="<?= $this->adminLink(NULL) ?>" id="BlogPostsListFilters" onsubmit="return false;" class="form-inline">
                    <input type="hidden" name="s" value="<?= bff::$class ?>" />
                    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
                    <input type="hidden" name="page" value="<?= $f['page'] ?>" />
                    <input type="hidden" name="tab" value="<?= $f['tab'] ?>" />
                    <input type="hidden" name="tag" id="BlogPostsListFiltersTagId" value="<?= $f['tag'] ?>" />

                    <div class="left<? if($f['tab'] == 1 || ( ! Blog::categoriesEnabled() && ! Blog::tagsEnabled())){ ?> hidden<? } ?>">
                        <div class="left">
                            <? if(Blog::categoriesEnabled()){ ?><label><select name="cat" onchange="jBlogPostsList.refresh();"><?= $cats ?></select></label><? } ?>
                            <? if(Blog::tagsEnabled()){ ?><label class="relative">
                                <input type="text" class="autocomplete input-medium" id="BlogPostsListFiltersTagTitle" placeholder="<?= _te('blog','тег') ?>" value="<?= ($f['tag']>0 ? $tag['tag'] : '') ?>" />
                            </label><? } ?>
                            <input type="button" class="btn btn-small" value="<?= _te('','найти') ?>" onclick="jBlogPostsList.submit(false);" />
                        </div>
                        <div class="left" style="margin-left: 8px;"><a class="ajax cancel" onclick="jBlogPostsList.submit(true); return false;"><?= _t('','сбросить') ?></a></div>
                        <div class="clear"></div>
                    </div>
                    <div class="clear"></div>
                </form>
            </div>

            <table class="table table-condensed table-hover admtbl tblhover" id="BlogPostsListTable">
            <thead>
            <tr class="header nodrag nodrop">
                <th width="70"><?= _t('','ID') ?></th>
                <th class="left"><?= _t('blog','Заголовок') ?></th>
                <? if(Blog::categoriesEnabled()){ ?><th width="130"><?= _t('blog','Категория') ?></th><? } ?>
                <th width="100"><?= _t('','Created') ?></th>
                <th width="155"><?= _t('','Action') ?></th>
            </tr>
            </thead>
            <tbody id="BlogPostsList">
                <?= $list ?>
            </tbody>
            </table>
            <div id="BlogPostsListPgn"><?= $pgn ?></div>

<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
var jBlogPostsFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer, process = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#BlogPostsFormContainer');
        $progress = $('#BlogPostsProgress');
        $block = $('#BlogPostsFormBlock');
        $blockCaption = $block.find('span.caption');

        <? if(!empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<? } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jBlogPostsList.toggle(false);
            if(jBlogPostsForm) jBlogPostsForm.onShow();
        } else {
            jBlogPostsList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        if( process ) return;
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html((type == 'add' ? '<?= $lang_items._t('', 'Add') ?>' : '<?= $lang_items._t('', 'Edit') ?>'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, {duration:500, offset:-300});
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jBlogPostsList.toggle(true);
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

var jBlogPostsList =
(function()
{
    var $progress, $block, $list, $listTable, $listPgn, filters, processing = false, tab = <?= $f['tab'] ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';

    $(function(){
        $progress  = $('#BlogPostsProgress');
        $block     = $('#BlogPostsListBlock');
        $list      = $block.find('#BlogPostsList');
        $listTable = $block.find('#BlogPostsListTable');
        $listPgn   = $block.find('#BlogPostsListPgn');
        filters    = $block.find('#BlogPostsListFilters').get(0);

        $list.delegate('a.post-edit', 'click', function(){
            var id = intval($(this).data('id'));
            if(id>0) jBlogPostsFormManager.action('edit',id);
            return false;
        });

        $list.delegate('a.post-toggle', 'click', function(){
            var id = intval($(this).data('id'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.delegate('a.post-del', 'click', function(){
            var id = intval($(this).data('id'));
            if(id>0) del(id, this);
            return false;
        });

        $list.delegate('a.post-category', 'click', function(){
            var id = intval($(this).data('id'));
            if(id>0) {
                filters['cat'].value = id;
                if(tab == 1) {
                    filters['tab'].value = 0;
                    filters.submit();
                } else {
                    updateList();
                }
            }
            return false;
        });

        $(window).bind('popstate',function(e){
            if('state' in window.history && window.history.state === null) return;
            var loc = document.location;
            var actForm = /act=(add|edit)/.exec( loc.search.toString() );
            if( actForm!=null ) {
                var actId = /id=([\d]+)/.exec(loc.search.toString());
                jBlogPostsFormManager.action(actForm[1], actId && actId[1]);
            } else {
                jBlogPostsFormManager.action('cancel');
                updateList(false);
            }
        });

        <? if($bRotate) { ?>bff.rotateTable($list, ajaxUrl+'rotate', $progress, false, {tab: tab});<? } ?>

        <? if(Blog::tagsEnabled()) { ?>
        $block.find('#BlogPostsListFiltersTagTitle').autocomplete( ajaxUrl+'tags-autocomplete',
            {valueInput: '#BlogPostsListFiltersTagId', placeholder: 'Тег', onSelect: function(){
                jBlogPostsList.submit(false);
            }});
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
                <? if($bRotate) { ?>$list.tableDnDUpdate();<? } ?>
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
                filters['tag'].value = 0;
                $block.find('#BlogPostsListFiltersTagTitle').val('');
            }
            updateList();
        },
        page: function(id)
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
        refresh: function(resetPage, updateUrl)
        {
            if(resetPage) setPage(1);
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