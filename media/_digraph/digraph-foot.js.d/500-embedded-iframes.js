$(() => {
    var $iframes = $('iframe.embedded-iframe');
    setInterval(()=>{
        $iframes = $('iframe.embedded-iframe');
        $iframes.each((i) => {
            var $iframe = $iframes.eq(i);
            var iframe = $iframe.get()[0];
            var $contents = $iframe.contents();
            var $body = $contents.find('body');
            //ensure iframe is wrapped
            if (!$iframe.parent().is('div.embedded-iframe')) {
                $iframe.wrap('<div class="embedded-iframe loading" />');
                $iframe.css('overflow','hidden');
                $iframe.attr('scrolling','no');
            }
            //add iframe-embedded class
            $contents.addClass('iframe-embedded');
            //set up load/unload listeners
            iframe.onload = function(e) {
                $(iframe).parent('div.embedded-iframe').addClass('loaded').removeClass('loading');
                updateSingleFrame(iframe);
            };
            iframe.contentWindow.onunload = function(e) {
                $(iframe).parent('div.embedded-iframe').removeClass('loaded').addClass('loading');
            };
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
        var $contents = $iframe.contents();
        //set height if it isn't loading
        if (!$iframe.is('.loading') || ($iframe.is('.loading') && !$iframe.is('.loaded'))) {
            var height = $contents.find('html').get(0).offsetHeight;
            if (height && height != $contents.find('html').attr('data-ifheight')) {
                $contents.find('html').attr('data-ifheight',height);
                $iframe.animate({
                    height: height+'px'
                }),'fast';
            }
        }
    };
    updateFrames();
    $(window).on('resize',updateFrames);
    setInterval(updateFrames,100);
});

if (window!=window.top) { $('body').addClass('iframe-embedded'); }