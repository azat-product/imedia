/**
 * Bff.Utilites.AdminEx.js
 * @author Tamaranga | tamaranga.com
 * @version 0.5
 * @modified 18.jul.2018
 */

app.adm = true;
app.coreVersion = 2;

bff.extend(bff, {

confirm: function(q, o)
{
    o = o || {};
    var qq = {'sure':'Вы уверены?'};
    var res = confirm( ( qq.hasOwnProperty(q) ? qq[q] : q ) );
    if( res && o.hasOwnProperty('r') ) {
        bff.redirect(o.r);
    }
    return res;
},

adminLink: function(ev, module)
{
    return '?s='+module+'&ev='+ev;
},

userinfo: function(userID)
{
    if(userID) {
        $.fancybox('', {ajax:true, href: bff.adminLink('ajax&act=user-info&id=','users')+userID});
    }
    return false;
},

onTab: function(tab, siblings)
{
    tab = ( $(tab).hasClass('tab') ? $(tab) : $(tab).parent() );
    tab.addClass('tab-active');
    siblings = siblings || tab.siblings();
    siblings.removeClass('tab-active');
},

langTab: function(lang, prefix, toggler)
{
    toggler = $(toggler);
    if (toggler.hasClass('j-lang-toggler'))
    {
        var $block = toggler.closest('.box-content:visible');
        if ($block.length) {
            $block.find('.j-lang-togglers a').removeClass('active').filter('.lng-'+lang).addClass('active');
            $block.find('.j-lang-form').addClass('displaynone').filter('.j-lang-form-'+lang).removeClass('displaynone');
            $block.find('.j-publicator').each(function(){
                var obj = $(this).data('object');
                if (obj.length) eval(obj+'.setLang(\''+lang+'\');');
            });

        }
    } else {
        bff.onTab(toggler);
        toggler.closest('form:visible').find('.j-lang-form').addClass('displaynone').filter('.j-lang-form-'+lang).removeClass('displaynone');
    }
    return false;
},

errors: (function(){
    var $block, cont, warns, hide_timeout = false, err_clicked = false;

    $(function(){
        $block = $('#warning');
        cont   = $block.find('.warnblock-content');

        warns  = $('.warns', $block);
        if($block.is(':visible')) {
            bff.errors.show(false, {init:true}); // init hide-timeout
        }
        $block.on('click',function(){if( ! err_clicked){ err_clicked = true; }});
        $block.on('click', '.j-close', function(e){ nothing(e);
            bff.errors.hide();
        });
    });

    return {
        show: function(msg,o){
            o = o || {init:false, timeout:5000};

            var vis = $block.is(':visible');
            if( ! o.init)
            {
                if($.isArray(msg)) {
                    if(!msg.length) return;
                    msg = msg.join('<br/>');
                } else if($.isPlainObject(msg)) {
                    var res = []; for(var i in msg){
                        if ( ! msg.hasOwnProperty(i)) continue;
                        if ( ! msg[i].length) continue;
                        res.push(msg[i]);
                    }
                    if ( ! res.length) return;
                    msg = res.join('<br/>');
                }
                if(o.append && vis) {
                    warns.html( warns.html() + '<li>'+msg+'</li>');
                    clearTimeout(hide_timeout);
                } else {
                    warns.html('<li>'+msg+'</li>');
                }
                if(o.success) { cont.addClass('success alert-success').removeClass('error alert-danger'); }
                else { cont.addClass('error alert-danger').removeClass('success alert-success'); }
                if(!vis) { $block.fadeIn(); vis = true; }
            }

            if( ! vis) return;

            err_clicked = false;
            if(hide_timeout) clearTimeout(hide_timeout);
            if(o.timeout === -1) return;
            hide_timeout = setTimeout(function(){
                if( ! err_clicked) $block.fadeOut();
                clearTimeout(hide_timeout);
            }, o.timeout || 5000);
        },
        hide: function() {
            if(hide_timeout) clearTimeout(hide_timeout);
            err_clicked = false;
            $block.fadeOut();
        },
        stop_hide: function() {
            err_clicked = true;
            if(hide_timeout) clearTimeout(hide_timeout);
        }
    }
}()),
error: function(msg, o)
{
    bff.errors.show(msg, o);
    return false;
},

success: function(msg, o)
{
    if(o && $.isPlainObject(o)) {
        o.success = true;
    } else {
        o = {success:true};
    }
    bff.errors.show(msg,o);
},

busylayer: function(toggle, callback)
{
    callback = callback || new Function();
    toggle = toggle || false; 
    
    var layer = $('#busyLayer'), doc = document;
    if(!layer || !layer.length) //if not exists
    {
        var body = doc.getElementsByTagName('body')[0];           
    
        layer = doc.createElement('div');
        layer.id = 'busyLayer';
        layer.className = 'busyLayer';
        layer.style.display = 'none';
        layer.style.textAlign = 'center';
        //layer.innerHTML = '<img src="/img/progress-large.gif" />';       
        body.appendChild(layer); 
        layer = $(layer);
        
        layer.css({'filter':'Alpha(Opacity=65)', 'opacity':'0.65'});

//        $(doc).on('keydown',function(e) {
//            if (e.keyCode == 27 && layer.is(':visible')) { 
//                nothing(e);
//                layer.fadeOut(500); 
//            }
//        }); 
    }    

    if(layer.is(':visible')) {
        if(toggle){
            layer.fadeOut(500, callback);
        }
        return false;
    }
    
    var height = $(doc).height();
    layer.css({'height': height+'px', 'paddingTop': (height/2)+'px'}).fadeIn(500, callback);
    return false;
},

ajaxToggleWorking: false,
ajaxToggle: function(nRecordID, sURL, opts)
{
    if(bff.ajaxToggleWorking)
        return;
    
    bff.ajaxToggleWorking = true;

    var o = $.extend({
            link: '#lnk_',
            block: 'block', unblock: 'unblock',
            progress: false,
            toggled: false, // return toggled records ids
            complete: false // complete callback
        }, opts || {});

    if(sURL == '' || sURL == undefined) {
        $.assert(false, 'ajaxToggle: empty URL');
        return;
    }
    
    if(nRecordID<=0) {
        $.assert(false, 'ajaxToggle: empty record_id');
        return;
    }

    if( typeof(o.link) == 'object' ) {
        var type = $(o.link).data('toggle-type');
        if( type == 'check' || $(o.link).hasClass('check') ) {
            o.block = 'unchecked'; o.unblock = 'checked';
        } else if( type == 'fav' ) {
            o.block = 'fav'; o.unblock = 'unfav';
        }
    }

    var eLink = null;
    bff.ajax(sURL, {rec: nRecordID, toggled: o.toggled}, function(data)
    {
        if(data!=0) {
            if(o.toggled)
            {
               data.toggled.each( function(t){
                    eLink = !$(o.link+t).length || $(o.link);
                    if( eLink!=undefined) {
                        eLink.removeClass( (data.status ? o.block : o.unblock) );
                        eLink.addClass( (data.status ? o.unblock : o.block) );
                    }
               });
            }
            else {
                eLink = ( typeof(o.link) == 'object' ? $(o.link) : $(o.link+nRecordID) );
                if( eLink!=undefined) {
                    var has = eLink.hasClass( o.unblock);
                    eLink.removeClass( (has? o.unblock : o.block) );
                    eLink.addClass( (has? o.block : o.unblock) );
                }
            }
        }

        if(o.complete) o.complete(data);

        bff.ajaxToggleWorking = false;
    }, o.progress);
},

ajaxDeleteWorking: false,
ajaxDelete: function(sQuestion, nRecordID, sURL, link, opts)
{
    if(bff.ajaxDeleteWorking)
        return;

    if(sQuestion!==false)
        if( ! bff.confirm(sQuestion))
            return;
    
    bff.ajaxDeleteWorking = true;

    var o = $.extend({ 
            paramKey: 'rec',
            progress: false,
            remove: true,
            repaint: true
        }, opts || {});
    
    if(sURL == '' || sURL == undefined) {
        $.assert(false, 'ajaxDelete: empty URL');
        return;
    }

    if(nRecordID<=0)
        $.assert(false, 'ajaxDelete: empty recordID');
    
    var params = {}; params[o.paramKey] = nRecordID;
    bff.ajax(sURL, params, function(data, errors)
    {
        if(data && ( ! data.hasOwnProperty('success') || data.success) && ( ! errors || ! errors.length) ) {
            if (data.hasOwnProperty('modal')) {
                bff.ajaxDeleteWorking = false;
                var $modal = $(data.modal);
                app.$B.append($modal);
                $modal.one('hidden.bs.modal', function(){
                    $modal.remove();
                });
                $modal.modal('show');
                return;
            }

            if(o.onComplete)
                o.onComplete(data, o);
            
            if(o.remove && link) 
            {
                $link  = $(link);
                var $table = $link.parents('table.admtbl');
                if($table.length) {
                    $link.parents('tr:first').remove();
                    //repaint rows
                    if(o.repaint) {
                        $table.find('tr[class^=row]').each(function (key, value) {
                            $(value).attr('class', 'row'+(key%2))
                        });
                    }
                }
            }
        }        
        bff.ajaxDeleteWorking = false;
   }, o.progress);
},

rotateTable: function(list, url, progressSelector, callback, addParams, rotateClass, o)
{
    if(!$.tableDnD) return;

    callback    = callback || $.noop;
    rotateClass = rotateClass || 'rotate';
    addParams   = addParams || {};
    o = $.extend({before:false}, o || {});
    $(list).tableDnD({
        onDragClass: rotateClass,
        onDrop: function(table, dragged, target, position, changed)
        {
            if(changed && url!==false) {
                if (o.before!==false) addParams = o.before(addParams, dragged, target, position, changed);
                bff.ajax(url,
                    $.extend({ dragged : dragged.id, target : target.id, position : position }, addParams),
                    callback, progressSelector);
            }
        }
    });
},

textLimit: function(ta, count, counter) 
{
  var text = document.getElementById(ta);
  if(text.value.length > count) {
    text.value = text.value.substring(0,count);
  }
  if(counter) { // id of counter is defined
    document.getElementById(counter).value = text.value.length;
  }
},

textInsert: function(fieldSelector, text, wrap) 
{
    var field = $(fieldSelector);
    if(!field.length) return;
    field = field.get(0);

    // если opera, тогда непередаём фокус
    if(navigator.userAgent.indexOf('Opera')==-1) { field.focus(); }
    
    if(document.selection){ //ie
        document.selection.createRange().text = text + ' ';
    }
    else if (field.selectionStart || field.selectionStart == 0) 
    {
        if(wrap && wrap.open && wrap.close) {
            text = wrap.open + field.value.substring(field.selectionStart, field.selectionEnd) + wrap.close;
        }
        var strFirst = field.value.substring(0, field.selectionStart);
        field.value = strFirst + text + field.value.substring(field.selectionEnd, field.value.length);

        // ставим курсор
        if(!pos){
            var pos = (strFirst.length + text.length);
            field.selectionStart = field.selectionEnd = pos;
        } else {
            field.selectionStart = field.selectionEnd = (strFirst.length + pos);
        }
    } 
    else {
        field.value += text;
    } 
},

textReplace: function(text, replace)
{
    text = text.toString(); var key; replace = replace||{};
    for (key in replace) {
        if (replace.hasOwnProperty(key)) {
            text = text.replace(new RegExp(key, "g"), replace[key]);
        }
    }
    return text;
},

formSelects: 
{
    MoveAll: function(source_id, destination_id)
    {
        var source      = document.getElementById(source_id);
        var destination = document.getElementById(destination_id);
        
        for(var i=0; i<source.options.length; i++)
        {
            var opt = new Option(source.options[i].text, source.options[i].value, false);
            opt.style.color = source.options[i].style.color;
            destination.options.add(opt);
        }
        source.options.length = 0;
    },

    MoveSelect: function (source_id, destination_id)
    {
        var source      = document.getElementById(source_id);
        var destination = document.getElementById(destination_id);
        
        for(var i=source.options.length-1; i>=0; i--)
        {
            if(source.options[i].selected==true)
            {
                var opt = new Option(source.options[i].text, source.options[i].value, false);
                opt.style.color = source.options[i].style.color;
                destination.options.add(opt);
                source.options[i] = null;
            }
        }  
    },

    SelectAll: function(sel_id)
    {
        var sel = document.getElementById(sel_id);
        for(i=0; i<sel.options.length; i++)
        {
            sel.options[i].selected = true;
        }     
    },
    
    hasOptions: function(sel_id)
    {
        return document.getElementById(sel_id).options.length;
    }
},    

formChecker: function(form,options,onLoad){
    var self = this;
    $(function(){
        self.initialize(form,options);
        if(onLoad) onLoad();
    });
},

pgn: function(form,options){
    var self = this;
    $(function(){
        self.initialize(form,options);
    });
},

cropImageLoaded: false,
cropImage: function(o, crop, callback)
{
    bff.ajax( bff.adminLink('ajax&act=crop-image-init','site'), o, function(f)
    {
        if(!f || !f.res) return;
        
        var W = intval(f.width);
        var H = intval(f.height);

        $.fancybox(f.html, {onClosed: function(){
            api.destroy();
            if(cropped && !callback) location.reload();
        }});

        var cont = $('#popupCropImage'), api,
            $previews = $('.jcrop-preview', cont).attr('src', f.url),
            form = $('form:first', cont).get(0),
            cropped = false, boundx = 0, boundy = 0;

        crop = $.extend({x:0,y:0,x2:0,y2:0,w:0,h:0}, crop);

        function updateCropParams(c)
        {
            form.x.value = c.x; 
            form.y.value = c.y; 
            form.w.value = c.w; 
            form.h.value = c.h; 
            form.crop.value = [c.x,c.y,c.x2,c.y2,c.w,c.h].join(',');
            crop = c;
            updatePreview(c);
        }

        function updatePreview(c)
        {
            $previews.each(function(i,v){
                if (parseInt(c.w) <= 0) return;
                v = $(v);
                var rx = intval(v.parent().data('width')) / c.w;
                var ry = intval(v.parent().data('height')) / c.h;
                v.css({width: Math.round(rx * boundx) + 'px',
                       height: Math.round(ry * boundy) + 'px',
                       marginLeft: '-' + Math.round(rx * c.x) + 'px',
                       marginTop: '-' + Math.round(ry * c.y) + 'px'});
            });
        }

        setTimeout(function(){
             var img = $('.upload-crop-area', cont);
                 img.attr({src: f.url, width: W, height: H});
             if(crop.x==0 && crop.x2==0) {
                 var vert = H > W;
                 crop.x2 = (vert ? W : H);
                 crop.y2 = crop.x2;
                 crop.y = ((H - W) / 2);
             }

             img.Jcrop({
                setSelect: [crop.x, crop.y, crop.x2, crop.y2],
                minSize: [100, 100],
                aspectRatio: f.ratio, allowSelect: true, boxWidth: 330, trueSize: [W, H],
                addClass: 'custom', bgColor: '#000', bgOpacity: .5, handleOpacity: 0.8, sideHandles: false,
                onChange: updateCropParams,
                onSelect: updateCropParams
             }, function(){
                api = this;
                var bounds = api.getBounds();
                boundx = bounds[0];
                boundy = bounds[1];
                crop = api.tellSelect();
                updateCropParams(crop);
             });
        }, 200);
        
        var $form = $(form), process = false;
        $form.submit(function(){
            if(process) return false;
            process = true;
            bff.ajax(o.url, $form.serialize(), function(data){
                if(data) {
                    cropped = true;
                    if(cropped && callback) callback( crop, data['crop_packed'] || '' );
                    $.fancybox.close();
                }
                process = false;
            });
            return false;
        });
    });
    return false;
},

generateKeyword: function(from, to, url)
{
    from = $(from);
    var title =  (from.length ? $.trim( from.val() ) : '');
    if(title.length>0)
    {
        bff.ajax( (url || bff.adminLink('ajax&act=generate-keyword','site')), {title: title}, function(data){
            if(data.res) {
                to = $(to);
                if(to.length) to.val(data.keyword);
            }
        });
    }
    return false;
},

expandNS: function(id, url, o)
{
    o = $.extend({progress:false, cookie:false}, o || {});
    var state = [], separator = '.';
    if( o.cookie ) {
        state = bff.cookie(o.cookie);
        state = ( ! state ? [] : state.split(separator) );
        for(var i=0; i<state.length; i++) state[i] = intval(state[i]);
    }

    var row = $('#dnd-'+id);
    var subsFilter = []; for( var i = intval(row.data('numlevel')); i>=1; i--) subsFilter.push('[data-numlevel='+i+']');
    var subs = row.nextUntil( subsFilter.join(',') );
    var cookieParams = {expires:45, domain:'.'+app.host};
    if( subs.length ) {
        var pids = [];
        subs.each(function(i,e){
            var p = intval( $(e).data('pid') );
            if($.inArray(p,pids)==-1) pids.push(p);
        });
        var vis = subs.is(':visible');
        for(var i=0; i< pids.length; i++) {
            var j = $.inArray(pids[i], state);
            if(vis) { if(j!==-1) state.splice(j, 1); } else { if(j===-1) state.push(pids[i]); }
        }
        if(!vis) subs.filter('[data-pid="'+id+'"]').show(); else subs.hide();
        if(o.cookie) bff.cookie(o.cookie, (state.length ? state.join(separator) : ''), cookieParams);
    } else {
        bff.ajax(url+id,{},function(data){
            if(data) {
                if(!data.hasOwnProperty('cnt') || intval(data.cnt)>0) state.push(id);
                row.after(data.list).nextAll('[data-pid="'+id+'"]').show();
                row.parent().tableDnDUpdate();
                if(o.cookie) bff.cookie(o.cookie, (state.length ? state.join(separator) : ''), cookieParams);
            }
        }, o.progress);
    }
},

datepicker: function(selector, params)
{
    $(selector).attachDatepicker(params || {});
},

bootstrapJS: function()
{
    return $.fn.hasOwnProperty('button');
},

list: function(blockSelector, o)
{
    var processing = false, inited = false, rotateInited = false;
    var block, tabs = false, filter, list, pgn = false, form = false, more, total;
    var multi, cache = {autocomplete:{}};

    var self = {
        init: function()
        {
            if (inited) return false; inited = true;
            o = $.extend({
                list:'.j-list', pgn:'.j-list-pgn', filter:'.j-list-filter form', tabs:'.j-tabs',
                onInit:$.noop, onSubmit:$.noop, onReset:$.noop, onProgress:$.noop, onPopstate:$.noop, onTabChange:$.noop,
                rotate:false, orderSeparator:' ', dateRange: '-3:+3', more:false, onBeforeSubmitQueryPlus:$.noop, formTab:false,
                responsive: false,
            }, o || {});

            block = self['block'] = $(blockSelector); if (!block.length) { return false; }
            filter = self['filter'] = $(o.filter, block); if (!filter.length) { return false; }
            list = self['list'] = $(o.list, block); if (!list.length) { return false; }

            if (o.pgn !== false) {
                pgn = self['pgn'] = $(o.pgn, block);
                if (pgn.length) {
                    pgn.on('click', '.j-pgn-page', function(e){
                        e.preventDefault();
                        self.page($(this).data('page'), true);
                    });
                }
            }
            if (o.tabs !== false) {
                tabs = self['tabs'] = bff.tabs($(o.tabs, block), {
                    onChange: function(key, tab) {
                        o.onTabChange.call(self, key, tab);
                        filter.find('.j-tab-value').val(key);
                        self.submit({resetPage:true});
                    }
                });
                self.rotate();
            }
            if (o.more !== false) {
                more = block.find('.j-more');
                total = more.find('.j-total');
                more.click(function(e){
                    e.preventDefault();
                    self.submit({urlUpdate:false, more:true});
                });
            }

            // actions:
            list.on('click', '.j-toggle', function(e){
                e.preventDefault();
                var id = intval($(this).data('id'));
                var type = $(this).data('type');
                if (id > 0) {
                    var params = {progress: function(p){ self.progress(p, false); }, link: this};
                    bff.ajaxToggle(id, self.url()+'toggle&type='+type+'&id='+id, params);
                }
            });
            list.on('click', '.j-delete', function(e){
                e.preventDefault();
                var $el = $(this);
                var url = $el.attr('href');
                if ( ! url) {
                    var id = intval($(this).data('id'));
                    if (id > 0) {
                        url = self.url()+'delete&id='+id;
                    }
                }
                bff.ajaxDelete('sure', id, url, this, {repaint: false});
            });
            list.on('click', '.j-sort', function(e){
                e.preventDefault();
                var $el = $(this);
                filter.find('.j-order-value').val($el.data('id') + o.orderSeparator + $el.data('direction'));
                self.submit({resetPage:true});
            });

            // multi:
            multi = block.find('.j-multi-actions');
            if (multi && multi.length) {
                o.multi = $.extend({lang:{legend:'Selected', declension:'item;items;items'}}, o.multi || {});
                o.multi.lang.declension = o.multi.lang.declension.split(';');
                o.multi.all = list.find('.j-multi-all');

                list.on('change', '.j-multi-item', function(){
                    self.multiShow();
                    var $c = list.find('.j-multi-item:visible:checked:not(:disabled)');
                    o.multi.all.prop('checked', !(!$c.length || $c.length < list.find('.j-multi-item:visible:not(:disabled)').length));
                });
                list.on('change', '.j-multi-all', function(){
                    list.find('.j-multi-item').prop('checked', $(this).is(':checked'));
                    self.multiShow();
                });
            }

            // popstate
            var queryInitial = self.query();
            $(window).on('popstate',function(e){
                var loc = history.location || document.location;
                var query = loc.search.substr(1);
                if (query.indexOf('act=') >= 0) {
                    self.action('url', loc.href, {popstate:true});
                    return;
                } else {
                    if (form && form.block && form.block.is(':visible')) {
                        self.action('cancel', '', {popstate:true});
                    }
                }
                if( query.length == 0 ) query = queryInitial;
                filter.deserialize(query, true);
                o.onPopstate.call(self);
                filter.find('[data-input="autocomplete-value"]').each(function(){
                    var $val = $(this);
                    var name = $val.attr('name');
                    var v = $val.val();
                    if ( ! name ||  ! v) return;
                    if ( ! cache.autocomplete.hasOwnProperty(name)) return;
                    if ( ! cache.autocomplete[name].hasOwnProperty(v)) return;
                    filter.find('#'+$val.data('text')).val(cache.autocomplete[name][v]);
                });
                var $t = filter.find('.j-tab-value');
                if ($t.length) {
                    tabs.set($t.val());
                }
                self.submit({urlUpdate:false});
            });

            block.on('click', '.j-form-url', function(e){
                e.preventDefault();
                self.action('url', $(this).attr('href'));
            });
            filter.find('.j-datepicker').each(function(){
                bff.datepicker($(this), {yearRange: o.dateRange});
            });
            filter.on('click', '.j-button-more', function(){
                filter.find('.j-more').removeClass('more-hide');
                filter.find('.j-more-state').val(1);
                $(this).hide();
            });
            filter.on('click', '.j-button-reset', function(e){
                e.preventDefault();
                self.reset();
            });
            filter.on('click', '.j-button-submit', function(e){
                e.preventDefault();
                self.submit({resetPage:false});
            });
            filter.on('autocomplete.select', function(e, name){
                if ( ! name) return;
                var $val = filter.find('[name="'+name+'"]');
                if ( ! $val.length) return;
                var v = $val.val();
                if ( ! v) return;
                var $text = filter.find('#'+$val.data('text'));
                if ( ! $text.length) return;
                var t = $text.val();
                if ( ! t) return;
                if ( ! cache.autocomplete.hasOwnProperty(name)) { cache.autocomplete[name] = {}; }
                cache.autocomplete[name][v] = t;
            }); // TODO

            o.onInit.call(self, list, filter, tabs);
            list.find('.j-tooltip').tooltip();
            if (o.responsive) {
                bff.makeResponsive(list);
            }

            // form
            if (o.form !== false) {
                form = (function(o){
                    o = $.extend({
                        onInit:$.noop, block:false
                    }, (o || {}));
                    var form = o;
                    form.block = $(o.block); if (!form.block.length) { return false; }
                    form.block.on('list.cancel', function(e){ nothing(e); self.action('cancel');   });
                    form.block.on('list.refresh', function(e){ nothing(e);
                        self.action('cancel');
                        self.submit();
                    });
                    form.block.on('list.update', function(e){
                        nothing(e);
                        self.submit({urlUpdate:false});
                    });
                    form.block.on('form.reload', function(e, url){
                        nothing(e);
                        if ( ! url) url = window.location.href;
                        url = $('<div/>').html(url).text();
                        self.action('url', url);
                        self.submit({urlUpdate:false, processing:false});
                    });
                    return form;
                }(o.form || {}));
                if (form !== false && ! form.block.is(':empty')) {
                    self.formToggle(true);
                }
            }
        },
        submit: function(ex)
        {
            ex = $.extend({urlUpdate:true, resetPage:false, scroll:false, fade:true, processing:true, more:false}, ex||{});
            if (o.formTab) ex.urlUpdate = false;
            if (ex.processing && processing) { return; }
            if (ex.resetPage) { self.page(1, false); }
            if (o.more !== false) {  filter.find('.j-more-counter').val(ex.more ? list.find('.j-more-item').length : ''); }
            var query = self.query(o.onBeforeSubmitQueryPlus ? o.onBeforeSubmitQueryPlus(ex) : false);
            bff.ajax(self.url(), query, function(resp, err){
                if(resp && resp.success) {
                    if (resp.hasOwnProperty('list')) list.html(resp.list);
                    if (resp.hasOwnProperty('pgn') && pgn !== false) {
                        pgn.html(resp.pgn);
                    }
                    if (ex.urlUpdate) {
                        self.urlUpdate();
                    }
                    self.rotate();
                    if (multi && multi.length) { multi.hide(); }
                    list.find('.j-tooltip').tooltip();
                    if (o.more !== false) {
                        if (resp.hasOwnProperty('append')) {
                            list.find('.j-list-body').append(resp.append);
                        }
                        if (resp.hasOwnProperty('more')) {
                            more.toggleClass('hidden', (resp.more ? false : true));
                            if (resp.hasOwnProperty('total')) {
                                total.text(resp.total);
                            }
                        }
                    }
                    if (o.responsive) {
                        bff.makeResponsive(list);
                    }
                    o.onSubmit(resp, ex);
                } else {
                    bff.errors.show(err);
                }
            }, function(p){
                if (ex.processing) {
                    processing = p;
                    self.progress(p);
                }
            });
        },
        rotate: function(){
            if ( ! tabs) {
                if (o.rotate) {
                    if (rotateInited) {
                        list.tableDnDUpdate();
                    } else {
                        rotateInited = true;
                        bff.rotateTable(list, self.url() + 'rotate', function(p){
                            self.progress(p);
                        }, false);
                    }
                }
                return;
            }
            if (tabs.rotate()) {
                if (rotateInited) {
                    list.tableDnDUpdate();
                } else {
                    rotateInited = true;
                    bff.rotateTable(list, self.url() + 'rotate', function(p){
                        self.progress(p);
                    }, false);
                }
            } else {
                if (rotateInited) {
                    list.tableDnDRemove();
                    rotateInited = false;
                }
            }
        },
        refresh: function(resetPage, urlUpdate) {
            self.submit({resetPage:resetPage,urlUpdate:urlUpdate});
        },
        reset: function(opt)
        {
            opt = opt || {noSubmit:false};
            o.onReset.call(self);
            filter.find('.j-input').each(function(){
                switch ($(this).data('input')) {
                    case 'hidden':
                    case 'text':
                    case 'autocomplete-title':
                        $(this).val('');
                        break;
                    case 'autocomplete-value':
                        $(this).val(0);
                        break;
                    case 'select':
                        $(this).val(0);
                        break;
                    case 'checkbox':
                        $(this).removeProp('checked');
                        break;
                }
            });
            if (opt.noSubmit) return;
            self.submit({scroll:false});
        },
        page: function(id, update)
        {
            id = intval(id);
            if (id<=0) { id = 0; }
            var $val = filter.find('.j-page-value');
            if (!$val.length) { return 0; }
            if (id && intval($val.val()) != id) {
                $val.val(id);
                if (update!==false) {
                    if (update === true) update = {};
                    self.submit(update);
                }
            }
            return $val.val();
        },
        progress: function(show, fade) {
            bff.progress(show);
            o.onProgress.call(self, show, fade);
            if (fade !== false) {
                list.toggleClass('disabled');
            }
        },
        processing: function() {
            return processing;
        },
        query: function(plus) {
            var query = [];
            $.each(filter.serializeArray(), function(i, field) {
                if (field.value && field.value!=0 && field.value!='') {
                    query.push(field.name+'='+encodeURIComponent(field.value));
                }
            });
            if (plus) {
                $.each(plus, function(i, field) {
                    if (field.value && field.value!=0 && field.value!='') {
                        query.push(field.name+'='+encodeURIComponent(field.value));
                    }
                });
            }
            return query.join('&');
        },
        onTab: function(tab) {
            if (tabs !== false) {
                tabs.onTab(tab);
            }
        },
        url: function(query) {
            var url = filter.attr('action') + '?';
            if (query === true) {
                return url + self.query();
            }
            return url + 's='+filter.find('.j-module').val()
                      +'&ev='+filter.find('.j-method').val()+'&act=';
        },
        urlUpdate: function(url) {
            if (bff.h) {
                window.history.pushState({}, document.title, url || self.url(true));
            }
        },
        action: function(act, url, params) {
            switch(act) {
                case 'url': {
                    self.formInit(url, params);
                } break;
                case 'cancel': {
                    form.block.html('');
                    self.formToggle(false);
                    if (( ! params || ! params.popstate) && ! o.formTab) {
                        self.urlUpdate(self.url(true));
                    }
                } break;
            }
            return false;
        },
        formInit: function(url, params) {
            if (processing) return;
            bff.ajax(url, params, function(r){
                if (r && r.success) {
                    form.block.html(r.form);
                    self.formToggle(true);
                    if (( ! params || ! params.popstate) && ! o.formTab) {
                        self.urlUpdate(url);
                    }
                } else {
                    self.formToggle(false);
                }
            }, function(p){ processing = p; self.progress(p); });
        },
        formToggle: function(show) {
            if (show === true) {
                form.block.show();
                block.hide();
                $.scrollTo(form.block, {duration: 500, offset: -300});
                form.block.find('form').trigger('form.show');
            } else {
                form.block.hide();
                block.show();
            }
        },
        multiShow: function() {
            var $ch = list.find('.j-multi-item:checked');
            multi.find('.j-multi-info').html(o.multi.lang.legend+' <strong>'+bff.declension($ch.length, o.multi.lang.declension)+'</strong>');
            multi.toggle($ch.length ? true : false);
        },
        multiSubmit: function(act, params, callback) {
            if (processing) return;
            var query = self.query(list.find('.j-multi-item:checked').serializeArray()) + params;
            bff.ajax(self.url() + act, query, callback, function(p){ processing = p; self.progress(p); });
        },
        param:function(name, value){  o[name] = value; }
    };

    $(function(){
        self.init();
    });
    return self;
},

tabs: function(selector, o)
{
    o = $.extend({
        onInit:$.noop, onChange:$.noop, active:'', activeClass:'tab-active', ajax:true, rotate:false
    }, o || {});

    var block = $(selector);
    if (!block.length) {
        return false;
    }

    var $mobileActive = block.find('.j-box-tabs-active');
    var $active = block.find('.'+o.activeClass+' .j-tab');
    if ($active.length){
        o.active = intval($active.data('tab'));
        o.rotate = intval($active.data('rotate')) ? true : false;
    }

    if (o.ajax) {
        block.on('click', '.j-tab', function(){
            onChange($(this).data('tab'), $(this));
            return false;
        });
    }

    $(window).resize(function(){
        resize();
    });
    resize();

    function onChange(key, tab) {
        o.active = key;
        o.rotate = intval(tab.data('rotate')) ? true : false;
        o.onChange(key);
        tab = tab || block.find('.j-tab[data-tab="'+key+'"]');
        tab = ( $(tab).hasClass('tab') ? $(tab) : $(tab).parent() );
        tab.addClass(o.activeClass).siblings().removeClass(o.activeClass);
        block.trigger('tab.select', key);
        mobileTitle(tab);
    }

    function set(key) {
        if ( ! key) key = intval(key);
        var tab = block.find('.j-tab[data-tab="'+key+'"]');
        if ( ! tab.length){
            return;
        }
        o.active = key;
        o.rotate = intval(tab.data('rotate')) ? true : false;
        tab = ( $(tab).hasClass('tab') ? $(tab) : $(tab).parent() );
        tab.addClass(o.activeClass).siblings().removeClass(o.activeClass);
        block.trigger('tab.select', key);
        mobileTitle(tab);
    }

    function mobileTitle(tab)
    {
        $mobileActive.html(tab.find('.j-tab-title').html());
        block.removeClass('open');
    }

    function resize()
    {
        var w = $(window).width();
        if (w < 768) {
            block.find('.j-box-tabs').removeClass('l-box-tabs').addClass('dropdown-menu');
            block.find('.j-box-tabs-mobile').removeClass('hidden');
        } else {
            block.find('.j-box-tabs').addClass('l-box-tabs').removeClass('dropdown-menu');
            block.find('.j-box-tabs-mobile').addClass('hidden');
        }
    }

    return {
        onTab: onChange,
        set:set,
        rotate: function() {
            return o.rotate;
        },
        active: function() {
            return o.active;
        }
    };
},

progress: function(show)
{
    if (show === true) {
        app.$progress.show();
    } else if (show === false) {
        app.$progress.hide();
    } else {
        return app.$progress;
    }
},

makeResponsive: function($table)
{
    var data = {};
    $table.find('thead > tr').each(function(i){
        data[i] = {};
        $(this).children('th').each(function(ii){
            data[i][ii] = $(this).text();
        });
    });
    $table.find('tbody > tr').each(function(){
        var col = 0;
        $(this).children('td').not('.l-table-cell-heading').not('.l-table-action').each(function(){
            $(this).prepend('<span class="l-table-responsive-th">' + data[0][col] + '</span>');
            col++;
        });
    });
},

modal: function(url, o)
{
    if ( ! url) return;
    o = o || {};
    bff.ajax(url, o.hasOwnProperty('params') ? o.params : {}, function(data){
        if (data && data.success) {
            if (data.hasOwnProperty('modal')) {
                bff.modalShow(data.modal, o);
            }
        }
    });
},

modalShow: function(html, o)
{
    var $modal = $(html);
    app.$B.append($modal);
    $modal.one('hidden.bs.modal', function(){
        $modal.remove();
    });
    $modal.modal('show');
    if (o.hasOwnProperty('onLoad')) {
        o.onLoad($modal);
    }
}

});

$(function(){
    app.$B = $('body');
    app.$progress = $('#j-general-progress');

    app.$B.on('click', '.j-modal-link', function(e){
        e.preventDefault();
        var $el = $(this);
        var $modal = $el.closest('.modal');
        if ($modal.length) {
            $modal.modal('hide');
        }
        bff.modal($el.data('link'));
    });

    // Collapsable Sidebar
    $('.l-sidebar-toggle').click(function(e) {
        e.preventDefault();
        $('.l-sidebar').toggleClass('not-active');
        $('.l-sidebar-toggle').toggleClass('active');
        $('.l-content').toggleClass('not-active');
    });
    // Collapsable Sidebar
    $('.l-sidebar-toggle-sm').click(function(e) {
        e.preventDefault();
        $('.l-sidebar').toggleClass('active');
        $('.l-sidebar-toggle-sm').toggleClass('active');
    });

});

/*@cc_on bff.ie=true;@*/ 

bff.formChecker.prototype = 
{
    initialize: function(form, options)
    {
        this.submiting = false;
        this.setForm(form);
        this.options  = { 
            scroll: false, ajax: false, progress: false,
            errorMessage: true, errorMessageBlock: '#warning', errorMessageText: '#warning .warns',  
            password: '#password', passwordNotEqLogin: true, passwordMinLength: 3, 
            login: '#login', loginMinLength: 5};
        
        if(options) { for (var o in options) { 
            this.options[o] = options[o]; } }
        
        //init error message
        if(this.options.errorMessage){
            this.errorMessageBlock = $(this.options.errorMessageBlock);
            this.errorMessageText  = $(this.options.errorMessageText);
        }
        
        this.initInputs();   
         
        //var formOrigSubmit = this.form.get(0).submit;
        //this.form.get(0).submit = function(){ return (onSubmit()? false : formOrigSubmit()); };
        //console.log( this.form.get(0) ); 
        
        this.check();
    },
    
    initInputs: function()
    {
        var t = this;
        t.required_fields = t.form.find('.required');
        t.required_fields.on('blur keyup change', $.debounce(function(){ return t.check(); }, 400));
        t.submit_btn = t.form.find('input:submit');
        t.submit_btn_text = t.submit_btn.val();
        t.form.submit(function(){ 
            return t.onSubmit(); 
        });
    },

    setForm: function(form)
    {
        this.form = $(form);
        $.assert(this.form, 'formChecker: unable to find form');
    },

    onSubmit: function()
    {
        var t = this;
        var res = t.check(); 
        if(this.submitCheck)
            res = this.submitCheck();
            
        if(res)
        {
            t.submiting = true;
            if(t.options.ajax != false) {
                t.disableSubmit();
                bff.ajax(t.form.attr('action'), t.form.serializeArray(), function(data){
                    t.enableSubmit();
                    if(data){ 
                        t.form[0].reset(); 
                        if(typeof t.options.ajax === 'function') {
                            t.options.ajax(data);
                        }
                    }
                    t.submiting = false;
                    t.check();
                }, t.options.progress);
                return false;
            }
        }
        return res; 
    },
    
    enableSubmit: function(){
        this.submit_btn.prop('disabled', false).val( this.submit_btn_text );
    },

    disableSubmit: function(){
        this.submit_btn.prop('disabled', true); //.val('Подождите...');
    },

    showMessage: function(text){
        if(this.options.errorMessage) {
            this.errorMessageText.html('<li>'+text+'</li>'); 
            if(!this.errorMessageBlock.is(':visible'))     
                this.errorMessageBlock.fadeIn();
            
            this.errorMessageShowed = true; 
        }
    },  

    showErrors: function(errors){
        bff.errors.show(errors);
        this.form.find('.has-error').removeClass('has-error');
        this.form.find('.j-icon-wrapper icon').remove();
        this.fieldsErrors(errors);
    },

    fieldsErrors: function(errors){
        for (var i in errors) {
            if ( ! errors.hasOwnProperty(i)) continue;
            var $field = this.form.find('[name="'+i+'"]');
            if ($field.length) {
                var $par;
                if ($field.is(':hidden')) {
                    $par = $field.parent();
                    $par.addClass('j-icon-wrapper has-icon');
                } else {
                    $par = $field.parent('.j-icon-wrapper');
                }
                if ( ! $par.length) {
                    $field.wrap('<div class="j-icon-wrapper has-icon"></div>');
                    $par = $field.parent('.j-icon-wrapper');
                }
                $par.addClass('has-error');
                $field.after('<i class="fa fa-warning icon icon-error j-tooltip" data-container="body" title="'+errors[i]+'"></i>');
                $par.find('.j-tooltip').tooltip();
                $par.data('timeout', this.errorTimeout($par));
                $field.one('change', function(){
                    var $el = $(this).closest('.has-error');
                    clearTimeout($el.data('timeout'));
                    $el.removeClass('has-error');
                    if ($el.hasClass('j-icon-wrapper')) {
                        $el.find('i.icon').remove();
                    }
                });
            }
        }
    },

    errorTimeout: function($el){
        return setTimeout(function(){
            $el.removeClass('has-error');
            if ($el.hasClass('j-icon-wrapper')) {
                $el.find('i.icon').remove();
            }
        }, 10000);
    },

    check: function(focus, reinit){
        this.errorMessageShowed = false;   
        var ok_fields = 0;
        var me = this;
        if(reinit === true) {
            this.initInputs();
        }

        this.required_fields.each(function() {
            var obj = $(this), fld = obj.find('input:visible, textarea:visible, select:visible'), result = false;
            if(!fld.length) {
                result = 1;
            }
            else {
                if(obj.is('.check-email')){
                    result = me.checkEmail(fld);
                }
                else if(obj.is('.check-password')){
                    result = me.checkPassword(fld);
                }
                else if(obj.is('.check-login')){
                    result = me.checkLogin(fld);
                }
                else if(obj.is('.check-select')){
                    result = me.checkSelect(fld);
                }
                else if(obj.is('.check-radio')){
                    fld = obj.find('input:checked');
                    result = (!fld.length ? 0 : 1);
                }
                else{
                    result = me.checkEmpty(fld);
                }
            }

            if(result)
                obj.removeClass('clr-error');
            else {
                obj.addClass('clr-error');
                if(focus) fld.focus();
            }

            if(!result) return false;
            ok_fields += Number(result);  
        });

        var is_ok = (ok_fields == this.required_fields.length);
        if(is_ok && this.additionalCheck) {
            is_ok = this.additionalCheck();
        }
        
        //if(this.options.errorMessage && !this.errorMessageShowed)
        //    this.errorMessageBlock.fadeOut(); 
            
        //if(this.submiting)
        //    this.submit_btn.prop('disabled', !is_ok);

        if(this.afterCheck)
            this.afterCheck();
            
        return is_ok;
    },

    checkSelect: function(fld){                                 
        return parseInt(fld.val())!=0;
    },
    
    checkEmpty: function(fld){  
        return Boolean($.trim( fld.val() ));
    },

    checkLogin: function(fld){
        if(!this.checkEmpty(fld)) {
            return false;
        }

        var login = fld.val();
        if(login.length < this.options.loginMinLength) {
            this.showMessage('<b>логин</b> слишком короткий');  
            return false;
        }     
        
        var re = /^[a-zA-Z0-9_]*$/i;
        if(!re.test(login)) {
            this.showMessage('<b>логин</b> должен содержать только латиницу и цифры');  
            return false;
        }
        return true;
    },
    
    checkPassword: function(fld){
        if(!this.checkEmpty(fld)) {
            return false;
        }

        var pass = fld.val();
                
        if(fld.hasClass('check-password2'))
        {
            if(pass != $(this.options.password).val()) {
                this.showMessage('ошибка <b>подтверждения пароля</b>');  
                return false;
            }
            return true;
        }
        if(pass.length < this.options.passwordMinLength) {
            this.showMessage('<b>пароль</b> слишком короткий');  
            return false;
        }                
        if(this.options.passwordNotEqLogin && this.options.hasOwnProperty('login') && 
           (pass == this.options.login || pass == $(this.options.login).val() ) ) {
            this.showMessage('<b>логин</b> и <b>пароль</b> не должны совпадать');  
            return false;
        }
        return true;
    },
    
    checkEmail: function(fld){
        var re = /^\s*[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\s*$/i;
        if(this.checkEmpty(fld)) {
            var is_correct = re.test(fld.val());
            if(is_correct)
                fld.removeClass('clr-error');
            else
                fld.addClass('clr-error');

            return is_correct;
        }
        return false;
    }
};

bff.pgn.prototype = 
{
    initialize: function(form, options)
    {
        this.form = $(form).get(0);
        this.process = false;    
        this.options  = { progress: false, ajax: false };
        
        if(options) { for (var o in options) { 
            this.options[o] = options[o]; } }
        
        this.options.targetList = $(options.targetList);
        this.options.targetPagenation = $(options.targetPagenation);
        this.changeHash = (this.options.ajax && window.history && window.history.pushState);
    },
    prev: function(offset)
    {
        if(this.process) return;
        this.form['offset'].value = offset;
        this.update();
    },
    next: function(offset)
    {
        if(this.process) return;
        this.form['offset'].value = offset;  
        this.update();
    },
    update: function()
    {
        var self = this;
        if( ! self.options.ajax) {
            self.form.submit();
            return;
        }
        
        if(self.process)
            return;                            
            
        self.process = true;
        
        var url = $(self.form).attr('action');
        
        self.options.targetList.animate({'opacity': 0.65}, 400);
        bff.ajax(url, $(self.form).serialize(), function(data){
            if(data) {
                self.options.targetList.animate({'opacity': 1}, 100).html(data.list);
                self.options.targetPagenation.html(data.pgn);
            }
            
            if(self.changeHash) {
                var f = $(self.form).serialize();
                window.history.pushState({}, document.title, url + '?' + f);
            }            
            
            self.process = false;
        }, self.options.progress);
    }
};

//Text length
(function(){
  var lastLength = 0;
  window.checkTextLength = function(max_len, val, warn, nobr, limit){
    if(lastLength==val.length)return;
    lastLength=val.length;
    var n_len = replaceChars(val, nobr).length;
    warn.style.display = (n_len > max_len - 100) ? '' : 'none';
    if (n_len > max_len) {
      //if(limit && n_len + 50 > max_len) { limit.value = val.substr(0, max_len); return; }
      warn.innerHTML = 'Допустимый объем превышен на '+bff.declension(n_len - max_len, ['символ','символа','символов'])+'.';
    } else if (n_len > max_len - 50) {
      warn.innerHTML = 'Осталось: '+bff.declension(max_len - n_len, ['символ','символа','символов'])+'.';
    } else {
      warn.innerHTML = '';
    }
  };

  window.replaceChars = function(text, nobr) {
    var res = "";
    for (var i = 0; i<text.length; i++) {
      var c = text.charCodeAt(i);
      switch(c) {
        case 0x26: res += "&amp;"; break;
        case 0x3C: res += "&lt;"; break;
        case 0x3E: res += "&gt;"; break;
        case 0x22: res += "&quot;"; break;
        case 0x0D: res += ""; break;
        case 0x0A: res += nobr?"\t":"<br>"; break;
        case 0x21: res += "&#33;"; break;
        case 0x27: res += "&#39;"; break;
        default:   res += ((c > 0x80 && c < 0xC0) || c > 0x500) ? "&#"+c+";" : text.charAt(i); break;
      }
    }
    return res;
  };
})();

function onYMapError(err)
{
    $(function(){
        bff.error('YMap: '+err);
    });
}