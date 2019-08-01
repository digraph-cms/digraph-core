<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer extends \Digraph\Helpers\AbstractHelper
{
    public function &mail()
    {
        $mail = new PHPMailer(true);
        //set subject to be "Message from site_name"
        $mail->Subject = 'Message from '.$cms->config['package.defaults.site_name'];
        //run extra config and return
        $mail = $this->configureMail($mail);
        return $mail;
    }

    protected function configureMail($mail)
    {
        //currently does nothing, but is where more advanced
        //configuration of mailer would happen, SMTP, sendmail, etc
        return $mail;
    }
}
