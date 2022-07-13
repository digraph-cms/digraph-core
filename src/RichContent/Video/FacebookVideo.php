<?php

namespace DigraphCMS\RichContent\Video;

class FacebookVideo extends VideoEmbed
{
    public function __construct(string $id)
    {
        parent::__construct(
            'youtube',
            sprintf(
                'https://www.facebook.com/plugins/video.php?href=%s&show_text=false',
                urlencode($id)
            )
        );
        $this->iframe()->setAttribute('allowTransparency',true);
        $this->iframe()->setAttribute('allow', 'encrypted-media');
    }
}
