<?php
$package['fields.page_title'] = $package['url.text'];
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;

$type = $package['url.args.type'];
$forms = $this->helper('forms');
$form = $forms->addNoun($type, $package->noun()['dso.id']);

foreach ($this->helper('routing')->allHookFiles($type, 'form.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles($type, 'form_add.php') as $file) {
    include $file['file'];
}

$form->handle(
    function (&$form) use ($package,$type) {
        foreach ($this->helper('routing')->allHookFiles($type, 'form_handled.php') as $file) {
            include $file['file'];
        }
        foreach ($this->helper('routing')->allHookFiles($type, 'form_add_handled.php') as $file) {
            include $file['file'];
        }
    }
);
if ($form->handle()) {
    $cms->helper('notifications')->flashConfirmation(
        $cms->helper('strings')->string('notifications.add.confirmation', ['name'=>$form->object->name()])
    );
    $package->redirect($form->object->url()->string());
}

echo $form;
