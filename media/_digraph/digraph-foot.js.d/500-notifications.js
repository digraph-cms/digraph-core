/**
 * Notifications are loaded from a JSON file generated via the notifications
 * helper. User-specific notifications should be delivered via the flash
 * functions of the notifications helper, not the main ones. Especially if they
 * are not related to the exact current page and may be cached.
 */
document.addEventListener("DOMContentLoaded", function (event) {
  if (container = document.getElementById('digraph-notifications')) {
    digraph.getJSON(
      '_user/notifications.json',
      function (data) {
        for (var type in data) {
          if (data.hasOwnProperty(type)) {
            for (var i = 0; i < data[type].length; i++) {
              container.innerHTML =
                '<div class="notification notification-' + type + '">' +
                data[type][i] +
                '</div>' +
                container.innerHTML;
            }
          }
        }
      },
      null,
      false
    );
  }
});