<?php
$package->cache_noStore();

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

//do deletion
if ($form->handle()) {
    $n = $package->cms()->helper('notifications');
    $noun = $package->noun();
    if ($form['recurse']->value()) {
        //get list of all children, in breadth-first order
        $toDelete = $cms->helper('graph')->traverse($noun['dso.id']);
        //reverse order to delete children from leaves in
        $toDelete = array_reverse($toDelete);
    }
    $toDelete[] = $noun['dso.id'];
    //try to set max execution time to unlimited
    ini_set('max_execution_time', 0);
    //set time limit
    $limit = intval(ini_get('max_execution_time'));
    $limit = $limit?$limit-2:0;
    $start = time();
    foreach ($toDelete as $id) {
        if ($n = $cms->read($id, false)) {
            $n->delete();
        }
        if ($limit && time()-$start >= $limit) {
            $cms->helper('notifications')->flashError('Ran out of time while deleting child pages. Please run again to complete deletion process.');
            $package->redirect($package->url()->string());
            return;
        }
    }
    return;
}

echo $form;
