<h1>Manage Wayback link</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\ToggleButton;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\WaybackMachine;

echo '<div id="wayback_manage_interface" class="navigation-frame">';

$url = Context::arg('url');
if (Context::arg('context')) $context = new URL(Context::arg('context'));
else $context = null;

printf('<h2>Link: <code>%s</code></h2>', $url);

printf(
    '<p>Block all notifications about this link URL on any page %s</p>',
    new ToggleButton(
        WaybackMachine::noNotifyFlag($url),
        function () use ($url) {
            WaybackMachine::setNoNotifyFlag($url, null, true);
        },
        function () use ($url) {
            WaybackMachine::setNoNotifyFlag($url, null, false);
        }
    )
);

if ($context) {

    printf('<h2>Context: <code>%s</code></h2>', $context->pathString());

    printf(
        '<p>Block all notifications about this URL on this page only %s</p>',
        new ToggleButton(
            WaybackMachine::noNotifyFlag($url, $context),
            function () use ($url, $context) {
                WaybackMachine::setNoNotifyFlag($url, $context, true);
            },
            function () use ($url, $context) {
                WaybackMachine::setNoNotifyFlag($url, $context, false);
            }
        )
    );
}

echo "</div>";
