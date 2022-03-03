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
            ->addColumn('subject', 'string', ['length' => 250])
            ->addColumn('sender', 'uuid', ['null' => true])
            ->addColumn('recipient', 'uuid')
            ->addColumn('body', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR])
            ->addColumn('time', 'integer')
            ->addColumn('read', 'boolean')
            ->addColumn('archived', 'boolean')
            ->addColumn('important', 'boolean')
            ->addColumn('sensitive', 'boolean')
            ->addColumn('email', 'boolean')
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('sender')
            ->addIndex('recipient')
            ->addIndex('time')
            ->addIndex('email')
            ->create();
        $this->table('email')
            ->addColumn('uuid', 'uuid')
            ->addColumn('subject', 'string', ['length' => 250])
            ->addColumn('field_from', 'string', ['length' => 250])
            ->addColumn('field_to', 'string', ['length' => 500])
            ->addColumn('field_cc', 'string', ['length' => 500, 'null' => true])
            ->addColumn('field_bcc', 'string', ['length' => 500, 'null' => true])
            ->addColumn('field_important', 'boolean')
            ->addColumn('body_text', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => true])
            ->addColumn('body_html', 'text', ['limit' => MysqlAdapter::TEXT_REGULAR, 'null' => true])
            ->addColumn('message_uuid', 'uuid', ['null' => true])
            ->addColumn('recipient_uuid', 'uuid', ['null' => true])
            ->addColumn('time', 'integer')
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('message_uuid')
            ->addIndex('recipient_uuid')
            ->addIndex('time')
            ->create();
    }
}
