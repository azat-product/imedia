$(function(){

    function onFilterRegion(data, submit)
    {
        if( submit ) {
            var $form = $('#j-f-form');
            $form.attr('action', $form.attr('action').replace(app.hostSearch, data.link) );
            $form.append('<input type="hidden" name="region" value="'+data.id+'" />');
            $form.submit();
        }
    }

    /**
     * region-confirm (desktop)
     */
    var $confirmDesktop = $('#j-f-region-desktop-confirm');
    if ($confirmDesktop.length) {
        $confirmDesktop.on('click', '.j-confirm-yes', function(){
            bff.ajax(bff.ajaxURL('geo','filter-confirm-region'),{region_id:$(this).data('id')},function(data){
                if(data && data.success){
                    location.reload();
                }
                $confirmDesktop.hide();
            });
            return false;
        }).on('click', '.j-confirm-no', function(){
            $confirmDesktop.hide();
            $('#j-f-region-desktop-link').text($(this).data('filter-text')).triggerHandler('click');
            return false;
        });
    }

    /**
     * region-filter (desktop)
     */
    app.popup('f-region-desktop', '#j-f-region-desktop-popup', '#j-f-region-desktop-link', {onInit: function($p){
        var _this = this;
        var $st1 = $p.find('#j-f-region-desktop-st1');
        var $st2 = $p.find('#j-f-region-desktop-st2'), st2cache = {}, st2citySel = '.f-navigation__region_change__links a';
        function doFilter(type, $link)
        {
            var f = $link.metadata(); f['type'] = type; f['link'] = $link.attr('href');
            _this.getLink().text(f.title);
            onFilterRegion(f, true);
        }
        $st1.on('click', '.f-navigation__region_change__links a', function(){
            var region = $(this).metadata();
            if( st2cache.hasOwnProperty(region.id) ) {
                $st2.html(st2cache[region.id].html).add($st1).toggleClass('hide');
            } else {
                bff.ajax(bff.ajaxURL('geo','filter-desktop-step2'), {region_id:region.id}, function(data){
                    if(data && data.success) {
                        $st2.html((st2cache[region.id] = data).html).add($st1).toggleClass('hide');
                    }
                });
            }
            return false;
        });
        var $st1q = $st1.find('#j-f-region-desktop-st1-q'), $st1v = false;
        $st1q.keyup(function(){ // filter regions by 'title'
            if( $st1v == false) $st1v = $st1.find('#j-f-region-desktop-st1-v');
            var q = $st1q.val().toLowerCase();
            $st1v.find('ul, li').show();
            if( q == '' ) return false;
            $st1v.find('a').each(function(){
                if($(this).attr('title').toLowerCase().indexOf(q) == -1) $(this).parent().hide();
            });
            $st1v.find('ul.rel').each(function(){
                if($(this).show().find('li:visible').length == 1) $(this).hide();
            });
            $st1v.find('li.span3').each(function(){
                if( ! $(this).show().find('ul:visible').length) $(this).hide();
            });
            return false;
        });
        $st1.on('click', '#j-f-region-desktop-all', function(e){ nothing(e);
            _this.hide(); // search in "all" (reset region filter)
            doFilter('all', $(this));
            return false;
        });
        $st2.on('click', st2citySel, function(e){ nothing(e);
            _this.hide(); // search in "city"
            var $link = $(this);
            $link.metadata().title = $link.attr('title');
            $link.addClass('active').siblings().removeClass('active');
            doFilter('city', $link);
            return false;
        });
        $st2.on('click', '.j-f-region-desktop-st2-region', function(e){ nothing(e);
            $st2.find(st2citySel).removeClass('active');
            _this.hide(); // search in "region"
            doFilter('region', $(this));
            return false;
        });
        $st2.on('click', '.j-f-region-desktop-back', function(e){ nothing(e);
            $st2.add($st1).toggleClass('hide'); // reset "city" filter
            return false;
        });
    }});

    /**
     * country-filter (desktop)
     */
    app.popup('f-country-desktop', '#j-f-country-desktop-popup', '#j-f-region-desktop-link', {onInit: function($p){
        var _this = this;
        function doFilter(type, $link)
        {
            var f = $link.metadata(); f['type'] = type; f['link'] = $link.attr('href');
            _this.getLink().text(f.title);
            onFilterRegion(f, true);
        }

        var $st0 = $p.find('#j-f-country-desktop-st0');
        var $st1 = $p.find('#j-f-region-desktop-st1'), st1cache = {};
        var $st1v = $st1.find('#j-f-region-desktop-st1-v');
        var $st1title = $st1.find('#j-f-region-desktop-country-title');
        var $country = false;
        $st0.on('click', '.f-navigation__country_change__links a', function(){
            $country = $(this);
            $st1title.text($country.text());
            var country = $country.metadata();
            if(intval(country.noregions)){
                $st1qf.addClass('hide');
                $st1v.addClass('hide');
                if (st2cache.hasOwnProperty(country.id)) {
                    $st2.html(st2cache[country.id].html).add($st1).toggleClass('hide');
                    $st0.toggleClass('hide');
                } else {
                    bff.ajax(bff.ajaxURL('geo', 'filter-desktop-city-noregions'), {region_id: country.id}, function (data) {
                        if (data && data.success) {
                            $st2.html((st2cache[country.id] = data).html).add($st1).toggleClass('hide');
                            $st0.toggleClass('hide');
                        }
                    });
                }
            }else{
                $st1qf.removeClass('hide');
                $st1v.removeClass('hide');
                if (st1cache.hasOwnProperty(country.id)) {
                    $st1v.html(st1cache[country.id].html);
                    $st1.add($st0).toggleClass('hide');
                } else {
                    bff.ajax(bff.ajaxURL('geo', 'filter-desktop-step1'), {region_id: country.id}, function (data) {
                        if (data && data.success) {
                            $st1v.html((st1cache[country.id] = data).html);
                            $st1.add($st0).toggleClass('hide');
                        }
                    });
                }
            }
            return false;
        });
        $st1.on('click', '#j-f-country-desktop-all', function(e){ nothing(e);
            _this.hide(); // search in "all" (reset region filter)
            if( ! $country) {
                $country = $(this);
            }
            doFilter('country', $country);
            return false;
        });
        $st1.on('click', '.j-f-region-desktop-back', function(e){ nothing(e);
            $st0.toggleClass('hide');
            $st1.toggleClass('hide');
            $st2.addClass('hide');
            return false;
        });
        var $st1q = $st1.find('#j-f-region-desktop-st1-q');
        var $st1qf = $st1q.closest('form');
        $st1q.keyup(function(){ // filter regions by 'title'
            var q = $st1q.val().toLowerCase();
            $st1v.find('ul, li').show();
            if( q == '' ) return false;
            $st1v.find('a').each(function(){
                if($(this).attr('title').toLowerCase().indexOf(q) == -1) $(this).parent().hide();
            });
            $st1v.find('ul.rel').each(function(){
                if($(this).show().find('li:visible').length == 1) $(this).hide();
            });
            $st1v.find('li.span3').each(function(){
                if( ! $(this).show().find('ul:visible').length) $(this).hide();
            });
            return false;
        });
        var $st2 = $p.find('#j-f-region-desktop-st2'), st2cache = {}, st2citySel = '.f-navigation__region_change__links a';
        $st1.on('click', '.f-navigation__region_change__links a', function(){
            var region = $(this).metadata();
            if( st2cache.hasOwnProperty(region.id) ) {
                $st2.html(st2cache[region.id].html).add($st1).toggleClass('hide');
            } else {
                bff.ajax(bff.ajaxURL('geo','filter-desktop-step2'), {region_id:region.id}, function(data){
                    if(data && data.success) {
                        $st2.html((st2cache[region.id] = data).html).add($st1).toggleClass('hide');
                    }
                });
            }
            return false;
        });
        $st2.on('click', '.j-f-region-desktop-back', function(e){ nothing(e);
            $st2.add($st1).toggleClass('hide'); // reset "city" filter
            return false;
        });
        $st0.on('click', '#j-f-region-desktop-all', function(e){ nothing(e);
            _this.hide(); // search in "all" (reset region filter)
            doFilter('all', $(this));
            return false;
        });
        $st2.on('click', st2citySel, function(e){ nothing(e);
            _this.hide(); // search in "city"
            var $link = $(this);
            $link.metadata().title = $link.attr('title');
            $link.addClass('active').siblings().removeClass('active');
            doFilter('city', $link);
            return false;
        });
        $st2.on('click', '.j-f-region-desktop-st2-region', function(e){ nothing(e);
            $st2.find(st2citySel).removeClass('active');
            _this.hide(); // search in "region"
            doFilter('region', $(this));
            return false;
        });
    }});

    /**
     * region-filter (phone)
     */
    app.popup('f-region-phone', '#j-f-region-phone-popup', '#j-f-region-phone-link', {onInit: function($p){
        var _this = this;
        var $q = $p.find('#j-f-region-phone-q');
        var $qList = $p.find('#j-f-region-phone-q-list'), qListPresuggest = $qList.html();
        function doFilter(data)
        {
            $q.val('');
            qList(qListPresuggest, '');
            _this.getLink().find('span').text(data.title);
            onFilterRegion(data, true);
        }
        function qList(data, q)
        {
            var $notFoundList = $qList.next();
            if( ! data.length ) {
                $qList.hide().html(qListPresuggest);
                $notFoundList.find('.word').text(q);
                $notFoundList.show();
            } else {
                $notFoundList.hide();
                $qList.html(data).show();
            }
        }
        $q.keyup(function(){
            var q = this.value.toLowerCase();
            if( ! q.length) {
                qList(qListPresuggest, '');
            } else if(q.length >=2 ) {
                bff.ajax(bff.ajaxURL('geo', 'filter-phone-suggest'),{q:q},function(data){
                    if(data) {
                        qList(data.html, q);
                    }
                });
            }
        }).next().click(function(e){ nothing(e);
            $q.keyup();
        });
        $qList.on('click', 'li', function(e){ nothing(e);
            _this.hide(); // search in "region/city/all"
            doFilter( $(this).metadata() );
            return false;
        });
    }});
});