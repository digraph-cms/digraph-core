<?php
$package->noCache();

//run cron hooks
foreach ($this->helper('routing')->allHookFiles('_cron', 'cron.php') as $file) {
    include $file['file'];
}

$package->saveLog('cron ran', 200, 'cron ran');
