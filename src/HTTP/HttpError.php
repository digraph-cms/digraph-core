<?php

namespace DigraphCMS\HTTP;

use Exception;

class HttpError extends Exception
{
    /** @var int */
    protected $status;

    public function __construct(int $status, string $message = null)
    {
        $this->status = $status;
        parent::__construct($message ?? 'HTTP Status ' . $status);
    }

    public function status(): int
    {
        return $this->status;
    }
}
