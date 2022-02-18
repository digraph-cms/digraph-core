/*
Make notifications dismissible
*/
(() => {
    var wrapper = document.getElementById('notifications');
    if (!wrapper) return;

    document.addEventListener('DigraphDOMReady', checkNotifications);
    window.addEventListener('load', checkNotifications);
    checkNotifications();

    // Look for new notifications
    function checkNotifications() {
        // double-check wrapper
        wrapper = document.getElementById('notifications');
        if (!wrapper) return;
        // update notifications
        var ns = wrapper.getElementsByClassName('notification');
        for (const i in ns) {
            if (Object.hasOwnProperty.call(ns, i)) {
                const n = ns[i];
                if (n.classList.contains('notification--dismissible')) continue;
                n.classList.add('notification--dismissible');
                n.innerHTML = "<div class='notification__content'>" + n.innerHTML + "</div>";
                const button = document.createElement('a');
                button.innerHTML = '';
                button.classList.add('notification__dismiss');
                n.appendChild(button);
            }
        }
    }

    // event listener to handle clicks on notification__dismiss objects
    wrapper.addEventListener('click', (e) => {
        var target = e.target;
        while (target != wrapper && !target.classList.contains('notification__dismiss')) {
            target = target.parentElement;
        }
        if (!target.classList.contains('notification__dismiss')) return;
        var n = target.parentElement;
        n.parentElement.removeChild(n);
    });
})();