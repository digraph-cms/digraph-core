<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UnifiedAuthenticationTables extends AbstractMigration
{
    public function change(): void
    {
        // oauth table holds extra data from user oauth providers
        $this->table('user_source')
            ->addColumn('user_uuid', 'uuid')
            ->addColumn('source', 'string', ['length' => 100])
            ->addColumn('provider', 'string', ['length' => 100])
            ->addColumn('provider_id', 'string', ['length' => 250])
            ->addColumn('created', 'integer')
            ->addIndex(['user_uuid'])
            ->addIndex(['source'])
            ->addIndex(['provider'])
            ->addIndex(['source', 'provider', 'provider_id'], ['unique' => true])
            ->addForeignKey(['user_uuid'], 'user', ['uuid'])
            ->create();
    }
}
