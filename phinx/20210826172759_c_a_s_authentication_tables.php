<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CASAuthenticationTables extends AbstractMigration
{
    public function change(): void
    {
        // cas table holds extra data from user CAS providers
        $this->table('user_cas')
            ->addColumn('cas_user', 'uuid')
            ->addColumn('cas_provider', 'string', ['length' => 100])
            ->addColumn('cas_id', 'string', ['length' => 250])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addIndex(['cas_user'])
            ->addIndex(['cas_provider'])
            ->addIndex(['cas_id'])
            ->addIndex(['cas_user', 'cas_provider'], ['unique' => true])
            ->addIndex(['cas_provider', 'cas_id'], ['unique' => true])
            ->addForeignKey(['cas_user'], 'user', 'user_uuid')
            ->create();
    }
}
