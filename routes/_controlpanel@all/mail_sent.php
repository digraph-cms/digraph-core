<?php
$package->noCache();
$mail = $cms->helper('mail');
$paginator = $cms->helper('paginator');

echo $paginator->paginate(
    $mail->sentCount(),
    $package,
    'page',
    20,
    function ($start, $end) use ($mail,$cms) {
        ob_start();
        $s = $cms->helper('strings');
        echo "<table>";
        echo "<tr><th>Created</th><th>Send after</th><th>Message</th></tr>";
        foreach ($mail->sent($start, $end-$start) as $qm) {
            echo "<tr>";
            echo "<td valign='top'>".$s->dateHTML($qm->created)."</td>";
            echo "<td valign='top'>".($qm->sendAfter?$s->dateHTML($qm->sendAfter):'')."</td>";
            echo "<td valign='top'>".$qm->summaryText()."</td>";
            echo "</tr>";
        }
        echo "</table>";
        return ob_get_clean();
    }
);