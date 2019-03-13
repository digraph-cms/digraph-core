<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Logging;

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

    public function list()
    {
        $search = $this->factory()->search();
        $search->order('${count} DESC, ${dso.type} DESC, ${dso.modified.date} DESC');
        return $search->execute();
    }

    public function factory()
    {
        return $this->cms->factory('logging');
    }

    public function create(&$package, $level)
    {
        //save to internal digraph log
        $new = false;
        if (!($entry = $this->factory()->read($package['logging.save']))) {
            $new = true;
            $entry = $this->factory()->create([
                'dso.id' => $package['logging.save'],
                'dso.type' => 'level-'.$level,
                'count' => 0,
                'message' => $package['logging.messages.'.$package['logging.save']],
                'package' => $package->get(),
                'users' => [],
                'url' => $package->url().'',
                'log.package' => $package->log(),
                'log.cms' => $this->cms->log(),
                'config' => $this->cms->config->get()
            ]);
        }
        //record count
        $entry['count'] = $entry['count']+1;
        //record user/url
        $u = $this->cms->helper('users');
        $userKey = "users.".md5($u->id()).'.'.md5($_SERVER['REMOTE_ADDR'].$package->url());
        $entry[$userKey] = [
            'id' => $u->id(),
            'ip' => @$_SERVER['REMOTE_ADDR'],
            'fw' => @$_SERVER['HTTP_X_FORWARDED_FOR'],
            'url' => $package->url().''
        ];
        //save
        if ($new) {
            $entry->insert();
        } else {
            $entry->update();
        }
        return $entry;
    }

    public function monolog()
    {
    }
}
