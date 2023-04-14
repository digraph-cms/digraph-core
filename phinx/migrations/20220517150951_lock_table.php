<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LockTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('locking')
            ->addColumn('name', 'string', ['length' => 250, 'null' => false])
            ->addColumn('expires', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('exclusive', 'boolean', ['null' => false])
            ->addIndex('name')
            ->addIndex('expires')
            ->addIndex('exclusive')
            ->create();
    }
}
