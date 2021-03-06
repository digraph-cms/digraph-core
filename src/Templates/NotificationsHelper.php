<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Templates;

use Digraph\Helpers\AbstractHelper;

use Sesh\SessionTrait;

class NotificationsHelper extends AbstractHelper
{
    use SessionTrait;

    protected $notifications = [
        'error' => [],
        'confirmation' => [],
        'warning' => [],
        'notice' => []
    ];

    public function __construct(\Digraph\CMS $cms)
    {
        parent::__construct($cms);
        $this->sessionTraitInit();
    }

    public function flashes()
    {
        $out = [];
        foreach (['confirmation','notice','warning','error'] as $type) {
            if ($flashes = $this->sessionGetFlash($type)) {
                foreach ($flashes as $message) {
                    $out[$type][] = $message;
                }
            }
        }
        return $out;
    }

    public function printNotice($message)
    {
        $this->print($message, 'notice');
    }

    public function printConfirmation($message)
    {
        $this->print($message, 'confirmation');
    }

    public function printWarning($message)
    {
        $this->print($message, 'warning');
    }

    public function printError($message)
    {
        $this->print($message, 'error');
    }

    protected function print($message, $type)
    {
        echo "<div class='notification notification-$type'>$message</div>";
    }

    public function all()
    {
        return $this->notifications;
    }

    public function flashConfirmation($message, $name=null)
    {
        $this->flash('confirmation', $message, $name);
    }

    public function flashNotice($message, $name=null)
    {
        $this->flash('notice', $message, $name);
    }

    public function flashWarning($message, $name=null)
    {
        $this->flash('warning', $message, $name);
    }

    public function flashError($message, $name=null)
    {
        $this->flash('error', $message, $name);
    }

    public function flash($type, $message, $name=null)
    {
        $this->sessionPushFlash($type, $message);
    }

    public function confirmation($message, $name=null)
    {
        $this->add('confirmation', $message, $name);
    }

    public function notice($message, $name=null)
    {
        $this->add('notice', $message, $name);
    }

    public function warning($message, $name=null)
    {
        $this->add('warning', $message, $name);
    }

    public function error($message, $name=null)
    {
        $this->add('error', $message, $name);
    }

    public function add($type, $message, $name=null)
    {
        if ((!$name)) {
            $name = uniqid();
        }
        @$this->notifications[$type][$name] = $message;
    }
}
