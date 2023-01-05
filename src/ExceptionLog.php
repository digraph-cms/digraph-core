<?php

namespace DigraphCMS;

use DigraphCMS\Session\Session;
use Throwable;
use ZipArchive;

class ExceptionLog
{
    static function log(Throwable $th): void
    {
        // generate data that will be saved
        $path = Config::get('paths.storage') . '/exception_log/' . date('Ymd');
        $time = time();
        $uuid = Digraph::longUUID();
        $file = "$path/$time $uuid.json";
        $data = [
            'uuid' => $uuid,
            'time' => time(),
            'user' => Session::uuid(),
            'authid' => Session::authentication() ? Session::authentication()->id() : null,
            'url' => Context::request()->url()->__toString(),
            'original_url' => Context::request()->originalUrl()->__toString(),
            '_REQUEST' => $_REQUEST,
            '_SERVER' => $_SERVER,
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_FILES' => $_FILES,
            'thrown' => static::throwableArray($th)
        ];
        // save data
        FS::touch($file);
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        // save uploaded files as well
        if ($_FILES) {
            $zipFile = "$path/$time $uuid.zip";
            $zip = new ZipArchive();
            $zip->open($zipFile, ZipArchive::CREATE);
            $zip->addFromString('log.json', json_encode($data, JSON_PRETTY_PRINT));
            foreach ($_FILES as $file) {
                $zip->addFile($file['tmp_name'], 'files/' . $file['name']);
            }
            $zip->close();
        }
    }

    protected static function throwableArray(?Throwable $th): ?array
    {
        if (!$th) return null;
        return [
            'class' => get_class($th),
            'code' => $th->getCode(),
            'message' => $th->getMessage(),
            'file' => static::shortenPath($th->getFile()),
            'line' => $th->getLine(),
            'trace' => array_map(
                function (array $e): array {
                    if (@$e['file']) {
                        $e['file'] = static::shortenPath($e['file']);
                    }
                    return $e;
                },
                $th->getTrace()
            ),
            'previous' => static::throwableArray($th->getPrevious()),
        ];
    }

    protected static function shortenPath(string $path): string
    {
        return preg_replace('/^' . preg_quote(dirname(Config::get('paths.base'))) . '/i', '', $path);
    }
}
