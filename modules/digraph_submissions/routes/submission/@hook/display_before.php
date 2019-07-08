<?php
if (!$package->noun()->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}
//always make browser-side TTL 0
$package['response.ttl'] = 10;
$package['response.browserttl'] = 0;

$n = $cms->helper('notifications');

$submission = $package->noun();
$parts = $submission->parts();
$chunks = $parts->chunks();

if ($submission->complete()) {
    $n->confirmation('Submission complete.');
} else {
    if ($submission->isEditable()) {
        $n->warning('This submission has not been fully completed yet. Please finish filling out any sections marked "incomplete."');
    } else {
        $n->error('Submission is currently incomplete.');
    }
}

if ($chunks) {
    echo "<div id='submission-chunks'>";
    foreach ($chunks as $cname => $chunk) {
        if (!$submission->isEditable()) {
            //place chunk body directly on the page if editing is open
            echo $chunk->body();
        } else {
            //otherwise include an iframe to it
            $url = $submission->url('chunk', ['chunk'=>$cname], true);
            echo "<iframe src='$url' class='embedded-iframe'></iframe>";
        }
    }
    echo "</div>";
}
