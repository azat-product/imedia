var jBBSItemPromote = (function(){
    var inited = false, o = {lang:{}, user_balance:0, items_total:0, svc_prices:{}, svc_id:0},
        $form, $formTotal, $formSubmit, $svcBlock, $psBlock, $psValue, $formPay;

    function init()
    {
        $form = $('#j-item-promote-form');
        $formTotal = $form.find('.j-total');
        $formPay = $('#j-item-promote-form-request');
        $formSubmit = $form.find('.j-submit');
        $svcBlock = $form.find('.j-svc-block');
        $psBlock = $form.find('.j-ps-block');
        $psValue = $form.find('.j-ps-value');
        var f = app.form($form, function(){
            if( ! $form.find('.j-svc-item :radio:checked').length ) {
                f.alertError(o.lang.svc_select);
                return;
            }
            if( ! $.trim( $psValue.val() ).length ) {
                f.alertError(o.lang.ps_select);
                return;
            }
            if (!bff.filter('bbs.promote.beforeSubmit', true, f, o)) {
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
        $form.on('click', '.j-svc-item', function(e){
            var $svcItem = $(this);
            $svcItem.addClass('active').find('.j-svc-descr').removeClass('hide');
            $svcItem.siblings().removeClass('active').find('.j-svc-descr').addClass('hide');
            if( $svcItem.find('.j-check').is(':disabled') ) {
                o.svc_id = 0;
                $svcBlock.find('.j-check:checked').prop('checked', false);
                refreshTotal();
                return;
            }
            o.svc_id = intval($svcItem.data('id'));
            refreshTotal();
        });

        $form.on('change', '[name="up_auto[t]"]', function(){
            $form.find('.j-a-up-type').addClass('hide');
            $form.find('.j-a-up-type-' + $(this).val()).removeClass('hide');
        });

        $form.on('change', '[name="fix_days"]', function(){
            refreshTotal();
        });

        refreshTotal();

        // hook: init
        bff.hook('bbs.promote.init', f, o);
    }

    function refreshTotal()
    {
        var price = 0;
        if( o.svc_prices.hasOwnProperty(o.svc_id) ) {
            price = o.svc_prices[o.svc_id];
        }
        if (o.svc_id == o.svc_fix_id ) {
            var $days = $form.find('[name="fix_days"]');
            if($days.length){
                price *= intval($days.val());
            }
        }

        price *= intval(o.items_total);
        $formTotal.text(price);
        $formSubmit.toggleClass('btn-success', (price > 0)).prop({disabled:!o.svc_id});
        var balanceHidden = $psBlock.find('.j-ps-item-balance').toggleClass('hide', price > o.user_balance).hasClass('hide');
        if( balanceHidden && $psValue.val() == 'balance' ) {
            $form.find('.j-ps-item:not(.hide):first').click();
        }
        if( price === 0 || o.user_balance >= price ) {
            $form.find('.j-ps-item-balance').click();
        }
        $psBlock.toggleClass('hide', !(o.svc_id > 0 && price > 0));
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('bbs.promote.settings', $.extend(o, options || {}));
            $(function(){ init(); });
        }
    };
})();