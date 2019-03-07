$(() => {
  $('a.digraph-link-intercept').on('click', (e) => {
    var $this = $(e.target);
    $this.attr('href', $this.attr('data-digraph-link'));
  });
});