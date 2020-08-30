<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Logging;

use Destructr\DSO;
use Destructr\Factory;

class LogEntry extends \Destructr\DSO
{
    public function __construct(array $data = null, Factory $factory = null)
    {
        parent::__construct($data, $factory);
    }

    public function name()
    {
        return $this->level() . ': ' . $this['message'];
    }

    public function level()
    {
        switch ($this['dso.type']) {
            case 'level-100':
                return 'DEBUG';
            case 'level-200':
                return 'INFO';
            case 'level-250':
                return 'NOTICE';
            case 'level-300':
                return 'WARNING';
            case 'level-400':
                return 'ERROR';
            case 'level-500':
                return 'CRITICAL';
            case 'level-550':
                return 'ALERT';
            case 'level-600':
                return 'EMERGENCY';
        }
        return 'UNKNOWN';
    }

    public function url()
    {
        return $this->factory->cms()->helper('urls')->url(
            '_logging',
            'entry',
            ['id' => $this['dso.id']]
        );
    }
}
