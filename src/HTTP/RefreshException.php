<?php

namespace DigraphCMS\HTTP;

use DigraphCMS\Context;

class RefreshException extends RedirectException
{
    protected $url, $permanent, $preserveMethod;

    public function __construct(bool $preserveMethod = false)
    {
        parent::__construct(Context::url(), false, $preserveMethod);
    }
}
