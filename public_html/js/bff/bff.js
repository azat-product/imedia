/**
 * Bff.Utilites.js
 * @author Tamaranga | tamaranga.com
 * @version 0.71
 * @owr 71368931f096c93f9c8d1d34980a60dd
 * @modified 18.apr.2018
 */

var bff = {
h: !!(window.history && history.pushState),

extend: function(destination, source)
{
    if(destination.prototype) {
        for (var property in source)
            destination.prototype[property] = source[property];
    } else {
        for (var property in source)
            destination[property] = source[property];
    }
    return destination;
},

redirect: function(url, ask, timeout)
{
    if(!ask || (ask && confirm(ask))) {
        window.setTimeout(function(){ window.location.href = url; }, (timeout || 0) * 1000);
    }
    return false;
},    

isEmail: function(str)
{
    if(str.length<=6) return false;
    var re = /^\s*[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,14}\s*$/i;
    return re.test(str);
},

ajax_utils: (function(){
    return {
        errors: function(errors, system){
            if ( ! app.hasOwnProperty('adm') || ! app.adm) return;
            if (system) {
                 bff.error('Ошибка передачи данных'+(errors?'('+errors+')':''));
            } else {
                bff.errors.show(errors);
            }
        },
        progress: function(progressSelector){
            var timer_reached = false, response_received = false, progress = null,
                progressIsFunc = false, progressIsButton = false;
            if(progressSelector!=undefined && progressSelector!=false) {
                if( (progressIsFunc = (Object.prototype.toString.call(progressSelector) === "[object Function]")) ) {
                    progress =  progressSelector;
                } else {
                    progress = $(progressSelector);
                    if( ! progress.length) progress = false;
                    else if (progress.is('input[type="button"]') || progress.is('button')) {
                        progressIsButton = true;
                    }
                }
            }
            if(progress) {
                if(progressIsFunc) { progress.call(this, true); }
                else if (progressIsButton) { progress.prop('disabled', true); }
                else { progress.show(); }
                setTimeout(function() {
                    timer_reached = true;
                    that.finish();
                }, 400);
            }
            var that = {
                finish: function(response_received_set) {
                    if( response_received_set ) { response_received = true; }
                    if(timer_reached && response_received && progress){
                        if(progressIsFunc) { progress.call(this, false); }
                        else if (progressIsButton) { progress.removeProp('disabled'); }
                        else { progress.hide(); }
                    }
                }
            };
            return that;
        }
    }
}()),

ajax: function(url, params, callback, progressSelector, o)
{
    o = o || {async: true, crossDomain: false, onError: false};
    var progress = bff.ajax_utils.progress(progressSelector);
    return $.ajax({
        url: url, data: params, dataType: 'json', type: 'POST', crossDomain: (o.crossDomain || false), async: (o.async && true),
        success: function(resp, status, xhr) {
            progress.finish(true);
        
            if(resp == undefined) {
                if(status!=='success')
                    bff.ajax_utils.errors(0, true);
                if(callback) callback(false); return;
            }

            if (resp.errors && (($.isArray(resp.errors) && resp.errors.length) || $.isPlainObject(resp.errors)) ) {
                if (o.hasOwnProperty('onError') && o.onError !== false) {
                    o.onError(resp.errors);
                } else {
                    bff.ajax_utils.errors(resp.errors, false);
                }
            }
                
            if(callback) callback(resp.data, resp.errors);
        },
        error: function(xhr, status, e) {
            progress.finish(true);
            bff.ajax_utils.errors(xhr.status, true);
            if(o.onError) o.onError(xhr.status, true, status, e);
            if(callback) callback(false);
        }
    });
},

iframeSubmit: function(form, callback, o)
{
    var $form = $(form); if( ! $form.length ) return;
    o = $.extend({json:true, button:'', prefix:'bff_ajax_iframe', url:$form.attr('action'), progress:false, beforeSubmit:$.noop}, o||{});
    if( $form.hasClass(o.prefix+'_inited') ) return;
    var iframeID = parseInt((Math.random()*(999 - 1)+1));
    $form.before('<iframe name="'+o.prefix+iframeID+'" class="'+o.prefix+'" id="'+o.prefix+iframeID+'" style="display:none;"></iframe>');
    var $iframe = $('#'+o.prefix+iframeID);
    $form.attr({target:$iframe.attr('name'), action:o.url, method:'POST', enctype:'multipart/form-data'});
    $form.addClass(o.prefix+'_inited');
    var _process = false, $button = $(o.button); if (!$button.length) $button = false;
    $form.submit(function(){
        if(_process) return false;
        if($.isFunction(o.beforeSubmit)) if( o.beforeSubmit.apply(this, [$form]) === false ) return false;
        if(bff.filter('bff.iframeSubmit', false, form, callback, o)) return false;
        _process = true; if($button) $button.button('loading');
        var progress = bff.ajax_utils.progress(o.progress);
        $iframe.load(function(){
            progress.finish(true); _process = false;
            var response = $iframe.contents().find('body');
            if( o.json ) {
                var responseContent = response.text();
                responseContent = (responseContent !== '' ? $.parseJSON(responseContent) : {} );
                var data = $.extend({data:'',errors:[]}, responseContent || {});
                bff.ajax_utils.errors(data.errors, false);
                callback.apply(this, [data.data, data.errors]);
                bff.hook('bff.iframeSubmit.data', form, [data.data, data.errors]);
            } else {
                callback.apply(this, [response.html()]);
                bff.hook('bff.iframeSubmit.data', form, [response.html()]);
            }
            if($button) $button.button('reset');
            $iframe.unbind('load');
            setTimeout(function(){ response.html(''); }, 1);
        });
    });
},

ajaxURL: function(sModule, sActionQuery)
{
    return '/index.php?bff=ajax&s='+sModule+'&lng='+app.lng+'&act='+sActionQuery;
},

ymaps: function(container, center, centerZoom, callback, o)
{
    YMaps.load(function(){
        var map = new YMaps.Map($(container).get(0));
        if($.type(center) === 'string') {
            center = YMaps.GeoPoint.fromString(center);
        }
        map.setCenter( center, centerZoom || 12 );
        callback = callback || $.noop;
        callback(map);
    });
},

map: (function(){
    var YANDEX = 'yandex', GOOGLE = 'google';
    var currentType = YANDEX;

    function isYandex() {
        return (currentType === YANDEX);
    }
    function isGoogle() {
        return (currentType === GOOGLE);
    }

    return {
        setType: function(type) {
            currentType = type;
        },
        isYandex: isYandex,
        isGoogle: isGoogle,
        init: function (container, center, callback, o)
        {
            o = $.extend({controls:'view', zoom:16, marker:false}, o||{});
            if (!$.isArray(center)) {
                center = center.split(',', 2);
                center[0] = parseFloat(center[0]);
                center[1] = parseFloat(center[1]);
            }
            callback = callback || $.noop;

            var map, type = currentType;
            var self = {
                getMap: function(){
                    return map;
                },
                panTo: function(point, opts){
                    if (this.isYandex()) {
                        map.panTo(point, opts);
                    } else if (this.isGoogle()) {
                        map.panTo(($.isArray(point) ? new google.maps.LatLng(point[0], point[1]) : point));
                        if (opts && opts.hasOwnProperty('callback') && $.isFunction(opts['callback'])) {
                            opts.callback.call(self);
                        }
                    }
                },
                refresh: function(){
                    if (this.isYandex()) {
                        map.container.fitToViewport();
                    } else if (this.isGoogle()) {
                        var lastCenter = map.getCenter();
                        google.maps.event.trigger(map, 'resize');
                        map.setCenter(lastCenter);
                    }
                },
                isYandex: function(){ return (type === YANDEX); },
                isGoogle: function(){ return (type === GOOGLE); }
            };

            switch(type)
            {
                case YANDEX:
                    ymaps.ready(function(){
                        var controls = {view:'smallMapDefaultSet',search:'mediumMapDefaultSet'};
                        map = new ymaps.Map(container, {
                            zoom: o.zoom,
                            center: center,
                            controls: ( controls.hasOwnProperty(o.controls) ? [controls[o.controls]] : o.controls )
                        }, {autoFitToViewport: 'always'});
                        map.behaviors.disable('scrollZoom');
                        map.container.fitToViewport();

                        callback.call(self, map, type);
                        if (o.marker) {
                            map.geoObjects.add(new ymaps.Placemark(map.getCenter(), {draggable: false}));
                        }
                    });
                    break;
                case GOOGLE:
                    $(function(){
                        if (typeof container === 'string' || container instanceof String) {
                            container = document.getElementById(container);
                        }
                        map = new google.maps.Map(container, {
                            zoom: o.zoom,
                            center: new google.maps.LatLng(center[0], center[1]),
                            mapTypeId: google.maps.MapTypeId.ROADMAP,
                            scrollwheel: false
                        });

                        callback.call(self, map, type);
                        if (o.marker) {
                            new google.maps.Marker({position: map.getCenter(), map: map, draggable: false});
                        }
                    });
                    break;
            }

            return self;
        },
        editor: function()
        {
            if (isGoogle()) {
                return bffmapEditorGoogle();
            }
            return bffmapEditorYandex();
        },
        coordsFromString: function (v)
        {
            if ( ! $.isArray(v)) {
                v = v.split(',', 2);
            }
            v = [parseFloat(v[0]), parseFloat(v[1])];
            if (isGoogle()) {
                return new google.maps.LatLng(v[0], v[1]);
            }
            return v;
        }
    };
}()),

maxlength: function(element, options)
{
    var o = {}, $e, $m, maxLength = 0, lastLength;

    $(function(){
        o = $.extend(true, {
            limit:0, // max. text length
            message:false, // message block
            cut:true, // cut
            escape:false, //escape chars
            lang: {
                left: '[symbols] осталось',
                symbols: ['знак','знака','знаков']
            },
            nobr:false, // true: convert \n => <br />, false \n => \t
            onError:$.noop, // error callback
            onChange:$.noop // change callback
        }, options);

        maxLength = intval(o.limit);
        $e = $(element); if( ! $e.length || o.limit <=0 ) return;
        $e.on('keyup input paste', function(){ _check($e.val()); o.onChange($e); });
        $m = $(o.message); o.message = ( $m.length > 0 );

        _check($e.val());
        if( ! o.cut ) $e.attr({maxlength: maxLength});
    });

    function _check(value)
    {
        if(lastLength === value.length) return;
        lastLength = value.length;
        var currentLength = ( o.escape ? _escape(value).length : value.length );
        if( ! o.message ) return;
        if (currentLength > maxLength) {
            if(o.cut) {
                $e.val( value.substr(0, maxLength) );
            } else {
                if(o.onError && $.isFunction(o.onError)) {
                    o.onError();
                }
            }
        } else {
            $m.text( o.lang.left.toString().replace(new RegExp('\\[symbols\\]'), bff.declension(maxLength-currentLength, o.lang.symbols)) );
        }
    }

    function _escape(text)
    {
        var res = '';
        for (var i = 0; i < text.length; i++) {
          var c = text.charCodeAt(i);
          switch(c) {
            case 0x26: res += '&amp;'; break;
            case 0x3C: res += '&lt;'; break;
            case 0x3E: res += '&gt;'; break;
            case 0x22: res += '&quot;'; break;
            case 0x0D: res += ''; break;
            case 0x0A: res += ( o.nobr ? "\t" : '<br />' ); break;
            case 0x21: res += '&#33;'; break;
            case 0x27: res += '&#39;'; break;
            default:   res += ((c > 0x80 && c < 0xC0) || c > 0x500) ? '&#'+c+';' : text.charAt(i); break;
          }
        }
        return res;
    }
},

// author: Sergey Chikuyonok
placeholder: (function(){
    var data_key = 'plc-label',
        fields_key = 'bindedFiles';
    // is placeholder supported by browser (Webkit, Firefox 3.7) 
    var nativePlaceholder = ('placeholder' in document.createElement('input'));
         
    /**
     * Функция, отвечающая за переключение отображения заполнителя.
     * Срабатывает автоматически при фокусе/блюре с элемента ввода.
     * @param {Event} evt
     */
    function placeholderSwitcher(evt) {
        var input = $(this),
            label = input.data(data_key);

        if (!$.trim(input.val()) && evt.type == 'blur')
            label.show();
        else
            label.hide();
    }

    function focusOnField(evt) {
        var binded_fields = $(this).data(fields_key);

        if (binded_fields) {
            $(binded_fields).filter(':visible:first').focus();
            evt.preventDefault();
        }
    }

    function linkPlaceholderWithField(label, field) {
        label = $(label);
        field = $(field);
                   
        if (!label.length || !field.length)
            return;     
            
        /** @type Array */
        var binded_fields = label.data(fields_key);

        if (!binded_fields) {
            binded_fields = [];
            label
                .data(fields_key, binded_fields)
                .click(focusOnField);
        }

        binded_fields.push(field[0]);
        field.data(data_key, label)
            .bind('focus blur', placeholderSwitcher) 
            .blur();             
    }

    return {
        init: function(context) {                     
            $(context || document).find('label.placeholder').each(function(){
                if(nativePlaceholder) return;
                linkPlaceholderWithField(this, '#'+$(this).attr('for'));
                $( $(this).data(fields_key) ).blur();
            }); 
        },

        linkWithField: linkPlaceholderWithField
    };
})(),

st: {                      
    versioninig: false, _in: {}, 
    _url: function(url){
        if(url.indexOf('http') == 0) return url;
        return ( url.indexOf('core') == 0 ? '/js/bff'+url.substr(4) : '/js/'+url );
    },
    includeJS: function(url, callback, ver) {
       ver = ver || 1.0;
       if(this.isDone(url)) {
            if(callback) callback();
            return false;
        }
        this.done(url,ver);
        $.getScript( (this._url(url) + (this.versioninig ? '?v=' + ver : '')) , function(){
            if(callback) callback();
        });
    },
    includeCSS: function(url, ver) {
        ver = ver || 1.0;
        if(this.isDone(url)) {
            if(callback) callback();
            return false;
        }
        this.done(url,ver);
        var el = document.createElement('link'); el.href = (this._url(url) + (this.versioninig ? '?v=' + ver : '')); el.rel = 'stylesheet'; el.type = 'text/css';
        document.getElementsByTagName('head')[0].appendChild(el);
    },
    isDone: function(url) {
        return this._in.hasOwnProperty(url);
    },
    done: function(url, ver) {
        ver = ver || 1.0;
        this._in[url] = ver;
    }
},

input: {
    file: function(self, id) {
        var file = self.value.split("\\");
        file = file[file.length-1];
        if( file.length>30 ) file = file.substring(0, 30)+'...';
        var html = '<a href="#delete" onclick="bff.input.reset(\''+self.id+'\'); $(\'#'+id+'\').html(\'\'); $(this).blur(); return false;"></a>' + file;
        $('#'+id).html(html);
    },
    reset: function(id) {
        var o = document.getElementById(id);
        if (o.type == 'file') {
            try{
                o.parentNode.innerHTML = o.parentNode.innerHTML;
            } catch(e){}
        } else {
            o.value = '';
        }
    }
},

declension: function(count, forms, add) {
    var prefix = (add!==false ? count+' ':'');
    var n = Math.abs(count) % 100; var n1 = n % 10;  
    if (n > 10 && n < 20) { return prefix+forms[2]; }
    if (n1 > 1 && n1 < 5) { return prefix+forms[1]; }
    if (n1 == 1) { return prefix+forms[0]; }
    return prefix+forms[2];
},

/**
 * Создает куки или возвращает значение.
 * @examples:
 * bff.cookie('the_cookie', 'the_value'); - Задает куки для сессии.
 * bff.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'site.com', secure: true }); Создает куки с опциями.
 * bff.cookie('the_cookie', null); - Удаляет куки.
 * bff.cookie('the_cookie'); - Возвращает значение куки.
 *
 * @param {String} name Имя куки.
 * @param {String} value Значение куки.
 * @param {Object} options Объект опций куки.
 * @option {Number|Date} expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                               If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                               If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                               when the the browser exits.
 * @option {String} path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option {String} domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option {Boolean} secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                          require a secure protocol (like HTTPS).
 *
 * @return {mixed} значение куки
 * @author Klaus Hartl (klaus.hartl@stilbuero.de), Vlad Yakovlev (red.scorpix@gmail.com)
 * @version 1.0.1, @date 2009-11-12
 */
_cookie: function(name, value, options) {
    if ('undefined' != typeof value) {
        options = options || {};                        
        if (null === value) {
            value = '';
            options.expires = -1;
        }
        // CAUTION: Needed to parenthesize options.path and options.domain in the following expressions,
        // otherwise they evaluate to undefined in the packed version for some reason…
        var path = options.path ? '; path=' + options.path : '',
            domain = options.domain ? '; domain=' + options.domain : '',
            secure = options.secure ? '; secure' : '',
            expires = '';

        if (options.expires && ('number' == typeof options.expires || options.expires.toUTCString)) {
            var date; 
            if ('number' == typeof options.expires) {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 86400000/*24 * 60 * 60 * 1000*/));
            } else {
                date = options.expires;
            }   
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }   
        window.document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
        return true;
    }

    var cookieValue = null;
    if (document.cookie && '' != document.cookie) {
        $.each(document.cookie.split(';'), function() {
            var cookie = $.trim(this);
            // Does this cookie string begin with the name we want?
            if (cookie.substring(0, name.length + 1) == (name + '=')) {
                cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                return false;
            }
        });
    }

    return cookieValue;
},

cookie: function(name, value, options) {
  return bff._cookie(name, value, options);
},

fp: function(){
    return md5([navigator.userAgent,[screen.height,screen.width,screen.colorDepth].join("x"),(new Date).getTimezoneOffset(),!!window.sessionStorage,!!window.localStorage,$.map(navigator.plugins,function(n){return[n.name,n.description,$.map(n,function(n){return[n.type,n.suffixes].join("~")}).join(",")].join("::")}).join(";")].join("###"));
},

hooks: (function(){
    var data = {};
    return {
        run: function(/* key, arg1, arg2, ... */) {
            var args = Array.prototype.slice.call(arguments, 0);
			var key = args.shift();
			if (typeof key !== 'string' || !data[key]) {
			    return false;
			}
			var len = data[key].length, i;
			for (i = 0; i < len; i++) {
                data[key][i].callback.apply(data[key][i].context, args);
            }
            return true;
        },
        filter: function(/* key, value, arg1, ... */) {
            var args = Array.prototype.slice.call(arguments, 0);
            var key = args.shift();
			if (typeof key !== 'string' || !data[key]) {
			    return args[0];
			}
			var len = data[key].length, i;
			for (i = 0; i < len; i++) {
                args[0] = data[key][i].callback.apply(data[key][i].context, args);
            }
            return args[0];
        },
        add: function(key, callback, priority, context) {
            if (typeof key !== 'string' || typeof callback !== 'function') {
                return;
            }
            var hook = {
                callback: callback,
                priority: intval(priority || 5),
                context: context
            };
            var hooks = data[key];
            if (hooks) {
                hooks.push(hook);
                hooks.sort(function(a,b){return a.priority > b.priority});
            } else {
                hooks = [hook];
            }
            data[key] = hooks;
        },
        remove: function(key, callback) {
            if (!data[key]) {
                return;
            }
            if (!callback) {
				data[key] = [];
				return;
			}
			var hooks = data[key];
			for (var i = hooks.length; i--;) {
                if (hooks[i].callback === callback) {
                    hooks.splice(i, 1);
                }
            }
        },
        has: function(key) {
            return !!data[key];
        }
    }
}()),

hook: function(/* key, arg1, arg2, ... */) {
    return bff.hooks.run.apply(this, arguments);
},

hookAdd: function(key, callback, priority, context) {
    return bff.hooks.add(key, callback, priority, context);
},

hooksAdded: function(key) {
    return bff.hooks.has(key);
},

hookRemove: function(key, callback) {
    return bff.hooks.remove(key, callback);
},

filter: function(/* key, value, arg1, ... */) {
    return bff.hooks.filter.apply(this, arguments);
}

};

(function(){
	var ua = navigator.userAgent.toLowerCase(), browser = {};
	var matched = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
		/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
		/(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
		/(msie) ([\w.]+)/.exec( ua ) ||
		ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
		[];
    matched = {
        browser: matched[ 1 ] || "",
        version: matched[ 2 ] || "0"
    };
    if( matched.browser ) {
        browser[ matched.browser ] = true;
        browser.version = matched.version;
    }
    if( browser.chrome ) { browser.webkit = true; }
    else if ( browser.webkit ) { browser.safari = true; }
    bff.browser = browser;
}());

// Common shortcut-functions
function nothing(e)
{
    if( ! e) {
        if (window.event) e = window.event;
        else return false;
    }
    if (e.cancelBubble != null) e.cancelBubble = true;
    if (e.stopPropagation) e.stopPropagation();
    if (e.preventDefault) e.preventDefault();
    if (window.event) e.returnValue = false;
    if (e.cancel != null) e.cancel = true;
    return false;
}
    
function intval(number) 
{
    return number && + number | 0 || 0;
}

//Exceptions
function bff_report_exception(msg, url, line) {
    $.ajax({ url: '/index.php?bff=errors-js', data: {'e': msg, 'f': url || window.location.href, 'l': line}, dataType: 'json', type: 'POST' });
}
window.onerror = function (msg, url, line) { bff_report_exception(msg, url, line); };

$(function(){

    // Placeholder
    bff.placeholder.init();

});

(function($){

  // Simple JavaScript Templating, John Resig - http://ejohn.org/blog/javascript-micro-templating/ - MIT Licensed
  var cache = {};
  bff.tmpl = function (str, data){
    var fn = !/\W/.test(str) ? cache[str] = cache[str] || bff.tmpl(document.getElementById(str).innerHTML) :
      new Function("obj", "var p=[],print=function(){p.push.apply(p,arguments);};with(obj){p.push('" +
        str.replace(/[\r\t\n]/g, " ").split("<%").join("\t").replace(/((^|%>)[^\t]*)'/g, "$1\r")
           .replace(/\t=(.*?)%>/g, "',$1,'").split("\t").join("');").split("%>").join("p.push('").split("\r").join("\\'")
      + "');}return p.join('');");
    return data ? fn( data ) : fn;
  };

    /* Debounce and throttle function's decorator plugin 1.0.4 Copyright (c) 2009 Filatov Dmitry (alpha@zforms.ru)
     * Dual licensed under the MIT and GPL licenses: http://www.opensource.org/licenses/mit-license.php, http://www.gnu.org/licenses/gpl.html
     */
  $.extend({
        debounce : function(fn, timeout, invokeAsap, context) {
            if(arguments.length == 3 && typeof invokeAsap != 'boolean') {
                context = invokeAsap;
                invokeAsap = false;
            }
            var timer;
            return function() {
                var args = arguments;
                if(invokeAsap && !timer) { fn.apply(context, args); }
                clearTimeout(timer);
                timer = setTimeout(function() { if(!invokeAsap) { fn.apply(context, args); } timer = null; }, timeout);
            };
        },
        throttle : function(fn, timeout, context) {
            var timer, args;
            return function() {
                args = arguments;
                if(!timer) {
                    (function() {
                        if(args) { fn.apply(context, args); args = null; timer = setTimeout(arguments.callee, timeout); }
                        else { timer = null; }
                    })();
                }
            };
        },
        assert : function(cond, msg, force_report) {
            if (!cond) {
                bff_report_exception(msg, window.location.href, window.location.href);
            }
        }
    });

})(jQuery);

//------------------------------------------------------------------------------
function Flash () {
    this._swf = ''; this._width = 0; this._height = 0; this._params = [];
}
Flash.prototype = { 
setSWF: function (_swf, _width, _height) { this._swf = _swf; this._width = _width; this._height = _height; },
setParam: function (paramName, paramValue) { this._params[this._params.length] = paramName+'|||'+paramValue; },
display: function () {
    var _txt = '';
    var params_res = '';
    _txt += '<object >\n';
    _txt += '<param width="'+this._width+'" height="'+this._height+'" name="movie" value="'+this._swf+'" />\n'
    _txt += '<param name="quality" value="high" />\n';
    for ( i=0;i<this._params.length;i++ ) {
        _param = this._params[i].split ('|||');
        _txt += '\t<param name="'+_param[0]+'" value="'+_param[1]+'" />\n';
        params_res += _param[0]+'="'+_param[1]+'" ';
    }

    _txt += '<embed width="'+this._width+'" height="'+this._height+'" src="'+this._swf+'" '+params_res+' quality="high" type="application/x-shockwave-flash"></embed>';
    _txt += '</object>';
    document.write (_txt);
}};

/**
 * Copyright (c) Copyright (c) 2007, Carl S. Yestrau All rights reserved.
 * Code licensed under the BSD License: http://www.featureblend.com/license.txt
 */
var FlashDetect = new function(){var self = this; self.installed = false;
    (function(){
        if(navigator.plugins && navigator.plugins.length>0){
            var type = 'application/x-shockwave-flash', mimeTypes = navigator.mimeTypes;
            if(mimeTypes && mimeTypes[type] && mimeTypes[type].enabledPlugin && mimeTypes[type].enabledPlugin.description){
                self.installed = true;
            }
        } else if(window.execScript){
            var found = false, activeXDetectRules = ['ShockwaveFlash.ShockwaveFlash','ShockwaveFlash.ShockwaveFlash.7','ShockwaveFlash.ShockwaveFlash.6'];
            var getActiveXObject = function(name){ var obj = -1;try{obj = new ActiveXObject(name);}catch(err){obj = {activeXError:true};} return obj; };
            for(var i=0; i<activeXDetectRules.length && found===false; i++){
                var obj = getActiveXObject(activeXDetectRules[i]);
                if(!obj.activeXError){ self.installed = true; found = true; }
            }
        }
    }());
};

//------------------------------------------------------------------------------
jQuery.fn.fadeToggle=function(speed,easing,callback){return this.animate({opacity:"toggle"},speed,easing,callback)};
jQuery.fn.slideFadeToggle=function(speed,easing,callback){return this.animate({opacity:"toggle",height:"toggle"},speed,easing,callback)};

/**
 * Auto Expanding Text Area (1.2.2) by Chrys Bader (www.chrysbader.com) chrysb@gmail.com
 * Copyright (c) 2008 Chrys Bader (www.chrysbader.com)
 * Dual licensed under the MIT (MIT-LICENSE.txt) and GPL (GPL-LICENSE.txt) licenses.
 */
(function(b){b.fn.autogrow=function(a){return this.each(function(){new b.autogrow(this,a)})};b.autogrow=function(a,c){this.options=c||{};this.interval=this.dummy=null;this.line_height=this.options.lineHeight||parseInt(b(a).css("line-height"));this.min_height=this.options.minHeight||parseInt(b(a).css("min-height"));this.max_height=this.options.maxHeight||parseInt(b(a).css("max-height"));this.textarea=b(a);if(this.line_height==NaN)this.line_height=0;if(this.min_height==NaN||this.min_height==0)this.min_height= this.textarea.height();this.init()};b.autogrow.fn=b.autogrow.prototype={autogrow:"1.2.2"};b.autogrow.fn.extend=b.autogrow.extend=b.extend;b.autogrow.fn.extend({init:function(){var a=this;this.textarea.css({overflow:"hidden",display:"block"});this.textarea.bind("focus",function(){a.startExpand()}).bind("blur",function(){a.stopExpand()});this.checkExpand()},startExpand:function(){var a=this;this.interval=window.setInterval(function(){a.checkExpand()},400)},stopExpand:function(){clearInterval(this.interval)}, checkExpand:function(){if(this.dummy==null){this.dummy=b("<div></div>");this.dummy.css({"font-size":this.textarea.css("font-size"),"font-family":this.textarea.css("font-family"),width:this.textarea.css("width"),padding:this.textarea.css("padding"),"line-height":this.line_height+"px","overflow-x":"hidden",position:"absolute",top:0,left:-9999}).appendTo("body")}var a=this.textarea.val().replace(/(<|>)/g,"");a=bff.browser.msie?a.replace(/\n/g,"<BR>new"):a.replace(/\n/g,"<br>new");if(this.dummy.html()!= a){this.dummy.html(a);if(this.max_height>0&&this.dummy.height()+this.line_height>this.max_height)this.textarea.css("overflow-y","auto");else{this.textarea.css("overflow-y","hidden");if(this.textarea.height()<this.dummy.height()+this.line_height||this.dummy.height()<this.textarea.height())this.textarea.animate({height:this.dummy.height()+this.line_height+"px"},100)}}}})})(jQuery);

/**
 * Copyright (c) 2007 Ariel Flesler - aflesler@gmail.com | https://github.com/flesler
 * Licensed under MIT
 * @author Ariel Flesler
 * @version 2.1.2
 */
;(function(f){"use strict";"function"===typeof define&&define.amd?define(["jquery"],f):"undefined"!==typeof module&&module.exports?module.exports=f(require("jquery")):f(jQuery)})(function($){"use strict";function n(a){return!a.nodeName||-1!==$.inArray(a.nodeName.toLowerCase(),["iframe","#document","html","body"])}function h(a){return $.isFunction(a)||$.isPlainObject(a)?a:{top:a,left:a}}var p=$.scrollTo=function(a,d,b){return $(window).scrollTo(a,d,b)};p.defaults={axis:"xy",duration:0,limit:!0};$.fn.scrollTo=function(a,d,b){"object"=== typeof d&&(b=d,d=0);"function"===typeof b&&(b={onAfter:b});"max"===a&&(a=9E9);b=$.extend({},p.defaults,b);d=d||b.duration;var u=b.queue&&1<b.axis.length;u&&(d/=2);b.offset=h(b.offset);b.over=h(b.over);return this.each(function(){function k(a){var k=$.extend({},b,{queue:!0,duration:d,complete:a&&function(){a.call(q,e,b)}});r.animate(f,k)}if(null!==a){var l=n(this),q=l?this.contentWindow||window:this,r=$(q),e=a,f={},t;switch(typeof e){case "number":case "string":if(/^([+-]=?)?\d+(\.\d+)?(px|%)?$/.test(e)){e= h(e);break}e=l?$(e):$(e,q);case "object":if(e.length===0)return;if(e.is||e.style)t=(e=$(e)).offset()}var v=$.isFunction(b.offset)&&b.offset(q,e)||b.offset;$.each(b.axis.split(""),function(a,c){var d="x"===c?"Left":"Top",m=d.toLowerCase(),g="scroll"+d,h=r[g](),n=p.max(q,c);t?(f[g]=t[m]+(l?0:h-r.offset()[m]),b.margin&&(f[g]-=parseInt(e.css("margin"+d),10)||0,f[g]-=parseInt(e.css("border"+d+"Width"),10)||0),f[g]+=v[m]||0,b.over[m]&&(f[g]+=e["x"===c?"width":"height"]()*b.over[m])):(d=e[m],f[g]=d.slice&& "%"===d.slice(-1)?parseFloat(d)/100*n:d);b.limit&&/^\d+$/.test(f[g])&&(f[g]=0>=f[g]?0:Math.min(f[g],n));!a&&1<b.axis.length&&(h===f[g]?f={}:u&&(k(b.onAfterFirst),f={}))});k(b.onAfter)}})};p.max=function(a,d){var b="x"===d?"Width":"Height",h="scroll"+b;if(!n(a))return a[h]-$(a)[b.toLowerCase()]();var b="client"+b,k=a.ownerDocument||a.document,l=k.documentElement,k=k.body;return Math.max(l[h],k[h])-Math.min(l[b],k[b])};$.Tween.propHooks.scrollLeft=$.Tween.propHooks.scrollTop={get:function(a){return $(a.elem)[a.prop]()}, set:function(a){var d=this.get(a);if(a.options.interrupt&&a._last&&a._last!==d)return $(a.elem).stop();var b=Math.round(a.now);d!==b&&($(a.elem)[a.prop](b),a._last=this.get(a))}};return p});

// Deserialize
(function($) {
	$.fn.deserialize = function(data, clearForm, clearFilter) {
		this.each(function(){
			deserialize(this, data, !!clearForm, clearFilter||false);
		});
	};
	function deserialize(element, data, clearForm, clearFilter)
	{
		var splits = decodeURIComponent(data).split('&'), i = 0, split = null, key = null, value = null, splitParts = null;
		if (clearForm) {
			var clInp = $('input[type="checkbox"],input[type="radio"]', element);
			if(clearFilter) clInp = clInp.filter(clearFilter); clInp.prop('checked', false);
			    clInp = $('select,input[type="text"],input[type="password"],input[type="hidden"],textarea', element);
			if(clearFilter) clInp = clInp.filter(clearFilter); clInp.val('');
		}
		var kv = {};
		while(split = splits[i++]){ splitParts = split.split('='); key = splitParts[0] || ''; value = (splitParts[1] || '').replace(/\+/g, ' ');
			if (key != ''){ if( key in kv ){ if( $.type(kv[key]) !== 'array' ) kv[key] = [kv[key]]; kv[key].push(value); } else kv[key] = value; }
		}
		for( key in kv ) {
		    value = kv[key];
		    $('select[name="'+ key +'"],input[type="text"][name="'+ key +'"],input[type="password"][name="'+ key +'"],input[type="hidden"][name="'+ key +'"],textarea[name="'+ key +'"]', element).val(value);
		    if( ! $.isArray(value) ) value = [value];
		    for( var key2 in value ) {
		        var value2 = value[key2];
			    $('input[type="checkbox"][name="'+ key +'"][value="'+ value2 +'"],input[type="radio"][name="'+ key +'"][value="'+ value2 +'"]', element).prop('checked', true);
            }
		}
    }
})(jQuery);

/**
 * http://www.myersdaily.org/joseph/javascript/md5-text.html
 */
!function(n){var r=function(n,r){var t=n[0],c=n[1],i=n[2],a=n[3];t=o(t,c,i,a,r[0],7,-680876936),a=o(a,t,c,i,r[1],12,-389564586),i=o(i,a,t,c,r[2],17,606105819),c=o(c,i,a,t,r[3],22,-1044525330),t=o(t,c,i,a,r[4],7,-176418897),a=o(a,t,c,i,r[5],12,1200080426),i=o(i,a,t,c,r[6],17,-1473231341),c=o(c,i,a,t,r[7],22,-45705983),t=o(t,c,i,a,r[8],7,1770035416),a=o(a,t,c,i,r[9],12,-1958414417),i=o(i,a,t,c,r[10],17,-42063),c=o(c,i,a,t,r[11],22,-1990404162),t=o(t,c,i,a,r[12],7,1804603682),a=o(a,t,c,i,r[13],12,-40341101),i=o(i,a,t,c,r[14],17,-1502002290),c=o(c,i,a,t,r[15],22,1236535329),t=u(t,c,i,a,r[1],5,-165796510),a=u(a,t,c,i,r[6],9,-1069501632),i=u(i,a,t,c,r[11],14,643717713),c=u(c,i,a,t,r[0],20,-373897302),t=u(t,c,i,a,r[5],5,-701558691),a=u(a,t,c,i,r[10],9,38016083),i=u(i,a,t,c,r[15],14,-660478335),c=u(c,i,a,t,r[4],20,-405537848),t=u(t,c,i,a,r[9],5,568446438),a=u(a,t,c,i,r[14],9,-1019803690),i=u(i,a,t,c,r[3],14,-187363961),c=u(c,i,a,t,r[8],20,1163531501),t=u(t,c,i,a,r[13],5,-1444681467),a=u(a,t,c,i,r[2],9,-51403784),i=u(i,a,t,c,r[7],14,1735328473),c=u(c,i,a,t,r[12],20,-1926607734),t=e(t,c,i,a,r[5],4,-378558),a=e(a,t,c,i,r[8],11,-2022574463),i=e(i,a,t,c,r[11],16,1839030562),c=e(c,i,a,t,r[14],23,-35309556),t=e(t,c,i,a,r[1],4,-1530992060),a=e(a,t,c,i,r[4],11,1272893353),i=e(i,a,t,c,r[7],16,-155497632),c=e(c,i,a,t,r[10],23,-1094730640),t=e(t,c,i,a,r[13],4,681279174),a=e(a,t,c,i,r[0],11,-358537222),i=e(i,a,t,c,r[3],16,-722521979),c=e(c,i,a,t,r[6],23,76029189),t=e(t,c,i,a,r[9],4,-640364487),a=e(a,t,c,i,r[12],11,-421815835),i=e(i,a,t,c,r[15],16,530742520),c=e(c,i,a,t,r[2],23,-995338651),t=f(t,c,i,a,r[0],6,-198630844),a=f(a,t,c,i,r[7],10,1126891415),i=f(i,a,t,c,r[14],15,-1416354905),c=f(c,i,a,t,r[5],21,-57434055),t=f(t,c,i,a,r[12],6,1700485571),a=f(a,t,c,i,r[3],10,-1894986606),i=f(i,a,t,c,r[10],15,-1051523),c=f(c,i,a,t,r[1],21,-2054922799),t=f(t,c,i,a,r[8],6,1873313359),a=f(a,t,c,i,r[15],10,-30611744),i=f(i,a,t,c,r[6],15,-1560198380),c=f(c,i,a,t,r[13],21,1309151649),t=f(t,c,i,a,r[4],6,-145523070),a=f(a,t,c,i,r[11],10,-1120210379),i=f(i,a,t,c,r[2],15,718787259),c=f(c,i,a,t,r[9],21,-343485551),n[0]=l(t,n[0]),n[1]=l(c,n[1]),n[2]=l(i,n[2]),n[3]=l(a,n[3])},t=function(n,r,t,o,u,e){return r=l(l(r,n),l(o,e)),l(r<<u|r>>>32-u,t)},o=function(n,r,o,u,e,f,c){return t(r&o|~r&u,n,r,e,f,c)},u=function(n,r,o,u,e,f,c){return t(r&u|o&~u,n,r,e,f,c)},e=function(n,r,o,u,e,f,c){return t(r^o^u,n,r,e,f,c)},f=function(n,r,o,u,e,f,c){return t(o^(r|~u),n,r,e,f,c)},c=function(n){var t,o=n.length,u=[1732584193,-271733879,-1732584194,271733878];for(t=64;t<=n.length;t+=64)r(u,i(n.substring(t-64,t)));n=n.substring(t-64);var e=[0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];for(t=0;t<n.length;t++)e[t>>2]|=n.charCodeAt(t)<<(t%4<<3);if(e[t>>2]|=128<<(t%4<<3),t>55)for(r(u,e),t=0;16>t;t++)e[t]=0;return e[14]=8*o,r(u,e),u},i=function(n){var r,t=[];for(r=0;64>r;r+=4)t[r>>2]=n.charCodeAt(r)+(n.charCodeAt(r+1)<<8)+(n.charCodeAt(r+2)<<16)+(n.charCodeAt(r+3)<<24);return t},a="0123456789abcdef".split(""),d=function(n){for(var r="",t=0;4>t;t++)r+=a[n>>8*t+4&15]+a[n>>8*t&15];return r},h=function(n){for(var r=0;r<n.length;r++)n[r]=d(n[r]);return n.join("")},v=n.md5=function(n){return h(c(n))},l=function(n,r){return n+r&4294967295};if("5d41402abc4b2a76b9719d911017c592"!=v("hello"))var l=function(n,r){var t=(65535&n)+(65535&r),o=(n>>16)+(r>>16)+(t>>16);return o<<16|65535&t}}(window);