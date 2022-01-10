Digraph.CodeMirror = {
    config: {
        theme: 'digraph',
        lineWrapping: true,
        extraKeys: {
            'Enter': 'newlineAndIndentContinueMarkdownList',
            'Ctrl-/': 'toggleComment'
        }
    },
    fromTextArea: function (textarea, config = {}) {
        return this.prep(CodeMirror.fromTextArea(
            textarea,
            Object.assign(this.config, config)
        ));
    },
    CodeMirror: function (t, config = {}) {
        return this.prep(CodeMirror(
            t,
            Object.assign(this.config, config)
        ));
    },
    prep: function (cm) {
        // currently does nothing, but could be used to add automatic event listeners
        return cm;
    }
};

document.addEventListener('DigraphDOMReady', (e) => {
    var divs = e.target.getElementsByTagName('div');
    for (let i = 0; i < divs.length; i++) {
        const div = divs[i];
        if (div.classList.contains('codemirror-input-wrapper') && !div.classList.contains('codemirror-input-wrapper--active')) {
            div.classList.add('codemirror-input-wrapper--active');
            if (div.dataset.codeMirrorConfig) {
                var config = JSON.parse(div.dataset.codeMirrorConfig);
            } else {
                var config = {};
            }
            if (div.dataset.codemirrorMode) {
                config.mode = div.dataset.codemirrorMode;
            }
            Digraph.CodeMirror.fromTextArea(
                div.getElementsByTagName('textarea')[0],
                config
            );
        }
    }
});