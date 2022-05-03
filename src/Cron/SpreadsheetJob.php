<?php

namespace DigraphCMS\Cron;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\FS;
use Exception;
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
            $reader = ReaderEntityFactory::createReaderFromFile($file);
            $reader->open($file);
            foreach ($reader->getSheetIterator() as $sheetNum => $sheet) {
                $header = null;
                foreach ($sheet->getRowIterator() as $rowNum => $row) {
                    // set up headers
                    if (!$header) {
                        foreach ($row->getCells() as $cell) {
                            $header[] = $cell->getValue();
                        }
                        continue;
                    }
                    // convert row to array
                    $rowArray = [];
                    foreach ($row->getCells() as $i => $cell) {
                        $rowArray[$header[$i]] = $cell->getValue();
                    }
                    // set up deferred job
                    $job->spawn(function () use ($rowFn, $rowArray, $sheetNum, $rowNum) {
                        return $rowFn($rowArray, $sheetNum, $rowNum) ?? "Processed sheet $sheetNum row $rowNum";
                    });
                }
            }
            // final job to clean up file
            $job->spawn(function () use ($file) {
                if (unlink($file)) return "Deleted temp file $file";
                else return "Failed to delete temp file $file";
            });
            // close and commit
            $reader->close();
            DB::commit();
            return "Set up spreadsheet processing jobs";
        } catch (Throwable $th) {
            DB::rollback();
            throw new Exception("Error processing spreadsheet $file: " . get_class($th) . ":" . $th->getMessage());
        }
    }
}
