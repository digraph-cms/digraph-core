<?php
//run helper hook_cron() methods
foreach ($cms->allHelpers() as $name) {
    $h = $cms->helper($name);
    if (method_exists($h, 'hook_cron')) {
        $start = microtime(true);
        $result = $h->hook_cron();
        $time = round((microtime(true)-$start)*1000);
        $cron['helper '.$name] = [
            'result' => $result,
            'time' => $time
        ];
    }
}
