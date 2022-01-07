<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Filestore extends AbstractMigration
{
    public function change(): void
    {
        // oauth table holds extra data from user oauth providers
        $this->table('filestore')
            ->addColumn('uuid', 'uuid')
            ->addColumn('hash', 'string', ['length' => 32])
            ->addColumn('filename', 'string', ['length' => 100])
            ->addColumn('bytes', 'integer')
            ->addColumn('rich_media_uuid', 'uuid', ['null' => true])
            ->addColumn('meta', 'json')
            ->addColumn('created', 'integer')
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addIndex(['uuid'], ['unique' => true])
            ->addIndex(['hash'])
            ->addIndex(['filename'])
            ->addIndex(['rich_media_uuid'])
            ->addForeignKey(['created_by'], 'user', ['uuid'])
            ->addForeignKey(['rich_media_uuid'], 'rich_media', ['uuid'])
            ->create();
    }
}
