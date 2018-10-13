var jBBSLimitsPayed = (function(){
    var inited = false, o = {lang:{}, user_balance:0},
        $form, $formTotal, $formSubmit, $psBlock, $psValue, $formPay, $price;

    function init()
    {
        $form = $('#j-limits-paid-form');
        $formTotal = $form.find('.j-total');
        $price = $form.find('.j-price');
        $formPay = $('#j-limits-paid-form-request');
        $formSubmit = $form.find('.j-submit');
        $psBlock = $form.find('.j-ps-block');
        $psValue = $form.find('.j-ps-value');
        var f = app.form($form, function(){
            if( ! intval($form.find('[name="items"]').val()) ) {
                f.alertError(o.lang.svc_select);
                return;
            }
            if( ! $.trim( $psValue.val() ).length ) {
                f.alertError(o.lang.ps_select);
                return;
            }
            f.ajax('', {}, function(resp, errors){
                if(resp && resp.success) {
                    if(resp.hasOwnProperty('redirect')) {
                        bff.redirect(resp.redirect);
                    } else {
                        $formPay.html(resp.form).find('form:first').submit();
                    }
                } else {
                    app.alert.error(errors);
                }
            });
        });
        $form.on('click', '.j-ps-item', function(e){
            if ( $(e.target).is('input') || $(e.target).parents('label').length ) {
                return;
            }
            var $item = $(this);
            $item.siblings().removeClass('active').find('.j-radio').prop({checked: false});
            $item.addClass('active').find('.j-radio').prop({checked: true});
            $psValue.val( $item.data('key') );
        });
        $form.on('click', '.j-ps-item .j-radio', function(e){
            var $item = $(this).closest('.j-ps-item');
            $item.addClass('active');
            $item.siblings().removeClass('active').find('.j-radio').prop({checked: false});
            $psValue.val( $item.data('key') );
        });

        $form.on('click', '.j-package', function(e){
            var $el = $(this);
            $el.addClass('active').siblings().removeClass('active');
            $el.find('[name="single"]').prop('checked', true);
            refreshTotal();
        });

        $form.on('change', '.j-items', function(e){
            refreshTotal();
        });

        refreshTotal();

        // hook: init
        bff.hook('bbs.limits.payed.init', f, o);
    }

    function refreshTotal()
    {
        var price = 0;
        var items = 0;
        var $single = $form.find('[name="single"]:checked');
        if(intval($single.val())){
            items = 1;
            price = $single.data('price');
        }else{
            var $option = $form.find('.j-items :checked');
            price = $option.data('price');
            items = $option.val();
            $price.text(price);
        }
        $formTotal.text(price);
        $form.find('[name="items"]').val(items);
        var balanceHidden = $psBlock.find('.j-ps-item-balance').toggleClass('hide', price > o.user_balance).hasClass('hide');
        if( balanceHidden && $psValue.val() == 'balance' ) {
            $form.find('.j-ps-item:not(.hide):first').trigger('click');
        }
        if( price === 0 || o.user_balance >= price ) {
            $form.find('.j-ps-item-balance').trigger('click');
        }

    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('bbs.limits.payed.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        }
    };
})();