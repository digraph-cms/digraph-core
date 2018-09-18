/**
 * Notifications are loaded from a JSON file generated via the notifications
 * helper. User-specific notifications should be delivered via the flash
 * functions of the notifications helper, not the main ones. Especially if they
 * are not related to the exact current page and may be cached.
 */
document.addEventListener("DOMContentLoaded", function(event) {
  if (container = document.getElementById('digraph-notifications')) {
    fetch('{{config.url.base}}user/notifications.json?u={{digraph_media_token}}')
      //turn into json
      .then(function(response) {
        return response.json();
      })
      //receive json and put it on the page
      .then(function(data) {
        for (var type in data) {
          if (data.hasOwnProperty(type)) {
            for (var i = 0; i < data[type].length; i++) {
              var n = document.createElement('div');
              console.log(n);
              n.classList.add('notification');
              n.classList.add('notification-' + type);
              n.append(data[type][i]);
              container.prepend(n);
            }
          }
        }
      });
  }
});