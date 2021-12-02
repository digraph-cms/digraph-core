// disable built-in file drag/drop and copying, this happens in the blocks
// and files toolbox
addEventListener("trix-file-accept", function (event) {
    event.preventDefault();
});

window.addEventListener('DigraphMessage-block-insert', function (event) {
    const data = JSON.parse(atob(event.data));
    const editorID = data.editorID;
    delete data.editorID;
    const editor = document.getElementById(editorID + '-editor').editor;
    const attachment = new Trix.Attachment(data);
    console.log(attachment);
    editor.insertAttachment(attachment);
});

(()=>{
    Trix.config.attachments.content = { presentation: 'gallery' };
})();