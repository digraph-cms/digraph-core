<h1>Brightness/Color settings</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Theme;
use DigraphCMS\Users\Users;

Context::response()->setNoIndex();

if ($user = Users::current())
    Notifications::printConfirmation('You are signed in, so these settings will be saved to your account and persist across all browsers you sign in on.');
else
    Notifications::printNotice('You are not signed in, so these settings will be saved to a cookie specific to this web browser.');

if (Theme::colorMode() === null) {
    $auto = "<strong>automatic</strong>";
}
else {
    $auto = new CallbackLink(
        fn() => Theme::setColorMode(null),
    )
        ->setAttribute('data-target', '_frame')->setID('theme-auto')
        ->addChild('automatic');
}

if (Theme::colorMode() === 'light') {
    $light = '<strong>light</strong>';
}
else {
    $light = new CallbackLink(
        fn() => Theme::setColorMode('light'),
    )
        ->setAttribute('data-target', '_frame')->setID('theme-light')
        ->addChild('light');
}

if (Theme::colorMode() === 'dark') {
    $dark = '<strong>dark</strong>';
}
else {
    $dark = new CallbackLink(
        fn() => Theme::setColorMode('dark'),
    )
        ->setAttribute('data-target', '_frame')->setID('theme-dark')
        ->addChild('dark');
}

if (Theme::colorblindMode()) {
    $colorblind_on = '<strong>on</strong>';
    $colorblind_off = new CallbackLink(
        fn() => Theme::setColorblindMode(false),
    )
        ->setAttribute('data-target', '_frame')->setID('colorblind-off')
        ->addChild('off');
}
else {
    $colorblind_off = '<strong>off</strong>';
    $colorblind_on = new CallbackLink(
        fn() => Theme::setColorblindMode(true),
    )
        ->setAttribute('data-target', '_frame')->setID('colorblind-on')
        ->addChild('on');
}

echo "<div class='theme-menu navigation-frame navigation-frame--stateless' id='theme-menu'>";

echo "<div style='white-space:nowrap;'>";
echo "<h2>Theme brightness</h2>";
echo implode('&nbsp;|&nbsp;', [$auto, $light, $dark]);
echo "<h2>Color blind mode</h2>";
echo implode('&nbsp;|&nbsp;', [$colorblind_off, $colorblind_on]);

// also generate a script that sets the appropriate body classes on load
echo "<script>";
if (Theme::colorMode() == 'dark') {
    echo "document.body.classList.remove('colors--light');";
    echo "document.body.classList.add('colors--dark');";
}
elseif (Theme::colorMode() == 'light') {
    echo "document.body.classList.add('colors--light');";
    echo "document.body.classList.remove('colors--dark');";
}
else {
    echo "document.body.classList.remove('colors--light');";
    echo "document.body.classList.remove('colors--dark');";
}
if (Theme::colorblindMode()) {
    echo "document.body.classList.add('colors--colorblind');";
}
else {
    echo "document.body.classList.remove('colors--colorblind');";
}
echo "</script>";

echo '</div>';
echo "</div>";
