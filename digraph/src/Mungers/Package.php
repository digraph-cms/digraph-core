<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Mungers;

use Digraph\CMS\CMS;
use Digraph\CMS\DSO\NounInterface;
use Digraph\CMS\Urls\Url;

class Package extends \Digraph\Mungers\Package
{
    protected $cms;
    protected $noun;
    protected $unfiltered = [
        'response.content'
    ];

    public function &cms(CMS &$set = null) : ?CMS
    {
        if ($set) {
            $this->cms = $set;
        }
        return $this->cms;
    }

    public function &noun(NounInterface &$set = null) : ?NounInterface
    {
        if ($set) {
            $this->noun = $set;
        }
        return $this->noun;
    }

    public function &url(Url &$set = null) : ?Url
    {
        if ($set) {
            $this->url = $set;
        }
        return $this->url;
    }

    public function redirect($url, int $code=302)
    {
        $this->skipGlob('build/');
        $this['response.status'] = $code;
        $this['response.redirect'] = "$url";
        $this['response.ready'] = true;
    }

    public function error(int $code, string $message='Unspecified error')
    {
        $this->skipGlob('build/');
        $this['response.status'] = $code;
        $this['response.error'] = $message;
    }
}
