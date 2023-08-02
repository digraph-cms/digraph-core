<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Format;
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
    '<tr><th>Scheduled for</th><td>%s</td></tr>',
    $job['scheduled'] ? Format::datetime($job['scheduled']) : '<em>immediately</em>'
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