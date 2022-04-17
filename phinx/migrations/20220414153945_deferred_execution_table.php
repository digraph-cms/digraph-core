<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class DeferredExecutionTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('defex')
            ->addColumn('group', 'uuid')
            ->addColumn('run', 'integer', ['null' => true])
            ->addColumn('error', 'boolean', ['null' => true])
            ->addColumn('message', 'text', ['null' => true, 'length' => 250])
            ->addColumn('job', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addIndex('group')
            ->addIndex('run')
            ->addIndex('error')
            ->create();
    }
}
