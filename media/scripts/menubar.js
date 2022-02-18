/*
Make menu dropdowns work more nicely
*/
(() => {
    document.addEventListener('DigraphDOMReady', checkMenus);
    window.addEventListener('load', checkMenus);
    checkMenus();

    // Look for new dropdown menus
    function checkMenus(e) {
        if (!e || !e.target) return;
        var menus = e.target.getElementsByClassName('menuitem--dropdown');
        for (const i in menus) {
            if (Object.hasOwnProperty.call(menus, i)) {
                const m = menus[i];
                if (m.classList.contains('menuitem--dropdown-js')) continue;
                m.classList.add('menuitem--dropdown--js');
                m.addEventListener('mouseenter', () => {
                    // add focus class and clear timer if it exists
                    m.classList.add('menuitem--focus');
                    if (m.timer) clearTimeout(m.timer);
                    // load iframe if necessary
                    if (m.dataset.dropdownUrl) {
                        var url = m.dataset.dropdownUrl;
                        var frame = m.getElementsByClassName('menuitem__frame')[0];
                        frame.classList.add('navigation-frame');
                        frame.classList.add('navigation-frame--stateless');
                        frame.setAttribute('data-initial-source', url);
                        frame.parentElement.dispatchEvent(new Event('DigraphDOMReady', { bubbles: true }));
                        delete m.dataset.dropdownUrl;
                    }

                });
                m.addEventListener('mouseleave', () => {
                    // set timer to clear focus class
                    m.timer = setTimeout(() => m.classList.remove('menuitem--focus'), 500);
                });
            }
        }
    }
})();