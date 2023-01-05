<h1>Exception log</h1>
<p>
    This log holds all recently-recorded PHP thrown exceptions/errors, and responses with HTTP status codes >= 500.
    By default exceptions are retained for 30 days.
    Exception logs contain all data submitted by the user in the request that led to the error, and may contain personally-identifiable information.
</p>
<?php

use DigraphCMS\Config;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

$path = Config::get('paths.storage') . '/exception_log';

$dayDirs = array_reverse(glob("$path/" . str_repeat('[0123456789]', 8), GLOB_ONLYDIR));
foreach ($dayDirs as $dayDir) {
    $date = DateTime::createFromFormat('Ymd', basename($dayDir));
    $files = array_reverse(glob("$dayDir/*.json"));
    echo '<h2>' . Format::date($date) . ' (' . count($files) . ')</h2>';
    echo new PaginatedTable(
        $files,
        function (string $path): array {
            $name = basename($path);
            $time = intval(explode(' ', $name)[0]);
            $data = json_decode(file_get_contents($path), true);
            $url = new URL($data['url']);
            return [
                Format::time($time),
                sprintf(
                    '<a href="%s">%s</a>',
                    new URL('log:' . explode('.', basename($path))[0]),
                    $data['thrown']['message']
                ),
                $url->fullPathString(),
                $data['_SERVER']['REMOTE_ADDR'],
                Users::user($data['user']),
            ];
        },
        [
            'Time',
            'Message',
            'URL',
            'IP',
            'User',
        ]
    );
}
