$(() => {
    // locate all the .embedded-iframe objects, update every second
    var $iframes = $('iframe.embedded-iframe');
    var setupFrames = () => {
        $iframes = $('iframe.embedded-iframe');
        $iframes.each((i) => {
            var $iframe = $iframes.eq(i);
            //ensure iframe is wrapped
            if (!$iframe.parent().is('div.embedded-iframe')) {
                $iframe.wrap('<div class="embedded-iframe" />');
                $iframe.addClass('resizer-activated');
                iFrameResize({
                    log: true
                },'iframe.embedded-iframe');
            }
            //ensure body is classed/embedded properly
            $iframe.contents().addClass('iframe-embedded');
        });
    };
    setInterval(setupFrames, 100);
    setupFrames();
});

if (window != window.top) {
    $('body').addClass('iframe-embedded');
}