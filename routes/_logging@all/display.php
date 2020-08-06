<?php
$l = $cms->helper('logging');
$logs = $l->list();

?>
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
$classes = [
    'INFO' => 'highlighted-notice',
    'NOTICE' => 'highlighted-notice',
    'WARNING' => 'highlighted-warning',
    'ERROR' => 'highlighted-error',
    'CRITICAL' => 'highlighted-error',
    'EMERGENCY' => 'highlighted-error',
];
foreach ($logs as $log) {
    echo "<tr class='log-entry log-level-".$log->level()." ".@$classes[$log->level()]."'>";
    echo "<td><a href='".$log->url()."'>".$log->name()."</a></td>";
    echo "<td>".$log['package.response.status']."</td>";
    echo "<td>".$log['count']."</td>";
    echo "<td style='width:25%;'><div style='max-width:10em;overflow:hidden;white-space:nowrap;'>".$log['package.request.url']."</div></td>";
    echo "</tr>";
}
?>
</table>
