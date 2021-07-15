<?php

namespace DigraphCMS\Session;

class SessionNamespace
{
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function set(string $key, $value): void
    {
        Session::set($this->name . ':' . $key, $value);
    }

    public function get(string $key)
    {
        return Session::get($this->name . ':' . $key);
    }

    public function unset(string $key): void
    {
        Session::unset($this->name . ':' . $key);
    }

    public function list(): array
    {
        return $this->glob('**');
    }

    public function glob(string $glob)
    {
        $return = [];
        foreach (Session::glob($this->name . ':' . $glob) as $key => $value) {
            $return[substr($key, strlen($this->name) + 1)] = $value;
        }
        return $return;
    }
}
