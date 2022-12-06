<?php

namespace DigraphCMS\RichContent\Video;

class YouTubePlaylist extends VideoEmbed
{
    public function __construct(string $id)
    {
        parent::__construct(
            'youtube',
            sprintf(
                'https://www.youtube-nocookie.com/embed/videoseries?list=%s',
                $id
            )
        );
        $this->iframe()->setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
    }
}
