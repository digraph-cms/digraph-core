<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\FS;
use DigraphCMS\Spreadsheets\SpreadsheetReader;
use Exception;
use Throwable;

class SpreadsheetJob extends DeferredJob
{
    public function __construct(string $srcFile, callable $rowFn = null, string $ext = null, string $group = null, callable $setupFn = null, callable $teardownFn = null)
    {
        $group = $group ?? parent::uuid();
        $ext = strtolower($ext ?? pathinfo($srcFile, PATHINFO_EXTENSION) ?? '.xlsx');
        $cacheFile = Config::get('cache.path') . '/spreadsheet_jobs/' . $group . '.' . $ext;
        FS::touch($cacheFile);
        FS::copy($srcFile, $cacheFile, false, true);
        $function = function (DeferredJob $job) use ($cacheFile, $rowFn, $setupFn, $teardownFn) {
            return static::prepareJobs($job, $cacheFile, $rowFn, $setupFn, $teardownFn);
        };
        parent::__construct($function, $group);
    }

    public static function prepareJobs(DeferredJob $job, string $file, callable $rowFn, ?callable $setupFn, ?callable $teardownFn): string
    {
        try {
            DB::beginTransaction();
            $filename = basename($file);
            $count = 0;
            // spawn setupFn job if applicable
            if ($setupFn) {
                $count++;
                $job->spawn(function (DeferredJob $job) use ($setupFn, $file) {
                    return $setupFn($file, $job) ?? "Ran setup function";
                });
            }
            // spawn jobs for every row
            foreach (SpreadsheetReader::rows($file) as $i => $row) {
                $count++;
                $job->spawn(function (DeferredJob $job) use ($rowFn, $row, $filename, $i) {
                    return $rowFn($row, $job) ?? "$filename row $i";
                });
            }
            // spawn teardownFn job if applicable
            if ($teardownFn) {
                $count++;
                $job->spawn(function (DeferredJob $job) use ($teardownFn, $file) {
                    return $teardownFn($file, $job) ?? "Ran setup function";
                });
            }
            // spawn final job to clean up file
            $count++;
            $job->spawn(function () use ($file) {
                if (unlink($file)) return "Deleted temp file $file";
                else return "Failed to delete temp file $file";
            });
            // commit and return status
            DB::commit();
            return "Set up $count spreadsheet processing jobs";
        } catch (Throwable $th) {
            DB::rollback();
            throw new Exception("Error processing spreadsheet $file: " . get_class($th) . ":" . $th->getMessage());
        }
    }
}
