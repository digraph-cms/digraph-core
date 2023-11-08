/**
 * When a secure content navigation frame is loaded and contains the special
 * SECURE_CONTENT_LOADED comment, remove the navigation-frame class so that
 * whatever was inside the secure content frame can now inherit its parents'
 * navigation frame rules.
 * 
 * Also cleans up some data attributes that are no longer needed.
 */
(() => {
  var reloaded = false;
  document.addEventListener('DigraphDOMReady', function (e) {
    const content = e.target;
    if (content.classList.contains('navigation-frame') && content.classList.contains('secure-content')) {
      if (content.innerHTML.indexOf('<!--SECURE_CONTENT_LOADED-->') > -1) {
        content.classList.remove('navigation-frame');
        content.classList.remove('navigation-frame--stateless');
        delete content.dataset.target;
        delete content.dataset.initialSource;
        delete content.dataset.currentUrl;
        // if we haven't already, reload all secure content frames
        if (reloaded) return;
        var frames = document.getElementsByClassName('secure-content');
        Array.from(frames).forEach(function (frame) {
          frame.reloadFrame();
        });
      }
    }
  });
})();