(() => {
    document.addEventListener('DigraphDOMReady', checkFooterPosition);
    window.addEventListener('resize', checkFooterPosition);
    window.addEventListener('load', checkFooterPosition);
    checkFooterPosition();

    function checkFooterPosition() {
        const footer = document.getElementById('footer');
        const offset = footer.classList.contains('footer--fixed') ? footer.offsetHeight : 0;
        if (window.innerHeight > document.body.offsetHeight + offset) {
            footer.classList.add('footer--fixed');
        } else {
            footer.classList.remove('footer--fixed');
        }
    }
})();