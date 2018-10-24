document.addEventListener("DOMContentLoaded", function(event) {
  /* published field */
  var publishedFields = document.querySelectorAll('.Form .Container.class-Published');
  for (var i = 0; i < publishedFields.length; i++) {
    var container = publishedFields[i];
    var select = container.querySelectorAll('select')[0];
    var datetimes = [].slice.call(container.querySelectorAll('.Container.class-DateAndTime'));
    select.addEventListener('change', function(e) {
      if (select.value == 'date') {
        datetimes.map(function(e) {
          e.classList.remove('hidden');
        });
      } else {
        datetimes.map(function(e) {
          e.classList.add('hidden');
        });
      }
    });
    var event = document.createEvent("HTMLEvents");
    event.initEvent("change", true, true);
    select.dispatchEvent(event);
  }
});