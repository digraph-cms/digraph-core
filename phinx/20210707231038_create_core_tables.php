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
        $slugs = $this->table('slugs');
        $slugs
            ->addColumn('slug_url', 'string', ['length' => 100])
            ->addColumn('slug_page', 'uuid')
            ->addIndex(['slug_url'])
            ->addIndex(['slug_page'])
            ->addIndex(['slug_url', 'slug_page'], ['unique' => true])
            ->addForeignKey('slug_page', 'pages', 'page_uuid')
            ->create();
        // links table holds edges between pages
        $links = $this->table('links');
        $links
            ->addColumn('link_start', 'uuid')
            ->addColumn('link_end', 'uuid')
            ->addColumn('link_type', 'string', ['length' => 50])
            ->addIndex(['link_start'])
            ->addIndex(['link_end'])
            ->addIndex(['link_type'])
            ->addIndex(['link_start', 'link_end', 'link_type'], ['unique' => true])
            ->addForeignKey('link_start', 'pages', 'page_uuid')
            ->addForeignKey('link_end', 'pages', 'page_uuid')
            ->create();
    }
}
