<h1>Autocomplete field in frame</h1>
<p>This page verifies that autocompletes inside frames work properly.</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\UserField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\UI\Notifications;

echo '<div class="navigation-frame" id="ac-test-frame">';

echo '<p><a href="' . Context::url() . '" data-target="_frame">Reload frame, does it still work after?</a>';

$form = new FormWrapper();
$form->button()->setText('Does nothing');

$ac = (new UserField('User'))
    ->addForm($form);

if ($form->ready()) {
    Notifications::printConfirmation('Selected ' . $ac->value());
}

echo $form;

echo '</div>';
