<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCoreTables extends AbstractMigration
{
    public function change(): void
    {
        // pages table holds page data
        $page = $this->table('pages');
        $page
            ->addColumn('uuid', 'uuid')
            ->addColumn('class', 'string', ['length' => 50])
            ->addColumn('data', 'json')
            ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('created_by', 'string')
            ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
            ->addColumn('updated_by', 'string')
            ->addIndex(['uuid'], ['unique' => true])
            ->addIndex(['class'])
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->create();
        // links table holds edges between pages
        // $link = $this->table('links');
        // $link
        //     ->addColumn('start', 'uuid')
        //     ->addColumn('end', 'uuid')
        //     ->addColumn('type', 'string', ['limit' => 64])
        //     ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
        //     ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
        //     ->addIndex(['start', 'end', 'type'], ['unique' => true])
        //     ->addIndex(['start'])
        //     ->addIndex(['end'])
        //     ->addIndex(['type'])
        //     ->addForeignKey('start', 'pages', 'uuid')
        //     ->addForeignKey('end', 'pages', 'uuid')
        //     ->create();
        // slugs table holds alternative URLs for pages
        // $slug = $this->table('slugs');
        // $slug
        //     ->addColumn('path', 'string', ['limit' => 1024])
        //     ->addColumn('page_uuid', 'uuid')
        //     ->addColumn('created', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'timezone' => true])
        //     ->addColumn('updated', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP', 'timezone' => true])
        //     ->addIndex(['page_uuid'])
        //     ->addIndex(['path'])
        //     ->addForeignKey('page_uuid', 'pages', 'uuid')
        //     ->create();
    }
}
