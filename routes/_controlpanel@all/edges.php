<?php
$package->noCache();
$e = $cms->helper('edges');
$p = $cms->helper('paginator');
$n = $cms->helper('notifications');
$count = $e->count();
$token = $cms->helper('session')->getToken('edge.delete');

//do deletions
if ($delete = $package['url.args.delete']) {
    if ($delete = json_decode($delete, true)) {
        list($start, $end) = $delete;
        if ($package['url.args.hash'] == md5($token.$start.$end)) {
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

//list output
echo $p->paginate(
    $count,
    $package,
    'page',
    20,
    function ($start, $end) use ($package,$e,$cms,$token) {
        $out = '';
        $out .= "<table>";
        $out .= "<tr><th>Start</th><th>End</th><th></th></tr>";
        foreach ($e->list(20, $start-1) as $edge) {
            $snoun = $cms->read($edge['edge_start']);
            $slink = $snoun?$snoun->link():'[noun not found: '.$edge['edge_start'].']';
            $enoun = $cms->read($edge['edge_end']);
            $elink = $enoun?$enoun->link():'[noun not found: '.$edge['edge_end'].']';
            $out .= "<tr>";
            $out .= "<td>$slink</td>";
            $out .= "<td>$elink</td>";
            $durl = $package->url();
            $durl['args.delete'] = json_encode([$edge['edge_start'], $edge['edge_end']]);
            $durl['args.hash'] = md5($token.$edge['edge_start'].$edge['edge_end']);
            $out .= "<td><a href='$durl' class='row-button row-delete'>delete</a></td>";
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
