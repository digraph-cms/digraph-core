Digraph.RichContent = {
    /**
     * @param {string} withSelection formatting string for when there is selected text
     * @param {string} withoutSelection string to insert if there is no selected text
     */
    insertEvent: function (withSelection, withoutSelection) {
        const e = new Event('rich-content-insert', { bubbles: true });
        e.insertWithSelection = withSelection;
        e.insertWithoutSelection = withoutSelection ?? withSelection;
        return e;
    },
    insertTagEvent: function (tag, parameters = {}) {
        const eq = parameters['_'] ? parameters['_'] : null;
        delete parameters['_'];
        var t = '[{0}'.format(tag);
        if (eq) t += '="{0}"'.format(eq);
        for (const i in parameters) {
            if (Object.hasOwnProperty.call(parameters, i)) {
                const v = parameters[i];
                t += ' {0}="{1}"'.format(i, v);
            }
        }
        return Digraph.RichContent.insertEvent(
            '{0}]{content}[/{1}]'.format(t, tag),
            '{0}/]'.format(t)
        )
    }
};

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
        this.contentWrapper = this.getDivByClass('rich-content-editor__content-editor__editor');
        this.contentWrapper.appendChild(this.textareaWrapper);
        this.mediaWrapper = this.getDivByClass('rich-content-editor__media-editor');
        // set up event listeners for toolbar
        this.toolbar = this.getDivByClass('rich-content-editor__toolbar');
        this.toolbar.addEventListener('click', (e) => { this.toolbarClick(e); });
        // reset toolbar on escape key
        this.toolbar.addEventListener('keydown', (e) => {
            if (e.key == 'Escape' || e.key == 'Esc') {
                this.toolbar.dispatchEvent(new Event('navigation-frame-reset', {
                    bubbles: false
                }));
            }
        });
        // insert event listeners
        this.wrapper.addEventListener('rich-content-insert', (e) => {
            var cm = this.getDivByClass('CodeMirror').CodeMirror;
            var content = cm.getSelection();
            if (content == '') {
                cm.replaceSelection(e.insertWithoutSelection);
            }else {
                cm.replaceSelection(e.insertWithSelection.replace('{content}',content));
            }
        });
    }
    toolbarClick(e) {
        var target = e.target;
        while (!target.classList.contains('toolbar__button')) {
            target = target.parentNode;
            if (target == this.toolbar) return;
        }
        if (target.dataset.command) {
            var cm = this.getDivByClass('CodeMirror').CodeMirror;
            cm.execCommand(target.dataset.command);
        }
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