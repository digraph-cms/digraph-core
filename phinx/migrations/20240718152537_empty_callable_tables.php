<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * This migration empties the tables that hold callables, because after
 * switching serialization libraries the old ones are no longer usable and will
 * generate a gazillion errors.
 */
final class EmptyCallableTables extends AbstractMigration
{
    public function change(): void
    {
        $this->table('cron')->truncate();
        $this->table('defex')->truncate();
    }
}
