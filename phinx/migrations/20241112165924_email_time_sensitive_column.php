<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EmailTimeSensitiveColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('email')
            ->addColumn('time_sensitive', 'boolean', ['default' => false, 'after' => 'uuid'])
            ->addIndex('time_sensitive')
            ->save();
    }
}
