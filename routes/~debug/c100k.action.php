<?php

use DigraphCMS\Content\Page;
use DigraphCMS\DB\DB;

DB::pdo()->beginTransaction();
for ($i = 0; $i < 100000; $i++) {
    $page = new Page([
        'foo' => [
            'bar' => bin2hex(random_bytes(8)),
            'baz' => bin2hex(random_bytes(12))
        ]
    ]);
    $page->insert();
}
DB::pdo()->commit();
