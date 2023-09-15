<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DeleteLockingTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('locking')
            ->drop()
            ->save();
    }
}
