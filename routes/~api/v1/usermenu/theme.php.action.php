<?php

use DigraphCMS\HTML\DIV;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Theme;

$mode = (new DIV)->addClass('button-menu');
$mode->addChild(
    (new CallbackLink(function () {
        Theme::setColorMode(null);
    }))
        ->addChild('Auto')
        ->addClass('button')
        ->addClass(Theme::colorMode() === null ? 'button--interactive' : 'button--neutral')
);
$mode->addChild(
    (new CallbackLink(function () {
        Theme::setColorMode('dark');
    }))
        ->addChild('Dark')
        ->addClass('button')
        ->addClass(Theme::colorMode() !== 'dark' ? 'button--neutral' : 'button--interactive')
);
$mode->addChild(
    (new CallbackLink(function () {
        Theme::setColorMode('light');
    }))
        ->addChild('Light')
        ->addClass('button')
        ->addClass(Theme::colorMode() !== 'light' ? 'button--neutral' : 'button--interactive')
);

$colorblind = (new DIV)->addClass('button-menu');
$colorblind->addChild(
    (new CallbackLink(function () {
        Theme::setColorblindMode(true);
    }))
        ->addChild('On')
        ->addClass('button')
        ->addClass(Theme::colorblindMode() === true ? 'button--interactive' : 'button--neutral')
);
$colorblind->addChild(
    (new CallbackLink(function () {
        Theme::setColorblindMode(false);
    }))
        ->addChild('Off')
        ->addClass('button')
        ->addClass(Theme::colorblindMode() !== true ? 'button--interactive' : 'button--neutral')
);

echo "<div class='theme-menu navigation-frame navigation-frame--stateless' id='theme-menu'>";

echo "<h1>Color settings</h1>";
echo "<h2>Dark/light mode</h2>";
echo $mode;
echo "<h2>Colorblind mode</h2>";
echo $colorblind;

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

echo "</div>";
