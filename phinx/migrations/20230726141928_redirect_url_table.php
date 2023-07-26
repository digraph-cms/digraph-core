<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RedirectUrlTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('redirect')
            // basic columns for redirect from/to and type
            ->addColumn('redirect_from', 'string', ['null' => false, 'length' => 250])
            ->addColumn('redirect_to', 'string', ['null' => false, 'length' => 250])
            ->addIndex('redirect_from', ['unique' => true])
            // track created time/user
            ->addColumn('created', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addIndex(['created'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->create();
    }
}