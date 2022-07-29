<?php

use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;
use DigraphCMS\UI\Theme;

$mode = new ButtonMenu('Color mode', [
    new ButtonMenuButton(
        'Auto',
        function () {
            Theme::setColorMode(null);
        },
        [Theme::colorMode() === null ? 'button--interactive' : 'button--neutral']
    ),
    new ButtonMenuButton(
        'Dark',
        function () {
            Theme::setColorMode('dark');
        },
        [Theme::colorMode() !== 'dark' ? 'button--neutral' : 'button--interactive']
    ),
    new ButtonMenuButton(
        'Light',
        function () {
            Theme::setColorMode('light');
        },
        [Theme::colorMode() !== 'light' ? 'button--neutral' : 'button--interactive']
    )
]);

$colorblind = new ButtonMenu('Colorblind mode', [
    new ButtonMenuButton(
        'On',
        function () {
            Theme::setcolorblindMode(true);
        },
        [Theme::colorblindMode() === true ? 'button--interactive' : 'button--neutral']
    ),
    new ButtonMenuButton(
        'Off',
        function () {
            Theme::setcolorblindMode(false);
        },
        [Theme::colorblindMode() !== true ? 'button--interactive' : 'button--neutral']
    )
]);

echo "<div class='theme-menu navigation-frame navigation-frame--stateless' id='theme-menu'>";
echo "<h1>Color settings</h1>";
echo "<h2>Dark/light mode</h2>";
echo $mode;
echo "<h2>Colorblind mode</h2>";
echo $colorblind;
echo "</div>";

// also generate a script that sets the appropriate body classes on load
echo "<script>";
if (Theme::colorMode() == 'dark') {
    echo "document.body.classList.remove('colors--light');";
    echo "document.body.classList.add('colors--dark');";
} elseif (Theme::colorMode() == 'light') {
    echo "document.body.classList.add('colors--light');";
    echo "document.body.classList.remove('colors--dark');";
} else {
    echo "document.body.classList.remove('colors--light');";
    echo "document.body.classList.remove('colors--dark');";
}
if (Theme::colorblindMode()) {
    echo "document.body.classList.add('colors--colorblind');";
} else {
    echo "document.body.classList.remove('colors--colorblind');";
}
echo "</script>";
