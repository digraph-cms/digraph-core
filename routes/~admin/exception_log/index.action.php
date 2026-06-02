<h1>Exception log</h1>
<p>
    This log holds all recently-recorded PHP thrown exceptions/errors, and responses with HTTP status codes >= 500.
    By default exceptions are retained for 30 days.
    Exception logs contain all data submitted by the user in the request that led to the error, and may contain
    personally-identifiable information.
</p>
<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Spreadsheets\CellWriters\DateTimeCell;
use DigraphCMS\Spreadsheets\CellWriters\UserCell;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

$path = Config::get('paths.storage') . '/exception_log';

$ip = Context::arg_string('ip', true);

if ($ip) {
    Notifications::printNotice("Limiting display to IP address $ip");
    Breadcrumb::setTopName("IP: " . htmlentities($ip, ENT_QUOTES));
    Breadcrumb::parent(new URL('./'));
}

$dayDirs = array_reverse(glob("$path/" . str_repeat('[0123456789]', 8), GLOB_ONLYDIR));
foreach ($dayDirs as $dayDir) {
    $date = DateTime::createFromFormat('Ymd', basename($dayDir));
    $files = array_reverse(glob("$dayDir/*.json"));
    echo '<h2>' . Format::date($date) . ' (' . count($files) . ')</h2>';
    $table = new PaginatedTable(
        $files,
        function (string $path) use ($ip): array|null {
            $name = basename($path);
            $time = intval(explode(' ', $name)[0]);
            $data = json_decode(file_get_contents($path), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            // filter to given ip if specified
            if ($ip && $data['_SERVER']['REMOTE_ADDR'] !== $ip)
                return null;
            // parse URL
            try {
                $url = new URL($data['url']);
            }
            catch (Throwable $th) {
                $url = $data['url'];
            }
            // return row
            return [
                Format::time($time),
                sprintf(
                    '<a href="%s">%s</a>',
                    new URL('log:' . explode('.', basename($path))[0]),
                    $data['thrown']['message'] ?: $data['thrown']['class']
                ),
                $url instanceof URL ? $url->fullPathString() : "<em>$url</em>",
                @$data['_SERVER']['REMOTE_ADDR']
                ? sprintf('<a href="%s">%s</a>', new URL('?ip=' . @$data['_SERVER']['REMOTE_ADDR']), @$data['_SERVER']['REMOTE_ADDR'])
                : '',
                Users::user($data['user']),
            ];
        },
        [
            'Time',
            'Message',
            'URL',
            'IP',
            'User',
        ],
    );
    $table->download(
        $date->format('Y-m-d') . ' exception log',
        function (string $path) {
            $name = basename($path);
            $time = intval(explode(' ', $name)[0]);
            $data = json_decode(file_get_contents($path), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);
            try {
                $url = new URL($data['url']);
            }
            catch (Throwable $th) {
                $url = $data['url'];
            }
            return [
                @$data['_SERVER']['REMOTE_ADDR'],
                new DateTimeCell($time),
                $data['thrown']['message'] ?: $data['thrown']['class'],
                $url instanceof URL ? $url->fullPathString() : "<em>$url</em>",
                new UserCell(Users::user($data['user'])),
            ];
        },
        [
            'IP',
            'Time',
            'Message',
            'URL',
            'User',
        ],
    );
    echo $table;
}
