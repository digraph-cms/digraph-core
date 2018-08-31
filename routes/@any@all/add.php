<?php
$package['response.cacheable'] = false;

$type = $package['url.noun'];
$forms = $this->helper('forms');
$form = $forms->addNoun($type);

$parent = null;
if ($package['url.args.parent']) {
    $parent = $package->cms()->read($package['url.args.parent']);
}

foreach ($this->helper('routing')->allHookFiles($type, 'form.php') as $file) {
    include $file['file'];
}
foreach ($this->helper('routing')->allHookFiles($type, 'form_add.php') as $file) {
    include $file['file'];
}

$form->handle(function (&$form) use ($package,$parent,$type) {
    $package->redirect($form->noun->url()->string());
    if ($parent) {
        $form->noun->addParent($parent['dso.id']);
    }
    foreach ($this->helper('routing')->allHookFiles($type, 'form_handled.php') as $file) {
        include $file['file'];
    }
    foreach ($this->helper('routing')->allHookFiles($type, 'form_add_handled.php') as $file) {
        include $file['file'];
    }
});

echo $form;
