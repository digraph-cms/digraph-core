(() => {
    document.addEventListener('DigraphDOMReady', (e) => {
        const buttons = e.target.getElementsByClassName('attachment-insert-button');
        for (let i = 0; i < buttons.length; i++) {
            const button = buttons[i];
            button.addEventListener('click', () => {
                var top = window;
                while (top.parent != top) {
                    top = top.parent;
                }
                top.postMessage(
                    '[trix-attachment]' + button.dataset.attachment,
                    '*'
                );
            });
        }
    });
})();