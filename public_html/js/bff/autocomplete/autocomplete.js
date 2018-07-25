/**
 * Bff.Autocomplete.js
 * @author Tamaranga | tamaranga.com
 * @version 0.62
 * @modified 4.aug.2016
 */

(function($){

    $.autocomplete = function (textInput, url, o)
    {
        o = $.extend(true, {valueInput: false, list:false, cancel: false,
            minChars : 2, timeout: 450, progress: true, suggest: false, params: {},
            after: false, before: false, validate: true, onSelect: false, onShow: false, doPrepareText: false,
            highlight: true, classes: {dropdown:'autocomplete', notfound:'', active:'hovered', progress:'autocomplete-progress', parent:''},
            newElement: false, newElementValue: '__##'
        }, o||{});
        var $text = $(textInput), $value = $(o.valueInput), $cancel = $(o.cancel), $dropdown,
            typingTimeout, size = 0, hovered = false, cacheQuery = {}, cacheData = {};

        $text.attr({autocomplete:'off'});
        if( o.hasOwnProperty('placeholder') ) $text.attr('placeholder', o.placeholder);
        if( ! (o.width || false) ) o.width = $text.outerWidth() - 2;

        if( $cancel.length ) {
            $cancel.click(function(e){
                nothing(e);
                doSelect(0,'');
                $text.focus();
            });
        }

        if(o.list === false) {
            $text.after('<ul class="'+ o.classes.dropdown+'"></ul>');
            $dropdown = $text.next().css({width: o.width});
        } else {
            $dropdown = $(o.list);
        }

        if(o.suggest && o.suggest!==false) {
            if($.isArray(o.suggest)) { for(var i in o.suggest) { cacheData[o.suggest[i][0]] = o.suggest[i]; } }
        }

        $dropdown.on('mouseover mousedown', 'li', function(e){ // mouseover mouseout
            if(e.type === 'mouseover') { if(hovered!==false) { $dropdown.children().eq(hovered).removeClass(o.classes.active); } }
            else {
                var i = $(this).data('value');
                doSelect( i, ( o.doPrepareText ? o.doPrepareText( $(this).html() ) : $(this).text() ) );
                clearDropdown();
                if(i === '-2')  { window.setTimeout(function() { $text.focus(); }, 300); }
            }
        });

        function doSelect(val, txt, isEnter)
        {
            if(val === '-1'){ return; }
            if(val === '-2' && o.suggest!==false){ val = 0; txt = ''; o.suggest = false; }
            var curVal = getValue();
            $value.val(val);
            $text.val(txt);
            if($cancel.length) $cancel.toggle(txt.length > 0);
            if(o.onSelect) {
                var data = false; if( cacheData.hasOwnProperty(val) ) { data = cacheData[val]; }
                var extra = {data: data, value: val, title: txt, prev: curVal, changed: (intval(curVal)!==intval(val)), enter:(isEnter===true)};
                o.onSelect(val, txt, extra);
            }
        }

        function getData(text)
        {
            window.clearInterval(typingTimeout);
            if(o.minChars !== null && text.length >= o.minChars)
            {
                clearDropdown();
                if(cacheQuery.hasOwnProperty(text)) {
                    showData(cacheQuery[text], text, false);
                } else {
                    o.params['q'] = text;
                    if(o.before) { o.before($text,text); }
                    bff.ajax(url, o.params, function(data) {
                        if(data) {
                            showData((cacheQuery[text] = data), text, true);
                            if(o.after) { o.after($text, text, data); }
                        }
                    }, function(){ if(o.progress) $text.toggleClass(o.classes.progress); });
                }
                $cancel.show();
            }
            if( ! text.length) {
                $cancel.hide();
            }
        }

        function showData(data, textHighlight, newData)
        {
            textHighlight = ( o.highlight ? (textHighlight + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\-]','g'),'\\$&') : '');
            var items = '', reg = new RegExp('('+textHighlight+')','i');
            if($.isArray(data)) {
                size = data.length;
                for(var i in data) {
                    if(newData) { cacheData[data[i][0]] = data[i]; }
                    items += '<li data-value="' + data[i][0] + '">' + data[i][1].toString().replace(reg,"<strong>$1</strong>") + '</li>';
                }
            } else {
                for(var i in data) {
                    if(newData) { cacheData[i] = data[i]; }
                    items += '<li data-value="' + i + '">' + data[i].toString().replace(reg,"<strong>$1</strong>") + '</li>';
                    size++;
                }
            }
            if(size) { $dropdown.removeClass(o.classes.notfound).html(items).show();
                if (o.classes.parent) {
                    $text.parent().addClass(o.classes.parent);
                }
                if(o.onShow){ o.onShow($dropdown);}
            }
            else $dropdown.addClass(o.classes.notfound);
            return size;
        }

        function clearDropdown()
        {
            $dropdown.hide();
            if (o.classes.parent) {
                $text.parent().removeClass(o.classes.parent);
            }
            size = 0;
            hovered = false;
        }

        function getText(lowercase)
        {
            var text = $text.val().toString();
            if( lowercase === true ) {
                text = text.toLowerCase();
            }
            return text;
        }

        function getValue()
        {
            return $value.val();
        }

        function nothing(e)
        {
            //var e = e.originalEvent || e;
            if (e.stopPropagation) { e.stopPropagation(); }
            if (e.preventDefault) { e.preventDefault(); }
            e.cancelBubble = true;
            e.returnValue = false;
            return false;
        }

        function checkEmpty()
        {
            clearDropdown();
            var v = getValue();
            if( (v === '' || v === 0) ) {
                if (o.validate) doSelect(0, '');
            }
        }

        function newElement(isEnter)
        {
            doSelect(o.newElementValue, getText(true), isEnter);
        }

        $text.keyup(function(e)
        {
            window.clearTimeout(typingTimeout);
            switch(e.which)
            {
                case 27: case 9: // escape
                {
                    checkEmpty();
                } break;
                case 46: case 8: { // delete, backspace
                    clearDropdown();
                    // invalidate previous selection
                    if (o.validate) { $value.val(''); }

                    typingTimeout = window.setTimeout(function() { getData( getText() ); }, o.timeout);

                    if (o.onSelect && ! getText().length) {
                        doSelect(0, '');
                        if (o.suggest!==false) { showData(o.suggest, '', false); }
                    }
                    return true;
                } break;
                case 13: { // enter
                    if ( $dropdown.is(':hidden') ) {
                        if (o.newElement) newElement(true);
                        else getData( getText() );
                    } else {
                        if (hovered !== false) {
                            var $h = $dropdown.children().eq(hovered);
                            doSelect($h.data('value'), ( o.doPrepareText ? o.doPrepareText( $h.html() ) : $h.text()), true);
                            $text.blur();
                            clearDropdown();
                        } else {
                            var text = getText(true), ok = false;
                            if( size > 0 && text.length >= o.minChars ) {
                                var data = ( cacheQuery.hasOwnProperty(text) ? cacheQuery[text] : ( o.suggest!==false ? o.suggest : {}) );
                                for (var i in data) {
                                    if (data[i][1].toString().toLowerCase() === text) {
                                        doSelect(data[i][0], text, true); ok = true;
                                    }
                                }
                            }
                            if(ok) { $text.blur(); }
                            else {
                                if (o.newElement) newElement(true);
                                else checkEmpty();
                            }
                            clearDropdown();
                        }
                    }
                    nothing(e);
                    return false;
                } break;
                case 40: case 38: { // move up, down
                    switch(e.which) {
                        case 40: // down
                          hovered = ((hovered >= size - 1) || hovered === false ? 0 : hovered + 1); break;
                        case 38: // up
                          hovered = (hovered <= 0 ? size - 1 : hovered - 1); break;
                        default: break;
                    }
                    // hover item
                    $dropdown.children().removeClass(o.classes.active).eq(hovered).addClass(o.classes.active);
                } break;
                default: {
                    // invalidate previous selection
                    if (o.validate) { $value.val(''); }
                    typingTimeout = window.setTimeout(function() { getData( getText() ); }, o.timeout);
                } break;
            }
        })
        .on('blur', function(){
            window.setTimeout(function() {
                if (o.newElement) {
                    if (getValue() == o.newElementValue) {
                        newElement(false);
                        clearDropdown();
                    }
                } else {
                    checkEmpty();
                }
            }, 200);
        })
        .on('focus', function(){
            if( ! o.validate ) {
                getData( getText() );
            } else {
                if(o.suggest!==false) { showData(o.suggest, '', false); }
            }
        });

        return {
            setSuggest: function(data, reset){
                o.suggest = data;
                if(reset===true) {
                    cacheQuery = {};
                    cacheData = {}; if($.isArray(data)) { for(var i in data) { cacheData[data[i][0]] = data[i]; } }
                    doSelect(0, '');
                }
            },
            setParam: function(key, value){
                o.params[key] = value;
            },
            reset: function(){
                doSelect(0, '');
            }
        };
    };

    $.fn.autocomplete = function(url, o, callback)
    {
        return this.each(function(){
            var api = $.autocomplete(this, url, o);
            if($.isFunction(callback)) callback.call(api);
        });
    };

}(jQuery));

try{ bff.st.done('core/autocomplete/autocomplete.js'); }catch(e){}