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
            ->addColumn('uuid', 'uuid', ['null' => false])
            ->addColumn('url', 'string', ['length' => 250, 'null' => false])
            ->addColumn('wb_time', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('wb_url', 'string', ['length' => 250, 'null' => true])
            ->addColumn('created', 'biginteger', ['signed' => false, 'null' => false])
            ->addIndex('uuid', ['unique' => true])
            ->addIndex('url')
            ->addIndex('wb_time')
            ->addIndex('created')
            ->create();
    }
}
