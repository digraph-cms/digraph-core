<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\Tag;
use DigraphCMS\URL\URL;

class FORM extends Tag
{
    protected $tag = 'form';
    protected $block = true;

    protected $method = 'post';
    protected $action;
    protected $token;

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    protected static $counter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'form-' . self::$counter++);
    }

    public function children(): array
    {
        $children = parent::children();
        $children[] = $this->token();
        return $children;
    }

    public function token(): Token
    {
        if (!$this->token) {
            $this->token = new Token($this);
        }
        return $this->token;
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes['method'] = $this->method();
        $attributes['action'] = $this->action();
        return $attributes;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function action(): URL
    {
        return $this->action ?? Context::url();
    }

    public function addChild($child)
    {
        if (method_exists($child, 'setForm')) {
            $child->setForm($this);
        }
        return parent::addChild($child);
    }
}
