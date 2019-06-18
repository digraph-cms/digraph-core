<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

class Session extends \Sesh\Session
{
    public function get($key)
    {
        return @$this->session['digraph'][$key];
    }

    public function set($key, $value)
    {
        return @$this->session['digraph'][$key] = $value;
    }
}
