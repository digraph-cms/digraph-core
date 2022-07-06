(() => {
    unobfuscate(document.body);

    document.addEventListener('DigraphDOMReady', (e) => {
        unobfuscate(e.target);
    });

    function unobfuscate(target) {
        Array.from(target.getElementsByClassName('base64-obfuscated'))
            .forEach(element => {
                element.innerHTML = atob(element.firstChild.innerHTML);
                element.classList.remove('base64-obfuscated');
                element.classList.remove('base64-obfuscated--decoded');
                element.dispatchEvent(
                    new Event('DigraphDOMReady', {
                        bubbles: true,
                        cancelable: false
                    })
                );
            });
    }
})();