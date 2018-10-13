var jBBSShopItems = (function(){
    var inited = false, o = {lang:{},ajax:true}, listMngr,
    $form, $cat, $catVal, $list, $pgn;

    function init()
    {
        $form = $('#j-shop-view-items-list');
        $list = $form.find('.j-list');
        $pgn = $('#j-shop-view-items-pgn');

        //cat
        $cat = $form.find('.j-cat');
        $catVal = $form.find('.j-cat-value');
        $cat.on('click', '.j-cat-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $catVal.val(value);
            $cat.find('.j-cat-dropdown').dropdown('toggle').blur();
            onCat(value, true);
        });

        listMngr = app.list($form, {
            onSubmit: function(resp, ex) {
                if(ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                $pgn.html(resp.pgn);
                $list.find('.j-list-'+app.device()).html(resp.list).find('.j-tooltip').tooltip();
            },
            onProgress: function(progress, ex) {
                if(ex.fade) $list.toggleClass('disabled');
            },
            onPopstate: function() {
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

        // hook: init
        bff.hook('bbs.shop.items.init', listMngr, o);
    }

    function onCat(value, submit)
    {
        $cat.find('.j-cat-title').html( $cat.find('.j-cat-option[data-value="'+intval(value)+'"]').html() );
        if( ! submit ) return;
        listMngr.page(1, false);
        listMngr.submit({scroll:true});
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('bbs.shop.items.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        }
    };
})();