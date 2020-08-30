<?php
$package['response.cacheable'] = false;

$forms = $this->helper('forms');
$form = $forms->addNoun('default',null,'downtime');

if ($form->handle()) {
    if ($form->object->insert()) {
        $cms->helper('hooks')->noun_trigger($form->object, 'added');
        $cms->helper('notifications')->flashConfirmation(
            $cms->helper('strings')->string(
                'notifications.add.confirmation',
                ['name'=>$form->object->link()]
            )
        );
        $package->redirect($form->object->url('edit', null, true)->string());
    } else {
        $cms->helper('notifications')->error(
            $cms->helper('strings')->string(
                'notifications.add.error'
            )
        );
    }
}

echo $form;
