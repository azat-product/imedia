/**
 * Кабинет: настройки
 */
var jMySettings = (function(){
    var inited = false, o = {lang:{},url_settings:'',tab:'',geoMapZoom:15},
        geo = {$block:0,addr:{$block:0,map:{},editor:{},lastQuery:''}},
        $cont, avatarUploader;

    function init()
    {
        $cont = $('#j-my-settings');

        // blocks togglers
        $cont.on('click', '.j-block-toggler', function(e){ nothing(e);
            toggleBlock($(this).data('block'), true, true);
        });

        // contacts (can be off)
        if ($cont.find('.j-form-contacts').length)
        {
            // avatar uploader
            avatarUploader = new qq.FileUploaderBasic({
                button: $cont.find('#j-my-avatar-upload').get(0),
                action: o.url_settings,
                params: {hash: app.csrf_token, act: 'avatar-upload'},
                uploaded: 0, multiple: false, sizeLimit: o.avatarMaxsize,
                allowedExtensions: ['jpeg','jpg','png','gif'],
                onSubmit: function(id, fileName) {
                    setAvatarImg(false,true);
                },
                onComplete: function(id, fileName, data) {
                    if(data && data.success) {
                        setAvatarImg(data[o.avatarSzNormal]);
                    } else {
                        setAvatarImg(false,false);
                        if(data.errors) {
                            app.alert.error(data.errors, {title: o.lang.ava_upload});
                        }
                    }
                    return true;
                },
                messages: o.lang.ava_upload_messages,
                showMessage: function(message, code) {
                    app.alert.error(message, {title: o.lang.ava_upload});
                }
            });

            // phones
            phonesInit(o.phonesLimit, o.phonesData);

            // geo
            geo.fn = (function(){
                geo.$block = $cont.find('#j-my-geo');
                //addr
                geo.addr.$block = geo.$block.find('#j-my-geo-addr');
                geo.addr.$addr = geo.addr.$block.find('#j-my-geo-addr-addr');
                geo.addr.$lat = geo.addr.$block.find('#j-my-geo-addr-lat');
                geo.addr.$lon = geo.addr.$block.find('#j-my-geo-addr-lon');
                geoMapInit();

                return {
                    onCity: function(cityID, ex)
                    {
                        if( ! ex.changed ) return;
                        if(ex.title.length > 0) {
                             geoMapSearch();
                        }
                    }
                };
            }());

            // contacts form
            app.form($cont.find('.j-form-contacts'), function(){
                var f = this;
                if( ! f.checkRequired({focus:true}) ) return;
                f.ajax(o.url_settings,{},function(data,errors){
                    if(data && data.success) {
                        f.alertSuccess(o.lang.saved_success);
                        f.$field('name').val(data.name);
                    } else {
                        f.fieldsError(data.fields, errors);
                    }
                });
            }, {noEnterSubmit:true});
        }

        // social form
        socialButtonsInit();

        // enotify form
        app.form($cont.find('.j-form-enotify'), false, {
            onInit: function($f){
                var f = this;
                var $checks = $f.find('.j-my-enotify-check');
                $checks.on('click', function(){
                    if( f.isProcessing() ) return false;
                    f.ajax(o.url_settings,{},function(resp,errors){
                        if(resp && resp.success) {
                            $checks.prop({readonly:false});
                        } else {
                            f.alertError(errors);
                        }
                    });
                    return true;
                });
            }
        });

        // pass form
        app.form($cont.find('.j-form-pass'), function(){
            var f = this;
            if( ! f.checkRequired({focus:true}) ) return;
            if( f.fieldStr('pass1') != f.fieldStr('pass2') ) {
                f.fieldsError(['pass2'], o.lang.pass_confirm);
                return;
            }
            f.ajax(o.url_settings,{},function(data,errors){
                if(data && data.success) {
                    f.alertSuccess(o.lang.pass_changed, {reset:true});
                } else {
                    f.fieldsError(data.fields, errors);
                }
            });
        });

        // phone form
        var $phoneForm = $cont.find('.j-form-phone');
        if ($phoneForm.length) {
            var phoneInput = app.user.phoneInput($phoneForm.find('.j-phone-number'));
            var $phoneStep1 = $phoneForm.find('.j-phone-change-step1');
            var $phoneStep2 = $phoneForm.find('.j-phone-change-step2');
            var phoneStep = 1;
            function phoneFormSteps(step2)
            {
                phoneStep = (step2 ? 2 : 1);
                if (step2) {
                    $phoneStep1.hide();
                    $phoneStep2.show();
                } else {
                    $phoneStep1.show();
                    $phoneStep2.hide();
                }
            }
            var phoneForm = app.form($phoneForm, function(){
                var f = this;
                if (!f.checkRequired({focus:true})) return;
                if (f.fieldStr('phone') === f.fieldStr('phone0')) {
                    return;
                }
                if (phoneStep === 1) {
                    if (f.fieldStr('phone').length < 9) {
                        f.$field('phone').focus(); return;
                    }
                    f.ajax(o.url_settings,{step:'code-send'},function(data,errors) {
                        if (data && data.success) {
                            phoneFormSteps(true);
                            $phoneStep2.find('.j-phone-change-code').val('').focus();
                            f.alertSuccess(o.lang.phone_code_sended);
                        } else {
                            f.fieldsError(data.fields, errors);
                        }
                    });
                } else if (phoneStep === 2) {
                    f.ajax(o.url_settings,{step:'finish'},function(data,errors) {
                        if (data && data.success) {
                            phoneFormSteps(false);
                            phoneInput.reset();
                            f.$field('phone0').val(data.phone);
                            f.alertSuccess(o.lang.phone_changed);
                        } else {
                            f.fieldsError(data.fields, errors);
                        }
                    });
                }
            });
            $phoneForm.on('click', '.j-phone-change-repeate', function(e){ nothing(e);
                phoneStep = 1;
                phoneForm.submit();
                phoneStep = 2;
            });
            $phoneForm.on('click', '.j-phone-change-back', function(e){ nothing(e);
                phoneFormSteps(false);
            });
        }

        // email form
        app.form($cont.find('.j-form-email'), function(){
            var f = this;
            if( ! f.checkRequired({focus:true}) ) return;
            if( ! bff.isEmail( f.fieldStr('email') ) ) {
                f.fieldsError(['email'], o.lang.email_wrong);
                return;
            }
            if( f.fieldStr('email') === f.fieldStr('email0') ) {
                f.fieldsError(['email'], o.lang.email_diff);
                return;
            }
            f.ajax(o.url_settings,{},function(data,errors){
                if(data && data.success) {
                    if(data.msg){
                        f.alertSuccess(data.msg);
                    }
                } else {
                    f.fieldsError(data.fields, errors);
                }
            });
        });

        // destroy form
        app.form($cont.find('.j-form-destroy'), function(){
            var f = this;
            if( ! f.checkRequired({focus:true}) ) return;
            f.ajax(o.url_settings,{},function(data,errors){
                if(data && data.success) {
                    f.alertSuccess(data.message_success, {reset:true});
                    setTimeout(function(){
                        bff.redirect(data.redirect);
                    }, 1500);
                } else {
                    f.fieldsError(data.fields, errors);
                }
            });
        });

        $cont.find('.j-block-contacts').on('toggle-show', function(){
            geoMapInit();
        });
        $cont.find('.j-block-shop').on('toggle-show', function(){
            if(jShopsForm && jShopsForm.onBlockShow){
                jShopsForm.onBlockShow();
            }
        });

        if (o.tab.length) {
            toggleBlock(o.tab, true, false);
        }

    }

    function setAvatarImg(img, bProgress)
    {
        var $img = $cont.find('#j-my-avatar-img');

        if(bProgress){
            $img.parent().addClass('hidden').after(o.uploadProgress);
        }else{
            $img.parent().removeClass('hidden').next('.j-progress').remove();
        }

        if( img ) {
            $img.attr('src', img);
        } else {
            //
        }
    }

    function phonesInit(limit, phones)
    {
        var index  = 0, total = 0;
        var $block = $cont.find('#j-my-phones');

        function add(value)
        {
            if(limit>0 && total>=limit) return;
            index++; total++;
            value = (value ? value.replace(/"/g, "&quot;") : '');
            var tpl = $cont.find('.j-tpl-user-phones');
            var plus = (total==1);
            if (tpl.length) {
                $block.append(bff.tmpl(tpl.html(), {value:value, index:index, total:total, plus:plus, o:o}));
            } else {
                $block.append('<div class="i-formpage__contacts__item">'+
                                   '<div class="input-prepend">'+
                                        '<span class="add-on"><i class="ico ico__phone-dark"></i></span>'+
                                        '<input type="tel" maxlength="30" name="phones['+index+']" value="'+value+'" class="input-large" placeholder="'+ o.lang.phones_tip+'" />'+
                                   '</div>'+
                                    (plus ? '&nbsp;<a class="pseudo-link-ajax j-plus" href="#"><small>'+ o.lang.phones_plus+'</small></a>':'')+
                              '</div>');
            }
        }

        $block.on('click', '.j-plus', function(e){ nothing(e);
            add('');
        });

        phones = phones || {};
        for(var i in phones) {
            if( phones.hasOwnProperty(i) ) {
                add(phones[i].v);
            }
        }
        if( ! total && limit > 0 ) {
            add('');
        }
    }

    function geoMapInit()
    {
        if(geo.addr.inited) return;
        if( ! $('#j-my-geo-addr-map').is(':visible')) return;
        geo.addr.inited = 1;
        geo.addr.map = app.map('j-my-geo-addr-map', [geo.addr.$lat.val(), geo.addr.$lon.val()], function(map){
            geo.addr.mapEditor = bff.map.editor();
            geo.addr.mapEditor.init({
                map: map, version: '2.1',
                coords: [geo.addr.$lat, geo.addr.$lon],
                address: geo.addr.$addr,
                addressKind: 'house',
                updateAddressIgnoreClass: 'typed'
            });

            geo.addr.$addr.on('change keyup input', $.debounce(function(){
                if( ! $.trim(geo.addr.$addr.val()).length ) {
                    geo.addr.$addr.removeClass('typed');
                } else {
                    geo.addr.$addr.addClass('typed');
                    geoMapSearch();
                }
            }, 700));
            geoMapSearch();
        }, {zoom: o.geoMapZoom});
    }

    function geoMapSearch()
    {
        if( ! geo.addr.mapEditor) { return; }

        var $country = geo.$block.find('.j-geo-city-select-country');
        var country = ($country.is('select') ? $country.find('option:selected').text() :
                       $country.val());
        var query = [country];
        var city = $.trim( geo.$block.find('.j-geo-city-select-ac').val() );
        if(city) query.push(city);
        var addr = $.trim( geo.addr.$addr.val() );
        if(addr) query.push(addr);
        query = query.join(', ');
        if( geo.addr.lastQuery == query ) return;
        geo.addr.mapEditor.search( geo.addr.lastQuery = query, false, function(){
            geo.addr.mapEditor.centerByMarker();
        } );
    }

    function socialButtonsInit()
    {
        var popup;
        $cont.on('click', '.j-my-social-btn', function(e){ nothing(e);
            var $btn = $(this);
            var m = $btn.metadata(); if( ! m || ! m.hasOwnProperty('provider') ) return;
            if( $btn.hasClass('active') ) {
                bff.ajax(o.url_settings, {act:'social-unlink',provider:m.provider,hash:app.csrf_token}, function(resp, errors){
                    if( resp && resp.success ) {
                        $btn.removeClass('active');
                    } else {
                        app.alert.error(errors);
                    }
                });
                return;
            }
            m = $.extend({w: 450, h: 380}, m || {});
            if(popup !== undefined) popup.close();
            popup = window.open(o.url_social + m.provider+'?ret='+encodeURIComponent(o.url_settings), "u_login_social_popup",
                "width=" + m.w + "," +
                "height=" + m.h + "," +
                "left=" + ( (app.$W.width() - m.w) / 2 ) + "," +
                "top=" + ( (app.$W.height() - m.h) / 2 ) + "," +
                "resizable=yes,scrollbars=no,toolbar=no,menubar=no,location=no,directories=no,status=yes");
            popup.focus();
        });
    }

    function toggleBlock(key, open, scrollTo)
    {
        var $toggler = $cont.find('.j-block-toggler[data-block="'+key+'"]');
        if ( ! $toggler.length) return;
        var $block = $cont.find('.j-block-'+key);
        if ( ! $block.length) return;

        if (open) {
            if ($toggler.hasClass('active')) {
                toggleBlock(key, false);
                return;
            }
            var $togglerPrev = $cont.find('.j-block-toggler.active');
            if ($togglerPrev.length) {
                toggleBlock($togglerPrev.data('block'), false);
            }
            $block.removeClass('hide');
            $toggler.addClass('pseudo-link-ajax active')
                .find('.j-icon').toggleClass('fa-chevron-down fa-chevron-up');
            if(scrollTo) {
                $.scrollTo($toggler, {duration: 400, offset: -20, axis: 'y', onAfter:function(){
                    $block.trigger('toggle-show');
                }});
            }else{
                $block.trigger('toggle-show');
            }
        } else {
            $block.addClass('hide');
            $block.trigger('toggle-hide');
            $toggler.removeClass('pseudo-link-ajax active')
                .find('.j-icon').toggleClass('fa-chevron-down fa-chevron-up');
        }
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('users.my.settings', $.extend(o, options || {}));
            $(function(){
                init();
                bff.hook('users.my.init', o);
            });
        },
        onCitySelect: function(cityID, cityTitle, ex){
            geo.fn.onCity(cityID, ex);
        }
    };
}());