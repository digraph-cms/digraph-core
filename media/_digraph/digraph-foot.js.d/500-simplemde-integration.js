/**
 * Toggle SimpleMDE on content fields
 */
$(() => {
  $('.Form .Container.DigraphContent').each(function (e) {
    var $container = $(this);
    var $filter = $container.find('.Field.class-ContentFilter');
    var $textArea = $container.find('.Field.class-ContentTextarea');
    var simpleMDE = null;
    var updateFN = function (e) {
      var markdown = !!$filter.find('[value="' + $filter.val() + '"]').text().match(/markdown/i);
      if (markdown && !simpleMDE) {
        // should be markdown, enable simpleMDE
        simpleMDE = new SimpleMDE({
          element: $textArea[0],
          autoDownloadFontAwesome: false,
          spellChecker: false
        });
        $textArea.addClass('mde-active');
      }
      if (!markdown && simpleMDE) {
        // should not be markdown, disable simpleMDE
        simpleMDE.toTextArea();
        simpleMDE = null;
        $textArea.removeClass('mde-active');
        setTimeout(function () {
          $textArea.height($textArea[0].scrollHeight);
        }, 10);
      }
    };
    $textArea.change(function (e) {
      if (simpleMDE) {
        simpleMDE.value($textArea.val());
      }
    });
    $filter.change(updateFN);
    updateFN();
  });
});