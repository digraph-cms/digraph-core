<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LongerDeferredExecutionGroups extends AbstractMigration
{
    public function change(): void
    {
        $this->table('defex')
            ->changeColumn('group', 'string', ['length' => 150, 'null' => false])
            ->save();
    }
}