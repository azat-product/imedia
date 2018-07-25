var jShopsAbonement = (function(){
    var inited = false, process = false, $abonChange;
    var $prolongForm, $prolongBlock, $prolongHelp, $userExpire, o = {price:0};

    function init()
    {
        $abonChange = $('#j-shops-abonement-change-block');
        $prolongForm = $('#j-abonement-prolong-form');
        $prolongBlock = $prolongForm.find('#j-abonement-prolong');
        $prolongHelp = $('.j-abonement-help');
        $userExpire = $('#j-abonement-user-expire');

        $prolongForm.on('click', '.j-abonement-prolong-toggle', function(){
            $prolongBlock.toggleClass('hide');
            $userExpire.toggleClass('hide');
            return false;
        });

        $prolongForm.on('change', '[name="abonement_period"]', function(){
            var pr = o.user_prices[$(this).val()];
            $prolongBlock.find('.j-abonement-price').html(pr.pr);
            $prolongBlock.find('.j-abonement-expire').html(pr.ex);
        });

        $prolongForm.on('click', '.j-abonement-change-toggle', function(){
            $prolongForm.addClass('hide');
            $abonChange.removeClass('hide');
            return false;
        });

        $prolongForm.on('click', '#j-abonement-autoupdate-toggle', function(){
            $prolongForm.find('#j-abonement-autoupdate').toggleClass('hide', !$(this).find('input').is(':checked'));
        });

        $prolongForm.on('change', '.j-subscribe', function(){
            var $el = $(this);
            bff.ajax(o.url_submit, {act:'subscribe', auto:$el.is(':checked') ? 1 : 0, hash:app.csrf_token}, function(data){
                if(data && data.success){
                    app.alert.success(o.lang.saved_success);
                }
            });
        });

        var formProlong = app.form($prolongForm, function(){
            var f = this;
            if( ! f.checkRequired({focus:true}) ) return;
            if (!bff.filter('shops.abonement.prolong.beforeSubmit', true, f, o)) {
                return;
            }
            if(process) return;
            process = true;
            f.ajax(o.url_submit,{},function(data,errors){
                if (data && data.success) {
                    if (data.hasOwnProperty('redirect')) {
                        bff.redirect(data.redirect);
                    } else {
                        f.alertSuccess(o.lang.saved_success);
                    }
                } else {
                    f.fieldsError(data.fields, errors);
                }
                process = false;
            });
        }, {noEnterSubmit:true});
        bff.hook('shops.abonement.formProlong.init', formProlong, o);

        $abonChange.on('click', '.j-abonement-change-toggle', function(){
            $prolongForm.removeClass('hide');
            $abonChange.addClass('hide');
            return false;
        });

        var $select = $abonChange.find('[name="abonement_period"]');
        $abonChange.on('change', '[name="abonement_id"]', function(){
            var id = $(this).val();
            var price = o.prices[id];
            var options = '';
            var hide_ex = true;
            var hide_pr = true;
            $.each(price, function(k, v){
                options = options + '<option value="' + k + '">' +  v.m + '</option>';
                if(v.m != ''){
                    hide_ex = false;
                }
                if(v.pr != '0'){
                    hide_pr = false;
                }
            });
            $select.html(options).toggle(!hide_ex);
            $select.trigger('change');
            $abonChange.find('.j-abonement-price-block').toggle(!hide_pr);
        });

        $select.change(function(){
            var id = intval($abonChange.find(':checked').val());
            var v = intval($(this).val());
            var pr = o.prices[id][v];
            $abonChange.find('.j-abonement-price').html(pr.pr);
            $abonChange.find('.j-abonement-expire').html(pr.ex);
            o.price = pr.pr;
            if(typeof(jShopsShopPromote) == 'object'){
                jShopsShopPromote.setPrice(pr.pr);
            }
        });

        var form = app.form('#j-abonement-form', function(){
            var f = this;
            if( ! f.checkRequired({focus:true}) ) return;
            if (!bff.filter('shops.abonement.beforeSubmit', true, f, o)) {
                return;
            }
            if(process) return;
            process = true;
            f.ajax(o.url_submit,{},function(data,errors){
                if(data && data.success) {
                    if(data.hasOwnProperty('redirect')) {
                        bff.redirect(data.redirect);
                    } else {
                        f.alertSuccess(o.lang.saved_success);
                    }
                } else {
                    f.fieldsError(data.fields, errors);
                }
                process = false;
            });
        }, {noEnterSubmit:true});
        bff.hook('shops.abonement.form.init', form, o);

        if (intval(o.abonement)) {
            $prolongForm.find('.j-abonement-change-toggle').trigger('click');
        }
    }

    function getPrice()
    {
        return o.price;
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('shops.abonement.settings', $.extend(o, options || {}));
            $(function(){
                init();
                bff.hook('shops.abonement.init', o);
            });
        },
        price:getPrice
    };
}());