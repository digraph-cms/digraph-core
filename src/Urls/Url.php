<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Urls;

use Flatrr\FlatArray;
use HtmlObjectStrings\A;

class Url extends FlatArray
{
    const VERBSEPARATOR = '/';
    const ARGINITIALSEPARATOR = '?';
    const ARGSEPARATOR = '&';
    const ARGVALUESEPARATOR = '=';
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
            'text' => 'untitled',
            'canonical' => false
        ]);
    }

    public function canonical(bool $set = null)
    {
        if ($set !== null) {
            $this->set('canonical', $set);
        }
        return $this->get('canonical');
    }

    public function html(string $text = null, bool $canonical = null)
    {
        if (!$text) {
            $text = $this['text'];
        }
        $a = new A();
        $a->attr('href', $this->string($canonical));
        $a->content = $text;
        return $a;
    }

    public function string(bool $canonical = null) : string
    {
        return $this->get('base').$this->routeString($canonical);
    }

    public function routeString(bool $canonical = null) : string
    {
        return $this->pathString($canonical).$this->argString();
    }

    public function pathString(bool $canonical = null) : string
    {
        if ($canonical === null) {
            $canonical = $this->get('canonical');
        }
        $noun = $this->get('noun');
        if ($canonical && $this->get('object')) {
            $noun = $this->get('object');
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
        $out = implode('/', array_map(
            function ($e) {
                return urlencode(urldecode($e));
            },
            explode('/', $out)
        ));
        return $out;
    }

    public function argString() : string
    {
        if ($this->get('args')) {
            $args = [];
            $argarr = $this['args'];
            ksort($argarr);
            foreach ($argarr as $key => $value) {
                if ($value === true) {
                    $value = 1;
                } elseif ($value === false) {
                    $value = 0;
                }
                if (!strval($value)) {
                    // it is best to omit args with values that can't be represented as strings
                } else {
                    $args[] = $key.static::ARGVALUESEPARATOR.urlencode($value);
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
