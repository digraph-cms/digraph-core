<?php
$package->cache_noStore();
$mail = $cms->helper('mail');
$paginator = $cms->helper('paginator');

echo $paginator->paginate(
    $mail->unsentCount(),
    $package,
    'page',
    20,
    function ($start, $end) use ($mail, $cms) {
        ob_start();
        $s = $cms->helper('strings');
        echo "<table>";
        echo "<tr><th>Created</th><th>Send after</th><th>Message</th></tr>";
        foreach ($mail->unsent($start - 1, $end - $start + 1) as $qm) {
            echo "<tr>";
            echo "<td valign='top'>" . $s->dateHTML($qm->created) . "</td>";
            echo "<td valign='top'>" . ($qm->sendAfter ? $s->dateHTML($qm->sendAfter) : '') . "</td>";
            echo "<td valign='top'>" . $qm->summaryText() . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        return ob_get_clean();
    }
);
