<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DefaultUsers extends AbstractMigration
{
    public function change(): void
    {
        // set up system user
        $this->table('user')
            ->insert([
                'uuid' => 'system',
                'name' => 'System',
                'data' => '{}',
                'created' => time(),
                'created_by' => 'system',
                'updated' => time(),
                'updated_by' => 'system'
            ])
            ->save();
        // set up guest user
        $this->table('user')
            ->insert([
                'uuid' => 'guest',
                'name' => 'Guest',
                'data' => '{}',
                'created' => time(),
                'created_by' => 'system',
                'updated' => time(),
                'updated_by' => 'system'
            ])
            ->save();
        // set up default groups
        $this->table('user_group')
            ->insert([
                'uuid' => 'admins',
                'name' => 'Administrators'
            ])
            ->insert([
                'uuid' => 'editors',
                'name' => 'Editors'
            ])
            ->save();
        // set up group memberships
        $this->table('user_group_membership')
            ->insert([
                'user_uuid' => 'system',
                'group_uuid' => 'admins'
            ])
            ->save();
    }
}
