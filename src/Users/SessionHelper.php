<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\CMS;
use Digraph\Helpers\AbstractHelper;

class SessionHelper extends AbstractHelper
{
    protected $session;

    public function getPersistedData($keep = false)
    {
        $data = $this->get('persistData.' . $_GET['pers_token']);
        if (!$keep) {
            $this->set('persistData.' . $_GET['pers_token'], false);
        }
        return $data;
    }

    public function persistData($data, $noun, $verb, $args = [])
    {
        $token = md5(rand());
        $args['pers_token'] = $token;
        $this->set('persistData.' . $token, $data);
        return $this->cms->helper('urls')->url(
            $noun,
            $verb,
            $args
        );
    }

    public function get($key)
    {
        return $this->session->get($key);
    }

    public function set($key, $value)
    {
        return $this->session->set($key, $value);
    }

    public function getToken(string $name, int $ttl = 3600 * 24): string
    {
        return $this->session->getToken($name, $ttl);
    }

    public function checkToken(string $name, $value, bool $keep = false): bool
    {
        return $this->session->checkToken($name, $value, $keep);
    }

    public function userID(string $id = null): ?string
    {
        return $this->session->userID($id);
    }

    public function userSID(string $id = null): ?string
    {
        return $this->session->userSID($id);
    }

    public function deauthorize()
    {
        $this->session->deauthorize();
    }

    public function __construct(CMS $cms)
    {
        parent::__construct($cms);
        $this->session = Session::getInstance($cms->config['site_id']);
    }
}
