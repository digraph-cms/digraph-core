<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\CMS;
use Digraph\Helpers\AbstractHelper;

class SessionHelper extends AbstractHelper
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
        $this->session = Session::getInstance($cms->config['site_id']);
    }
}
