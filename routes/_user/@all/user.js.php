<?php
//make media file
$package->makeMediaFile('user.js');

//not cacheable, so that actions are unique to each user
$package->noCache();

//return empty file
if (!$this->helper('users')->id()) {
    return;
}

?>
/**
 * This file loads the current user, and is not cacheable. The idea is that
 * other scripts should be fully cacheable.
 */
digraph.user.id = "<?php echo $this->helper('users')->id(); ?>";
digraph.user.sid = "<?php echo $this->helper('session')->userSID(); ?>";
