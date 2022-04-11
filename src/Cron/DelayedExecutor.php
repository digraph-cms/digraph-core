<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\FS;
use Exception;

class DelayedExecutor
{
    protected static $lockFailed = [];

    public static function run(int $endByTime = null)
    {
        // make sure necessary lock files directory exists
        FS::mkdir(Config::get('cache.path') . '/delex_locks/');
        // proceed with jobs one at a time
        while ($job = static::getNextJob()) {
            // create if necessary and obtain exclusive lock for job file
            $lockFile = Config::get('cache.path') . '/delex_locks/' . $job['id'];
            touch($lockFile);
            $lock = fopen($lockFile, 'r');
            if (!flock($lock, LOCK_EX)) {
                static::$lockFailed[] = $job['id'];
                fclose($lock);
                continue;
            }
            // try to execute a single job, if a lock can be obtained for it
            try {
                // check for expiration
                if ($job['expires'] && time() >= $job['expires']) throw new Exception('Job expired');
                // unserialize and execute
                $runner = unserialize($job['runner']);
                $runner->run();
                // record success in table
                DB::query()->update(
                    'delex',
                    [
                        'executed' => time(),
                        'error' => false
                    ],
                    $job['id']
                )->execute();
            } catch (\Throwable $th) {
                // record exception in table
                DB::query()->update(
                    'delex',
                    [
                        'executed' => time(),
                        'error' => true,
                        'error_message' => get_class($th) . ': ' . $th->getMessage()
                    ],
                    $job['id']
                )->execute();
            }
            // release and delete file lock
            flock($lock, LOCK_UN);
            fclose($lock);
            unlink($lockFile);
            // break loop if time limit has been reached
            if ($endByTime && time() >= $endByTime) break;
        }
    }

    protected static function getNextJob()
    {
        $query = DB::query()->from('delex')
            ->where('executed is null')
            ->where('scheduled <= ?', [time()])
            ->order('expires ASC, id ASC')
            ->limit(1);
        if (static::$lockFailed) {
            $query->where('id NOT IN ?', [static::$lockFailed]);
        }
        return $query->fetch();
    }
}
