<?php
$package->noCache();

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

$form['recurse'] = new Formward\Fields\Checkbox(
    $s->string('forms.delete.confirm_delete_children')
);

//submit button
$form->submitButton()->label($s->string('forms.confirm_button'));

//delete function
function do_delete(&$noun, &$cms, $recurse=false)
{
    //recurse if necessary
    if ($recurse) {
        foreach ($noun->children() as $child) {
            do_delete($child, $cms, $recurse);
        }
    }
    //delete this noun
    if ($noun->delete()) {
        $cms->helper('notifications')->confirmation(
            $cms->helper('strings')->string('forms.delete.confirm_deleted', [$noun->name()])
        );
    } else {
        $cms->helper('notifications')->error(
            $cms->helper('strings')->string('forms.delete.confirm_deleted_error', [$noun->name()])
        );
    }
    return;
}

//do deletion
if ($form->handle()) {
    $n = $package->cms()->helper('notifications');
    $noun = $package->noun();
    do_delete($noun, $package->cms(), $form['recurse']->value());
    return;
}

echo $form;
