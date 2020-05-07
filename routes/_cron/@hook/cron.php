<?php
/*
 * The primary way of adding cron tasks is through helper hook_cron() methods.
 * Any module with a hook_cron() method will have it called here, and the return
 * value will be added to the JSON output.
 *
 * hook_cron()'s output should be an array, including at least the following
 * key/value pairs. Additional keys can exist, but may not display in admin
 * interfaces that don't specifically support that data.
 *   * 'result' => bool/int : indicates success/failure, or number of actions taken
 *   * 'errors' => array : an array of error messages (can be omitted if no error messages are available)
 *   * 'names' => array : an array of the names of what was operated on (can be omitted)
 */
$CRON['helpers'] = [];
foreach ($cms->allHelpers() as $name) {
    $h = $cms->helper($name);
    if (method_exists($h, 'hook_cron')) {
        $start = microtime(true);
        $result = $h->hook_cron();
        $time = round((microtime(true)-$start)*1000);
        if (@$result['result'] === 0) {
            $result['result'] = true;
        }
        $CRON['helpers'][$name] = [
            'result' => $result,
            'time' => $time
        ];
    }
}
