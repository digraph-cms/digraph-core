$(() => {
    var $iframes = $('iframe.embedded-iframe');
    setInterval(() => {
        $iframes = $('iframe.embedded-iframe');
        $iframes.each((i) => {
            var $iframe = $iframes.eq(i);
            var iframe = $iframe.get()[0];
            var $contents = $iframe.contents();
            var $body = $contents.find('body');
            //ensure iframe is wrapped
            if (!$iframe.parent().is('div.embedded-iframe')) {
                $iframe.wrap('<div class="embedded-iframe loading" />');
            }
            //ensure body is embedded properly
            if (!$contents.is('.iframe-embedded')) {
                //add iframe-embedded class
                $contents.addClass('iframe-embedded');
                //set up load/unload listeners
                iframe.contentWindow.onload = function (e) {
                    $(iframe).parent('div.embedded-iframe').addClass('loaded').removeClass('loading');
                    updateSingleFrame(iframe);
                };
                iframe.contentWindow.onbeforeunload = function (e) {
                    $(iframe).parent('div.embedded-iframe').removeClass('loaded').addClass('loading');
                };
            }
        });
    }, 300);
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
        if (!$iframe.is('.loading') || $iframe.is('.loaded')) {
            //only continue if html tag exists
            if (!$contents.find('html').get(0)) {
                return;
            }
            //only continue if body has iframe-embedded class
            if (!$contents.find('body').is('.iframe-embedded')) {
                return;
            }
            //get height from html tag
            var height = $contents.find('html').get(0).offsetHeight;
            //set height
            if ((height > 10 || !$iframe.is('.resized')) && height != $iframe.height()) {
                $iframe.addClass('resized');
                $iframe.height(height);
            }
        }
    };
    updateFrames();
    $(window).on('resize', updateFrames);
    setInterval(updateFrames, 250);
});

if (window != window.top) {
    $('body').addClass('iframe-embedded');
}