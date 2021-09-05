/**
 * Initialize editors on DigraphDOMReady events
 */
document.addEventListener('DigraphDOMReady', (event) => {
    const srcs = event.target.getElementsByClassName('editorjs');
    for (let i = 0; i < srcs.length; i++) {
        new DigraphEditor(srcs[i]);
    }
});

/**
 * Simple wrapper to hold a textarea, create a new EditorJS, and tie their
 * content together so that the editor initializes from JSON in the textarea,
 * and that JSON updates as the editor changes.
 */
DigraphEditor = function (textarea) {
    this.textarea = textarea;
    this.textarea.classList.add('editor-active');
    this.container = document.createElement("div");
    this.textarea.parentNode.insertBefore(this.container, this.textarea);
    this.editor = new EditorJS({
        holder: this.container,
        tools: Digraph.editorTools,
        data: this.textarea.value ? JSON.parse(this.textarea.value) : {},
        onChange: () => {
            this.editor.save().then(output => {
                this.textarea.value = JSON.stringify(output);
            });
        }
    });
}
