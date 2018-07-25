<?php
    /**
     * Посадочные страницы
     * @var $this SEO
     */
?>
<?= tplAdmin::blockStart(_t('seo', 'SEO / Посадочные страницы / Добавление'), false, array('id'=>'SeoLandingPagesFormBlock','style'=>'display:none;')); ?>
    <div id="SeoLandingPagesFormContainer"></div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart(_t('seo', 'SEO / Посадочные страницы'), true, array('id'=>'SeoLandingPagesListBlock','class'=>(!empty($act) ? 'hidden' : '')),
        array('title'=>_t('seo', '+ добавить страницу'),'class'=>'ajax','onclick'=>'return jSeoLandingPagesFormManager.action(\'add\',0);'),
        array()
    ); ?>
            <div class="actionBar">
                <form method="get" action="<?= $this->adminLink(NULL) ?>" id="SeoLandingPagesListFilters" onsubmit="return false;" class="form-inline">
                <input type="hidden" name="s" value="<?= bff::$class ?>" />
                <input type="hidden" name="ev" value="<?= bff::$event ?>" />
                <input type="hidden" name="page" value="<?= $f['page'] ?>" />
                
                <div class="left">
                    <div class="left">
                        <input style="width:375px;" type="text" name="title" placeholder="<?= _te('seo', 'Название / URL / ID посадочной страницы') ?>" value="<?= HTML::escape($f['title']) ?>" />
                        <input type="button" class="btn btn-small button cancel" onclick="jSeoLandingPagesList.submit(false);" value="<?= _te('', 'найти') ?>" />
                    </div>
                    <div class="left"><a class="ajax cancel" onclick="jSeoLandingPagesList.submit(true); return false;"><?= _t('', 'сбросить') ?></a></div>
                    <div class="clear"></div>
                </div>
                <div class="right">
                    <div id="SeoLandingPagesProgress" class="progress" style="display: none;"></div>
                </div>
                <div class="clear"></div>
                </form>
            </div>

            <table class="table table-condensed table-hover admtbl tblhover" id="SeoLandingPagesListTable">
                <thead>
                    <tr class="header nodrag nodrop">
                        <th width="60">ID</th>
                        <th class="left"><?= _t('seo', 'Посадочный URL') ?></th>
                        <th class="left"><?= _t('seo', 'Оригинальный URL') ?></th>
                        <th width="100" class="left"><?= _t('', 'Действие') ?></th>
                    </tr>
                </thead>
                <tbody id="SeoLandingPagesList">
                    <?= $list ?>
                </tbody>
            </table>
            <div id="SeoLandingPagesListPgn"><?= $pgn ?></div>
            
<?= tplAdmin::blockStop(); ?>

<div>
    <div class="left"></div>
    <div class="right desc" style="width:60px; text-align:right;">
        
    </div>
</div>

<script type="text/javascript">
var jSeoLandingPagesFormManager = (function(){
    var _progress, _block, _blockCaption, _formContainer, _process = false;
    var _ajaxUrl = '<?= $this->adminLink(bff::$event,'','js'); ?>';

    $(function(){
        _formContainer = $('#SeoLandingPagesFormContainer');
        _progress = $('#SeoLandingPagesProgress');
        _block = $('#SeoLandingPagesFormBlock');
        _blockCaption = _block.find('span.caption');

        <?php if( ! empty($act)) { ?> _action('<?= $act ?>', <?= $id ?>); <?php } ?>
    });

    function _onFormToggle($visible)
    {
        if ($visible) {
            jSeoLandingPagesList.toggle(false);
            if(jSeoLandingPagesForm) jSeoLandingPagesForm.onShow();
        } else {
            jSeoLandingPagesList.toggle(true);
            jSeoLandingPagesList.refresh(false, false);
        }
    }

    function _initForm($type, $id, $params)
    {
        if( _process ) return;
        bff.ajax(_ajaxUrl, $params, function(data){
            if(data && (data.success || intval($params.save)===1)) {
                _blockCaption.html('<?= _t('seo', 'SEO / Посадочные страницы / ') ?>'+($type == 'add' ? '<?= _t('', 'Добавление') ?>' : '<?= _t('', 'Редактирование') ?>'));
                _formContainer.html(data.form);
                _block.show();
                $.scrollTo( _blockCaption, {duration:500, offset:-300});
                _onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, _ajaxUrl + '&act='+$type+'&id='+$id);
                }
            } else {
                jSeoLandingPagesList.toggle(true);
            }
        }, function(p){ _process = p; _progress.toggle(); });
    }

    function _action($type, $id, $params)
    {
        $params = $.extend($params || {}, {act:$type});
        switch($type) {
            case 'add':
            {
                if( $id > 0 ) return _action('edit', $id, $params);
                if(_block.is(':hidden')) {
                    _initForm($type, $id, $params);
                } else {
                    _action('cancel');
                }
            } break;
            case 'cancel':
            {
                _block.hide();
                _onFormToggle(false);
            } break;
            case 'edit':
            {
                if( ! ($id || 0) ) return _action('add', 0, $params);
                $params.id = $id;
                _initForm($type, $id, $params);
            } break;
        }
        return false;
    }

    return {
        action: _action
    };
}());

var jSeoLandingPagesList = (function(){
    var _progress, _block, _list, _listTable, _listPgn, _filters, _process = false;
    var _ajaxUrl = '<?= $this->adminLink(bff::$event.'&act=','','js'); ?>';
    
    $(function(){
        _progress  = $('#SeoLandingPagesProgress');
        _block     = $('#SeoLandingPagesListBlock');
        _list      = _block.find('#SeoLandingPagesList');
        _listTable = _block.find('#SeoLandingPagesListTable');
        _listPgn   = _block.find('#SeoLandingPagesListPgn');
        _filters    = _block.find('#SeoLandingPagesListFilters').get(0);

        _list.delegate('a.landingpage-edit', 'click', function(){
            var $id = intval($(this).data('id'));
            if ($id>0) jSeoLandingPagesFormManager.action('edit',$id);
            return false;
        });

        _list.delegate('a.landingpage-toggle', 'click', function(){
            var $id = intval($(this).data('id'));
            var $type = $(this).data('type');
            if ($id>0) {
                var $params = {progress: _progress, link: this};
                bff.ajaxToggle($id, _ajaxUrl+'toggle&type='+$type+'&id='+$id, $params);
            }
            return false;
        });

        _list.delegate('a.landingpage-del', 'click', function(){
            var $id = intval($(this).data('id'));
            if ($id>0) _del($id, this);
            return false;
        });

        $(window).bind('popstate',function(e){
            if('state' in window.history && window.history.state === null) return;
            var loc = document.location;
            var $act = /act=(add|edit)/.exec( loc.search.toString() );
            if( $act!=null ) {
                var $id = /id=([\d]+)/.exec(loc.search.toString());
                jSeoLandingPagesFormManager.action($act[1], $id && $id[1]);
            } else {
                jSeoLandingPagesFormManager.action('cancel');
                _updateList(false);
            }
        });

    });

    function _isProcessing()
    {
        return _process;
    }

    function _del($id, $link)
    {
        bff.ajaxDelete('<?= _t('', 'Delete?') ?>', $id, _ajaxUrl+'delete&id='+$id, $link, {progress: _progress, repaint: false});
        return false;
    }

    function _updateList($updateUrl)
    {
        if(_isProcessing()) return;
        var f = $(_filters).serialize();
        bff.ajax(_ajaxUrl, f, function(r){
            if (r) {
                _list.html(r.list);
                _listPgn.html(r.pgn);
                if ($updateUrl !== false && bff.h) {
                    window.history.pushState({}, document.title, $(_filters).attr('action') + '?' + f);
                }
            }
        }, function(p){ _progress.toggle(); _process = p; _list.toggleClass('disabled'); });
    }

    function _setPage($id)
    {
        _filters.page.value = intval($id);
    }

    return {
        submit: function($resetForm)
        {
            if (_isProcessing()) return false;
            _setPage(1);
            if ($resetForm) {
                _filters['title'].value = '';
                //
            }
            _updateList();
        },
        page: function($id)
        {
            if (_isProcessing()) return false;
            _setPage($id);
            _updateList();
        },
        refresh: function($resetPage, $updateUrl)
        {
            if ($resetPage) _setPage(0);
            _updateList($updateUrl);
        },
        toggle: function($show)
        {
            if ($show === true) {
                _block.show();
                if(bff.h) window.history.pushState({}, document.title, $(_filters).attr('action') + '?' + $(_filters).serialize());
            }
            else _block.hide();
        }
    };
}());
</script>