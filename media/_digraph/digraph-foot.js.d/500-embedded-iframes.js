$(() => {
    var $iframes = $('iframe.embedded-iframe');
    setInterval(()=>{
        $iframes = $('iframe.embedded-iframe');
    },1000);
    var updateFrames = () => {
        $iframes.each((i) => {
            var $iframe = $iframes.eq(i);
            var $content = $iframe.contents();
            //add iframe-embedded class
            $content.find('body').addClass('iframe-embedded');
            //set height
            var height = $content.find('html').get(0).offsetHeight;
            $iframe.height(height);
        });
    }
    setInterval(updateFrames, 500);
});

if (window!=window.top) { $('body').addClass('iframe-embedded'); }