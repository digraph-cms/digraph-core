$(() => {
    //add drag cues to file fields
    var dragTimer;
    // set up fields
    $('.Form input[type="file"]')
        .wrap('<div class="file-field-wrapper">');
    // alter fields on window dragover
    $('html')
        .on('dragover', function (e) {
            var dt = e.originalEvent.dataTransfer;
            if (dt.types && (dt.types.indexOf ? dt.types.indexOf('Files') != -1 : dt.types.contains('Files'))) {
                var $fields = $('.Form input[type="file"]');
                $fields.each(function () {
                    var $this = $(this);
                    var $wrapper = $this.parent();
                    $wrapper.height($wrapper.height());
                    $this.addClass('drag');
                    $wrapper.addClass('drag');
                    if ($fields.length == 1) {
                        $this.addClass('drag-onlyfield');
                        $wrapper.addClass('drag-onlyfield');
                    }
                    window.clearTimeout(dragTimer);
                });
            }
        })
        .on('dragleave', function (e) {
            dragTimer = window.setTimeout(function () {
                $('.Form .file-field-wrapper,.Form input[type="file"]')
                    .removeClass('drag')
                    .removeClass('drag-onlyfield')
                    .height('auto');
            });
        });
    //add filled cues to file fields
    $('.Form input[type="file"]')
        .change(function () {
            var $this = $(this);
            var $wrapper = $this.parent();
            if ($this.val()) {
                $this
                    .addClass('filled')
                    .removeClass('drag')
                    .removeClass('drag-onlyfield');
                $wrapper
                    .removeClass('drag')
                    .removeClass('drag-onlyfield')
                    .height('auto');
            }
        });
})