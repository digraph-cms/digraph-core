<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CoreIntColumnFixes extends AbstractMigration
{
    public function change(): void
    {
        $update = [
            'cron' => [
                'null' => ['run_last', 'error_time'],
                'notnull' => ['run_next']
            ],
            'datastore' => [
                'null' => [],
                'notnull' => ['created', 'updated']
            ],
            'defex' => [
                'null' => ['run'],
                'notnull' => []
            ],
            'email' => [
                'null' => ['sent'],
                'notnull' => ['time']
            ],
            'email_unsubscribe' => [
                'null' => [],
                'notnull' => ['time']
            ],
            'filestore' => [
                'null' => [],
                'notnull' => ['created']
            ],
            'locking' => [
                'null' => [],
                'notnull' => ['expires']
            ],
            'page' => [
                'null' => [],
                'notnull' => ['created', 'updated']
            ],
            'rich_media' => [
                'null' => [],
                'notnull' => ['created', 'updated']
            ],
            'search_index' => [
                'null' => [],
                'notnull' => ['updated']
            ],
            'session' => [
                'null' => [],
                'notnull' => ['created', 'expires']
            ],
            'session_expiration' => [
                'null' => [],
                'notnull' => ['date']
            ],
            'user' => [
                'null' => [],
                'notnull' => ['created', 'updated']
            ],
            'user_source' => [
                'null' => [],
                'notnull' => ['created']
            ],
        ];
        foreach ($update as $table => $groups) {
            $table = $this->table($table);
            foreach ($groups['null'] as $column) {
                $table->changeColumn($column, 'biginteger', ['null' => true, 'signed' => false]);
            }
            foreach ($groups['notnull'] as $column) {
                $table->changeColumn($column, 'biginteger', ['null' => false, 'signed' => false]);
            }
            $table->save();
        }
    }
}
