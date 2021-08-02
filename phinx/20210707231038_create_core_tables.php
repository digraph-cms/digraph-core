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
            ->addColumn('page_class', 'string', ['length' => 50])
            ->addColumn('page_data', 'json')
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('created_by', 'string')
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated_by', 'string')
            ->addIndex(['page_uuid'])
            ->addIndex(['page_class'])
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->create();
        // aliases table holds URL aliases/redirects
        $aliases = $this->table('aliases');
        $aliases
            ->addColumn('alias_uuid', 'uuid')
            ->addColumn('alias_start', 'string', ['length' => 100])
            ->addColumn('alias_end', 'string', ['length' => 100])
            ->addIndex(['alias_start'])
            ->addIndex(['alias_end'])
            ->addIndex(['alias_start', 'alias_end'], ['unique' => true])
            ->create();
        // links table holds edges between pages
        $links = $this->table('links');
        $links
            ->addColumn('link_uuid', 'uuid')
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
