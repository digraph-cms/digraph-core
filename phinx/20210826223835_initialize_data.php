<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitializeData extends AbstractMigration
{
    public function change(): void
    {
        $this->table('user_group')
            ->insert([
                'uuid' => 'admins',
                'name' => 'Administrators'
            ])
            ->save();
    }
}
