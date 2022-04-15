<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;

$job = DB::query()->from('defex')->where('id = ?', [Context::arg('id')])->fetch();

if (!$job) throw new HttpError(404, 'Job not found');

echo "<h1>Job #" . $job['id'] . "</h1>";

echo "<table>";

printf(
    '<tr><th>Group</th><td><a href="%s">%s</a> (%s jobs)</td></tr>',
    new URL('_inspect_group.html?id=' . $job['group']),
    $job['group'],
    Deferred::groupCount($job['group'])
);

printf(
    '<tr><th>Run at</th><td>%s</td></tr>',
    $job['run'] ? Format::datetime($job['run']) : '<em>pending</em>'
);

printf(
    '<tr><th>Message</th><td>%s</td></tr>',
    $job['message'] ? $job['message'] : '<em>none</em>'
);

printf(
    '<tr><th>Error</th><td>%s</td></tr>',
    $job['error'] ? '<strong>yes</strong>' : '<em>none</em>'
);

echo '<tr><th>Job</th><td>';
echo '<textarea style="width:40em;">' . htmlspecialchars($job['job']) . '</textarea>';
echo '</td></tr>';

echo "</table>";

$trim = function ($file) {
    $file = realpath($file);
    $base = realpath(Config::get('paths.base'));
    if (substr($file, 0, strlen($base)) == $base) {
        $file = substr($file, strlen($base));
    }
    return $file;
};

try {
    $trace = unserialize($job['trace']);
    Theme::addBlockingPageCss('/styles_fallback/*.css');
    echo '<h2>Origin stack trace</h2>';
    echo "<div class='stack-trace'>";
    foreach ($trace as $t) {
        echo "<div>";
        if (@$t['file']) {
            echo "<strong>" . htmlentities($trim(@$t['file'])) . ":" . @$t['line'] . "</strong><br>";
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
    }
    echo "</div>";
} catch (\Throwable $th) {
}