<?php

namespace DigraphCMS;

use Exception as GlobalException;
use Throwable;

class Exception extends GlobalException
{
    protected $data = null;

    public function __construct(string $message = "", $data = null, Throwable $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, 1, $previous);
    }

    public function data()
    {
        return $this->data;
    }
}
