var bffGalleryInline = (function(){

    function init(block, options)
    {
        var $block = $(block); if (!$block.length) return;

        var o = {duration: 800, iddle: 3.5, imageWidth: 565, descriptionWidth: 565, navigationWidth: 32,
                 imageClick: true};
        if(options) $.extend(o, options || {});

        var $image, $imageList, $description, $navigation, $play;
        var timer, timerInProgress = false;
        var current = 0, total = 0;

        $image = $('.image > ul', $block);
        $imageList = $('li', $image);
        if((total = $imageList.length) < 1) return;
        if( o.imageClick === true ) {
            $imageList.on('click',function(e){
                nothing(e);
                if( timerInProgress ) {
                    timerInProgress = false;
                    stopTimer();
                }
                moveTo( (current + 1 >= total) ? 0 : current + 1 );
            });
        }

        $description = $('.description ul', $block);
        
        $play = $('.play', $block);
        $play.on('click',playGallery);

        $navigation = $('.navigation ul', $block);
        $navigation.find('span').on('click',function(){
            moveTo( parseInt($(this).attr('rel'), 10) );
        });

        function moveTo(index)
        {
            current = index;
            changePlayImage();

            var params = {duration:o.duration, queue:false};
            $image.animate({ left: -(index * o.imageWidth), top:0, position: 'relative' }, params);
            $description.animate({ left: -(index * o.descriptionWidth), top:0, position: 'relative' }, params);
            $navigation.animate({ left: -(index * o.navigationWidth), top:0 }, params);
        }

        function playCallback()
        {
            if (!timerInProgress) {
                stopTimer();
            } else {
                current++;
                if (current >= total)
                {
                    timerInProgress = false;
                    moveTo(0);
                    stopTimer();
                } else {
                    moveTo(current);
                }
            }
        }

        function stopTimer()
        {
            if (!timer) return;
            clearInterval(timer);
            timer = false;
        }

        function changePlayImage()
        {
            if(timerInProgress)
                $play.css({backgroundImage: 'url(/img/i-gallery-pause.png)'});
            else
                $play.css({backgroundImage: 'url(/img/i-gallery-play.png)'});
        }

        function playGallery()
        {
            if (timerInProgress) {
                timerInProgress = false;
                changePlayImage();
            } else {
                timerInProgress = true;
                moveTo( (current + 1 >= total) ? 0 : current + 1 );
                timer = setInterval(playCallback, o.iddle * 1000);
            }
        }
    }
    
    return {
        init: function(block, options)
        {
            $(function(){ init(block, options); });
        }
    };

}());
