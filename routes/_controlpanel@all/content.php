<?php
$p = $cms->helper('paginator');
$s = $cms->helper('strings');
$search = $cms->factory()->search();
$args = [];
if ($package['url.args.user']) {
    $search->where('${dso.created.user.id} = :user OR ${dso.modified.user.id} = :user');
    $args['user'] = $package['url.args.user'];
    $package->overrideParent($cms->helper('urls')->parse('_controlpanel/content'));
    $package['fields.page_name'] = 'Content: ' . htmlentities($package['url.args.user']);
}

//list output
echo $p->paginate(
    $search->count($args),
    $package,
    'page',
    20,
    function ($start, $end) use ($search, $args, $s, $cms) {
        $out = '';
        $out .= "<table>";
        $out .= "<tr><th>Noun</th><th colspan=2>Created/By</th><th colspan=2>Modified/By</th></tr>";
        $search = clone $search;
        $search->offset($start - 1);
        $search->limit(20);
        $search->order('${dso.modified.date} desc');
        foreach ($search->execute($args) as $noun) {
            $out .= "<tr>";
            $out .= "<td>" . $noun->link() . "</td>";
            $out .= "<td>" . $s->dateHTML($noun['dso.created.date']) . "</td>";
            $out .= "<td>" . user_link($noun['dso.created.user.id'],$cms) . "</td>";
            if ($noun['dso.created.date'] != $noun['dso.modified.date']) {
                $out .= "<td>" . $s->dateHTML($noun['dso.modified.date']) . "</td>";
                $out .= "<td>" . user_link($noun['dso.modified.user.id'],$cms) . "</td>";
            }
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);

function user_link(string $user, $cms) {
    $url = $cms->helper('urls')->parse('_controlpanel/content?user='.$user);
    return "<a href='$url'>$user</a>";
}