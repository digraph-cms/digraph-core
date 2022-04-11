<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class DelayedExecutionTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('delex')
            ->addColumn('parent', 'uuid', ['null' => true])
            ->addColumn('label', 'string', ['null' => true, 'length' => 250])
            ->addColumn('created', 'integer')
            ->addColumn('scheduled', 'integer')
            ->addColumn('expires', 'integer', ['null' => true])
            ->addColumn('runner', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addColumn('executed', 'integer', ['null' => true])
            ->addColumn('error', 'boolean', ['null' => true])
            ->addColumn('error_message', 'text', ['null' => true, 'length' => 250])
            ->addIndex('parent')
            ->addIndex('created')
            ->addIndex('scheduled')
            ->addIndex('expires')
            ->addIndex('executed')
            ->addIndex('error')
            ->create();
    }
}
