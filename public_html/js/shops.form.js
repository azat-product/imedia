var jShopsForm = (function(){
    var inited = false, o = {edit:true, lang:{}, url_submit:'', geoMapZoom:15}, $cont, form,
        geo = {$block:0,addr:{$block:0,map:{},editor:{},lastQuery:''}};

    function init()
    {
        $cont = $('#j-shops-form');
        var $logo = $cont.find('#j-shop-logo-preview');

        // logo upload
        var $logoFilename = $cont.find('#j-shop-logo-fn');
        var $logoDelete = $cont.find('#j-shop-logo-delete');
        var logoUpload = new qq.FileUploaderBasic({
            button: $cont.find('#j-shop-logo-upload').get(0),
            action: bff.ajaxURL('shops', '&ev=logo'),
            uploaded: 0, multiple: false, sizeLimit: o.logoMaxSize,
            allowedExtensions: ['jpeg','jpg','png','gif'],
            onSubmit: function(id, fileName) {
                var params = {hash: app.csrf_token, act: 'upload'};
                if( ! o.edit) params['tmp'] = $logoFilename.val();
                logoUpload.setParams(params);
                $logo.parent().addClass('hidden').after(o.uploadProgress);
            },
            onComplete: function(id, fileName, data) {
                $logo.parent().removeClass('hidden').next('.j-progress').remove();
                if(data && data.success) {
                    $logo.attr('src', data.preview);
                    if( ! o.edit) $logoFilename.attr('value', data.filename);
                    $logoDelete.show();
                } else {
                    if(data.errors) {
                        app.alert.error(data.errors, {title: o.lang.logo_upload});
                    }
                }
                return true;
            },
            messages: o.lang.logo_upload_messages,
            showMessage: function(message, code) {
                app.alert.error(message, {title: o.lang.logo_upload});
            }
        });
        // logo delete
        $logoDelete.on('click', function(e){ nothing(e);
            $logoDelete.hide();
            bff.ajax(bff.ajaxURL('shops', 'delete&ev=logo'),
                {fn:$logoFilename.val(), hash: app.csrf_token}, function(data, errors){
                if(data && data.success) {
                    $logo.attr('src', data.preview);
                } else {
                    $logoDelete.show();
                    if(errors) app.alert.error(errors);
                }
            });
        });

        // cats
        if(o.catsOn) {
            catsInit();
        }

        // geo
        geo.fn = (function(){
            geo.$block = $cont.find('#j-shop-geo');
            //addr
            geo.addr.$block = geo.$block.find('#j-shop-geo-addr');
            geo.addr.$addr = geo.addr.$block.find('.j-shop-geo-addr-addr');
            geo.addr.$lat = geo.addr.$block.find('#j-shop-geo-addr-lat');
            geo.addr.$lon = geo.addr.$block.find('#j-shop-geo-addr-lon');

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

        // phones
        phonesInit(o.phonesLimit, o.phonesData);

        // social links
        socialLinksInit(o.socialLinksLimit, o.socialLinksData);

        // form
        form = app.form($cont, function(){
            if( ! form.checkRequired({focus:true}) ) return;
            if (!bff.filter('shops.form.beforeSubmit', true, form, o)) {
                return false;
            }
            form.ajax(o.url_submit,{},function(data,errors){
                if(data && data.success) {
                    if(data.hasOwnProperty('redirect')) {
                        bff.redirect(data.redirect);
                    } else {
                        form.alertSuccess(o.lang.saved_success);
                        if(data.hasOwnProperty('refill')) {
                            for(var i in data.refill) {
                                form.$field(i).val(data.refill[i]);
                            }
                        }
                    }
                } else {
                    form.fieldsError(data.fields, errors);
                }
            });
        }, {noEnterSubmit:true});

        // hook: init
        bff.hook('shops.form.init', form, o);

        if (o.titlesLang) {
            langInit();
        }
    }

    function catsInit()
    {
        var $block = $cont.find('#j-shop-cats');
        var $popup = $('.j-cat-select-popup', $block), popup;
        var $selected = $('.j-cat-selected-items', $block),
            $last = $('.j-cat-selected-last', $block);
        var cache = {}, total = 0, limit = o.catsLimit;

        function selectCategory(device, $link)
        {
            var data = $link.metadata();
            if ($selected.find('.j-value[value="'+(data.id)+'"]').length) return;
            if (limit>0 && total>=limit) return; total++;

            var id = [], title = [], parentData = {}, parentID = data.pid, currentID = data.id;
            while (cache[device].hasOwnProperty(parentID)) {
                var parentCats = cache[device][parentID].cats;
                for (var i in parentCats) {
                    if (parentCats[i].id == currentID) {
                        parentData = parentCats[i];
                        id.unshift(parentCats[i].id);
                        title.unshift(parentCats[i].t);
                    }
                }
                currentID = parentID;
                parentID = cache[device][parentID].pid;
            }

            popup.hide();

            selectCategoryItem(data.id, title.join(' &raquo; '), parentData.i);
        }

        function selectCategoryItem(id, title, icon)
        {
            $last.val(id);
            var tpl = $cont.find('.j-tpl-category-item');
            if (tpl.length) {
                $selected.append(bff.tmpl(tpl.html(), {id:id, title:title, icon:icon, o:o}));
            } else {
                $selected.append('<div class="controls j-cat-selected-item">'+
                        '<div class="i-formpage__catselect rel">'+
                            '<div class="i-formpage__catselect__done">'+
                                '<input type="hidden" name="cats[]" class="j-value" value="'+id+'" />'+
                                '<img src="'+icon+'" class="abs" alt="" />'+
                                '<div class="i-formpage__catselect__done_cat">'+
                                    '<a href="javascript:void(0);">'+title+'</a> <a href="#" class="j-cat-selected-item-delete"><i class="icon-remove"></i></a>'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                    '</div>');
            }
        }

        function initPopup(device)
        {
            var $st1 = $popup.find('.j-cat-select-step1-'+device);
            var $st2 = $popup.find('.j-cat-select-step2-'+device);
            cache[device] = {};
            cache[device][o.catsRootID] = {html:'',cats:o.catsMain,pid:0};
            function st2View(parentID, fromStep1)
            {
                if( cache[device].hasOwnProperty(parentID) ) {
                    $st2.html(cache[device][parentID].html);
                    if(fromStep1) $st2.add($st1).toggleClass('hide');
                    if(app.device(app.devices.phone)) {
                        $.scrollTo($st2, {offset: -10, duration: 400, axis: 'y'});
                    }
                } else {
                    bff.ajax(bff.ajaxURL('shops','form&ev=catsList'), {parent:parentID, device:device}, function(data){
                        if(data && data.success) {
                            cache[device][parentID] = data;
                            st2View(parentID, fromStep1);
                        }
                    });
                }
            }
            $st1.on('click', 'a.j-main', function(){
                var data = $(this).metadata();
                if( data.subs > 0 ) {
                    st2View(data.id, true);
                } else {
                    selectCategory(device, $(this), $st1);
                }
                return false;
            });
            $st2.on('click', '.j-back', function(){
                var prevID = intval($(this).metadata().prev);
                if( prevID == o.catsRootID ) {
                    $st2.add($st1).toggleClass('hide');
                } else {
                    st2View(prevID, false);
                }
                return false;
            });
            $st2.on('click', '.j-sub', function(){
                var data = $(this).metadata();
                if( data.subs > 0 ) {
                    st2View(data.id, false);
                } else {
                    selectCategory(device, $(this), $st2);
                }
                return false;
            });
        }

        popup = app.popup('form-cat-select', $popup, $('.j-cat-select-link', $block), {onInit: function(){
            initPopup(app.devices.desktop);
            initPopup(app.devices.phone);
        }});

        $selected.on('click', '.j-cat-selected-item-delete', function(e){ nothing(e);
            $(this).closest('.j-cat-selected-item').remove();
            var $lastValue = $selected.find('.j-value:last');
                $last.val( ($lastValue.length ? $lastValue.val() : 0) );
            total--;
        });

        if(o.catsSelected) {
            for(var k in o.catsSelected) {
                var v = o.catsSelected[k];
                if(v && v.hasOwnProperty('id')) selectCategoryItem(v.id, v.title, v.icon);
            }
        }
    }

    function phonesInit(limit, data)
    {
        var index = 0, total = 0;
        var $block = $cont.find('#j-shop-phones');

        function add(value)
        {
            if(limit>0 && total>=limit) return;
            index++; total++;
            value = (value ? value.replace(/"/g, "&quot;") : '');
            var tpl = $cont.find('.j-tpl-shop-phones');
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

        data = data || {};
        for(var i in data) {
            if( data.hasOwnProperty(i) ) {
                add(data[i].v);
            }
        }
        if( ! total ) {
            add('');
        }
    }

    function socialLinksInit(limit, data)
    {
        var index = 0, total = 0;
        var $block = $cont.find('#j-shop-social-links');
        var $types = $cont.find('#j-shop-social-links-types');

        function add(type, value)
        {
            if(limit>0 && total>=limit) return;
            index++; total++;
            $types.val(type);
            value = (value?value.replace(/"/g, "&quot;"):'');
            var tpl = $cont.find('.j-tpl-social-links');
            if (tpl.length) {
                $block.append(bff.tmpl(tpl.html(), {value:value, index:index, total:total, types:$types.html(), o:o}));
            } else {
                $block.append('<div class="controls j-social-link">'+
                            '<select name="social['+index+'][t]" class="span4 j-type">'+$types.html()+'</select>&nbsp;'+
                            '<div class="input-append sh-social-item">'+
                                '<input type="text" class="input-large" name="social['+index+'][v]" value="'+value+'" maxlength="300" placeholder="'+ o.lang.social_link+'" />'+
                                '<a href="#" class="add-on j-delete"><i class="icon-remove"></i></a>'+
                            '</div>'+
                        '</div>');
            }
            if(type) {
                $block.find('.j-social-link:last .j-type').val(type);
            }
        }

        $cont.find('#j-social-links-plus').on('click', function(e){ nothing(e);
            add(0, '');
        });

        $block.on('click', '.j-delete', function(e){ nothing(e);
            $(this).closest('.j-social-link').remove();
            total--;
        });

        data = data || {};
        for(var i in data) {
            if( data.hasOwnProperty(i) ) {
                add(data[i].t, data[i].v);
            }
        }
        if( ! total ) {
            add(0, '');
        }
    }

    function geoMapInit()
    {
        if(geo.addr.inited) return;
        if( ! $('#j-shop-geo-addr-map').is(':visible')) return;
        geo.addr.inited = 1;

        geo.addr.map = app.map('j-shop-geo-addr-map', [geo.addr.$lat.val(), geo.addr.$lon.val()], function(map){
            if (this.isYandex()) {
                map.controls.remove('searchControl');
            }
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
        var country = '';
        if($country.is('select')){
            country = $country.find('option:selected').text();
        }else{
            country = $country.val();
        }
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

    function langInit()
    {
        $cont.on('click', '.j-lang', function(){
            var $el = $(this);
            $el.addClass('active').siblings().removeClass('active');
            $cont.find('.j-lang-form').addClass('hide');
            $cont.find('.j-lang-form-'+$el.data('lng')).removeClass('hide');
        });
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('shops.form.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        },
        onCitySelect: function(cityID, cityTitle, ex){
            geo.fn.onCity(cityID, ex);
        },
        onBlockShow:function(){
            geoMapInit();
        }
    };
}());