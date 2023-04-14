<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * This migration is being added to coincide with updating to Phinx 0.13, to
 * help make sure all the index columns line up with the correct types, and that
 * nullability and integer types/signing are up to date and correct everywhere.
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
                ->changeColumn('id', 'integer', ['signed' => false, 'null' => false])
                ->changePrimaryKey('id')
                ->save();
        }
        // re-add all the foreign keys that reference primary key id columns
        foreach ($foreign_keys as $table => $key) {
            $this->table($table)
                ->addForeignKey($key['column'], $key['table'])
                ->save();
        }
    }
}
