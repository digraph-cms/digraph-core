/**
 * Tools for managing state in navigation frames
 */

// DigraphDOMReady handler for doing initial loading in navigation frames that need it
document.addEventListener('DigraphDOMReady', (e) => {
    var divs = e.target.getElementsByTagName('div');
    for (let i = 0; i < divs.length; i++) {
        const div = divs[i];
        if (div.classList.contains('navigation-frame')) {
            // load initial source
            if (div.dataset.initialSource) {
                div.resetFrame = function (e) {
                    Digraph.state.get(div.dataset.initialSource, div);
                    e.stopPropagation();
                }
                div.addEventListener('navigation-frame-reset', div.resetFrame);
                div.dispatchEvent(new Event('navigation-frame-reset'));
            }
        }
    }
});

document.addEventListener('navigation-frame-navigate', (e) => {
    var event_target = e.target;
    var event_url = e.navigateUrl;
    if (!event_target || !event_url) return;
    // find parent and target
    var parent, target;
    [parent, target] = Digraph.state.navigationParentAndTarget(event_target);
    // parent and target found
    if (parent && target) {
        // dispatch appropriate events
        if (Digraph.state.frameIsStateless(parent)) {
            // stateless navigation frames don't update the address bar or browser history
            Digraph.state.get(event_url, parent);
        } else {
            // otherwise call the function that pushes to address bar and browser history
            Digraph.state.getAndPush(event_url, parent);
        }
        e.stopPropagation();
    }
});

// click handler for links in navigation frames
document.addEventListener('click', (e) => {
    var event_target = e.target;
    if (!event_target) return;
    while (event_target.tagName != 'A') {
        event_target = event_target.parentNode;
        if (event_target == document.body || !event_target) {
            return;
        }
    }
    // determine if we should even use this link
    if (!event_target.getAttribute('href')) {
        return;
    }
    if (event_target.getAttribute('href').substring(0, 1) == '#') {
        return;
    }
    var parent, target;
    [parent, target] = Digraph.state.navigationParentAndTarget(event_target);
    // parent and target found
    if (parent && event_target && !event_target.attributes.target && target != '_top') {
        if (Digraph.state.frameIsStateless(parent)) {
            // stateless navigation frames don't update the address bar or browser history
            Digraph.state.get(event_target.getAttribute('href'), parent);
        } else {
            // otherwise call the function that pushes to address bar and browser history
            Digraph.state.getAndPush(event_target.getAttribute('href'), parent);
        }
        e.preventDefault();
    }
});

// submit handler for forms in navigation frames
document.addEventListener('submit', (e) => {
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
});

// popstate handler for back button
window.addEventListener('popstate', (e) => {
    if (e.state.url && e.state.frame) {
        Digraph.state.get(e.state.url, document.getElementById(e.state.frame));
    };
});

// state handler object in Digraph global
Digraph.state = {
    // get requested URL and updated browser history so that forward/back work
    getAndPush: (url, frame) => {
        Digraph.state.get(url, frame, true);
    },
    // only get the requested URL and replace frame contents
    get: (url, frame, pushState = false) => {
        Digraph.state.addXHRListeners(frame, pushState);
        frame.stateUpdateRequest.open('GET', url);
        frame.stateUpdateRequest.setRequestHeader('X-For-Navigation-Frame', 'y');
        frame.stateUpdateRequest.send();
    },
    // post the given data to the given URL and replace frame contents
    post: (data, url, frame) => {
        Digraph.state.addXHRListeners(frame);
        frame.stateUpdateRequest.open('POST', url, true);
        frame.stateUpdateRequest.setRequestHeader('X-For-Navigation-Frame', 'y');
        frame.stateUpdateRequest.send(data);
    },
    addXHRListeners: (frame, pushState = false) => {
        frame.classList.add('loading');
        if (frame.stateUpdateRequest) {
            frame.stateUpdateRequest.abort();
        }
        frame.stateUpdateRequest = new XMLHttpRequest();
        frame.stateUpdateRequest.addEventListener('load', (e) => {
            if (e.target.status == 200) {
                const doc = new DOMParser().parseFromString(e.target.response, 'text/html');
                if (document.getElementById('notifications') && doc.getElementById('notifications')) {
                    document.getElementById('notifications').innerHTML += doc.getElementById('notifications').innerHTML;
                }
                const docElement = doc.getElementById(frame.dataset.id ?? frame.getAttribute('id'));
                if (!docElement) {
                    if (frame.classList.contains('navigation-frame--hide-if-missing')) {
                        frame.style.display = 'none';
                    } else {
                        frame.classList.add('error');
                        if (document.getElementById('notifications')) {
                            var error = document.createElement('div');
                            error.classList.add('notification');
                            error.classList.add('notification--error');
                            error.innerText = 'Error loading navigation frame: target ID missing (' + (frame.dataset.id ?? frame.getAttribute('id')) + ')';
                            document.getElementById('notifications').appendChild(error);
                            error.parentElement.dispatchEvent(new Event('DigraphDOMReady', { bubbles: true }));
                        }
                    }
                    return;
                }
                // update frame
                frame.innerHTML = docElement.innerHTML;
                if (!Digraph.state.frameIsStateless(frame)) {
                    if (document.getElementById('breadcrumb') && doc.getElementById('breadcrumb')) {
                        document.getElementById('breadcrumb').innerHTML = doc.getElementById('breadcrumb').innerHTML;
                    }
                    if (document.getElementsByTagName('title') && doc.getElementsByTagName('title')) {
                        document.getElementsByTagName('title')[0].innerHTML = doc.getElementsByTagName('title')[0].innerHTML;
                    }
                }
                // update classes from pulled element
                if (docElement.getAttribute('class')) {
                    frame.setAttribute('class', docElement.getAttribute('class'));
                }
                // dispatch dom ready event
                frame.dispatchEvent(
                    new Event('DigraphDOMReady', {
                        bubbles: true,
                        cancelable: false
                    })
                );
                frame.classList.remove('loading');
                // execute scripts
                Array.from(frame.getElementsByTagName('script')).forEach(
                    oldElement => {
                        const newScript = document.createElement('script');
                        Array.from(oldElement.attributes).forEach(
                            attr => newScript.setAttribute(attr.name, attr.value)
                        );
                        newScript.appendChild(document.createTextNode(oldElement.innerHTML));
                        oldElement.parentNode.replaceChild(newScript, oldElement);
                    }
                );
                // focus autofocus element
                var af = frame.getElementsByClassName('navigation-frame__autofocus')[0];
                if (af) af.focus();
                // if requested, push state on completion
                if (pushState) {
                    history.pushState({ url: frame.stateUpdateRequest.responseURL, frame: frame.getAttribute('id') },
                        document.getElementsByTagName('title')[0].innerHTML,
                        frame.stateUpdateRequest.responseURL
                    );
                }
            } else {
                if (document.getElementById('notifications')) {
                    var error = document.createElement('div');
                    error.classList.add('notification');
                    error.classList.add('error');
                    error.innerText = 'Error loading navigation frame: ' + e.target.status;
                    document.getElementById('notifications').appendChild(error);
                }
                console.error(e);
                frame.classList.add('error');
            }
        });
        frame.stateUpdateRequest.addEventListener('error', (e) => {
            if (document.getElementById('notifications') && doc.getElementById('notifications')) {
                document.getElementById('notifications').innerHTML += doc.getElementById('notifications').innerHTML;
            }
            if (document.getElementById('notifications')) {
                var error = document.createElement('div');
                error.classList.add('notification');
                error.classList.add('error');
                error.innerText = 'Error loading navigation frame: unknown error';
                document.getElementById('notifications').appendChild(error);
            }
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
                    return [null, null];
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
    },
    frameIsStateless: (frame) => {
        do {
            if (frame.classList.contains('navigation-frame--stateless')) {
                return true;
            }
        } while (frame = frame.parentElement);
        return false;
    }
};