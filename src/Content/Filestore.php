<?php

namespace DigraphCMS\Content;

use DigraphCMS\Config;
use DigraphCMS\Cron\DeferredJob;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\ExceptionLog;
use DigraphCMS\FS;
use DigraphCMS\Media\TextExtractor;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\Search\Search;
use DigraphCMS\Serializer;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use Exception;

class Filestore
{
    protected static $cache = [];

    public static function delete(FilestoreFile $file): bool
    {
        // delete file
        if (
            !DB::query()
                ->delete('filestore')
                ->where('uuid = ?', [$file->uuid()])
                ->execute()
        ) {
            return false;
        }
        // determine if any with this hash remain
        $unique = !DB::query()
            ->from('filestore')
            ->where('hash = ?', [$file->hash()])
            ->count();
        // delete file if was unique
        if ($unique) {
            FS::delete($file->path(), Config::get('filestore.path'));
        }
        return true;
    }

    public static function create(string $data, string $filename, string $parent, array $meta, string $uuid = null, null|callable $permissions = null): FilestoreFile
    {
        // create file
        $hash = md5($data);
        $dest = static::path($hash);
        FS::mkdir(dirname($dest));
        file_put_contents($dest, $data);
        $file = new FilestoreFile(
            $uuid ?? Digraph::uuid(),
            $hash,
            $filename,
            filesize($dest),
            $parent,
            $meta,
            time(),
            Session::user(),
            $permissions
        );
        $file->write();
        static::insert($file);
        static::$cache[$file->uuid()] = $file;
        // queue job to index file in search index
        static::updateSearchIndex($file);
        // return file
        return $file;
    }

    public static function migrateToSearchIndexing(DeferredJob $job): string
    {
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
    }

    public static function updateSearchIndex(FilestoreFile $file): void
    {
        // don't index if file has permissions
        if ($file->permissions()) return;
        // queue job to index file in search index
        $uuid = $file->uuid();
        new DeferredJob(
            function () use ($uuid) {
                $file = Filestore::get($uuid);
                if (!$file) return "File $uuid not found";
                $parent_uuid = $file->parentUUID();
                $parent_page = Pages::get($parent_uuid);
                if (!$parent_page) {
                    $parent_media = RichMedia::get($parent_uuid);
                    if (!$parent_media) return "Parent $parent_uuid is not a page or media";
                    $parent_uuid = $parent_media->parentUUID();
                    $parent_page = Pages::get($parent_uuid);
                    if (!$parent_page) return "Parent $parent_uuid is not a page";
                }
                $text = TextExtractor::extractFilestoreFile($file);
                if (!$text) return "No text extracted from $uuid";
                Search::indexURL(
                    $parent_page->uuid(),
                    $parent_page->url('filestore:' . $file->uuid()),
                    $file->filename(),
                    $text
                );
                return "Indexed file $uuid";
            },
            'search_index'
        );
    }

    public static function upload(string $src, string $filename, string $parent, array $meta, ?callable $permissions = null): FilestoreFile
    {
        $hash = md5_file($src);
        $dest = static::path($hash);
        FS::mkdir(dirname($dest));
        FS::copy($src, $dest, false, true);
        $file = new FilestoreFile(
            Digraph::uuid(),
            $hash,
            $filename,
            filesize($dest),
            $parent,
            $meta,
            time(),
            Session::user(),
            $permissions,
        );
        $file->write();
        static::insert($file);
        static::$cache[$file->uuid()] = $file;
        return $file;
    }

    public static function insert(FilestoreFile $file)
    {
        DB::query()
            ->insertInto(
                'filestore',
                [
                    'uuid' => $file->uuid(),
                    'hash' => $file->hash(),
                    'filename' => $file->filename(),
                    'bytes' => filesize($file->path()),
                    'parent' => $file->mediaUUID(),
                    'meta' => json_encode($file->meta()),
                    'created' => $file->created()->getTimestamp(),
                    'created_by' => $file->createdByUUID(),
                    'permissions' => $file->permissions() ? Serializer::serialize($file->permissions()) : null,
                ]
            )
            ->execute();
    }

    public static function path(string $hash): string
    {
        return Config::get('filestore.path') .
            '/' . substr($hash, 0, 2) .
            '/' . substr($hash, 2, 2) .
            '/' . $hash;
    }

    public static function url(string $uuid): string
    {
        return (new URL('/filestore/file:' . $uuid))
            ->__toString();
    }

    /**
     * Generate a FileSelect object for building queries to the file table
     *
     * @return FilestoreSelect
     */
    public static function select(): FilestoreSelect
    {
        return new FilestoreSelect(
            DB::query()->from('filestore')
        );
    }

    /**
     * Get the top result for a given UUID
     *
     * @param string|null $uuid
     * @return FilestoreFile|null
     */
    public static function get(?string $uuid): ?FilestoreFile
    {
        if ($uuid === null) return null;
        if (!isset(static::$cache[$uuid])) {
            static::$cache[$uuid] = self::doGet($uuid);
        }
        return static::$cache[$uuid];
    }

    protected static function doGet(string $uuid): ?FilestoreFile
    {
        $query = DB::query()->from('filestore')
            ->where('uuid = ?', [$uuid]);
        if ($result = $query->execute()->fetch()) {
            return static::resultToFile($result);
        } else {
            return null;
        }
    }

    public static function resultToFile($result): ?FilestoreFile
    {
        if (!is_array($result)) {
            return null;
        }
        if (isset(static::$cache[$result['uuid']])) {
            return static::$cache[$result['uuid']];
        }
        $data = json_decode($result['meta'], true, 512, JSON_THROW_ON_ERROR);
        if ($result['permissions']) {
            $permissions = Digraph::unserialize($result['permissions']);
            if (!is_callable($permissions)) {
                ExceptionLog::log(new Exception('Error unserializing permissions for file ' . $result['uuid']));
                $permissions = fn() => false;
            }
        } else {
            $permissions = null;
        }
        static::$cache[$result['uuid']] = new FilestoreFile(
            $result['uuid'],
            $result['hash'],
            $result['filename'],
            $result['bytes'],
            $result['parent'],
            $data,
            $result['created'],
            $result['created_by'],
            $permissions
        );
        return static::$cache[$result['uuid']];
    }
}
