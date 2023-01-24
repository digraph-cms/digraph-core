window.addEventListener('click', e => {
  // find target
  var event_target = e.target;
  if (!event_target) return;
  // find closest column-filter and hide all others
  var column_filter = event_target.closest('.column-filter');
  closeAllExcept(column_filter);
  // find link tag
  while (event_target.tagName != 'A') {
    event_target = event_target.parentNode;
    if (event_target == document.body || !event_target) {
      return;
    }
  }
  // check class
  if (event_target.classList.contains('column-filter__toggle__open') || event_target.classList.contains('column-filter__toggle__close')) {
    if (column_filter) {
      if (column_filter.classList.contains('column-filter--open')) column_filter.classList.remove('column-filter--open');
      else column_filter.classList.add('column-filter--open');
      e.preventDefault();
    }
  }
  // close all
  function closeAllExcept(keep_open) {
    Array.from(document.getElementsByClassName('column-filter--open'))
      .forEach(e => {
        if (e != keep_open) e.classList.remove('column-filter--open');
      });
  }
});