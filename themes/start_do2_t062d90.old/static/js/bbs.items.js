var jBBSItemsList = (function(){
    var inited = false, o = {list:'',pgn:'',pp:false,onInit:$.noop,lang:{},ajax:true}, listMngr,
    $form, $cat, $catVal, $list, $pgn, $pp, $ppVal, self = {};

    function init()
    {
        $form = self['form'] = $(o.list);
        $list = self['list'] = $form.find('.j-list');
        $pgn  = self['pgn'] = $(o.pgn);

        //cat
        $cat = $form.find('.j-cat');
        $catVal = $form.find('.j-cat-value');
        $cat.on('click', '.j-cat-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $catVal.val(value);
            $cat.find('.j-cat-dropdown').dropdown('toggle').blur();
            onCat(value, true);
        });

        //pp
        if (o.pp !== false) {
            $pp = $form.find(o.pp);
            $ppVal = $form.find('.j-pp-value');
            $pp.on('click', '.j-pp-option', function(e){ nothing(e);
                var value = $(this).data('value');
                $ppVal.val(value);
                $pp.find('.j-pp-dropdown').dropdown('toggle').blur();
                onPP(value, true);
            });
        }

        listMngr = self['listMngr'] = app.list($form, {
            onSubmit: function(resp, ex) {
                if(ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                $pgn.html(resp.pgn);
                $list.find('.j-list-'+app.device()).html(resp.list);
            },
            onProgress: function(progress, ex) {
                if(ex.fade) $list.toggleClass('disabled');
            },
            onPopstate: function() {
                if (o.pp !== false) {
                    onPP($ppVal.val(), false);
                }
                onCat($catVal.val(), false);
            },
            ajax: o.ajax
        });

        // pgn
        $pgn.on('keyup', '.j-pgn-goto', function(e){
            if(e.hasOwnProperty('keyCode') && e.keyCode == 13) {
                listMngr.page($(this).val(), true);
                nothing(e);
            }
        });
        if (o.ajax) {
            $pgn.on('click', '.j-pgn-page', function(e){ nothing(e);
                listMngr.page($(this).data('page'));
            });
        }

        o.onInit.call(self);
    }

    function onCat(value, submit)
    {
        $cat.find('.j-cat-title').html( $cat.find('.j-cat-option[data-value="'+intval(value)+'"]').html() );
        if( ! submit ) return;
        listMngr.page(1, false);
        listMngr.submit({scroll:true});
    }

    function onPP(value, submit)
    {
        $pp.find('.j-pp-title').html( $pp.find('.j-pp-option[data-value="'+intval(value)+'"]').html() );
        if( ! submit ) return;
        listMngr.page(1, false);
        listMngr.submit({scroll:true});
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = $.extend(o, options || {});
            $(function(){ init(); });
        }
    };
})();