(() => {
    unobfuscate(document.body);

    document.addEventListener('DigraphDOMReady', (e) => {
        unobfuscate(e.target);
    });

    function unobfuscate(target) {
        Array.from(target.getElementsByClassName('obfuscated--base64'))
            .forEach(element => {
                element.innerHTML = atob(element.firstChild.innerHTML);
                element.classList.remove('obfuscated--base64');
                element.classList.add('obfuscated--decoded');
                element.dispatchEvent(
                    new Event('DigraphDOMReady', {
                        bubbles: true,
                        cancelable: false
                    })
                );
            });
    }
})();