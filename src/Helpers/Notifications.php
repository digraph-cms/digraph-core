<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;
use Digraph\Session\SessionTrait;

class Notifications extends AbstractHelper
{
    use SessionTrait;

    protected $notifications = array();

    public function __construct(\Digraph\CMS &$cms)
    {
        parent::__construct($cms);
        $this->sessionTraitInit();
    }

    public function all()
    {
        foreach (['confirmation','notice','warning','error'] as $type) {
            if ($flashes = $this->sessionGetFlash($type)) {
                foreach ($flashes as $message) {
                    $this->add($type, $message);
                }
            }
        }
        return $this->notifications;
    }

    public function flashConfirmation($message)
    {
        $this->flash('confirmation', $message);
    }

    public function flashNotice($message)
    {
        $this->flash('notice', $message);
    }

    public function flashWarning($message)
    {
        $this->flash('warning', $message);
    }

    public function flashError($message)
    {
        $this->flash('error', $message);
    }

    public function flash($type, $message)
    {
        $this->sessionPushFlash($type, $message);
    }

    public function confirmation($message)
    {
        $this->add('confirmation', $message);
    }

    public function notice($message)
    {
        $this->add('notice', $message);
    }

    public function warning($message)
    {
        $this->add('warning', $message);
    }

    public function error($message)
    {
        $this->add('error', $message);
    }

    public function add($type, $message)
    {
        @$this->notifications[$type][] = $message;
    }
}
