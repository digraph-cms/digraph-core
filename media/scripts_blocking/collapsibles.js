/**
 * Collapsible blocks
 */
document.addEventListener('DigraphDOMReady', (e) => {
    elements = e.target.getElementsByClassName('collapsible');
    for (const i in elements) {
        if (Object.hasOwnProperty.call(elements, i)) {
            const content = elements[i];
            content.classList.remove('collapsible');
            content.classList.add('collapsible__content');
            const wrapper = document.createElement('div');
            wrapper.classList.add('collapsible--js');
            content.parentNode.insertBefore(wrapper, content);
            const expandButton = document.createElement('a');
            expandButton.classList.add('collapsible__button');
            expandButton.classList.add('collapsible__expand');
            expandButton.innerHTML = content.dataset.collapsibleName
                ? 'Show ' + content.dataset.collapsibleName
                : 'Show section';
            wrapper.appendChild(expandButton);
            wrapper.appendChild(content);
            const collapseButton = document.createElement('a');
            collapseButton.classList.add('collapsible__button');
            collapseButton.classList.add('collapsible__collapse');
            collapseButton.innerHTML = content.dataset.collapsibleName
                ? 'Hide ' + content.dataset.collapsibleName
                : 'Hide section';
            wrapper.appendChild(collapseButton);
            // button controls
            expandButton.addEventListener('click', (e) => {
                wrapper.classList.add('collapsible--js--expanded');
                wrapper.dispatchEvent(new Event('DigraphLayoutUpdated', { bubbles: true }));
            });
            collapseButton.addEventListener('click', (e) => {
                wrapper.classList.remove('collapsible--js--expanded');
                wrapper.dispatchEvent(new Event('DigraphLayoutUpdated', { bubbles: true }));
            });
        }
    }
});