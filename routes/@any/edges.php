<?php
$package->noCache();
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
             $n->flashNotification("Requested delete of edge <code>$start =&gt; $end</code>");
             if ($e->delete($start, $end)) {
                 $n->flashConfirmation("Deleted edge <code>$start =&gt; $end</code>");
             }
         } else {
             $n->flashError('Incorrect link hash, please try again');
         }
     }
     $url = $package->url();
     unset($url['args.delete']);
     unset($url['args.hash']);
     $package->redirect($url);
     return;
 }

/*
parent-adding form
 */
$pform = $f->form('Add parent');
$pform->addClass('compact-form');
$pform['noun'] = $f->field('noun', '');
$pform['noun']->required(true);
if ($pform->handle()) {
    if ($e->create($pform['noun']->value(), $noun['dso.id'])) {
        $p = $cms->read($pform['noun']->value())->link();
        $c = $noun->link();
        $n->flashConfirmation("Created edge from $p to $c");
    }
    $package->redirect($package->url());
    return;
}

/*
child-adding form
 */
$cform = $f->form('Add child');
$cform->addClass('compact-form');
$cform['noun'] = $f->field('noun', '');
$cform['noun']->required(true);
if ($cform->handle()) {
    if ($e->create($noun['dso.id'], $cform['noun']->value())) {
        $c = $cms->read($cform['noun']->value())->link();
        $p = $noun->link();
        $n->flashConfirmation("Created edge from $p to $c");
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
echo "<h4>Current parents</h4>";
printEdges($e->parents($noun['dso.id']), true);
echo "</div>";

echo '<div class="edges-children">';
echo '<h3>Children</h3>';
echo $cform;
echo "<h4>Current children</h4>";
printEdges($e->children($noun['dso.id']));
echo "</div>";

echo "</div>";

function printEdges($edges, $reverse=false)
{
    global $noun,$cms,$token,$package;
    echo "<table>";
    foreach ($edges as $end) {
        if ($reverse) {
            $start = $end;
            $end = $package->noun()['dso.id'];
        } else {
            $start = $package->noun()['dso.id'];
        }
        $durl = $package->url();
        $durl['args.delete'] = json_encode([$start,$end]);
        $durl['args.hash'] = md5($token.$start.$end);
        echo "<tr>";
        $dn = $reverse?$cms->read($start, false):$cms->read($end, false);
        if ($dn) {
            echo "<td>".$dn->link()."</td>";
        } else {
            echo "<td>[not found: ".($reverse?$start:$end)."]</td>";
        }
        echo "<td><a href='$durl' class='row-button row-unlink'>unlink</a></td>";
        echo "</tr>";
    }
    echo "</table>";
}

?>
<style>
.edges-manager {
    display:flex;
}
.edges-children {
    padding-left: 0.5em;
    flex-grow: 1;
    width: 50%;
    border-left: 1px dotted rgba(127,127,127,0.5);
}
.edges-parents {
    padding-right: 0.5em;
    flex-grow: 1;
    width: 50%;
}
</style>
