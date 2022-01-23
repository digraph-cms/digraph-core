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
        $this->table('page')
            ->insert([
                'uuid' => $uuid = Digraph::uuid(),
                'name' => 'Home',
                'data' => '{}',
                'class' => 'page',
                'slug_pattern' => '/home',
                'created' => time(),
                'updated' => time()
            ])
            ->save();
        $this->table('page_slug')
            ->insert([
                'url' => 'home',
                'page_uuid' => $uuid
            ])
            ->save();
    }
}
