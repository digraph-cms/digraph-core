/*
Add iframe-embedded class to body, as quickly as possible, if
we are embedded in an iframe
 */
(() => {
    if (window != window.top) {
        var addBodyClass = function () {
            setTimeout(() => {
                document.addEventListener(
                    'load',
                    function () {
                        document.body.classList.remove('loading');
                    }
                );
            }, 200);
            if (document.body) {
                document.body.classList.add('iframe-embedded');
            } else {
                setTimeout(addBodyClass, 50);
            }
        }
        addBodyClass();
        window.onbeforeunload = function () {
            document.body.classList.add('loading');
        }
    }
})();

/*
Set up embedded frames
*/
document.addEventListener('DigraphDOMReady', (e) => {
    const es = e.target.getElementsByTagName('iframe');
    for (let i = 0; i < es.length; i++) {
        const iframe = es[i];
        const wrapper = document.createElement('div');
        wrapper.classList.add('embedded-iframe-wrapper');
        if (iframe.classList.contains('embedded-iframe')) {
            iframe.parentNode.insertBefore(wrapper, iframe);
            iframe.classList.remove('embedded-iframe');
            wrapper.appendChild(iframe);
            iFrameResize({}, iframe);
        }
    }
});

/*
Set up interval timer for changing loading status of wrappers
*/
(() => {
    var checkLoadingStatus = () => {
        const wrappers = document.getElementsByClassName('embedded-iframe-wrapper');
        for (let i = 0; i < wrappers.length; i++) {
            const wrapper = wrappers[i];
            const iframe = wrapper.getElementsByTagName('iframe')[0];
            if (iframe && iframe.contentDocument.body && !iframe.contentDocument.body.classList.contains('loading')) {
                wrapper.classList.remove('loading');
            } else {
                wrapper.classList.add('loading');
            }
        }
    };
    setInterval(checkLoadingStatus, 200);
})();