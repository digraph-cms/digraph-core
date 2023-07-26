<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LongerNameColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('page')
            ->changeColumn('name', 'string', ['length' => 250, 'null' => false])
            ->save();
    }
}