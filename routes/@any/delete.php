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
$form['recurse']->default(true);

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
    if ($form['recurse']->value()) {
        $toDelete = $cms->helper('edges')->children_recursive($noun['dso.id']);
    }
    $toDelete[] = $noun['dso.id'];
    $limit = ini_get('max_execution_time')-2;
    $start = time();
    foreach ($toDelete as $id) {
        if ($n = $cms->read($id, false)) {
            $n->delete();
        }
        if (time()-$start >= $limit) {
            $cms->helper('notifications')->flashError('Ran out of time while deleting child pages. Please run again to complete deletion process.');
            $package->redirect($package->url()->string());
            return;
        }
    }
    return;
}

echo $form;
