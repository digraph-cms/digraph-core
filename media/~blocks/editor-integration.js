(() => {
    document.addEventListener('DigraphDOMReady', (e) => {
        const buttons = e.target.getElementsByClassName('block-insert-button');
        for (let i = 0; i < buttons.length; i++) {
            const button = buttons[i];
            button.addEventListener('click', () => {
                Digraph.message('block-insert', button.dataset.block);
            });
        }
    });
})();