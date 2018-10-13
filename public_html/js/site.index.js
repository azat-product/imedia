var jSiteIndex = (function(){
    var initedMap = false;
    var o = {lang:{},infoClass:'index-map__info'};

    function initMap(regions)
    {
        var temp_array = regions.map(function(item){
            return item.items;
        });
        var highest_value = Math.max.apply(Math, temp_array);
        for(var i in regions){
            if( ! regions.hasOwnProperty(i)) continue;
            var $r = $('g[data-id='+ regions[i].id +']');
            if( ! $r.length) continue;
            $r.data('region', regions[i]);
            if(regions[i].numlevel == 2){
                var k = 225 - Math.round( 40 * regions[i].items/highest_value);
                $r.css({'fill': 'rgb('+k+', '+k+', '+k+')'});
            }
        }
        $('.index-map g').on('mouseover',function (e) {
            var region_data = $(this).data('region');
            $('<div class="' + o.infoClass + '"><div>'+
                region_data.title + '<br>' +
                o.lang.items + ' ' + region_data.items.toLocaleString("en-UK") +
                '</div></div>'
            )
            .appendTo('body');
        })
        .on('mouseleave',function () {
            $('.'+o.infoClass).remove();
        })
        .on('mousemove',function(e) {
            var mouseX = e.pageX, //X coordinates of mouse
                mouseY = e.pageY; //Y coordinates of mouse
            var $mapInfo = $('.'+o.infoClass);
            $mapInfo.css({
                top: mouseY+30,
                left: mouseX - ($mapInfo.width()/2)
            });
        }).on('click',function(e){
            var region_data = $(this).data('region');
            window.location.href = region_data.l;
        });
    }

    return {
        initMap: function(options, data)
        {
            o = $.extend(o, options || {});
            if(initedMap || !data.hasOwnProperty('regions')) return; initedMap = true;
            $(function(){ initMap(data.regions); });
        }
    };
})();