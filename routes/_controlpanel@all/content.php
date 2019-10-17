<?php
$p = $cms->helper('paginator');
$s = $cms->helper('strings');
$search = $cms->factory()->search();

//list output
echo $p->paginate(
    $search->count(),
    $package,
    'page',
    20,
    function ($start, $end) use ($search,$s) {
        $out = '';
        $out .= "<table>";
        $out .= "<tr><th>Noun</th><th colspan=2>Created/By</th><th colspan=2>Modified/By</th></tr>";
        $search = clone $search;
        $search->offset($start-1);
        $search->limit(20);
        $search->order('${dso.modified.date} desc');
        foreach ($search->execute() as $noun) {
            $out .= "<tr>";
            $out .= "<td>".$noun->link()."</td>";
            $out .= "<td>".$s->dateHTML($noun['dso.created.date'])."</td>";
            $out .= "<td>".$noun['dso.created.user.id']."</td>";
            if ($noun['dso.created.date'] != $noun['dso.modified.date']) {
                $out .= "<td>".$s->dateHTML($noun['dso.modified.date'])."</td>";
                $out .= "<td>".$noun['dso.modified.user.id']."</td>";
            }
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
