<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DatastoreTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('datastore')
            // datastore data
            ->addColumn('ns', 'string', ['length' => 255, 'null' => false])
            ->addIndex('ns')
            ->addColumn('grp', 'string', ['length' => 255, 'null' => false])
            ->addIndex('grp')
            ->addColumn('key', 'string', ['length' => 255, 'null' => false])
            ->addIndex('key')
            ->addIndex(['ns', 'grp', 'key'], ['unique' => true])
            ->addColumn('value', 'string', ['length' => 255, 'null' => true])
            ->addIndex('value')
            ->addColumn('data', 'json')
            // track created/updated time/user
            ->addColumn('created', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addColumn('updated', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('updated_by', 'uuid', ['null' => false])
            ->addIndex(['created'])
            ->addIndex(['updated'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['updated_by'], 'user', ['uuid'])
            // create table
            ->create();
    }
}
