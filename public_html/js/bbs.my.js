var jMyItems = (function(){
    var inited = false, o = {lang:{},status:{},ajax:true}, listMngr,
    $form, $cat, $catVal, $status, $statusVal, $statusArrows,
    $actions,
    $list, $pgn, $pp, $ppVal, total, selectAll;

    function init()
    {
        $form = $('#j-my-items-form');
        $list = $form.find('#j-my-items-list');
        $pgn = $form.find('#j-my-items-pgn');
        var $alertLimitsPayed = $form.find('#j-alert-limits-payed');
        var $alertShopAbonement = $form.find('#j-alert-shop-abonement');

        //cat
        $cat = $form.find('#j-my-items-cat');
        $catVal = $form.find('#j-my-items-cat-value');
        $cat.on('click', '.j-cat-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $cat.find('.j-cat-dropdown').dropdown('toggle').blur();
            onCat(value, true, true);
            return false;
        });

        //actions
        $actions = $form.find('#j-my-items-sel-actions');
        $actions.on('click', '.j-sel-action', function(e){ nothing(e);
            var act = $(this).data('act');
            if( act == 'mass-delete' ) {
                if( ! confirm(o.lang.delete_confirm_mass) ) {
                    return;
                }
            }
            var data = $form.serialize();
            if(selectAll){
                data += '&select_all=1';
            }
            bff.ajax('?act='+act+'&hash='+app.csrf_token, data, function(resp, errors){
                if( resp && resp.success ) {
                    app.alert.success(resp.message || '');
                    listMngr.submit({});
                    if(resp.hasOwnProperty('limitsPayed')){
                        $alertLimitsPayed.toggleClass('hidden', ! intval(resp.limitsPayed));
                    }
                    if(resp.hasOwnProperty('shopAbonement')){
                        $alertShopAbonement.toggleClass('hidden', ! intval(resp.shopAbonement));
                    }
                } else {
                    app.alert.error(errors);
                }
            });
        });
        $actions.on('click', '.j-mass-select', function(e){
            e.preventDefault();
            var device = app.device();
            var act = $(this).data('act');
            switch(act){
                case 'all':
                    $list.find('.j-check-'+device+':visible').prop('checked', true);
                    massActionsToggle(device);
                    var $actionsBlock = $actions.find('.j-my-items-sel-actions-'+device);
                    $actionsBlock.find('.j-sel-title').html( o.lang.sel_selected.replace(new RegExp('\\[items\\]'), bff.declension(total, o.lang['sel_items_'+device].split(';'))) );
                    selectAll = true;
                    break;
                case 'all-page':
                    $list.find('.j-check-'+device+':visible').prop('checked', true);
                    massActionsToggle(device);
                    break;
                case 'cancel':
                    massActionsToggle(device, true);
                    break;
            }
        });

        //item status
        $list.on('click', '.j-i-status', function(e){ nothing(e);
            var id = $(this).data('id');
            var act = $(this).data('act');
            switch(act) {
                case 'unpublicate':
                case 'delete':
                case 'publicate':
                {
                    if( act == 'delete' ) {
                        if ( ! confirm(o.lang.delete_confirm) ) {
                            return;
                        }
                    }
                    bff.ajax(bff.ajaxURL('bbs', 'item-status&status=') + act, {id:id,hash:app.csrf_token}, function(resp, errors){
                        if( resp && resp.success ) {
                            app.alert.success(resp.message || '');
                            listMngr.submit({});
                            if(resp.hasOwnProperty('limitsPayed')){
                                $alertLimitsPayed.toggleClass('hidden', ! intval(resp.limitsPayed));
                            }
                            if(resp.hasOwnProperty('shopAbonement')){
                                $alertShopAbonement.toggleClass('hidden', ! intval(resp.shopAbonement));
                            }
                        } else {
                            app.alert.error(errors);
                        }
                    });
                } break;
                case 'promote': {
                    //
                } break;
            }
            return false;
        });

        //item svc up free
        $list.on('click', '.j-i-up-free', function(e){ nothing(e);
            var id = $(this).data('id');
            bff.ajax(bff.ajaxURL('bbs', 'item-svc-up-free'), {id: id, hash: app.csrf_token}, function (resp, errors) {
                if (resp && resp.success) {
                    app.alert.success(resp.message || '');
                    listMngr.submit({});
                } else {
                    app.alert.error(errors);
                }
            });
            return false;
        });

        //item svc up auto
        $list.on('click', '.j-i-up-auto', function(e) {
            e.preventDefault();
            var $el = $(this);
            var $sett = $el.next('.j-i-up-auto-sett');
            if($sett.length){
                $sett.toggleClass('hide');
            }else{
                bff.ajax(bff.ajaxURL('bbs', 'item-svc-up-auto-form'), {id: $el.data('id'), hash: app.csrf_token}, function (resp, errors) {
                    if (resp && resp.success) {
                        $el.after(resp.html);
                        var $f = $el.next('.j-i-up-auto-sett').find('form');
                        var $load = $f.find('.j-submit');
                        var f = app.form($f, function(){
                            if( ! f.checkRequired() ) return;
                            $load.button('loading');
                            f.ajax(bff.ajaxURL('bbs', 'item-svc-up-auto-save'), {}, function(resp, errors){
                                $load.button('reset');
                                if(resp && resp.success) {
                                    app.alert.success(resp.message);
                                    $el.find('span').text( intval(resp.on) ? o.lang.up_auto_off : o.lang.up_auto_on);
                                    $el.find('i').toggleClass('text-blue', intval(resp.on));
                                } else {
                                    app.alert.error(errors);
                                }
                            });
                        });

                        $f.on('change', '[name="t"]', function(){
                            $f.find('.j-a-up-type').addClass('hide');
                            $f.find('.j-a-up-type-' + $(this).val()).removeClass('hide');
                        });

                        $f.on('click', '.j-cancel', function(e){
                            e.preventDefault();
                            $el.next('.j-i-up-auto-sett').addClass('hide');
                        });
                    }else{
                        app.alert.error(errors);
                    }
                });
            }
        });

        //mass
        massChecks(app.devices.desktop);
        massChecks(app.devices.phone);

        //status
        $status = $form.find('.j-status-options');
        $statusArrows = $form.find('#j-my-items-status-arrows');
        $statusVal = $form.find('#j-my-items-status-value');
        $form.on('click', '.j-status-option', function(e){ nothing(e);
            var value = $(this).data('value'); $statusVal.val(value);
            onStatus(value, true);
        });
        $statusArrows.on('click', '.j-left, .j-right', function(e){ nothing(e);
            var value = $(this).data('value'); $statusVal.val(value);
            onStatus(value, true);
        });
        onStatus($statusVal.val(), false);

        //query
        var $query = $form.find('.j-q');
        $query.filter(':hidden').prop({disabled:true});
        syncQuery($query);
        app.$W.on('resize', $.debounce(function(){
            $query.prop({disabled:false}).filter(':hidden').prop({disabled:true});
        }, 80));
        $form.on('click', '.j-q-submit', function(){
            onCat(0, false, true);
            listMngr.submit({scroll:true}, true);
        });

        //pp
        $pp = $form.find('#j-my-items-pp');
        $ppVal = $form.find('#j-my-items-pp-value');
        $pp.on('click', '.j-pp-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $ppVal.val(value);
            $pp.find('.j-pp-dropdown').dropdown('toggle').blur();
            onPP(value, true);
            return false;
        });

        listMngr = app.list($form, {
            onSubmit: function(resp, ex) {
                if(ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                var device = app.device();
                $list.find('.j-my-items-list-'+device).html(resp.list);
                for (var i in resp.counters) {
                    $form.find('.j-status-option[data-value="'+i+'"] .j-counter').text(resp.counters[i]);
                }
                $pgn.html(resp.pgn);
                total = resp.total;
                $pp.toggle(resp.total > 0);
                massActionsToggle(device, true);
                $cat.find('.j-cat-list').html(resp.cats);
            },
            onProgress: function(progress, ex) {
                if(ex.fade) $list.toggleClass('disabled');
            },
            onPopstate: function() {
                onCat($catVal.val(), false);
                onStatus($statusVal.val(), false);
                onPP($ppVal.val(), false);
            },
            ajax: o.ajax
        });

        //pgn
        if (o.ajax) {
            $pgn.on('click', '.j-pgn-page', function(e){ nothing(e);
                listMngr.page($(this).data('page'));
            });
        }
        var $modal = false;
        $('#j-limits-payed-info').on('click', function(e){
            e.preventDefault();
            if ($modal && $modal.length) {
                $modal.remove();
            }
            bff.ajax(bff.ajaxURL('bbs', 'limits-payed-popup'), {shop: $(this).data('shop')}, function (resp, errors) {
                if (resp && resp.success) {
                    if (resp.html) {
                        $modal = $(resp.html);
                        app.$B.append($modal);
                        $modal.modal('show');
                        $modal.on('hidden.bs.modal', function(){
                            $modal.remove();
                            $modal = false;
                        });
                    }
                } else {
                    app.alert.error(errors);
                }
            });
        });
        total = o.total;

        // hook: init
        bff.hook('bbs.my.items.init', listMngr, o);
    }

    function onCat(value, submit, updateValue)
    {
        $cat.find('.j-cat-title').html( $cat.find('.j-cat-option[data-value="'+intval(value)+'"]').html() );
        if( updateValue ) $catVal.val( value );
        if( submit ) {
            listMngr.page(1, false);
            listMngr.submit({scroll:true});
        }
    }

    function onStatus(value, submit)
    {
        value = intval(value);
        $status.removeClass('active');
        $form.find('.j-status-option[data-value="'+value+'"]').parent().addClass('active');
        if( o.status.hasOwnProperty(value) ) {
            var arr = o.status[value];
            $statusArrows.find('.j-left').data('value', arr.left).find('a').toggle(arr.left!==false);
            $statusArrows.find('.j-title').text(arr.title);
            $statusArrows.find('.j-right').data('value', arr.right).find('a').toggle(arr.right!==false);
        }
        $actions.hide().find('.j-sel-actions').hide().filter('[data-status="'+value+'"]').show();
        if( submit ) {
            //onCat(0, false, true);
            listMngr.submit({scroll:true}, true);
        }
    }

    function onPP(value, submit)
    {
        $pp.find('.j-pp-title').html( $pp.find('.j-pp-option[data-value="'+intval(value)+'"]').html() );
        if( submit ) {
            listMngr.submit({scroll:true}, true);
        }
    }

    function syncQuery(fields)
    {
        var block_changes = false;
        fields.on('change focus blur keyup', function(e){
            if( ! block_changes) {
                  block_changes = true;
                  fields.not(e.target).val( $(e.target).val() );
                  block_changes = false;
            }
        });
    }

    function massChecks(device)
    {
        $list.on('click', '.j-check-'+device, function(){
            massActionsToggle(device);
        });
    }

    function massActionsToggle(device, reset)
    {
        selectAll = false;
        var checkClass = '.j-check-'+device;
        if( reset ) {
            $list.find(checkClass+':visible:checked').prop('checked', false);
        }
        var $checks = $list.find(checkClass+':visible:checked');
        var selected = $checks.length;
        $actions.toggle( selected > 0 );
        var $actionsBlock = $actions.find('.j-my-items-sel-actions-'+device);
        if( ! $actionsBlock.length ) return;
        $actionsBlock.toggle( selected > 0 );
        $actionsBlock.find('.j-sel-title').html( o.lang.sel_selected.replace(new RegExp('\\[items\\]'), bff.declension(selected, o.lang['sel_items_'+device].split(';'))) );
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('bbs.my.items.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        }
    };
})();

var jMyFavs = (function(){
    var inited = false, o = {lang:{},ajax:true}, listMngr,
    $form, $cat, $catVal, $list, $pgn, $pp, $ppVal;

    function init()
    {
        $form = $('#j-my-favs-form');
        $list = $form.find('#j-my-favs-list');
        $pgn = $form.find('#j-my-favs-pgn');

        //cat
        $cat = $form.find('#j-my-favs-cat');
        $catVal = $form.find('#j-my-favs-cat-value');
        $cat.on('click', '.j-cat-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $catVal.val(value);
            $cat.find('.j-cat-dropdown').dropdown('toggle').blur();
            onCat(value, true);
            return false;
        });

        //cleanup
        $form.on('click', '.j-cleanup', function(e){ nothing(e);
            bff.ajax(listMngr.getURL(), {act:'cleanup',hash:app.csrf_token}, function(resp, errors){
                if(resp && resp.success) {
                    location.reload();
                } else {
                    app.alert.error(errors);
                }
            });
            return false;
        });

        //pp
        $pp = $form.find('#j-my-favs-pp');
        $ppVal = $form.find('#j-my-favs-pp-value');
        $pp.on('click', '.j-pp-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $ppVal.val(value);
            $pp.find('.j-pp-dropdown').dropdown('toggle').blur();
            onPP(value, true);
        });

        listMngr = app.list($form, {
            onSubmit: function(resp, ex) {
                if(ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                $pgn.html(resp.pgn);
                $list.find('.j-list-'+app.device()).html(resp.list);
            },
            onProgress: function(progress, ex) {
                if(ex.fade) $list.toggleClass('disabled');
            },
            onPopstate: function() {
                onPP($ppVal.val(), false);
                onCat($catVal.val(), false);
            },
            ajax: o.ajax
        });

        //pgn
        if (o.ajax) {
            $pgn.on('click', '.j-pgn-page', function(e){ nothing(e);
                listMngr.page($(this).data('page'));
            });
        }

        // hook: init
        bff.hook('bbs.my.favs.init', listMngr, o);
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
            o = bff.filter('bbs.my.favs.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        }
    };
})();