/**
 * Форма поиска магазинов
 */
var jShopsSearch = (function(){
    var inited = false, $form, $list, $pgn,
        listTypes = {list:1,map:3},
        url = document.location.pathname, o = {lang:{},ajax:true,geoMapZoom:10};

    function init()
    {
        $form = $('#j-f-form');
        $list = $('#j-shops-search-list');
        $pgn = $('#j-shops-search-pgn');
        // devices
        desktop_tablet.init();
        phone.init();
        $(window).on('app-device-changed', function(e, device){
            onSubmit({fade:false});
        });

        // contacts
        var contactsClass = '.j-contacts';
        var contactsShowList = function($link, _device) {
            if(_device == app.devices.phone) {
                $link.next().slideToggle();
            } else {
                $list.find(contactsClass+':visible').not($link.next()).addClass('hide');
                $link.next().toggleClass('hide');
            }
        };
        $list.on('click', '.j-contacts-ex', function(e){ nothing(e);
            var $link = $(this), _device = $link.data('device');
            var $shop = $link.closest('.j-shop');
            if ($shop.hasClass('j-contacts-loaded')) {
                contactsShowList($link, _device);
            } else {
                bff.ajax(bff.ajaxURL('shops','shop-contacts-list'), {
                    ex:$shop.data('ex'), hash:app.csrf_token, lt: listTypes.list
                }, function(data, errors) {
                    if(data && data.success) {
                        $shop.addClass('j-contacts-loaded').find(contactsClass).html(data.html);
                        contactsShowList($link, _device);
                    } else if(errors) {
                        app.alert.error(errors);
                    }
                });
            }
        }).on('click', function(e){
            var $target = $(e.target);
            if( ! ( $target.is('a') || $target.parents('a').length ||
                   $target.parents(contactsClass).length || $target.is(contactsClass)) ) {
                $list.find(contactsClass+':visible').addClass('hide');
            }
        });

        // list: type
        $list.find('#j-f-listtype').on('click', '.j-type', function(){
            if( ! $(this).hasClass('active') ) {
                onListType($(this).metadata());
            }
        });
        // pgn
        $pgn.on('keyup', '.j-pgn-goto', function(e){
            if(e.hasOwnProperty('keyCode') && e.keyCode == 13) {
                onPage($(this).val(), true);
                nothing(e);
            }
        });
        if (o.ajax) {
            $pgn.on('click', '.j-pgn-page', function(e){ nothing(e);
                onPage($(this).data('page'), true);
            });
        }

        // history
        if(o.ajax) {
            var queryInitial = prepareQuery();
            $(window).bind('popstate',function(e){
                var loc = history.location || document.location;
                var query = loc.search.substr(1);
                if( query.length == 0 ) query = queryInitial;
                $form.deserialize(query, true);
                desktop_tablet.onPopstate();
                phone.onPopstate();
                onSubmit({popstate:true});
            });
        }

        // hook: init
        bff.hook('shops.search.init', $form, o);
    }

    function onListType(typeData)
    {
        var v = $form.get(0).elements['lt'];
        if( typeData ) {
            v.value = typeData.id;
            o.ajax = false; // reload page on list type changes
            onSubmit();
            return typeData;
        }
        if( ! o.listtype.hasOwnProperty(v.value) ) {
            for(var i in o.listtype) { v.value = i; break; }
        }
        return {id:v.value,title:o.listtype[v.value].t};
    }

    function onPage(pageId, update)
    {
        pageId = intval(pageId);
        if( pageId <=0 ) pageId = 0;
        var v = $form.get(0).elements['page'];
        if( pageId && intval(v.value) != pageId ) {
            v.value = pageId;
            if(update) onSubmit({scroll:true});
        }
    }

    function onSubmit(ex)
    {
        ex = $.extend({popstate:false, scroll:false, fade:true}, ex||{});
        var query = prepareQuery();
        if(o.ajax) {
            bff.ajax(url, query, function(data){
                if(data && data.success) {
                    if( ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                    $pgn.html(data.pgn);
                    var list = $list.find('.j-list-'+app.device());
                    if( onListType().id == listTypes.map ) {
                        list.find('.j-maplist').html(data.list);
                        o.items = data.items;
                        desktop_tablet.itemsToMap(o.items);
                        phone.itemsToMap(o.items);
                    } else {
                        list.html(data.list);
                    }
                    if( ! ex.popstate) history.pushState(null, null, url+'?'+query);
                }
            }, function(p){
                if(ex.fade) $list.toggleClass('disabled');
            });
        } else {
            bff.redirect(url+'?'+query);
        }
    }

    function prepareQuery()
    {
        var query = [];
        $.each($form.serializeArray(), function(i, field) {
            if(field.value && field.value!=0 && field.value!='') query.push( field.name+'='+encodeURIComponent(field.value) );
        });
        return query.join('&');
    }

    var desktop_tablet = (function()
    {
        var inited = false, map = false, mapClusterer, $mapItems = [], mapContent = {}, bffmap = false, mapInfoWindow = false, mapMarkers = false;

        function init()
        {
            // map
            if( onListType().id == listTypes.map ) {
                mapInit();
            }
        }

        function mapInit()
        {
            var $c = $list.find('.j-search-map-desktop'); if ( ! $c.length) return;

            bffmap = app.map($c.get(0), o.defaultCoords, function(mmap) {
                if (this.isYandex()) {
                    map = mmap;
                    map.controls.remove('searchControl');
                    map.controls.remove('geolocationControl');
                    mapClusterer = new ymaps.Clusterer({
                        preset: 'twirl#blueClusterIcons',
                        clusterBalloonWidth: 240,
                        clusterBalloonHeight: 250,
                        clusterBalloonLeftColumnWidth: 37,
                        clusterDisableClickZoom: false,
                        zoomMargin: 15
                    });
                }else if (this.isGoogle()) {
                    map = mmap;
                    mapInfoWindow = new google.maps.InfoWindow({});
                }
                var $mapToggler = $list.find('#j-search-map-toggler').
                    on('click', 'a.j-search-map-toggler-link', function(e)
                    {
                        var $link = $(this);
                        var $arr = $('.j-search-map-toggler-arrow', $mapToggler);
                        var list = $list.find('.j-maplist'), listMap = $list.find('.j-map');
                        if( ! $link.hasClass('active') ) {
                            $link.text(o.lang.map_toggle_close).addClass('active');
                            $arr.html('&raquo;');
                            list.hide(); listMap.removeClass(listMap.data('short-class'));
                        } else {
                            $link.text(o.lang.map_toggle_open).removeClass('active');
                            $arr.html('&laquo;');
                            list.show(); listMap.addClass(listMap.data('short-class'));
                        }
                        if (bffmap.isYandex()) {
                            map.container.fitToViewport();
                            map.setBounds(map.getBounds());
                        }else if (bffmap.isGoogle()) {
                            if (mapClusterer) {
                                mapClusterer.fitMapToMarkers();
                            }
                            bffmap.refresh();
                        }
                        nothing(e);
                    });
                desktop_tablet.itemsToMap(o.items);
            }, {controls: 'search'});
        }

        function mapListItemToggle($item)
        {
            $mapItems.filter('.active').removeClass('active');
            if( $item && $item.length ) $item.addClass('active');
        }
        
        function mapBalloonOpen(point)
        {
            if ( ! bffmap || ! bffmap.isYandex()) return;
            var geoObjectState = mapClusterer.getObjectState(point),
                cluster = geoObjectState.isClustered && geoObjectState.cluster;
            if(cluster) {
                geoObjectState.cluster.state.set('activeObject', point);
                mapBalloonContent(point);
                mapClusterer.balloon.open(geoObjectState.cluster);
            } else {
                if( ! point.balloon.isOpen() ) point.balloon.open();
            }
        }

        function mapBalloonContent(placemark, listItemToggle)
        {
            if ( ! bffmap || ! bffmap.isYandex()) return;
            var index = placemark.properties.get('index');
            if (listItemToggle === true) mapListItemToggle( $mapItems.filter('[data-index="'+index+'"]') );
            if ( mapContent.hasOwnProperty(index) ) return;
            placemark.properties.set('balloonContent', o.lang.map_content_loading);
            mapContent[index] = '';
            bff.ajax(bff.ajaxURL('shops','shop-contacts-list'), {
                ex:placemark.properties.get('ex'), hash:app.csrf_token, lt: listTypes.map, device: app.device()
            }, function(data, errors) {
                if(data && data.success) {
                    placemark.properties.set('balloonContent', (mapContent[index] = data.html));
                } else if(errors) {
                    app.alert.error(errors);
                }
            });
        }

        function popupMarker(itemID, markerClick)
        {
            if ( ! bffmap || ! bffmap.isGoogle()) return;

            if (mapMarkers.hasOwnProperty(itemID)) {
                var m = mapMarkers[itemID];
                if( ! m.hasOwnProperty('ballon')){
                    bff.ajax(bff.ajaxURL('shops','shop-contacts-list'), {
                        ex: m.ex, hash:app.csrf_token, lt: listTypes.map, device: app.device()
                    }, function(data, errors) {
                        if(data && data.success) {
                            mapMarkers[itemID].ballon = data.html;
                            popupMarker(itemID, markerClick);
                        } else if(errors) {
                            app.alert.error(errors);
                        }
                    });
                    return;
                }

                if (markerClick!==true) {
                    map.panTo(m.position);
                }
                mapInfoWindow.close();
                mapInfoWindow.setPosition(m.position);
                mapInfoWindow.setContent(m.ballon);
                mapInfoWindow.open(map);
            }
            return false;
        }

        return {
            init:init,
            onPopstate: function() {
                if( ! inited ) return;
            },
            itemsToMap: function(items) {
                if (!bffmap) return;
                if (bffmap.isYandex()) {
                    // items map: clear
                    if (map === false) return;
                    mapContent = {};
                    mapClusterer.removeAll();
                    map.geoObjects.remove(mapClusterer);

                    var itemsToCluster = [], j = 0;
                    for (var i in items) {
                        var v = items[i];
                        v.point_desktop = itemsToCluster[j++] = new ymaps.Placemark([parseFloat(v.addr_lat), parseFloat(v.addr_lon)], {
                            index: i, ex: v.ex, num: v.num, clusterCaption: v.num
                        }, {preset: 'twirl#blueIcon', openEmptyBalloon: true});
                        v.point_desktop.events.add('click', function (e) {
                            var placemark = e.get('target');
                            if ($mapItems.length) {
                                var itemIndex = placemark.properties.get('index');
                                mapListItemToggle($mapItems.filter('[data-index="' + itemIndex + '"]'));
                            }
                        });
                        v.point_desktop.events.add('balloonopen', function (e) {
                            mapBalloonContent(e.get('target'));
                        });
                    }
                    mapClusterer.add(itemsToCluster);
                    map.geoObjects.add(mapClusterer);
                    if (itemsToCluster.length > 1) {
                        var pos = ymaps.util.bounds.getCenterAndZoom(
                            mapClusterer.getBounds(), map.container.getSize(), map.options.get('projection')
                        );
                        map.setCenter(pos.center, pos.zoom);
                    } else {
                        if (itemsToCluster.length) {
                            map.setCenter(itemsToCluster[0].geometry.getCoordinates());
                        }
                    }
                    map.container.fitToViewport();
                    mapClusterer.events.once('objectsaddtomap', function () {
                        map.setBounds(mapClusterer.getBounds(), {checkZoomRange: true});
                    });
                    mapClusterer.events.add('balloonopen', function (e) {
                        var target = e.get('target');
                        if (target.getGeoObjects) {
                            var activeObject = target.state.get('activeObject');
                            mapBalloonContent(activeObject, true);
                            target.state.events.add('change', function () {
                                var newActiveObject = target.state.get('activeObject');
                                if (activeObject != newActiveObject) {
                                    activeObject = newActiveObject;
                                    mapBalloonContent(activeObject, true);
                                }
                            });
                        }
                    });
                }
                else if (bffmap.isGoogle())
                {
                    if (mapClusterer) {
                        mapClusterer.clearMarkers();
                    }
                    mapMarkers = {};
                    var mapMarkersToCluster = [];
                    mapInfoWindow.close();
                    var v, j = 0;
                    for (var i in items) {
                        v = items[i];
                        var id = j++;
                        var marker = new google.maps.Marker({
                            position: new google.maps.LatLng(parseFloat(v.addr_lat), parseFloat(v.addr_lon))
                        });
                        marker.itemID = id;
                        mapMarkers[id] = {
                            position: marker.getPosition(),
                            ex: v.ex
                        };
                        mapMarkersToCluster.push(marker);
                        google.maps.event.addListener(marker, 'click', function () {
                            popupMarker(this.itemID, true);
                            mapListItemToggle($mapItems.filter('[data-index="' + this.itemID + '"]'));
                        });
                    }

                    mapClusterer = new MarkerClusterer(map, mapMarkersToCluster, {
                        imagePath: app.rootStatic+'/js/markerclusterer/images/m'
                    });
                    if (mapMarkersToCluster.length > 0) {
                        if (mapMarkersToCluster.length > 1) {
                            mapClusterer.fitMapToMarkers();
                        }else{
                            bffmap.panTo([v.addr_lat, v.addr_lon], {delay: 10, duration: 200});
                        }
                    }
                    bffmap.refresh();
                }

                // items: list
                $mapItems = $list.find('.j-maplist .j-maplist-item').bind('click', function (e) {
                    var $item = $(this);
                    var itemIndex = $item.data('index');
                    if (!o.items.hasOwnProperty(itemIndex) || $(e.target).is('a') || $(e.target).parents('a').length) return;
                    mapListItemToggle($item);
                    if (bffmap.isYandex()) {
                        var itemPoint = o.items[itemIndex].point_desktop;
                        var state = mapClusterer.getObjectState(itemPoint), cluster = state.cluster;
                        if ((itemPoint.getMap() || (cluster && cluster.getMap()))) {
                            mapBalloonOpen(itemPoint);
                        } else {
                            map.panTo(itemPoint.geometry.getCoordinates(), {
                                duration: 400, delay: 0, callback: function () {
                                    mapClusterer.events.once('objectsaddtomap', function () {
                                        mapBalloonOpen(itemPoint);
                                    });
                                }
                            });
                        }
                    }
                    else if (bffmap.isGoogle())
                    {
                        popupMarker(itemIndex, false);
                    }
                });

            }
        };
    }());

    var phone = (function()
    {
        var inited = false, map, mapClusterer, mapContent, bffmap = false, mapInfoWindow = false, mapMarkers = false;


        function init()
        {
            // map
            if( onListType().id == listTypes.map ) {
                mapInit();
            }
        }

        function mapInit()
        {
            var $c = $('.j-search-map-phone'); if ( ! $c.length) return;

            bffmap = app.map($c.get(0), o.defaultCoords, function(mmap) {
                if (this.isYandex()) {
                    map = mmap;
                    // Создаем собственный макет с информацией о выбранном геообъекте.
                    var customItemContentLayout = ymaps.templateLayoutFactory.createClass('<div class="ballon_body">{{ properties.balloonContentBody|raw }}</div>');

                    mapClusterer = new ymaps.Clusterer({
                        preset: 'twirl#blueClusterIcons',
                        clusterBalloonContentLayoutWidth: 250,
                        clusterBalloonContentLayoutHeight: 100,
                        clusterBalloonLeftColumnWidth: 37,
                        clusterDisableClickZoom: false,
                        clusterBalloonItemContentLayout: customItemContentLayout,
                        zoomMargin: 15
                    });

                }else if (this.isGoogle()) {
                    map = mmap;
                    mapInfoWindow = new google.maps.InfoWindow({});
                }
                phone.itemsToMap(o.items);
            }, {controls: 'view', zoom: o.geoMapZoom});
        }

        function mapBalloonContent(placemark)
        {
            if ( ! bffmap || ! bffmap.isYandex()) return;
            var index = placemark.properties.get('index');
            if ( mapContent.hasOwnProperty(index) ) return;
            placemark.properties.set('balloonContent', o.lang.map_content_loading);
            mapContent[index] = '';
            bff.ajax(bff.ajaxURL('shops','shop-contacts-list'), {
                ex:placemark.properties.get('ex'), hash:app.csrf_token, lt: listTypes.map, device: app.device()
            }, function(data, errors) {
                if(data && data.success) {
                    placemark.properties.set('balloonContent', (mapContent[index] = data.html));
                } else if(errors) {
                    app.alert.error(errors);
                }
            });
        }

        function popupMarker(itemID, markerClick)
        {
            if ( ! bffmap || ! bffmap.isGoogle()) return;

            if (mapMarkers.hasOwnProperty(itemID)) {
                var m = mapMarkers[itemID];
                if( ! m.hasOwnProperty('ballon')){
                    bff.ajax(bff.ajaxURL('shops','shop-contacts-list'), {
                        ex: m.ex, hash:app.csrf_token, lt: listTypes.map, device: app.device()
                    }, function(data, errors) {
                        if(data && data.success) {
                            mapMarkers[itemID].ballon = data.html;
                            popupMarker(itemID, markerClick);
                        } else if(errors) {
                            app.alert.error(errors);
                        }
                    });
                    return;
                }

                if (markerClick!==true) {
                    map.panTo(m.position);
                }
                mapInfoWindow.close();
                mapInfoWindow.setPosition(m.position);
                mapInfoWindow.setContent(m.ballon);
                mapInfoWindow.open(map);
            }
            return false;
        }

        return {
            init:init,
            onPopstate: function() {
                if( ! inited ) return;
            },
            itemsToMap: function(items) {
                if (!bffmap) return;
                if (bffmap.isYandex()) {
                    // items: map
                    if (map === false) return;
                    mapContent = {};
                    mapClusterer.removeAll();
                    map.geoObjects.remove(mapClusterer);
                    var itemsToCluster = [], j = 0;
                    for (var i in items) {
                        var v = items[i];
                        var placemark = itemsToCluster[j++] = new ymaps.Placemark([parseFloat(v.addr_lat), parseFloat(v.addr_lon)], {
                            index: i, ex: v.ex, clusterCaption: v.num
                        }, {preset: 'twirl#blueIcon', openEmptyBalloon: true});
                        placemark.events.add('balloonopen', function (e) {
                            mapBalloonContent(e.get('target'));
                        });
                    }
                    mapClusterer.add(itemsToCluster);
                    map.geoObjects.add(mapClusterer);
                    if (itemsToCluster.length > 1) {
                        var pos = ymaps.util.bounds.getCenterAndZoom(
                            mapClusterer.getBounds(), map.container.getSize(), map.options.get('projection')
                        );
                        map.setCenter(pos.center, pos.zoom);
                    } else {
                        if (itemsToCluster.length) {
                            map.setCenter(itemsToCluster[0].geometry.getCoordinates());
                        }
                    }
                    map.container.fitToViewport();

                    mapClusterer.events.add('balloonopen', function (e) {
                        var target = e.get('target');
                        if (target.getGeoObjects) {
                            var activeObject = target.state.get('activeObject');
                            mapBalloonContent(activeObject);
                            target.state.events.add('change', function () {
                                var newActiveObject = target.state.get('activeObject');
                                if (activeObject != newActiveObject) {
                                    activeObject = newActiveObject;
                                    mapBalloonContent(activeObject);
                                }
                            });
                        }
                    });
                }
                else if (bffmap.isGoogle())
                {
                    if (mapClusterer) {
                        mapClusterer.clearMarkers();
                    }
                    mapMarkers = {};
                    var mapMarkersToCluster = [];
                    mapInfoWindow.close();
                    var v, j = 0;
                    for (var i in items) {
                        v = items[i];
                        var id = j++;
                        var marker = new google.maps.Marker({
                            position: new google.maps.LatLng(parseFloat(v.addr_lat), parseFloat(v.addr_lon))
                        });
                        marker.itemID = id;
                        mapMarkers[id] = {
                            position: marker.getPosition(),
                            ex: v.ex
                        };
                        mapMarkersToCluster.push(marker);
                        google.maps.event.addListener(marker, 'click', function () {
                            popupMarker(this.itemID, true);
                        });
                    }

                    mapClusterer = new MarkerClusterer(map, mapMarkersToCluster, {
                        imagePath: app.rootStatic+'/js/markerclusterer/images/m'
                    });
                    if (mapMarkersToCluster.length > 0) {
                        if (mapMarkersToCluster.length > 1) {
                            mapClusterer.fitMapToMarkers();
                        }else{
                            bffmap.panTo([v.addr_lat, v.addr_lon], {delay: 10, duration: 200});
                        }
                    }
                    bffmap.refresh();
                }
            }
        };
    }());

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('shops.search.settings', $.extend(o, options || {}));
            $(function(){
                init();
            });
        }
    };
}());

$(function(){
    /**
     * category-filter (desktop)
     */
    app.popup('f-cat-desktop', '#j-f-cat-desktop-popup', '#j-f-cat-desktop-link', {onInit: function($p){
        var _this = this;
        var $st1 = $p.find('#j-f-cat-desktop-step1');
        var $st2 = $p.find('#j-f-cat-desktop-step2'), st2cache = {};
        function doFilter(type, $link)
        {
            var f = $link.metadata(); f['type'] = type; f['link'] = $link.attr('href');
            _this.getLink().children('.title').text(f.title);
            bff.redirect(f['link']);
        }
        function st2View(parentID, fromStep1)
        {
            if( st2cache.hasOwnProperty(parentID) ) {
                $st2.html(st2cache[parentID].html);
                if(fromStep1) $st2.add($st1).toggleClass('hide');
            } else {
                bff.ajax(bff.ajaxURL('shops','search&ev=catsList'), {parent:parentID, device:app.devices.desktop}, function(data){
                    if(data && data.success) {
                        st2cache[parentID] = data;
                        st2View(parentID, fromStep1);
                    }
                });
            }
        }
        $st1.on('click', '.j-all', function(){
            _this.hide();
            doFilter('all', $(this));
            return false;
        });
        $st1.on('click', '.j-main', function(){
            var data = $(this).metadata();
            if( data.subs > 0 )  {
                st2View(data.id, true);
            } else {
                _this.hide();
                doFilter('cat', $(this));
            }
            return false;
        });
        $st2.on('click', '.j-back', function(){
            var prevID = $(this).metadata().prev;
            if( prevID === 0) {
                $st2.add($st1).toggleClass('hide');
            } else {
                st2View(prevID, false);
            }
            return false;
        });
        $st2.on('click', '.j-parent', function(){
            _this.hide();
            doFilter('cat', $(this));
            return false;
        });
        $st2.on('click', '.j-sub', function(){
            var data = $(this).metadata();
            if( data.subs > 0 )  {
                st2View(data.id, false);
            } else {
                _this.hide();
                doFilter('cat', $(this));
            }
            return false;
        });
    }});
    /**
     * category-filter (phone)
     */
    app.popup('f-cat-phone', '#j-f-cat-phone-popup', '#j-f-cat-phone-link', {onInit: function($p){
        var _this = this;
        var $st1 = $p.find('#j-f-cat-phone-step1');
        var $st2 = $p.find('#j-f-cat-phone-step2'), st2cache = {};
        function doFilter(type, $link)
        {
            var f = $link.metadata(); f['type'] = type; f['link'] = $link.attr('href');
            _this.getLink().children('.title').text(f.title);
            bff.redirect(f['link']);
        }
        function st2View(parentID, fromStep1)
        {
            if( st2cache.hasOwnProperty(parentID) ) {
                $st2.html(st2cache[parentID].html);
                if(fromStep1) $st2.add($st1).toggleClass('hide');
                $.scrollTo($st2, {offset: -10, duration: 400, axis: 'y'});
            } else {
                bff.ajax(bff.ajaxURL('shops','search&ev=catsList'), {parent:parentID, device:app.devices.phone}, function(data){
                    if(data && data.success) {
                        st2cache[parentID] = data;
                        st2View(parentID, fromStep1);
                    }
                });
            }
        }
        $st1.on('click', '.j-main', function(){
            var data = $(this).metadata();
            if( data.subs > 0 )  {
                st2View(data.id, true);
            } else {
                _this.hide();
                doFilter('cat', $(this));
            }
            return false;
        });
        $st2.on('click', '.j-back', function(){
            var prevID = $(this).metadata().prev;
            if( prevID === 0) {
                $st2.add($st1).toggleClass('hide');
            } else {
                st2View(prevID, false);
            }
            return false;
        });
        $st2.on('click', '.j-parent', function(){
            _this.hide();
            doFilter('cat', $(this));
            return false;
        });
        $st2.on('click', '.j-sub', function(){
            var data = $(this).metadata();
            if( data.subs > 0 )  {
                st2View(data.id, false);
            } else {
                _this.hide();
                doFilter('cat', $(this));
            }
            return false;
        });
    }});
});