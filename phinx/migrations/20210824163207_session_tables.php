<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SessionTables extends AbstractMigration
{
    public function change(): void
    {
        // holds authenticated user session tokens
        $this->table('session')
            ->addColumn('user_uuid', 'uuid')
            ->addColumn('comment', 'string')
            ->addColumn('secret', 'string', ['length' => 44])
            ->addColumn('created', 'integer')
            ->addColumn('expires', 'integer')
            ->addColumn('ip', 'string', ['length' => 39])
            ->addColumn('ua', 'string', ['length' => 250])
            ->addIndex(['user_uuid'])
            ->addIndex(['secret'])
            ->addIndex(['expires'])
            ->addIndex(['ip'])
            ->addForeignKey(['user_uuid'], 'user', ['uuid'])
            ->create();
        // holds early expirations for authenticated user session tokens
        $this->table('session_expiration')
            ->addColumn('session_id', 'integer')
            ->addColumn('date', 'integer')
            ->addColumn('reason', 'string')
            ->addIndex(['session_id'], ['unique' => true])
            ->addForeignKey(['session_id'], 'session')
            ->create();
    }
}
