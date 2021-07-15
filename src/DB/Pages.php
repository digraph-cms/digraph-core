<?php

namespace DigraphCMS\DB;

class Pages extends AbstractDataObjectSource
{
    const TABLE = 'pages';

    public static function get(string $uuid): ?Page
    {
        return parent::get($uuid);
    }

    public static function objectClass(array $result): string
    {
        return Page::class;
    }
}

Pages::__init();
