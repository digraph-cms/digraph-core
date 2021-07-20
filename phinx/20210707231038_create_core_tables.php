<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCoreTables extends AbstractMigration
{
    public function change(): void
    {
        // pages table holds page data
        $pages = $this->table('pages');
        $pages
            ->addColumn('page_uuid', 'uuid')
            ->addColumn('page_class', 'string', ['length' => 50])
            ->addColumn('page_data', 'json')
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('created_by', 'string')
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated_by', 'string')
            ->addIndex(['page_uuid'], ['unique' => true])
            ->addIndex(['page_class'])
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->create();
        // links table holds edges between pages
        $links = $this->table('links');
        $links
            ->addColumn('link_start', 'uuid')
            ->addColumn('link_end', 'uuid')
            ->addColumn('link_type', 'string', ['length' => 50])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('created_by', 'string')
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated_by', 'string')
            ->addIndex(['link_start'])
            ->addIndex(['link_end'])
            ->addIndex(['link_type'])
            ->addIndex(['link_start', 'link_end', 'link_type'], ['unique' => true])
            ->addForeignKey('link_start', 'pages', 'page_uuid')
            ->addForeignKey('link_end', 'pages', 'page_uuid')
            ->create();
        // aliases table holds alternative URLs for pages
        $aliases = $this->table('aliases');
        $aliases
            ->addColumn('alias_page', 'uuid')
            ->addColumn('alias_slug', 'string', ['length' => 100])
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('created_by', 'string')
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated_by', 'string')
            ->addIndex(['alias_slug'], ['unique' => true])
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->addForeignKey(['alias_page'], 'pages', ['page_uuid'])
            ->create();
    }
}
