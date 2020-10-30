<?php
$package->cache_noStore();
$mail = $cms->helper('mail');
$paginator = $cms->helper('paginator');

echo $paginator->paginate(
    $mail->errorCount(),
    $package,
    'page',
    20,
    function ($start, $end) use ($mail, $cms) {
        ob_start();
        $s = $cms->helper('strings');
        echo "<table>";
        echo "<tr><th>Date sent</th><th>Error</th><th>Message</th></tr>";
        foreach ($mail->errors($start - 1, $end - $start + 1) as $qm) {
            echo "<tr>";
            echo "<td valign='top'>" . ($qm->sent ? $s->dateHTML($qm->sent) : '') . "</td>";
            echo "<td valign='top'>" . ($qm->error ?? '') . "</td>";
            echo "<td valign='top'>" . $qm->summaryText() . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        return ob_get_clean();
    }
);
