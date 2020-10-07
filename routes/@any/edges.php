<?php
$package->cache_noStore();
$f = $cms->helper('forms');
$e = $cms->helper('edges');
$n = $cms->helper('notifications');
$token = $cms->helper('session')->getToken('edge.delete');
$noun = $package->noun();

/*
delete handling
 */
 if ($delete = $package['url.args.delete']) {
     if ($delete = json_decode($delete, true)) {
         list($start, $end) = $delete;
         if ($package['url.args.hash'] == md5($token.$start.$end)) {
             if ($e->delete($start, $end)) {
                 $start = $cms->read($start)->link();
                 $end = $cms->read($end)->link();
                 $n->flashConfirmation("Deleted all edges from $start to $end");
             }
         } else {
             $n->flashError('Incorrect link hash, please try again');
         }
     }
     $url = $package->url();
     unset($url['args.delete']);
     unset($url['args.hash']);
     $package->redirect($url->string(true));
     return;
 }

/*
parent-adding form
 */
$pform = $f->form('Add parent');
$pform->addClass('compact-form');
$pform['noun'] = $f->field('noun', 'Noun');
$pform['noun']->required(true);
$pform['type'] = $f->field('text', 'Edge type');
if ($pform->handle()) {
    if ($target = $cms->read($pform['noun']->value())) {
        if ($e->create($target['dso.id'], $noun['dso.id'], $pform['type']->value())) {
            $p = $target->link();
            $c = $noun->link();
            $n->flashConfirmation("Created edge from $p to $c");
        }
    }
    $package->redirect($package->url());
    return;
}

/*
child-adding form
 */
$cform = $f->form('Add child');
$cform->addClass('compact-form');
$cform['noun'] = $f->field('noun', 'Noun');
$cform['noun']->required(true);
$cform['type'] = $f->field('text', 'Edge type');
if ($cform->handle()) {
    if ($target = $cms->read($cform['noun']->value())) {
        if ($e->create($noun['dso.id'], $target['dso.id'], $cform['type']->value())) {
            $c = $target->link();
            $p = $noun->link();
            $n->flashConfirmation("Created edge from $p to $c");
        }
    }
    $package->redirect($package->url());
    return;
}

/*
Output HTML for interface
 */
echo "<div class='edges-manager'>";

echo '<div class="edges-parents">';
echo '<h3>Parents</h3>';
echo $pform;
echo "<h4>Current parent edges</h4>";
printEdges($e->parents($noun['dso.id']), true);
echo "</div>";

echo '<div class="edges-children">';
echo '<h3>Children</h3>';
echo $cform;
echo "<h4>Current child edges</h4>";
printEdges($e->children($noun['dso.id']));
echo "</div>";

echo "</div>";

function printEdges($edges, $reverse=false)
{
    global $noun,$cms,$package;
    $token = $cms->helper('session')->getToken('edge.delete');
    echo "<table style='width:100%!important;min-width:100%!important;max-width:100%!important;'>";
    echo "<tr><th>".($reverse?'From':'To')."</th><th>Type</th><th>&nbsp;</th></tr>";
    foreach ($edges as $edge) {
        if ($reverse) {
            $start = $edge->start();
            $end = $package->noun()['dso.id'];
        } else {
            $start = $package->noun()['dso.id'];
            $end = $edge->end();
        }
        $durl = $package->url();
        $durl['args.delete'] = json_encode([$start, $end]);
        $durl['args.hash'] = md5($token.$start.$end);
        echo "<tr>";
        $dn = $reverse?$cms->read($start, false):$cms->read($end, false);
        if ($dn) {
            echo "<td>".$dn->link()."</td>";
        } else {
            echo "<td>[not found: ".($reverse?$start:$end)."]</td>";
        }
        echo "<td>".$edge->type()."</td>";
        echo "<td><a href='".$durl->string(true)."' class='row-button row-unlink'>unlink</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}
