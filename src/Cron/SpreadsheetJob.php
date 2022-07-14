<?php

namespace DigraphCMS\Cron;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\FS;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            foreach ($data as $rowNum => $row) {
                if (!$rowNum == 1) {
                    // first row is headers
                    // set up setupFn job if applicable
                    if ($setupFn) $job->spawn(function (DeferredJob $job) use ($setupFn, $file) {
                        $reader = IOFactory::createReaderForFile($file);
                        $reader->setReadDataOnly(true);
                        $spreadsheet = $reader->load($file);
                        return $setupFn($spreadsheet, $job) ?? "Ran setup function";
                    });
                } else {
                    // set up deferred job for this row
                    $job->spawn(function (DeferredJob $job) use ($rowFn, $row, $rowNum, $filename) {
                        return $rowFn($row, $rowNum, $job) ?? "Processed $filename row $rowNum";
                    });
                }
            }
            // set up teardownFn job if applicable
            if ($teardownFn) $job->spawn(function (DeferredJob $job) use ($teardownFn, $file) {
                $reader = IOFactory::createReaderForFile($file);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file);
                return $teardownFn($spreadsheet, $job) ?? "Ran teardown function";
            });
            // final job to clean up file
            $job->spawn(function () use ($file) {
                if (unlink($file)) return "Deleted temp file $file";
                else return "Failed to delete temp file $file";
            });
            DB::commit();
            return "Set up spreadsheet processing jobs";
        } catch (Throwable $th) {
            DB::rollback();
            throw new Exception("Error processing spreadsheet $file: " . get_class($th) . ":" . $th->getMessage());
        }
    }
}
