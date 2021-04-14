/*
Add iframe-embedded class to body, as quickly as possible, if
we are embedded in an iframe
 */
(() => {
    if (window != window.top) {
        var addBodyClass = function () {
            var b = document.getElementsByTagName('body')[0];
            if (b) {
                b.classList.add('iframe-embedded');
                b.classList.add('loading');
                $(document).ready(() => {
                    $('body').removeClass('loading');
                });
            } else {
                setTimeout(addBodyClass, 100);
            }
        }
        addBodyClass();
        window.onbeforeunload = function () {
            $('body').addClass('loading');
        }
    }
})();

$(() => {
    // locate all the .embedded-iframe objects, update every second
    var $iframes = $('iframe.embedded-iframe');
    var setupFrames = () => {
        $iframes = $('iframe.embedded-iframe');
        $iframes.each((i) => {
            var $iframe = $iframes.eq(i);
            //do initial setup of iframe if it isn't wrapped
            if (!$iframe.parent().is('div.embedded-iframe')) {
                // wrap iframe and add class
                $iframe.wrap('<div class="embedded-iframe" />');
                $iframe.addClass('resizer-activated');
                // set up iframe resizer
                iFrameResize({
                    log: false
                }, 'iframe.embedded-iframe');
            }
            //check/set loading status
            var b = $iframe[0].contentDocument.getElementsByTagName('body')[0];
            if (!b || b.classList.contains('loading') || !b.classList.contains('iframe-embedded')) {
                $iframe.parent().addClass('loading');
            } else {
                $iframe.parent().removeClass('loading');
            }
        });
    };
    setInterval(setupFrames, 100);
    setupFrames();
});
