<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class MessagingTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('message')
            ->addColumn('uuid', 'uuid')
            ->addColumn('category', 'string', ['length' => 50])
            ->addColumn('subject', 'string', ['length' => 250])
            ->addColumn('sender', 'uuid', ['null' => true])
            ->addColumn('recipient', 'uuid')
            ->addColumn('body', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addColumn('time', 'integer')
            ->addColumn('read', 'boolean', ['default' => 0])
            ->addColumn('archived', 'boolean', ['default' => 0])
            ->addColumn('important', 'boolean')
            ->addColumn('sensitive', 'boolean')
            ->addColumn('email', 'boolean')
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('category')
            ->addIndex('sender')
            ->addIndex('recipient')
            ->addIndex('time')
            ->addIndex('email')
            ->create();
    }
}
