/**
 * Bff.Utilites.AdminEx.js
 * @author Tamaranga | tamaranga.com
 * @version 0.437
 * @modified 6.sep.2017
 */

app.adm = true;

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
        $block.click(function(){if( ! err_clicked){ err_clicked = true; }});
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
                    var res = []; for(var i in msg) res.push(msg[i]);
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

//        $(doc).keydown(function(e) {
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
}

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
        t.required_fields.bind('blur keyup change', $.debounce(function(){ return t.check(); }, 400));
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

    showMessage: function( text ){
        if(this.options.errorMessage) {
            this.errorMessageText.html('<li>'+text+'</li>'); 
            if(!this.errorMessageBlock.is(':visible'))     
                this.errorMessageBlock.fadeIn();
            
            this.errorMessageShowed = true; 
        }
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