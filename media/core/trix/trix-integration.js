(function () {
    // disable built-in file drag/drop and copying, this happens in the blocks
    // and files toolbox
    addEventListener("trix-file-accept", function (event) {
        event.preventDefault();
    });
    window.addEventListener('message', function (event) {
        if (event.isTrusted && event.data.startsWith('[trix-attachment]')) {
            const data = JSON.parse(atob(event.data.substr(17)));
            const editorID = data.editorID;
            delete data.editorID;
            delete data.name;
            const editor = document.getElementById(editorID + '-editor').editor;
            const attachment = new Trix.Attachment(data);
            editor.insertAttachment(attachment);
        }
    });
})();