<?php
$noun = $package->noun();
$url = $noun->linkUrl(null, $package['url.args']);

if (!$url) {
    $cms->helper('notifications')->error(
        $cms->helper('strings')->string('link.notifications.nourl')
    );
    return;
}

//display metadata page if requested, or if user can edit
if ($noun['link.showpage'] || $noun->isEditable()) {
    //show notice for users who are only seeing metadata page because
    //they have edit permissions
    if (!$noun['link.showpage']) {
        $cms->helper('notifications')->notice(
            $cms->helper('strings')->string('link.notifications.editbypass')
        );
    }
}

if (!$noun['link.showpage'] && !$noun->isEditable()) {
    $package->redirect($url);
    return;
}

?>

<h2><?php echo $this->helper('strings')->string('link.explanation'); ?></h2>
<p><a href="<?php echo $url; ?>"><?php echo $url; ?></a></p>
