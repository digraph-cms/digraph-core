<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropOldWaybackTable extends AbstractMigration
{
    public function change(): void
    {
        // drop the old wayback table, because it's now entirely defunct
        // everything that used to be here is now handled using Cache::getDeferred
        $this->table('wayback_machine')
            ->drop()
            ->save();
    }
}
