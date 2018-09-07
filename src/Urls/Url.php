<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Urls;

use Flatrr\FlatArray;
use HtmlObjectStrings\A;

class Url extends FlatArray
{
    const VERBSEPARATOR = '/';
    const ARGINITIALSEPARATOR = '@';
    const ARGSEPARATOR = '@';
    const ARGVALUESEPARATOR = ':';
    const DEFAULTVERB = 'display';
    const HOMEALIAS = 'home';

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        $this->merge([
            'base' => '/',
            'noun' => 'home',
            'verb' => static::DEFAULTVERB,
            'args' => [],
            'text' => 'untitled'
        ]);
    }

    public function html(string $text = null, bool $canonical = false)
    {
        if (!$text) {
            $text = $this['text'];
        }
        $a = new A();
        $a->attr('href', $this->string($canonical));
        $a->content = $text;
        return $a;
    }

    public function string(bool $canonical = false) : string
    {
        return $this->get('base').$this->routeString($canonical);
    }

    public function routeString(bool $canonical = false) : string
    {
        return $this->pathString($canonical).$this->argString();
    }

    public function pathString(bool $canonical = false) : string
    {
        $noun = $this->get('noun');
        if ($canonical && $this->get('canonicalnoun')) {
            $noun = $this->get('canonicalnoun');
        }
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
