<?php

namespace DigraphCMS\DB;

class Page extends AbstractDataObject
{
    const SOURCE = Pages::class;

    public function class(): string
    {
        return 'page';
    }
}
