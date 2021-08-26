<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SessionTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('sess_auth')
            ->addColumn('user', 'uuid')
            ->addColumn('comment', 'string')
            ->addColumn('secret', 'string', ['length' => 44])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('expires', 'timestamp', ['timezone' => true])
            ->addColumn('ip', 'string', ['length' => 39])
            ->addColumn('ua', 'string', ['length' => 39])
            ->addIndex(['user'])
            ->addIndex(['secret'])
            ->addIndex(['expires'])
            ->addIndex(['ip'])
            ->addForeignKey(['user'], 'user', ['user_uuid'])
            ->create();
        $this->table('sess_exp')
            ->addColumn('auth', 'integer')
            ->addColumn('date', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('reason', 'string')
            ->addIndex(['auth'], ['unique' => true])
            ->addForeignKey(['auth'], 'sess_auth')
            ->create();
    }
}
