<?php
$package->cache_noStore();
$l = $cms->helper('logging');
$p = $cms->helper('paginator');
$s = $cms->helper('strings');

$search = $this->factory('logging')->search();
$search->order('${dso.modified.date} DESC');

echo $p->paginate(
    $search->count(),
    $package,
    'page',
    20,
    function ($start, $end) use ($search, $cms) {
        $date = null;
        $search = clone $search;
        $search->limit($end - $start);
        $search->offset($start - 1);
        $out = '';
        foreach ($search->execute() as $log) {
            $mDate = $cms->helper('strings')->date($log['dso.modified.date']);
            if ($mDate != $date) {
                $date = $mDate;
                $out .= "<h2>$date</h2>";
            }
            $out .= "<div class='digraph-card'>";
            $out .= "<code><a href='".$log->url()."'>".$log->name()."</a></code>";
            $out .= "</div>";
            // $out .= "<tr class='" . @$classes[$log->level()] . "'>";
            // $out .= "<td style='max-width:75%;overflow:hidden;'><a href='" . $log->url() . "'>" . $log->name() . "</a></td>";
            // $out .= "<td>" . $log['count'] . "</td>";
            // $out .= "<td style='width:25%;'><div style='max-width:10em;overflow:hidden;white-space:nowrap;'>" . $log['package.request.url'] . "</div></td>";
            // $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
