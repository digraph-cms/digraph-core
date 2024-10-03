<?php

namespace DigraphCMS\Content;

use DigraphCMS\Notes\AbstractNotesManager;

/**
 * @extends AbstractNotesManager<AbstractPage>
 */
class PageNotes extends AbstractNotesManager
{
    protected static function ns(): string
    {
        return 'pages_core';
    }

    protected static function groupName(mixed $parent): string
    {
        return $parent->uuid();
    }
}
