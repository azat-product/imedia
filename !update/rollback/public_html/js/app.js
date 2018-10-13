// imagesLoaded
(function(c,n){var k="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";c.fn.imagesLoaded=function(l){function m(){var b=c(h),a=c(g);d&&(g.length?d.reject(e,b,a):d.resolve(e));c.isFunction(l)&&l.call(f,e,b,a)}function i(b,a){b.src===k||-1!==c.inArray(b,j)||(j.push(b),a?g.push(b):h.push(b),c.data(b,"imagesLoaded",{isBroken:a,src:b.src}),o&&d.notifyWith(c(b),[a,e,c(h),c(g)]),e.length===j.length&&(setTimeout(m),e.unbind(".imagesLoaded")))}var f=this,d=c.isFunction(c.Deferred)?c.Deferred():
0,o=c.isFunction(d.notify),e=f.find("img").add(f.filter("img")),j=[],h=[],g=[];e.length?e.bind("load.imagesLoaded error.imagesLoaded",function(b){i(b.target,"error"===b.type)}).each(function(b,a){var e=a.src,d=c.data(a,"imagesLoaded");if(d&&d.src===e)i(a,d.isBroken);else if(a.complete&&a.naturalWidth!==n)i(a,0===a.naturalWidth||0===a.naturalHeight);else if(a.readyState||a.complete)a.src=k,a.src=e}):m();return d?d.promise(f):f}})(jQuery);

// browser detector
(function(){var a={init:function(){this.browser=this.searchString(this.dataBrowser)||"An unknown browser";this.version=this.searchVersion(navigator.userAgent)||this.searchVersion(navigator.appVersion)||"an unknown version";this.OS=this.searchString(this.dataOS)||"an unknown OS"},searchString:function(e){for(var b=0;b<e.length;b++){var c=e[b].string;var d=e[b].prop;this.versionSearchString=e[b].versionSearch||e[b].identity;if(c){if(c.indexOf(e[b].subString)!=-1){return e[b].identity}}else{if(d){return e[b].identity}}}},searchVersion:function(c){var b=c.indexOf(this.versionSearchString);if(b==-1){return}return parseInt(c.substring(b+this.versionSearchString.length+1))},dataBrowser:[{string:navigator.userAgent,subString:"Chrome",identity:"Chrome"},{string:navigator.userAgent,subString:"OmniWeb",versionSearch:"OmniWeb/",identity:"OmniWeb"},{string:navigator.vendor,subString:"Apple",identity:"Safari",versionSearch:"Version"},{prop:window.opera,identity:"Opera"},{string:navigator.vendor,subString:"iCab",identity:"iCab"},{string:navigator.vendor,subString:"KDE",identity:"Konqueror"},{string:navigator.userAgent,subString:"Firefox",identity:"Firefox"},{string:navigator.vendor,subString:"Camino",identity:"Camino"},{string:navigator.userAgent,subString:"Netscape",identity:"Netscape"},{string:navigator.userAgent,subString:"MSIE",identity:"msie",versionSearch:"MSIE"},{string:navigator.userAgent,subString:"Gecko",identity:"Mozilla",versionSearch:"rv"},{string:navigator.userAgent,subString:"Mozilla",identity:"Netscape",versionSearch:"Mozilla"}],dataOS:[{string:navigator.platform,subString:"Win",identity:"Windows"},{string:navigator.platform,subString:"Mac",identity:"Mac"},{string:navigator.userAgent,subString:"iPhone",identity:"iPhone/iPod"},{string:navigator.platform,subString:"Linux",identity:"Linux"}]};a.init();window.$.client={os:a.OS,browser:a.browser,ver:a.version}})();

var app =
{
    _popups: {}, h: (window.history && window.history.pushState), csrf_token:'',
    devices: {desktop:'desktop',tablet:'tablet',phone:'phone'}, $B:0, $D:0, $W:0,
    uid: function(){ return ''; },
    init: function(o)
    {
        //options
        for(var k in o) app[k] = o[k];
        app.$B = $('body'); app.$D = $(document); app.$W = $(window);
        //browser
        app.$B.addClass( $.client.browser.toLowerCase() ).
               addClass( $.client.browser.toLowerCase()+$.client.ver ).
               addClass( $.client.os.toLowerCase() );
        //popups
        app.$D.on('click keydown', function(e){
            if(e.type == 'keydown') { if( e.keyCode == 27 ) app.popupsHide(false, e); }
            else { app.popupsHide(false, e); }
        });
        var w_width = app.$W.width();
        app.$W.resize($.debounce(function(){
            if (w_width != app.$W.width()) { app.popupsHide(); w_width = app.$W.width(); }
        }, 300, true));
        //device
        this.device = (function(){
            var cookieKey = app.cookiePrefix+'device', current = o.device;
            var deviceOnResize = $.debounce(function(e){ e = e || {type:'onload'};
                if (app.hasOwnProperty('deviceNoResponsive') && app.deviceNoResponsive) return;
                var width = window.innerWidth;
                var width_device = ( width >= 980 ? app.devices.desktop : ( width >= 768 ? app.devices.tablet : app.devices.phone ) );
                if( width_device!=current || e.type == 'focus' ) {
                    bff.cookie(app.cookiePrefix+'device',(current = width_device),{expires:200,path:'/',domain:'.'+app.host});
                    if(e.type == 'resize') app.$W.trigger('app-device-changed', [width_device]);
                    //var img = (window.Image ? (new Image()) : document.createElement('img'));
                    //img.src = '/index.php?bff=device&type='+(current = width_device)+'&r='+Math.random();
                }
            }, 600);
            app.$W.on('focus resize', deviceOnResize); deviceOnResize();
            return function(check){ return ( check||false ? check === current : current ); };
        }());
        this.csrf_token = app.$B.prev().find('meta[name="csrf_token"]').attr('content') || '';
        bff.map.setType(app.mapType);
        //user
        app.user.init({logined:app.logined});
        //fav
        app.$D.on('click', '.j-i-fav', function(e){ nothing(e);
            var $el = $(this).blur(), data = $.extend({id:0},$el.metadata()||{});
            app.items.fav(data.id, function(added){
                $el.toggleClass('active', added).
                    attr('title', (added ? app.lang.fav_out : app.lang.fav_in) ).
                    find('.j-i-fav-icon').toggleClass('fa-star', added).toggleClass('fa-star-o', !added);
            });
            return false;
        });
        //scrolltop
        var $scrollTop = $('#j-scrolltop').click(function(){
            $('body,html').animate({
                scrollTop: 0
            }, 400);
            return false;
        });
        app.$W.scroll(function(){
            if(app.device() != app.devices.desktop) return;
            if ($(this).scrollTop() > 1000) {
                $scrollTop.fadeIn();
            } else {
                $scrollTop.fadeOut();
            }
        });

    },
    user: (function(){
        var i = false, settKey, settData, $menu;
        return {
            init: function(o) {
                if(i) return; i = true;
                settKey = app.cookiePrefix+'usett';
                settData = intval( bff.cookie(settKey) || 0);
                $menu = ( app.logined ? $('#j-header-user-menu') : $('#j-header-guest-menu') );
            },
            logined: function() {
                return app.logined;
            },
            counter: function(key, value) {
                if( ! app.logined && key == 'fav' ) {
                    $menu.find('.j-cnt-fav').text(value); return;
                }
                if( ! key || ! app.logined ) return;
                var $counter = $menu.find('.j-cnt-'+key);
                if( $counter.length ) $counter.text(value).removeClass('hide');
            },
            settings: function(key, save)
            {
                if('undefined' != typeof save) {
                    var cs = {expires:350,domain:'.'+app.host,path:'/'};
                    if(save === true)  {
                        if(!(settData & key))
                            bff.cookie(settKey, (settData |= key), cs);
                    } else {
                        bff.cookie(settKey, (settData -= key), cs);
                    }
                } else {
                    return (settData & key);
                }
            },
            phoneInput: function(bl)
            {
                bl = $(bl); if (!bl.length || bl.hasClass('j-inited')) return false; bl.addClass('j-inited');
                var input = bl.find('.j-phone-number-input'),
                    icon = bl.find('[data-type="country-icon"]'), iconClass = 'country-icon country-icon-',
                    countryList = bl.find('.j-phone-number-country-list'),
                    countryItems = bl.find('.j-country-item'), countryData = {},
                    countryPopup = app.popup('phone-country-list', countryList, icon);
                countryItems.on('click', function(e){ nothing(e);
                    var item = $(this), data = item.metadata(), itemP = item.parent();
                    if (itemP.hasClass('active')) { countryPopup.hide(); input.focus(); return; }
                    countryItems.parent().removeClass('active'); itemP.addClass('active');
                    icon.attr('class', iconClass + data.cc);
                    countryPopup.hide();
                    input.val(data.pc).focus();
                }).each(function(){ var c = $(this).metadata(); countryData[c.id] = c; });
                var prevQ = '';
                input.on('input paste keyup keydown change', function(e){
                    var Q = input.val(); if (prevQ == Q) return;
                    if (Q = Q.replace(/[^\d]/g, "")) Q = '+' + Q;
                    var found = [], iconCode = '', maxNum = Number.MAX_VALUE;
                    for (var i in countryData) {
                        var code = countryData[i].pc;
                        if (Q.length > code.length ?
                            Q.substr(0, code.length) == code :
                            code.substr(0, Q.length) == Q) {
                            found.push(i);
                            if (code.length < maxNum || Q.substr(0, code.length) == code) {
                                icon.attr('class', iconClass);
                                maxNum = code.length;
                                if (Q) {
                                    icon.attr('class', iconClass + countryData[i].cc);
                                }
                            }
                        }
                    }
                    if (found.length == 0) { input.val(prevQ); return false; }
                    prevQ = Q; if (Q == '+') icon.attr('class', iconClass);
                    input.val(Q);
                    app.inputError(input, false);
                }).keyup();

                return {
                    input: input,
                    reset: function(){
                        input.val(input.data('default')).keyup();
                    },
                    popup: countryPopup,
                };
            }
        };
    }()),
    popup: function(id, popupSelector, linkSelector, o)
    {
        if(id && app._popups[id]) { return app._popups[id]; } o = o || {};
        var $popup = $(popupSelector); if(!$popup.length) return false;
        var $popup_link = $(linkSelector); if(!$popup_link.length){ $popup_link = false; o.pos = false; }
        o = $.extend({bl:false,scroll:false,pos:false,top:true}, o || {});

        var visible = false, focused = false, inited = false;
        if(o.pos!==false) {
            o.pos = $.extend({left:0,top:0}, (o.pos===true?{}:o.pos));
        }
        var self = {
            o:o,
            init: function() {
                if(inited) return; inited = true;
                if(o.onInit) o.onInit.call(self, $popup);
                $popup.on('click', 'a.close,.j-close', function(e){ nothing(e); self.hide(); return false; } );
            },
            show: function(event){ 
                self.init();
                if(event) nothing(event);
                if(visible) return false;
                if(o.pos!==false) {
                    var pos = $popup_link.position();
                    if (o.pos.hasOwnProperty('minRightSpace')) {
                        var rightSpace = $popup_link.parent().width() - (pos.left + $popup_link.width());
                        if (rightSpace < o.pos.minRightSpace) {
                            pos = {right: rightSpace - 20, top: o.pos.top + pos.top, left: 'auto'}; // open left
                        } else {
                            pos = {left: o.pos.left + pos.left, top: o.pos.top + pos.top, right: 'auto'}; // open right
                        }
                    } else {
                        pos = {left: o.pos.left + pos.left, top: o.pos.top + pos.top};
                    }
                    $popup.css(pos);
                }
                focused = false; visible = true;
                if(o.onShow) o.onShow.call(self, $popup);
                else $popup.fadeIn(100);
                if(o.scroll) $.scrollTo($popup, {offset: -100, duration:500, axis: 'y'});
                app.popupsHide(id, event); // hide popups, except this one ($popup)
                if(o.bl) app.busylayer();
                return true;
            },
            hide: function(callback) {
                callback = callback || $.noop;
                if( ! visible) return false;
                if(o.onHide) { o.onHide.call(self, $popup); callback(); }
                else $popup.fadeOut(300, callback);
                focused = visible = false;
                if(o.bl) app.busylayer(false);
                return true;
            },
            toggle: function(event) {
                return ( visible ? self.hide() : self.show(event) );
            },
            isVisible: function(){ return visible; },
            isFocused: function(){ return focused; },
            setFocused: function(state) { focused = state; },
            isInited: function(){ return inited; },
            getPopup: function(){ return $popup; },
            getLink: function(){ return $popup_link; },
            setOptions: function(opts){ o = opts || {}; }
        };

        $popup.on('mouseleave mouseenter', function(e){ focused = !( e.type == 'mouseleave' ); });

        if( $popup_link ) {
            $popup_link.on('click', function(e){
                self.toggle(e);
                nothing(e); return false;
            });
        }
        $popup.data('popup-id', id);
        return ( app._popups[id] = self );
    },
    popupsHide: function(exceptID, event)
    {
        $.each(this._popups, function(id,p){
            if(exceptID !== false) {
                if(id === exceptID) return;
                p.hide();
            } else if(p.isVisible() && ! p.isFocused() && p.getPopup().has(event.target).length === 0 ) {
                p.hide();
            }
        });
    },
    busylayer: function(toggle, callback)
    {
        callback = callback || $.noop;
        var id = 'busyLayer', $el = $('#'+id);
        if( ! $el.length) {
            app.$B.append('<div id="'+id+'" class="l-busy-layer hide"></div>');
            $el = $('#'+id);
        }
        if( $el.is(':visible') || toggle === true ) {
            if(toggle === false) {
                $el.fadeOut('fast', function(){ $el.hide(); callback(); });
            }
            return $el;
        }
        $el.height( app.$D.height() - 10 ).fadeIn('fast', callback);
        return $el;
    },
    alert: (function()
    {
        var timer = false, o_def = {title:false, hide:5000},
            $block, $wrap, $title, $message, type_prev;

        $(function(){
            $block = $('#j-alert-global'); if( ! $block.length) return;
            $wrap = $block.find('.j-wrap');
            $title = $block.find('.j-title');
            $message = $block.find('.j-message');
            $block.on('click', '.close', function(e){ nothing(e); _hide(); return false; } );
        });

        function _show(type, msg, o)
        {
            if(timer) clearTimeout(timer);
            do {
                if(!msg) break;
                if($.isArray(msg)) { if(!msg.length) break; msg = msg.join(', '); } // array
                else if ($.isPlainObject(msg) ) { // object
                    var res = []; for(var i in msg) res.push(msg[i]);
                        msg = res.join(', ');
                } else { } // string

                o = $.extend({}, o_def, o||{});
                $wrap.removeClass('alert-'+type_prev).addClass('alert-'+type); type_prev = type;
                if(o.title) $title.html(o.title).removeClass('hide');
                else $title.html('').addClass('hide');
                if(msg.indexOf('&lt;')!=-1) msg = msg.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                $message.html(msg);
                $block.fadeIn(300);
                if(o.hide!==false && o.hide >=0 ) { timer = setTimeout(function(){ _hide(); }, o.hide); }
                return;
            } while(false);
            _hide();
        }

        function _hide()
        {
            $block.fadeOut(1000);
        }

        return {
            error: function(msg, o)   { _show('error', msg, o); },
            info: function(msg, o)    { _show('info', msg, o); },
            success: function(msg, o) { _show('success', msg, o); },
            show: _show,
            hide: _hide,
            noHide: function() { if (timer) clearTimeout(timer); }
        };
    }()),
    inputError: function(field, show, _focus)
    {
        if($.isArray(field)) {
            $.each(field, function(i,v){
                app.inputError(v, show, _focus);
            });
            return false;
        }        
        var $field = $(field);
        if($field.length) {
            var $group = $field.closest('.control-group');
            if(show!==false) {
                if(_focus!==false) $field.focus();
                if( $group.length ) $group.addClass('error');
                else $field.addClass('input-error');
            } else {
                if( $group.length ) $group.removeClass('error');
                else $field.removeClass('input-error');
            }
        }
        return false;
    },
    form: function(formSelector, onSubmit, o)
    {
        o = $.extend({btn_loading:app.lang.form_btn_loading, onInit: $.noop, noEnterSubmit:false}, o||{});
        var $form, form, $btn, btnVal, inited = false, process = false;
        var classes = {input_error: 'input-error'};
        var self = {
            init: function(){
                if( inited ) return; inited = true;
                $form = $(formSelector);
                if( $form.length ) {
                    if(app.user.logined()) $form.append('<input type="hidden" name="hash" value="'+app.csrf_token+'" />');
                    $form.append('<input type="hidden" name="uufp" value="'+bff.fp()+'" />');
                    form = $form.get(0);
                    $btn = $form.find('button.j-submit:last');
                    if(o.onInit) o.onInit.call(self, $form);
                    if(onSubmit && onSubmit!==false) {
                        $form.submit(function(e){
                            nothing(e);
                            if(self.isProcessing()) return;
                            onSubmit.call(self, $form);
                        });
                    }
                    // prevent enter submit
                    if (o.noEnterSubmit) {
                        $form.bind('keyup keypress', function(e) {
                            if ((e.keyCode || e.which) == 13 && ! $(e.target).is('textarea')) {
                                e.preventDefault();
                                return false;
                            }
                        });
                    }
                }
            },
            getForm: function(){ return $form; },
            processed: function(yes){ process = yes;
                if($btn && $btn.length) {
                    if(yes) { btnVal = $btn.val(); $btn.prop({disabled:true}).val(o.btn_loading);
                    } else { $btn.removeProp('disabled').val(btnVal); }
                }
            },
            isProcessing: function(){ return process; },
            field: function(name){ return form.elements[name]; },
            $field: function(name){ return $(form.elements[name]); },
            fieldStr: function(name){ return ( form.elements[name] ? $.trim(form.elements[name].value) : '' ); },
            fieldError: function(fieldName, message, opts){
                opts = opts||{};
                if( ! opts.hasOwnProperty('title') && ! app.device(app.devices.phone) ) opts.title = app.lang.form_alert_errors;
                self.fieldsError([fieldName], [message], opts);
            },
            fieldsError: function(fieldsNames, message, opts){
                opts = $.extend({title:false,focus:true,scroll:false}, opts||{});
                self.fieldsResetError();
                var fields = [];
                for(var i in fieldsNames) {
                    if(form.elements[fieldsNames[i]]) {
                        fields.push(form.elements[fieldsNames[i]]);
                    }
                }
                var fieldFirst;
                if(fields.length > 0) { // mark fields
                    fieldFirst = fields[0];
                    app.inputError(fields, true, false);
                }
                if(message) app.alert.error(message, {title:opts.title});
                if(fieldFirst) { // focus & scroll to first field
                    if(opts.scroll) $.scrollTo(fieldFirst, {offset: -150, duration: 500, axis: 'y'});
                    if(opts.focus && app.device(app.devices.desktop)) fieldFirst.focus();
                }
            },
            fieldsResetError: function(){ app.inputError(form.elements, false); },
            checkRequired: function(opts){
                self.fieldsResetError();
                var fields = [];
                $('.j-required:visible,.j-required[type="hidden"]', form).each(function(){
                    var $this = $(this), empty;
                    if( ! $this.is(':input') ) $this = $this.find(':input:visible:first');
                    if( $this.is(':checkbox') ) {
                        empty = ( ! $form.find('input:checkbox[name="'+$this.attr('name')+'"]:checked').length );
                    } else if( $this.is(':radio') ) {
                        empty = ( ! $form.find('input:radio[name="'+$this.attr('name')+'"]:checked').length );
                    } else {
                        empty = ( $this.val() == 0 || ! $.trim($this.val()).length );
                    }
                    if(empty) { fields.push( $this.attr('name') ); }
                });
                if( fields.length > 0 ) {
                    opts = $.extend({
                        focus: false,
                        title: (app.device(app.devices.phone) ? false : app.lang.form_alert_errors)
                    }, opts||{});
                    self.fieldsError(fields, app.lang.form_alert_required, opts);
                }
                return ( ! fields.length );
            },
            alertError: function(message, opts){
                app.alert.error(message, opts);
            },
            alertSuccess: function(message, o){
                o = $.extend({reset:false}, o||{});
                self.fieldsResetError();
                if(o.reset) self.reset();
                app.alert.success(message, o);
            },
            buttonSuccess: function(btn, opts){
                if( ! btn) btn = $btn;
                opts = $.extend({text:false,revert:false,reset:false,speed:250}, opts || {});
                var button_text = btn.html(),
                    button_class = btn.attr('class'),
                    success_text = ( opts.text!==false ? opts.text : btn.next('.success-text').html() ),
                    popup = $form.parents('.box-shadow'),
                    revert = (opts.revert ? opts.revert : popup.length);

                btn.fadeTo(opts.speed, 0, function(){
                    btn.attr({
                        'class': 'success-send',
                        disabled: 'disabled'
                    }).html(success_text).fadeTo(opts.speed, 1);
                });

                if(revert) {
                    setTimeout(function(){
                        if(popup.length) {
                            app.popup( popup.data('popup-id') ).hide();
                        }
                        btn.fadeTo(opts.speed, 0, function(){
                            btn.attr({'class': button_class}).prop('disabled',false)
                                .html(button_text).fadeTo(opts.speed, 1);
                            if(opts.reset) form.reset();
                        });
                    }, 2000)
                } else {
                    if(opts.reset) form.reset();
                }
            },
            reset: function(){
                form.reset();
            },
            ajax: function(url, params, callback, $progress, opts){
                if(self.isProcessing()) return;
                if(bff.filter('app.form.ajax', false, self, url, params, callback, $progress, opts)) return;
                bff.ajax(url, $form.serialize()+'&'+ $.param(params||{}), callback, function(p){
                    self.processed(p);
                    if($progress && $progress.length) $progress.toggle();
                    bff.hook('app.form.ajax.progress', self, p);
                }, opts);
            },
            submit: function(){
                if(self.isProcessing()) return;
                onSubmit.call(self, $form);
            }
        };
        self.init();
        return self;
    },
    list: function(formSelector, o)
    {
        o = $.extend({onInit:$.noop, onSubmit:$.noop, onPopstate:$.noop, onProgress:$.noop, onDeviceChanged:$.noop, ajax:true,
                      url: document.location.pathname, submitOnDeviceChanged: true}, o||{});
        var $form;

        $form = $(formSelector);
        if( ! $form.length ) return;
        if(o.ajax) {
            var queryInitial = getQuery();
            app.$W.on('popstate',function(e){
                var loc = history.location || document.location;
                var query = loc.search.substr(1);
                if( query.length == 0 ) query = queryInitial;
                $form.deserialize(query, true);
                o.onPopstate.call(this, $form);
                onSubmit({popstate:true});
            });
        }

        app.$W.on('app-device-changed', function(e, device){
            if( ! o.submitOnDeviceChanged ) return;
            onSubmit({fade:false, deviceChanged:true, device:device});
        });

        o.onInit.call(this, $form);

        function onSubmit(ex, resetPage)
        {
            ex = $.extend({popstate:false, scroll:false, fade:true}, ex||{});
            if( resetPage ) onPage(1, false);
            var query = getQuery();
            if(o.ajax) {
                bff.ajax(o.url, query+'&hash='+app.csrf_token+'&device='+app.device(), function(response, errors){
                    if(response && response.success) {
                        o.onSubmit(response, ex);
                        if( ! ex.popstate) history.pushState(null, null, o.url+'?'+query);
                    } else {
                        app.alert.error(errors);
                    }
                }, function(p){
                    o.onProgress(p, ex);
                });
            } else {
                bff.redirect(o.url+'?'+query);
            }
        }

        function onPage(pageId, update)
        {
            pageId = intval(pageId);
            if( pageId <=0 ) pageId = 0;
            var $val = $form.find('[name="page"]:first');
            if( ! $val.length ) return;
            if( pageId && intval($val.val()) != pageId ) {
                $val.val(pageId);
                if(update!==false) onSubmit({scroll:true});
            }
            return $val.val();
        }

        function getQuery()
        {
            var query = [];
            $.each($form.serializeArray(), function(i, field) {
                if(field.value && field.value!=0 && field.value!='')
                    query.push( field.name+'='+encodeURIComponent(field.value) );
            });
            return query.join('&');
        }

        return {
            submit: onSubmit,
            page: onPage,
            getForm: function(){ return $form; },
            getQuery: getQuery,
            getURL: function(){ return o.url; }
        };
    },
    map: function(container, center, callback, o)
    {
        try {
            return bff.map.init(container, center, callback, o);
        } catch(e) {
            bff_report_exception(e.name + ":" + e.message);
        }
        return false;
    },
    items: (function() {
        var cookieData = false, cookieKey,
            favAlert = function(added){
                if(!added) return;
                var msg = app.lang.fav_added_msg, title = app.lang.fav_added_title;
                if(app.device(app.devices.phone)){ msg = title; title = false;}
                app.alert.success(msg,{title:title,hide:3500});
            };
        var self = {
            fav: function(id, callback) {
                callback = callback || $.noop;
                if(app.user.logined()) {
                    bff.ajax(bff.ajaxURL('bbs','item-fav'), {id:id,hash:app.csrf_token}, function(data, error){
                        if(data.success) { favAlert(data.added); callback(data.added); app.user.counter('fav', data.cnt); }
                        else app.alert.error(error);
                    });
                } else {
                    if(cookieData === false) {
                        cookieKey = (app.cookiePrefix+'fav');
                        cookieData = bff.cookie(cookieKey) || [];
                        if(typeof cookieData === 'string') {
                            cookieData = $.map(cookieData.split('.'), function(i){return intval(i);});
                        }
                    }
                    var i = $.inArray(id, cookieData), added = false;
                    if(i!=-1) {
                        cookieData.splice(i, 1);
                    } else {
                        if(cookieData.length >= 25) {
                            app.alert.error(app.lang.fav_limit);
                            callback(false); return;
                        }
                        cookieData.push(id); added = true;
                    }
                    favAlert(added);
                    callback(added);
                    app.user.counter('fav', cookieData.length);
                    bff.cookie(cookieKey, cookieData.join('.'), {expires: 2, path: '/', domain: '.'+app.host});
                }
            }
        };
        return self;
    }())
};