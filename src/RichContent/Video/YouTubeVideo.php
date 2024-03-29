<?php

namespace DigraphCMS\RichContent\Video;

class YouTubeVideo extends VideoEmbed
{
    public function __construct(string $id, int $start)
    {
        parent::__construct(
            'youtube',
            sprintf(
                'https://www.youtube-nocookie.com/embed/%s%s',
                $id,
                $start ? ('?start=' . $start) : ''
            )
        );
        $this->iframe()->setAttribute('allow', 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture');
    }
}
