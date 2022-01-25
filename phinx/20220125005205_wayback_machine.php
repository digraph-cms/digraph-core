<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WaybackMachine extends AbstractMigration
{
    public function change(): void
    {
        // rich media content, so that this content can be edited
        // and permissioned separately from actual page content
        $this->table('wayback')
            ->addColumn('uuid', 'uuid')
            ->addColumn('url', 'string', ['length' => 250])
            ->addColumn('date', 'integer', ['null' => true])
            ->addColumn('data', 'json')
            ->addColumn('created', 'integer')
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('url')
            ->addIndex('date')
            ->addIndex('created')
            ->create();
    }
}
