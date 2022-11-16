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
          toolbar: 'bold,italic,strike,underline|bulletlist,orderedlist,quote|email,link,unlink|youtube,date,time|removeformat|source',
          emoticonsEnabled: false,
          resizeHeight: true,
          enablePasteFiltering: true,
          autoUpdate: true,
          plugins: 'plaintext,autosave,autoyoutube',
        }
      );
    });
  // set up field wrappers
  Array.from(target.getElementsByClassName('safe-bbcode-field--nojs'))
    .forEach(ta => {
      ta.classList.remove('safe-bbcode-field--nojs');
    });
});