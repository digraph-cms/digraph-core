<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCoreTables extends AbstractMigration
{
    public function change(): void
    {
        // pages table holds page data
        $this->table('page')
            ->addColumn('page_uuid', 'uuid')
            ->addColumn('page_slug', 'string', ['length' => 100])
            ->addColumn('page_name', 'string', ['length' => 100])
            ->addColumn('page_class', 'string', ['length' => 50])
            ->addColumn('page_data', 'json')
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('created_by', 'string')
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated_by', 'string')
            ->addIndex(['page_uuid'], ['unique' => true])
            ->addIndex(['page_slug'])
            ->addIndex(['page_name'])
            ->addIndex(['page_class'])
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->create();
        // slugs table holds additional older slugs
        $this->table('page_slugs')
            ->addColumn('slug_url', 'string', ['length' => 100])
            ->addColumn('slug_page', 'uuid')
            ->addIndex(['slug_url'])
            ->addIndex(['slug_page'])
            ->addIndex(['slug_url', 'slug_page'], ['unique' => true])
            ->addForeignKey('slug_page', 'page', 'page_uuid')
            ->create();
        // links table holds edges between pages
        $this->table('page_links')
            ->addColumn('link_start', 'uuid')
            ->addColumn('link_end', 'uuid')
            ->addColumn('link_type', 'string', ['length' => 50])
            ->addIndex(['link_start'])
            ->addIndex(['link_end'])
            ->addIndex(['link_type'])
            ->addIndex(['link_start', 'link_end', 'link_type'], ['unique' => true])
            ->addForeignKey('link_start', 'page', 'page_uuid')
            ->addForeignKey('link_end', 'page', 'page_uuid')
            ->create();
        // users table holds basic user data
        $this->table('user')
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
        $this->table('user_oauth2')
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
            ->addForeignKey(['oauth_user'], 'user', 'user_uuid')
            ->create();
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
        // groups table holds the group names to which users belong
        $this->table('user_groups')
            ->addColumn('group_user', 'uuid')
            ->addColumn('group_name', 'string', ['length' => 100])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addIndex(['group_user'])
            ->addIndex(['group_name'])
            ->addIndex(['group_user', 'group_name'], ['unique' => true])
            ->addForeignKey(['group_user'], 'user', 'user_uuid')
            ->create();
    }
}
