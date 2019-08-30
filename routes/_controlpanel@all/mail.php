<?php
$package->noCache();
$mail = $cms->helper('mail');

// $message = $mail->message();
// $message->setSubject('Test email');
// $message->addTo('elhober@unm.edu');
// $message->addTo(['jelliott@cs.unm.edu','Joby Elliott']);
// $message->setBody('This is a test email');
// $message->addTag('test');
// $mail->send($message);

echo "<h2>Mail queue</h2>";
echo "<p>";
echo "<a href='".$this->url('_controlpanel', 'mail_queue')."'><strong>Queued emails:</strong></a> ".$mail->unsentCount();
echo "<br><a href='".$this->url('_controlpanel', 'mail_sent')."'><strong>Sent emails:</strong></a> ".$mail->sentCount();
echo "<br><a href='".$this->url('_controlpanel', 'mail_errors')."'><strong>Send errors:</strong></a> ".$mail->errorCount();
echo "</p>";

// $paginator = $cms->helper('paginator');
// echo $paginator->paginate(
//     $mail->tagCount('test'),
//     $package,
//     'page',
//     20,
//     function ($start, $end) use ($mail,$cms) {
//         ob_start();
//         $s = $cms->helper('strings');
//         echo "<table>";
//         echo "<tr><th>Message</th><th>Sent</th></tr>";
//         foreach ($mail->tagged('test', $start, $end-$start) as $qm) {
//             echo "<tr>";
//             echo "<td valign='top'>".$qm->summaryText(['hidetags'=>true,'hidebcc'=>true,'hidereplyto'=>true])."</td>";
//             echo "<td valign='top'>";
//             if ($qm->sendAfter > time()) {
//                 echo "Queued: ".$s->dateHTML($qm->sendAfter);
//             } elseif ($qm->sent) {
//                 echo $s->dateHTML($qm->sent);
//                 if ($qm->error) {
//                     echo "<br>Error:<br>".$qm->error;
//                 }
//             } else {
//                 echo "Queued";
//             }
//             echo "</td>";
//             echo "</tr>";
//         }
//         echo "</table>";
//         return ob_get_clean();
//     }
// );
