/**
 * objects with the class "digraph-link" store their canonical URL in the
 * attribute data-digraph-link, and that swaps in place of the href attribute
 * on mousedown
 */
document.addEventListener("DOMContentLoaded", function(event) {
  let links = document.getElementsByClassName('digraph-link');
  for (var i = 0; i < links.length; i++) {
    let link = links[i];
    link.addEventListener('mousedown', function(e) {
      link.setAttribute('href', link.getAttribute('data-digraph-link'));
    });
  }
});