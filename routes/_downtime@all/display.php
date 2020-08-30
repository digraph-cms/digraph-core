<?php
$package->cache_noStore();
$p = $cms->helper('paginator');
$s = $cms->helper('strings');

$search = $cms->factory('downtime')->search();
$search->order('${downtime.start} desc, ${downtime.end} asc');

$url = $cms->helper('urls')->parse('_downtime/add');
echo "<p>" . $url->html() . "</p>";

//list output
echo $p->paginate(
    $search->count(),
    $package,
    'page',
    20,
    function ($start, $end) use ($search, $s) {
        $out = '';
        $out .= "<table>";
        $out .= "<tr><th>Downtime</th><th>Start</th><th>End</th><th>Created/By</th><th>Modified/By</th></tr>";
        $search = clone $search;
        $search->limit($end - $start);
        $search->offset($start - 1);
        foreach ($search->execute() as $downtime) {
            $class = '';
            if ($downtime['downtime.start'] > time()) {
                $class = 'highlighted';
            } elseif ($downtime['downtime.end'] >= time() || !$downtime['downtime.end']) {
                $class = 'highlighted-warning';
            }
            $out .= "<tr class='$class'>";
            $out .= "<td>" . $downtime->link() . "</td>";
            $out .= "<td>" . $s->datetimeHTML($downtime['downtime.start']) . "</td>";
            if ($downtime['downtime.end']) {
                $out .= "<td>" . $s->datetimeHTML($downtime['downtime.end']) . "</td>";
            } else {
                $out .= "<td><em>none</em></td>";
            }
            $out .= "<td>" . $s->dateHTML($downtime['dso.created.date']);
            $out .= "<br>" . $downtime['dso.created.user.id'] . "</td>";
            if ($downtime['dso.created.date'] != $downtime['dso.modified.date']) {
                $out .= "<td>" . $s->dateHTML($downtime['dso.modified.date']);
                $out .= "<br>" . $downtime['dso.modified.user.id'] . "</td>";
            }
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
