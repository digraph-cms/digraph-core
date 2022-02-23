<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CoreTables extends AbstractMigration
{
    public function change(): void
    {
        // users table holds basic user data
        $this->table('user')
            ->addColumn('uuid', 'uuid')
            ->addColumn('name', 'string', ['length' => 100])
            ->addColumn('data', 'json')
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid', ['null' => true])
            ->addIndex(['uuid'], ['unique' => true])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->create();
        // groups table holds the groups
        $this->table('user_group')
            ->addColumn('uuid', 'uuid')
            ->addColumn('name', 'string', ['length' => 100])
            ->addIndex(['uuid'], ['unique' => true])
            ->create();
        // logs which members are in which groups
        $this->table('user_group_membership')
            ->addColumn('user_uuid', 'uuid')
            ->addColumn('group_uuid', 'uuid')
            ->addForeignKey(['user_uuid'], 'user', ['uuid'])
            ->addForeignKey(['group_uuid'], 'user_group', ['uuid'])
            ->create();
        // pages table holds page data
        $this->table('page')
            ->addColumn('uuid', 'uuid')
            ->addColumn('name', 'string', ['length' => 100])
            ->addColumn('class', 'string', ['length' => 50])
            ->addColumn('slug_pattern', 'string', ['length' => 100])
            ->addColumn('data', 'json')
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid', ['null' => true])
            ->addIndex(['uuid'], ['unique' => true])
            ->addIndex(['name'])
            ->addIndex(['class'])
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->create();
        // slugs table holds additional older slugs
        $this->table('page_slug')
            ->addColumn('url', 'string', ['length' => 100])
            ->addColumn('page_uuid', 'uuid')
            ->addIndex(['url'])
            ->addIndex(['page_uuid'])
            ->addIndex(['url', 'page_uuid'], ['unique' => true])
            ->addForeignKey(['page_uuid'], 'page', ['uuid'])
            ->create();
        // links table holds edges between pages
        $this->table('page_link')
            ->addColumn('start_page', 'uuid')
            ->addColumn('end_page', 'uuid')
            ->addColumn('type', 'string', ['length' => 50])
            ->addIndex(['start_page'])
            ->addIndex(['end_page'])
            ->addIndex(['type'])
            ->addIndex(['start_page', 'end_page', 'type'], ['unique' => true])
            ->addForeignKey(['start_page'], 'page', ['uuid'])
            ->addForeignKey(['end_page'], 'page', ['uuid'])
            ->create();
    }
}
