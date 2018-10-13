<?php
?>
<?= $captchaHTML ?>
<script type="text/javascript">
<?php js::start(); ?>
var jReCaptcha = function(){
    var inited = false, exec = false, irf = false;

    function init()
    {
        if (inited) return;
        inited = true;
        $('.j-google-recaptcha').each(function(){
            var $el = $(this);
            grecaptcha.render($el.get(0), {
                sitekey: '<?= $siteKey ?>'
            });
        });

        <?php if ( ! empty($invisible)): ?>
        bff.hookAdd('app.form.ajax', function (res, form, url, params, callback, $progress, opts) {
            var $f = form.getForm();
            if ($f.hasClass('e')) return res;
            var $el = $f.find('.j-google-recaptcha');
            if ($el.length) {
                exec = {form:form, url:url, params:params, callback:callback, $progress:$progress, opts:opts};
                grecaptcha.execute();
                return true;
            } else {
                exec = false;
            }
            return res;
        });
        bff.hookAdd('app.form.ajax.progress', function (form, p) {
            if ( ! p) {
                grecaptcha.reset();
                var $f = form.getForm();
                $f.removeClass('e');
                exec = false;
            }
        });

        bff.hookAdd('bff.iframeSubmit', function (res, form, callback, o) {
            var $f = $(form);
            if ($f.hasClass('e')) return res;
            var $el = $f.find('.j-google-recaptcha');
            if ($el.length) {
                irf = {form:form, callback:callback, o:o};
                grecaptcha.execute();
                return true;
            } else {
                irf = false;
            }
            return res;
        });
        bff.hookAdd('bff.iframeSubmit.data', function (form, data) {
            var $f = $(form);
            grecaptcha.reset();
            $f.removeClass('e');
            irf = false;
        });
        <?php else: ?>
        bff.hookAdd('app.form.ajax.progress', function (form, p) {
            if ( ! p) {
                var $f = form.getForm();
                if ($f.find('.j-google-recaptcha').length) {
                    grecaptcha.reset();
                }
            }
        });
        bff.hookAdd('bff.iframeSubmit.data', function (form, data) {
            var $f = $(form);
            if ($f.find('.j-google-recaptcha').length) {
                grecaptcha.reset();
            }
        });
        <?php endif; ?>
    }

    function onExecuted(e)
    {
        var $r;
        if (exec && exec.form) {
            var $f = exec.form.getForm();
            $r = $f.find('[name="g-recaptcha-response"]');
            if ( ! $r.val().length) {
                $r.val(e);
            }
            $f.addClass('e');
            exec.form.ajax(exec.url, exec.params, exec.callback, exec.$progress, exec.opts);
            $f.removeClass('e');
        } else if (irf && irf.form) {
            $r = irf.form.find('[name="g-recaptcha-response"]');
            if ( ! $r.val().length) {
                $r.val(e);
            }
            irf.form.addClass('e').find('.j-submit').trigger('click');
        }
    }

    return {
        init:init,
        onExecuted:onExecuted
    };
}();
<?php if ( ! empty($invisible)): ?>
var jReCaptchaOnExecuted = function (e) {
    jReCaptcha.onExecuted(e);
};
<?php endif; ?>
var onloadReCaptcha = function() {
    $(function(){
        jReCaptcha.init();
    });
};
<?php js::stop(); ?>
</script>