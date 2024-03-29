<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;

error_reporting(0);

$group = Context::arg('group');
if (!$group || !Deferred::groupCount($group)) throw new HttpError(400);
$justRan = Deferred::runJobs($group, time() + 2);

Context::response()->browserTTL(2);
Context::response()->filename('dpb.json');

$pending = DB::query()->from('defex')->where('`group` = ?', [$group])
    ->where('run is null')
    ->count();

$completed = DB::query()->from('defex')->where('`group` = ?', [$group])
    ->where('run is not null')
    ->count();

echo json_encode([
    'group' => $group,
    'total' => $pending + $completed,
    'pending' => $pending,
    'completed' => $completed,
    'justran' => $justRan
]);
