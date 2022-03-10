/*
Make autosized frames match their content height
Only works with local content, but has no dependencies
*/
(() => {
    // update menus to prepare them for automatic overflowed mode
    document.addEventListener('DigraphDOMReady', updateAutosizedFrames);
    window.addEventListener('load', updateAutosizedFrames);
    window.addEventListener('resize', updateAutosizedFrames);
    updateAutosizedFrames();
    function updateAutosizedFrames(e) {
        Array.from(document.getElementsByClassName('autosized-frame'))
            .forEach(frame => {
                frame.style.marginBottom = frame.style.height;
                frame.style.height = 0;
                var height = frame.contentDocument.body.scrollHeight;
                if (height) {
                    frame.style.overflow = 'hidden';
                    frame.setAttribute('scrolling', 'no');
                    frame.style.height = height + 'px';
                    frame.style.marginBottom = null;
                }
            });
    }
})();