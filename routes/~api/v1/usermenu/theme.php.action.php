<?php

use DigraphCMS\UI\Theme;
use DigraphCMS\UI\ToggleButton;

$auto = new ToggleButton(
    Theme::colorMode() === null,
    fn() => Theme::setColorMode(null),
    fn() => Theme::setColorMode('light'),
    true,
);

$darkmode = new ToggleButton(
    Theme::colorMode() === 'dark',
    fn() => Theme::setColorMode('dark'),
    fn() => Theme::setColorMode('light'),
    true,
);

$colorblind = new ToggleButton(
    !!Theme::colorblindMode(),
    fn() => Theme::setcolorblindMode(true),
    fn() => Theme::setcolorblindMode(false),
    true,
);

echo "<div class='theme-menu navigation-frame navigation-frame--stateless' id='theme-menu'>";

echo "<h1 style='white-space:nowrap;'>Color settings</h1>";
echo "<h2 style='white-space:nowrap;'>Automatic dark/light mode</h2>";
echo $auto;
if (Theme::colorMode() !== null) {
    echo "<h3 style='white-space:nowrap;'>Dark mode</h3>";
    echo $darkmode;
}
echo "<h2 style='white-space:nowrap;'>Color blindness mode</h2>";
echo $colorblind;

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

echo "</div>";
