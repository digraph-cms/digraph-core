sceditor.formats.bbcode.get('url').quoteType = 1;
sceditor.formats.bbcode.get('email').quoteType = 1;

document.addEventListener('DigraphDOMReady', (e) => {
  const target = e.target;
  // set up sceditor objects
  Array.from(target.getElementsByClassName('safe-bbcode-input--nojs'))
    .forEach(ta => {
      ta.classList.remove('safe-bbcode-input--nojs');
      sceditor.create(
        ta,
        {
          format: 'bbcode',
          toolbar: 'bold,italic,strike,underline|bulletlist,orderedlist,quote|link,unlink,email,youtube|removeformat|source',
          emoticonsEnabled: false,
          resizeHeight: true,
          resizeWidth: false,
          enablePasteFiltering: true,
          autoUpdate: true,
          plugins: 'plaintext,autoyoutube',
          style: Digraph.config.SCEditorStyle
        }
      );
    });
  // set up field wrappers
  Array.from(target.getElementsByClassName('safe-bbcode-field--nojs'))
    .forEach(ta => {
      ta.classList.remove('safe-bbcode-field--nojs');
    });
});