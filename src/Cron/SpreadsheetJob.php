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
    public function __construct(string $srcFile, callable $rowFn = null, string $ext = null, string $group = null)
    {
        $group = $group ?? parent::uuid();
        $ext = strtolower($ext ?? pathinfo($srcFile, PATHINFO_EXTENSION) ?? '.xlsx');
        $cacheFile = Config::get('cache.path') . '/spreadsheet_jobs/' . $group . '.' . $ext;
        FS::touch($cacheFile);
        FS::copy($srcFile, $cacheFile, false, true);
        $function = function (DeferredJob $job) use ($cacheFile, $rowFn) {
            return static::prepareJobs($job, $cacheFile, $rowFn);
        };
        parent::__construct($function, $group);
    }

    public static function prepareJobs(DeferredJob $job, string $file, callable $rowFn): string
    {
        try {
            DB::beginTransaction();
            $filename = basename($file);
            $reader = IOFactory::createReaderForFile($file);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $data = $spreadsheet->getActiveSheet()->toArray(null,true,true,true);
                $header = null;
                foreach ($data as $rowNum => $row) {
                    // set up headers
                    if (!$header) {
                        foreach ($row->getCells() as $cell) {
                            $header[] = $cell->getValue();
                        }
                        continue;
                    }
                    // set up deferred job
                    $job->spawn(function () use ($rowFn, $row, $rowNum, $filename) {
                        return $rowFn($row, $rowNum) ?? "Processed $filename row $rowNum";
                    });
                }
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