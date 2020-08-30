<?php
$package['response.cacheable'] = false;

$forms = $this->helper('forms');
if ($downtime = $cms->factory('downtime')->read($package['url.args.id'])) {
    $form = $forms->editNoun($downtime);
} else {
    $package->error(404);
    return;
}

if ($form->handle()) {
    if ($form->object->update()) {
        $cms->helper('hooks')->noun_trigger($form->object, 'added');
        $cms->helper('notifications')->flashConfirmation(
            $cms->helper('strings')->string(
                'notifications.edit.confirmation',
                ['name' => $form->object->link()]
            )
        );
        $package->redirect($form->object->url('edit', null, true)->string());
    } else {
        $cms->helper('notifications')->error(
            $cms->helper('strings')->string(
                'notifications.edit.error'
            )
        );
    }
}

echo $form;
