<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * This migration is being added to coincide with updating to Phinx 0.13, to
 * help make sure all the index columns line up with the new default types.
 */
final class PhinxCleanup extends AbstractMigration
{
    public function change(): void
    {
        // drop all foreign keys to primary key id columns
        $foreign_keys = [
            'session_expiration' => ['column' => 'session_id', 'table' => 'session'],
        ];
        foreach ($foreign_keys as $table => $key) {
            $this->table($table)
                ->dropForeignKey($key['column'])
                ->save();
        }
        // update all the primary key id columns
        $primary_keys = [
            'cron', 'datastore', 'defex', 'email', 'email_unsubscribe',
            'filestore', 'locking', 'page', 'page_link', 'page_slug',
            'rich_media', 'search_index', 'session', 'session_expiration',
            'user', 'user_group', 'user_group_membership', 'user_source'
        ];
        foreach ($primary_keys as $table) {
            $this->table($table)
                ->changeColumn('id', 'integer', ['signed' => false, 'null' => false, 'identity' => true])
                ->changePrimaryKey('id')
                ->save();
        }
        // re-add all the foreign keys that reference primary key id columns
        foreach ($foreign_keys as $table => $key) {
            $this->table($table)
                ->changeColumn($key['column'], 'integer', ['null' => false, 'signed' => false])
                ->addForeignKey($key['column'], $key['table'])
                ->save();
        }
    }
}
