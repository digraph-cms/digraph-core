<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;use Phinx\Migration\AbstractMigration;

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
            function (DeferredJob $job) {
                $files = DB::query()
                    ->from('filestore')
                    ->where('permissions is null')
                    ->select('uuid', true);
                foreach ($files as $r) {
                    $uuid = $r['uuid'];
                    $job->spawn(function () use ($uuid) {
                        $file = Filestore::get($uuid);
                        if (!$file) return "File $uuid not found";
                        Filestore::updateSearchIndex($file);
                        return "Queued index of file $uuid";
                    });
                }
                return sprintf("Spawned %s filestore indexing jobs", $files->count());
            },
            'index_filestore_migration'
        );
    }
}
