<h1>Color preferences</h1>
<?php

use DigraphCMS\HTML\Forms\Fields\RadioListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Theme;
use DigraphCMS\Users\Users;

// inform user of how settings will be saved
if ($user = Users::current()) {
    Notifications::printNotice('You are signed in. Settings will be saved to your account and remembered across all devices.');
    Breadcrumb::parent($user->profile());
} else {
    Cookies::required(['ui']);
    Notifications::printNotice('You are not signed in. Settings will be saved to a cookie on this computer only.');
}

$mode = (new RadioListField('Preferred color mode', [
    'default' => 'Default: Auto detect color settings from your browser',
    'dark' => 'Dark mode',
    'light' => 'Light mode'
]))->setRequired(true)
    ->setDefault(Theme::colorMode() ?? 'default');

$colorblind = (new RadioListField('Colorblind mode', [
    'off' => 'Off: use default colors',
    'on' => 'On: attempt to use colors that are more distinguishable for common forms of colorblindness'
]))->setRequired(true)
    ->setDefault(Theme::colorblindMode() ? 'on' : 'off');

$form = (new FormWrapper())
    ->addChild($mode)
    ->addChild($colorblind)
    ->addCallback(function () use ($mode, $colorblind) {
        $modeValue = $mode->value() == 'default' ? null : $mode->value();
        $colorblindValue = $colorblind->value() == 'on' ? true : false;
        if ($user = Users::current()) {
            if ($modeValue === null) {
                unset($user['ui.colormode']);
            } else {
                $user['ui.colormode'] = $modeValue;
            }
            $user['ui.colorblindmode'] = $colorblindValue;
            $user->update();
        } else {
            Cookies::set('ui', 'color', [
                'color' => $modeValue,
                'colorblindmode' => $colorblindValue
            ]);
        }
        throw new RefreshException();
    });

$form->token()->setCSRF(false);
$form->button()->setText('Save color settings');

echo $form;
