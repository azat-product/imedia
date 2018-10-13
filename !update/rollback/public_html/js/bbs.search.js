/**
 * Форма поиска объявлений
 */
var jBBSSearch = (function(){
    var inited = false, $form, $list, $pgn,
        listTypes = {list:1,gallery:2,map:3},
        url = document.location.pathname, o = {lang:{},ajax:true,isVertical:false, isMapVertical:false, filterDropdownMargin:15};

    function init()
    {
        $form = $('#j-f-form');
        $list = $('#j-bbs-search-list');
        $pgn = $('#j-bbs-search-pgn');
        // devices
        desktop_tablet.init();
        if (o.isVertical) { desktop_tablet_vertical.init(); }
        phone.init();
        $(window).on('app-device-changed', function(e, device){
            onSubmit({fade:false});
        });

        // list: sort
        app.popup('f-sort', '#j-f-sort-dd', '#j-f-sort-dd-link', {onInit:function($p){
            var _this = this;
            var link = this.getLink();
            $p.on('click', '.j-f-sort', function(){
                var sortData = $(this).metadata();
                _this.hide();
                if( link.data('current') != sortData.key ) {
                    link.find('.lnk').text(sortData.title);
                    link.data('current', sortData.key);
                    onSort(sortData);
                }
            });
        }});
        // list: type
        $list.find('#j-f-listtype').on('click', '.j-type', function(){
            if( ! $(this).hasClass('active') ) {
                //$(this).addClass('active').siblings().removeClass('active');
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
                if (o.isVertical) {
                    desktop_tablet_vertical.onPopstate(query);
                }
                desktop_tablet.onPopstate();
                phone.onPopstate();
                // list: sort
                var f_sort = onSort();
                $list.find('#j-f-sort-dd-link').data('current', f_sort.key).find('.lnk').text(f_sort.title);
                // list: type
                //var f_listtype = onListType();
                //$list.find('#j-f-listtype .j-type').removeClass('active').filter('[data-id="'+f_listtype.id+'"]').addClass('active');
                onSubmit({popstate:true});
            });
        }

        $form.on('click', '.j-submit', function(){
            onPage(1, false);
            if (o.isVertical) {
                desktop_tablet_vertical.prepareSubmit($form);
            }
        });

        // hook: init
        bff.hook('bbs.search.init', $form, o);
    }

    function onCatType(typeData)
    {
        var v = $form.get(0).elements['ct'];
        if( typeData ) {
            v.value = typeData.id;
            onSubmit();
            return typeData;
        }
        if( ! o.cattype.hasOwnProperty(v.value) ) {
            for(var i in o.cattype) { v.value = i; break; }
        }
        return o.cattype[v.value];
    }

    function onSort(sortData)
    {
        var v = $form.get(0).elements['sort'];
        if( sortData ) {
            v.value = sortData.key;
            onPage(1, false);
            onSubmit();
            return sortData;
        }
        if( ! o.sort.hasOwnProperty(v.value) ) {
            for(var i in o.sort) { v.value = i; break; }
        }
        return {key:v.value,title:o.sort[v.value].t};
    }

    function onListType(typeData)
    {
        var v = $form.get(0).elements['lt'];
        if( typeData ) {
            v.value = typeData.id;
            o.ajax = false; // reload page on list type changes
            //onPage(1, false);
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
        return v.value;
    }

    function onSubmit(ex)
    {
        ex = $.extend({popstate:false, scroll:false, fade:true, resetPage:false}, ex||{});
        if (ex.resetPage) onPage(1, false);
        var query = prepareQuery();
        if(o.ajax) {
            var ajaxQuery = query;
            if(o.isMapVertical){ ajaxQuery = ajaxQuery + '&mapVertical=1'; }
            bff.ajax(url, ajaxQuery, function(data){
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
        if (o.isVertical) {
            desktop_tablet_vertical.prepareSubmit($form);
        }
        $.each($form.serializeArray(), function(i, field) {
            if(field.value && field.value!=0 && field.value!='') query.push( field.name+'='+encodeURIComponent(field.value) );
        });
        return query.join('&');
    }

    var desktop_tablet = (function()
    {
        var inited = false, $filter, filterButtons = {}, $typesToggler, childCache = {}, childName = 'dc',
            map = false,  bffmap = false, mapClusterer = false, $mapItems = [], mapInfoWindow = false, mapMarkers = false;

        function init()
        {
            $filter = $('#j-f-desktop'); if( ! $filter.length && ! o.isVertical) return; inited = true;
            var $form = $('#j-f-form');
            $filter.find('.j-button').each(function(){
                var b = $(this).metadata(); b.$button = $(this); b.$buttonCaret = b.$button.find('.j-button-caret');
                b.popup = app.popup('f-desktop-button-'+b.key, b.$button.prev(), b.$button, {pos:{top:b.$button.outerHeight()+intval(o.filterDropdownMargin), minRightSpace:200}, onInit: function($p){
                    var _this = this;
                    $p.on('click', '.j-submit', function(){
                        _this.hide();
                        onSubmit();
                    }).on('click', ':checkbox', function(){
                        onCheckbox($(this), b);
                        if ( $(this).hasClass('j-reset') ) {
                            _this.hide();
                            onSubmit();
                        }
                    }).on('keyup', '.j-from,.j-to', function(){
                        $p.find('.j-reset').prop({checked:false,disabled:false});
                        updateBlockButton(b);
                    }).on('click', '.j-catLink', function(e){
                        e.preventDefault();
                        _this.hide();
                        bff.redirect($(this).attr('href')+'?'+prepareQuery());
                    });
                    if(b.type == 'price') {
                        $p.find('.j-curr-select').change(function(){
                            $p.find('.j-curr').val(' '+this.options[this.selectedIndex].text);
                            updateBlockButton(b);
                        });
                    } else if(b.type == 'metro') {
                        updateBlockButton(b);
                    }
                }, onShow: function($p){
                    $p.addClass('open').fadeIn(100);
                    b.$buttonCaret.toggleClass('fa-caret-up fa-caret-down');
                }, onHide: function($p){
                    $p.removeClass('open').fadeOut(100);
                    b.$buttonCaret.toggleClass('fa-caret-up fa-caret-down');
                }});
                b.$popup = b.popup.getPopup();
                filterButtons[b.key] = b;
            });
            $filter.on('click', '.j-checkbox :checkbox', function(){
                if( $(this).is('[name^=ow]') ) $filter.find('[name^=ow]').not(this).prop({checked:false});
                onSubmit();
            });

            // list: cat types
            $typesToggler = $list.find('.j-f-cattype-desktop').on('click', function(){
                if( $(this).parent().hasClass('active') ) return;
                var type = $(this).metadata();
                toggleFiltersByCatType(type);
                onCatType(type);
                $typesToggler.parent().removeClass('active');
                $(this).parent().addClass('active');
            });

            // map
            if( onListType().id == listTypes.map ) {
                mapInit();
            }
        }

        function onCheckbox($check, b)
        {
            var isChecked = $check.is(':checked');
            if ( $check.hasClass('j-reset') ) {
                if( isChecked ) {
                    b.$popup.find(':checkbox').not($check).prop({checked:false});
                    b.$popup.find('.j-from,.j-to').val('');
                    $check.prop({disabled:true});
                }
            } else {
                if( isChecked ) {
                    b.$popup.find('.j-reset').prop({checked:false,disabled:false});
                }
            }
            if( b.parent ) updateChildBlock(b);
            updateBlockButton(b);
        }

        function updateBlockButton(b)
        {
            var checks = [];
            if( b.$popup.find('.j-children').length ) {
                checks = b.$popup.find('.j-children > div:not(.hide) :checkbox:not(.j-reset):checked');
            } else {
                checks = b.$popup.find(':checkbox:not(.j-reset):checked');
            }
            var active = false;
            var value = o.lang.btn_reset;
            var value_plus = 0;
            switch(b.type) {
                case 'checks':
                case 'checks-child':
                    if( active = checks.length > 0 ) {
                        //value = []; checks.each(function(){ value.push( $(this).parent().text() ); }); value = value.join(', ');
                        value = checks.first().parent().text();
                        value_plus = checks.length;
                    }
                break;
                case 'range':
                case 'price':
                    var from = parseInt(b.$popup.find('.j-from').val());
                    var to = parseInt(b.$popup.find('.j-to').val());
                    var from_to = (from > 0 || to > 0);
                    if(from_to && from>=to) from = 0;
                    if( active = (checks.length > 0 || from_to) ) {
                        value = ( from_to ? (from>0&&to>0 ? from+' - '+to:(from>0 ? o.lang.range_from+'&nbsp;'+from : o.lang.range_to+'&nbsp;'+to))
                                        + (b.type == 'price' ? ' '+b.$popup.find('.j-curr').val() : '')
                                    : checks.first().parent().text() );
                        value_plus = (checks.length + (from_to?1:0));
                    }
                break;
                case 'metro':
                    active = checks.length > 0;
                    if (active) {
                        value = bff.declension(checks.length, o.lang.metro_declension.split(';'));
                        b.$popup.find('.j-metro-branch').each(function(){
                            var $br = $(this);
                            var l = $br.find(':checkbox:checked').length;
                            $br.find('.j-cnt').html(l ? l : '');
                        });
                    } else {
                        b.$popup.find('.j-cnt').html('');
                    }
                break;
            }
            b.$button.toggleClass('selected', active)
                .find('.j-value').html(value+' <i class="fa fa-plus-square extra'+(value_plus<=1 ? ' hide' :'')+'"></i>');
        }

        function toggleFiltersByCatType(typeData)
        {
            if( o.cattype_ex ) return;
            var is_seek = (intval(typeData.id) === 1);
            // filter: buttons
            for(var i in filterButtons) {
                var b = filterButtons[i];
                var disable = ( is_seek ? !b.seek : false);
                b.$popup.find(':input').prop('disabled', disable);
                if(o.ajax) b.$button.toggleClass('hide', disable);
            }
            // filter: checkboxes
            $filter.find('.j-checkbox').each(function(){
                var b = $(this).parent();
                var disable = !( ! is_seek || b.hasClass('j-seek') );
                b.find(':checkbox').prop('disabled', disable);
                if(o.ajax) { if( disable ) b.hide(); else b.show(); }
            });
        }

        function updateChildBlock(parentBlock)
        {
            var parents = parentBlock.$popup.find(':checkbox:not(.j-reset):checked');
            var parentId = parentBlock.id;
            var childBlock = getChildBlock(parentBlock.key); if(childBlock === false) return;
            var childPrefix = parentBlock.key+'-child-';
            var $childrenContainer = childBlock.$popup.find('.j-children');
                $childrenContainer.children().addClass('hide');
            var childVisible = false;
            parents.each(function(i){
                var $parentCheck = $(this);
                var parentValue = $parentCheck.val();
                var childKey = childPrefix+parentValue;
                if( $childrenContainer.find('#'+childKey).removeClass('hide').length > 0 ){ childVisible = true; return; }
                bff.ajax(bff.ajaxURL('bbs','dp-child'), {dp_id:parentId, dp_value:parentValue, format:'f-desktop'}, function(data){
                    if(data && data.success && $.isPlainObject(data)) {
                        data.key = childKey;
                        data.parent = {id:parentId, value:parentValue, title:$parentCheck.parent().text(), num:intval($parentCheck.data('num'))};
                        var html = buildChildrenHTML((childCache[childKey] = data));
                        if(html){ childBlock.$button.removeClass('hide'); }
                        var after = null;
                        $childrenContainer.children().each(function(i){
                            if( data.parent.num > intval($(this).data('num')) ) {
                                after = $(this);
                            }
                        });
                        if(after) after.after(html);
                        else $childrenContainer.prepend(html);
                        updateBlockButton(childBlock);
                    }
                });
            });
            childBlock.$button.toggleClass('hide', ! childVisible);
            updateBlockButton(childBlock);
        }

        function buildChildrenHTML(data)
        {
            if( ! data || ! $.isArray(data.multi) ) return '';
            var html = '<div id="'+data.key+'" data-num="'+data.parent.num+'"><div class="f-catfilter__popup__subtitle rel"><span>'+data.parent.title+'</span> <hr/></div>';
            var total = data.multi.length;
            if( total > 0 ) {
                var cols = 1, colsMin = {2:4,3:15}; for(var j in colsMin) { if(total>=colsMin[j]) cols = j; else break; }
                var items_in_col = Math.ceil(total/cols);
                var break_column = items_in_col;
                var i = 0, col_i = 1, name_child = childName+'['+data.df+']['+data.id+']';
                html += '<ul style="float:left;">';
                for (var k in data.multi) {
                    if (i == break_column) {
                        col_i++;
                        html += '</ul><ul style="float:left;">';
                        if (col_i<cols) break_column += items_in_col;
                    }
                    var m = data.multi[k];
                    html += '<li><label class="checkbox"><input type="checkbox" name="'+name_child+'['+m.value+']" value="'+m.value+'" />'+m.name+'</label></li>';
                    i++;
                }
                html += '</ul>';
                html += '<div style="clear:both;"></div>';
            }
            return html+'</div>';
        }

        function getChildBlock(parentKey)
        {
            var popup = app.popup('f-desktop-button-'+parentKey+'-child');
            if( popup === false ) return false;
            return $.extend( popup.getLink().metadata(), {$button:popup.getLink(),popup:popup,$popup:popup.getPopup()} );
        }

        function mapInit()
        {
            var mapContainer = $list.find('.j-search-map-desktop'); if (!mapContainer.length) { return; }
            bffmap = app.map(mapContainer.get(0), o.defaultCoords, function(mmap) {
                if (this.isYandex()) {
                    map = mmap;
                    map.controls.remove('searchControl');
                    map.controls.remove('geolocationControl');

                    var mapLayouts = {
                        sidebarItem: ymaps.templateLayoutFactory.createClass(
                            '<span class="badge[if data.isSelected] badge-success[endif]" style="margin-bottom: 5px; cursor: pointer;">$[properties.num]</span>'
                        )
                    };

                    // Создаем собственный макет с информацией о выбранном геообъекте.
                    var customItemContentLayout = ymaps.templateLayoutFactory.createClass('<div class="ballon_body">{{ properties.balloonContentBody|raw }}</div>');

                    mapClusterer = new ymaps.Clusterer({
                        preset: 'twirl#blueClusterIcons',
                        clusterBalloonContentLayoutWidth: 400,
                        clusterBalloonContentLayoutHeight: 100,
                        clusterBalloonLeftColumnWidth:37,
                        clusterDisableClickZoom: false,
                        clusterBalloonItemContentLayout: customItemContentLayout,
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
                        if( ! $link.hasClass('active') ) {
                            $link.text(o.lang.map_toggle_close).addClass('active');
                            $arr.html('&raquo;');
                            $list.find('.j-maplist').hide().end().find('.j-map').removeClass('span7');
                        } else {
                            $link.text(o.lang.map_toggle_open).removeClass('active');
                            $arr.html('&laquo;');
                            $list.find('.j-maplist').show().end().find('.j-map').addClass('span7');
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
                mapClusterer.balloon.open(geoObjectState.cluster);
            } else {
                if( ! point.balloon.isOpen() ) point.balloon.open();
            }
        }

        function popupMarker(itemID, markerClick)
        {
            if ( ! bffmap || ! bffmap.isGoogle()) return;

            if (mapMarkers.hasOwnProperty(itemID)) {
                var m = mapMarkers[itemID];
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
                // filter: buttons
                for(var i in filterButtons) {
                    if( ! filterButtons.hasOwnProperty(i) ) continue;
                    var b = filterButtons[i];
                    if( b.parent ) updateChildBlock(b);
                    updateBlockButton(b);
                }
                // list: cat types
                var f_cattype = onCatType();
                $typesToggler.parent().removeClass('active');
                $typesToggler.filter('[data-id="'+f_cattype.id+'"]').parent().addClass('active');
                toggleFiltersByCatType(f_cattype);
            },
            itemsToMap: function(items) {
                function baloonHTML(v) {
                    var tpl = $list.find('.j-tpl-map-balloon-desktop');
                    if (tpl.length) { return bff.tmpl(tpl.html(), v); }
                    return '<div class="sr-page__map__balloon" style="padding-top: 0;">' +
                    (v.imgs > 0 ? '<div class="sr-page__map__balloon_img pull-left">\
                                <span class="rel inlblk">\
                                    <a class="thumb stack rel inlblk" href="' + v.link + '" title="' + v.title + '">\
                                        <img class="rel br2 zi3 shadow" src="' + v.img_s + '" alt="' + v.title + '" />\
                                        <span class="abs border b2 shadow">&nbsp;</span>\
                                        <span class="abs border r2 shadow">&nbsp;</span>\
                                    </a>\
                                </span>\
                            </div>' : '') +
                    '<div class="sr-page__map__balloon_descr pull-left">\
                        <h6><a href="' + v.link + '">' + v.title + '</a></h6>' +
                    (v.price_on ? '<p class="sr-page__map__balloon_price"><strong>' + v.price + '</strong> <small>' + v.price_mod + '</small></p>' : '') +
                    '<p><small>' + v.cat_title + '</small><br /><i class="fa fa-map-marker"></i> ' + v.city_title + (v.hasOwnProperty('district_title') ? ', '+ v.district_title : '') +', ' + v.addr_addr + '</p>\
                            </div>' +
                    '</div>';
                }

                if (!bffmap) return;
                if (bffmap.isYandex()) {

                    // items: map
                    mapClusterer.removeAll();
                    map.geoObjects.remove(mapClusterer);

                    var itemsToCluster = [], j = 0;
                    for (var i in items) {
                        var v = items[i];

                        v.point_desktop = itemsToCluster[j++] = new ymaps.Placemark([parseFloat(v.lat), parseFloat(v.lon)], {
                            index: i,
                            num: v.num,
                            //clusterCaption: '<span class="badge">'+v.num+'</span>',
                            clusterCaption: v.num,
                            balloonCloseButton: false,
                            balloonContentBody: baloonHTML(v)
                        }, {preset: 'twirl#blueIcon'});
                        v.point_desktop.events.add('click', function (e) {
                            if ($mapItems.length) {
                                var itemIndex = e.get('target').properties.get('index');
                                mapListItemToggle($mapItems.filter('[data-index="' + itemIndex + '"]'));
                            }
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
                            position: new google.maps.LatLng(parseFloat(v.lat), parseFloat(v.lon))
                        });
                        marker.itemID = id;
                        mapMarkers[id] = {
                            position: marker.getPosition(),
                            ballon: '<div style="overflow: hidden;">'+baloonHTML(v)+'</div>'
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
                            bffmap.panTo([v.lat, v.lon], {delay: 10, duration: 200});
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
                        var geoObjectState = mapClusterer.getObjectState(itemPoint), cluster = geoObjectState.cluster;
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
                    if (o.isMapVertical) {
                        $.scrollTo($list, {offset: 0, duration:500, axis: 'y'});
                    }
                });
            }
        };
    }());

    var desktop_tablet_vertical = (function()
    {
        var inited = false, $filter, childCache = {}, childName = 'dc';

        function init()
        {
            $filter = $('#j-f-vertical'); if( ! $filter.length ) return; inited = true;

            $filter.find('.j-filter-bl').each(function(){
                var b = $(this).metadata(); b.$bl = $(this);
                b.$bl.on('click', ':checkbox', function(){
                    var $el = $(this);
                    if ( $el.hasClass('j-reset') ) {
                        if($el.is(':checked')){
                            b.$bl.find(':checkbox').not($el).prop({checked:false});
                            b.$bl.find('.j-from,.j-to').val('');
                        }
                        submit();
                    }
                    updateBlock(b);
                    if(o.ajax) {
                        onSubmit();
                    }
                }).on('keyup', '.j-from,.j-to', function(){
                    updateBlock(b);
                    if(o.ajax) {
                        onSubmit();
                    }
                });
                if(b.type == 'price'){
                    b.$bl.on('click', '.j-currency-select', function(e){
                        e.preventDefault();
                        b.$bl.find('[name="p[c]"]').val($(this).data('id'));
                        updateBlock(b);
                        if(o.ajax) {
                            onSubmit();
                        }
                    });
                }
            });

            $filter.on('click', '.j-submit', function(e){
                e.preventDefault();
                submit();
            });

            $filter.on('click', '.j-reset-all', function(e){
                e.preventDefault();
                $filter.find(':checkbox:checked:not(.j-reset)').prop({checked:false});
                $filter.find('.j-from,.j-to').val('');
                submit();
            });

            var $desktop = $('#j-filter-vertical-desktop');
            var $tablet  = $('#j-filter-vertical-tablet');

            $(window).on('app-device-changed', function(e, device){
                placeFilter();
            });

            function placeFilter()
            {
                switch(app.device()){
                    case app.devices.desktop:
                        $desktop.append($filter);
                        break;
                    case app.devices.tablet:
                    case app.devices.phone:
                        $tablet.append($filter);
                        break;
                }
            }
            placeFilter();

            if(o.ajax){
                $filter.find('.j-submit').addClass('hidden');
            }
        }

        function updateBlock(b)
        {
            var checks = [];
            if( b.$bl.find('.j-children').length ) {
                checks = b.$bl.find('.j-children > div:not(.hide) :checkbox:not(.j-reset):checked');
            } else {
                checks = b.$bl.find(':checkbox:not(.j-reset):checked');
            }
            var active = false;
            var value = o.lang.btn_reset;
            var value_plus = 0;
            switch(b.type) {
                case 'checks':
                case 'checks-child':
                    if( active = checks.length > 0 ) {
                        value = checks.first().parent().text();
                        value_plus = checks.length;
                    }
                    break;
                case 'range':
                case 'price':
                    var from = parseInt(b.$bl.find('.j-from').val());
                    var to = parseInt(b.$bl.find('.j-to').val());
                    var from_to = (from > 0 || to > 0);
                    var it = '';
                    if(b.type == 'price'){
                        var $a = b.$bl.find('.j-currency-select[data-id="'+b.$bl.find('[name="p[c]"]').val()+'"]');
                        $a.parent().addClass('active').siblings().removeClass('active');
                        it = ' '+$a.text();
                        var $sel = b.$bl.find('.j-currency-selected');
                        if($sel.length){
                            $sel.html(it);
                        }
                    }
                    if(from_to && from>=to) from = 0;
                    if( active = (checks.length > 0 || from_to) ) {
                        value = ( from_to ? (from>0&&to>0 ? from+' - '+to:(from>0 ? o.lang.range_from+'&nbsp;'+from : o.lang.range_to+'&nbsp;'+to)) + it
                            : checks.first().parent().text() );
                        value_plus = (checks.length + (from_to?1:0));
                    }
                    break;
                case 'metro':
                    active = checks.length > 0;
                    if (active) {
                        value = bff.declension(checks.length, o.lang.metro_declension.split(';'));
                        b.$bl.find('.j-metro-branch').each(function(){
                            var $br = $(this);
                            var l = $br.find(':checkbox:checked').length;
                            $br.find('.j-cnt').html(l ? l : '');
                        });
                    } else {
                        b.$bl.find('.j-cnt').html('');
                    }
                    break;
                case 'subcats':
                    return;
            }
            b.$bl.toggleClass('selected', active)
                .find('.j-value').html(value+' <i class="fa fa-plus-square extra'+(value_plus<=1 ? ' hidden' :'')+'"></i>');
            if(active){
                b.$bl.find('.j-reset').prop({checked:false,disabled:false}).closest('.checkbox').removeClass('disabled');
            }else{
                b.$bl.find('.j-reset').prop({checked:true,disabled:true}).closest('.checkbox').addClass('disabled');
            }
            if(intval(b.parent)) updateChildBlock(b);
        }

        function updateChildBlock(b)
        {
            var parents = b.$bl.find(':checkbox:not(.j-reset):checked');
            var parentId = b.id;
            var childBlock = getChildBlock(b.key);
            if(childBlock === false) return;
            var childPrefix = b.key+'-child-';
            var $childrenContainer = childBlock.$bl.find('.j-children');
            $childrenContainer.children().addClass('hide');
            var childVisible = false;
            parents.each(function(i){
                var $parentCheck = $(this);
                var parentValue = $parentCheck.val();
                var childKey = childPrefix+parentValue;
                if( $childrenContainer.find('#'+childKey).removeClass('hide').length > 0 ){ childVisible = true; return; }
                bff.ajax(bff.ajaxURL('bbs','dp-child'), {dp_id:parentId, dp_value:parentValue, format:'f-desktop'}, function(data){
                    if(data && data.success && $.isPlainObject(data)) {
                        data.key = childKey;
                        data.parent = {id:parentId, value:parentValue, title:$parentCheck.parent().text(), num:intval($parentCheck.data('num'))};
                        var html = buildChildrenHTML((childCache[childKey] = data));
                        if(html){ childBlock.$bl.removeClass('hide'); }
                        var after = null;
                        $childrenContainer.children().each(function(i){
                            if( data.parent.num > intval($(this).data('num')) ) {
                                after = $(this);
                            }
                        });
                        if(after) after.after(html);
                        else $childrenContainer.prepend(html);
                        updateBlock(childBlock);
                    }
                });
            });
            childBlock.$bl.toggleClass('hide', ! childVisible);
            updateBlock(childBlock);
        }

        function getChildBlock(parentKey)
        {
            var result = false;
            $filter.find('.j-filter-bl').each(function(){
                var b = $(this).metadata();
                if(b.key && b.key == (parentKey+'-child')){
                    b.$bl = $(this);
                    result = b;
                }
            });
            return result;
        }

        function buildChildrenHTML(data)
        {
            if( ! data || ! $.isArray(data.multi) ) return '';
            var html = '<div id="'+data.key+'" data-num="'+data.parent.num+'"><div class="f-catfilter__popup__subtitle rel"><span>'+data.parent.title+'</span> <hr/></div>';
            var total = data.multi.length;
            if( total > 0 ) {
                var name_child = childName+'['+data.df+']['+data.id+']';
                for (var k in data.multi) {
                    if( ! data.multi.hasOwnProperty(k)) continue;
                    var m = data.multi[k];
                    html += '<div class="checkbox"> <label> '+
                    '<input type="checkbox" name="'+name_child+'['+m.value+']" value="'+m.value+'" />'+m.name+
                    '</label> </div>';
                }
            }
            return html+'</div>';
        }

        function submit()
        {
            if(o.ajax) {
                onSubmit();
            } else {
                $form.find('.j-submit:first').trigger('click');
            }
        }

        function prepareSubmit($form)
        {
            $form.find('.j-vertical').remove();
            $.each($filter.find(':input'), function() {
                var $el = $(this);
                var name = $el.attr('name');
                if( ! name) return;
                $form.append('<input class="j-vertical hide" type="'+$el.attr('type')+'" name="'+name+'" value="'+$el.val()+'" '+($el.is(':checked') ? 'checked="checked"' : '')+' />');
            });
        }

        return {
            init:init,
            onPopstate: function(query) {
                if( ! inited ) return;
                $filter.deserialize(query, true);
                $filter.find('.j-filter-bl').each(function(){
                    var b = $(this).metadata(); b.$bl = $(this);
                    updateBlock(b);
                });
            },
            prepareSubmit: prepareSubmit
        };
    }());

    var phone = (function()
    {
        var inited = false, $filter, filterSelects = {}, $filterToggler, childCache = {}, childName = 'mdc',
            map = false, bffmap = false, mapClusterer = false, $mapItems = [], mapInfoWindow = false, mapMarkers = false;

        function init()
        {
            $filter = $('#j-f-phone'); if( ! $filter.length ) return; inited = true;
            $filter.find('.j-select').each(function(){
                var $select = $(this).find('select:first');
                var b = $select.metadata(); b.$select = $select; b.$val = $select.prev(); b.$block = $(this);
                    b.val = intval(b.$val.val());
                $select.on('change', function(){
                    var valNew = intval(this.value > 0 ? this.value : 0);
                    $select.find('option[value="-1"]').text(b.title+': '+this.options[this.selectedIndex].text)
                           .end().val(-1);
                    if( valNew != b.val ) {
                        b.$val.val( (b.val = valNew)).prop({disabled:b.val<=0});
                        if(b.parent) updateChildBlock(b, b.val);
                    }
                }).on('blur', function(){
                    if(this.selectedIndex!=0) $select.val(-1);
                    b.$val.val(b.val);
                });
                filterSelects[b.key] = b;
            }).end()
            .on('click', '.j-checkbox :checkbox', function(){
                if( $(this).is('[name^=mow]') ) $filter.find('[name^=mow]').not(this).prop({checked:false});
                onSubmit();
            })
            .on('click', '.j-submit', function(){
                onSubmit();
            })
            .on('click', '.j-cancel', function(){
                $filter.toggleClass('hide');
                $filterToggler.toggle();
            });
            $filterToggler = $filter.prev().find('a').on('click', function(){
                $filter.toggleClass('hide');
                $(this).toggle();
            });

            // list: cat types
            app.popup('f-type-phone', '#j-f-cattype-phone-dd', '#j-f-cattype-phone-dd-link', {onInit:function($p){
                var _this = this;
                var link = this.getLink();
                $p.on('click', '.j-f-cattype-phone', function(){
                    var typeData = $(this).metadata();
                    _this.hide();
                    if( link.data('current') != typeData.id ) {
                        link.find('.lnk').text(typeData.title);
                        //link.find('.cnt').text(typeData.items);
                        link.data('current', typeData.id);
                        toggleFiltersByCatType(typeData);
                        onCatType(typeData);
                    }
                });
            }});

            // map
            if( onListType().id == listTypes.map ) {
                mapInit();
            }
        }

        function toggleFiltersByCatType(typeData)
        {
            if( o.cattype_ex ) return;
            var is_seek = (intval(typeData.id) === 1);
            // filter: select
            for(var i in filterSelects) {
                var b = filterSelects[i];
                var disable = ( is_seek ? !b.seek : false);
                b.$select.prop('disabled', disable);
                if(o.ajax) b.$block.toggleClass('hide', disable);
            }
            // filter: checkboxes
            $filter.find('.j-checkbox').each(function(){
                var b = $(this);
                var disable = !( ! is_seek || b.hasClass('j-seek') );
                b.find(':checkbox').prop('disabled', disable);
                if(o.ajax) { if( disable ) b.hide(); else b.show(); }
            });
        }

        function updateChildBlock(parentBlock, parentValue)
        {
            var parentId = parentBlock.id;
            var childBlock = getChildBlock(parentBlock.key); if(childBlock === false) return;
            if( ! parentValue ) {
                childBlock.$val.val(0);
                childBlock.$block.addClass('hide');
                return;
            }
            var childKey = parentBlock.key+'-child-'+parentValue;
            var childUpdater = function(data){
                var html = ''; if($.isArray(data.multi)) for(var k in data.multi) { var m = data.multi[k]; html += '<option value="'+m.value+'">'+m.name+'</option>'; };
                childBlock.$select.find('option:gt(1)').remove();
                childBlock.$select.val(-1).find('option:first').text(childBlock.title+': '+o.lang.btn_reset);
                childBlock.$select.append(html);
                childBlock.$val.attr({name:childName+'['+data.df+']'});
                childBlock.$block.toggleClass('hide', parentValue<=0);
            };

            if( childCache.hasOwnProperty(childKey) ) {
                childUpdater( childCache[childKey] );
            } else {
                bff.ajax(bff.ajaxURL('bbs','dp-child'), {dp_id:parentId, dp_value:parentValue, format:'f-phone'}, function(data){
                    if(data && data.success) {
                        childUpdater( (childCache[childKey] = data) );
                    }
                });
            }
        }

        function getChildBlock(parentKey)
        {
            var $block = $('#j-f-phone-'+parentKey+'-child'); if( ! $block.length ) return false;
            var $select = $block.find('select:first'); if( ! $select.length ) return false;
            return $.extend( $select.metadata(), {$block:$block,$select:$select,$val:$select.prev()} );
        }

        function popupMarker(itemID, markerClick)
        {
            if ( ! bffmap || ! bffmap.isGoogle()) return;

            if (mapMarkers.hasOwnProperty(itemID)) {
                var m = mapMarkers[itemID];
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

        function mapInit()
        {
            var mapContainer = $('.j-search-map-phone'); if (!mapContainer.length) { return; }
            bffmap = app.map(mapContainer.get(0), o.defaultCoords, function(mmap) {
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
            }, {controls: 'view'});
        }

        return {
            init:init,
            onPopstate: function() {
                if( ! inited ) return;
                // filter: selects
                for(var i in filterSelects) {
                    if( ! filterSelects.hasOwnProperty(i) ) continue;
                    var b = filterSelects[i];
                    b.val = intval(b.$val.val());
                    var txt = b.title+': '+( b.val > 0 ? b.$select.find('option[value="'+b.val+'"]').text() : o.lang.btn_reset );
                    b.$select.val(-1).find('option:first').text(txt);
                    if(b.parent) updateChildBlock(b, b.val);
                }
                // list: cat types
                var f_cattype = onCatType();
                $('#j-f-cattype-phone-dd-link').data('current', f_cattype.id)
                    .find('.lnk').text(f_cattype.title).end()
                    .find('.cnt').text(f_cattype.items);
                toggleFiltersByCatType(f_cattype);
            },
            itemsToMap: function(items) {
                function baloonHTML(v) {
                    var tpl = $list.find('.j-tpl-map-balloon-phone');
                    if (tpl.length) { return bff.tmpl(tpl.html(), v); }
                    return '<div class="sr-page__map__balloon sr-page__map__balloon_mobile" style="padding-top:0;">' +
                        '<div class="sr-page__map__balloon_descr">\
                            <h6><a href="' + v.link + '">' + v.title + '</a></h6>' +
                        (v.price_on ? '<p class="sr-page__map__balloon_price"><strong>' + v.price + '</strong> <small>' + v.price_mod + '</small></p>' : '') +
                        '<p><small>' + v.cat_title + '</small></p>\
                            </div>' +
                        '</div>';
                }

                if (!bffmap) return;
                if (bffmap.isYandex()) {
                    // items: map
                    mapClusterer.removeAll();
                    map.geoObjects.remove(mapClusterer);
                    var itemsToCluster = [], j = 0;
                    for (var i in items) {
                        var v = items[i];
                        itemsToCluster[j++] = new ymaps.Placemark([parseFloat(v.lat), parseFloat(v.lon)], {
                            index: i,
                            clusterCaption: v.num,
                            balloonContentBody: baloonHTML(v)
                        }, {preset: 'twirl#blueIcon'});
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
                            position: new google.maps.LatLng(parseFloat(v.lat), parseFloat(v.lon))
                        });
                        marker.itemID = id;
                        mapMarkers[id] = {
                            position: marker.getPosition(),
                            ballon: '<div style="overflow: hidden;">'+baloonHTML(v)+'</div>'
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
                            bffmap.panTo([v.lat, v.lon], {delay: 10, duration: 200});
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
            o = bff.filter('bbs.search.settings', $.extend(o, options || {}));
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
    var $form = $('#j-f-form');
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
                bff.ajax(bff.ajaxURL('bbs','search&ev=catsList'), {parent:parentID, device:app.devices.desktop}, function(data){
                    if(data && data.success) {
                        st2cache[parentID] = data;
                        $st2.html(data.html);
                        if(fromStep1) $st2.add($st1).toggleClass('hide');
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
            
            if( data.subs > 0 && data.lvl < app.catsFilterLevel)  {
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
                bff.ajax(bff.ajaxURL('bbs','search&ev=catsList'), {parent:parentID, device:app.devices.phone}, function(data){
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
    /**
     * category-filter, index (phone)
     */
    (function(){
        var $st1 = $('#j-f-cat-phone-index-step1'); if( ! $st1.length ) return;
        var $st2 = $('#j-f-cat-phone-index-step2'), st2cache = {};
        function doFilter(type, $link)
        {
            var f = $link.metadata(); f['type'] = type; f['link'] = $link.attr('href');
            bff.redirect(f['link']);
        }
        function st2View(parentID, fromStep1)
        {
            if( st2cache.hasOwnProperty(parentID) ) {
                $st2.html(st2cache[parentID].html);
                if(fromStep1) $st2.add($st1).toggleClass('hide');
                $.scrollTo($st2, {offset: -10, duration: 400, axis: 'y'});
            } else {
                bff.ajax(bff.ajaxURL('bbs','index&ev=catsList'), {parent:parentID, device:app.devices.phone}, function(data){
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
            doFilter('cat', $(this));
            return false;
        });
        $st2.on('click', '.j-sub', function(){
            var data = $(this).metadata();
            if( data.subs > 0 )  {
                st2View(data.id, false);
            } else {
                doFilter('cat', $(this));
            }
            return false;
        });
    }());
    /**
     * quick search (desktop)
     */
    (function(){
        var QS = {i:false, cache:{}};
        QS.q = $('#j-f-query').on('change keyup input', $.debounce(function(){
            if( ! app.device(app.devices.desktop) ) return;
            if( ! QS.i ) {
                QS.i = true;
                QS.form = $('#j-f-form');
                QS.popup = app.popup('search-quick-dd', '#j-search-quick-dd');
                QS.list = QS.popup.getPopup().find('.j-search-quick-dd-list');
            }
            var query = $.trim(QS.q.val());
            if(query.length<3) QS.popup.hide();
            else {
                if( QS.cache.hasOwnProperty(query) ) {
                    QS.popup.hide();
                    QS.list.html(QS.cache[query]);
                    if( QS.cache[query]!='' ) QS.popup.show();
                    return;
                }
                bff.ajax(bff.ajaxURL('bbs','&ev=searchQuick'), QS.form.serialize(), function(data){
                    if(data && data.success && data.cnt>0) {
                        QS.list.html( (QS.cache[query] = data.items) );
                        QS.popup.show();
                    } else {
                        QS.popup.hide(); QS.cache[query] = '';
                    }
                });
            }
        }, 700));
    }());
});