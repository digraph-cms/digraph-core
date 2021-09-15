const Digraph = {};

document.addEventListener('DOMContentLoaded', (event) => {
    document.body.dispatchEvent(
        new Event('DigraphDOMReady', {
            bubbles: true,
            cancelable: false
        })
    );
});

document.addEventListener('click', (event) => {
    if (event.target.tagName == 'A') {
        // first see if target has a data-target attribute, use that
        var parent = event.target.dataset.target;
        if (!parent || !(parent = document.getElementById(parent))) {
            // otherwise try to find a parent navigation-frame
            parent = event.target.parentElement;
            while (parent) {
                if (parent.tagName == 'BODY') {
                    // no navigation-frame parent found
                    return;
                }
                if (parent.classList.contains('navigation-frame') && parent.getAttribute('id')) {
                    // nearest navigation-frame found
                    break;
                }
                parent = parent.parentElement;
            }
        }
        // determine target
        var target = event.target.dataset.target ?? parent.dataset.target ?? parent.getAttribute('id');
        if (target == '_frame') {
            target = parent.getAttribute('id');
        }
        // parent and target found
        if (parent && target && target != '_top') {
            Digraph.state.pushState(event.target.getAttribute('href'), parent);
            event.preventDefault();
        }
    }
});

window.addEventListener('popstate', (e) => {
    if (e.state.url && e.state.frame) {
        Digraph.state.loadState(e.state.url, document.getElementById(e.state.frame));
    };
});

Digraph.state = {
    pushState: (url, frame) => {
        Digraph.state.loadState(url, frame);
        history.pushState(
            { url: url, frame: frame.getAttribute('id') },
            document.getElementsByTagName('title')[0].innerHTML,
            url
        );
    },
    loadState: (url, frame) => {
        if (frame.stateUpdateRequest) {
            frame.stateUpdateRequest.abort();
        }
        frame.classList.add('loading');
        frame.stateUpdateRequest = new XMLHttpRequest();
        frame.stateUpdateRequest.addEventListener('load', (e) => {
            const doc = new DOMParser().parseFromString(e.target.response, 'text/html');
            frame.innerHTML = doc.getElementById(frame.getAttribute('id')).innerHTML;
            if (document.getElementById('breadcrumb') && doc.getElementById('breadcrumb')) {
                document.getElementById('breadcrumb').innerHTML = doc.getElementById('breadcrumb').innerHTML;
            }
            if (document.getElementById('notifications') && doc.getElementById('notifications')) {
                document.getElementById('notifications').innerHTML = doc.getElementById('notifications').innerHTML;
            }
            if (document.getElementsByTagName('title') && doc.getElementsByTagName('title')) {
                document.getElementsByTagName('title')[0].innerHTML = doc.getElementsByTagName('title')[0].innerHTML;
            }
            frame.classList.remove('loading');
        });
        frame.stateUpdateRequest.addEventListener('error', (e) => {
            frame.classList.add('error');
        });
        frame.stateUpdateRequest.addEventListener('abort', (e) => {
            frame.classList.remove('loading');
        });
        frame.stateUpdateRequest.open('GET', url);
        frame.stateUpdateRequest.send();
    }
};