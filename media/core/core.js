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
        // determine if we should even use this link
        if (!event.target.getAttribute('href')) {
            return;
        }
        if (event.target.getAttribute('href').substring(0, 1) == '#') {
            return;
        }
        var parent, target;
        [parent, target] = Digraph.state.navigationParentAndTarget(event.target);
        // parent and target found
        if (parent && target && target != '_top') {
            Digraph.state.pushState(event.target.getAttribute('href'), parent);
            event.preventDefault();
        }
    }
});

document.addEventListener('submit', (event) => {
    if (event.target.tagName == 'FORM') {
        var [parent, target] = Digraph.state.navigationParentAndTarget(event.target);
        // parent and target found
        if (parent && target && target != '_top') {
            Digraph.state.post(new FormData(event.target), event.target.getAttribute('action'), parent);
            event.preventDefault();
        }
    }
});

window.addEventListener('popstate', (e) => {
    if (e.state.url && e.state.frame) {
        Digraph.state.load(e.state.url, document.getElementById(e.state.frame));
    };
});

Digraph.state = {
    pushState: (url, frame) => {
        Digraph.state.load(url, frame);
        history.pushState(
            { url: url, frame: frame.getAttribute('id') },
            document.getElementsByTagName('title')[0].innerHTML,
            url
        );
    },
    load: (url, frame) => {
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
    },
    post: (data, url, frame) => {
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
        frame.stateUpdateRequest.open('POST', url);
        frame.stateUpdateRequest.send(data);
    },
    navigationParentAndTarget: (target) => {
        // first see if target has a data-target attribute, use that
        var parent = target.dataset.target;
        if (!parent || !(parent = document.getElementById(parent))) {
            // otherwise try to find a parent navigation-frame
            parent = target.parentElement;
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
        var target = target.dataset.target ?? parent.dataset.target ?? parent.getAttribute('id');
        if (target == '_frame') {
            target = parent.getAttribute('id');
        }
        return [parent, target];
    }
};