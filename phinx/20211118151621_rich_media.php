<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RichMedia extends AbstractMigration
{
    public function change(): void
    {
        // rich media content, so that this content can be edited
        // and permissioned separately from actual page content
        $this->table('rich_media')
            ->addColumn('uuid', 'uuid')
            ->addColumn('class', 'string', ['length' => 50])
            ->addColumn('name', 'string', ['length' => 50])
            ->addColumn('data', 'json')
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('updated', 'integer')
            ->addColumn('updated_by', 'uuid', ['null' => true])
            ->addColumn('page_uuid', 'uuid', ['null' => true])
            ->addIndex('page_uuid')
            ->addIndex('uuid')
            ->addIndex('class')
            ->addIndex('created')
            ->addIndex('created_by')
            ->addIndex('updated')
            ->addIndex('updated_by')
            ->create();
    }
}
