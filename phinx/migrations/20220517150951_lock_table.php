<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LockTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('locking')
            ->addColumn('name', 'string',['length' => 250])
            ->addColumn('expires', 'integer')
            ->addColumn('exclusive', 'boolean')
            ->addIndex('name')
            ->addIndex('expires')
            ->addIndex('exclusive')
            ->create();
    }
}
