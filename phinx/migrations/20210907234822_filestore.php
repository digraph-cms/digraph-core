<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Filestore extends AbstractMigration
{
    public function change(): void
    {
        // oauth table holds extra data from user oauth providers
        $this->table('filestore')
            ->addColumn('uuid', 'uuid', ['null' => false])
            ->addColumn('hash', 'string', ['length' => 32, 'null' => false])
            ->addColumn('filename', 'string', ['length' => 100, 'null' => false])
            ->addColumn('bytes', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('parent', 'uuid', ['null' => true])
            ->addColumn('meta', 'json', ['null' => false])
            ->addColumn('created', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('created_by', 'uuid', ['null' => false])
            ->addIndex(['uuid'], ['unique' => true])
            ->addIndex(['hash'])
            ->addIndex(['filename'])
            ->addIndex(['parent'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->create();
    }
}
