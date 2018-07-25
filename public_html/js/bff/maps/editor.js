/**
 * Класс для работы с точкой на карте Yandex, Google
 * @author Tamaranga | tamaranga.com
 * @version 0.67
 * @modified 8.nov.2016
 * @return {Object}
 */

function bffmapEditorYandex()
{
    var o, map, $coords = [], marker, markerIsDraggable;
    
    function init(options, center)
    {
        setOptions(options);

        // центрируем, исходя из указанных координат
        if (center) {
            var coords = [];
            if($coords.length == 1) {
                coords = $coords.val().split(',', 2);
            } else if($coords.length == 2) {
                coords = [$coords[0].val(), $coords[1].val()];
            }
            map.setCenter(coords);
        }

        // создаем маркер
        marker = new ymaps.Placemark(map.getCenter(), o.markerProperties, o.markerOptions);
        map.geoObjects.add(marker);
        if( markerIsDraggable ) {
             // заверешение перетаскивания маркера
             marker.events.add('dragend', function(e) {
                updateMarker( marker.geometry.getCoordinates() );
             });
            // обновляем позицию маркера по клику
            map.events.add('click', function (e) {
                updateMarker( e.get( (o.version == '2.1' ? 'coords' : 'coordPosition') ) );
            });
        }

        // SearchControl
        if(o.searchInit!==false) {
            var oMapSearchCtrl = new ymaps.control.SearchControl(o.searchOptions);
            oMapSearchCtrl.events.add('resultselect', function(e) {
                oMapSearchCtrl.getResult(e.get((o.version == '2.1' ? 'index' : 'resultIndex'))).then(function (res) { // GeoObject
                    var coords = res.geometry.getCoordinates();
                    updateMarker( coords, false );
                    map.panTo( coords );
                    updateAddress( res.properties.get('name') );
                });
            });
            map.controls.add(oMapSearchCtrl, {left:'40px', top:'10px'});
        }
    }

    function setOptions(options)
    {
        o = $.extend({
            map: false, //element (ymaps.Map)
            coords: [], //element (для хранения координат): $latlong, [$lat,$long], []
            address: false, //element (для хранения адреса)
            onAddress: false, // обработчик смены адреса
            addressKind: 'normal', // 'house','street','metro','district','locality' или false
            markerProperties: {}, // свойства маркера
            markerOptions: {draggable: true, hasBalloon: false}, // опции маркера
            searchInit: false, // инициализировать SearchControl
            searchOptions: {}, // настройки SearchControl
            updateAddressIgnoreClass: '', // изменять строку адреса если не указан класс
            version: '2.0', //версия api яндекс карт
            coordsPrecision: 6 //точность координат
        }, options || {});

        if( o.searchInit!==false ) {
            switch(o.version)
            {
                case '2.1':
                    o.searchOptions = $.extend(true, {options:{
                        noPlacemark: true, noCentering:true, noPopup: false,
                        resultsPerPage: 3}}, o.searchOptions);
                    break;
                case'2.0':
                default:
                    o.searchOptions = $.extend({width: 500,
                        noPlacemark: true, noCentering:true, noPopup: true,
                        resultsPerPage: 3}, o.searchOptions);
                    break;
            }
        }

        if(options.hasOwnProperty('map')) map = o.map;
        if(options.hasOwnProperty('coords')) $coords = o.coords;

        markerIsDraggable = ( o.markerOptions['draggable'] === true );
    }

    function updateMarker(coords, updateAddr, panToAddr)
    {
        coords = bff.map.coordsFromString(coords);

        marker.geometry.setCoordinates(coords);

        if( $coords.length == 1 ) {
            $coords.val( [ coords[0].toPrecision(o.coordsPrecision),
                           coords[1].toPrecision(o.coordsPrecision)
                           ].join(',') );
        } else if($coords.length == 2) {
            $coords[0].val( coords[0].toPrecision(o.coordsPrecision) );
            $coords[1].val( coords[1].toPrecision(o.coordsPrecision) );
        }

        if( updateAddr!==false ) {
            updateAddress( coords );
        }
        if( (panToAddr || panToAddr === 0) && $(map.container.getElement()).parent().is(':visible')) {
            map.panTo(coords, {duration: panToAddr});
        }

        if(o.onUpdate) o.onUpdate(marker);
    }
    
    function updateAddress(a)
    {
        if( (o.address === false && o.onAddress === false) ||
            ( o.updateAddressIgnoreClass.length > 0 && o.address.hasClass(o.updateAddressIgnoreClass) ) )
            return;


        try
        {
            if( $.isArray(a) ) // координаты
            {
                var coords = a;
                var opts = {};
                var _addr = '';

                switch(o.addressKind) {
                    case false: // full address
                    {
                        ymaps.geocode(coords, opts).then(function (res) {
                            var names = [];
                            res.geoObjects.each(function(obj) {
                                names.push( obj.properties.get('name') );
                            });
                            _addr = names.reverse().join(', ');
                            if(o.address) { o.address.val(_addr); }
                            if(o.onAddress) { o.onAddress(_addr); }

                        });
                    } break;
                    case 'normal': { // city + street + house
                        opts['kind']  = 'house';
                        ymaps.geocode(coords, opts).then(function (res) {
                            _addr = res.geoObjects.get(0).properties.get('name');
                            coords = res.geoObjects.get(0).geometry.getCoordinates();
                            ymaps.geocode(coords, {kind: 'locality'}).then(function (res) {
                                try{
                                    _addr = res.geoObjects.get(0).properties.get('name')+', '+_addr;
                                    if(o.address) { o.address.val(_addr); }
                                    if(o.onAddress) { o.onAddress(_addr); }
                                } catch(e) {}
                            });
                        });
                    } break;
                    default: { // kind = 'house','street','metro','district','locality'
                        opts['kind']  = o.addressKind;
                        ymaps.geocode(coords, opts).then(function (res) {
                            var names = [];
                            res.geoObjects.each(function(obj) {
                                names.push(obj.properties.get('name'));
                            });
                            try{
                                _addr = res.geoObjects.get(0).properties.get('name');
                                if(o.address) { o.address.val(_addr); }
                                if(o.onAddress) { o.onAddress(_addr); }
                            } catch(e) {}
                        });
                    } break;
                }
            } else { // строка
                if(o.address) { o.address.val(new String(a)); }
                if(o.onAddress) { o.onAddress(new String(a)); }
            }

        } catch(e){
            
        }
    }
    
    function fireUpdate()
    {
        updateMarker( marker.geometry.getCoordinates() );
    }

    function centerByMarker()
    {
        map.setCenter( marker.geometry.getCoordinates() );
    }
    
    return {
        init: init,
        setOptions: setOptions,
        fireUpdate: fireUpdate,
        updateMarker: updateMarker,
        centerByMarker: centerByMarker,
        search: function(addr, panDuration, callback)
        {
            var geo = ymaps.geocode(addr, {results: 1});
            geo.then(function(res){
                if(res.geoObjects.getLength()) {
                    var obj = res.geoObjects.get(0);
                    var coords = obj.geometry.getCoordinates();
                    updateMarker(coords, false, panDuration);
                    if(callback) callback(obj, coords);
                }
            });
        }
    };
}

function bffmapEditorGoogle()
{
    var o, map, $coords = [], marker, markerIsDraggable;

    function init(options, center)
    {
        setOptions(options);

        // центрируем, исходя из указанных координат
        if (center) {
            var coords = [];
            if ($coords.length == 1) {
                coords = $coords.val().split(',', 2);
            } else if ($coords.length == 2) {
                coords = [$coords[0].val(), $coords[1].val()];
            }
            map.setCenter({lat:coords[0], lng:coords[1]});
        }

        // создаем маркер
        o.markerOptions = $.extend(o.markerOptions, {position: map.getCenter(), map: map});
        marker = new google.maps.Marker(o.markerOptions);

        if (markerIsDraggable) {
             // заверешение перетаскивания маркера
             google.maps.event.addListener(marker, 'dragend', function(e) {
                updateMarker( marker.getPosition() );
             });
            // обновляем позицию маркера по клику
            google.maps.event.addListener(map, 'click', function(e){
                updateMarker(e.latLng);
            });
        }
    }

    function setOptions(options)
    {
        o = $.extend({
            map: false, // element (google.maps.Map)
            coords: [], // element (для хранения координат): $latlong, [$lat,$long], []
            address: false, //element (для хранения адреса)
            onAddress: false, // обработчик смены адреса
            addressKind: 'normal', // 'region'='country+city', 'house'='street+house', 'normal'='city+street+house', false='full address'
            markerOptions: {draggable: true, animation: google.maps.Animation.DROP}, // опции маркера
            searchBox: false, // инициализировать SearchBox: 'id' input блока
            updateAddressIgnoreClass: '' // изменять строку адреса если не указан класс
        }, options || {});

        if(options.hasOwnProperty('map')) map = o.map;
        if(options.hasOwnProperty('coords')) $coords = o.coords;

        if (o.searchBox!==false) {
            var inputSearch = document.getElementById(o.searchBox);
            map.controls[google.maps.ControlPosition.TOP_LEFT].push(inputSearch);
            inputSearch.style.display = 'block';
            var searchBox = new google.maps.places.SearchBox(inputSearch);

            google.maps.event.addListener(searchBox, 'places_changed', function(e) {
                nothing(e);
                var places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }
                var place = places[0];
                updateMarker(place.geometry.location, false, true);
                updateAddress(place.formatted_address);
            });

            google.maps.event.addListener(map, 'bounds_changed', function() {
                var bounds = map.getBounds();
                searchBox.setBounds(bounds);
            });
        }

        markerIsDraggable = (o.markerOptions['draggable'] === true);
    }

    function updateMarker(coords, updateAddr, panToAddr)
    {
        if ( ! (coords instanceof google.maps.LatLng)) {
            coords = bff.map.coordsFromString(coords);
        }

        marker.setPosition(coords);

        var coordsString = coords.toUrlValue();
        if( $coords.length == 1 ) {
            $coords.val(coordsString);
        } else if($coords.length == 2) {
            coordsString = coordsString.split(',', 2);
            $coords[0].val( coordsString[0] );
            $coords[1].val( coordsString[1] );
        }

        if( updateAddr!==false ) {
            updateAddress(coords);
        }
        if( (panToAddr || panToAddr === 0) && $(map.getDiv()).parent().is(':visible')) {
            map.panTo(coords);
        }

        if(o.onUpdate) o.onUpdate(marker);
    }

    function updateAddress(a)
    {
        if( (o.address === false && o.onAddress === false) ||
            ( o.updateAddressIgnoreClass.length > 0 && o.address.hasClass(o.updateAddressIgnoreClass) ) )
            return;

        try
        {
            if(a instanceof google.maps.LatLng) // coords
            {
                var _addr = [];
                switch(o.addressKind) {
                    case 'region': _addr = ['country','city']; break;
                    case 'normal': _addr = ['city','street','house']; break;
                    case 'house':  _addr = ['street','house']; break;
                }
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({'latLng': a}, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK && results[0]) {
                        var ex = explainResult(results[0]);
                        var res = [];
                        if (_addr.length > 0) {
                            for (var i in _addr) {
                                var k = _addr[i];
                                if (ex.hasOwnProperty(k) && ex[k].length) {
                                    res.push(ex[k]);
                                }
                            }
                        } else {
                            res = [results[0].formatted_address];
                        }
                        if(o.address) { o.address.val(res.join(', ')); }
                        if(o.onAddress) { o.onAddress(res.join(', ')); }
                    }
                });
            } else { // string
                if(o.address) { o.address.val( new String(a) ) }
                if(o.onAddress) { o.onAddress(new String(a)); }
            }

        } catch(e){

        }
    }

    function explainResult(res)
    {
        var data = {country:'',state:'',city:'',street:'',house:'',establishment:'',postal_code:''};
        for (var i = 0; i < res.address_components.length; i++) {
            var addr = res.address_components[i];
            switch (addr.types[0]) {
                case 'country': data.country = addr.long_name; break;
                case 'administrative_area_level_1': data.state = addr.long_name; break;
                case 'locality': data.city = addr.long_name; break;
                case 'route': data.street = addr.long_name; break;
                case 'street_number': data.house = addr.long_name; break;
                case 'establishment': data.establishment = addr.long_name; break;
                case 'postal_code': data.postal_code = addr.short_name; break;
            }
        }
        return data;
    }

    function fireUpdate()
    {
        updateMarker(marker.getPosition());
    }

    function centerByMarker()
    {
        map.setCenter(marker.getPosition());
    }

    return {
        init: init,
        setOptions: setOptions,
        fireUpdate: fireUpdate,
        updateMarker: updateMarker,
        centerByMarker: centerByMarker,
        search: function(addr, panDuration, callback)
        {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': addr}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results[0]) {
                    var obj = results[0];
                    var coords = obj.geometry.location;
                    updateMarker(coords, false, panDuration);
                    if(callback) callback(obj, coords);
                }
            });
        }
    };
}