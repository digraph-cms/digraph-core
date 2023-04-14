<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CronTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('cron')
            ->addColumn('parent', 'string', ['length' => 100, 'null' => false])
            ->addColumn('name', 'string', ['length' => 100, 'null' => false])
            ->addColumn('interval', 'string', ['length' => 25, 'null' => false])
            ->addColumn('run_next', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('run_last', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('error_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('error_message', 'text', ['null' => true, 'length' => 250])
            ->addColumn('job', 'text', ['null' => false, 'limit' => MysqlAdapter::TEXT_REGULAR])
            ->addIndex('parent')
            ->addIndex(['parent', 'name'], ['unique' => true])
            ->addIndex('run_next')
            ->addIndex('run_last')
            ->addIndex('error_time')
            ->create();
    }
}
