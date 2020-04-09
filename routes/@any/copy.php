<?php
$package->noCache();
$f = $cms->helper('forms');
$n = $cms->helper('notifications');
$noun = $package->noun();
$package['fields.page_name'] = $package['fields.page_title'] = 'Copy ' . $noun->name();

$form = $f->form('');
$form->addClass('compact-form');
$form['parent'] = $f->field('noun', 'Set parent for copy');
$form['parent']->required(true);
if ($parent = $noun->parent()) {
    $form['parent']->default($parent['dso.id']);
}
// $form['recurse'] = $f->field('checkbox', 'Recursively copy children (may take a long time if there are many children)');
// $form['edges_out'] = $f->field('checkbox', 'Copy outbound edges (this page only, children\'s edges are never copied)');
// $form['edges_in'] = $f->field('checkbox', 'Copy inbound edges (this page only, children\'s edges are never copied)');
echo $form;

if ($form->handle()) {
    $copy = copyNoun($noun);
    $cms->helper('edges')->create($form['parent']->value(), $copy['dso.id']);
    $n->flashConfirmation($copy->name().' created');
    $package->redirect($copy->url()->__toString());
}

function copyNoun($noun)
{
    global $cms;
    // copy noun itself, including all data except dso.id
    $copy = $noun->get();
    unset($copy['dso']['id']);
    $copy = $cms->factory()->create($copy);
    // insert changes
    $copy->insert();
    //return copy
    return $copy;
}
