var jView = (function(){
    var inited = false, $container, o = {lang:{},
            geoMapZoom:12,
            fotorama: {
                width: '100%', maxwidth: '100%', maxheight: 450, minheight: 250,
                nav: 'thumbs', fit: 'contain',
                keyboard: true,
                loop: true, click: true, swipe: true
            },
            fancybox: {
                enabled: true, padding: 0, title: false,
                helpers: { thumbs: {width:50, height:50}, overlay: {locked: false} }
            }
        },
        images = {$block:0,viewer:0,mapIndex:0,lastIndex:0},
        map = {$block:0,map:0,marker:0};

    function init()
    {
        $container = $('#j-view-container');

        // images
        images.$block = $container.find('#j-view-images');
        if (o.fancybox.enabled) {
        var zoomImages = [];
        images.$block.find('.j-zoom').each(function(){ zoomImages.push({href:$(this).data('zoom')}); });
        images.$block.on('click', '.j-zoom', function(e){ nothing(e);
            o.fancybox.index = $(this).data('index');
            $.fancybox(zoomImages, o.fancybox);
            return false;
        });
        }


        var $imagesFrames = images.$block.find('.j-view-images-frame');
        if ($imagesFrames.length > 1 || $imagesFrames.is(':not(.j-map)')) {
            images.viewer = (images.$block.fotorama(o.fotorama)).data('fotorama');
        }

        // map
        map.$block = $container.find('#j-view-map');
        if( map.$block.length ) {
            var mapCenter = [parseFloat(o.addr_lat), parseFloat(o.addr_lon)];
            map.map = app.map(map.$block.get(0), o.addr_lat+','+o.addr_lon, function(map){
                if (this.isYandex()) {
                    map.controls.remove('searchControl');
                    map.options.set('autoFitToViewport', 'ifNull');
                }
            }, {marker:true, zoom: o.geoMapZoom});
            images.mapIndex = (images.viewer.size - 1);
            var _map_showed = false;
            images.$block.on('fotorama:showend', function(e, fotorama, extra){
                if( ! extra.user ) return;
                var opts = false;
                if( images.viewer.activeIndex == images.mapIndex ) {
                    if( ! _map_showed ) {
                        map.map.panTo(mapCenter, {delay: 10, duration: 200});
                    }
                    opts = {click: false, swipe: false}; _map_showed = true;
                } else if( _map_showed ) {
                    opts = {click: true, swipe: true}; _map_showed = false;
                }
                if( opts!==false ) {
                    images.viewer.setOptions(opts);
                }
            });
        }

        // contacts expand
        var expanded = false, expandedProccess = false;
        var $expandLinks = $container.find('.j-v-contacts-expand-link');
        var $expandBlocks = $container.find('.j-v-contacts-expand-block');
        $expandLinks.on('click', function(e){ nothing(e);
            if( expanded || expandedProccess ) return;
            bff.ajax(bff.ajaxURL('bbs','item-contacts'), {page:'view',id:o.item_id,mod:o.mod,hash:app.csrf_token}, function(resp, errors){
                if(resp && resp.success) {
                    expanded = true;
                    $expandLinks.hide();
                    if(resp.hasOwnProperty('phones')) $expandBlocks.find('.j-c-phones').html(resp['phones']);
                    if(resp.hasOwnProperty('contacts')) {
                        for(var c in resp.contacts) {
                            $expandBlocks.find('.j-c-' + c).html(resp.contacts[c]);
                        }
                    }
                } else {
                    app.alert.error(errors);
                }
            }, function(p){ expandedProccess = p; });
        });

        // send to friend popup
        app.popup('v-send4friend-desktop', '#j-v-send4friend-desktop-popup', '#j-v-send4friend-desktop-link', {onInit: function($p){
            var _this = this;
            var f = app.form($p.find('form:first'), function($f){
                if( ! bff.isEmail( f.fieldStr('email') ) ) {
                    f.fieldError('email', o.lang.sendfriend.email); return;
                }
                f.ajax('?act=sendfriend', {}, function(data, errors){
                    if(data && data.success) {
                        _this.hide(function(){
                            f.alertSuccess(o.lang.sendfriend.success, {reset:true});
                        });
                    } else {
                        f.fieldsError(data.fields, errors);
                        if( data.later ){ _this.hide(function(){ f.reset(); }); }
                    }
                });
            });
        }});

        // claim popup
        app.popup('v-claim-desktop', '#j-v-claim-desktop-popup', '#j-v-claim-desktop-link', {onInit: function($p){
            var _this = this,
                $f = $p.find('form:first'),
                $reason_checks = $f.find('.j-claim-check'),
                $reason_other = $f.find('.j-claim-other'),
                f;

            function _refresh_catpcha() {
                $f.find('.j-captcha').triggerHandler('click');
            }

            $reason_checks.on('click', function(){
                if( intval($(this).val()) == o.claim_other_id ) {
                    if( $reason_other.toggle().is(':visible') ) {
                        $reason_other.find('textarea').focus();
                    }
                }
            });
            f = app.form($f, function(){
                if( ! $reason_checks.filter(':checked').length ) {
                    app.alert.error(o.lang.claim.reason_checks); return;
                } else {
                    if( $reason_other.is(':visible') ) {
                        if($.trim($reason_other.find('textarea').val()).length<10) {
                            f.fieldError('comment', o.lang.claim.reason_other); return;
                        }
                    }
                }
                if (f.$field('captcha').length) {
                    if ( ! f.fieldStr('captcha').length) {
                        f.fieldError('captcha', o.lang.claim.captcha);
                        return;
                    }
                }
                f.ajax('?act=claim', {}, function(data, errors){
                    if(data && data.success) {
                        _this.hide(function(){
                            f.alertSuccess(o.lang.claim.success, {reset:true});
                            $reason_other.hide();
                            $f.find('.j-captcha').triggerHandler('click');
                        });
                    } else {
                        if( ! app.user.logined() && data.captcha) {
                            _refresh_catpcha();
                        }
                        f.fieldsError(data.fields, errors);
                    }
                });
            });

            _refresh_catpcha();
        }});

        // views stat popup
        var statPopup = false;
        $('#j-v-viewstat-desktop-link', $container).on('click', function(e){ nothing(e);
            if( statPopup === false ) {
                bff.ajax('?act=views-stat', {}, function(data, errors){
                    if(data && data.success) {
                        $('#j-v-viewstat-desktop-popup-container', $container).html(data.popup);
                        bff.st.includeJS('d3.v3.min.js', function(){
                            statPopup = app.popup('v-viewstat-desktop', '#j-v-viewstat-desktop-popup', false, {onInit: function($p){
                                viewsChart('#j-v-viewstat-desktop-popup-chart', data.stat.data, data.lang);
                            }, bl: true, scroll:true});
                            statPopup.show();
                        });
                    } else {
                        app.alert.error(errors);
                    }
                });
            } else {
                statPopup.show();
            }
        });

        // mobile form
        var $mobileFormBlock = $('#j-view-contact-mobile-block', $container);
        $mobileFormBlock.on('click', '.j-toggler', function(e){ nothing(e);
            $(this).hide();
            $('.j-form', $mobileFormBlock).removeClass('hide');
        });

        // owner panel
        var $ownerPanel = $('#j-v-owner-panel');
        if( $ownerPanel.length ) {
            var statusURL = bff.ajaxURL('bbs', 'item-status&status=');
            var statusData = {id:o.item_id,hash:app.csrf_token};
            var statusMessage = function(message, redirect) {
                app.alert.success(message || '');
                setTimeout(function(){
                    if( redirect ) bff.redirect(redirect);
                    else location.reload();
                }, 1500);
            };
            $ownerPanel.on('click', '.j-item-next', function(e){ nothing(e);
                //
            }).on('click', '.j-panel-actions-toggler', function(e){ nothing(e);
                var $this = $(this), state = $this.data('state');
                var $actions = $ownerPanel.find('.j-panel-actions');
                $this.find('.j-toggler-state').toggleClass('hide');
                $this.data('state', (state == 'hide' ? 'show' : 'hide'));
                if( state == 'hide' ) $actions.slideUp(); else $actions.slideDown();
            }).on('click', '.j-item-delete', function(e){ nothing(e);
                bff.ajax(statusURL+'delete', statusData, function(resp, errors){
                    if( resp && resp.success ) {
                        statusMessage(resp.message);
                    } else {
                        app.alert.error(errors);
                    }
                });
            }).on('click', '.j-item-unpublicate', function(e){ nothing(e);
                bff.ajax(statusURL+'unpublicate', statusData, function(resp, errors){
                    if( resp && resp.success ) {
                        statusMessage(resp.message, false);
                    } else {
                        app.alert.error(errors);
                    }
                });
            }).on('click', '.j-item-publicate', function(e){ nothing(e);
                bff.ajax(statusURL+'publicate', statusData, function(resp, errors){
                    if( resp && resp.success ) {
                        statusMessage(resp.message, false);
                    } else {
                        app.alert.error(errors);
                    }
                });
            });
            $container.on('click', '.j-item-refresh', function(e){ nothing(e);
                var $this = $(this);
                bff.ajax(statusURL+'refresh', statusData, function(resp, errors){
                    if( resp && resp.success ) {
                        $this.remove();
                        statusMessage(resp.message, resp.redirect);
                    } else {
                        app.alert.error(errors);
                    }
                });
            }).on('click', '.j-item-publicate', function(e){ nothing(e);
                bff.ajax(statusURL+'publicate', statusData, function(resp, errors){
                    if( resp && resp.success ) {
                        statusMessage(resp.message, false);
                    } else {
                        app.alert.error(errors);
                    }
                });
            });
        }

        if(o.msg_success && o.msg_success.length){
            app.alert.success(o.msg_success);
        }
        if(o.msg_error && o.msg_error.length){
            app.alert.error(o.msg_error);
        }

        // hook: init
        bff.hook('bbs.view.init', $container, o);
    }

	function viewsChart(blockID, data, lang)
	{
        var dateFormat = d3.time.format("%Y-%m-%d");
        data.map(function(d) {
            var date = dateFormat.parse(d.date);
            d.contacts = +d.contacts;
            d.item = +d.item;
            d.total = +d.total;
            d.date = date.getDate()+' '+lang.shortMonths[date.getMonth()];
            d.dateFull = date.getDate()+' '+lang.months[date.getMonth()]+' '+date.getFullYear();
            return d;
        });

        var verticalDate = (( data.length >= 12 ) ? 25 : 0);
        var margin = {top: 5, right: 30, bottom: 20 + verticalDate, left: 45},
            width = 600 - margin.left - margin.right,
            height = (310 + verticalDate) - margin.top - margin.bottom;

        var x = d3.scale.ordinal().rangeRoundBands([0, width], .5);
        var xAxis = d3.svg.axis().scale(x).orient("bottom");

        var y = d3.scale.linear().range([height, 0]);
        var yAxis = d3.svg.axis().scale(y).orient("left");//.ticks(7);

        var chart = d3.select(blockID).append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
          .append("g")
            .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        var barTooltip = d3.select(blockID).append("div")
            .attr("class", "bar-tooltip")
            .style("opacity", 0);

        x.domain(data.map(function(d){ return d.date; }));
        y.domain([0, d3.max(data, function(d){ return d.total; })+2]);

        chart.append("g")
          .attr("class", "x axis")
          .attr("transform", "translate(0," + height + ")")
          .call(xAxis);
        if( verticalDate ) {
          chart.selectAll("text")
            .attr("y", -4)
            .attr("x", -8)
            .attr("transform", "rotate(-90)")
            .style("text-anchor", "end");
        }

        chart.append("g")
          .attr("class", "y axis")
          .call(yAxis)
        .append("text")
          .attr("transform", "rotate(-90)")
          .attr("y", -40)
          .attr("x", -(height/2) )
          .attr("dy", ".71em")
          .style("text-anchor", "middle")
          .text(lang.y_title);

        var bar = chart.selectAll(".bar")
          .data(data)
        .enter().append("g")
          .attr("class", "bar")
          .attr("data-index", function(d,i){ return i; })
          .on("mousemove", function(d) {
            var pos = d3.mouse(this);
            barTooltip.transition().duration(100).style("opacity", 1);
            barTooltip.html("<span><b>"+d.dateFull+"</b></span><span>"+lang.item_views+": <b>"+ d.item+"</b></span><span>"+lang.contacts_views+": <b>"+d.contacts+"</b></span>")
                .style("left", (pos[0] + 65) + "px")
                .style("top", (pos[1] + 10) + "px");
          })
          .on("mouseout", function(d) {
            barTooltip.transition().duration(50).style("opacity", 0);
          });

        bar.append("rect")
          .attr("class", "bar-item")
          .attr("x", function(d) { return x(d.date); })
          .attr("width", x.rangeBand())
          .attr("y", function(d) { return y(d.total); })
          .attr("height", function(d) { return height - y(d.item); });

        bar.append("rect")
          .attr("class", "bar-contacts")
          .attr("x", function(d) { return x(d.date); })
          .attr("width", x.rangeBand())
          .attr("y", function(d) { return y(d.contacts); })
          .attr("height", function(d) { return height - y(d.contacts); });

        bar.append("text")
          .attr("class", "bar-cnt-total")
          .attr("x", function(d) { return x(d.date) + ( x.rangeBand() / 2 ); })
          .attr("y", function(d) { return y(d.total) - 3; })
          .text(function(d) { return d.total; });
	}

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('bbs.view.settings', $.extend(o, options || {}));
            $(function(){
                init();
            });
        },
        showMap: function()
        {
            if( map.map !== 0 ) {
                if(images.viewer.show) {
                    images.viewer.show(images.mapIndex);
                }
                map.map.panTo([parseFloat(o.addr_lat), parseFloat(o.addr_lon)], {delay: 10, duration: 200});
            }
            return false;
        },
        viewsChart: viewsChart
    };
}());