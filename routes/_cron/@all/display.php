<?php
$package->makeMediaFile('cron_result.json');
$package['response.ttl'] = 3600;
// $package->noCache();
$CRON_OUTPUT = [
    'started' => date('r'),
    'modules' => []
];

//run cron hooks
foreach ($this->helper('routing')->allHookFiles('_cron', 'cron.php') as $file) {
    $cron = [];
    include $file['file'];
    $CRON_OUTPUT['modules'][$file['module']] = $cron;
}

echo json_encode($CRON_OUTPUT, JSON_PRETTY_PRINT);

$package->saveLog('cron ran', 200, 'cron ran');
