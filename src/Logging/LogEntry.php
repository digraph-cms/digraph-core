<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Logging;

use Destructr\DSO;
use Destructr\DSOFactoryInterface;

class LogEntry extends \Destructr\DSO
{
    public function __construct(array $data = null, DSOFactoryInterface &$factory = null)
    {
        parent::__construct($data, $factory);
    }
}
