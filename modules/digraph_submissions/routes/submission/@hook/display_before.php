<?php
if (!$package->noun()->isViewable()) {
    //deny access for those with no access
    $package->error(403);
}
//always make browser-side TTL 0
$package['response.browserttl'] = 0;

$n = $cms->helper('notifications');

$submission = $package->noun();
$parts = $submission->parts();
$chunks = $parts->chunks();

if (!$submission->complete()) {
    if ($submission->isEditable()) {
        $n->warning('This submission has not been fully completed yet. Please finish filling out any sections marked "incomplete."');
    } else {
        $n->error('Submission is currently incomplete.');
    }
}

if ($chunks) {
    //determine if we're in edit mode
    $editMode = false;
    if ($submission->isEditable()) {
        if (!$submission->complete()) {
            $editMode = true;
        } elseif ($package['url.args.edit']) {
            $editMode = true;
        }
    }
    //display link to enter edit mode if necessary
    if ($submission->isEditable() && !$editMode) {
        $url = $package->url();
        $url['args.edit'] = true;
        echo "<p><a href='$url' class='cta-button'>Edit submission</a></p>";
    } elseif ($submission->isEditable()) {
        $url = $package->url();
        unset($url['args.edit']);
        echo "<p><a href='$url' class='cta-button'>Exit editing mode</a></p>";
    }
    //set cache based on edit mode
    if ($editMode) {
        $package->noCache();
    } else {
        $package['response.ttl'] = 300;
    }
    //output chunks
    echo "<div id='submission-chunks'>";
    foreach ($chunks as $cname => $chunk) {
        if (!$editMode) {
            //place chunk body directly on the page if editing is open
            echo $chunk->body(true);
        } else {
            //otherwise include an iframe to it
            $url = $submission->url('chunk', ['chunk'=>$cname], true);
            echo "<iframe src='$url' class='embedded-iframe'></iframe>";
        }
    }
    echo "</div>";
}
