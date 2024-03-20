<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Exception;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

Theme::addBlockingThemeCss('/styles_fallback/*.css');

$name = urldecode(Context::url()->actionSuffix());
$time = intval(explode(' ', $name)[0]);
$dayDir = Config::get('paths.storage') . '/exception_log/' . date('Ymd', $time);
$path = "$dayDir/$name.json";
$files = "$dayDir/$name.zip";

if (!file_exists($path)) throw new HttpError(404);

echo "<h1>Error logged " . Format::datetime($time) . "</h1>";

$data = json_decode(file_get_contents($path), true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

printf(
    '<a href="%s">View raw log file JSON</a>',
    new URL('json:' . $name)
);

if (!is_array($data)) {
    Notifications::printError('Failed to decode JSON file');
    return;
}

echo "<h2>Error message and trace</h2>";
displayThrownLogData($data['thrown']);

echo "<h2>User info</h2>";
if ($data['user'] !== 'guest') {
    $user = Users::user($data['user']);
    echo "<p>User: $user</p>";
    $query = DB::query()
        ->from('session')
        ->select('created, comment, ip, ua, expires, session_expiration.date as date, session_expiration.reason as reason')
        ->leftJoin('session_expiration on session_expiration.session_id = session.id')
        ->where('session.id = ?', [$data['authid']])
        ->order('session.created desc');
    echo new PaginatedTable(
        $query,
        function (array $row): array {
            return [
                Format::date($row['created']),
                $row['comment'],
                $row['ip'],
                Session::fullBrowser($row['ua']) . '<br><small>' . $row['ua'] . '</small>',
                Format::date($row['expires']),
                @$row['date'] ? Format::date($row['date']) : "<em>N/A</em>",
                @$row['reason'] ?? "<em>N/A</em>"
            ];
        },
        [
            'Signed in', 'Comment', 'IP', 'UA', 'Expiration', 'Deauthorized', 'Deauthorization reason'
        ]
    );
} else {
    echo "<h3>Users seen at this IP (" . $data['_SERVER']['REMOTE_ADDR'] . ")</h3>";
    $query = DB::query()
        ->from('session')
        ->select('session.user_uuid as user_uuid, created, comment, ip, ua, expires, session_expiration.date as date, session_expiration.reason as reason')
        ->leftJoin('session_expiration on session_expiration.session_id = session.id')
        ->where('session.ip = ?', [$data['_SERVER']['REMOTE_ADDR']])
        ->order('session.created desc');
    echo new PaginatedTable(
        $query,
        function (array $row): array {
            return [
                Users::user($row['user_uuid']),
                Format::date($row['created']),
                $row['comment'],
                $row['ip'],
                Session::fullBrowser($row['ua']) . '<br><small>' . $row['ua'] . '</small>',
                Format::date($row['expires']),
                @$row['date'] ? Format::date($row['date']) : "<em>N/A</em>",
                @$row['reason'] ?? "<em>N/A</em>"
            ];
        },
        [
            'User', 'Signed in', 'Comment', 'IP', 'UA', 'Expiration', 'Deauthorized', 'Deauthorization reason'
        ]
    );
    echo "<h3>Users seen with this UA</h3>";
    $query = DB::query()
        ->from('session')
        ->select('session.user_uuid as user_uuid, created, comment, ip, ua, expires, session_expiration.date as date, session_expiration.reason as reason')
        ->leftJoin('session_expiration on session_expiration.session_id = session.id')
        ->where('session.ua = ?', [$data['_SERVER']['HTTP_USER_AGENT']])
        ->order('session.created desc');
    echo new PaginatedTable(
        $query,
        function (array $row): array {
            return [
                Users::user($row['user_uuid']),
                Format::date($row['created']),
                $row['comment'],
                $row['ip'],
                Session::fullBrowser($row['ua']) . '<br><small>' . $row['ua'] . '</small>',
                Format::date($row['expires']),
                @$row['date'] ? Format::date($row['date']) : "<em>N/A</em>",
                @$row['reason'] ?? "<em>N/A</em>"
            ];
        },
        [
            'User', 'Signed in', 'Comment', 'IP', 'UA', 'Expiration', 'Deauthorized', 'Deauthorization reason'
        ]
    );
}

if (file_exists($files)) {
    echo "<h2>Uploaded files</h2>";
    Notifications::printWarning("This request included posted files for upload. You may download them as a ZIP file, but be warned that they are user-provided and their safety cannot be guaranteed.");
    printf(
        '<a href="%s">Download posted files</a>',
        new URL('files:' . $name)
    );
}

/**
 * @param array<string,mixed> $th
 * @return void
 */
function displayThrownLogData(array $th): void
{
    echo "<section class='stack-trace'>";
    // error message
    echo '<div class="error">';
    printf(
        '%s: <strong>%s</strong> (%s)',
        $th['class'],
        $th['message'],
        $th['code']
    );
    if (@$th['file']) {
        echo '<br>';
        echo $th['file'] . ':' . $th['line'];
    }
    echo '</div>';
    // attached data
    if ($th['class'] == Exception::class && $th['data'] !== null) {
        echo "<h3>Attached data:</h3>";
        echo "<pre>" . print_r($th['data'], true) . "</pre>";
    }
    // stack trace
    echo "<div class='stack-trace'>";
    foreach ($th['trace'] as $t) {
        echo "<div>";
        if (@$t['file']) {
            echo "<strong>" . htmlentities(@$t['file']) . ":" . @$t['line'] . "</strong><br>";
        }
        echo "<em>" . @$t['class'] . @$t['type'] . @$t['function'] . '()</em>';
        if (@$t['args']) {
            echo "<div class='trace-args'>";
            foreach ($t['args'] as $arg) {
                $arg = htmlentities(print_r($arg, true));
                if (strpos($arg, "\n")) {
                    $id = crc32(serialize([@$t['class'], @$t['type'], @$t['function'], $arg]));
                    echo "<div class=\"collapsible-multiline\" id=\"$id\">";
                    echo "<div id=\"$id-collapsed\">";
                    echo "<a class=\"expand\" href=\"#$id\">+</a>";
                    echo "<a class=\"collapse\" href=\"#$id-collapsed\">-</a>";
                    echo '&nbsp;' . $arg;
                    echo "</div>";
                    echo "</div>";
                } else {
                    echo "<div>$arg</div>";
                }
            }
            echo "</div>";
        }
        echo "</div>";
        // previous
        if ($th['previous']) {
            echo "<h3>Previous error:</h3>";
            displayThrownLogData($th['previous']);
        }
    }
    echo "</div>";
    echo "</section>";
}
