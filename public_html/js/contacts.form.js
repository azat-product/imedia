var jContactsForm = (function(){
    var inited = false, o = {lang:{}, url_submit:'', captcha_url:''}, $form;

    function init()
    {
        $form = $('#j-contacts-form');
        var f = app.form($form, function()
        {
            if( ! f.checkRequired({focus:true}) ) return;

            if( ! bff.isEmail( f.fieldStr('email') ) ) {
                return f.fieldError('email', o.lang.email);
            }
            if (f.$field('captcha').length) {
                if ( ! f.fieldStr('captcha').length) {
                    return f.fieldError('captcha', o.lang.captcha);
                }
            }
            if (!bff.filter('contacts.form.beforeSubmit', true, f, o)) {
                return;
            }
            f.ajax(o.url_submit, {}, function(data,errors){
                if(data && data.success) {
                    f.alertSuccess(o.lang.success);
                    f.reset();
                    refreshCaptha();
                } else {
                    f.fieldsError(data.fields, errors);
                    if (data.captcha) {
                        refreshCaptha();
                    }
                }
            });
            return false;
        });

        bff.hook('contacts.form.init', f, o);
    }

    function refreshCaptha()
    {
        $form.find('#j-contacts-form-captcha-code').attr('src', o.captcha_url+'&r='+Math.random(1));
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('contacts.form.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        },
        refreshCaptha: refreshCaptha
    };
}());