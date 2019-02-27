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
tr.log-entry a {
    color:#fff;
}
tr.log-level-ERROR {
    background-color:#f00;
}
</style>
<table>
    <thead>
        <tr>
            <th>Log</th>
            <th>Count</th>
            <th>Request</th>
        </tr>
    </thead>
<?php
foreach ($logs as $log) {
    echo "<tr class='log-entry log-level-".$log->level()."'>";
    echo "<td><a href='".$log->url()."'>".$log->name()."</a></td>";
    echo "<td>".$log['count']."</td>";
    echo "<td style='width:25%;'><div style='max-width:10em;overflow:hidden;white-space:nowrap;'>".$log['package.request.url']."</div></td>";
    echo "</tr>";
}
?>
</table>
