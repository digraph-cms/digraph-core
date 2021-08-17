<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UserTables extends AbstractMigration
{
    public function change(): void
    {
        // users table holds basic user data
        $users = $this->table('users');
        $users
            ->addColumn('user_uuid', 'uuid')
            ->addColumn('user_name', 'string', ['length' => 100])
            ->addColumn('user_data', 'json')
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('created_by', 'string')
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated_by', 'string')
            ->addIndex(['user_uuid'], ['unique' => true])
            ->create();
        // oauth table holds extra data from user oauth providers
        $oauth = $this->table('oauth2');
        $oauth
            ->addColumn('oauth_user', 'uuid')
            ->addColumn('oauth_provider', 'string', ['length' => 100])
            ->addColumn('oauth_id', 'string', ['length' => 250])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addIndex(['oauth_user'])
            ->addIndex(['oauth_provider'])
            ->addIndex(['oauth_id'])
            ->addIndex(['oauth_user', 'oauth_provider'], ['unique' => true])
            ->addIndex(['oauth_provider', 'oauth_id'], ['unique' => true])
            ->addForeignKey(['oauth_user'], 'users', 'user_uuid')
            ->create();
    }
}
