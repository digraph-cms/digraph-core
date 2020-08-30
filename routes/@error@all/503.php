<?php
$package['fields.page_name'] = $package['fields.page_title'] = $package['url.text'] = 'Temporarily unavailable';

if ($package['downtime']) {
    echo "<div class='digraph-card'>";
    echo '<h2>' . $package['downtime.title'] . '</h2>';
    if ($package['downtime.end']) {
        $cms->helper('notifications')->printNotice(
            'This outage is scheduled to end by '.
            $cms->helper('strings')->dateTimeHTML($package['downtime.end'])
        );
    }
    echo $package['downtime.message'];
    echo "</div>";
}
