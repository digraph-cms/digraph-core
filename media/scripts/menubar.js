/*
Make menu dropdowns work more nicely
*/
(() => {
    // update menus to prepare them for automatic overflowed mode
    document.addEventListener('DigraphDOMReady', updateMenus);
    window.addEventListener('load', updateMenus);
    updateMenus();
    function updateMenus(e) {
        var target = e ? e.target ?? document : document;
        Array.from(target.getElementsByClassName('menubar'))
            .filter(m => !m.classList.contains('menubar--js'))
            .filter(m => !m.parentElement.classList.contains('menuitem__dropdown'))
            .forEach(menu => {
                menu.classList.add('menubar--js');
                var toggle = document.createElement('span');
                toggle.innerHTML = '<a class="menuitem__link">' + (menu.getAttribute('aria-label') ?? 'Menu') + '</a>';
                toggle.classList.add('menuitem');
                toggle.classList.add('menubar--overflowed__toggle');
                menu.insertBefore(toggle, menu.childNodes[0]);
                // toggle events
                toggle.addEventListener('click', (e) => {
                    if (menu.classList.contains('menubar--overflowed--open')) {
                        menu.classList.remove('menubar--overflowed--open');
                    } else {
                        menu.classList.add('menubar--overflowed--open');
                    }
                });
            });
    }
    // check menus to see if they need to go into overflowed mode
    document.addEventListener('DigraphDOMReady', checkMenus);
    window.addEventListener('load', checkMenus);
    window.addEventListener('resize', checkMenus);
    checkMenus();
    function checkMenus(e) {
        Array.from(document.getElementsByClassName('menubar--js'))
            .forEach(menu => {
                menu.style.height = menu.offsetHeight + 'px';
                menu.classList.remove('menubar--overflowed');
                menu.classList.add('menubar--checking-overflow');
                if (menu.offsetWidth < menu.scrollWidth) {
                    menu.classList.add('menubar--overflowed');
                }
                menu.style.height = 'auto';
                menu.classList.remove('menubar--checking-overflow');
            });
    }
    // update dropdown menus
    document.addEventListener('DigraphDOMReady', updateDropDowns);
    window.addEventListener('load', updateDropDowns);
    updateDropDowns();
    function updateDropDowns(e) {
        var target = e ? e.target ?? document : document;
        Array.from(target.getElementsByClassName('menuitem--dropdown'))
            .filter(m => !m.classList.contains('menuitem--dropdown-js'))
            .forEach(m => {
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
            });
    }
})();