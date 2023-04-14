<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class EmailTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('email')
            ->addColumn('uuid', 'uuid', ['null' => false])
            ->addColumn('time', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('category', 'string', ['length' => 50, 'null' => false])
            ->addColumn('subject', 'string', ['length' => 250, 'null' => false])
            ->addColumn('to', 'string', ['length' => 254, 'null' => false])
            ->addColumn('to_uuid', 'uuid', ['null' => true])
            ->addColumn('from', 'string', ['length' => 254, 'null' => false])
            ->addColumn('cc', 'string', ['length' => 254, 'null' => true])
            ->addColumn('bcc', 'string', ['length' => 254, 'null' => true])
            ->addColumn('body_text', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => false])
            ->addColumn('body_html', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => false])
            ->addColumn('blocked', 'boolean', ['null' => false])
            ->addColumn('error', 'string', ['length' => 254, 'null' => true])
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('category')
            ->addIndex('to')
            ->addIndex('time')
            ->addIndex('blocked')
            ->addIndex('error')
            ->create();
        $this->table('email_unsubscribe')
            ->addColumn('email', 'string', ['length' => 254, 'null' => false])
            ->addColumn('category', 'string', ['length' => 50, 'null' => false])
            ->addColumn('time', 'biginteger', ['signed' => false, 'null' => false])
            ->addIndex(['email', 'category'], ['unique' => true])
            ->addIndex('email')
            ->addIndex('category')
            ->addIndex('time')
            ->create();
    }
}
