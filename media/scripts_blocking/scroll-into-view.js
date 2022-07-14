Digraph.scrollIntoView = function (el, position) {
    var offset = el.getBoundingClientRect();
    var scroll = false;
    switch (position) {
        case 'top':
            scroll = offset.top < 0;
            position = 'start';
            break;
        case 'bottom':
            scroll = offset.bottom > window.innerHeight;
            position = 'end';
            break;
        case 'center':
            scroll = offset.top < 0 || offset.bottom > window.innerHeight;
            break;
    }
    if (scroll) el.scrollIntoView({
        behavior: "smooth",
        inline: "nearest",
        block: position
    });
};