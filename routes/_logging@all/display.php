<?php
if (!$cms->helper('logging')->monolog()) {
    $cms->helper('notifications')->warning(
        $cms->helper('strings')->string('logging.nomonolog')
    );
}

$l = $cms->helper('logging');
$logs = $l->list();

?>
<style>
tr.log-entry,
tr.log-entry a,
tr.log-entry a:hover {
    color:#fff;
}
tr.log-level-DEBUG {
    background-color:#3B3B3B;
}
tr.log-level-INFO,
tr.log-level-NOTICE {
    background-color:#1C528D;
}
tr.log-level-WARNING {
    background-color:#ffbc00;
}
tr.log-level-ERROR {
    background-color:#ff7c00;
}
tr.log-level-CRITICAL,
tr.log-level-ALERT {
    background-color:#ff2200;
}
tr.log-level-EMERGENCY,
tr.log-level-UNKNOWN {
    background-color:#e500ff;
}
</style>
<table>
    <thead>
        <tr>
            <th>Log</th>
            <th>HTTP</th>
            <th>Count</th>
            <th>Request</th>
        </tr>
    </thead>
<?php
foreach ($logs as $log) {
    echo "<tr class='log-entry log-level-".$log->level()."'>";
    echo "<td><a href='".$log->url()."'>".$log->name()."</a></td>";
    echo "<td>".$log['package.response.status']."</td>";
    echo "<td>".$log['count']."</td>";
    echo "<td style='width:25%;'><div style='max-width:10em;overflow:hidden;white-space:nowrap;'>".$log['package.request.url']."</div></td>";
    echo "</tr>";
}
?>
</table>
