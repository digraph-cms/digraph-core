document.addEventListener('DigraphDOMReady', (e) => {
    var divs = e.target.getElementsByTagName('div');
    for (let i = 0; i < divs.length; i++) {
        const div = divs[i];
        if (div.classList.contains('rich-content-editor')) {
            new DigraphRichContentEditor(div);
        }
    }
});

class DigraphRichContentEditor {
    constructor(wrapper) {
        this.wrapper = wrapper;
        this.textareaWrapper = this.getDivByClass('codemirror-input-wrapper');
        this.contentWrapper = this.getDivByClass('rich-content-editor__content-editor');
        this.contentWrapper.appendChild(this.textareaWrapper);
        this.mediaWrapper = this.getDivByClass('rich-content-editor__media-editor');
    }
    getDivByClass(c) {
        var divs = this.wrapper.getElementsByTagName('div');
        for (let i = 0; i < divs.length; i++) {
            const div = divs[i];
            if (div.classList.contains(c)) {
                return div;
            }
        }
        return null;
    }
}