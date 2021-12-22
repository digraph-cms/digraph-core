/**
 * Tools for managing state in navigation frames
 */

 document.addEventListener('click', (e) => {
    if (e.target.tagName == 'A') {
        // determine if we should even use this link
        if (!e.target.getAttribute('href')) {
            return;
        }
        if (e.target.getAttribute('href').substring(0, 1) == '#') {
            return;
        }
        var parent, target;
        [parent, target] = Digraph.state.navigationParentAndTarget(e.target);
        // parent and target found
        if (parent && target && target != '_top') {
            Digraph.state.getAndPush(e.target.getAttribute('href'), parent);
            e.preventDefault();
        }
    }
});

document.addEventListener('submit', (e) => {
    if (e.target.tagName == 'FORM') {
        var [parent, target] = Digraph.state.navigationParentAndTarget(e.target);
        // parent and target found
        if (parent && target && target != '_top') {
            var data = new FormData(e.target);
            // add clicked button value
            if (e.submitter.name && e.submitter.value) {
                data.append(e.submitter.name, e.submitter.value);
            }
            // submit
            Digraph.state.post(data, e.target.getAttribute('action'), parent);
            e.preventDefault();
        }
    }
});

window.addEventListener('popstate', (e) => {
    if (e.state.url && e.state.frame) {
        Digraph.state.get(e.state.url, document.getElementById(e.state.frame));
    };
});

Digraph.state = {
    getAndPush: (url, frame) => {
        Digraph.state.get(url, frame);
        history.pushState(
            { url: url, frame: frame.getAttribute('id') },
            document.getElementsByTagName('title')[0].innerHTML,
            url
        );
    },
    get: (url, frame) => {
        Digraph.state.addXHRListeners(frame);
        frame.stateUpdateRequest.open('GET', url);
        frame.stateUpdateRequest.send();
    },
    post: (data, url, frame) => {
        Digraph.state.addXHRListeners(frame);
        frame.stateUpdateRequest.open('POST', url, true);
        frame.stateUpdateRequest.send(data);
    },
    addXHRListeners: (frame) => {
        frame.classList.add('loading');
        if (frame.stateUpdateRequest) {
            frame.stateUpdateRequest.abort();
        }
        frame.stateUpdateRequest = new XMLHttpRequest();
        frame.stateUpdateRequest.addEventListener('load', (e) => {
            if (e.target.status == 200) {
                const doc = new DOMParser().parseFromString(e.target.response, 'text/html');
                const newHTML = doc.getElementById(frame.getAttribute('id')).innerHTML;
                if (newHTML) {
                    frame.innerHTML = newHTML;
                } else {
                    frame.classList.add('error');
                }
                if (document.getElementById('breadcrumb') && doc.getElementById('breadcrumb')) {
                    document.getElementById('breadcrumb').innerHTML = doc.getElementById('breadcrumb').innerHTML;
                }
                if (document.getElementById('notifications') && doc.getElementById('notifications')) {
                    document.getElementById('notifications').innerHTML = doc.getElementById('notifications').innerHTML;
                }
                if (document.getElementsByTagName('title') && doc.getElementsByTagName('title')) {
                    document.getElementsByTagName('title')[0].innerHTML = doc.getElementsByTagName('title')[0].innerHTML;
                }
                frame.dispatchEvent(
                    new Event('DigraphDOMReady', {
                        bubbles: true,
                        cancelable: false
                    })
                );
                frame.classList.remove('loading');
            } else {
                console.error(e);
                frame.classList.add('error');
            }
        });
        frame.stateUpdateRequest.addEventListener('error', (e) => {
            console.error(e);
            frame.classList.add('error');
        });
        frame.stateUpdateRequest.addEventListener('abort', (e) => {
            frame.classList.remove('loading');
            frame.classList.remove('error');
        });
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
