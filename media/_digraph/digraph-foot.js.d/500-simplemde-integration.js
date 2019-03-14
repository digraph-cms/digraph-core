/**
 * Toggle SimpleMDE on content fields
 */
document.addEventListener('DOMContentLoaded', function(e) {
  var containers = document.querySelectorAll('.Form .Container.class-Content');
  for (var i = 0; i < containers.length; i++) {
    //add event listener to toggle SimpleMDE based on filter field
    var container = containers[i];
    var filterField = container.querySelectorAll('.Field.class-ContentFilter')[0];
    var textArea = container.querySelectorAll('.Field.class-ContentTextarea')[0];
    var simpleMDE = null;
    filterField.addEventListener('change', function(e) {
      var name = this.options[this.selectedIndex].innerHTML;
      if (name.match(/markdown/i)) {
        if (!simpleMDE) {
          //hide textarea and create SimpleMDE
          textArea.classList.add('hidden');
          simpleMDE = new SimpleMDE({
            element: textArea,
            autoDownloadFontAwesome: false,
            spellChecker: false
          });
        }
      } else {
        if (simpleMDE) {
          simpleMDE.toTextArea();
          simpleMDE = null;
          //unhide and dispatch change event to textarea so it will resize
          textArea.classList.remove('hidden');
          setTimeout(function() {
            textArea.style.cssText = 'height:' + textArea.scrollHeight + 'px';
          }, 0);
        }
      }
    });
    //dispatch event immediately
    var event = document.createEvent("HTMLEvents");
    event.initEvent("change", true, true);
    filterField.dispatchEvent(event);
  }
});
