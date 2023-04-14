<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SessionTables extends AbstractMigration
{
    public function change(): void
    {
        // holds authenticated user session tokens
        $this->table('session')
            ->addColumn('user_uuid', 'uuid', ['null' => false])
            ->addColumn('comment', 'string', ['null' => false])
            ->addColumn('secret', 'string', ['length' => 44, 'null' => false])
            ->addColumn('created', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('expires', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('ip', 'string', ['length' => 39, 'null' => false])
            ->addColumn('ua', 'string', ['length' => 250, 'null' => false])
            ->addIndex(['user_uuid'])
            ->addIndex(['secret'])
            ->addIndex(['expires'])
            ->addIndex(['ip'])
            ->addForeignKey(['user_uuid'], 'user', ['uuid'])
            ->create();
        // holds early expirations for authenticated user session tokens
        $this->table('session_expiration')
            ->addColumn('session_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('date', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('reason', 'string', ['null' => false])
            ->addIndex(['session_id'], ['unique' => true])
            ->addForeignKey(['session_id'], 'session')
            ->create();
    }
}
