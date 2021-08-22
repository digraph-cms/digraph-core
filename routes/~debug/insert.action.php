<?php

use DigraphCMS\Content\Page;
use DigraphCMS\DB\DB;
use DigraphCMS\Users\Users;

ini_set('max_execution_time', 0);
ini_set('memory_limit', '1G');
DB::beginTransaction();
for ($i = 0; $i < 10000; $i++) {
    $page = new Page([
        'foo' => [
            'bar' => bin2hex(random_bytes(8)),
            'baz' => bin2hex(random_bytes(12))
        ]
    ]);
    $page->name(Users::randomName());
    $page->insert();
}
DB::commit();
