var jUsersWriteForm = (function(){

    function initForm(o)
    {
        // form submit
        var f = app.form(o.form_id);
        var $f = f.getForm();
        bff.iframeSubmit($f, function(resp, errors){
                if(resp && resp.success) {
                    f.alertSuccess(o.lang.success, {reset:true});
                    $('.j-attach-block .j-cancel-link', $f).triggerHandler('click');
                } else {
                    f.fieldsError(resp.fields, errors);
                }
                if (o.captcha) {
                    $captcha.triggerHandler('click');
                }
            }, {
            beforeSubmit: function(){
                if( ! app.user.logined() ) {
                    if( ! f.checkRequired() ) return false;
                    if( ! bff.isEmail( f.fieldStr('email') ) ) {
                        f.fieldError('email', o.lang.email); return false;
                    }
                }
                if( f.fieldStr('message').length < 10 ) {
                    f.fieldError('message', o.lang.message); return false;
                }
                return bff.filter('users.write.form.beforeSubmit', true, f, o);
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

        // captcha
        if (o.captcha) {
            var $captcha = $('.j-captcha', $f);
            if ($captcha.length) {
                $captcha.on('click', function(){
                    $(this).attr('src', $(this).data('url')+Math.random());
                }).triggerHandler('click');
            } else {
                o.captcha = false;
            }
        }

        bff.hook('contacts.form.init', f, o);
    }

    return {
        init: function(options)
        {
            options = bff.filter('users.write.form.settings', $.extend({lang:{}, form_id:'', captcha:false}, options || {}));
            $(function(){
                initForm(options);
            });
        }
    };
}());