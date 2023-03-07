<?php

namespace DigraphCMS\Content;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\FS;
use DigraphCMS\Session\Session;

class Filestore
{
    protected static $cache = [];

    public static function delete(FilestoreFile $file): bool
    {
        // delete file
        if (!DB::query()
            ->delete('filestore')
            ->where('uuid = ?', [$file->uuid()])
            ->execute()) {
            return false;
        }
        // determine if any with this hash remain
        $unique = !DB::query()
            ->from('filestore')
            ->where('hash = ?', [$file->hash()])
            ->count();
        // delete file if was unique
        if ($unique) {
            FS::delete($file->src(), Config::get('filestore.path'));
        }
        return true;
    }

    public static function create(string $data, string $filename, string $parent, array $meta): FilestoreFile
    {
        $hash = md5($data);
        $dest = static::path($hash);
        FS::mkdir(dirname($dest));
        file_put_contents($dest, $data);
        $file = new FilestoreFile(
            Digraph::uuid(),
            $hash,
            $filename,
            filesize($dest),
            $parent,
            $meta,
            time(),
            Session::user()
        );
        $file->write();
        static::insert($file);
        static::$cache[$file->uuid()] = $file;
        return $file;
    }

    public static function upload(string $src, string $filename, string $parent, array $meta): FilestoreFile
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
            Session::user()
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
                    'bytes' => filesize($file->src()),
                    'parent' => $file->mediaUUID(),
                    'meta' => json_encode($file->meta()),
                    'created' => $file->created()->getTimestamp(),
                    'created_by' => $file->createdByUUID()
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
        static::$cache[$result['uuid']] = new FilestoreFile(
            $result['uuid'],
            $result['hash'],
            $result['filename'],
            $result['bytes'],
            $result['parent'],
            $data,
            $result['created'],
            $result['created_by']
        );
        return static::$cache[$result['uuid']];
    }
}
