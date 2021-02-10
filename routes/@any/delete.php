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
$form['id']->addValidatorFunction('match', function ($field) use ($s) {
    if ($field->value() != $this->package->noun()['dso.id']) {
        return $s->string('forms.delete.confirm_match_error');
    }
    return true;
});

$form['recurse'] = new Formward\Fields\Checkbox(
    $s->string('forms.delete.confirm_delete_children')
);
$form['recurse']->default(false);

//submit button
$form->submitButton()->label($s->string('forms.confirm_button'));

//do deletion
if ($form->handle()) {
    $n = $package->cms()->helper('notifications');
    $g = $cms->helper('graph');
    $noun = $package->noun();
    $deleteChildren = [];
    if ($form['recurse']->value()) {
        //get list of all children, in breadth-first order
        $deleteChildren = $g->traverse($noun['dso.id']);
        $fullTree = $deleteChildren;
        array_shift($deleteChildren);
        $keptChildren = [];
        //filter out those with parents outside the given tree
        $deleteChildren = array_filter(
            $deleteChildren,
            function ($id) use ($g, $fullTree, &$keptChildren) {
                foreach ($g->parentIDs($id) as $pid) {
                    if (in_array($pid, $keptChildren) || !in_array($pid, $fullTree)) {
                        $keptChildren[] = $id;
                        return false;
                    }
                }
                return true;
            }
        );
        //notify about children being kept
        foreach ($keptChildren as $tk) {
            if ($tk == $noun['dso.id']) {
                continue;
            }
            $tkn = $cms->read($tk);
            $tkn = $tkn ? $tkn->link() : "<code>$tk</code>";
            $n->flashNotice("$tkn has ancestors outside the tree, it is not being deleted.");
        }
        //reverse order to delete children from leaves in
        $deleteChildren = array_reverse($deleteChildren);
    }
    //try to set max execution time to unlimited
    ini_set('max_execution_time', 0);
    //set time limit
    $limit = intval(ini_get('max_execution_time'));
    $limit = $limit ? $limit - 2 : 0;
    $start = time();
    //try to delete as many children as possible in time limit
    $childrenDeleted = 0;
    foreach ($deleteChildren as $id) {
        if ($child = $cms->read($id, false)) {
            $child->delete();
            $childrenDeleted++;
        }
        if ($limit && time() - $start >= $limit) {
            $n->flashWarning('Ran out of time while deleting children. Please run again to complete deletion process.');
            $package->redirect($package->url()->string());
            return;
        }
    }
    //delete noun and redirect to either parent or home
    $afterUrl = $noun->parentUrl() ?? $cms->helper('urls')->parse('home');
    $noun->delete();
    if ($childrenDeleted) {
        $n->flashConfirmation('Deleted ' . $noun->name() . ' and ' . $childrenDeleted . ' children');
    } else {
        $n->flashConfirmation('Deleted ' . $noun->name());
    }
    $package->redirect($afterUrl);
    return;
}

echo $form;
