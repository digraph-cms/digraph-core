<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DeferredExecutionSchedulingColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('defex')
            ->addColumn('scheduled', "biginteger", ["signed" => false, "null" => true])
            ->addIndex('scheduled')
            ->update();
    }
}