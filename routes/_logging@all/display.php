<?php
$package->cache_noStore();
$l = $cms->helper('logging');
$p = $cms->helper('paginator');
$s = $cms->helper('strings');

$search = $this->factory('logging')->search();
$search->order('${dso.type} DESC, ${count} DESC, ${dso.modified.date} DESC');

$classes = [
    'INFO' => '',
    'DEBUG' => '',
    'NOTICE' => 'highlighted-notice',
    'WARNING' => 'highlighted-warning',
    'ERROR' => 'highlighted-error',
    'CRITICAL' => 'highlighted-error',
    'EMERGENCY' => 'highlighted-error',
];

echo $p->paginate(
    $search->count(),
    $package,
    'page',
    20,
    function ($start, $end) use ($classes, $search) {
        $out = '';
        $out .= "<table>";
        $out .= "<tr><th>Log</th><th>Count</th><th>Path</th></tr>";
        $search = clone $search;
        $search->limit($end - $start);
        $search->offset($start - 1);
        foreach ($search->execute() as $log) {
            $out .= "<tr class='" . @$classes[$log->level()] . "'>";
            $out .= "<td><a href='" . $log->url() . "'>" . $log->name() . "</a></td>";
            $out .= "<td>" . $log['count'] . "</td>";
            $out .= "<td style='width:25%;'><div style='max-width:10em;overflow:hidden;white-space:nowrap;'>" . $log['package.request.url'] . "</div></td>";
            $out .= "</tr>";
        }
        $out .= "</table>";
        return $out;
    }
);
