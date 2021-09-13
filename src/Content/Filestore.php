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

    public static function upload(string $src, string $filename, string $page, array $meta)
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
            $page,
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
                    'page_uuid' => $file->pageUUID(),
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
     * @param string $uuid
     * @return User|null
     */
    public static function get(string $uuid): ?FilestoreFile
    {
        if (!isset(static::$cache[$uuid])) {
            static::$cache[$uuid] = self::doGet($uuid);
        }
        return static::$cache[$uuid];
    }


    protected static function doGet(string $uuid): ?FilestoreFile
    {
        $query = DB::query()->from('filestore')
            ->where('uuid = ?', [$uuid]);
        $result = $query->execute();
        if ($result && $result = $result->fetch()) {
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
        if (false === ($data = json_decode($result['meta'], true))) {
            throw new \Exception("Error decoding File json metadata");
        }
        static::$cache[$result['uuid']] = new FilestoreFile(
            $result['uuid'],
            $result['hash'],
            $result['filename'],
            $result['bytes'],
            $result['page_uuid'],
            $data,
            $result['created'],
            $result['created_by']
        );
        return static::$cache[$result['uuid']];
    }
}
