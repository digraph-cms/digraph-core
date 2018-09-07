<?php
$package['response.cacheable'] = false;

//form setup
$form = new Formward\Form(
    $this->helper('lang')->string('forms.confirm_delete_title', [$package->noun()->name()])
);

//noun ID confirmation field
$form['id'] = new Formward\Fields\Input(
    $this->helper('lang')->string('forms.confirm_delete_field', [$package->noun()['dso.id']])
);
$form['id']->addValidatorFunction('match', function (&$field) {
    if ($field->value() != $this->package->noun()['dso.id']) {
        return $this->helper('lang')->string('forms.confirm_delete_match_error');
    }
    return true;
});

//submit button
$form->submitButton()->label($this->helper('lang')->string('forms.confirm_button'));

//do deletion
if ($form->handle()) {
    $package->noun()->delete();
    $package->cms()->helper('notifications')->confirmation(
        $this->helper('lang')->string('forms.confirm_delete_confirmation')
    );
}

echo $form;
