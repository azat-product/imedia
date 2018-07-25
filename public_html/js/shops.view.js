var jShopsView = (function(){
    var inited = false, $container, o = {lang:{},geoMapZoom:12,geoMapZoomPhone:12}, progress = false;

    function init()
    {
        $container = $('#j-shops-v-container');

        // descr
        $container.on('click', '#j-shop-view-descr-ex-phone', function(e){ nothing(e);
            var $content = $(this).parent(); $(this).remove();
            $content.html($content.text() + $content.next().text());
        });
        $container.on('click', '#j-shop-view-descr-ex', function(e){ nothing(e);
            var $content = $(this).parent(); $(this).remove();
            $content.html($content.text() + $content.next().text());
        });

        // addr - map
        if(o.addr_map) {
            var map = false;
            app.popup('shop-v-map', '#j-shop-view-map-popup', '#j-shop-view-map-toggler', {
                onShow: function($p){
                    $p.fadeIn(100, function(){
                        if(map) {
                            map.panTo([parseFloat(o.addr_lat), parseFloat(o.addr_lon)], {delay: 10, duration: 200});
                        }else{
                            map = app.map('j-shop-view-map-container', o.addr_lat+','+o.addr_lon, false, {
                                marker: true,
                                zoom: o.geoMapZoom
                            });
                        }
                    });
                }
            });
            
            // phone
            var phoneMap = false;
            var $map = $container.find('#j-shop-view-phone-map');
            $container.on('click', '#j-shop-view-phone-map-toggler', function(e){ nothing(e);
                if ( ! phoneMap) {
                    $map.show();
                    phoneMap = app.map($map.get(0), o.addr_lat+','+o.addr_lon, false, {
                        marker: true,
                        zoom: o.geoMapZoomPhone
                    });
                } else {
                    $map.toggle();
                    if($map.is(':visible')){
                        phoneMap.panTo([parseFloat(o.addr_lat), parseFloat(o.addr_lon)], {delay: 10, duration: 200});
                    }
                }
            });
        }

        // contacts expand
        $container.on('click', '.j-shop-view-c-toggler', function(e){
            nothing(e); if(progress) return;
            bff.ajax(bff.ajaxURL('shops','shop-contacts'), {hash:app.csrf_token, ex: o.ex},
                function(data, errors) {
                    if(data && data.success) {
                        if (data.hasOwnProperty('phones')) $container.find('.j-shop-view-c-phones').html(data['phones']);
                        if (data.hasOwnProperty('contacts')) {
                            for(var c in data.contacts) {
                                $container.find('.j-shop-view-c-' + c).html(data.contacts[c]);
                            }
                        }
                        $('.j-shop-view-c-toggler', $container).hide();
                    } else {
                        app.alert.error(errors);
                    }
                }, function(p){ progress = p; }
            );
        });

        // send4friend popup
        app.popup('shop-v-send4friend-desktop', '#j-shop-view-send4friend-desktop-popup', '#j-shop-view-send4friend-desktop-link', {onInit: function($p){
            var _this = this;
            var f = app.form($p.find('form:first'), function($f){
                if( ! bff.isEmail( f.fieldStr('email') ) ) {
                    f.fieldError('email', o.lang.sendfriend.email); return;
                }
                f.ajax(bff.ajaxURL('shops','shop-sendfriend'), {id: o.id}, function(data, errors){
                    if(data && data.success) {
                        _this.hide(function(){
                            f.alertSuccess(o.lang.sendfriend.success, {reset:true});
                        });
                    } else {
                        f.fieldsError(data.fields, errors);
                        if( data.later ){ _this.hide(function(){ f.reset(); }); }
                    }
                });
            });
        }});

        // claim popup
        app.popup('shops-v-claim-desktop', '#j-shops-v-claim-desktop-popup', '#j-shops-v-claim-desktop-link', {onInit: function($p){
            var _this = this,
                $f = $p.find('form:first'),
                $reason_checks = $f.find('.j-claim-check'),
                $reason_other = $f.find('.j-claim-other'),
                f;

            function _refresh_catpcha() {
                $f.find('.j-captcha').triggerHandler('click');
            }

            $reason_checks.on('click', function(){
                if( intval($(this).val()) == o.claim_other_id ) {
                    if( $reason_other.toggle().is(':visible') ) {
                        $reason_other.find('textarea').focus();
                    }
                }
            });
            f = app.form($f, function(){
                if( ! $reason_checks.filter(':checked').length ) {
                    app.alert.error(o.lang.claim.reason_checks); return;
                } else {
                    if( $reason_other.is(':visible') ) {
                        if($.trim($reason_other.find('textarea').val()).length<10) {
                            f.fieldError('comment', o.lang.claim.reason_other); return;
                        }
                    }
                }
                if (f.$field('captcha').length) {
                    if ( ! f.fieldStr('captcha').length) {
                        f.fieldError('captcha', o.lang.claim.captcha); return;
                    }
                }
                f.ajax(bff.ajaxURL('shops','shop-claim'), {id:o.id}, function(data, errors){
                    if(data && data.success) {
                        _this.hide(function(){
                            f.alertSuccess(o.lang.claim.success, {reset:true});
                            $reason_other.hide();
                            $f.find('.j-captcha').triggerHandler('click');
                        });
                    } else {
                        if( ! app.user.logined() && data.captcha) {
                            _refresh_catpcha();
                        }
                        f.fieldsError(data.fields, errors);
                        if( data && data.hasOwnProperty('later') && data.later ) _this.hide();
                    }
                });
            });

            _refresh_catpcha();
        }});

        // request
        var $requestForm = $container.find('#j-shop-view-request-form-block');
        $container.on('click', '#j-shop-view-request-form-toggler', function(e){ nothing(e);
            if ($requestForm.is(':visible')) return false;
            var f = app.form($requestForm.find('.j-form'), function(){
                if( ! f.checkRequired({focus:true}) ) return;
                f.ajax(o.request_url, {id: o.id}, function(data, errors){
                    if(data && data.success) {
                        f.alertSuccess(o.lang.request.success, {reset:true});
                    } else {
                        f.fieldsError(data.fields, errors);
                    }
                });
            });
            bff.maxlength($('.j-description', $requestForm), {
                limit: 3000, cut: false,
                message: $('.j-description-maxlength', $requestForm),
                lang: {
                    left: o.lang.request.maxlength_symbols_left,
                    symbols: o.lang.request.maxlength_symbols.split(';')
                }
            });
            $requestForm.show();
            return false;
        });

        // hook: init
        bff.hook('shops.view.init', $container, o);
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('shops.view.settings', $.extend(o, options || {}));
            $(function(){
                init();
            });
        }
    };
}());