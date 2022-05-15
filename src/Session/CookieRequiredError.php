<?php

namespace DigraphCMS\Session;

class CookieRequiredError extends \Exception
{
    protected $cookieTypes;

    public function __construct(array $cookieTypes, string $message = '')
    {
        $this->cookieTypes = $cookieTypes;
        parent::__construct($message);
    }

    public function cookieTypes(): array
    {
        return $this->cookieTypes;
    }
}
