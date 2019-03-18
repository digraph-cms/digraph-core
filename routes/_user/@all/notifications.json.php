<?php
//make media file
$package->makeMediaFile('notifications.json');

//make it non-cacheable
$package->noCache();

//pull flashes from notifications helper
echo json_encode($this->helper('notifications')->flashes());
