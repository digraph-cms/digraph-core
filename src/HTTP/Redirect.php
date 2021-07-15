<?php

namespace DigraphCMS\HTTP;

class Redirect extends Response
{
    protected $status = 302;

    public function renderHeaders()
    {
        parent::renderHeaders();
        header('Location: ' . $this->url());
        http_response_code($this->status);
    }
}
