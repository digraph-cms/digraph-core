<?php

namespace DigraphCMS;

use DigraphCMS\Cache\RateLimit;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
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
        if (Context::request()) {
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
        } else {
            $data = [
                'uuid' => $uuid,
                'time' => time(),
                'user' => Session::uuid(),
                'authid' => Session::authentication() ? Session::authentication()->id() : null,
                'url' => '[no Context::request()]',
                'original_url' => '[no Context::request()]',
                '_REQUEST' => $_REQUEST,
                '_SERVER' => $_SERVER,
                '_GET' => $_GET,
                '_POST' => $_POST,
                '_FILES' => $_FILES,
                'thrown' => static::throwableArray($th)
            ];
        }
        // send email if lock isn't exceeded
        $hash = md5(serialize([
            get_class($th),
            method_exists($th, 'getCode') ? $th->getCode() : null,
            method_exists($th, 'getFile') ? $th->getFile() : null,
            method_exists($th, 'getLine') ? $th->getLine() : null,
            method_exists($th, 'getMessage') ? $th->getMessage() : null,
        ]));
        RateLimit::run(
            'exception_notification',
            $hash,
            Config::get('exception_log.notify_frequency'),
            function () use ($th, $time, $uuid, $path) {
                foreach (Config::get('exception_log.notify_emails') as $address) {
                    $subject = substr(implode(' ', [
                        'Site Error:',
                        method_exists($th, 'getMessage') ? $th->getMessage() : get_class($th),
                        Context::url(),
                    ]), 0, 250);
                    $body = implode('<br>', [
                        sprintf(
                            '<a href="%s">A new error</a> has been logged at <a href="%s">%s</a>',
                            new URL("/admin/exception_log/log:$time $uuid"),
                            Context::url(),
                            Context::url()
                        ),
                        sprintf(
                            'Error message: %s',
                            method_exists($th, 'getMessage') ? $th->getMessage() : 'No message: ' . get_class($th)
                        ),
                        sprintf(
                            'As of %s there have been <a href="%s">%s other errors logged today</a>',
                            Format::time(time()),
                            new URL('/admin/exception_log/'),
                            count(glob("$path/*.json"))
                        )
                    ]);
                    $sent = false;
                    try {
                        // try to send mail using proper system
                        Emails::send(
                            $msg = Email::newForEmail('service', $address, $subject, new RichContent($body))
                        );
                        if ($msg->error()) {
                            $body .= '<br>Additional email system error: ' . $msg->error();
                            $sent = false;
                        } else {
                            $sent = true;
                        }
                    } catch (Throwable $th) {
                        $sent = false;
                        $body .= '<br>Additional email system error: ' . get_class($th);
                        if (method_exists($th, 'getMessage')) $body .= '<br>Message: ' . $th->getMessage();
                    }
                    // fall back to trying to use mail() function
                    if (!$sent) {
                        $headers = "MIME-Version: 1.0" . "\r\n";
                        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                        mail($address, $subject, $body, $headers);
                    }
                }
            }
        );
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
                if (!$file['tmp_name']) continue;
                if (!file_exists($file['tmp_name'])) continue;
                $zip->addFile($file['tmp_name'], 'files/' . $file['name']);
            }
            $zip->close();
        }
    }

    /**
     * @param Throwable|null $th
     * @return array<string,mixed>|null
     */
    protected static function throwableArray(?Throwable $th): ?array
    {
        if (!$th) return null;
        return [
            'class' => get_class($th),
            'code' => method_exists($th, 'getCode') ? $th->getCode() : null,
            'data' => $th instanceof Exception ? $th->data() : null,
            'message' => method_exists($th, 'getMessage') ? $th->getMessage() : null,
            'file' => method_exists($th, 'getFile') ? static::shortenPath($th->getFile()) : null,
            'line' => method_exists($th, 'getLine') ? $th->getLine() : null,
            'trace' => array_map(
                function (array $e): array {
                    if (@$e['file']) {
                        $e['file'] = static::shortenPath($e['file']);
                    }
                    return $e;
                },
                method_exists($th, 'getTrace') ? $th->getTrace() : []
            ),
            'previous' => method_exists($th, 'getPrevious') ? static::throwableArray($th->getPrevious()) : null,
        ];
    }

    protected static function shortenPath(string $path): string
    {
        $base = dirname(Config::get('paths.base'));
        if (substr($path, 0, strlen($base)) == $base) {
            return substr($path, strlen($base));
        } else {
            return $path;
        }
    }
}
