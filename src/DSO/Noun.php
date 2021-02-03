<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\DSO;
use Destructr\Factory;

class Noun extends DSO implements NounInterface
{
    const SLUG_ENABLED = false;
    const FILESTORE = false;
    const ROUTING_NOUNS = [];
    const SLUG_ID_LENGTH = 4;
    const HOOK_TRIGGER_PARENTS = true;
    const HOOK_TRIGGER_CHILDREN = true;

    public function __construct(array $data = null, Factory $factory = null)
    {
        parent::__construct($data, $factory);
        $this->merge($factory->cms()->config['defaultnoun']);
        if ($factory->cms()->config['defaultnounbytype.' . $this['dso.type']]) {
            $this->merge($factory->cms()->config['defaultnounbytype.' . $this['dso.type']]);
        }
        $this->resetChanges();
    }

    /**
     * Allow nouns to override configured permissions for their own
     * verb URLs. This plugs directly into the permissions helper, so
     * it operates at the lowest possible level for permissions.
     *
     * Return null to use permissions from config, or a boolean value
     * to override them.
     *
     * @param string $verb
     * @param string $user
     * @return boolean|null
     */
    public function permissions(string $verb, ?string $user): ?bool
    {
        return null;
    }

    public function breadcrumbName(string $verb): ?string
    {
        return null;
    }

    public function addMenuEnabled(string $verb)
    {
        return $verb == 'display';
    }

    public function addMenuFilter(string $type)
    {
        return true;
    }

    public function hook_postEditUrl()
    {
        return $this->url('edit', null, true)->string();
    }

    public function hook_postAddUrl()
    {
        return $this->url('edit', null, true)->string();
    }

    public function infoCard()
    {
        return
        "<article class='digraph-card type-" . $this['dso.type'] . "'>" .
        "<h1>" . $this->title() . "</h1>" .
        $this->content_text(50) .
        "<a href='" . $this->url() . "'>read more</a>" .
            "</article>";
    }

    public function content_text($wordCount = null)
    {
        $text = $this->body();
        $text = \Soundasleep\Html2Text::convert(
            $this->body(),
            [
                'ignore_errors' => true,
                'drop_links' => true,
            ]
        );
        if ($wordCount) {
            $text = preg_replace('/[nosummary].*?[\/nosummary]/', '', $text);
            $text = preg_split('/[ ]+/', $text);
            if (count($text) > $wordCount) {
                $text = array_slice($text, 0, $wordCount);
                $text = implode(' ', $text) . '...';
            } else {
                $text = implode(' ', $text);
            }
        }
        return $text;
    }

    public function cms()
    {
        return $this->factory->cms();
    }

    public function formMap(string $action): array
    {
        $map = [];
        if (!static::SLUG_ENABLED) {
            $map['digraph_slug'] = false;
        }
        return $map;
    }

    public function template($verb = null)
    {
        return null;
    }

    public function body()
    {
        if (!$this['digraph.body']) {
            return null;
        }
        return $this->cms()->helper('filters')->filterContentField(
            $this['digraph.body'],
            $this['dso.id']
        );
    }

    public function insert(): bool
    {
        $this->cms()->helper('hooks')->noun_trigger($this, 'insert');
        return parent::insert();
    }

    public function update(bool $sneaky = false): bool
    {
        if (!$sneaky) {
            $this->cms()->helper('hooks')->noun_trigger($this, 'update');
        } else {
            $this->cms()->helper('hooks')->noun_trigger($this, 'update_sneaky');
        }
        return parent::update($sneaky);
    }

    public function delete(bool $permanent = false): bool
    {
        $this->cms()->helper('hooks')->noun_trigger($this, 'delete');
        if ($permanent) {
            $this->cms()->helper('hooks')->noun_trigger($this, 'delete_permanent');
        }
        return parent::delete($permanent);
    }

    public function fileUrl($id = null, $args = [])
    {
        if ($id === null) {
            $fs = $this->cms()->helper('filestore');
            $files = $fs->list($this, static::FILESTORE_PATH);
            if (!$files) {
                return null;
            }
            $f = array_pop($files);
            $id = $f->uniqid();
        }
        $args['f'] = $id;
        return $this->url(
            'file',
            $args
        );
    }

    public function actions($links)
    {
        if ($this->children()) {
            $links['ordering'] = '!id/order';
        }
        return $links;
    }

    public function isEditable()
    {
        return $this->cms()->helper('permissions')->checkUrl($this->url('edit'));
    }

    public function parentUrl($verb = 'display')
    {
        if ($verb != 'display') {
            return $this->url();
        }
        if ($parent = $this->parent()) {
            return $parent->url();
        }
        return null;
    }

    public function parent()
    {
        $pids = $this->cms()->helper('edges')->parents($this['dso.id'], null, true);
        foreach ($pids as $pid) {
            if ($parent = $this->cms()->read($pid)) {
                return $parent;
            }
        }
        return null;
    }

    public function children(string $sortRule = null, $includeAll = false)
    {
        /* pull list of child IDs from edge helper, create IN clause to get them */
        $cids = $this->cms()->helper('graph')->childIDs($this['dso.id'], 'normal');
        if (!$cids) {
            //short-circuit if edge helper has no children for this noun
            return [];
        }
        /* set up search */
        $search = $this->factory->search();
        /* main search */
        $cids = array_map([$search, 'quote'], $cids);
        $cids = '${dso.id} in (' . implode(',', $cids) . ')';
        $search->where($cids);
        /* if no sort rule, pull it from our own config */
        if (!$sortRule) {
            $sortRule = $this['digraph.order.mode'];
        }
        /* pull sort rule based on manual sort rule if necessary -- this allows
        ordering to be different depending on whether unsorted items are going
        at the top or bottom */
        $manualSort = false;
        if ($sortRule == 'manual' && $this['digraph.order.mode'] == 'manual') {
            $manualSort = true;
            if ($this['digraph.order.unsorted'] == 'before') {
                $unsorted = 'before';
                $sortRule = 'manual_before';
            } else {
                $unsorted = 'after';
                $sortRule = 'manual_after';
            }
        }
        /* sorting rules */
        $cms = $this->factory->cms();
        if (!$sortRule || !$cms->config["child_sorting.$sortRule"]) {
            $sortRule = 'default';
        }
        $rule = $cms->config["child_sorting.$sortRule"];
        if (is_array($rule)) {
            // if a rule is an array, it should be a helper name and method
            // the given method should work like strcmp
            $children = $search->execute();
            usort($children, [$this->cms()->helper($rule[0]), $rule[1]]);
        } else {
            // if a rule is a single string, it should be a SQL ordering clause
            $search->order($rule);
            $children = $search->execute();
        }
        /* manually sort */
        if ($manualSort) {
            // add all manually-specified ids to $manuallySorted, removing from
            // $children as we go
            $manuallySorted = [];
            foreach ($this['digraph.order.manual'] as $ov) {
                foreach ($children as $ck => $cv) {
                    if ($cv['dso.id'] == $ov) {
                        $manuallySorted[] = $cv;
                        unset($children[$ck]);
                    }
                }
            }
            // append/prepend $children to $manuallySorted
            if ($unsorted == 'before') {
                $children = array_merge($children, $manuallySorted);
            } else {
                $children = array_merge($manuallySorted, $children);
            }
        }
        /* return */
        return $children;
    }

    public function name($verb = null)
    {
        if ($this->get('digraph.name')) {
            return $this->factory->cms()->helper('filters')->sanitize($this->get('digraph.name'));
        }
        return $this->get('dso.type') . ' ' . $this->get('dso.id');
    }

    public function title($verb = null)
    {
        if ($this->get('digraph.title')) {
            return $this->factory->cms()->helper('filters')->sanitize($this->get('digraph.title'));
        }
        return $this->name($verb);
    }

    public function link(string $text = null, string $verb = null, array $args = null, bool $canonical = false)
    {
        if (method_exists($this, 'tagLink')) {
            $args = [];
            return $this->tagLink($args);
        }
        return $this->url($verb, $args, $canonical)->html($text);
    }

    public function slug()
    {
        $slugs = $this->cms()->helper('slugs')->slugs($this['dso.id']);
        return @array_shift($slugs);
    }

    public function slugIsAmbiguous()
    {
        if ($this->slug()) {
            return count($this->cms()->helper('slugs')->nouns($this->slug())) > 1;
        } else {
            return false;
        }
    }

    public function url(string $verb = null, array $args = null, bool $canonical = false)
    {
        if (!$verb) {
            $verb = 'display';
        }
        $noun = null;
        if (!$canonical && $this->slug() && !$this->slugIsAmbiguous()) {
            $noun = $this->slug();
        } else {
            $noun = $this->get('dso.id');
        }
        if ($args) {
            $args = $args;
        }
        $url = $this->factory->cms()->helper('urls')->url($noun, $verb, $args);
        $url['object'] = $this['dso.id'];
        $url->noun($this);
        return $this->factory->cms()->helper('urls')->addText($url, $this);
    }
}
