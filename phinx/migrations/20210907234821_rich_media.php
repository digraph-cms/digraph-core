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
            ->addColumn('uuid', 'uuid', ['null' => false])
            ->addColumn('class', 'string', ['length' => 50, 'null' => false])
            ->addColumn('name', 'string', ['length' => 50, 'null' => false])
            ->addColumn('data', 'json', ['null' => false])
            ->addColumn('created', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addColumn('updated', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('updated_by', 'uuid', ['null' => false])
            ->addColumn('parent', 'uuid', ['null' => true])
            ->addIndex('parent')
            ->addIndex('uuid')
            ->addIndex('class')
            ->addIndex('name')
            ->addIndex('created')
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addIndex('updated')
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            ->create();
    }
}
