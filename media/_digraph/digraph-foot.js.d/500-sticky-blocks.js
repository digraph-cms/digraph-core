$(() => {
    $('.sticky-block').wrap('<div class="sticky-block-placeholder"><div class="sticky-block-wrapper"></div></div>');
    // set placeholder and wrapper sizes as window resizes
    var resize = () => {
        $('.sticky-block-placeholder').each((i, e) => {
            var $e = $(e);
            var $w = $(e).find('.sticky-block-wrapper');
            var $b = $(e).find('.sticky-block');
            $e.height($w.height());
            $b.width($e.width());
        });
    };
    resize();
    $(window).on('resize', resize);
    // place/stick elements as window resizes/scrolls
    var stick = () => {
        $('.sticky-block-placeholder').each((i, e) => {
            var $p = $(e);
            var $w = $(e).find('.sticky-block-wrapper');
            var $b = $(e).find('.sticky-block');
            var vpTop = $(window).scrollTop();
            var vpBottom = vpTop + $(window).height();
            var top = $p.position().top - $(window).scrollTop();
            var bottom = top + $p.height();
            if ($b.is('.bottom')) {
                var aboveBottom = bottom < vpBottom;
                var belowBottom = bottom > vpBottom;
                if (belowBottom) {
                    $w.addClass('stuck');
                    console.log('need to stick to bottom');
                } else {
                    $w.removeClass('stuck');
                    console.log('need to unstick from bottom');
                }
            }
        });
    };
    stick();
    $(window)
        .on('resize', stick)
        .on('scroll', stick);
});