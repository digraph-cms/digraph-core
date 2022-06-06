<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CustomPageSorting extends AbstractMigration
{
    public function change(): void
    {
        $this->table('page')
            ->addColumn('sort_name', 'string', ['length' => 100, 'null' => true])
            ->addColumn('sort_weight', 'integer', ['default' => 0])
            ->addIndex('sort_name')
            ->addIndex('sort_weight')
            ->save();
    }
}
