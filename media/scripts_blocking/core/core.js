const Digraph = {
    debounce: (fn, timeout = 400) => {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { fn.apply(this, args); }, timeout);
        };
    },
    getCookie: (type, name) => {
        const fullName = type + '/' + name;
        const allCookies = decodeURIComponent(document.cookie).split(';');
        for (let i = 0; i < allCookies.length; i++) {
            const el = allCookies[i].trim();
            if (el.indexOf(fullName + '=') == 0) {
                return JSON.parse(el.substring(fullName.length + 1, el.length)).value;
            }
        }
        return null;
    },
    uuid: (prefix) => {
        return (prefix ? prefix + '_' : '')
            + Digraph.config.uuidPattern.replaceAll('0', (matched, index, original) => {
                const r = parseInt(Math.random() * (Digraph.config.uuidChars.length - 1), 10);
                return Digraph.config.uuidChars.substring(r, r + 1);
            });
    },
    decodeHTML: (html) => {
        var txt = document.createElement('textarea');
        txt.innerHTML = html;
        return txt.value;
    },
    popover: () => {
        var popover = document.getElementById('_popover');
        if (!popover) {
            popover = document.createElement('div');
            popover.setAttribute('id', '_popover');
            var popover_content = document.createElement('div');
            popover_content.setAttribute('id', '_popover_content');
            popover_content.classList.add('navigation-frame');
            popover_content.classList.add('navigation-frame--stateless');
            popover_content.setAttribute('data-target', '_top');
            popover.appendChild(popover_content);
            var popover_closer = document.createElement('a');
            popover_closer.classList.add('_popover__closer');
            popover_closer.addEventListener('click', Digraph.hidePopover);
            popover.appendChild(popover_closer);
            document.body.appendChild(popover);
        }
        return popover;
    },
    showPopover: () => {
        var popover = Digraph.popover();
        popover.classList.add('_popover--visible');
        Digraph.popover.escape_listener = document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                Digraph.hidePopover();
            }
        });
    },
    hidePopover: () => {
        Digraph.popover().classList.remove('_popover--visible');
        document.removeEventListener(Digraph.popover.escape_listener);
    },
};

document.addEventListener('DOMContentLoaded', (e) => {
    document.body.classList.remove('no-js');
    document.body.classList.add('has-js');
    document.body.dispatchEvent(
        new Event('DigraphDOMReady', {
            bubbles: true,
            cancelable: false
        })
    );
});

/**
 * Tools for managing messaging back up to the topmost frame
 * This should maybe stay in core.js because it's small and needs to load fast
 */

Digraph.message = (name, data) => {
    var top = window;
    data = JSON.stringify(data);
    while (top.parent != top) {
        top = top.parent;
        top.postMessage(
            '[digraph-message]' + name + ':' + data,
            Digraph.config.origin
        );
    }
};

window.addEventListener('message', function (event) {
    if (typeof event.data != 'string') return;
    if (event.data.startsWith('[digraph-message]')) {
        if (event.origin == Digraph.config.origin) {
            var data = event.data.substr(17).split(':', 2);
            var messageEvent = new Event('DigraphMessage-' + data[0], {
                bubbles: true,
                cancelable: false
            });
            messageEvent.data = JSON.parse(data[1]);
            window.dispatchEvent(messageEvent);
        } else {
            console.warn('invalid message, origin is ' + event.origin + ', expected ' + Digraph.config.origin);
        }
    }
});

if (!String.prototype.format) {
    String.prototype.format = function () {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function (match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
}