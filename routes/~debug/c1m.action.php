<?php

use DigraphCMS\Content\Page;
use DigraphCMS\DB\DB;

ini_set('max_execution_time', 0);
ini_set('memory_limit','1G');
DB::pdo()->beginTransaction();
for ($i = 0; $i < 1000000; $i++) {
    $page = new Page([
        'foo' => [
            'bar' => bin2hex(random_bytes(8)),
            'baz' => bin2hex(random_bytes(12))
        ]
    ]);
    $page->insert();
}
DB::pdo()->commit();
