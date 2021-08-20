<?php

namespace DigraphCMS\HTTP;

use Exception;

class HttpError extends Exception
{
    public function __construct(int $status, string $message)
    {
        $this->status = $status;
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}