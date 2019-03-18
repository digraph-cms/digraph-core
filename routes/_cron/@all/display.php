<?php
$package->makeMediaFile('cron.json');
$package->noCache();
$cron = [];

//run cron hooks
foreach ($this->helper('routing')->allHookFiles('_cron', 'cron.php') as $file) {
    include $file['file'];
}

echo json_encode($cron, JSON_PRETTY_PRINT);

$package->saveLog('cron ran', 200, 'cron ran');
