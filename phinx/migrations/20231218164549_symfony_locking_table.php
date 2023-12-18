<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SymfonyLockingTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('lock_keys', [
            'id' => false,
            'primary_key' => ['key_id'],
            'encoding'  => 'utf8',
            'collation' => 'utf8_mb4_bin',
        ])
            ->addColumn('key_id', 'string', ['length' => 64, 'null' => false])
            ->addcolumn('key_token', 'string', ['length' => 44, 'null' => false])
            ->addColumn('key_expiration', 'integer', ['signed' => false, 'length' => 10, 'null' => false])
            ->create();
    }
}
