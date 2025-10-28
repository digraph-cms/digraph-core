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
    static function logMessage(string $message, mixed $data = null, Throwable|null $previous = null): void
    {
        static::log(
            new Exception(
                $message,
                $data,
                $previous
            )
        );
    }

    static function log(Throwable $th, bool $no_email = false): void
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
                'url' => static::actualUrl(),
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
                'url' => static::actualUrl(),
                '_REQUEST' => $_REQUEST,
                '_SERVER' => $_SERVER,
                '_GET' => $_GET,
                '_POST' => $_POST,
                '_FILES' => $_FILES,
                'thrown' => static::throwableArray($th)
            ];
        }
        // transcode arrays entirely into UTF-8
        $data = static::transcodeArray($data);
        // send email if lock isn't exceeded
        $hash = md5(serialize([
            static::actualUrl(),
            get_class($th),
            $th->getCode(),
            $th->getFile(),
            $th->getLine(),
            $th->getMessage(),
        ]));
        if (!$no_email) {
            RateLimit::run(
                'exception_notification',
                $hash,
                Config::get('exception_log.notify_frequency'),
                function () use ($th, $time, $uuid, $path) {
                    foreach (Config::get('exception_log.notify_emails') as $address) {
                        $subject = substr(implode(' ', [
                            'Site Error:',
                            $th->getMessage(),
                            Context::url(),
                        ]), 0, 250);
                        $count = count(glob("$path/*.json"));
                        $body = implode('<br>', [
                            sprintf(
                                '<a href="%s">A new error</a> has been logged at <kbd>%s</kbd>',
                                new URL("/admin/exception_log/log:$time $uuid"),
                                static::actualUrl()
                            ),
                            sprintf(
                                'Error message: %s',
                                $th->getMessage(),
                            ),
                            sprintf(
                                'As of %s there %s been <a href="%s">%s other error%s logged today</a>',
                                Format::time(time()),
                                $count == 1 ? 'has' : 'have',
                                new URL('/admin/exception_log/'),
                                number_format($count),
                                $count == 1 ? '' : 's'
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
                            $th->getMessage();
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
        }
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
                if (is_array($file['tmp_name'])) {
                    foreach ($file as $f) {
                        if (!$f['tmp_name']) continue;
                        if (!file_exists($f['tmp_name'])) continue;
                        $zip->addFile($f['tmp_name'], 'files/' . $f['name']);
                    }
                } else {
                    if (!$file['tmp_name']) continue;
                    if (!file_exists($file['tmp_name'])) continue;
                    $zip->addFile($file['tmp_name'], 'files/' . $file['name']);
                }
            }
            $zip->close();
        }
    }

    /**
     * Recursively transcode all strings in an array to UTF-8, so that things
     * don't break when we try to save all this data into a JSON file.
     */
    protected static function transcodeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::transcodeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = static::transcodeString($value);
            }
        }
        return $data;
    }

    protected static function transcodeString(string $string): string
    {
        // convert to UTF-8 if not already
        if (mb_detect_encoding($string, 'UTF-8', true) === false) {
            $string = mb_convert_encoding($string, 'UTF-8');
        }
        // remove any non-UTF-8 characters
        return preg_replace('/[^\x{0000}-\x{FFFF}]/u', '', $string);
    }

    /**
     * @param Throwable|null $th
     *
     * @return array<string,mixed>|null
     */
    protected static function throwableArray(?Throwable $th): ?array
    {
        if (!$th) return null;
        return [
            'class' => get_class($th),
            'code' => $th->getCode(),
            'data' => $th instanceof Exception ? $th->data() : null,
            'message' => $th->getMessage(),
            'file' => $th->getFile(),
            'line' => $th->getLine(),
            'trace' => array_map(
                function (array $e): array {
                    if (@$e['file']) {
                        $e['file'] = static::shortenPath($e['file']);
                    }
                    return $e;
                },
                $th->getTrace(),
            ),
            'previous' => $th->getPrevious(),
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

    /**
     * @return string get the actual URL of the current request, from outside the CMS, sans query string
     */
    protected static function actualUrl(): string
    {
        return sprintf(
            '%s://%s%s',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI']
        );
    }
}