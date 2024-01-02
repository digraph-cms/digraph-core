<?php

declare(strict_types=1);

use DigraphCMS\DB\DB;
use Phinx\Migration\AbstractMigration;
use Symfony\Component\Lock\Store\PdoStore;

final class SymfonyLockingTable extends AbstractMigration
{
    public function change(): void
    {
        try {
            (new PdoStore(DB::pdo()))
                ->createTable();
        } catch (\Throwable $th) {
            // FAILS SILENTLY
        }
    }
}
