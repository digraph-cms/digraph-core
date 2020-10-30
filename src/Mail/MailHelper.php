<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper extends \Digraph\Helpers\AbstractHelper
{
    protected $pdo;

    /* DDL for table */
    const DDL = <<<EOT
CREATE TABLE IF NOT EXISTS "digraph_mail" (
    "mail_id" INTEGER,
    "mail_created" INTEGER,
    "mail_sendafter" INTEGER NOT NULL,
    "mail_sent" INTEGER,
    "mail_error" TEXT,
    "mail_tags" TEXT,
    "mail_message" TEXT NOT NULL,
    PRIMARY KEY("mail_id")
);
EOT;

    /* indexes to create on table */
    const IDX = [
        'CREATE INDEX IF NOT EXISTS digraph_mail_created_IDX ON digraph_mail (mail_created);',
        'CREATE INDEX IF NOT EXISTS digraph_mail_sendafter_IDX ON digraph_mail (mail_sendafter);',
        'CREATE INDEX IF NOT EXISTS digraph_mail_sent_IDX ON digraph_mail (mail_sent);',
        'CREATE INDEX IF NOT EXISTS digraph_mail_error_IDX ON digraph_mail (data_error);',
    ];

    public function errors(int $offset=null, int $limit=null)
    {
        $sql = 'SELECT * FROM digraph_mail WHERE mail_error is not null ORDER BY mail_sent desc, mail_created desc';
        if ($limit) {
            $sql .= ' LIMIT '.$limit;
        }
        if ($limit && $offset) {
            $sql .= ' OFFSET '.$offset;
        }
        return $this->fetch($sql);
    }

    public function unsent(int $offset=null, int $limit=null)
    {
        $sql = 'SELECT * FROM digraph_mail WHERE mail_sent is null and mail_error is null ORDER BY mail_sendafter asc, mail_created desc';
        if ($limit) {
            $sql .= ' LIMIT '.$limit;
        }
        if ($limit && $offset) {
            $sql .= ' OFFSET '.$offset;
        }
        return $this->fetch($sql);
    }

    public function tagged(string $tag, int $offset=null, int $limit=null)
    {
        $sql = 'SELECT * FROM digraph_mail WHERE mail_tags like :tag ORDER BY mail_sent desc';
        if ($limit) {
            $sql .= ' LIMIT '.$limit;
        }
        if ($limit && $offset) {
            $sql .= ' OFFSET '.$offset;
        }
        return $this->fetch($sql, ['tag'=>"%|$tag|%"]);
    }

    public function sent(int $offset=null, int $limit=null)
    {
        $sql = 'SELECT * FROM digraph_mail WHERE mail_sent is not null and mail_error is null ORDER BY mail_created desc';
        if ($limit) {
            $sql .= ' LIMIT '.$limit;
        }
        if ($limit && $offset) {
            $sql .= ' OFFSET '.$offset;
        }
        return $this->fetch($sql);
    }

    public function tagCount(string $tag)
    {
        $sql = 'SELECT count() FROM digraph_mail WHERE mail_tags like :tag';
        return $this->count($sql, ['tag'=>"%|$tag|%"]);
    }

    public function unsentCount()
    {
        $sql = 'SELECT count() FROM digraph_mail WHERE mail_sent is null and mail_error is null';
        return $this->count($sql);
    }

    public function sentCount()
    {
        $sql = 'SELECT count() FROM digraph_mail WHERE mail_sent is not null and mail_error is null';
        return $this->count($sql);
    }

    public function errorCount()
    {
        $sql = 'SELECT count() FROM digraph_mail WHERE mail_error is not null';
        return $this->count($sql);
    }

    public function queueLength()
    {
        $sql = 'SELECT count() FROM digraph_mail WHERE mail_sent is null and mail_error is null';
        return $this->count($sql);
    }

    public function hook_cron()
    {
        // send unset messages
        $unsent = $this->fetch(
            'SELECT * FROM digraph_mail WHERE mail_sendafter <= :time and mail_sent is null and mail_error is null ORDER BY mail_created asc LIMIT 20',
            ['time' => time()]
        );
        foreach ($unsent as $qm) {
            $this->doSend($qm);
        }
        // clear errors older than 30 days
        $this->execute(
            'DELETE FROM digraph_mail WHERE mail_error is not null AND mail_sent < :time;',
            ['time' => time()-(86400*30)]
        );
        // return number of sent messages
        return count($unsent);
    }

    public function doSend(QueuedMessage $qm)
    {
        if (!$this->cms->config['mail.enabled']) {
            return null;
        }
        $success = false;
        $error = 'Unspecified error';
        try {
            $mail = $this->mail();
            //to
            foreach ($qm->message->to() as $a) {
                if (is_array($a)) {
                    $mail->addAddress($a[0], $a[1]);
                } else {
                    $mail->addAddress($a);
                }
            }
            //from
            if (is_array($qm->message->from())) {
                $mail->setFrom($qm->message->from()[0], $qm->message->from()[1]);
            } else {
                $mail->setFrom($qm->message->from());
            }
            //cc
            foreach ($qm->message->cc() as $a) {
                if (is_array($a)) {
                    $mail->addCC($a[0], $a[1]);
                } else {
                    $mail->addCC($a);
                }
            }
            //bcc
            foreach ($qm->message->bcc() as $a) {
                if (is_array($a)) {
                    $mail->addBCC($a[0], $a[1]);
                } else {
                    $mail->addBCC($a);
                }
            }
            //subject
            $mail->Subject = $qm->message->subject();
            //body
            $mail->msgHTML($qm->message->body());
            $mail->AltBody = \Soundasleep\Html2Text::convert(
                $qm->message->body(),
                [
                    'ignore_errors' => true,
                    'drop_links' => false
                ]
            );
            //try to send
            $success = $mail->send();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        //record results in database
        if ($success) {
            $this->execute(
                'UPDATE digraph_mail SET mail_sent = :time WHERE mail_id = :id;',
                ['time' => time(), 'id' => $qm->id]
            );
        } else {
            $this->execute(
                'UPDATE digraph_mail SET mail_sent = :time, mail_error = :error WHERE mail_id = :id;',
                ['time' => time(), 'id' => $qm->id, 'error' => $error]
            );
        }
        //return result
        return $success;
    }

    /**
     * Enqueue a message in the database for sending in cron jobs
     *
     * @param Message $message
     * @return void
     */
    public function send(Message $message)
    {
        $sql = "INSERT INTO digraph_mail (mail_created, mail_sendafter, mail_sent, mail_error, mail_tags, mail_message) VALUES (:created,:sendafter,null,null,:tags,:message);";
        $args = [
            'created' => time(),
            'sendafter' => $message->sendAfter(),
            'tags' => $message->tags()?('|'.implode('|', $message->tags()).'|'):'',
            'message' => serialize($message)
        ];
        return $this->execute($sql, $args);
    }

    /**
     * Execute SQL and return whether or not it succeeded.
     *
     * @param string $sql
     * @param array $args
     * @return bool
     */
    protected function execute($sql, $args=[])
    {
        $fargs = [];
        foreach ($args as $key => $value) {
            $fargs[':'.$key] = $value;
        }
        $s = $this->pdo->prepare($sql);
        if (!$s) {
            throw new \Exception('PDO prepare error: '.implode(', ', $this->pdo->errorInfo()));
        }
        return $s->execute($fargs);
    }

    /**
     * Execute SQL and return associative array of data_name/data_value pairs
     *
     * @param string $sql
     * @param array $args
     * @return array
     */
    protected function fetch($sql, $args=[])
    {
        $fargs = [];
        foreach ($args as $key => $value) {
            $fargs[':'.$key] = $value;
        }
        $s = $this->pdo->prepare($sql);
        $res = [];
        if ($s->execute($fargs)) {
            foreach ($s->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $res[] = new QueuedMessage($row);
            }
        }
        return $res;
    }

    protected function count($sql, $args=[])
    {
        $fargs = [];
        foreach ($args as $key => $value) {
            $fargs[':'.$key] = $value;
        }
        $s = $this->pdo->prepare($sql);
        if ($s->execute($fargs)) {
            return $s->fetchAll()[0][0];
        }
        return 0;
    }

    public function construct()
    {
        //uses sqlite-only pdo
        $this->pdo = $this->cms->pdo('mail');
        //ensure that tables and indexes exist
        $this->pdo->exec(static::DDL);
        foreach (static::IDX as $idx) {
            $this->pdo->exec($idx);
        }
    }

    public function message()
    {
        $config = $this->cms->config;
        $message = new Message();
        $message->setSubject($config['mail.default.subject']);
        $message->setFrom($config['mail.default.from']);
        if ($config['mail.default.replyto']) {
            $message->setReplyTo($config['mail.default.from']);
        }
        foreach ($config['mail.default.cc'] as $a) {
            $message->addCC($a);
        }
        foreach ($config['mail.default.bcc'] as $a) {
            $message->addBCC($a);
        }
        return $message;
    }

    /**
     * Deprecated: This function will no longer be public in the future.
     *
     * Get a PHPMailer object to send mail to.
     *
     * @return void
     */
    public function mail()
    {
        $mail = new PHPMailer(true);
        //set subject to be "Message from site_name"
        $mail->Subject = $this->cms->config['mail.default.subject'];
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
