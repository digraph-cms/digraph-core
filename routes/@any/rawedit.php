<?php
$package->noCache();

$noun = $package->noun();
$cms->helper('notifications')->warning('<strong>Proceed with caution:</strong> this tool can irretrievably destroy data in the object you are editing.');

$form = new \Formward\Form('Edit Raw Data');
$form['yaml'] = new \Formward\Fields\YAML('YAML');
$value = $noun->get();
unset($value['dso']['id']);
$form['yaml']->default($noun->get());
$form['yaml']->addTip('Note: dso.created and dso.modified will be recreated as you save. The value of dso.created will be freshly added as if the object is being created now by you, and dso.modified will be updated to reflect the current time and your user info.');
$form['yaml']->addTip('Note: editing dso.id is disallowed, even through this low-level form. If you want to do that you have to do it directly in the database.');
$form['yaml']->required(true);

if ($form->handle()) {
    $value = $form['yaml']->value();
    $value['dso']['id'] = $noun['dso.id'];
    $noun->set(null, $value);
    if (!$noun->update()) {
        $cms->helper('notifications')->error('<strong>Update error:</strong> failed to update object in the database.');
        return;
    }
    $cms->helper('notifications')->flashConfirmation(
        $cms->helper('strings')->string('notifications.edit.confirmation', ['name'=>$noun->name()])
    );
    $package->redirect($noun->url()->string());
}

echo $form;
