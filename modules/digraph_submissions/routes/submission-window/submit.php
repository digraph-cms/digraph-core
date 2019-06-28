<?php
$package->noCache();
$package['fields.page_title'] = $package['url.text'];
$noun = $package->noun();
$g = $cms->helper('graph');

//check that submitting is allowed
if (!$noun->canSubmit()) {
    //if user can't submit, redirect back to submission window so it can tell them why
    $package->redirect($noun->url());
    return;
}

//set up basic form
$forms = $this->helper('forms');
$form = $forms->addNoun($noun->submissionType(), $noun);

//find previous submissions of the same type
$previous = null;
$search = $cms->factory()->search();
$search->where('${dso.type} = :type AND ${owner} = :owner');
$search->order('${dso.created.date} desc');
$results = $search->execute([
    ':type' => $noun->submissionType(),
    ':owner' => $noun->cms()->helper('users')->id()
]);
$previous = @array_shift($results);

//if previous submission exists, autofill using it
if ($previous) {
    $form['submitter']->default($previous['submitter']);
}

//echo form
echo $form;

//handle form to save
if ($form->handle()) {
    if ($form->object->insert()) {
        $object = $cms->read($form->object['dso.id'], false, true);
        $cms->helper('edges')->create($package['noun.dso.id'], $object['dso.id']);
        $cms->helper('hooks')->noun_trigger($object, 'added');
        $cms->helper('notifications')->flashConfirmation(
            $cms->helper('strings')->string(
                'notifications.add.confirmation',
                ['name'=>$object->link()]
            )
        );
    } else {
        $cms->helper('notifications')->flashError(
            $cms->helper('strings')->string(
                'notifications.add.error'
            )
        );
    }
    $package->redirect(
        $form->object->hook_postAddUrl()
    );
}
