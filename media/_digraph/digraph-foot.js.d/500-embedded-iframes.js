$(() => {
    var $iframes = $('iframe.embedded-iframe');
    setInterval(()=>{
        $iframes = $('iframe.embedded-iframe');
        $iframes.each((i) => {
            var $iframe = $iframes.eq(i);
            //ensure iframe is wrapped
            if (!$iframe.parent().is('div.embedded-iframe')) {
                $iframe.wrap('<div class="embedded-iframe" />');
                $iframe.css('overflow','hidden');
                $iframe.attr('scrolling','no');
            }
            //add iframe-embedded class
            $iframe.contents().addClass('iframe-embedded');
        });
    },100);
    var updateFrames = () => {
        $iframes.each((i) => {
            var $iframe = $iframes.eq(i);
            var iframe = $iframe.get()[0];
            updateSingleFrame(iframe);
        });
    };
    var updateSingleFrame = (iframe) => {
        var $iframe = $(iframe);
        var $content = $iframe.contents();
        //set height if it isn't loading
        if (!$iframe.is('.loading')) {
            var height = $content.find('html').get(0).offsetHeight;
            $iframe.animate({
                height: height+'px'
            }),'fast';
        }
        //set up load/unload listeners
        iframe.onload = function(e) {
            $(iframe).parent('div.embedded-iframe').removeClass('loading');
            updateSingleFrame(iframe);
        };
        iframe.contentWindow.onunload = function(e) {
            $(iframe).parent('div.embedded-iframe').addClass('loading');
        };
    };
    updateFrames();
    $(window).on('resize',updateFrames);
});

if (window!=window.top) { $('body').addClass('iframe-embedded'); }