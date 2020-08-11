$(() => {
    // skip everything if there aren't any embedded playlists
    if ($('ul.embeddedplaylist').length == 0) {
        return;
    }

    // load youtube iframe api asynchronously
    var tag = document.createElement('script');
    tag.src = "//www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
});

function onYouTubeIframeAPIReady() {
    // keep track of player count for assigning IDs
    var playerCount = 0;
    var players = [];

    $('ul.embeddedplaylist').each(function (e) {
        var $ul = $(this).hide();
        var videos = [];
        $ul.find('a').each(function (e) {
            var video = parseUrl($(this).attr('href'));
            video.title = $(this).text();
            videos.push(video);
        });
        // construct controls
        var $controls = buildControls(videos, parseInt($ul.attr('data-per-row')));
        $controls.attr('data-autoplay', $ul.attr('data-autoplay'));
        $controls.insertAfter($ul);
        // build player
        var player = new YT.Player($controls.attr('id') + '_player', {
            height: '100%',
            width: '100%',
            videoId: videos[0].id,
            playerVars: {
                modestBranding: 1,
                rel: 0
            },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
        players[playerCount - 1] = player;
        var $player = $controls.find('.embeddedplaylist-player-wrapper');
        // set up thumbnail click handlers
        $controls.find('a.embeddedplaylist-thumb').click(function (event) {
            var $a = $(this);
            // make everyone inactive but me
            $a.closest('.thumbs').find('a').removeClass('active');
            $a.addClass('active');
            // get video and player ID and play
            var id = parseUrl($a.attr('href')).id;
            var playerID = parseInt($a.attr('data-player-id'));
            players[playerID].loadVideoById({
                videoId: id
            });
            // prevent click event
            event.preventDefault();
            return false;
        });
        // set first thumbnail active
        $controls.find('.thumbs .thumb:first-child a').addClass('active');
    });

    function onPlayerReady(event) {
        var $wrapper = $(event.target.getIframe()).closest('div.embeddedplaylist');
        if ($wrapper.attr('data-autoplay') == 'true') {
            event.target.playVideo();
        }
    }

    function onPlayerStateChange(event) {
        var player = event.target;
        var $wrapper = $(player.getIframe()).closest('div.embeddedplaylist');
        var playerID = parseInt($wrapper.attr('data-player-id'));
        var videoID = player.getVideoData().video_id;
        var $currentA = $wrapper.find('a[data-video-id="' + videoID + '"]');
        switch (event.data) {
            case 1:
                pauseAllPlayers(playerID);
                break;
            case 0:
                var $nextA = $currentA.closest('.cell').next().find('a');
                $nextA.click();
                break;
        }
    }

    function pauseAllPlayers(except) {
        for (let i = 0; i < players.length; i++) {
            const player = players[i];
            if (i !== except) {
                player.pauseVideo();
            }
        }
    }

    function buildControls(videos, perRow) {
        if (!perRow) {
            perRow = 3;
        }
        // set up wrapper
        var idNum = playerCount++;
        var id = 'player-' + idNum;
        var $wrapper = $('<div class="embeddedplaylist" id="' + id + '" data-player-id="' + idNum + '">');
        $wrapper.append('<div class="embeddedplaylist-player-outer-wrapper"><div class="embeddedplaylist-player-wrapper"><div id="' + id + '_player"></div></div></div>');
        // div.thumbs hold the thumbnails
        var $thumbs = $('<div class="thumbs">');
        for (var i = 0; i < videos.length; i++) {
            var $thumb = $('<div class="thumb" style="width:' + (100 / perRow) + '%;">');
            $thumb.append(makeThumb(videos[i]));
            $thumbs.append($thumb);
        }
        $thumbs.find('a').attr('data-player-id', idNum);
        $wrapper.append($thumbs);
        // return whole wrapper
        return $wrapper;
    }

    function makeThumb(video) {
        var $thumb = $(makeThumbHTML(video));
        $thumb.append(makeThumbImage(video));
        $thumb.append('<div class="embeddedplaylist-thumb-title">' + video.title + '</div>');
        return $thumb;
    }

    function makeThumbHTML(video) {
        return '<a class="embeddedplaylist-thumb service-' + video.service + '" data-video-id="' + video.id + '" href="https://www.youtube.com/watch?v=' + video.id + '" target="_blank" title="' + video.title + '"></a>';
    }

    function makeThumbImage(video) {
        return $('<img src="https://img.youtube.com/vi/' +
            video.id + '/hqdefault.jpg" alt="' +
            video.title + '">');
    }

    /**
     * Parse the YouTube ID out of a URL
     * @param url 
     */
    function parseUrl(url) {
        var video = {
            url: url
        };
        if (/\/\/(www\.)?youtube\.com/.test(url)) {
            video.id = url.match(/v=([^&]+)/)[1];
        } else if (/\/\/youtu.be/.test(url)) {
            video.id = url.match(/\.be\/([^?]+)/)[1];
        }
        return video;
    }
}