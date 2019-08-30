<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mail;

class QueuedMessage
{
    public $id;
    public $created;
    public $sendAfter;
    public $sent;
    public $error;
    public $tags;
    public $message;
    
    public function __construct($row)
    {
        $this->id = $row['mail_id'];
        $this->created = $row['mail_created'];
        $this->sendAfter = $row['mail_sendafter'];
        $this->sent = $row['mail_sent'];
        $this->error = $row['mail_error'];
        $this->tags = array_filter(explode('|', $row['mail_tags']));
        $this->message = unserialize($row['mail_message']);
    }

    public function summaryText($options=[])
    {
        ob_start();
        echo "<dl>";
        echo "<dt>Subject</dt>";
        echo "<dd>".$this->message->subject()."</dd>";
        echo "<dt>From</dt>";
        echo "<dd>".$this->fmtAddress($this->message->from())."</dd>";
        if (!@$options['hidereplyto'] && $this->message->replyTo()) {
            echo "<dt>Reply to</dt>";
            echo "<dd>".$this->fmtAddress($this->message->replyTo())."</dd>";
        }
        echo "<dt>To</dt>";
        echo "<dd>".$this->fmtMultipleAddresses($this->message->to())."</dd>";
        if ($this->message->cc()) {
            echo "<dt>CC</dt>";
            echo "<dd>".$this->fmtMultipleAddresses($this->message->cc())."</dd>";
        }
        if (!@$options['hidebcc'] && $this->message->bcc()) {
            echo "<dt>BCC</dt>";
            echo "<dd>".$this->fmtMultipleAddresses($this->message->bcc())."</dd>";
        }
        if (!@$options['hidetags'] && $this->tags) {
            echo "<dt>Tags</dt>";
            echo "<dd>".implode(', ', $this->tags)."</dd>";
        }
        echo "<dt>Body</dt>";
        echo "<dd>".$this->message->body()."</dd>";
        echo "</dl>";
        return ob_get_clean();
    }
    protected function fmtMultipleAddresses(array $addresses)
    {
        return implode(', ', array_map(
            function ($e) {
                return $this->fmtAddress($e);
            },
            $addresses
        ));
    }

    protected function fmtAddress($addr)
    {
        if (is_array($addr)) {
            return '"'.$addr[1].'" &lt;'.$addr[0].'&gt;';
        } else {
            return $addr;
        }
    }
}
