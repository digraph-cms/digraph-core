<?php
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;
$s = $this->helper('strings');

//form setup
$form = new Formward\Form(
    $s->string('forms.delete.confirm_form_title', [$package->noun()->name()])
);

//noun ID confirmation field
$form['id'] = new Formward\Fields\Input(
    $s->string('forms.delete.confirm_field_label', [$package->noun()['dso.id']])
);
$form['id']->required(true);
$form['id']->addValidatorFunction('match', function (&$field) use ($s) {
    if ($field->value() != $this->package->noun()['dso.id']) {
        return $s->string('forms.delete.confirm_match_error');
    }
    return true;
});

$form['children'] = new Formward\Fields\Checkbox(
    $s->string('forms.delete.confirm_delete_children')
);

//submit button
$form->submitButton()->label($s->string('forms.confirm_button'));

//do deletion
if ($form->handle()) {
    $package->noun()->delete();
    $package->cms()->helper('notifications')->confirmation(
        $s->string('forms.confirm_delete_confirmation')
    );
    return;
}

echo $form;
