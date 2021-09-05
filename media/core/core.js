const Digraph = {};

document.addEventListener('DOMContentLoaded', (event) => {
    document.body.dispatchEvent(
        new Event('DigraphDOMReady', {
            bubbles: true,
            cancelable: false
        })
    );
});
