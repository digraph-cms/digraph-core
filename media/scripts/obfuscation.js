(() => {
    unobfuscate(document.body);

    document.addEventListener('DigraphDOMReady', (e) => {
        unobfuscate(e.target);
    });

    function unobfuscate(target) {
        Array.from(target.getElementsByClassName('obfuscated--base64'))
            .forEach(element => {
                const base64 = atob(element.firstChild.innerHTML);
                const bytes = new Uint8Array(base64.length);
                for (let i = 0; i < base64.length; i++) {
                    bytes[i] = base64.charCodeAt(i);
                }
                const decoder = new TextDecoder('utf-8');
                element.innerHTML = decoder.decode(bytes);
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