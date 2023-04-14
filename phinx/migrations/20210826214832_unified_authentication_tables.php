<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UnifiedAuthenticationTables extends AbstractMigration
{
    public function change(): void
    {
        // oauth table holds extra data from user oauth providers
        $this->table('user_source')
            ->addColumn('user_uuid', 'uuid', ['null' => false])
            ->addColumn('source', 'string', ['length' => 100, 'null' => false])
            ->addColumn('provider', 'string', ['length' => 100, 'null' => false])
            ->addColumn('provider_id', 'string', ['length' => 250, 'null' => false])
            ->addColumn('created', 'integer', ['null' => false])
            ->addIndex(['user_uuid'])
            ->addIndex(['source'])
            ->addIndex(['provider'])
            ->addIndex(['source', 'provider', 'provider_id'], ['unique' => true])
            ->addForeignKey(['user_uuid'], 'user', ['uuid'])
            ->create();
    }
}
