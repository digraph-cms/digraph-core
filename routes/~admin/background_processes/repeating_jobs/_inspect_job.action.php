<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Format;

$job = DB::query()->from('cron')->where('id = ?', [Context::arg('id')])->fetch();

if (!$job) throw new HttpError(404, 'Job not found');

echo "<h1>Job #" . $job['id'] . "</h1>";
echo "<h2>" . $job['name'] . "</h2>";

echo "<table>";

printf(
    '<tr><th>Parent</th><td>%s</td></tr>',
    $job['parent']
);

printf(
    '<tr><th>Last run</th><td>%s</td></tr>',
    $job['run_last'] ? Format::datetime($job['run_last']) : '<em>pending</em>'
);

printf(
    '<tr><th>Next run</th><td>%s</td></tr>',
    $job['run_next'] ? Format::datetime($job['run_next']) : '<em>none</em>'
);

printf(
    '<tr><th>Last error</th><td>%s</td></tr>',
    $job['error_message'] ?? '<em>none</em>'
);

printf(
    '<tr><th>Last error time</th><td>%s</td></tr>',
    $job['error_time'] ? Format::datetime($job['error_time']) : '<em>none</em>'
);

echo '<tr><th>Job</th><td>';
echo '<textarea style="width:40em;">' . htmlspecialchars($job['job']) . '</textarea>';
echo '</td></tr>';

echo "</table>";
