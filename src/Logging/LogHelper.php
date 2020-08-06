<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Logging;

use Digraph\Mail\Message;

class LogHelper extends \Digraph\Helpers\AbstractHelper
{
    /**
     * Detailed debug information
     */
    public const DEBUG = 100;
    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    public const INFO = 200;
    /**
     * Uncommon events
     */
    public const NOTICE = 250;
    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    public const WARNING = 300;
    /**
     * Runtime errors
     */
    public const ERROR = 400;
    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public const CRITICAL = 500;
    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    public const ALERT = 550;
    /**
     * Urgent alert.
     */
    public const EMERGENCY = 600;

    public function hook_cron()
    {
        $search = $this->cms->factory('logging')->search();
        $exp = 2 * time() - strtotime($this->cms->config['cron.logexpiration']);
        $search->where('${dso.created.date} < :exp');
        $result = $search->execute(['exp' => $exp], null);
        $deleted = 0;
        $errors = [];
        foreach ($result as $l) {
            if ($l->delete(true)) {
                $deleted++;
            } else {
                $errors[] = 'error deleting ' . $l['dso.id'];
            }
        }
        return [
            'result' => $deleted,
            'errors' => $errors,
        ];
    }

    function list() {
        $search = $this->factory()->search();
        $search->limit(5);
        $search->order('${count} DESC, ${dso.type} DESC, ${dso.modified.date} DESC');
        return $search->execute();
    }

    public function factory()
    {
        return $this->cms->factory('logging');
    }

    public function create($package, $level)
    {
        //save to internal digraph log
        $new = false;
        if (!($entry = $this->factory()->read($package['logging.save']))) {
            $new = true;
            $entry = $this->factory()->create([
                'dso.id' => $package['logging.save'],
                'dso.type' => 'level-' . $level,
                'level' => $level,
                'count' => 0,
                'message' => $package['logging.messages.' . $package['logging.save']],
                'package' => $package->get(),
                'users' => [],
                'url' => $package->url() . '',
                'phpurl' => "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", ENT_QUOTES, 'UTF-8',
                'log.package' => $package->log(),
                'log.cms' => $this->cms->log(),
                'config' => $this->cms->config->get(),
            ]);
        }
        //record count
        $entry['count'] = $entry['count'] + 1;
        //record referer
        $referer = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '[empty]';
        $entry['referers.' . md5($referer) . '.url'] = $referer;
        $entry['referers.' . md5($referer) . '.count'] = $entry['referers.' . md5($referer) . '.count'] + 1;
        //record user/url
        $u = $this->cms->helper('users');
        $userKey = "users." . md5($u->id()) . '.' . md5($_SERVER['REMOTE_ADDR'] . $package->url());
        $entry[$userKey] = [
            'id' => $u->id(),
            'ip' => @$_SERVER['REMOTE_ADDR'],
            'fw' => @$_SERVER['HTTP_X_FORWARDED_FOR'],
            'ua' => @$_SERVER['HTTP_USER_AGENT'],
            'url' => $package->url() . '',
        ];
        //save
        if ($new) {
            //send mail
            $this->sendMail($entry);
            //insert
            $entry->insert();
        } else {
            $entry->update();
        }
        return $entry;
    }

    protected function sendMail($entry)
    {
        if (!$this->cms->config['logging.mail.recipients']) {
            return;
        }
        if ($entry['level'] < $this->cms->config['logging.mail.threshold']) {
            return;
        }
        $mail = $this->cms->helper('mail');
        $message = new Message();
        $message->setSubject('Site error: ' . $entry['message']);
        $body = [
            'A new error has been logged at:',
            '<a href="' . $entry['url'] . '">' . $entry['url'] . '</a>',
            '',
            'View the log entry online at:',
            '<a href="' . $entry->url() . '">' . $entry->url() . '</a>',
        ];
        $message->setBody(implode('<br>', $body));
        foreach ($this->cms->config['logging.mail.recipients'] as $recipient) {
            $message->addBCC($recipient);
        }
        var_dump($message);
        $mail->send($message);
    }
}
