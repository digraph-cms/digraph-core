<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class LongerUserAgentColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('session')
            ->changeColumn('ua', 'string', ['null' => false, 'length' => 350])
            ->save();
    }
}