<?php
$package->noCache();

$package['fields.page_title'] = $package['url.text'];

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
    $cms->helper('notifications')->confirmation(
        $cms->helper('strings')->string(
            'notifications.add.confirmation',
            ['name'=>$form->object->link()]
        )
    );
}

echo $form;
