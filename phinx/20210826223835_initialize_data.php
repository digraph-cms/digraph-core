<?php

declare(strict_types=1);

use DigraphCMS\Digraph;
use Phinx\Migration\AbstractMigration;

final class InitializeData extends AbstractMigration
{
    public function change(): void
    {
        $this->table('user_group')
            ->insert([
                'uuid' => 'admins',
                'name' => 'Administrators'
            ])->insert([
                'uuid' => 'editors',
                'name' => 'Editors'
            ])
            ->save();
    }
}
