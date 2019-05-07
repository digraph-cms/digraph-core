<?php
$noun = $package->noun();
$fs = $cms->helper('filestore');
$files = $fs->list($noun, $noun::FILESTORE_PATH);
if (!$files) {
    $cms->helper('notifications')->error(
        $cms->helper('strings')->string('file.notifications.nofile')
    );
    return;
}

$f = array_pop($files);
//display metadata page if requested, or if user can edit
if (!$noun['file.showpage'] && !$noun->isEditable()) {
    //there is a file, send it to the browser and finish
    $fs->output($package, $f);
    return;
}

//show notice for users who are only seeing metadata page because
//they have edit permissions
if (!$noun['file.showpage']) {
    $cms->helper('notifications')->notice(
        $cms->helper('strings')->string('file.notifications.editbypass')
    );
}

//printing the metacard here will put it above the body text
echo $f->metacard();
