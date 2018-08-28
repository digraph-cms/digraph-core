<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\CMS;

class Session extends AbstractHelper
{
    protected $session;

    public function userID(string $id = null) : ?string
    {
        return $this->session->userID($id);
    }

    public function deauthorize()
    {
        $this->session->deauthorize();
    }


    public function __construct(CMS &$cms)
    {
        parent::__construct($cms);
        $this->session = \Sesh\Session::getInstance();
    }
}
