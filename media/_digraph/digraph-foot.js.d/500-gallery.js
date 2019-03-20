/*{include:magnific/magnific-popup.js}*/

$(function() {
  $gallery = $('.digraph-gallery');
  if ($gallery.length) {
    $gallery.magnificPopup({
      delegate: 'a',
      type: 'ajax',
      gallery: {
        enabled: true
      },
      callbacks: {
        parseAjax: function(mfpResponse) {
          //trim down to just gallery file
          mfpResponse.data = $(mfpResponse.data).find('.digraph-gallery-file').eq(0);
          //replace image with div/background
          var $img = mfpResponse.data.find('.file > img');
          if ($img) {
            mfpResponse.data.find('.file').append('<div class="img-bg" style="background-image:url(\'' + $img.attr('src') + '\');"></div>');
          }
        }
      }
    });
  }
});