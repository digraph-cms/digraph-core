<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class WaybackMachine extends AbstractMigration
{
    public function change(): void
    {
        // rich media content, so that this content can be edited
        // and permissioned separately from actual page content
        $this->table('wayback_machine')
            ->addColumn('uuid', 'uuid')
            ->addColumn('url', 'string', ['length' => 250])
            ->addColumn('wb_time', 'integer', ['null' => true])
            ->addColumn('wb_url', 'string', ['length' => 250, 'null' => true])
            ->addColumn('created', 'integer')
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('url')
            ->addIndex('wb_time')
            ->addIndex('created')
            ->create();
    }
}
