<?php

namespace DigraphCMS\RichContent;

use DigraphCMS\Context;
use DigraphCMS\URL\URL;
use Formward\Fields\DisplayOnly;

class RichContentAttachmentField extends DisplayOnly
{
    protected $editorID;

    public function editorID(string $set = null): string
    {
        if ($set) {
            $this->editorID = $set;
        }
        return $this->editorID;
    }

    public function content(?string $set = null): string
    {
        return sprintf(
            '<iframe src="%s" class="embedded-iframe"></iframe>',
            new URL('/~api/v1/iframes/editor_attachments.php?editor=' . $this->editorID . '&page=' . Context::pageUUID()),
        );
    }
}
