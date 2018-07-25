var jMyMessages = (function(){
    var inited = false, o = {lang:{},ajax:true},
        listMngr, $form, $list, $pgn,
        $folder, $folderVal, $folderArrows,
        $pp, $ppVal;

    function init()
    {
        $form = $('#j-my-messages-form');
        $list = $form.find('#j-my-messages-list');
        $pgn = $form.find('#j-my-messages-pgn');

        //folder
        $folder = $form.find('.j-folder-options');
        $folderArrows = $form.find('#j-my-messages-folder-arrows');
        $folderVal = $form.find('#j-my-messages-folder-value');
        $form.on('click', '.j-folder-option', function(e){ nothing(e);
            var value = $(this).data('value'); $folderVal.val(value);
            onFolder(value, true);
        });
        $folderArrows.on('click', '.j-left, .j-right', function(e){ nothing(e);
            var value = $(this).data('value'); $folderVal.val(value);
            onFolder(value, true);
        });
        onFolder($folderVal.val(), false);

        //pp
        $pp = $form.find('#j-my-messages-pp');
        $ppVal = $form.find('#j-my-messages-pp-value');
        $pp.on('click', '.j-pp-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $ppVal.val(value);
            $pp.find('.j-pp-dropdown').dropdown('toggle').blur();
            onPP(value, true);
        });

        listMngr = app.list($form, {
            onSubmit: function(resp, ex) {
                if(ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                $list.html(resp.list);
                $pgn.html(resp.pgn);
                $pp.toggle( resp.total > 0 );
            },
            onProgress: function(progress, ex) {
                if(ex.fade) $list.toggleClass('disabled');
            },
            onPopstate: function() {
                onFolder($folderVal.val(), false);
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

        // contact click
        $list.on('click', '.j-contact', function(e){
            if( $(e.target).is('a') || $(e.target).parents('a').length ) return;
            nothing(e);
            document.location = $(this).data('contact');
        });

        // user in folder
        $list.on('click', '.j-f-action', function(e){ nothing(e);
            var $btn = $(this);
            var userID = intval($btn.data('user-id'));
            var shopID = intval($btn.data('shop-id'));
            var folderID = intval($btn.data('folder-id'));
            if( ! userID || ! folderID ) return;
            bff.ajax(document.location.href, {act:'move2folder', user:userID, shop:shopID, folder:folderID, hash:app.csrf_token}, function(data){
                if(data && data.success) {
                    $btn.toggleClass('active', (data.added==1));
                }
            });
        });

        // hook: init
        bff.hook('internalmail.my.messages.init', listMngr, o);
    }

    function onFolder(value, submit)
    {
        value = intval(value);
        $folder.removeClass('active');
        $form.find('.j-folder-option[data-value="'+value+'"]').parent().addClass('active');
        if( o.folders.hasOwnProperty(value) ) {
            var arr = o.folders[value];
            $folderArrows.find('.j-left').data('value', arr.left).find('a').toggle(arr.left!==false);
            $folderArrows.find('.j-title').text(arr.title);
            $folderArrows.find('.j-right').data('value', arr.right).find('a').toggle(arr.right!==false);
        }
        if( submit ) {
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

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('internalmail.my.messages.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        }
    };
}());

var jMyChat = (function(){
    var inited = false, o = {lang:{},ajax:true},
        listMngr, $listForm, $list, $pgn;

    function init()
    {
        // list
        $listForm = $('#j-my-chat-list-form');
        $list = $listForm.find('#j-my-chat-list');
        setTimeout(function(){
            $list.scrollTop( $list.get(0).scrollHeight + 100 );
        }, 1);

        $pgn = $listForm.find('#j-my-chat-list-pgn');
        listMngr = app.list($listForm, {
            onSubmit: function(resp, ex) {
                if(ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                $list.html(resp.list).scrollTop($list.get(0).scrollHeight + 100);
                $pgn.html(resp.pgn);
            },
            onProgress: function(progress, ex) {
                if(ex.fade) $list.toggleClass('disabled');
            },
            onPopstate: function() {
                //
            },
            submitOnDeviceChanged: false,
            ajax: o.ajax
        });

        // pgn
        if (o.ajax) {
            $pgn.on('click', '.j-pgn-page', function(e){ nothing(e);
                listMngr.page($(this).data('page'));
            });
        }

        // form
        var f = app.form('#j-my-chat-form');
        var $f = f.getForm();
        bff.iframeSubmit($f, function(resp, errors){
                if(resp && resp.success) {
                    f.alertSuccess(o.lang.success, {reset:true});
                    $('.j-attach-block .j-cancel-link', $f).triggerHandler('click');
                    listMngr.submit({}, true);
                } else {
                    f.fieldsError(resp.fields, errors);
                }
            }, {
            beforeSubmit: function(){
                if( f.fieldStr('message').length < 5 ) {
                    f.fieldError('message', o.lang.message); return false;
                }
                return bff.filter('internalmail.my.chat.beforeSubmit', true, f, o);
            }
        });
         // attach file
        var file_api = ( ( window.File && window.FileReader && window.FileList && window.Blob ) ? true : false );
        var $upload = $('.j-attach-block .j-upload', $f),
            $cancel = $('.j-attach-block .j-cancel', $f),
            $cancelFilename = $('.j-cancel-filename', $cancel);
        $upload.on('change', '.j-upload-file', function(){
            var name = (this.value || ''), size = '';
            if( file_api && this.files[0] ) name = this.files[0].name;
            if( ! name.length ) return;
            name = name.replace(/.+[\\\/]/, "");
            if(name.length > 32) name = name.substring(0,32) + '...';
            $cancelFilename.html(name+'&nbsp;&nbsp;&nbsp;&nbsp;');
            $upload.addClass('hide');
            $cancel.removeClass('hide');
        });
        $('.j-cancel-link', $cancel).on('click', function(e){ nothing(e);
            try{
            var file = $('.j-upload-file', $upload).get(0);
                file.parentNode.innerHTML = file.parentNode.innerHTML;
            } catch(e){}
            $upload.removeClass('hide');
            $cancel.addClass('hide');
            $cancelFilename.html('');
        });

        // hook: init
        bff.hook('internalmail.my.chat.init', f, o);
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('internalmail.my.chat.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        }
    };
}());