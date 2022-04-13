<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CronTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('cron')
            ->addColumn('parent', 'string', ['length' => 100])
            ->addColumn('name', 'string', ['length' => 100])
            ->addColumn('interval', 'string', ['length' => 25])
            ->addColumn('run_next', 'integer')
            ->addColumn('run_last', 'integer', ['null' => true])
            ->addColumn('run_halted', 'integer', ['null' => true])
            ->addColumn('error_time', 'integer', ['null' => true])
            ->addColumn('error_message', 'text', ['null' => true, 'length' => 250])
            ->addColumn('job', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addIndex('parent')
            ->addIndex(['parent', 'name'], ['unique' => true])
            ->addIndex('run_next')
            ->addIndex('run_last')
            ->addIndex('run_halted')
            ->addIndex('error_time')
            ->create();
    }
}
