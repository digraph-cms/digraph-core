<?php
$package->makeMediaFile('cron_result.json');
$package['response.ttl'] = $cms->config['cron.minttl'];
$CRON_OUTPUT = [
    'started' => date('r'),
    'hooks' => []
];

/*
 * Run cron hook files
 *
 * Hook files are the first line of how cron jobs get run, but are not the
 * preferred way for modules to add cron tasks. The first way a module should
 * add cron tasks is by adding a hook_cron() method to the helper related to
 * whatever cleanup task is required.
 *
 * Adding a hook file is only meant to be used for situations where a module
 * adds a cron task category of sorts, in which it needs to call other classes
 * that might be extended off of the module's base.
 */
foreach ($this->helper('routing')->allHookFiles('_cron', 'cron.php') as $file) {
    $CRON = [];
    include $file['file'];
    $CRON_OUTPUT['hooks'][$file['module']] = $CRON;
}

echo json_encode($CRON_OUTPUT, JSON_PRETTY_PRINT);

$package->saveLog('cron ran', 200);
