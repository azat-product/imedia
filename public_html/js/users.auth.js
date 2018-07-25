var jUserAuth = (function(){
    var inited = false, url = document.location.href, o = {lang:{}};

    function init(context, options, callback)
    {
         if(inited) return; inited = true;
         o = bff.filter('users.auth.'+context+'.settings', $.extend(o, options || {}));
         $(function(){
             callback();
         });
    }

    function initLoginSocialButtons()
    {
        var url_login = o.login_social_url,
            url_return = o.login_social_return,
            popup;

        function socialPopup(o)
        {
            o = $.extend({w: 450, h: 380}, o || {});
            if(popup !== undefined) popup.close();
            var centerWidth = ($(window).width() - o.w) / 2;
            var centerHeight = ($(window).height() - o.h) / 2;
            popup = window.open(url_login+ o.provider+'?ret='+encodeURIComponent(url_return), "u_login_social_popup", "width=" + o.w + ",height=" + o.h + ",left=" + centerWidth + ",top=" + centerHeight + ",resizable=yes,scrollbars=no,toolbar=no,menubar=no,location=no,directories=no,status=yes");
            popup.focus();
            return false;
        }
        $('.j-u-login-social-btn').on('click', function(e){ nothing(e);
            var meta = $(this).metadata();
            if( meta && meta.hasOwnProperty('provider') ) {
                socialPopup(meta);
            }
        });
    }

    return {
        login: function(options)
        {
            o.login_social_url = '/';
            o.login_social_return = '/';
            init('login', options, function()
            {
                var f = app.form('#j-u-login-form', function($f){
                    if( ! f.checkRequired() ) return;
                    if( ! bff.isEmail( f.fieldStr('email') ) ) {
                        return f.fieldError('email', o.lang.email);
                    }
                    if( ! f.fieldStr('pass').length ) {
                        return f.fieldError('pass', o.lang.pass);
                    }
                    if (!bff.filter('users.auth.login.beforeSubmit', true, f, o)) {
                        return;
                    }
                    f.ajax(url, {}, function(data, errors){
                        if(data && data.success) {
                            bff.redirect(data.redirect);
                        } else {
                            f.fieldsError(data.fields, errors);
                            if (data.status == 1) {
                                app.alert.noHide();
                            }
                        }
                    });
                });
                initLoginSocialButtons();
                app.$B.on('click', '.j-resend-activation', function(e){ nothing(e);
                    if( ! bff.isEmail( f.fieldStr('email') ) ) {
                        f.fieldError('email', o.lang.email); return;
                    }
                    f.ajax(url, {step:'resend-activation'}, function(data, errors){
                        if(data && data.success) {
                            bff.redirect(data.redirect);
                        } else {
                            f.fieldsError(data.fields, errors);
                        }
                    });
                });
                bff.hook('users.auth.login.init', f, o);
            });
        },
        register: function(options)
        {
            o.phone = false;
            o.captcha = false;
            o.pass_confirm = false;
            o.login_social_url = '/';
            o.login_social_return = '/';
            init('register', options, function()
            {
                var f = app.form('#j-u-register-form', function($f){
                    if( ! f.checkRequired() ) return;
                    if( ! bff.isEmail( f.fieldStr('email') ) ) {
                        return f.fieldError('email', o.lang.email);
                    }
                    if( ! f.fieldStr('pass').length ) {
                        return f.fieldError('pass', o.lang.pass);
                    }
                    if( o.pass_confirm ) {
                        var pass2 = f.fieldStr('pass2');
                        if( ! pass2.length || pass2 != f.fieldStr('pass') ) {
                            return f.fieldError('pass2', o.lang.pass2);
                        }
                    }
                    if( o.captcha ) {
                        if( ! f.fieldStr('captcha').length ) {
                            return f.fieldError('captcha', o.lang.captcha);
                        }
                    }
                    if( ! f.$field('agreement').is(':checked') ) {
                        return f.fieldError('agreement', o.lang.agreement);
                    }
                    if (!bff.filter('users.auth.register.beforeSubmit', true, f, o)) {
                        return;
                    }
                    f.ajax(url, {}, function(data, errors){
                        if(data && data.success) {
                            bff.redirect(data.redirect);
                        } else {
                            if(o.captcha && data.captcha) {
                                $f.find('.j-captcha').triggerHandler('click');
                            }
                            f.fieldsError(data.fields, errors);
                        }
                    });
                });
                if (o.phone) {
                    app.user.phoneInput(f.getForm().find('.j-phone-number'));
                }
                initLoginSocialButtons();
                bff.hook('users.auth.register.init', f, o);
            });
        },
        registerEmailed: function(options)
        {
            init('registerEmailed', options, function()
            {
                var $retry = $('#j-u-register-emailed-retry'), retry_process = false;
                if( $retry.length ) {
                    $retry.on('click', 'a', function(e){ nothing(e);
                        if(retry_process) return;
                        bff.ajax(url, {step:'emailed'}, function(data, errors){
                            if(data && data.success) {
                                app.alert.success(o.lang.success);
                                $retry.fadeOut();
                            } else {
                                app.alert.error(errors);
                            }
                        }, function(p){ retry_process = p; });
                    });
                }
                bff.hook('users.auth.registerEmailed.init', o);
            });
        },
        registerSocial: function(options)
        {
            init('registerSocial', options, function()
            {
                var formStatus = 'register', f, $form;
                function setFormStatus(status)
                {
                    formStatus = status;
                    $('.j-social', $form).toggle();
                    $('.j-shortpage-title').html(o.lang[status].title);
                    $('#j-u-register-social-email', $form).prop({readonly:(status=='login')});
                    app.alert.hide();
                }
                f = app.form('#j-u-register-social-form', function($f)
                {
                    if(formStatus == 'register') {
                        if( ! bff.isEmail( f.fieldStr('email') ) ) {
                            return f.fieldError('email', o.lang.register.email);
                        }
                        if( ! f.$field('agreement').is(':checked') ) {
                            return f.fieldError('agreement', o.lang.register.agreement);
                        }
                        if (!bff.filter('users.auth.registerSocial.register.beforeSubmit', true, f, o)) {
                            return;
                        }
                        f.ajax(url, {step:'social'}, function(data, errors){
                            if(data && data.success) {
                                if( data.exists ) {
                                    setFormStatus('login');
                                } else {
                                    bff.redirect(data.redirect);
                                }
                            } else {
                                f.fieldsError(data.fields, errors);
                            }
                        });
                    } else {
                        if( ! f.fieldStr('pass').length ) {
                            return f.fieldError('pass', o.lang.login.pass);
                        }
                        if (!bff.filter('users.auth.registerSocial.login.beforeSubmit', true, f, o)) {
                            return;
                        }
                        f.ajax(o.login_url, {social:true}, function(data, errors){
                            if(data && data.success) {
                                bff.redirect(data.redirect);
                            } else {
                                f.fieldsError(data.fields, errors);
                            }
                        });
                    }
                });
                $form = f.getForm();
                $('#j-u-register-social-email-change', $form).on('click', function(e){ nothing(e);
                    setFormStatus('register');
                });
                bff.hook('users.auth.registerSocial.init', f, o);
            });
        },
        registerPhone: function(options)
        {
            init('registerPhone', options, function()
            {
                var $blockCode = $('#j-u-register-phone-block-code');
                var $blockPhone = $('#j-u-register-phone-block-phone');
                var $currentNumber = $('#j-u-register-phone-current-number');

                // phone inputs:
                if ($blockCode.length) {
                    // init inputs
                    app.user.phoneInput($blockPhone.find('#j-u-register-phone-input').closest('.j-phone-number'));
                    app.user.phoneInput($blockPhone.find('#j-u-register-phone-input-m').closest('.j-phone-number'));
                    // change phone step1:
                    $('.j-u-register-phone-change-step1-btn').on('click', function(e){ nothing(e);
                        $blockCode.toggle(); if ($blockCode.is(':visible')) $blockCode.find('.j-u-register-phone-code-input:visible').focus();
                        $blockPhone.toggle(); if ($blockPhone.is(':visible')) $blockPhone.find('.j-phone-number-input:visible').focus();
                    });
                    // change phone step2:
                    $blockPhone.on('click', '.j-u-register-phone-change-step2-btn', function(e){ nothing(e);
                        var $btn = $(this); if ($btn.is(':disabled')) return;
                        var $phone = $blockPhone.find('.j-phone-number-input:visible');
                        if (!$phone.val().length) {
                            $phone.focus(); return;
                        }
                        bff.ajax(url, {step:'phone',act:'phone-change',phone:$phone.val()}, function(data, errors){
                            if(data && data.success) {
                                app.alert.success(o.lang.change_success);
                                $currentNumber.text(data.phone);
                                $blockCode.toggle().find('.j-u-register-phone-code-input:visible').focus();
                                $blockPhone.toggle();
                            } else {
                                app.alert.error(errors);
                            }
                        }, function(p) {
                            if(p) $btn.prop('disabled','disabled');
                            else  $btn.removeProp('disabled');
                        });
                    });
                }

                // validate code:
                $blockCode.on('click', '.j-u-register-phone-code-validate-btn', function(e){ nothing(e);
                    var $btn = $(this); if ($btn.is(':disabled')) return;
                    var $code = $blockCode.find('.j-u-register-phone-code-input:visible');
                    if (!$code.val().length) {
                        $code.focus(); return;
                    }
                    bff.ajax(url, {step:'phone',act:'code-validate',code:$code.val()}, function(data, errors){
                        if(data && data.success) {
                            bff.redirect(data.redirect);
                        } else {
                            app.alert.error(errors);
                        }
                    }, function(p) {
                        if(p) $btn.prop('disabled','disabled');
                        else  $btn.removeProp('disabled');
                    });
                });

                // resend code:
                $blockCode.on('click', '.j-u-register-phone-code-resend-btn', function(e){ nothing(e);
                    var $btn = $(this); if ($btn.is(':disabled')) return;
                    bff.ajax(url, {step:'phone',act:'code-resend'}, function(data, errors){
                        if(data && data.success) {
                            app.alert.success(o.lang.resend_success);
                        } else {
                            app.alert.error(errors);
                        }
                    }, function(p) {
                        if(p) $btn.prop('disabled','disabled');
                        else  $btn.removeProp('disabled');
                    });
                });

                bff.hook('users.auth.registerPhone.init', $blockCode, o);
            });
        },
        forgotStart: function(options)
        {
            init('forgotStart', options, function()
            {
                function formSubmit($f) {
                    var f = this;
                    if( ! f.checkRequired() ) return;
                    if( ! bff.isEmail( f.fieldStr('email') ) ) {
                        f.fieldError('email', o.lang.email); return;
                    }
                    if (!bff.filter('users.auth.forgotStart.beforeSubmit', true, f, o)) {
                        return;
                    }
                    f.ajax(url, {}, function(data, errors){
                        if(data && data.success) {
                            f.alertSuccess(o.lang.success, {hide:false,reset:true});
                        } else {
                            f.fieldsError(data.fields, errors);
                        }
                    });
                }
                app.form('#j-u-forgot-start-form-'+app.devices.desktop, formSubmit);
                app.form('#j-u-forgot-start-form-'+app.devices.phone, formSubmit);
            });
        },
        forgotFinish: function(options)
        {
            init('forgotFinish', options, function()
            {
                function formSubmit($f) {
                    var f = this;
                    if( ! f.fieldStr('pass').length ) {
                        f.fieldError('pass', o.lang.pass); return;
                    }
                    if (!bff.filter('users.auth.forgotFinish.beforeSubmit', true, f, o)) {
                        return;
                    }
                    f.ajax(url, {}, function(data, errors){
                        if(data && data.success) {
                            f.alertSuccess(o.lang.success, {hide:false,reset:true});
                            $f.find('button[type="submit"]').prop({disabled:true});
                        } else {
                            f.fieldsError(data.fields, errors);
                        }
                    });
                }
                app.form('#j-u-forgot-finish-form-'+app.devices.desktop, formSubmit);
                app.form('#j-u-forgot-finish-form-'+app.devices.phone, formSubmit);
            });
        }
    };
}());