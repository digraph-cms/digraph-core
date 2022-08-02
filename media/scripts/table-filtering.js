window.addEventListener('click', e => {
  // find link tag
  var link_tag = e.target;
  if (!link_tag) return;
  while (link_tag.tagName != 'A') {
    link_tag = link_tag.parentNode;
    if (link_tag == document.body || !link_tag) {
      return;
    }
  }
  // check class
  if(link_tag.classList.contains('column-filter__toggle__open') || link_tag.classList.contains('column-filter__toggle__close')) {
    var column_filter = link_tag.closest('.column-filter');
    if (column_filter) {
      if (column_filter.classList.contains('column-filter--open')) column_filter.classList.remove('column-filter--open');
      else column_filter.classList.add('column-filter--open');
      e.preventDefault();
    }
  }
});