<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers;

use Flatrr\SelfReferencingFlatArray;
use Digraph\CMS;
use Digraph\Urls\Url;
use Digraph\DSO\NounInterface;

class Package extends SelfReferencingFlatArray implements PackageInterface, \Serializable
{
    protected $startTime;
    protected $treeLevel = 0;
    protected $startTimes = [];
    protected $skips = [];
    protected $log = [];
    protected $cms;
    protected $noun;
    protected $unfiltered = [
        'response.content'
    ];

    public function get(string $name = null, bool $raw = false)
    {
        if (isset($unfiltered[$name])) {
            $raw = true;
        }
        return parent::get($name, $raw);
    }

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

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        $this->startTime = microtime(true);
    }

    public function skip($name) : bool
    {
        if ($name instanceof MungerInterface) {
            $name = $name->name();
        }
        foreach ($this->skips as $pattern) {
            if (preg_match('/'.$pattern.'/i', $name)) {
                return true;
            }
        }
        return false;
    }

    public function skipGlob(string $pattern)
    {
        //convert glob wildcards into regex
        $pattern = preg_quote($pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = str_replace('\\*\\*', '.*', $pattern);
        $pattern = str_replace('\\*', '[^\/]*', $pattern);
        $pattern = str_replace('\\?', '[^\/]', $pattern);
        $pattern = '^'.$pattern;
        $this->skips[] = $pattern;
        $this->skips = array_unique($this->skips);
    }

    public function resetSkips()
    {
        $this->skips = [];
    }

    public function hash(string $name = null) : string
    {
        return md5($this->serialize($name));
    }

    public function serialize(string $name = null) : string
    {
        return json_encode($this->get($name));
    }

    public function unserialize($serialized, string $name = null)
    {
        $this->set($name, json_decode($serialized, true));
    }

    public function log($message=null)
    {
        if ($message) {
            $prefix = $this->msElapsed().': ';
            $prefix .= str_repeat('  ', $this->treeLevel);
            $this->log[] = $message;
        }
        return $this->log;
    }

    public function mungeStart(MungerInterface $munger)
    {
        $this->log($munger->name().": started");
        $this->startTimes[$munger->name()] = $this->msElapsed();
        $this->treeLevel++;
    }

    public function mungeFinished(MungerInterface $munger)
    {
        $this->treeLevel--;
        $time = $this->msElapsed()-$this->startTimes[$munger->name()];
        $this->log($munger->name().": finished in {$time}ms");
    }

    protected function msElapsed() : int
    {
        return round((microtime(true)-$this->startTime)*(1000));
    }
}
