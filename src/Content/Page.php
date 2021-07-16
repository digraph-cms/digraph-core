<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractDataObject;

class Page extends AbstractDataObject
{
    const SOURCE = Pages::class;

    public function class(): string
    {
        return 'page';
    }
}
