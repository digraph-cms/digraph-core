<?php
$package->noCache();
$s = $cms->helper('slugs');
$p = $cms->helper('paginator');
$n = $cms->helper('notifications');
$count = $s->count();
$token = $cms->helper('session')->getToken('slug.delete');

echo "<p>This site currently has $count custom URLs. The most recently added/updated ones are listed first and take precedence when determining the preferred URL of a page.</p>";

//do deletions
if ($delete = $package['url.args.delete']) {
    if ($delete = unserialize($delete)) {
        list($url, $noun) = $delete;
        if ($package['url.args.hash'] != md5($token.$url.$noun)) {
            if ($s->delete($url, $noun)) {
                $n->flashConfirmation("Deleted URL <code>$url =&gt; $noun</code>");
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
    function ($start, $end) use ($package,$s,$cms,$session) {
        $out = '';
        $out .= "<table>";
        $out .= "<tr><th>URL</th><th>Noun</th></tr>";
        foreach ($s->list(20, $start-1) as $slug) {
            $url = $slug['slug_url'];
            $noun = $cms->read($slug['slug_noun']);
            $nlink = $noun?$noun->link():'[noun not found: '.$slug['slug_noun'].']';
            $out .= "<tr>";
            $out .= "<td>$url</td>";
            $out .= "<td>$nlink</td>";
            $durl = $package->url();
            $durl['args.delete'] = serialize([$slug['slug_url'], $slug['slug_noun']]);
            $durl['args.hash'] = md5($token.$slug['slug_url'].$slug['slug_noun']);
            $out .= "<td><a href='$durl'>delete</a></td>";
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
