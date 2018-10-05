/**
 * Actions are loaded from a JSON file, by scanning the page for objects with
 * the CSS class digraph-actionbar
 *
 * Actionbars must contain an attribute data-id that indicates the ID/slug of
 * the object to retrieve actions for. Each actionbar on the page will generate
 * its own fetch of the JSON file, but they are cached by default so it
 * shouldn't be a performance problem
 */
document.addEventListener("DOMContentLoaded", function(event) {
  var actionbars = document.getElementsByClassName('digraph-actionbar');
  for (var i = 0; i < actionbars.length; i++) {
    let actionbar = actionbars[i];
    fetch('{{config.url.base}}user/actionbar.json?id=' + actionbar.getAttribute('data-id') + '&sid=' + digraph.user.sid)
      //turn into json
      .then(function(response) {
        return response.json();
      })
      //receive json and put it on the page
      .then(function(data) {
        if (data.length > 0) {
          actionbar.innerHTML += data.join(' ');
          actionbar.classList.remove('inactive');
        }
      });
  }
});