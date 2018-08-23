<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Urls;

use Flatrr\SelfReferencingFlatArray;
use HtmlObjectStrings\A;

class Url extends SelfReferencingFlatArray
{
    const VERBSEPARATOR = '/';
    const ARGINITIALSEPARATOR = '@';
    const ARGSEPARATOR = '@';
    const ARGVALUESEPARATOR = ':';
    const DEFAULTVERB = 'display';
    const HOMEALIAS = 'home';

    public function dso(&$dso = null)
    {
        $this->set('dso', $dso);
        return $this->get('dso');
    }

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        $this->merge([
            'base' => '/',
            'noun' => 'home',
            'verb' => static::DEFAULTVERB,
            'args' => []
        ]);
    }

    public function text()
    {
        $out = $this->routeString();
        if ($this['dso']) {
            $out = $this['dso']->name();
        }
        if (!$out) {
            $out = '[none]';
        }
        return $out;
    }

    public function html(string $text = null)
    {
        if (!$text) {
            $text = $this->text();
        }
        $a = new A();
        $a->attr('href', "$this");
        $a['content'] = $text;
        return $a;
    }

    public function string() : string
    {
        return $this->get('base').$this->routeString();
    }

    public function routeString() : string
    {
        return $this->pathString().$this->argString();
    }

    public function pathString() : string
    {
        $noun = $this->get('noun');
        $verb = $this->get('verb');
        if ($noun == static::HOMEALIAS && $verb == static::DEFAULTVERB) {
            return '';
        }
        $out = $noun.static::VERBSEPARATOR;
        if ($verb != static::DEFAULTVERB) {
            $out .= $verb;
        }
        if ($out == '/') {
            $out = '';
        }
        return $out;
    }

    public function argString() : string
    {
        if ($this->get('args')) {
            $args = [];
            $argarr = $this['args'];
            ksort($argarr);
            foreach ($argarr as $key => $value) {
                if ($value === true || !strval($value)) {
                    $args[] = $key;
                } else {
                    $args[] = $key.static::ARGVALUESEPARATOR.$value;
                }
            }
            return static::ARGINITIALSEPARATOR.implode(static::ARGSEPARATOR, $args);
        }
        return '';
    }

    public function __toString()
    {
        return $this->string();
    }
}
