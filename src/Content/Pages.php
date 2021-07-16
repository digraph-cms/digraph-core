<?php

namespace DigraphCMS\Content;

use DigraphCMS\DB\AbstractDataObject;
use DigraphCMS\DB\AbstractDataObjectSource;
use DigraphCMS\Session\Session;

class Pages extends AbstractDataObjectSource
{
    const TABLE = 'pages';

    public static function objectClass(array $result): string
    {
        return Page::class;
    }

    protected static function insertObjectValues(AbstractDataObject $object): array
    {
        return [
            'uuid' => $object->uuid(),
            'data' => json_encode($object->get()),
            'class' => $object->class(),
            'created_by' => $object->createdBy(),
            'updated_by' => $object->updatedBy(),
        ];
    }

    protected static function updateObjectValues(AbstractDataObject $object): array
    {
        return [
            'data' => json_encode($object->get()),
            'class' => $object->class(),
            'updated_by' => Session::user()
        ];
    }
}

Pages::__init();
