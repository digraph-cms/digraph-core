<?php
if (!$package['noun.url']) {
    $cms->helper('notifications')->error(
        $cms->helper('strings')->string('link.notifications.nourl')
    );
    return;
}

$noun = $package->noun();

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
    $package->redirect($noun['url']);
    return;
}

?>

<h2><?php echo $this->helper('strings')->string('link.explanation'); ?></h2>
<p><a href="<?php echo $noun['url']; ?>"><?php echo $noun['url']; ?></a></p>
