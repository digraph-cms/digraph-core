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
if ($noun['file.showpage'] || $noun->isEditable()) {
    //show notice for users who are only seeing metadata page because
    //they have edit permissions
    if (!$noun['file.showpage']) {
        $cms->helper('notifications')->notice(
            $cms->helper('strings')->string('file.notifications.editbypass')
        );
    }
    echo $f->metacard();
    //dislay metadata page and return so that we skip outputting file
    return;
}
//there is a file, send it to the browser
$fs->output($package, $f);
