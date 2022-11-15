document.addEventListener('DigraphDOMReady', (e) => {
  const target = e.target;
  Array.from(target.getElementsByClassName('safe-bbcode-input--nojs'))
    .forEach(ta => {
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
});