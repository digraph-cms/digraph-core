<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Cron\DeferredJob;
use Phinx\Migration\AbstractMigration;

final class IndexingFilestore extends AbstractMigration
{
    public function change(): void
    {
        /**
         * This migration does nothing but queue a background job to index
         * existing filestore files. Future filestore files will be indexed
         * automatically as they are created.
         */
        new DeferredJob(
            [Filestore::class, 'runSearchIndexing'],
            'index_filestore_migration'
        );
    }
}
