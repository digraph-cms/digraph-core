<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers;

use Digraph\CMS;
use Digraph\DSO\NounInterface;
use Digraph\Logging\LogHelper;
use Digraph\Urls\Url;
use Flatrr\SelfReferencingFlatArray;

class Package extends SelfReferencingFlatArray implements PackageInterface, \Serializable
{
    protected $startTime;
    protected $treeLevel = 0;
    protected $startTimes = [];
    protected $skips = [];
    protected $log = [];
    protected $cms;
    protected $unfiltered = [
        'response.content',
        'noun',
        'url'
    ];

    public function saveLog($message, $level=null, $id=null)
    {
        if (!$id) {
            $id = md5(
                $this['request.hash'].
                $this->hash('error').
                $this->hash('logging.messages')
            );
        } else {
            $id = md5($id);
        }
        $this['logging.save'] = $id;
        $this['logging.messages.'.$id] = $message;
        $log = $this->cms->helper('logging')->create($this, $level);
        $this['logging.save'] = false;
        return $log;
    }

    public function cacheTag(string $tag)
    {
        if (!$this['cachetags'] || !in_array($tag, $this['cachetags'])) {
            $this->push('cachetags', $tag);
        }
    }

    public function noCache()
    {
        $this['response.cacheable'] = false;
        $this['response.ttl'] = 0;
    }

    public function binaryContent(string $set = null)
    {
        if ($set !== null) {
            unset($this['response.content']);
            $this['response.outputmode'] = 'binary';
            $this['response.binarycontent'] = base64_encode($set);
        }
        return base64_decode($this['response.binarycontent']);
    }

    public function template(string $set = null) : string
    {
        if ($set) {
            $this['response.template'] = $set;
        }
        if ($this['response.template']) {
            return $this['response.template'];
        }
        if ($n = $this->noun()) {
            /*
                This section allows template rules to be saved inside nouns, as
                their data. Setting digraph.template to a template name will
                request that template name.

                Setting it to an array will allow templates to be specified by
                verb, including a '*' wildcard key. For example, setting
                digraph.template.display to 'content-only' would set that object
                to use the content-only template, but only for the display verb.
            */
            if ($t = $n['digraph.template']) {
                if (is_string($t)) {
                    return $t;
                }
                if (is_array($t)) {
                    if (isset($t[$this['url.verb']])) {
                        return $t[$this['url.verb']];
                    }
                    if (isset($t['*'])) {
                        return $t['*'];
                    }
                }
            }
            if ($template = $n->template($this['url.verb'])) {
                return $template;
            }
        }
        return $this->cms->config['templates.default'];
    }

    public function makeMediaFile(string $filename, string $mime = null)
    {
        $this['response.filename'] = $filename;
        if (!$mime) {
            $mime = $this->cms->helper('media')->mime($filename);
        }
        $this['response.mime'] = $mime;
    }

    public function get(string $name = null, bool $raw = false, $unescape = true)
    {
        if (isset($unfiltered[$name])) {
            $raw = true;
        }
        return parent::get($name, $raw);
    }

    public function &cms(CMS &$set = null) : ?CMS
    {
        if ($set) {
            $this->log('Set CMS');
            $this->cms = $set;
        }
        return $this->cms;
    }

    public function noun(NounInterface $set = null) : ?NounInterface
    {
        if ($set) {
            $this->log('Set Noun: '.$set['dso.id'].': '.$set->name());
            $this['noun'] = $set->get();
            $this['response.last-modified'] = $set['dso.modified.date'];
            $this->url($set->url($this['url.verb'], $this['url.args']));
            $this->cacheTag($set['dso.id']);
        }
        if ($this['noun']) {
            return $this->cms->factory()->create($this['noun']);
        } else {
            return null;
        }
    }

    public function url(Url $set = null) : ?Url
    {
        if ($set) {
            $this->log('Set URL: '.$set);
            $this['url']= $set->get();
            $this['fields.page_name'] = $set['text'];
        }
        return new Url($this['url']);
    }

    public function redirect($url, int $code=302)
    {
        $this->log('Redirect: '.$code.': '.$url);
        $this->skipGlob('setup**');
        $this->skipGlob('build**');
        $this->skipGlob('error**');
        $this->skipGlob('template**');
        $this['response.status'] = $code;
        $this['response.redirect'] = "$url";
        $this['response.ready'] = true;
    }

    public function error(int $code, string $message='Unspecified error')
    {
        $this->log("Error $code: $message");
        $this->skipGlob('setup**');
        $this->skipGlob('build**');
        $this['response.status'] = $code;
        $this['error.message'] = $message;
    }

    public function __construct(array $data = null)
    {
        parent::__construct($data);
        $this->startTime = microtime(true);
        $this['uniqid'] = uniqid('package.', true);
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
        //return json encoded package
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
            $this->log[] = $prefix.$message;
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
