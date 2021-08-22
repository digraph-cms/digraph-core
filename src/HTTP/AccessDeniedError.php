<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\Users\Users;

class AccessDeniedError extends HttpError
{
    public function __construct(string $message)
    {
        parent::__construct(
            Users::current() ? 403 : 401,
            $message
        );
    }
}