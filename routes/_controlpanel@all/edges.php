<?php
$package->cache_noStore();
$e = $cms->helper('edges');
$p = $cms->helper('paginator');
$n = $cms->helper('notifications');
$count = $e->count();
$token = $cms->helper('session')->getToken('edge.delete');

//do deletions
if ($delete = $package['url.args.delete']) {
    if ($delete = json_decode($delete, true)) {
        list($start, $end, $type) = $delete;
        if ($package['url.args.hash'] == md5($token.$start.$end.$type)) {
            if ($e->delete($start, $end, $type)) {
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
        $out .= "<tr><th>Type</th><th>Start</th><th>End</th><th></th></tr>";
        foreach ($e->list(20, $start-1) as $edge) {
            $snoun = $cms->read($edge->start());
            $slink = $snoun?$snoun->link():'[noun not found: '.$edge->start().']';
            $enoun = $cms->read($edge->end());
            $elink = $enoun?$enoun->link():'[noun not found: '.$edge->end().']';
            $out .= "<tr>";
            $out .= "<td>".$edge->type()."</td>";
            $out .= "<td>$slink</td>";
            $out .= "<td>$elink</td>";
            $durl = $package->url();
            $durl['args.delete'] = json_encode([$edge->start(), $edge->end(),$edge->type()]);
            $durl['args.hash'] = md5($token.$edge->start().$edge->end().$edge->type());
            $out .= "<td><a href='$durl' class='row-button row-delete'>delete</a></td>";
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
