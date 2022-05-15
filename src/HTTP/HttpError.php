<?php

namespace DigraphCMS\HTTP;

use Exception;

class HttpError extends Exception
{
    protected $status;

    public function __construct(int $status, string $message = null)
    {
        $this->status = $status;
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
