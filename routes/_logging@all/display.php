<?php
if (!$cms->helper('log')->monolog()) {
    $cms->helper('notifications')->warning(
        $cms->helper('strings')->string('logging.nomonolog')
    );
}
